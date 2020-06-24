<?php

// kernel-globals.inc.php

// security COmmon to kcm, payroll, gateway, etc
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
public $gb_system_home; // '../kcm-gateway/kcm2-gateway.php'
public $gb_system_name; // 'KCM' - used for login title such as KCM Login
public $gb_db = NULL;
public $gb_sql = NULL;
public $gb_pdo;
//public $gb_chain;   //??????????? results in circular reference - need to eliminate
public $gb_systemTitle = '';
public $gb_banner_image_kidchess = 'kcm_banner_kidchess.gif';
public $gb_banner_image_system = ''; // gateway, roster, etc
public $gb_emitterClassName = '';
public $gb_cssFile_htmlCode = array();   // html code for css files for this system
public $gb_isExport = FALSE;
public $gb_session_events;
public $gb_session_proxy;
public $gb_session_security;  //???? used

abstract function gb_appMenu_init($chain, $emitter, $overrides=NULL);

function __construct($systemTitle, $imageFileName, $emitterName) {
    // do in kcm kernel
   // $session = draff_get_session();
    register_shutdown_function( "fatal_handler" );
    $this->gb_session_security = new Draff_SessionNode(array('@kcmSecurity'));
    $this->gb_session_events = new Draff_SessionNode(array('@kcmSecurity','@events'));
    $this->gb_session_proxy = new Draff_SessionNode(array('@kcmSecurity','@proxy'));
  //  $session->ses_addPath(SESSION_SECURITY, SESSION_DRAFF_TYPE_ROOT,  SESSION_SECURITY);
  //  $session->ses_addPath(SESSION_SECURITY_EVENTS, SESSION_SECURITY, SESSION_SECURITY_EVENTS);
  //  $session->ses_addPath(SESSION_SECURITY_PROXY,  SESSION_SECURITY, SESSION_SECURITY_PROXY);
    $this->gb_systemTitle      = $systemTitle;
    $this->gb_logoImageFile    = $imageFileName;
    $this->gb_emitterClassName = $emitterName;
  //  $this->gb_db               = rc_getGlobalDatabaseObject();  // to be eliminated
  //  $this->gb_sql              = new krnLib_sql_database($this->gb_db);   // to be eliminated
    $this->gb_pdo              =  new raccon_database_engine;
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
//     $this->gb_emitterClassName = $emitterName;
// }

function gb_forceLogin () {
    $loginAuthorization = new kcmKernel_login_authorization;
    $loginAuthorization->krnLogin_forceLogin ($this);
}

function gbKrn_add_cssFile($cssPath, $media="all", $levelStr="") {
	$timestamp = filemtime( __DIR__ . "/" . $levelStr . $cssPath );
    $this->gb_cssFile_htmlCode[] = "<link rel='stylesheet' type='text/css' media='{$media}' href='{$levelStr}{$cssPath}?v={$timestamp}'>";
}

function gb_getNow() {
    // need to use this function in more places ???????????????????????????
    // need to use the proxy to ovedrride the current date/time
    // do not use this function for logging, etc
	return date( "Y-m-d H:i:s" );
}

} // end class

class kcmKernel_security_events {

function krnSecure_isEventAuthorized($kcmGlobals, $programId, $userId=NULL) {
    // return TRUE or FALSE
    // save in session so can quickly retrieve ?????
    if ( empty($userId) ) {
        $userId = $kcmGlobals->gb_user->krnUser_staffId; // use proxy if applicable - rc_getStaffId();
    }
    // if is-admin (and in admin mode, i.e. not my-schedule, etc) then
        // authorized
    // else
    // Search authorization table (fast)
    // if school-dow in table then authorized
    // Search schedule (slower)
    // if on schedule within short date range then authorized

}

function krnSecure_getAllAuthorizedEvents($kcmGlobals, $userId=NULL, $schoolId=NULL, $dow=NULL, $year=NULL, $semeseter=NULL) {
    if ( empty($userId) ) {
        $userId = $kcmGlobals->gb_user->krnUser_staffId; // use proxy if applicable - rc_getStaffId();
    }
    //???? need is-admin flag
    // if is-admin then get all
    // else
        // Search authorization table (fast)
        // Search schedule
    // return list of programs
}

function krnSecure_getMyAuthorizedEvents($kcmGlobals, $userId=NULL, $schoolId=NULL, $dow=NULL, $year=NULL, $semeseter=NULL) {
    if ( empty($userId) ) {
        $userId = $kcmGlobals->gb_user->krnUser_staffId; // use proxy if applicable - rc_getStaffId();
    }
    //???? need is-admin flag
    // if is-admin then get all
    // else
        // Search authorization table (fast)
        // Search schedule
    // return list of programs
}

function krnSecure_authorizedEvents($kcmGlobals, $userId=NULL, $isMyEvents, $schoolId=NULL, $dow=NULL, $year=NULL, $semeseter=NULL) {
    if ( empty($userId) ) {
        $userId = $kcmGlobals->gb_user->krnUser_staffId; // use proxy if applicable - rc_getStaffId();
    }
    //???? need is-admin flag
    // if is-admin then get all
    // else
        // Search authorization table (fast)
        // Search schedule
    // return list of programs
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

class kcmKernel_login_authorization {

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
        $emitter = new $kernelGlobals->gb_emitterClassName($kernelGlobals,'draff-html-default');
        $emitter->zone_htmlHead($kernelGlobals->gb_systemTitle . 'Kidchess Coach Login');
		print PHP_EOL;
		print PHP_EOL . '<body class="draff-zone-body-normal">';
        $url = rc_reconstructURL();
 	    print PHP_EOL . PHP_EOL . "<form class='draff-zone-form-normal' action='../../admin?do=login' method='post' autocomplete='off'>\n";
        $emitter->krnEmit_banner_output($kernelGlobals, 'Kidchess Coach Login');
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
    $emitter->zone_body_start();
    $emitter->krnEmit_banner_output($this, 'Edit User Profile');
    $emitter->zone_start('draff-zone-content-default');
    rc_showAdminProfilePage('../');
    $emitter->zone_end();
    $emitter->zone_body_end();
    exit;
}

} // end class

class raccon_database_engine extends draff_database_engine {

function __construct() {
	if (rc_isAdmin()) {
		$dbuser = RC_DB_USER_ADMIN;
		$dbpass = RC_DB_PW_ADMIN;
	}
	else {
		$dbuser = RC_DB_USER_NOT_ADMIN;
		$dbpass = RC_DB_PW_NOT_ADMIN;
	}
    parent::__construct(RC_DB_DATABASE_HOST, RC_DB_DATABASE_NAME, $dbuser, $dbpass, 'utf8_unicode_ci');
}

} // end class

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

        print fmt_error( $errno, $errstr, $errfile, $errline);
    }
}

function fmt_error( $errno, $errstr, $errfile, $errline ) {
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

?>