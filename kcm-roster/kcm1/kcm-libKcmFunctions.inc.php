<?php

// kcm-libKcmFunctions.inc.php

//=============
//=  Dup Submit

function kcmRedirectIfDupSubmit($pKcmState,$pPageName='') {
	$tranId = kcm_getParam('kcmTranId',NULL);  // get from get or post 
    if (!is_null($tranId) and isset( $_SESSION['kcmTranId'][$tranId] )) {  // avoid duplicate submissions
//rc_setDebugData( 'DupSubmit-Check', $tranId . '-->Found (unsetting)');
		unset( $_SESSION['kcmTranId'][$tranId] );
        return;
	}
//rc_setDebugData( 'DupSubmit-Duplicate', $tranId . '--> Is Duplicate');
    if ($pPageName==='');
        $pPageName=$_SERVER['PHP_SELF'];    
    $err ='Possible duplicate submission not processed (additional submit click was ignored)';
//exit($err);    
	rc_queueError($err);
    if ($pKcmState===NULL)
        $pUrl = $pPageName;    
    else
        $pUrl = $pKcmState->convertToUrl($pPageName);    
    rc_redirectToURL($pUrl);
}
 
function kcmUiFormSubmitIsFirstTime() {
	$tranId = kcm_getParam('kcmTranId',NULL);  // get from get or post 
    if (!is_null($tranId) and isset( $_SESSION['kcmTranId'][$tranId] )) {  // avoid duplicate submissions
		unset( $_SESSION['kcmTranId'][$tranId] );
        return TRUE;
	}
	else {
       exit('---------double---------------');
        return FALSE;
    }    
}    
function kcmUiFormSubmitGetErrorMsg($pIsFirstTime=FALSE) {
    if ($pIsFirstTime) 
        return '';
    else    
        return 'Possible duplicate submission not processed (additional submit click was ignored)';
}
function kcmUiFormSubmitRedirectIfNotFirst($pIsFirstTime,$pkcmState,$pPageName='') {
    if ($pIsFirstTime) 
        return '';
    if ($pPageName==='');
        $pPageName=$_SERVER['PHP_SELF'];    
    $pUrl = $pkcmState->convertToUrl($pPageName);    
	rc_queueError(kcmUiFormSubmitGetErrorMsg($pIsFirstTime));
    rc_redirectToURL($pUrl);
}

function kcm_stringIsSafe($pString) {
    global $errFlag, $errArray;
    if ($pString===NULL or $pString==='')
        return '';
    else if (preg_match('"^[\w %#\$\&\(\).\-]+$"',$pString)!==1) 
        return 'Descriptions may only consist of letters, digits, ".", "%", "_", "(", ")", "#", "-", and "$"';
    else
        return '';    
}
// @jpr@kcm2 START
function kcm_stringIsSafeKcm2($pString) {
    global $errFlag, $errArray;
    if ($pString===NULL or $pString==='')
        return '';
    else if (preg_match('"^[\w %#\$\&\(\).\-\!\@\%\+\=\[\]\/\*\?\:\;]+$"',$pString)!==1) 
        return 'Descriptions may only consist of letters, digits, and common punctuation (but not quotes, and a few other symbols) ';
    else
        return '';    
}
// @jpr@kcm2 END
function kcm_getParam($pTag, $pDefault=NULL) {
    if (isset($_GET[$pTag]))
        return $_GET[$pTag];
    if (isset($_POST[$pTag]))
        return $_POST[$pTag];
    return $pDefault;
}

function det($a,$EggButtonBack='') {
    if ($a===TRUE) 
       $s='TRUE';
    else if ($a===FALSE) 
       $s='FALSE';
    else   
       $s='OTHER';
    echo '====>'.$EggButtonBack.'='.$s.'<br>';   
}
function kcm_gamePercent($pWin, $pLost, $pDraw) {
    $games = $pWin + $pLost + $pDraw;
    if ($games == 0)
        return '';
    else    
        return  round( ( 100 * ( $pWin + $pDraw * .5) ) / $games );
}
 
function kcm_catchBadProgramId($argProgramId) {
    if ($argProgramId=='') 
        rc_redirectToURL('kcm.php?'.$s, NULL, true);
}        
function kcm_catchBadPeriodId($argPeriodId) {
    if ($argPeriodId=='') 
        rc_redirectToURL('kcm-overviewProgram.php?'.$s, NULL, true);
}        
function kcm_catchBadKidPeriodId($argKidPeriodId) {  //?? not needed
    if ($argKidPeriodId=='') 
        rc_redirectToURL('kcm-periodHome.php?'.$s, NULL, true);
}        
function kcm_catchBadLoginLevel($pProgram, $pDesiredLevel) {
// see if logged-in user has the authority to do the specified task
}

//function kcm_getStaffName($pStaffId, $pDb) { 
//    if ($pStaffId==0 or $pStaffId==NULL or $pStaffId='')
//        return '(unknown)';
//    $query = "SELECT `sSt:ShortName` FROM `st:staff` WHERE `sSt:StaffId` ='".$pStaffId."'";
//    $result = $pDb->rc_query( $query );
//    if ($result->num_rows == 0) 
//        return '(unknown)';
//    if ($result === FALSE) {
//        print rc_makeErrorHTML( "Data retrieval error.  Please try again." );
//        $err = TRUE;
//    }
//    $row=$result->fetch_array(); 
//    $s = $row['sSt:ShortName'];
//    if ($s=='')
//        $s = '(unknown)';
//    return $s;    
//    }    
    
function kcm_getStaffName($pStaffId, $pDb, $pStaffName=NULL) {
    if ($pStaffId<1)
        return '(unknown)';
    if ($pStaffName===NULL)    
        $pStaffName = '';
    if ($pStaffName!=='') 
        return $pStaffName;
    $query = "SELECT `sSt:ShortName` FROM `st:staff` WHERE `sSt:StaffId` ='".$pStaffId."'";
    $result = $pDb->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }    
    if ($result->num_rows == 0)                    
        return '(Not Found)';
    $row=$result->fetch_array(); 
    return $row['sSt:ShortName'];
}

function kcm_db_CriticalError($pPageName, $pLine, $pMessage=NULL, $pQuery=NULL) {
    if ( ($pMessage === NULL) or ($pMessage='') )
        $pMessage = 'Database Error';
    $message =  $pMessage . ', Line='.$pLine. ', File='.basename($pPageName) ;   
    print "<br><br>".$message."<br><br>";
    if (rc_getStaffId()=='')
        print '<br><br>'.$message.'<br><br>';
    rc_queueError($message );
    $err = TRUE;
    //rc_redirectToURL( $_POST['url'] );
    exit($err);
}

function kcm_convertStringToPointValues($pSkillString) {
    if ($pSkillString == '') {
        return array_fill ( 0 , kcmMAX_POINT_CATEGORIES,  0 );
    }
    $pointCategories = explode('~',$pSkillString);
    for ($i=count($pointCategories); $i<kcmMAX_POINT_CATEGORIES ;$i++) {
        $pointCategories[] = 0;
    }
    return $pointCategories;
}
function kcm_PointValuesToString($pPointValueArray) {
    return implode('~',$pPointValueArray);
}

function kcm_dbError($pPageName, $pLine, $pMessage=NULL, $pQuery=NULL) {
    if ($pMessage === NULL)
        $pMessage = 'Database Error';
    $message =  $pMessage . ', Line='.$pLine. ', File='.basename($pPageName) ;   
    rc_queueError($message );
    $err = TRUE;
    //rc_redirectToURL( $_POST['url'] );
    exit($err);
}

class kcm_db_transaction {
public $db;
public $errorCount;
    
function dbtStart() {
	$this->db = rc_getGlobalDatabaseObject();
    $this->db->rc_startTransaction();
    $this->errorCount = 0;
    set_error_handler(array($this,"dbtError")); 
}

function dbtCommit() {
    if ($this->errorCount >= 1) {
         echo "<br><br>There were errors - preparing to rollback...";
         $this->db->rc_rollback();
         echo "<br>All transactions in this approval have been cancelled (rolled back)<br><br>";
         kcm_dbError('Approval transaction had error and was cancelled (rolled back)',0);
    }
    else
        $this->db->rc_commit();
    restore_error_handler ();
}
function dbtError($errno, $errstr,  $errfile, $errline ) {  // error handler
  $errDesc = $errstr . ', Line = '. $errline . ', ErrorCode=' . $errno . ', File = ' . basename($errfile);
  echo '<EggButtonBack>Program Error: ' . $errDesc .  '</EggButtonBack>';
  ++$this->errorCount;
  if ( ($errno==E_WARNING) or ($errno==E_NOTICE)) { 
      echo " (continuing)<br>";
      return;
  }    
  echo "<br><br>Ending Script<br>";
  kcm_dbError( "Approval Transaction Error (transaction was rolled-back): " . $errDesc );
  die();
} 

function dbtQuery($pQuery, $lineNum=NULL) {
    $result = $this->db->rc_query( $pQuery );
    if ($result === FALSE) {
        $this->db->rc_rollback();
        rc2_dbError( __FILE__,$lineNum);
    }    
    return $result;
}

function dbtDoQuery($pDoQuery, $lineNum=NULL) {
    $result = $pDoQuery->doQuery(); 
    if ($result === FALSE) {
        $this->db->rc_rollback();
        rc2_dbError( __FILE__,$lineNum);
    }    
    return $result;
}

}

function kcm_authorizeProgramId($pProgramId, $db) {
    // RC_ACCESS_ROSTER can access all programs
    if ( rc_checkAccess (RC_ACCESS_ROSTER))
        return;
    // if not RC_ACCESS_LIMITED cannot access any program
    if ( ! rc_checkAccess (RC_ACCESS_ROSTER_LIMITED)) {
        exit('You are not authorized to access any program');
    }    
    // if RC_ACCESS_LIMITED can only access certain programs
    // do not recalculate if already approved
    //if ( ($pProgramId == $_SESSION['kcm'] ['AuthProgramId'])
    //   and ($_SESSION['kcm'] ['AuthStaffId'] == rc_getStaffId() ) )  {
    //    return;
    //}
    $staffId = rc_getStaffId();
    // get School Id for specified program
    $sql = array();
    $sql[] = "Select `pPr:@SchoolId`,`pPr:DateClassFirst`";
    $sql[] = "FROM `pr:program`";
    $sql[] = "WHERE `pPr:ProgramId`='$pProgramId'";
    $query = implode( $sql, ' ');
    //echo '<hr>', $query,'<hr>' ;    
    $result = $db->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    if ($result->num_rows == 0) {
        exit('System Inconsistency - This program does not exist');
    }
    $row=$result->fetch_array();
    $schoolId = $row['pPr:@SchoolId'];
    $classFirst = $row['pPr:DateClassFirst'];
    //if ($result->num_rows == 0) {
    // calculate date range for calendar check
    kcm_getScheduleDateRange($dateRangeStart,$dateRangeEnd, 'ka',$classFirst);
    $fldList[] = 'cSD:ScheduleDateId';  // ca:scheduledate
    $fldList[] = 'cSD:@ProgramId';
    $fldList[] = 'cSD:ClassDate';
    $fldList[] = 'cSD:HolidayFlag';
    $fldList[] = 'cSD:Published?';
    $fldList[] = 'cSS:ScheduleDateStaffId'; //ca:scheduledate_staff
    $fldList[] = 'cSS:@ScheduleDateId';
    $fldList[] = 'cSS:@StaffId';
    $fldList[] = 'cSS:RoleType';
    $fldList[] = 'pPr:@SchoolId';
    $fields = "`" . implode($fldList,"`, `") . "`";
    $sql = array();
    $sql[] = "Select $fields";
    $sql[] = "FROM `ca:scheduledate_staff`";
    $sql[] = "LEFT JOIN `ca:scheduledate` ON `cSD:ScheduleDateId` = `cSS:@ScheduleDateId`";
    $sql[] = "LEFT JOIN `pr:program` ON `pPr:ProgramId` = `cSD:@ProgramId`";
    $sql[] = " WHERE `cSS:@StaffId`='$staffId'";
    //$sql[] = "  AND  `cSD:@ProgramId`='$pProgramId'";
    $sql[] = "  AND  `pPr:@SchoolId`='$schoolId'";
    $sql[] = "  AND (`cSD:ClassDate` BETWEEN '$dateRangeStart' AND '$dateRangeEnd') ";
    $sql[] = "  AND (`cSS:RoleType` IN ('1', '2', '5') ) ";
//    $sql[] = "  AND `cSD:Published?`  = '1' ";    // jpr@2018-04 
    //????? and check role type for site leader or head coach
    $query = implode( $sql, ' ');
    //echo '<hr>', $query,'<hr>' ;    
    $result = $db->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    if ($result->num_rows == 0) {
        exit('You are not authorized to access this program');
    }
    // all is good
    $_SESSION['kcm'] ['AuthProgramId'] = $pProgramId;
    $_SESSION['kcm'] ['AuthStaffId'] = rc_getStaffId();
}

function kcm_getScheduleDateRange(&$pStartDate,&$pEndDate,$mode,$classFirst=NULL) {
    // mode: 'as' = admin schedule, 'ks' = kcm schedule,
    //       'kr' = kcm site manager, 'ka'=authorized
    switch ($mode) {
        case 'as':  $bef = '-13';   $aft = '+7';  $dow = -1;  break;  // schedule - raccoon  ???
        case 'ks':  $bef =  '+0';   $aft = '+6';  $dow = -1;  break;  // schedule - kcm
        case 'kr':  $bef = '-8';    $aft = '+5';  $dow = -1;  break;  // kcm.php site manager reporting
        case 'ka':  $bef = '-14';   $aft = '+14'; $dow = -1;  break;  // kcm.php coach  - and authorize program id in kcm lib
        default:    $bef = '-7';    $aft = '+7';  $dow = -1;  break;
    }
    $bef = '-14'; $aft='+21'; // added jpr@2018-04  @jpr longer date range
    $dateToday = date_create( "today" );
	$dateStart = clone $dateToday;
	$dateEnd = clone $dateStart;
    if ($dow >= 0) {
        while (date_format( $dateStart, 'N' ) != $dow) {  //1=Monday, 7=Sunday
            date_modify( $dateStart, '-1 day' );
        }    
    }    
    date_modify( $dateStart, $bef . ' day' );
    date_modify( $dateEnd, $aft . ' day' );
  	$pStartDate = date_format( $dateStart, 'Y-m-d' );
  	$pEndDate = date_format( $dateEnd, 'Y-m-d' );
}

function kcm_schedulePrev($db,$kcmState,$roster) {
    $dateObject = new datetime($kcmState->ksEntryDateSql); 
    if ($roster->program->ProgramType == 1)
        date_modify($dateObject,'-7 day');
    else    
        date_modify($dateObject,'-1 day');
    $newDateSql = $dateObject->format( "Y-m-d" );
    $kcmState->ksEntryDateSql = $newDateSql;
    $kcmState->ksSetArg('ClDate',$newDateSql);  
    $roster->schedule->overrideEntryDate($db,$roster,$kcmState->ksEntryDateSql);
}
function kcm_scheduleNext($db,$kcmState,$roster) {
    $dateObject = new datetime($kcmState->ksEntryDateSql); 
    if ($roster->program->ProgramType == 1)
        date_modify($dateObject,'+7 day');
    else    
        date_modify($dateObject,'+1 day');
    $newDateSql = $dateObject->format( "Y-m-d" );
    $kcmState->ksEntryDateSql = $newDateSql;
    $kcmState->ksSetArg('ClDate',$newDateSql);  
    $roster->schedule->overrideEntryDate($db,$roster,$kcmState->ksEntryDateSql);
}

function kcm_scheduleToday($db,$kcmState,$roster) {
    $roster->schedule->setTodayDefaults($db,$roster);
    $kcmState->ksEntryDateSql = $roster->schedule->entryDateSql;
    $kcmState->ksSetArg('ClDate',$roster->schedule->entryDateSql);  
}

function kcm__getProgramNameFromType( $progType ) {
// Note: make sure returned names are html-safe
	switch ($progType) {
		case RC_PROG_TYPE_CLASS: return '';
		case RC_PROG_TYPE_CAMP: return 'Camp';  //???? with date - especially if dup
		case RC_PROG_TYPE_TOURNAMENT: return 'Tournament';
		case RC_PROG_TYPE_SPECIAL: return 'Special Event';
		case RC_PROG_TYPE_NON_REG: return 'Non-registration Event';
		default: return "[Kid Chess Program type {$progType}]";
	}
}
function kcm_formatSqlDate( $mySqlDate, $pDateForm = 'D, M j, Y' ) {
    if (rc_isZeroDate( $mySqlDate )) {     
		return "";
	}
	$date = date_create_from_format( 'Y-m-d', $mySqlDate );
	if ($date === FALSE) {
		return ""; 
	}
	$text = date_format( $date, $pDateForm );
	if ($text === FALSE) {
		return ""; 
	}
	return $text;
}


?>