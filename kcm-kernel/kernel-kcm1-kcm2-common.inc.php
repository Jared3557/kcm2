<?php

//  kernel-kcm1-kcm2-appData.inc.php
// NOT USED ???????

function krnSecurity_getScheduleDateRange($mode='as',$classFirst=NULL) {
    // mode: 'as' = admin schedule, 'ks' = kcm schedule,
    //       'kr' = kcm site manager, 'ka'=authorized
    // switch ($mode) {
    //     case 'as':  $bef = '-13';   $aft = '+7';  $dow = -1;  break;  // schedule - raccoon  ???
    //     case 'ks':  $bef =  '+0';   $aft = '+6';  $dow = -1;  break;  // schedule - kcm
    //     case 'kr':  $bef = '-8';    $aft = '+5';  $dow = -1;  break;  // kcm.php site manager reporting
    //     case 'ka':  $bef = '-14';   $aft = '+14'; $dow = -1;  break;  // kcm.php coach  - and authorize program id in kcm lib
    //     default:    $bef = '-7';    $aft = '+7';  $dow = -1;  break;
    // }
    $bef = '-14'; $aft='+21'; ;  $dow = -1; // added jpr@2018-04  @jpr longer date range
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
    return array ('start'=>$pStartDate,'end'=>$pEndDate);
}

function rgb_authorizeProgramId($pProgramId, $db) {
    // RC_ACCESS_ROSTER can access all programs
    if ( rc_checkAccess (RC_ACCESS_ROSTER))
        return;
    // if not RC_ACCESS_LIMITED cannot access any program
    if ( ! rc_checkAccess (RC_ACCESS_ROSTER_LIMITED)) {
        exit('You are not authorized to access any program');
    }    
    if (isset($_SESSION['kcmSecurity'][$pProgramId])) {
        $expires = $_SESSION['kcmSecurity'][$pProgramId];
        if (rc_getNow() <= $expires) {
            return; // all is good until it expires
        }
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
    $results = krnSecurity_getScheduleDateRange('ka',$classFirst);
    $dateRangeStart = $result['start'];
    $dateRangeEnd   = $results['end'];
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
    if (!isset($_SESSION['kcmSecurity']))  {
        $_SESSION['kcmSecurity'] = array();
    }
    if (!isset($_SESSION['kcmSecurity'][$pProgramId]))  {
        $expires = date_create() ;
        date_modify( $expires, '5 minutes' );
  	    $expWhen = date_format( $expires, 'Y-m-d H:i:s' );
        $_SESSION['kcmSecurity'][$pProgramId] = $expWhen;
    }
}

function sec_getDateRange(&$dateFirst,&$dateLast, $today = NULL) {  // called with null from gateway-start
    // today can be overridden - but only for testing !!!!!!!
    if ($today===NULL) {
        $today = rc_getNowDate();
    }    
    $monthDay = substr($today,5);
    $beforeDays = ($monthDay<='02-10') ? 9*7 : 4*7;
    $afterDays = 14; 
    $dateFirst = draff_dateIncrement( $today,-$beforeDays);
    $dateLast = draff_dateIncrement( $today,$afterDays);
}

function kcm_authorizeProgramId($pProgramId, $db) {
    // RC_ACCESS_ROSTER can access all programs
    if ( rc_checkAccess (RC_ACCESS_ROSTER))
        return;
    // if not RC_ACCESS_LIMITED cannot access any program
    if ( ! rc_checkAccess (RC_ACCESS_ROSTER_LIMITED)) {
        exit('You are not authorized to access any program');
    }    
    if (isset($_SESSION['kcmSecurity'][$pProgramId])) {
        $expires = $_SESSION['kcmSecurity'][$pProgramId];
        if (rc_getNow() <= $expires) {
            return; // all is good until it expires
        }
    }
    $userId = rc_getStaffId();
    $programId = NULL;
    if ($this->secEngine_rst_programId === NULL) {
        return TRUE;  //?????????????? have not selected program yet - authorized to select a program (for gateway, but not for other KCM2 modules)
    }    
    //???? Should session be used to help skip some or all of this code ????
    //???? especially if user stays with same program
    //???? could use session logic when not changing tabs to a different program
    //???? and below logic when logging in first time or changing tabs to a different program
    
    // get date range that staff member must be scheduled for to be authorized
    $dateRangeStart = NULL;
    $dateRangeEnd = NULL;
    $this->sec_getDateRange($dateRangeStart,$dateRangeEnd);
    
    // get program date range and school id
    $sql = array();
    $query ="Select `pPr:@SchoolId`,`pPr:DateClassFirst`,`pPr:DateClassLast` From `pr:program` WHERE `pPr:ProgramId` = '{$this->secEngine_rst_programId}'";
    $result = $this->secEngine_rst_sql->sql_performQuery(  $query , __FILE__ , __LINE__ );
  	if ($result->num_rows != 1) {  
        return FALSE;  // URL contains invalid program Id
    }
    $row=$result->fetch_array();
    $schoolId = $row['pPr:@SchoolId'];
    $dateStart = $row['pPr:DateClassFirst'];
    $dateEnd = $row['pPr:DateClassLast'];
        // figure out if authorized using highest role
    $progIdClause = "`cSD:@ProgramId`='{$programId}'";
    if  ( ($dateEnd < $dateRangeStart) or ($dateStart > $dateRangeEnd) ) {
        // if not in date range need to get current semester(s) to authorize against
        $sql = array();
        $sql[] = "Select `pPr:ProgramId` From `pr:program`";
        $sql[] = "WHERE (`pPr:@SchoolId`='{$schoolId}')";
        $sql[] = "AND  (`pPr:DateClassFirst` <= '{$dateRangeEnd}') AND (`pPr:DateClassLast` >= '{$dateRangeStart}')";
        $query = implode( $sql, ' ');
    //??????????????????????????????????????????????????????????????????????????????????????????
        $result = $this->secEngine_rst_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
        // find current progId (what if two choices????)
        if ($result->num_rows == 0) {
             return FALSE;  // Should never happen - valid program Id not linked to a school
        }
        $p = array();
        while ($row=$result->fetch_array()) {
           $p[] = $row['pPr:ProgramId'];
        }
        $progIdClause = (count($p)==1) ? ("`cSD:@ProgramId`='{$p[0]}'") : ("`cSD:@ProgramId` IN (" . implode(',',$p) . ")");
    }
    
    // see if person is on current schedule for current semester(s) as a site-leader, assistant, or head coach
    $staffId = rc_getStaffId();
    $sql = array();
    $sql[] ="Select `cSD:ClassDate`, `cSS:RoleType`,`pPr:@SchoolId`";
    $sql[] ="FROM `ca:scheduledate`"; 
    $sql[] ="JOIN `ca:scheduledate_staff` ON (`cSS:@ScheduleDateId` = `cSD:ScheduleDateId`) AND (`cSS:@StaffId` ='{$staffId}')"; 
    $sql[] ="JOIN `pr:program` ON `pPr:ProgramId` = `cSD:@ProgramId`"; 
    $sql[] = "WHERE ({$progIdClause}) AND (`cSS:RoleType` IN ('1','2','5') ) ";  // ???? need to handle two progIds
    $sql[] = "   AND (`cSD:ClassDate` BETWEEN '{$dateRangeStart}' AND '{$dateRangeEnd}')";
    $sql[] = "LIMIT 1";
    $query = implode( $sql, ' ');
    //??????????????????????????????????????????????????????????????????????????????????????????
    $result = $this->secEngine_rst_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
	if ($result->num_rows == 0) {
        return FALSE;  // Not on schedule as site leader, etc
    }
    return TRUE;
//   if ($result->num_rows == 0) {
//        exit('You are not authorized to access this program');
//    }
//    // all is good
//    $_SESSION['kcm'] ['AuthProgramId'] = $pProgramId;
//    $_SESSION['kcm'] ['AuthStaffId'] = rc_getStaffId();
    // all is good
    if (!isset($_SESSION['kcmSecurity']))  {
        $_SESSION['kcmSecurity'] = array();
    }
    if (!isset($_SESSION['kcmSecurity'][$pProgramId]))  {
        $expires = date_create() ;
        date_modify( $expires, '5 minutes' );
  	    $expWhen = date_format( $expires, 'Y-m-d H:i:s' );
        $_SESSION['kcmSecurity'][$pProgramId] = $expWhen;
    }
}


?>