<?php

//--- draff-chain.inc.php ---


const DRAFF_TYPE_MSG_STATUS   = 1; // status - such as record saved
const DRAFF_TYPE_MSG_FIELD    = 2; // field error
const DRAFF_TYPE_MSG_ERROR    = 3; // continue execution after displaying error (seldom used)
const DRAFF_TYPE_MSG_FATAL    = 4; // stop execution after displaying draff-message-error
const DRAFF_TYPE_RELAUNCH_IF_ERROR    = '*error*';

//============================================================
// app_data_posted Class
//============================================================

class draff_appData {
// appData is not saved in session data automatically
// appData must use the chain do do this
// the chain is responsible for saving session data
// and the chain contains functions to make this easy

function __construct() {
    // do not put anything here - unless self::__construct(); is added to all scripts that create appData
}

} // end class

class Draff_SessionNode {
public $ses_session_array;    // public for debugging
public $ses_session_path;     // public for debugging

function __construct($pathArray=array()) {
    $this->ses_session_path = implode (' // ',$pathArray);
    if ( !isset($_SESSION['@draffRoot']) ) {
        $_SESSION['@draffRoot'] = array();
    }
    $currentNode = &$_SESSION['@draffRoot'];
    foreach($pathArray as $key) {
        if ( !isset($currentNode[$key]) ) {
            $currentNode[$key] = array();
        }
        $currentNode = &$currentNode[$key];
    }
    $this->ses_session_array = &$currentNode;
}

function ses_get ($key, $default=NULL) {
    $value = $this->ses_session_array[$key] ?? $default;
    return $value;
}

function ses_set ($key, $value) {
    $this->ses_session_array[$key] = $value;
}

function ses_exists ( $key) {
    return isset($this->ses_session_array[$key]);
}

function ses_read (&$variable, $fieldKey ) {
    if ( isset($this->ses_session_array[$fieldKey]) ) {
        $variable = $this->ses_session_array[$fieldKey];
    }
}

function ses_unset ($key) {
    if ( !empty($this->chn_chain_key))  {
        $this->adp_session->ses_unset($this->ses_session_array,$key);
    }
}

function ses_arrayMerge($array) {
    $this->ses_session_array = array_merge($this->ses_session_array, $array);
}

function ses_arrayIsEmpty() {
    return empty($this->ses_session_array);
}

function ses_arrayGet() {
    return $this->ses_session_array;
}

function ses_arrayGetCount() {
    return count($this->ses_session_array);
}

function ses_arrayClear() {
    $this->ses_session_array = array();
}

} // end class

//============================================================
// Chain Object Class
//============================================================

class Draff_Chain {
// the chain object controls the streams and steps
// generally speaking, the chain object selects the current stream and step
// and then many functions concerning the current stream and step are handled by the chain object
// and not directly by the stream and step objects

public  $chn_submit = array();   // submit from last step submitted
public  $chn_posted;  // posted from form submits, plus optional additional fields, unique for each chain-id
public  $chn_shared;  // shared between tabs, one value for all tabs and all chain ids
public  $chn_status;  // public status of form/chain - not posted/form data - used by apps
public  $chn_state;  // status of chain - not posted/form data - private to chain (critical data which should be hidden)
private $chn_chains;  // all chains
public  $chn_messages;  // array of messages
public  $chn_formErrors;  // array of form errors

public  $chn_url_rsmMode  = NULL;
public $chn_url_params   = array();
private $chn_url_scriptName = '';
private $chn_url_path = '';
private $chn_url_rsmChain = NULL;


private  $chn_chain_key = NULL;    // stream key (stream key can be empty)
private  $chn_chain_mode = NULL;


// these items are passed to functions in the current script
private $chn_app_globals; // object intended for entire set of apps - and a single definition for entire set of apps, not just one script
private $chn_app_data;  // data local, and defined by script, has abstract methods
private $chn_app_emitter = NULL;
private $chn_app_emitterName = '';

// there can be many streams stored in $_SESSION
// but generally speaking the chain is involved with only the stream specified by the current stream token
// ... a steam has no token until there is posted data - selecting a new page must result in posted data

//--- the current form key is stored in the "stream"
private  $chn_form_array;   // array of step objects   // should be private
private  $chn_form_firstKey = NULL;  // current step object
private  $chn_form_current  = NULL;  // current step object
private  $chn_form_curKey   = NULL;  // current step key - associated with stream

private $chn_error_count = 0;

function __construct( $scriptData, $appGlobals, $emitterClassName ) {

    $urlIsHttps   = isset($_SERVER['HTTPS']) && filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN);
    $urlPort = ($_SERVER['SERVER_PORT'] == '80') ? '' : (':' . $_SERVER["SERVER_PORT"]);
    $urlProtocol = $urlIsHttps ? 'https' : 'http';
    $urlProtocol = $urlProtocol . '://' . $_SERVER['SERVER_NAME'] . $urlPort;
    $this->chn_url_scriptName  = $_SERVER['SCRIPT_NAME'];
    $this->chn_url_path = $urlProtocol . $this->chn_url_scriptName;
    $this->chn_url_params   = $_GET;
    $this->chn_url_rsmMode  = isset($this->chn_url_params['rsmMode']) ? $this->chn_url_params['rsmMode'] : NULL;
    $this->chn_chain_key = $this->chn_url_params['rsmCST'] ?? NULL;
    if ( empty($this->chn_chain_key) ) {
        $url = $this->chn_url_redirect(array('rsmCST'=>uniqid()));  // start new stream
    }
    if ( !empty($this->chn_chain_key) ) {
        $this->chn_chains = new Draff_SessionNode(array('@rsmChains'));
        $this->chn_posted = new Draff_SessionNode(array('@rsmChains',$this->chn_chain_key,'@posted'));
        $this->chn_status = new Draff_SessionNode(array('@rsmChains',$this->chn_chain_key,'@status'));  //???????
        $this->chn_state  = new Draff_SessionNode(array('@rsmChains',$this->chn_chain_key,'@state'));  //???????
        $this->chn_shared = new Draff_SessionNode(array('@shared'));  //???????
        $this->chn_messages = new Draff_SessionNode(array('@rsmMessages'));
        $this->chn_formErrors = new Draff_SessionNode(array('@rsmFormErrors'));
    }
    $this->chn_app_emitterName = $emitterClassName;
    $this->chn_app_globals = $appGlobals;
    $this->chn_app_data = $scriptData;
    $this->chn_form_array = array();
    if (isset($this->chn_url_params['dbg']) ) {
        dbg($this->chn_chains);
    }
    if (isset($this->chn_url_params['pst']) ) {
        dbg($this->chn_posted);
    }
    if (isset($this->chn_url_params['chn']) ) {
        dbg($this->chn_state,$this->chn_status,$this->chn_posted);
    }
}

//============================================================
// Form functions
//============================================================

function chn_form_register($stepKey,$stepClassName) {
    // at this point the chain token is established
    // all steps in the script must be added (all steps needed for post processing,stream_root,  etc)
    $newStep =  new $stepClassName($stepKey, $stepClassName);  // draff_formStep object
    $this->chn_form_array[$stepKey] = $newStep;
    if (empty($this->chn_form_firstKey)) {
        $this->chn_form_firstKey = $stepKey;
    }
}

function chn_form_launch($formIndex=NULL) {
       // $this->chn_state->ses_set('#curFormId',$this->chn_form_curKey);  //???? chain may not exist
        //?????????????? what happens now ???????
       // exit;  // after everything has been outputted (to the web page) all done
    if ( $formIndex === DRAFF_TYPE_RELAUNCH_IF_ERROR ) {
        if ( $this->chn_formErrors->ses_arrayIsEmpty() ) {
            return;
        }
        $this->chn_url_redirect();
    }
    if ( $formIndex===NULL ) {  // will always be true after a redirect ?????
        if ( isset($this->chn_url_params['rsmForm'] ) ) { // argument takes priority
            // only use once - do not inherit as specified form must be able to launch another form
            $formIndex = $this->chn_url_params['rsmForm'];
            unset($this->chn_url_params['rsmForm']);
        }
        else if ( $this->chn_state->ses_exists( '#curFormId') ) {
            $formIndex = $this->chn_state->ses_get('#curFormId');
        }
        else {
            $formIndex = $this->chn_form_firstKey;
        }
        $this->chn_form_curKey = $formIndex;
        $this->chn_form_current = $this->chn_form_array[$this->chn_form_curKey];
        $this->chn_app_emitter = new $this->chn_app_emitterName ($this->chn_app_globals,$this->chn_form_current,'draff-html-select'); // ??????????? - eliminate style - put in content
        if ( !empty($_POST) ) {
            $this->chn_messages->ses_arrayClear();   //??? is this the best place for this
            // handle checkbox fields when checkbox is unchecked
            foreach ($_POST as $key=>$value) {
                if ( substr($key,0,6) == 'rsmCB_') {
                    // we now have a hidden field for a checkbox when it's unchecked
                    $unhiddenKey = substr($key,6);
                    if (!isset($_POST[$unhiddenKey])) {
                        $_POST[$unhiddenKey] = $value;  // now the unchecked checkbox has a value
                    }
                    unset($_POST[$key]);
                }
            }
            $this->chn_posted->ses_arrayMerge($_POST);
            // process submit into possible sub-components
            $submitString = isset( $_POST['submit']) ?   $_POST['submit'] : '';
            $this->chn_submit = explode('_',$submitString);
            // save state
            $this->chn_state->ses_set('#postedMicro',draff_getMicroTime() );
            $this->chn_state->ses_set('#postedWhen', date( "Y-m-d H:i:s" ) );  //???? not proxy time
            // finish processing and have form handle the submit
            // $this->chn_app_emitter = new $this->chn_app_emitterName ($this->chn_app_globals,$this->chn_form_current,'draff-html-select'); // ??????????? - eliminate style - put in content
            $this->chn_state->ses_set('#curFormId',$this->chn_form_curKey);  //???? chain may not exist
            $this->chn_form_current->drForm_processSubmit ( $this->chn_app_data, $this->chn_app_globals, $this, $this->chn_submit);
            exit;  // should never get here - above submit must end with a redirect
        }
        $this->chn_state->ses_set('#curFormId',$this->chn_form_curKey);  //???? chain may not exist
        // launch output functions
        $this->chn_form_current->drForm_form_addErrors( $this );  // move errors from session to current form ???? is here the best place
        $this->chn_form_current->drForm_initData( $this->chn_app_data, $this->chn_app_globals, $this );
        $this->chn_form_current->drForm_initFields( $this->chn_app_data, $this->chn_app_globals, $this );
        $this->chn_form_current->drForm_initHtml( $this->chn_app_data, $this->chn_app_globals, $this, $this->chn_app_emitter );
        $this->chn_form_current->drForm_outputPage( $this->chn_app_data, $this->chn_app_globals, $this, $this->chn_app_emitter );
        //?????????????? what happens now ???????
        exit;  // after everything has been outputted (to the web page) all done
    }
    // only get here if formIndex is specified and then there is always a redirect to new form
    $this->chn_form_curKey = $formIndex;
    $this->chn_form_current = $this->chn_form_array[$this->chn_form_curKey];
    $this->chn_state->ses_set('#curFormId',$this->chn_form_curKey);
    $this->chn_state->ses_set('#curWhen',date( "Y-m-d H:i:s" ));
    $url = $this->chn_url_redirect(array('rsmForm'=>$this->chn_form_curKey));
}

function chn_form_conclude($statusTypeInfoMessageEtc) {
    // start a new chain and redirect - ??????
}

//============================================================
// url functions
//============================================================

function chn_url_redirect() {
    $args = func_get_args();
    $url =  call_user_func_array(array($this,'chn_url_getString'),func_get_args());
    //$url = $this->chn_url_getString($fileName,$inherit,$argumentOverrides);
    session_write_close();  // make sure session vars get saved
    $url = str_replace( '&amp;', '&', $url );  // in case & was inserted as an html entity
    header( "Location: {$url}" );  // do the redirection
    exit();  // make sure everything gets closed properly
}

function chn_url_getString() {
    // call_user_func_array('func',$myArgs);
    // This gets a url string which can be used for redirecting or links
    // There are three optional arguments, all of different types
    //  ...  can be any order, and can have multiple arrays (but not the other types)
    //  FALSE or TRUE:  inherit the arguments
    //  string:  the script name with optional path, default is current script
    //       if the string has arguments, they will remain as-is
    //       ... dangerous as no checking for duplicate argument - safer to use arrays
    //  array(s); list of arguments to put in url, $key => value
    //      if value is empty, this argument will be deleted from the url
    //      if value is '*', this argument will be retrieved from the original url
    //       ... this option only makes sense when inherit arguments is FALSE
    // Note: arguments using more than a limited set of character
    //       must be sanitized before calling this function
    //       The standard safe characterars: ALPHA  DIGIT  _ - / . ~
    //         all else must be avoided including ? & % for hex, and many others
    //       need to pre-sanitize arguments coming from free-form fields
    //       but probably without exceptions these values should be posted,
    //          not passed as arguments
    // possible should add $value = urlencode ( $value ) on all arguments
    // $safeUrlParameter = urlencode( urldecode( $maybe_Unsfe_UrlParameter_Or_AlreadyEncoded ) );
    $functionArgs = func_get_args();
    $script = $this->chn_url_scriptName;
    $useExistingArgs = TRUE;
    $argsArray = array();
    foreach ($functionArgs as $arg) {
        if ( is_string($arg) ) {
            $script = $arg;
        }
        else if ( is_bool($arg) ) {
            $useExistingArgs = $arg;
        }
        else if ( is_array($arg) ) {
             $argsArray = array_merge ($argsArray, $arg);
             $x = $argsArray + $arg;
             // multiple arrays of arguments allowed
        }
    }
    // script: DRAFF_TYPE_URL_SELF or name of script - script cannot contain args, path is ok
    // $useExistingArgs: URL_ARG_KEEP, URL_ARGS_DISCARD
    // $urlNewArgs: array of new args (can also override or keep specific existing args)
    $args = $useExistingArgs ? $this->chn_url_params : array();
    if ( is_array($argsArray) ) {
        foreach ($argsArray as $key => $value) {
            //$value = urlencode( urldecode( $value ) ); // just to play it safe
            if ( empty($value) ) {
                if (isset($args[$key]) ) {
                    unset($args[$key]);
                }
            }
            else if ($value==='*' ) {
                // this option only makes sense when useExistingArgs is false
                // use argument from original url
                if (isset($this->chn_url_params[$key]) ) {
                    $args[$key] = $this->chn_url_params[$key];
                }
           }
           else {
                $args[$key] = $value;
            }
        }
    }
    $url = $script;
    $punc = ( strpos($script,'?')===FALSE)  ? '?' : '&';
    foreach ($args as $key => $value)  {
        $url = $url . $punc . $key . '=' . $value;
        $punc = '&';
    }
    return $url;
}

//============================================================
// misc functions
//============================================================

function chn_readPostedField( &$variable, $fieldId, $recordId = NULL) {
    $postId = ($recordId===NULL) ? $fieldId : ($fieldId . '_' . $recordId);
    $this->chn_posted->ses_read($variable, $postId);
}

function chn_clearAllPosted() {
    $this->chn_posted->ses_arrayClear;
}

function chn_message_field( $a1, $a2, $a3=NULL) {
    // There are two or three arguments
    // The final sting is the error/status message
    // If there is one string
    //   (1) The string is the error message
    //   (2) There is no field Id
    //   (3) The default type is a status message - otherwise message type is necessary
    // If there are two strings
    //   (1) The first string is the field Id
    //   (2) The second string is the error message
    //   (3) The only type is a field error (subject to change)
    // If there are no strings or over two strings it is a system error
    if ($a3==NULL) {
        $fieldId = $a1;
        $message = $a2;
    }
    else {
        $fieldId = $a1 . '_' . $a2;
        $message = $a3;
    }
    $this->chn_formErrors->ses_set($fieldId, $message);
}

function chn_message_set( ) {
    // There are up to three arguments
    // There must be exactly one or two stings
    // The final sting is the error/status message
    // If there is one string
    //   (1) The string is the error message
    //   (2) There is no field Id
    //   (3) The default type is a status message - otherwise message type is necessary
    // If there are two strings
    //   (1) The first string is the field Id
    //   (2) The second string is the error message
    //   (3) The only type is a field error (subject to change)
    // If there are no strings or over two strings it is a system error
    $messsage = NULL;
    $errType = DRAFF_TYPE_MSG_STATUS;
    $fieldId = NULL;
    $isSystemError = NULL;
    $functionArgs = func_get_args();
    foreach ($functionArgs as $arg) {
        if ( is_string($arg) ) {
            $messsage = $arg;
        }
        else if ( is_numeric($arg) ) {
            $errType = $arg;
        }
        else if ($arg === TRUE) {
            $errType = DRAFF_TYPE_MSG_FATAL;
        }
        else if ($arg === FALSE) {
            $errType = DRAFF_TYPE_MSG_ERROR;
        }
        else {
        //  other types not valid - should generate system error ??????
        }
    }
    $errorCount = $this->chn_messages->ses_arrayGetCount();
    $errorArray = array( 'message'=>$messsage, 'type'=>$errType, 'fieldId'=>NULL);
    $this->chn_messages->ses_set($errorCount+1, $errorArray);
}

private function chn_periodicCleaning () {
    // need way to clean out old posted data - and if done too soon need message on next submit of old tab
    //    this could be done periodically, maybe once every 5 minutes and/or how many chains there are
    //    mostly need to clean data of either closed or unclosed tabs that are very old
}

} // end class

?>