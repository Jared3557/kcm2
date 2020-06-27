<?php

// gateway-system-globals.inc.php

class kcmGateway_globals extends kcmKernel_globals  {
public $gb_menu;

function __construct() {
    //--- Define Standard Kcm-Gateway standard strings
    //---     for this particular kcm-system such as system-name, system-icon, and system-emitter
    parent::__construct('KCM-Gateway','../kcm-kernel/images/banner-icon-kcm.gif','kcmKernel_emitter') ;
    //--- Define Standard Kcm-Gateway user security
    $this->gb_owner = new kcmKernel_security_user($this, NULL);
    $this->gb_user  = new kcmKernel_security_user($this, $this->gb_owner);
    $this->gb_banner_image_system = 'kcm-banner-gateway.gif';
    $this->gb_menu = new Draff_Menu_Engine;
}

function gb_kernelOverride_getStandardUrlArgList() {
    $args = array();
    return $args;
}

function gwy_getKcmVersionSymbol($kcmVersion) {
    if ( $kcmVersion==1) {
        return '&hookrightarrow;';
    }
	return ($kcmVersion == 2) ? '&xrArr; ' : '&rarr; ';
}

function gwy_redirectToKcm1ProgramStart( $appGlobals, $chain, $programId ) {
    $url = self::gwy_getKcm1ProgramUrl( $appGlobals, $chain, $programId );
    rc_redirectToURL( $url );
}

function gwy_getKcm1ProgramUrl( $appGlobals, $chain, $programId ) {
    $sql = array();
    $sql[] = "Select `pPe:PeriodId`,`pPr:KcmVersion`";
    $sql[] = "FROM `pr:period`";
    $sql[] = "JOIN `pr:program` ON `pPr:ProgramId` = `pPe:@ProgramId`";
    $sql[] = "WHERE `pPe:@ProgramId` = '{$programId}'";
    $sql[] = "   AND `pPe:HiddenStatus` = '0'";
    $sql[] = "ORDER BY `pPe:PeriodSequenceBits`";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query( $query );
    if ( ($result === FALSE) || ($result->num_rows ==0)) {
        draff_errorTerminate( $query);
    }
    if ( $row=$result->fetch_array()) {
        //???? check authorization for this program ??
        $periodId = $row['pPe:PeriodId'];
        $kcmVersion = $row['pPr:KcmVersion'];
    }
    else {
        $message = "Error - This program has no periods.";  //??????????????????????????????
        $argSubmit = 'program';
    }
    //if ( $override==1) {
    //    $kcmVersion = 1;
    //}
    //else if ( $override==2) {
    //    $kcmVersion = 2;
    //}
    //else if ( $override) {
    //    $kcmVersion = ($kcmVersion==2) ? 1 : 2;
    //}
    if ( $kcmVersion == 2) {
       $url = $chain->chn_url_build_unchained_url('../kcm-roster/roster-home.php' , FALSE, array( 'PrId'=>$programId) );
    }
    else {
         $url = "../../kcm-periodHome.php?kcmp=_PrId-{$programId}_PeId-{$periodId}";
    }
    return $url;
}

function gb_ribbonMenu_Initialize($chain, $menu, ...$overrides ) {   /* required function - called by kernel emitter*/
    //$menu = $emitter->emit_menu;
    $this->gb_menu->drMenu_addLevel_top_start();
    $this->gb_menu->drMenu_addItem($chain,'ls-sd','My<br>Schedule'  , 'gateway.php');
    $this->gb_menu->drMenu_addItem($chain,'ls-me','My<br>Events'  , 'gateway-events.php',array('drfMode'=>'1'));
    $this->gb_menu->drMenu_addItem($chain,'ls-ae','All<br>Events'  , 'gateway-events.php',array('drfMode'=>'2'));
    $this->gb_menu->drMenu_addItem($chain,'ls_cl','Staff<br>List','gateway-staffList.php');
    $this->gb_menu->drMenu_addItem($chain,'ls_sl','School<br>List','gateway-schoolList.php');
    $this->gb_menu->drMenu_addItem($chain,'ls_ul','Useful<br>Links','gateway-links.php');
    $this->gb_menu->drMenu_addItem($chain,'kcm_home','Original<br>KCM'  , '../../kcm.php');
    if ($this->gb_owner->krnUser_isSysAdmin) {
        $this->gb_menu->drMenu_addItem($chain,'kcm_home','Set<br>Proxy'  , 'gateway-setup-proxy.php');
    }
    $this->gb_menu->drMenu_addLevel_top_end();
    $this->gb_menu->drMenu_markTopLevelItem('kcm_home');
    $this->gb_menu->drMenu_markTopLevelItem('ls-sd');
    $this->gb_menu->drMenu_markTopLevelItem('ls-me');
    $this->gb_menu->drMenu_markTopLevelItem('ls-ae');
    $this->gb_menu->drMenu_markTopLevelItem('ls-sd');
    $this->gb_menu->drMenu_markTopLevelItem('ls_cl');
    $this->gb_menu->drMenu_markTopLevelItem('ls_sl');
    $this->gb_menu->drMenu_markTopLevelItem('ls_ul');
}

}  // end class


//- function myEvents_makeNamesUnique($programMap) {
//-     $today = rc_getNowDate();
//-     $schoolMap = array();
//-     foreach ( $programMap as $program) {
//-         $type = $program->prog_progType;
//-         if ($type==1) {
//-             $schoolId = $program->prog_schoolId;
//-             if (isset( $schoolMap[$schoolId])) {
//-                 ++$schoolMap[$schoolId];
//-             }
//-             else {
//-                 $schoolMap[$schoolId] = 1;
//-             }
//-         }
//-     }
//-     foreach ( $programMap as $program) {
//-         $type = $program->prog_progType;
//-         if ($type==1) {
//-             $schoolId = $program->prog_schoolId;
//-             if ($schoolMap[$schoolId] > 1) {
//-                 $start = $program-> prog_dateFirst;
//-                 $end   = $program-> prog_dateLast;
//-                 if ( ($end<$today) or ($start>$today) ) {
//-                     $name = $program->prog_programName;
//-                     $semYear = $program->prog_schoolYear;
//-                     $semCode = $program-> prog_semester;
//-                     $semName = rc_getSemesterAndYearNameFromYearAndCodeList( $semYear, $semCode );
//-                     $program->prog_programName = $name . ' (' . $semName . ')';
//-                 }
//-             }
//-         }
//-     }
//- }

function gwy_get_scheduleWhenRange($appGlobals , $whenStart=NULL, $whenEnd=NULL, $forceWeek=FALSE) {
    $whenStart = '2019-11-12 12:00:00';
    // default when is 24 hours starting from now
    if ($whenStart == NULL) {
        $whenStart = $appGlobals->gb_getNow();
    }
    $whenStart = substr($whenStart,0,10) . ' ' . draff_timeIncrement( $whenStart , -60); // include todays classes an hour past end of class
    if ($whenEnd == NULL) {
        $whenEnd = $appGlobals->gb_getNow();
        $whenEnd = draff_dateIncrement( $whenEnd , 1); // tomorrow
        //  ??????
        $whenEnd = substr($whenEnd,0,10) . ' ' . draff_timeIncrement( $whenEnd , -240); // do include tommorrows classes until todays classes have mostly ended
    }
    $dateStart =  substr($whenStart,0,10);
    $dateEnd   =  substr($whenEnd,0,10);
    return array();  // $list($dateStart, $dateEnd, $whenStart, $whenEnd ) = gwy_get_scheduleWhenRange();
}

function gwy_get_authorizedDateRange ($appGlobals) {
    $today = '2019-11-12';  // should get from globals
    $before = -14;
    $after = 14;
    $dateStart = draff_dateIncrement( $today, $before);
    $dateEnd   = draff_dateIncrement( $today, $after);
    return array('dateStart'=>$dateStart,'dateEnd'=>$dateEnd,'today'=> $today);
}

function gwy_get_scheduleStaffId($appGlobals , $staffId=NULL) {
    if ($staffId === NULL) {
        $staffId = $appGlobals->gb_user->krnUser_staffId;
    }
    return $staffId;
}


function gwy_fetch_subQuery_myScheduledPrograms($appGlobals , $query, $staffId=NULL, $whenStart=NULL, $whenEnd=NULL) {
    // only programs on the schedule
    if ($staffId === NULL) {
        $staffId = $appGlobals->gb_user->krnUser_staffId;
    }
    $whenStart = '2019-11-12 12:00:00';
    // default when is 24 hours starting from now
    if ($whenStart == NULL) {
        $whenStart = $appGlobals->gb_getNow();
    }
    $whenStart = substr($whenStart,0,10) . ' ' . draff_timeIncrement( $whenStart , -60); // include todays classes an hour past end of class
    if ($whenEnd == NULL) {
        $whenEnd = $appGlobals->gb_getNow();
        $whenEnd = draff_dateIncrement( $whenEnd , 1); // tomorrow
        //  ??????
        $whenEnd = substr($whenEnd,0,10) . ' ' . draff_timeIncrement( $whenEnd , -240); // do include tommorrows classes until todays classes have mostly ended
    }
    $dateStart =  substr($whenStart,0,10);
    $dateEnd   =  substr($whenEnd,0,10);

    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddString( "`cSD:@ProgramId`");
    $query->rsmDbq_add( "FROM `ca:scheduledate`");
    $query->rsmDbq_add( "JOIN `ca:scheduledate_staff` ON (`cSS:@ScheduleDateId` = `cSD:ScheduleDateId`)");
    $query->rsmDbq_add( "    AND (`cSS:@StaffId` = '{$staffId}')");
    // testing dates redundant, but should make db much faster as first date checks uses index
    // so most records will be eliminated quickly,  and then test for the more precise time using when's
    $query->rsmDbq_add( "WHERE (`cSD:ClassDate` >= '{$dateStart}') AND (`cSD:ClassDate` <= '$dateEnd') " );
    $query->rsmDbq_add( "  AND (CONCAT ( `cSD:ClassDate`, ' ', `cSD:EndTime`) >= '{$whenStart}') AND (CONCAT ( `cSD:ClassDate`, ' ', `cSD:EndTime`) <= '$whenEnd')") ;

}


function gwy_fetch_subQuery_myAuthorizedPrograms($appGlobals , $query, $staffId=NULL, $whenStart=NULL, $whenEnd=NULL) {
    $staffId = gwy_get_scheduleStaffId($appGlobals , $staffId);
    $result = gwy_get_authorizedDateRange($appGlobals);
    $dateStart = $result['dateStart'];
    $dateEnd   = $result['dateEnd'];
    $today     = $result['today'];
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddString( "`cSD:@ProgramId` as `ProgramId`");
    $query->rsmDbq_add( "FROM `ca:scheduledate`");
    $query->rsmDbq_add( "JOIN `ca:scheduledate_staff` ON (`cSS:@ScheduleDateId` = `cSD:ScheduleDateId`)");
    $query->rsmDbq_add( "    AND (`cSS:@StaffId` = '{$staffId}')");
    $query->rsmDbq_add( "WHERE (`cSD:ClassDate` >= '{$dateStart}') AND (`cSD:ClassDate` <= '$dateEnd') " );
    $query->rsmDbq_add( "UNION") ;
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddString( "`pPr:ProgramId`  as `ProgramId`");
    $query->rsmDbq_add( "FROM `ca:programauthorization`");
    $query->rsmDbq_add( "JOIN `pr:program` ON (`pPr:ProgramId` = `cPA:@ProgramId`)");
    $query->rsmDbq_add( "   OR( ");
    $query->rsmDbq_add( "   (`pPr:@SchoolId` = `cPA:@SchoolId`) AND (`pPr:DayOfWeek`=`cPA:ProgramDow`) ");
    $query->rsmDbq_add( "   AND (`pPr:DateClassFirst` <= `cPA:DateExpires`) AND (`pPr:DayOfWeek`=`cPA:ProgramDow`) ");
    $query->rsmDbq_add( "   AND (`pPr:DateClassLast` >= '{$dateStart}') AND (`pPr:DateClassFirst`<='{$dateEnd}') ");
    $query->rsmDbq_add( "   )");
    $query->rsmDbq_add( "WHERE (`cPA:@StaffId`='{$staffId}') AND (`cPA:DateExpires`>='{$today}') " );
    $query->rsmDbq_add( "GROUP BY `ProgramId` " );
}

function gwy_fetch_selectMap_myStaff($appGlobals , $staffId=NULL, $whenStart=NULL, $whenEnd=NULL) {
    $query = new draff_database_query;
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_staff');
    $query->rsmDbq_add( "FROM `pr:program`");
    $query->rsmDbq_add( "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
    $query->rsmDbq_add( "JOIN `ca:scheduledate` on `cSD:@ProgramId` = `pPr:ProgramId`");
    $query->rsmDbq_add( "JOIN `ca:scheduledate_staff` ON (`cSS:@ScheduleDateId` = `cSD:ScheduleDateId`)");
    $query->rsmDbq_add( "JOIN `st:staff` ON `sSt:StaffId` = `cSS:@StaffId`" );
    $query->rsmDbq_add( "WHERE `pPr:ProgramId` IN (" );
    gwy_fetch_subQuery_myScheduledPrograms($appGlobals , $query, $staffId, $whenStart, $whenEnd);
    $query->rsmDbq_add( ")" );
    $query->rsmDbq_add( "GROUP BY `sSt:StaffId`" );
    $query->rsmDbq_add( "ORDER BY `sSt:FirstName`, `sSt:LastName`");
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
    $myStaffSelect = array();
    foreach ($result as $row) {
        $staffId = $row['sSt:StaffId'];
        $myStaffSelect[$staffId] = $row['sSt:FirstName'] . ' ' . $row['sSt:LastName'];
    }
    return $myStaffSelect;
}

function gwy_fetch_selectMap_mySchools($appGlobals , $query=NULL, $staffId=NULL, $whenStart=NULL, $whenEnd=NULL) {
    $query = new draff_database_query;
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_add( "FROM `pr:program`");
    $query->rsmDbq_add( "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
    $query->rsmDbq_add( "WHERE `pPr:ProgramId` IN (" );
    gwy_fetch_subQuery_myScheduledPrograms($appGlobals , $query, $staffId, $whenStart, $whenEnd);
    $query->rsmDbq_add( ")" );
    $query->rsmDbq_add( "ORDER BY `pSc:NameShort`");
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
    $mySchoolsSelect = array();
    foreach ($result as $row) {
        $schoolId = $row['pSc:SchoolId'];
        $mySchoolsSelect[$schoolId] = $row['pSc:NameShort'];  // should use uniquafier for name ??????????
    }
    return $mySchoolsSelect;
}

//function gwy_fetch_selectMap_myPrograms($appGlobals , $staffId=NULL, $whenStart=NULL, $whenEnd=NULL) {
//// just a list of programs without additional information
//// programs selected are a combination of scheduled and aurhorized programs
////???????????????? how to make names unique when school has multiple semesters ??????????????????????
//    $query = new draff_database_query;
//    $query->rsmDbq_selectAddColumns('dbRecord_program');
//    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
//    $query->rsmDbq_add( "FROM `pr:program`");
//    $query->rsmDbq_add( "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
//    $query->rsmDbq_add( "WHERE `pPr:ProgramId` IN (" );
//    gwy_fetch_subQuery_myScheduledPrograms($appGlobals , $query, $staffId, $whenStart, $whenEnd);
//    $query->rsmDbq_add( ")" );
//    $query->rsmDbq_add( "ORDER BY `pSc:NameShort`");
//    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
//    $myProgramsRecords = array();
//    foreach ($result as $row) {
//        $program = new dbRecord_program($row);
//        $myProgramsRecords[$program->prog_programId] = $program;
//    }
//    // check for same school but different semesters
//    $myProgramsSelect = $array();
//    foreach ($myProgramsRecords as $programId => $program ) {
//        $myProgramsSelect[$programId] = $program->prog_programName ;
//    }
//    return $mySchoolsSelect;
//}
//
?>