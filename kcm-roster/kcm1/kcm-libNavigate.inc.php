<?php

// kcm-libNavigate.inc.php

// Class to print HTML to screen, or export to PDF or Excel File
// that is this class is used for web pages and reports
// and a web page can contain a report

//const KCM_NAVMODE_JQUERY = 'jq'; //~~15/08-jquery - removed jquery support until needed
const KCM_NAVMODE_HELP = 'help'; //~~15/08
const KCM_NAVMODE_STARTPAGE = 'kcm'; //~~15/08

class kcm_libNavigate {
public $kcmState;
public $roster;
public $urlTitle;
public $pageTitle;
public $schoolId;
public $schoolName;
public $programId;
public $programType;
public $programDateFirst;
public $programDateLast;
public $programDesc;
public $periodId;
public $periodName;
private $styleFile;
private $styleMedia;
private $scriptArray; //~~15/08
private $isPortrait;
private $pageTitleLeft;
private $pageTitleRight;

function __construct($pKcmState) {
    $this->kcmState = $pKcmState; 
    $this->roster = NULL; // set later so can force login earlier
    $this->urlTitle = NULL;
    $this->pageTitle = '';
    $this->schoolName = NULL;
    $this->programType = NULL;
    $this->programDateFirst = NULL;
    $this->programDateLast = NULL;
    $this->programDesc = NULL;
    $this->programDesc = NULL;
    $this->periodName = NULL;
    $this->styleFile = array();
    $this->styleMedia = array();
    $this->scriptArray = array(); //~~15/08
    $this->isPortrait = FALSE;
}

function addStyleSheet($pFile,$pMedia='') {
    $this->styleFile[] = $pFile;
    $this->styleMedia[] = $pMedia;
}
function addScript($pScript) { //~~15/08
    $this->scriptArray[] = $pScript; //~~15/08
} //~~15/08


function setTitleStandard($pUrlTitle, $pPageTitle=NULL, $isPoints=FALSE) {
    $this->urlTitle = $pUrlTitle;
    if ($pPageTitle===TRUE) {
        $pPageTitle = NULL;
        $isPoints = TRUE;
    }
    if ($pPageTitle==NULL)
        $pPageTitle = $pUrlTitle;
    $periodId = $this->kcmState->ksPeriodId;   
    if ($this->roster==NULL)
        $periodId = NULL;
    if  ( ($periodId==NULL) or ($periodId<1) ) {
        $this->pageTitle = $pUrlTitle;
        $this->pageTitleLeft = '';
        $this->pageTitleRight = $pUrlTitle;
    }    
    else {
        $curPeriod = $this->roster->getPeriodFromPeriodId($periodId);
        $progName = $this->roster->program->getNameLong($this->roster);     
        $this->pageTitle = $pUrlTitle . '<br>' . $progName .', '.$curPeriod->PeriodName . '<br>'.$this->roster->schedule->meetDateDesc;
        if ( $isPoints === 'today' ) {     
            $dateDesc  = $this->roster->schedule->todayDateObject->format( "F j, Y" );
        }    
        else if ( $isPoints === TRUE ) {     
            $dateDesc  =  '<br>' .$this->roster->schedule->entryDateDesc;
        }    
        else {
            $dateDesc  = $this->roster->schedule->meetDateDesc;
        }    
        $this->pageTitleLeft = $pUrlTitle . '<br>' . $curPeriod->PeriodName;
        $this->pageTitleRight = $progName . '<br>' .$dateDesc;
    }    
}

function setTitle($pUrlTitle, $pPageTitle=NULL) {
    if ($pPageTitle==NULL)
        $pPageTitle = $pUrlTitle;
    $this->urlTitle = $pUrlTitle;
    $this->pageTitle = $pPageTitle;
}
function setClassData($pClassData) {
    $this->roster = $pClassData;
}

function setSchool($pSchoolName) {
    $this->schoolName = $pSchoolName;
}

function setSemester() {
}

function setPortrait() {
    $this->isPortrait = TRUE;
}

function setProgram($pType, $pDateFirst, $pDateLast, $pDesc) {
    $this->programType = $pType;
    $this->programDateFirst = $pDateFirst;
    $this->programDateLast = $pDateLast;
    $this->programDesc = $pDesc;
}

function setPeriod($pName) {
    $this->periodName = $pName;
}

function processLoginLogout($pSubmitArg, $pRefresh=FALSE) {
    if ($pSubmitArg == 'logout') {     
        $windowTitle = "KCM Logout";
        rc_clearMessages();
        unset( $_SESSION['Admin'] );
        unset( $_SESSION['Post']['Admin'] );
        if ( isset($_SESSION['kcm']['fromAdmin']) ) {
           unset($_SESSION['kcm']['fromAdmin']);
        }
        //????? clear other kcm session variables ???
        //$this->MessageStatus('You have logged out');
        rc_queueInfo( $msg );
        rc_redirectToURL('kcm.php', NULL, true);
        exit;
    }    
    $this->showForceLogin();
    if ($pSubmitArg == 'editProfile') {     
        $windowTitle = "";
        $this->show();
        rc_showAdminProfilePage();
        exit;
    }  
    //if ($pSubmitArg=='refresh' or $pRefresh) {
    //   rc_classCache::clearCache();
    //   return;
    //}   
  //$url  = @( $_SERVER["HTTPS"] != 'on' ) ? 'http://'.$_SERVER["SERVER_NAME"] :  'https://'.$_SERVER["SERVER_NAME"];
  //$url .= ( $_SERVER["SERVER_PORT"] !== 80 ) ? ":".$_SERVER["SERVER_PORT"] : "";
  //$url .= $_SERVER["REQUEST_URI"]; 
}
function show($mode='') {
    $argSubmit = kcm_getParam('Submit','Cancel');
    //if ($argSubmit=='logout') {
    //    $this->showLogout();
    //}
    $this->showWebPageHeader($mode);
    $this->showSystemHeader($mode);
    rc_showMessages();
    rc_clearMessages();
}

function showWebPageHeader($mode='') {
    $title = $this->urlTitle;
    if ( defined('RC_EXTRA_TITLE_TEXT') )
           $title .= ' [' . RC_EXTRA_TITLE_TEXT . ']';
   //if (RCx_LOCAL) {
    //    $title .= " [LOCAL]";
    //}
    //else if (RCx_TESTING) {
    //    $title .= " [TESTING]";
    //}
    print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">'.PHP_EOL;
    print PHP_EOL.'<html id="regTop">'.PHP_EOL;
    print PHP_EOL.'<head>'.PHP_EOL;
    print PHP_EOL."<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>".PHP_EOL;
    print PHP_EOL."<title>{$title}</title>".PHP_EOL;
    print "<link rel='icon' type='image/x-icon' href='images/kidchess-icon.jpg'>";
    for ($i = 0; $i<count($this->styleFile); ++$i) {
        $media = $this->styleMedia[$i];
        if ($media!='')
            $media = 'media="'.$media.'" ';
        print PHP_EOL.'<link rel="stylesheet" type="text/css" '.$media.'href="'.$this->styleFile[$i].'?ver=1.1"/>';
    }    
    print rc_getBackgroundImageStyleHtml();
    //if (RCx_TESTING) 
    //    print "<style type='text/css'>html {background-image: url('images/testing-background.jpg');background-repeat:repeat;}</style>";
    //if (RCx_LOCAL) 
    //    print "<style type='text/css'>html {background-image: url('images/testing-local-background.jpg');background-repeat:repeat;}</style>";
//~~15/08-jquery    if ( $mode == KCM_NAVMODE_JQUERY) {  //~~15/08  
//~~15/08-jquery        print PHP_EOL.'<script src="jqgrid/js/jquery-1.11.0.min.js" type="text/javascript"></script>';
//~~15/08-jquery        print PHP_EOL.'<script src="jqgrid/js/jquery-ui-1.10.3.custom.min.js" type="text/javascript"></script>';
//~~15/08-jquery    }    
    if ( count($this->scriptArray) >= 1) { //~~15/08
        for ($i = 0; $i<count($this->scriptArray); ++$i) { //~~15/08
            $s = $this->scriptArray[$i]; //~~15/08
            print PHP_EOL.'<script>'.$s.PHP_EOL.'</script>'; //~~15/08
        } //~~15/08
    } //~~15/08
    print PHP_EOL.'</head>';
    if ($this->isPortrait)
        print PHP_EOL.'<body class="kcm rptPortrait">';
    else    
        print PHP_EOL.'<body class="kcm">';
}

function cellIcon() {
    print PHP_EOL.'<td class="knavSystemIcon">';
    print PHP_EOL.'<img src="images/kcm-header.gif" height="70px">';
    print PHP_EOL.'</td>';
}
function cellMenu($mode='') {
    print PHP_EOL.'<td class="knavMenu" colspan="99">';
    $this->showKcmMenu($mode);
    print PHP_EOL.'</td>';
}
function cellTitle() {
    print PHP_EOL.'<td class="knavSystemTitleLeft">';
    print $this->pageTitleLeft;
    print PHP_EOL.'</td>';
    print PHP_EOL.'<td class="knavSystemTitleRight">';
    print $this->pageTitleRight;
    print PHP_EOL.'</td>';
}
function cellLogin() {
    print PHP_EOL.'<td class="knavSystemLogin">';
    print '<div style="display: inline-block;float:right">';
    if (rc_isAdmin()) { 
        //print '<div id="adminSuperTopHeader">';
        //print '<div id="adminLoginStatus">';
        //print '<div>';
        echo "You are logged in as ";
        print '<br>';
        echo "<span style='font-weight:bold;'>{$_SESSION['Admin']['LoginName']}</span>";
        //print "</div>";
        print '<br>';
        print "<div class='buttons'>";
        echo rc_regMakeLinkButton( "kcm.php?Submit=logout", "Log out" );
//????? should also pass kcm state ????????
        echo rc_regMakeLinkButton( "kcm.php?Submit=editProfile", "Edit Profile" );
        print "</div>";
    //echo rc_regMakeLinkButton( "admin?do=logout", "Log out" );
    //echo rc_regMakeLinkButton( "admin-profile", "Edit Profile" );
        //'Log out', 'logout', 'kcm.php',$pKcmState);
    }
    else {
        echo "You are not logged in.\n";
    }
    print '</div>';
    print PHP_EOL.'</td>';
}

function showSystemHeader($mode) {
    print PHP_EOL.'<table class="knavSystem ScreenOnly">';
    print PHP_EOL.'<tr>';
    $this->cellIcon();
    $this->cellTitle();
    $this->cellLogin();
    print PHP_EOL.'</tr>';
    print PHP_EOL.'<tr>';
    $this->cellMenu($mode);
    print PHP_EOL.'</tr>';
    print '</table>';
    print '<br>';
}

function showMenuActions() {
	if (rc_checkViewAccess( array( RC_ACCESS_ROSTER, RC_ACCESS_ROSTER_LIMITED ) )) {
		echo PHP_EOL.PHP_EOL."<div class='knavListAll knavListAction'>";
		$this->heading_setknavListTitle( "Period Actions" );
		$this->heading_setknavListLink( "Period Home", "kcm-periodHome.php",'pgHome');
		$this->heading_setknavListLink( "Enter Games", "kcm-game_entry.php",'pgHome');
		$this->heading_setknavListLink( "Enter Points", "kcm-point_entry.php",'pgHome');
		echo PHP_EOL."   </div>";
	}
}
function showMenuReports() {
	if (rc_checkViewAccess( array( RC_ACCESS_ROSTER, RC_ACCESS_ROSTER_LIMITED ) )) {
		echo PHP_EOL.PHP_EOL."<div class='knavListAll knavListReports'>";
		$this->heading_setknavListTitle( "Reports" );
        $this->heading_setknavListLink('Point Tally','kcm-rpt-PointTally.php','Point Tally');
        $this->heading_setknavListLink('Roster','kcm-rpt-Roster.php','Roster','Format','r');
        $this->heading_setknavListLink('Name Labels','kcm-rpt-NameLabels.php','Name Labels');
        $this->heading_setknavListLink('Pairing Labels','kcm-rpt-PairingLabels.php','Pairing Labels');
		echo PHP_EOL."   </div>";
	}
}
function showMenuAdminReports() {
	if (rc_checkViewAccess( array( RC_ACCESS_ROSTER, RC_ACCESS_ROSTER_LIMITED ) )) {
		echo PHP_EOL.PHP_EOL."<div class='knavListAll knavListReports'>";
		$this->heading_setknavListTitle( "Admin Reports" );
        $this->heading_setknavListLink('Drop List','kcm-rpt-adminReports.php','Drop List','mode','drop');
        $this->heading_setknavListLink('Email List','kcm-rpt-adminReports.php','Email List','mode','email');
        $this->heading_setknavListLink('Wait List','kcm-rpt-adminReports.php','Wait List','mode','wait');
         $this->heading_setknavListLink('Sign-Out Sheet','kcm-rpt-Sign-Out-Sheet.php','Sign-Out Sheet');
//       $this->heading_setknavListLink('Playground','kcm-periodPlayground.php','Playground');
		echo PHP_EOL."   </div>";
	}
}
function showMenuChange($mode) {
 	//$fromAdmin = isset( $_SESSION['kcm']['fromAdmin']);
	if (rc_checkViewAccess( array( RC_ACCESS_ROSTER, RC_ACCESS_ROSTER_LIMITED ) )) {
        //if ( ($this->roster==NULL) and  (! $fromAdmin) ) {
        //    return;
        //}
		echo PHP_EOL.PHP_EOL."<div class='knavListAll knavListChange'>";
        if ($this->roster!=NULL) {
  	        $this->heading_setknavListTitle( "Change Period" );
            for ($i = 0; $i<$this->roster->periodCount; ++$i) {
                $curPeriod = $this->roster->periodArray[$i];
                if ($curPeriod->PeriodSequenceBits<4096) {
                    if ($curPeriod->PeriodId==$this->kcmState->ksPeriodId) {
                        $s= '(' . $curPeriod->PeriodName. ')' ;
                        print PHP_EOL.'   <div class="knavListLink">'.$s.'</div>';
                    }    
                    else {
                        $this->heading_setknavListLink($curPeriod->PeriodName,
                           "kcm-periodHome.php",'','PeId',$curPeriod->PeriodId);
                    }    
                }       
            }
            echo "<hr>\n<strong>Change Program</strong>\n";
            $this->heading_setknavListLink( "Select Program", "kcm.php" );
        }    
        //if ($mode == 'kcm') {
    	//	$this->heading_setknavListLink( "Help for 1st time users", "kcm-help.php" );
        //}    
        //if ($fromAdmin) {
        if ($mode == KCM_NAVMODE_STARTPAGE) {
    		$this->heading_setknavListLink( "Admin", "rc_admin.php" );
        }
		echo PHP_EOL."   </div>";
	}
}
function showMenuAdmin() {
	if (rc_checkViewAccess( array( RC_ACCESS_ROSTER, RC_ACCESS_ROSTER_LIMITED ) )) {
		echo PHP_EOL.PHP_EOL."<div class='knavListAll knavListAdmin'>";
		$this->heading_setknavListTitle( "Admin" );
		$this->heading_setknavListLink( "Point Categories", "kcm-pointCats_edit.php",'pgHome' );
		$this->heading_setknavListLink( "Grade Groups", "kcm-gradeGroups_edit.php",'pgHome');
        // assigned groups only valid from home        
		$this->heading_setknavListLink( "Class Sub-Groups", "kcm-periodHome.php",'pgHome', 'Submit','KidGrpEdit');
	    echo "<hr>\n<strong></strong>\n";
		$this->heading_setknavListLink( "Point History", "kcm-point_history.php",'pgHome', 'Submit','KidGrpEdit');
		echo PHP_EOL."   </div>";
	}
}


function showKcmMenu($mode) {
    echo PHP_EOL.'<div class="knavMenu">';
    if ($mode==KCM_NAVMODE_HELP) {
        $this->heading_setknavListLink( "Back", "kcm.php" );
    }
    else {
        if ($this->roster!=NULL) {
            $this->showMenuActions();
            $this->showMenuReports();
        }    
        $this->showMenuChange($mode);
        if ($this->roster!=NULL) {
            $this->showMenuAdmin();
            $this->showMenuAdminReports();
        }    
    }    
    echo PHP_EOL.'</div>';
    echo PHP_EOL.'<div class="clearFloats">';
    echo PHP_EOL.'</div>';
}

function heading_setknavListTitle( $title ) {
	echo PHP_EOL."   <div class='knavListTitle'>";
	echo "   {$title}";
	echo "</div>";
}

function heading_setknavListLink($pDesc, $pFile, $pTarget=NULL, $pArgId=NULL, $pValue=NULL) {
    $url = $this->kcmState->convertToUrl($pFile,$pArgId,$pValue);
    print PHP_EOL.'   <div class="knavListLink"><a href="'.$url.'">'.$pDesc.'</a></div>';
}

function showForceLogin() {
    $fn = basename($_SERVER['PHP_SELF']);
    if ( rc_isAdmin()) 
        return;
    $this->showWebPageHeader('');
    rc_clearMessages();
    print PHP_EOL.'<table class="knavSystem">';
    print PHP_EOL.'<tr>';
    print PHP_EOL.'<td class="knavSystemIcon">';
    print PHP_EOL.'<img src="images/kcm-header.gif" height="70px">';
    print PHP_EOL.'</td>';
    print PHP_EOL.'<td class="knavSystemTitle">';
    print $this->pageTitle;
    print PHP_EOL.'</td>';
    print PHP_EOL.'<td class="knavSystemLogin">';
    print 'KCM Login';
    print PHP_EOL.'</td>';
    print PHP_EOL.'</tr>';
    print '</table>';
    print '<br><br><br><br>';
    rc_showAdminLoginPage();
    print '</body>';
    exit;  // no more processing of php script - MUST login first
}

}

function debvar($a) {
if ($a==='*&')
   return;
if (is_null($a)) {
   print 'NULL : ';
   return;
}
if (is_bool($a)) {
    if ($a===TRUE) 
       print 'TRUE : ';
    else if ($a===FALSE) 
       print 'FALSE : ';
    return;   
    }   
if (is_array($a)) {
    print '<hr>'.var_dump($a).'<hr>';
    return;   
}
print $a.' : ';
}

function debNull($a) {
    if ($a===NULL) 
        return 'Is Null';
    else 
        return 'Not Null';
}
function debBool($a) {
    if ($a)
        return $a . '(True)';
    else  
        return $a . '(False)';
}
function deb($a,$EggButtonBack='*&',$c='*&',$d='*&',$e='*&',$f='*&',$g='*&') {
    print'<br>';
    print '[';
    debvar($a);
    debvar($EggButtonBack);
    debvar($c);
    debvar($d);
    debvar($e);
    debvar($f);
    debvar($g);
    print ']';
}


?>