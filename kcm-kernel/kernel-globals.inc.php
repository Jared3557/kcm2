<?php

// kernel-globals.inc.php

// security Common to kcm, payroll, gateway, etc
// these classes will be extended for specific security requirements for each system

define ('DRAFF_TYPE_LIVE', RC_LIVE);

//--- move below to kcm kernel
CONST SESSION_SECURITY        = '$kcmSecurity';
CONST SESSION_SECURITY_EVENTS = '$events';
CONST SESSION_SECURITY_PROXY  = '$kcmProxy';

const RGB_HISTORY_SAVE_TRUE = 0;
const RGB_HISTORY_SAVE_FALSE = 1;

abstract class kcmKernel_globals {
public $gb_user;   // current user who can be proxy, usually same as gb_owner, security levels are never higher than gb_owner
public $gb_owner;  // current user who has logged on - use this one for modBy field
public $gb_isLoggedIn;
public $gb_db = NULL;
public $gb_sql = NULL;
public $gb_pdo;
public $gb_systemTitle = '';
public $gb_banner_image_kidchess = 'kcm_banner_kidchess.gif';
public $gb_banner_image_system = ''; // gateway, roster, etc
public $gb_cssFile_htmlCode = array();   // html code for css files for this system
public $gb_isExport = FALSE;
public $gb_session_events;
public $gb_session_proxy;
public $gb_banner;

abstract function gb_ribbonMenu_Initialize($chain, $emitter, ...$overrides);

function __construct($systemTitle, $imageFileName) {
    // do in kcm kernel
   // $session = draff_get_session();
    register_shutdown_function( "fatal_handler" );
    $this->gb_session_events = new Draff_SessionNode(array('@kcmSecurity','@events'));
    $this->gb_session_proxy = new Draff_SessionNode(array('@kcmSecurity','@proxy'));
  //  $session->ses_addPath(SESSION_SECURITY, SESSION_DRAFF_TYPE_ROOT,  SESSION_SECURITY);
  //  $session->ses_addPath(SESSION_SECURITY_EVENTS, SESSION_SECURITY, SESSION_SECURITY_EVENTS);
  //  $session->ses_addPath(SESSION_SECURITY_PROXY,  SESSION_SECURITY, SESSION_SECURITY_PROXY);
    $this->gb_systemTitle      = $systemTitle;
    $this->gb_logoImageFile    = $imageFileName;
    $this->gb_banner = new KcmKernel_Banner;
  //  $this->gb_db               = rc_getGlobalDatabaseObject();  // to be eliminated
  //  $this->gb_sql              = new krnLib_sql_database($this->gb_db);   // to be eliminated
    //$this->gb_pdo              =  new raccon_database_engine;
    if (rc_isAdmin()) {
        $dbuser = RC_DB_USER_ADMIN;
        $dbpass = RC_DB_PW_ADMIN;
    }
    else {
        $dbuser = RC_DB_USER_NOT_ADMIN;
        $dbpass = RC_DB_PW_NOT_ADMIN;
    }
    $this->gb_pdo =  new draff_database_engine(RC_DB_DATABASE_HOST, RC_DB_DATABASE_NAME, $dbuser, $dbpass, 'utf8_unicode_ci');
    $this->rsmDbe_modWho = $_SESSION['Admin']['StaffId'] ?? '';  //??????????????
    //$this->gb_chain            = new Draff_Chain();
    //--- Define Standard Kcm-Gateway css files
  //&&&&& $this->gbKrn_add_cssFile( 'css/rc_common.css', 'all','../../'); //&&&&&
    $this->gbKrn_add_cssFile( 'kcm/kcm-kernel/kernel-styleSheet.css','all','../../');
    $this->gbKrn_add_cssFile( 'kcm/draff/draff-styleSheet.css', 'all','../../');

}

// function krnGb_setSystemStrings($systemTitle, $imageFileName, $emitterName) {
//     $this->gb_systemTitle      = $systemTitle;
//     $this->gb_logoImageFile    = $imageFileName;
// }

function gb_forceLogin () {
    $loginAuthorization = new kcmKernel_security_authorizeLogin;
    $loginAuthorization->krnLogin_forceLogin ($this);
}

function gbKrn_add_cssFile($cssPath, $media="all", $levelStr="") {
	$timestamp = filemtime( __DIR__ . "/" . $levelStr . $cssPath );
    $this->gb_cssFile_htmlCode[] = "<link rel='stylesheet' type='text/css' media='{$media}' href='{$levelStr}{$cssPath}?v={$timestamp}'>";
}

 function gb_addGlobalEmitterOptions($emitter) {
    $emitter->emit_options->addOption_styleFile( 'kcm/kcm-kernel/kernel-styleSheet.css','all','../../');
    $emitter->emit_options->addOption_styleFile( 'kcm/draff/draff-styleSheet.css', 'all','../../');
   }
    
    function gb_getNow() {
    // need to use this function in more places ???????????????????????????
    // need to use the proxy to ovedrride the current date/time
    // do not use this function for logging, etc
	return date( "Y-m-d H:i:s" );
}

    function gb_set_title($bannerTitle1, $bannerTitle2='',$pageTitle='') {
        $this->krmEmit_bannerTitle1 = $bannerTitle1;
        $this->krmEmit_bannerTitle2 = $bannerTitle2;
        $this->krmEmit_pageTitle = empty($pageTitle) ? $bannerTitle1 : $pageTitle ;  // for bookmarks, tabs, etc
    }
    
    function gb_output_form ( $appData, $appChain, $appEmitter, $form ) {
        $appEmitter = $appChain->chn_register_emitter(new Draff_Emitter_Html($form));
        $this->gb_addGlobalEmitterOptions($appEmitter);
        $form->drForm_form_addErrors( $appChain );  // move errors from session to current form ???? is here the best place
        $form->drForm_initData( $appChain->chn_app_data, $appChain->chn_app_globals, $appChain );
        $form->drForm_initFields( $appChain->chn_app_data, $appChain->chn_app_globals, $appChain );
        $form->drForm_initHtml( $appChain->chn_app_data, $appChain->chn_app_globals, $appChain, $appChain->chn_app_emitter );
        $appEmitter->zone_htmlHead();
        $appEmitter->zone_body_start($appChain, $form);
        $appEmitter->drOutputIfNotReport(PHP_EOL.'<div class="zone-ribbon-group">');  // div so css can hide when printing
        $this->gb_banner->krnEmit_banner_output($this, $appEmitter);
        $appEmitter->zone_messages($appChain, $form);
        $this->gb_menu->drMenu_emit_menu($appEmitter);
        $appEmitter->drOutputIfNotReport(print PHP_EOL.'</div>');
        $form->drForm_outputHeader ( $appData, $this, $appChain, $appEmitter );
        $form->drForm_outputContent ( $appData, $this, $appChain, $appEmitter );
        $form->drForm_outputFooter  ( $appData, $this, $appChain, $appEmitter );
        $appEmitter->zone_body_end();
    }


} // end class


class kcmKernel_security_user {
public $krnUser_nowDate        = NULL;
public $krnUser_nowTime       = NULL;
public $krnUser_nowDateTime    = NULL;
public $krnUser_staffId        = NULL;
public $krnUser_staffLongName  = '';
public $krnUser_staffShortName = '';
public $krnUser_loginId        = NULL;      // from role table
public $krnUser_loginName      = '';
public $krnUser_isSysAdmin     = FALSE;
public $krnUser_isProxy        = FALSE;

function __construct($appGlobals, $actualUser = NULL) {
    $this->krnUser_nowDateTime = rc_getNow();
    $this->krnUser_nowDate = substr($this->krnUser_nowDateTime,0,10);
    $this->krnUser_nowTime = substr($this->krnUser_nowDateTime,11,8);
	if ( !isset( $_SESSION['Admin']['StaffId'] )) {
        $this->krnUser_loginId = NULL;
    }
    else {
        $this->krnUser_loginId = $_SESSION['Admin']['LoginId'];
        $this->krnUser_loginName = $_SESSION['Admin']['LoginName'];
        $this->krnUser_staffId = $_SESSION['Admin']['StaffId'];
        $this->krnUser_setName($appGlobals);
        $row = $this->krnUser_getRights($appGlobals);  // can get app rights with row
        $appGlobals->gb_isLoggedIn = TRUE;
    }
    if ($actualUser!=NULL) {
        $this->krnUser_setProxyUser($appGlobals, $actualUser); // only done if applicable
    }
}

function krnUser_setProxyUser($appGlobals, $actualUser) {
    $this->krnUser_isProxy    = FALSE;
    if ( $this->krnUser_loginId == NULL) {
        return;
    }
    if ( !$actualUser->krnUser_isSysAdmin) {
        // only sysAdmins can be proxy (maybe in future can add more for view?)
       return;
    }
    $proxyDate = $appGlobals->gb_session_proxy->ses_get( '#proxyDate' );
    if ( !empty($proxyDate)) {
        $this->krnUser_isProxy    = TRUE;
        $this->krnUser_nowDate = $proxyDate;
    }
    $proxyTime = $appGlobals->gb_session_proxy->ses_get( '#proxyTime' );
    if ( !empty($proxyTime)) {
        $this->krnUser_isProxy    = TRUE;
        $this->krnUser_nowTime    = $proxyTime;
    }
    $this->krnUser_nowDateTime = $this->krnUser_nowDate . ' ' . $this->krnUser_nowTime;
    $proxyLoginId = $appGlobals->gb_session_proxy->ses_get( '#proxyLoginId' );
    if ( !empty($proxyLoginId)) {
        $this->krnUser_isProxy    = TRUE;
        $result = $appGlobals->gb_pdo->rsmDbe_execute( "SELECT * FROM `st:loginidentity` where `sLI:LoginId` = '{$proxyLoginId}'");
        $row=$result->fetch();
        $this->krnUser_staffId   = $row['sLI:@StaffId'];
        $this->krnUser_loginId   = $row['sLI:LoginId'];
        $this->krnUser_loginName = $row['sLI:LoginName'];
        $this->krnUser_setName($appGlobals);
    }
}

function krnUser_setName($appGlobals) {
    $staff = $appGlobals->gb_pdo->rsmDbe_readRecord('dbRecord_staff',$this->krnUser_staffId);

    //$row = $appGlobals->gb_sql->sql_readSingleRecord( '*', 'st:staff',  'sSt:StaffId',  $this->krnUser_staffId, __FILE__, __LINE__);
    $this->krnUser_staffLongName = $staff->sSt_firstName . ' ' . $staff->sSt_lastName;
    $this->krnUser_staffShortName = $staff->sSt_shortName;
}

function krnUser_getRights($krnGlobals) {
    //$db = $krnGlobals->gb_db;
    //$sql = $krnGlobals->gb_sql;
    if ( $this->krnUser_loginId==NULL) {
        return;
    }
    $query = "SELECT `st:roledefinition`.* FROM `st:loginidentity`
        INNER JOIN `st:roledefinition` ON `sLI:@RoleId` = `sRD:RoleId`
        WHERE (`sLI:LoginId` = '{$this->krnUser_loginId}')
        AND (`sLI:HiddenStatus` = " . RC_HIDDEN_SHOW .")
        AND (`sRD:HiddenStatus` = " . RC_HIDDEN_SHOW .")";
    $result = $krnGlobals->gb_pdo->rsmDbe_execute($query);  //???? specify one row required
    //$result = $db->rc_query( $query );
    if ( $result->rowCount() == 0) {
        draff_errorTerminate( $query );
    }
    $row=$result->fetch();
    $this->krnUser_isSysAdmin = ($row['sRD:SystemAccess'] == 3);
    return $row;  // so can get other rights specific to an application
}

} // end class

class kcmKernel_security_authorizeLogin {

function krnLogin_forceLogin ($kernelGlobals) {
    $fn = basename($_SERVER['PHP_SELF']);
    if ( rc_isAdmin()) {  // is logged in
        $pSubmitArg =  isSet($_GET['Submit']) ? $_GET['Submit'] : NULL;
        if ($pSubmitArg == 'logout') {
            $this->krnLogin_process_logout($kernelGlobals);
            exit;
        }
        if ($pSubmitArg == 'editProfile') {
            $this->krnLogin_process_editProfile();
            exit;  // raccoon has taken over this process and will redirect back to kcm
        }
       return;  // logged in succesfully
    }
    else { // not logged in
        $this->krnLogin_process_login($kernelGlobals);
        exit;  // raccoon has taken over this process and will redirect back to kcm
    }
}

function krnLogin_process_login($kernelGlobals) {
        $emitter = new Draff_Emitter_Html (NULL); // $kernelGlobals,'draff-html-default');
        $emitter->emit_options->set_title('\'Kidchess Coach Login\'');
        $emitter->zone_htmlHead();
		print PHP_EOL;
		print PHP_EOL . '<body class="draff-zone-body-normal">';
        $url = rc_reconstructURL();
 	    print PHP_EOL . PHP_EOL . "<form class='draff-zone-form-normal' action='../../admin?do=login' method='post' autocomplete='off'>\n";
        $kernelGlobals->gb_banner->krnEmit_banner_output($kernelGlobals, $emitter );
        rc_clearMessages();
		print PHP_EOL . PHP_EOL .'<div class="zone-content-scrollable theme-panel">';
        $this->kernel_showAdminLoginPage('../../');
		print PHP_EOL . '</div>';
		print PHP_EOL . '</form>';
		print PHP_EOL . '</body>';
		print PHP_EOL . '</html>';
        exit;  // no more processing of php script - MUST login first
}

function kernel_showAdminLoginPage( $levelStr="" ) {
	// modified from rc_showAdminLoginPage
		// autocomplete='off': do not auto-fill the form (especially the password)
		// since many will login in public
	$val = rc_getValForForm( $_SESSION['Post']['Admin']['Login'], 'n' );
	//echo "<form action='{$levelStr}admin?do=login' method='post' autocomplete='off'>\n";
	rc_showFormTitle( "Please log in" );
	rc_showFormRow( "Login Name:", "<input type='text' name='n' value='{$val}'>" );
	echo "<input type='text' style='display:none;'>"; // hack to make no auto-fill work in Chrome
	rc_showFormRow( "Password:", "<input type='password' name='p' autocomplete='off'>" );
	rc_showFormRow( "", kernel_regMakeLinkButton( "Log in",'banner-login' ) );
	echo "<input type='hidden' name='url' value='" . rc_reconstructURL() . "'>";
	//echo "</form>\n";
//!!! do we want a 'Clear' button?
//--             //   print PHP_EOL . $this->kernel_regMakeLinkButton( "Log out",'banner-logout' );
//--    	// modified from rc_showAdminLoginPage
//--    		// autocomplete='off': do not auto-fill the form (especially the password)
//--    		// since many will login in public
//--    	$val = rc_getValForForm( $_SESSION['Post']['Admin']['Login'], 'n' );
//--    	//echo "<form action='{$levelStr}admin?do=login' method='post' autocomplete='off'>\n";
//--    	rc_showFormTitle( "Please log in" );
//--    	rc_showFormRow( "Login Name:", "<input type='text' name='n' value='{$val}'>" );
//--    	echo "<input type='text' style='display:none;'>"; // hack to make no auto-fill work in Chrome
//--    	rc_showFormRow( "Password:", "<input type='password' name='p' autocomplete='off'>" );
//--    	rc_showFormRow( "", "<input type='submit' value='Log in'>" );
//--    	echo "<input type='hidden' name='url' value='" . rc_reconstructURL() . "'>";
//--    	//echo "</form>\n";
//--    //!!! do we want a 'Clear' button?
//--             //   print PHP_EOL . $this->kernel_regMakeLinkButton( "Log out",'banner-logout' );
}

function krnLogin_process_logout() {
    unset($_GET[$pSubmitArg]);
    $windowTitle = "KCM Logout";
    rc_clearMessages();
    unset( $_SESSION['Admin'] );
    unset( $_SESSION['Post']['Admin'] );
    if ( isset($_SESSION['kcm']['fromAdmin']) ) {
       unset($_SESSION['kcm']['fromAdmin']);
    }
    //????? clear other kcm session variables ???
    //$this->theme-message-error-errorStatus('You have logged out');
    rc_queueInfo( $msg );
    $homeScriptUrl = strtok(rc_reconstructURL(),'?');    ;
    rc_redirectToURL($homeScriptUrl, NULL, true); //?????????????????????????????????
    exit;
}

function krnLogin_process_editProfile() {
    unset($_GET[$pSubmitArg]);
    $windowTitle = "";
    $this->wph_emit_html_head();
    $emitter = new kcmKernel_emitter(NULL,'draff-html-default');
    $emitter->emit_options->emtSetTitle('Edit User Profile');  // used in one other place
    $emitter->zone_body_start();
    $emitter->krnEmit_banner_output($this, $emitter);
    $emitter->zone_start('draff-zone-content-default');
    rc_showAdminProfilePage('../');
    $emitter->zone_end();
    $emitter->zone_body_end();
    exit;
}

} // end class

class KcmKernel_Banner {

    function krnEmit_banner_output($kernelGlobals, $emitter) {
        $this->krnEmit_banner_start($kernelGlobals);
        $this->krnEmit_banner_cell_logo('../kcm-kernel/images/'.$kernelGlobals->gb_banner_image_system);
        $this->krnEmit_banner_cell_proxyStatus($kernelGlobals);
        $this->krnEmit_banner_cell_title($emitter);
        $this->krnEmit_banner_cell_logo('../kcm-kernel/images/'.$kernelGlobals->gb_banner_image_kidchess);
        $this->krnEmit_banner_cell_login($kernelGlobals, $emitter);
        $this->krnEmit_banner_end();
    }
    
    private function krnEmit_banner_start($kernelGlobals) {
        $overlay = defined('RC_BACKGROUND_IMAGE') ? " style='background-image: url(" . '"../' . RC_BACKGROUND_IMAGE . '"' . "); background-repeat:repeat;'" : '';
        print PHP_EOL . PHP_EOL .'<div class="zone-ribbon theme-banner" '. $this->krnEmit_banner_getSystemBackgroundStyle() . '>';
        print PHP_EOL . '<table class="kcmKrn-banner-table">';
        print PHP_EOL . '<tr>';
    }
    
    private function krnEmit_banner_cell_logo($imageFileName) {
        print PHP_EOL;
        print PHP_EOL . '<td class="kcmKrn-banner-icon"><img src="'.$imageFileName.'"></td>';  // no overlay
    }
    
    private function krnEmit_banner_cell_title($emitter) {
        print PHP_EOL . '<td class="kcmKrn-banner-title"'.$this->krnEmit_banner_getSystemBackgroundStyle().'">';
        print PHP_EOL . '  ' . $emitter->emit_options->emtTitleLong;
        print PHP_EOL . '  </td>';
    }
    
    private function krnEmit_banner_cell_proxyStatus($kernelGlobals) {
        if ( $kernelGlobals === NULL) {
            return;
        }
        $actualUser = $kernelGlobals->gb_owner;
        $proxyUser = $kernelGlobals->gb_user;
        if ( ($actualUser==NULL) or ($proxyUser === NULL) ) {
            return;
        }
        $different = FALSE;
        $this->krnEmit_banner_proxy_compare($different,$actualUser->krnUser_staffId,$proxyUser->krnUser_staffId);
        $this->krnEmit_banner_proxy_compare($different,$actualUser->krnUser_nowDate,$proxyUser->krnUser_nowDate);
        $this->krnEmit_banner_proxy_compare($different,$actualUser->krnUser_nowDateTime,$proxyUser->krnUser_nowDateTime);
        if ( !$different) {
            return;
        }
        print PHP_EOL . '<td class="kcmKrn-banner-proxy"'.$this->krnEmit_banner_getSystemBackgroundStyle().'>';
        print PHP_EOL . '<div class=kcmKrn-banner-proxy-title>Proxy</div>';
        $pre = '';
        if ( $proxyUser->krnUser_nowDate !=$actualUser->krnUser_nowDate) {
            print PHP_EOL . $pre . draff_dateAsString($proxyUser->krnUser_nowDate,'F j, Y') . ' ('.draff_dateAsString($proxyUser->krnUser_nowDate,'l') . ')';
            $pre = '<br>';
        }
        if ( $proxyUser->krnUser_nowTime !=$actualUser->krnUser_nowTime) {
            print PHP_EOL . $pre . draff_timeAsString($proxyUser->krnUser_nowTime);
            $pre = '<br>';
        }
        if ( $proxyUser->krnUser_loginId!=$actualUser->krnUser_loginId) {
            print $pre.$proxyUser->krnUser_staffLongName;
        }
        print PHP_EOL . '</td>';
    }
    
    private function krnEmit_banner_cell_login($kernelGlobals, $emitter) {
        print PHP_EOL;
        print PHP_EOL . '<td class="kcmKrn-banner-login"'.$this->krnEmit_banner_getSystemBackgroundStyle().'>';
        if ( $kernelGlobals!=NULL) {
            if ( $kernelGlobals->gb_isLoggedIn) {
                print PHP_EOL."Logged in: <span class='font-weight-bold'>{$_SESSION['Admin']['LoginName']}</span>";
                print PHP_EOL.'<br>';
                if ($emitter->emit_options->emtTitleLong != 'Edit User Profile') {
                    $homeScript = $url=strtok(rc_reconstructURL(),'?');    ;
                    print PHP_EOL . kernel_regMakeLinkButton( "Log out",'banner-logout' );
                    print PHP_EOL . kernel_regMakeLinkButton( "Edit Profile",'banner-profile' );
                }
            }
            else {
                print PHP_EOL."Need to log in</span>";
            }
        }
        print PHP_EOL . '</td>';
    }
    
    private function krnEmit_banner_end() {
        print PHP_EOL . '</tr>';
        print PHP_EOL . '</table>';
        print PHP_EOL . '</div>';
        print PHP_EOL ;
    }
    
    private function krnEmit_banner_proxy_compare(&$dif, $value1, $value2) {
        if ( $value1 != $value2)
            $dif = TRUE;
    }
    
    private function krnEmit_banner_getSystemBackgroundStyle() {
        return defined('RC_BACKGROUND_IMAGE') ? " style='background-image: url(" . '"../../' . RC_BACKGROUND_IMAGE . '"' . "); background-repeat:repeat;'" : '';
    }
    
}

function fatal_handler() {
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;
    $error = error_get_last();
    if( $error !== NULL) {
        $errno   = $error["type"];
        $errfile = $error["file"];
        $errline = $error["line"];
        $errstr  = $error["message"];

        print fatal_error( $errno, $errstr, $errfile, $errline);
    }
}

function fatal_error( $errno, $errstr, $errfile, $errline ) {
    //$trace = print_r( debug_backtrace( false ), true );
// $trace = debug_backtrace( false );
    $trace = '';
    $p = strpos($errstr,'Stack trace:');
    if ($p!== FALSE) {
        $trace = substr($errstr,$p);
        $errstr = substr($errstr,0,$p);
    }
    $filePath = dirName($errfile);
    $len = strlen($filePath);
    $filePath .= substr($errfile,$len,1);
    $fileName = substr($errfile,$len+1);
    $content = "
    <html><head>
    <style>
    body {padding:16pt 4pt 4pt 4pt; width:100%;}
    td,th {border:1px solid black;padding:2pt 8pt 2pt 8pt;}
    table {margin:16pt 4pt 8pt 4pt;background-color:#fcc;width:90%;border-collapse: collapse;border-spacing: 0;empty-cells:show; border: 1pt solid; box-sizing: border-box;}
    </style>
    </head>
    <body>
    <table>
        <tbody>
            <tr>
                <td colspan='2' style='background-color:red;color:yellow;padding:4px 4px 4px 80px;font-size:20px;font-weight:bold;'>Fatal Error</td>
            </tr>
            <tr>
                <th>Path</th>
                <td>$filePath</td>
            </tr>
            <tr>
                <th>File</th>
                <td>$fileName</td>
            </tr>
            <tr>
                <th>Line</th>
                <td>$errline</td>
            </tr>
            <tr>
                <th>Error</th>
                <td><pre>$errstr</pre></td>
            </tr>
            <tr>
                <th>Trace</th>
                <td><pre>$trace</pre></td>
            </tr>
        </tbody>
    </table>
    </body>
    </html>
    ";
//        <thead><th>Item</th><th>Description</th></thead>
//            <tr>
//                <th>Errno</th>
//                <td><pre>$errno</pre></td>
//            </tr>
//            <tr>
//                <th>Trace</th>
//                <td><pre>$trace</pre></td>
//            </tr>
    return $content;
}

abstract class kcmKernel_Draff_Form extends Draff_Form {

    abstract protected function drForm_initData( $appData, $appGlobals, $appChain );
    abstract protected function drForm_initFields( $scriptData, $appGlobals, $chain );
    abstract protected function drForm_initHtml( $scriptData, $appGlobals, $chain, $emitter );
    abstract protected function drForm_outputHeader ( $scriptData, $appGlobals, $chain, $emitter );
    abstract protected function drForm_outputContent ( $scriptData, $appGlobals, $chain, $emitter );
    abstract protected function drForm_outputFooter ( $scriptData, $appGlobals, $chain, $emitter );
    
//    from parent
//    abstract protected function drForm_process_submit ( $scriptData, $appGlobals, $chain );
//    abstract protected function drForm_process_output ( $scriptData, $appGlobals, $chain, $emitter );

//    function kcmKernel_process_standard_output (  $appData, $appGlobals, $appChain, $appEmitter ) {
//        $appEmitter = $appChain->chn_register_emitter(new Draff_Emitter_Html($form));
//        $appGlobals->gb_addGlobalEmitterOptions($appEmitter);
//        $this->drForm_form_addErrors( $appChain );  // move errors from session to current form ???? is here the best place
//        $this->drForm_initData( $appChain->chn_app_data, $appChain->chn_app_globals, $appChain );
//        $this->drForm_initFields( $appChain->chn_app_data, $appChain->chn_app_globals, $appChain );
//        $this->drForm_initHtml( $appChain->chn_app_data, $appChain->chn_app_globals, $appChain, $appChain->chn_app_emitter );
//        $appEmitter->zone_htmlHead();
//        $appEmitter->zone_body_start($appChain, $form);
//        $appEmitter->drOutputIfNotReport(PHP_EOL.'<div class="zone-ribbon-group">');  // div so css can hide when printing
//        $appGlobals->gb_banner->krnEmit_banner_output($appGlobals, $appEmitter);
//        $appEmitter->zone_messages($appChain, $this);
//        $appGlobals->gb_menu->drMenu_emit_menu($appEmitter);
//        $appEmitter->drOutputIfNotReport(print PHP_EOL.'</div>');
//        $form->drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter );
//        $form->drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter );
//        $form->drForm_outputFooter  ( $appData, $appGlobals, $appChain, $appEmitter );
//        $appEmitter->zone_body_end();
//    }

}


?>