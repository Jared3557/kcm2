<?php

//--- rsm-chain-debug.php ---

ob_start();  // output buffering (needed for redirects, sy-content-header changes)

include_once( '../rc_defines.inc.php' );
include_once( '../rc_admin.inc.php' );
include_once( '../rc_database.inc.php' );
include_once( '../rc_messages.inc.php' );

include_once( 'rsm-chain.inc.php' );
include_once( 'rsm-emitter.inc.php' );
//include_once( 'rsm-form-lib.inc.php' );
include_once( 'rsm-functions.inc.php' );
//include_once( 'kcm2-zLib-functions.inc.php' );
//include_once( 'kcm2-zLib-emitter.inc.php' );
//include_once( 'kcm2-zLib-globals.inc.php' );
//include_once( 'kcm2-zLib-data-roster.inc.php');

//include_once( 'kcm2-zLib-data-games.inc.php' );
//include_once( 'kcm-roster-results-game-edit.inc.php' );

$isRaccoon = TRUE;
if (isset($_GET['local'])) {
    print 'Using Local Session<br>';
    session_start();
}
else if ($isRaccoon) {
    print 'Using Raccoon Session<br>';
    if ( (!isset($_SESSION['rsmChain'])) and (defined('RC_LIVE')) ) {
        //session_destroy ();
        rc_session_initialize();
        if (!isset($_SESSION['rsmChain'])) {
            session_destroy ();
            session_start();
        }
    }
}
else {
    session_start();
}

$emitter = new rsm_emitter_object;
$emitter->rsmHtmlHead_emit('Chain Debug');
$chainDebug = new rsm_chain_debug;
$chainDebug->chd_debug();

exit;

class rsm_chain_debug {

private $rsmChain_ref_root = NULL;
private $rsmChain_ref_shared = NULL;
public  $rsmChain_ref_streams = NULL;
private $rsmChain_ref_curStream = NULL;
private $rsmChain_ref_curStatus = NULL;

private $rsmChain_cur_StreamToken;
private $rsmChain_cur_formStepToken;

function __construct() {
    //--- get session array for all streams and shared
    //if ( ! isset($_SESSION['rsmChain']) ) {
    //    $_SESSION['rsmChain'] = array();
    //}
    $this->rsmChain_ref_root = & $_SESSION['#rsmChain'];  // streams are stored here
    dbg($_SESSION['#rsmChain']);
    // get shared array
    //if ( ! isset($this->rsmChain_ref_root['#rsmShared']) ) {
    //    $this->rsmChain_ref_root['#rsmShared'] = array();
    //}
    
    $this->rsmChain_ref_shared = &$this->rsmChain_ref_root['#rsmShared'];  // stream cannot have name of 'shared'
    // get streams array
    if ( ! isset($this->rsmChain_ref_root['#rsmStreams']) ) {
        $this->rsmChain_ref_root['#rsmStreams'] = array();
    }
    $this->rsmChain_ref_streams = &$this->rsmChain_ref_root['#rsmStreams'];  // stream cannot have name of 'shared'
    
}

function chd_debug() {
    $tag = array();
    $tag['ta'] = '<table style="border-collapse:collapse;border-spacing:0;empty-cells:show;">';
    $tag['thr'] = '<td style="border: 1pt solid black;background-color:#eeffff" colspan="99">';
    $tag['thc'] = '<td style="border: 1pt solid black;background-color:#eeffff">';
    $tag['td'] = '<td style="border: 1pt solid black; padding:6pt 5pt 6pt 5pt;">';
    $tag['sk'] = '<span style="border: 1pt solid gray;padding:2pt 5pt 2pt 5pt; margin:2pt 0pt 2pt 5pt;background-color:#ddddff">';
    $tag['sv'] = '<span style="border: 1pt solid gray;padding:2pt 5pt 2pt 5pt; margin:2pt 0pt 2pt 0pt;background-color:#eeeeff">';
    $tag['se'] = '</span>';
    print $tag['ta'];
    print '<tr>'.$tag['thr'].'Streams</td></tr>';
    print '<tr>';
    print $tag['thc'].'Stream</td>';
    print $tag['thc'].'Step</td>';
    print $tag['thc'].'Values</td>';
    print '</tr>';
    if ($this->rsmChain_ref_shared != NULL) {
        $this->chd_debug_row($tag,'Shared','',$this->rsmChain_ref_shared);
    }
    $prevToken = '';
    if ($this->rsmChain_ref_streams != NULL) {
        foreach ($this->rsmChain_ref_streams as $token => $stream) {
            if (is_array($stream)) {
                foreach($stream as $step => $posted) {
                    $this->chd_debug_row($tag,$token==$prevToken?'':$token,$step,$posted);
                    $prevToken = $token;
                }
            }
        }
    }
    print '</table>';
    
    print PHP_EOL . '<br><br>';
    $this->chd_debug_shared($tag);
}

function chd_debug_shared($tag) {
    print PHP_EOL . $tag['ta'];
    print PHP_EOL . '<tr>'.$tag['thr'].'Shared</td></tr>';
    print PHP_EOL . '<tr>';
    print PHP_EOL . PHP_EOL . $tag['thc'].'Key</td>';
    print PHP_EOL . $tag['thc'].'Value(s)</td>';
    print PHP_EOL . '</tr>';
    foreach ($this->rsmChain_ref_shared as $key => $value) {
        $this->chd_debug_shared_row($tag, $key, $value);
    }
    print PHP_EOL . '</table>';
}

function chd_debug_shared_row($tag, $key, $value) {
    print PHP_EOL . '<tr>';
    print PHP_EOL . $tag['td'].$key.'</td>';
    print PHP_EOL . $tag['td'];
    $this->outVariable($value);
    print '</td>';
    //?????? should add code to handle array values
    print PHP_EOL . '</td>';
    print PHP_EOL . '</tr>';
   
}


function chd_debug_row($tag, $stream,$step,$ar) {
    print PHP_EOL . '<tr>';
    print PHP_EOL . $tag['td'].$stream.'</td>';
    print PHP_EOL . $tag['td'].$step.'</td>';
    print PHP_EOL . $tag['td'];
    $sep = '';
    if (is_array($ar) ) {
        foreach($ar as $key => $value) {
       //     PHP_EOL . print $sep . $key . '=>' . $value;
             if (is_array($value)) {
                 $s = 'Array: ';
                 $sep = '';
                 foreach($value as $ak => $av) {
                    $s .= $sep . $ak . '(' . $av . ')';
                    if (is_array($av)) {
                         $s .= '???';
                    }
                    $sep = ', ';
                 }
                 print PHP_EOL . $tag['sk'] . $key . $tag['se'] . $tag['sv'] . $s . $tag['se'];
             }
             else {
                 print PHP_EOL . $tag['sk'] . $key . $tag['se'] . $tag['sv'] . $value . $tag['se'];
             }
        $sep = ', ';
        }
    }
else {
    dxxbg('skipped',$ar);
}
    print PHP_EOL . '</td>';
    print PHP_EOL . '</tr>';
   
}

function outVariable($a) {
  // echo '<br><br>$$$$$'; var_dump($a); echo '<br><br>';
   // print '#'.$a.'<br>';
    if (is_null($a)) {
       print 'NULL';
       return;
    }
    if (is_bool($a)) {
        if ($a===TRUE)
           outString( 'TRUE');
        else if ($a===FALSE)
           outString( 'FALSE');
        return;
        }
    if (is_array($a)) {
         outString(var_export($a,true));
        return;
    }
    if (is_object($a)) {
        print '{[Object]';
        outString(var_export($a,true));
       // var_dump($a);
        return;
    }
    if (strpos($a,'`') >= 1) { //SQL query string
        print '<span style="overflow-wrap:break-word;border:1px solid gray; background-color:#ffffcc;">'.$a.'</span>';
        return;
    }
    print outString($a);
}

}

function outString($s) {
    $ss = htmlspecialchars( $s, ENT_QUOTES );
    //print $ss;
    //print '<span style="display:inline;">'.$ss.'</span>';
    print '<span style="diaplay:inline-block; border:1px solid gray; padding:2pt;background-color:#ffffcc;">'.$ss.'</span>';
}

?>