<?php

// rc2_rpt-adminReports.php

ob_start();  // output buffering (needed for redirects, header changes)

include_once( 'rc_defines.inc.php' );
include_once( 'rc_messages.inc.php' );
include_once( 'rc_database.inc.php' );
include_once( 'rc_admin.inc.php' );
//include_once( 'rc2_common-lib.inc.php' );
include_once( 'kcm-libAsString.inc.php' );
include_once( 'kcm-libKcmState.inc.php' );
include_once( 'kcm-libKcmFunctions.inc.php' );
include_once( 'kcm-page-Engine.inc.php' );
include_once( 'kcm-libNavigate.inc.php' );
include_once( 'kcm-roster.inc.php' );
include_once( 'kcm-roster_objects.inc.php' );

const SEMESTER_NOW_KEY = '000000';

rc_session_initialize();

//**************************
//* Get Args and KCM State *
//**************************
$argSubmit = kcm_getParam('Submit','');
$thisPage = 'kcm-rpt-PointTally.php';
$isExport = ($argSubmit==='e' or $argSubmit==='p');
$kcmState = new kcm_kcmState();
$kcmState->ksCatchBadProgramId();

//*************************
//* HTML header and Login *
//*************************
$navigate = new kcm_libNavigate($kcmState);
$navigate->addStyleSheet('css/rc_common.css');
$navigate->addStyleSheet('css/rc_admin.css');
$navigate->addStyleSheet('css/kcm-common_css.css');
$navigate->addStyleSheet('css/kcm-common_screen.css','screen');
$navigate->addStyleSheet('css/kcm-common_print.css','print');
$navigate->addStyleSheet('css/kcm/kcm.css');
$navigate->addStyleSheet('css/rc2/rc2_rpt-programReports.css','');
$navigate->setTitle('Print Point Tally','Print Point Tally');
$navigate->processLoginLogout($argSubmit,TRUE);

//*****************
//* Get KCM State *
//*****************
//$argProgramId = $kcmState->argProgramId;

$argProgramId = $kcmState->ksProgramId;
//$argProgramId = rc2_get_param('PrId');
if ($argProgramId<1) {
    echo 'You need the program Id for this';
    exit;
}
$argReportMode = kcm_getParam('mode','drop');
//if ($argReportMode=='') {
//    echo 'You need the report mode for this';
//    exit;
//}
    

// open the global database
$dbSuccess = rc_openGlobalDatabase();
if ( ! $dbSuccess) {
	rc_queueError("Database connection error.");
}
$db = rc_getGlobalDatabaseObject();

//***************
//* Final Inits *
//***************

switch ($argReportMode) {
    case 'wait':
        $reportTitle = 'Wait List Report';
        $includeOld = FALSE;
        break;    
    case 'email':
        $reportTitle = 'Email List Report';
        $includeOld = FALSE;
        break;    
    default:
        $reportTitle = 'Drop List Report';
        $includeOld = TRUE;
        break;    
}
//@@@@@@@@@@@@@@
//@ Start Page @
//@@@@@@@@@@@@@@

$page = new kcm_pageEngine();
//$page->textBreak();

//rc2_htmlPage_headStart('Program Reports Selection');
//rc2_htmlPage_headStyleSheet('css/kcm/kcm.css');
//rc2_htmlPage_headStyleSheet('css/rc2/rc2_rpt-programReports.css');
////rc2_htmlPage_headStyleSheet('css/rc2/rc2_report-enrollmentStats.css');
////rc2_htmlPage_headCloseWindowJS();
//rc2_htmlPage_headEnd();
//rc2_htmlPage_bodyStart('Program Reports Selection', '');

$prevProgramId = getPrevProgramId($db, $argProgramId);
$transferList = getTransferredKids($db, $prevProgramId);

if ($argReportMode == 'wait') {
    $newRoster = new kcm_roster($argProgramId,0,TRUE); 
    loadRoster($newRoster,'w');  
    $reportTitle = 'Wait List Report';
    $navigate->setClassData($newRoster);
    $navigate->setTitleStandard($reportTitle,$reportTitle);
    $navigate->show();  //??????????????????? not for export
    printReportTitle($argReportMode,$page, 'w',$reportTitle ,$newRoster);
    $page->rpt_tableStart('');
    printKidListSection($argReportMode,$page, 'w','Wait List',$newRoster,NULL,NULL);
    $page->rpt_tableEnd();
    exit;
}

if ($argReportMode == 'email') {
    $newRoster = new kcm_roster($argProgramId,0,TRUE); 
    loadRoster($newRoster);  
    $reportTitle = 'Mail List Report';
    $navigate->setClassData($newRoster);
    $navigate->setTitleStandard($reportTitle,$reportTitle);
    $navigate->show();  //??????????????????? not for export
    printReportTitle($argReportMode,$page, 'm',$reportTitle ,$newRoster);
    $page->rpt_tableStart('');
    printKidListSection($argReportMode,$page, 'm','Mail List',$newRoster,NULL,NULL);
    $page->rpt_tableEnd();
    exit;
}

$newRoster = new kcm_roster($argProgramId,0,TRUE); 
loadRoster($newRoster);  

if ($includeOld) {
    if ($prevProgramId == 0) {
        $oldRoster = NULL;
    }
    else {
        $oldRoster = new kcm_roster($prevProgramId,0,TRUE); 
        loadRoster($oldRoster, '', $newRoster->program->SchoolYear);  
    }    
}    

//***********************
//* Menu and Page Title *
//***********************
$navigate->setClassData($newRoster);
$navigate->setTitleStandard($reportTitle,$reportTitle);
$navigate->show();  //??????????????????? not for export
// compute status of each kid
if (($oldRoster==NULL) OR ($oldRoster->kidCount == 0)) {  // jpr 2017-06-29 added if
    $oldDropped = array(0);   
    $oldDupCount = array(0);  
}
else {
    $oldDropped = array_fill ( 0, $oldRoster->kidCount, -1);   // jpr 2017-06-29 was 0
    $oldDupCount = array_fill ( 0, $oldRoster->kidCount, -1);  // jpr 2017-06-29 was 0
}    
if ($newRoster->kidCount == 0) {  // jpr 2017-06-29 added if
    $newDropped = array(0);   
    $newDupCount = array(0);  
}
else {
    $newDropped = array_fill ( 0, $newRoster->kidCount, -1);   // jpr 2017-06-29 was 0
    $newDupCount = array_fill ( 0, $newRoster->kidCount, -1);  // jpr 2017-06-29 was 0
}    
if ($oldRoster != NULL) {
    for ($i = 0; $i < $oldRoster->kidCount; $i++) {
        $oldKid = $oldRoster->kidArray[$i];  
        //$oldFirst = $oldKid->prg->FirstName;
        //$oldLast  = $oldKid->prg->LastName;
        $oldKidId     = $oldKid->prg->KidId;
        $match = 0;
        for ($j = 0; $j < $newRoster->kidCount; $j++) {
            $newKid = $newRoster->kidArray[$j];  
            $newKidId     = $newKid->prg->KidId;
            // could also check for name here if dup
            if ($newKidId == $oldKidId) {
                $oldDropped[$i] = $j;
                $newDropped[$j] = $i;
                ++$oldDupCount[$i];  
                ++$newDupCount[$j];
            }    
            if ( ($newKid->prg->FirstName == $oldKid->prg->FirstName) and ($newKid->prg->LastName == $oldKid->prg->LastName) ) {
                ++$oldDupCount[$i];  
                ++$newDupCount[$j];
            }
        }
    }
}

//$page->textOut('Drop List Report');
//$page->textBreak();
printReportTitle($argReportMode,$page, 'd',$reportTitle,$newRoster);
$page->rpt_tableStart('');

printKidListSection($argReportMode,$page, 'd','Drop List',$oldRoster,$oldDropped,$oldDupCount,$transferList);
printKidListSection($argReportMode,$page, 'r','Renewals (Veterans)',$newRoster,$newDropped,NULL);
printKidListSection($argReportMode,$page, 'n','New Kids (Rookies) and Veterans not enrolled previous semester',$newRoster,$newDropped,NULL);
printKidListSection($argReportMode,$page, '6','Kids in Grade 6 or higher',$oldRoster,$newDropped,NULL);
$page->rpt_tableEnd();

//rc2_htmlPage_AdminBodyEnd();


//=================================================
//==   Main code ends here
//==   Classes and Functions Start Here 
//=================================================

function printReportTitle($mode, $page, $section, $pTitle, $pNewRoster) {
    $page->headingStart('ProgramReportsPortrait'); //??? is kpage style needed here
    $page->headingText($pTitle);
    $page->headingNextColumn();
    $page->headingProgram($pNewRoster);
    $page->headingSemester($pNewRoster);
    $page->headingEnd();
}

function printHeading($mode, $page, $section, $pTitle) {
    $page->rpt_rowStart('');
    $page->rpt_cellOfText('pgrHeading',$pTitle,'colspan="99"');
    $page->rpt_rowEnd('');
    $page->rpt_rowStart('');
    if ($mode=='email') {
        $page->rpt_cellOfText('pgrFirst','First');
        $page->rpt_cellOfText('pgrLast','Last');
        $page->rpt_cellofText('pgrGrade',"Grade");
        $page->rpt_cellofText('pgrPeriod',"Period");
        $page->rpt_cellofText('pgrStatus',"Status");
        $page->rpt_cellofText('pgrEmail',"Parent-1");
        $page->rpt_cellofText('pgrEmail',"Email-1");
        $page->rpt_cellofText('pgrEmail',"Parent-2");
        $page->rpt_cellofText('pgrEmail',"Email-2");
    }
    else {
        $page->rpt_cellOfText('pgrFirst','First');
        $page->rpt_cellOfText('pgrLast','Last');
        $page->rpt_cellofText('pgrGrade',"Grade");
        $page->rpt_cellofText('pgrPeriod',"Period");
        $page->rpt_cellofText('pgrStatus',"Status");
        $page->rpt_cellofText('pgrPhone',"Home");
        $page->rpt_cellofText('pgrPhone',"Cell");
        $page->rpt_cellofText('pgrParent',"Parents");
        $page->rpt_cellofText('pgrParent',"Email");
        if ($mode=='wait') {
             $page->rpt_cellofText('pgrInvoiceNum','Invoice No');
             $page->rpt_cellofText('pgrInvoiceDate','Invoice Date');
        }     
    }    
    $page->rpt_rowEnd('');
}

function printKid($mode, $page, $kid, $pRoster, $transferList=NULL) {
    $periodName = $kid->getPeriodDesc(kcmPERIODFORMAT_SHORT);    
    $homePhone = $kid->prg->parent1->HomePhone;
    $cellPhone = $kid->prg->parent1->CellPhone;
    $name1 = $kid->prg->parent1->FirstName . ' ' . $kid->prg->parent1->LastName;
    $email = $kid->prg->parent1->Email;
    if ( $kid->prg->parent2!=NULL) {
        $email2 = $kid->prg->parent2->Email;
        if ( $email2 == $kid->prg->parent1->Email )
            $email2 = '';
        if ($email2 != '')
        $email =   $email . '<br>' . $email2;       
        $p1= $kid->prg->parent2->HomePhone;
        $p2= $kid->prg->parent2->CellPhone;
        if ( ($p1 == $homePhone) or ( $p1 == $cellPhone ))
            $p1 = '';
        if ( ($p2 == $homePhone) or ( $p2 == $cellPhone ) or ($p2==$p1))
            $p2 = '';
        if ($p1!='')
           $homePhone = $homePhone . '<br>' . $p1;        
        if ($p2!='')
           $homePhone = $homePhone . '<br>' . $p2;        
        $name2 = $kid->prg->parent2->FirstName . ' ' . $kid->prg->parent2->LastName;
    }
    else {
        $p1= '';
        $p2= '';
        $p3= '';
        $email2 = '';
        $name2 = '';
    }    
    $page->rpt_rowStart('');
    if ($mode=='email') {
        $s = $kid->prg->FirstName;
        $s = str_replace ('-->','',$s);
        $page->rpt_cellOfText('pgrFirst',$s);
        $page->rpt_cellOfText('pgrLast',$kid->prg->LastName);
        $page->rpt_cellofText('pgrGrade',$kid->prg->GradeDesc);
        $page->rpt_cellofText('pgrPeriod',$periodName);
        $page->rpt_cellofText('pgrStatus',kcmAsString_Rookie($kid));
        $page->rpt_cellofText('pgrEmail',$name1);
        $page->rpt_cellofText('pgrEmail',$kid->prg->parent1->Email);
        $page->rpt_cellofText('pgrEmail',$name2);
        $page->rpt_cellofText('pgrEmail',$email2);
    }
    else {
        $page->rpt_cellOfText('pgrFIrst',$kid->prg->FirstName);
        $page->rpt_cellOfText('pgrLast',$kid->prg->LastName);
        if (isset($transferList[$kid->prg->KidId])) {
             $page->rpt_cellofText('pgrPeriod',$transferList[$kid->prg->KidId],'colspan="7"');
        }
        else {
            $page->rpt_cellofText('pgrGrade',$kid->prg->GradeDesc);
            $page->rpt_cellofText('pgrPeriod',$periodName);
            $page->rpt_cellofText('pgrStatusRookie',kcmAsString_Rookie($kid));
            $page->rpt_cellofText('pgrPhone',$homePhone);
            $page->rpt_cellofText('pgrPhone',$cellPhone);
            $page->rpt_cellofText('pgrParent',$name1);
            $page->rpt_cellofText('pgrEmail',$email);
        }    
        if ($mode=='wait') {
             $page->rpt_cellofText('pgrInvoiceNum',$kid->per->InvoiceNo);
             $page->rpt_cellofText('pgrInvoiceDate',$kid->per->InvoiceDate);
        }     
    }    
    $page->rpt_rowEnd('');
}

function printKidListSection($mode, $page, $section, $pTitle,$pRoster,$pDropped, $dupList,$transferList=NULL) {
   
    if ($pRoster == NULL) {
        $page->rpt_rowStart('');
        $page->rpt_cellOfText('','<h1 style="color:red">Previous semester for school not found</h2>','colspan="99"');
        $page->rpt_rowEnd('');
        return;
    }
    if ($section != 'd') {
        $page->rpt_rowStart('');
        $page->rpt_cellOfText('','.','colspan="99"');
        $page->rpt_rowEnd('');
    }
    printHeading($mode, $page, $section, $pTitle);
    $count = 0;
    for ($i = 0; $i < $pRoster->kidCount; $i++) {
        $kid = $pRoster->kidArray[$i];
        $ok = FALSE;
        if ( ($dupList != NULL) and ($dupList[$i]>=0) ) { // jpr 2017-06-29 was 1
            $ok = FALSE;
        }    
        else if ($mode == 'wait') {
            $ok = TRUE;
        }
        else if ($mode == 'email') {
            $ok = TRUE;
        }
        else if ($section == '6') {
            if ($kid->prg->GradeCode >= 6) {
                $ok = TRUE;
            }
        }    
        else {    
            if ($kid->prg->GradeCode >= 6) {
                $ok = FALSE;
            }
            else if ($section == 'd') {
                // not found on new roster - so must be dropped
                if ($pDropped[$i] == -1) { // jpr 2017-06-29 was 0
                    $ok = TRUE;
                }
            }
            else if ($section == 'r') {
                // found on old roster - so must be renewal or new
                if ($pDropped[$i] >= 0) {  
                    $ok = TRUE;
                }
            }
            else if ($section == 'n') {
                // new kid  
                if ($pDropped[$i] == -1) {
                    $ok = TRUE;
                }
            }
        }    
        if ($ok) {
            printKid($mode,$page,$kid, $pRoster, $transferList);
            ++ $count;
        }    
    }        
    $page->rpt_rowStart('');
    $page->rpt_cellOfText('','Count = ' . $count,'colspan="99"');
    $page->rpt_rowEnd('');
}

function loadRoster($pRoster,$mode='',$curYear = NULL) {
    if ($mode == 'w') {
        $pRoster->load_roster_header(); 
        $pRoster->load_roster_kids('w');
    }
    else {
        $pRoster->load_roster_headerAndKids();  
    }
    $pRoster->sort_periodFilter(NULL,TRUE);
    $pRoster->sort_start();
    //$pRoster->sort_byPeriodCurrent('c');
    $pRoster->sort_byFirstName('c');
    $pRoster->sort_end();
    if ($curYear === NULL) {
        $curYear = $pRoster->program->SchoolYear;
    }    
    // if ($pRoster->program->SchoolYear == $curYear) return;  // harmless if commented out
    for ($i = 0; $i < $pRoster->kidCount; $i++) {
        $kid = $pRoster->kidArray[$i];
        $kid->prg->GradeCode = rc_getGradeForYearFromGradeForAnotherYear( $kid->prg->GradeCode, $pRoster->program->SchoolYear, $curYear);
        $kid->prg->GradeDesc = rc_getGradeNameFromNumber($kid->prg->GradeCode);
    }  
}

function getPrevProgramSemesterId($db, $pProgramId, $schoolId,  $curProgType, $curYearSem, $dow) {
    $sql = array();
    $sql[] = "Select `pPr:@SchoolId`,`pPr:SemesterCode`,`pPr:ProgramType`";
    $sql[] = "   ,`pPr:SchoolYear`,`pPr:DayOfWeek`, `pPr:ProgramId`";
    $sql[] = "FROM `pr:program`";
    $sql[] = "WHERE (`pPr:@SchoolId`='$schoolId')";
    if ( !empty($dow) ) {
        $sql[] = "  AND (`pPr:DayOfWeek`='$dow')";
    }    
    $sql[] = "  AND (`pPr:ProgramType`='$curProgType')";
    $query = implode( $sql, ' ');
    //echo '<hr>', $query,'<hr>' ;    
    $result = $db->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    $maxYearSem = 0;
    $maxProgId = 0;
    while ($row=$result->fetch_array()) {
        $newYear = $row['pPr:SchoolYear'];
        $newSemCode = $row['pPr:SemesterCode'];
        $newProgType = $row['pPr:ProgramType'];
        $newYearSem = $newYear . $newSemCode;
        if ( ($newYearSem < $curYearSem) and ($newYearSem > $maxYearSem) ) {
                $maxYearSem = $newYearSem;
                $maxProgId = $row['pPr:ProgramId'];
        }    
    }
    return $maxProgId;
}    

function getPrevProgramId($db, $pProgramId) {
    // get current program info
    $sql = array();
    $sql[] = "Select `pPr:@SchoolId`,`pPr:SemesterCode`,`pPr:ProgramType`";
    $sql[] = "   ,`pPr:SchoolYear`,`pPr:DayOfWeek`";
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
    $semCode = $row['pPr:SemesterCode'];
    $year = $row['pPr:SchoolYear'];
    $dow = $row['pPr:DayOfWeek'];
    $curProgType = $row['pPr:ProgramType'];
    $curYearSem = $year . $semCode;
    //--- find similar programs
    $maxProgId = getPrevProgramSemesterId($db, $pProgramId, $schoolId,  $curProgType,  $curYearSem, $dow);
    

    if ($maxProgId == 0) {
        $maxProgId = getPrevProgramSemesterId($db, $pProgramId, $schoolId,  $curProgType,  $curYearSem, NULL);
    }
    return $maxProgId;
 }

function getTransferredKids($db, $prevSemesterProgramId) {
    $transfers = array();
    if (empty($prevSemesterProgramId)) {
        return;
    }
    
    $query = "SELECT * FROM `pr:program` WHERE `pPr:ProgramId` = '{$prevSemesterProgramId}'"; 
    $result = $db->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    $row=$result->fetch_array();
    $prevYear = $row['pPr:SchoolYear'];
    $prevCode = $row['pPr:SemesterCode'];
    if ($prevCode==40) {
       $nextYear = $prevYear+1;
    }
    else {
       $nextYear = $prevYear;
    }
    $nextCode = ($prevCode == 20) ? 40 : 20;
    

    $sql = array();

    $sql[] = "SELECT";
    $sql[] = "     `rKd:FirstName` as `firstname`, ";
    $sql[] = "     `rKd:LastName` as `lastname`, ";
    $sql[] = "     `kp1`.`rKPr:@ProgramId` as `programid1`, ";
    $sql[] = "     `sc1`.`pSc:NameShort` as `schoolname1`,";
    $sql[] = "     `pr1`.`pPr:DayOfWeek` as `dayofweek1`, ";
    $sql[] = "     `kp2`.`rKPr:@ProgramId` as `programid2`, ";
    $sql[] = "     `sc2`.`pSc:NameShort` as `schoolname2`,";
    $sql[] = "     `pr2`.`pPr:DayOfWeek` as `dayofweek2`, ";
    $sql[] = "     `rKd:KidId` as `kidid`,";
    $sql[] = "     `sc1`.`pSc:SchoolId` as `schoolid1`, ";
    $sql[] = "     `kp1`.`rKPr:Grade` as `grade1`, ";
    $sql[] = "     `pr1`.`pPr:SchoolNameUniquifier` as `uniquifier1`,";
    $sql[] = "     `sc2`.`pSc:SchoolId` as `schoolid2`, ";
    $sql[] = "     `pr2`.`pPr:SchoolNameUniquifier` as `uniquifier2`,";
    $sql[] = "     `kp1`.`rKPr:KidProgramId` as `kidprogramid1`,";
    $sql[] = "     `kp1`.`rKPr:@KidId`";
    $sql[] = "FROM `ro:kid_program` AS `kp1` ";
    $sql[] = "INNER JOIN `pr:program` AS `pr1` ";
    $sql[] = "    ON (`pr1`.`pPr:ProgramId` = `kp1`.`rKPr:@ProgramId`) ";
    $sql[] = "     AND (`pr1`.`pPr:ProgramType` = '1')";
    $sql[] = "     AND (`pr1`.`pPr:SchoolYear`='{$prevYear}')";
    $sql[] = "     AND (`pr1`.`pPr:SemesterCode`='{$prevCode}')";
    $sql[] = "LEFT JOIN `pr:school` AS `sc1` ";
    $sql[] = "    ON `sc1`.`pSc:SchoolId` = `pr1`.`pPr:@SchoolId`";
    $sql[] = "LEFT JOIN `ro:kid_program` AS `kp2`";
    $sql[] = "    ON (`kp1`.`rKPr:@KidId` = `kp2`.`rKPr:@KidId`)";
    $sql[] = "INNER JOIN `pr:program` AS `pr2` ";
    $sql[] = "    ON (`pr2`.`pPr:ProgramId` = `kp2`.`rKPr:@ProgramId`) ";
    $sql[] = "         AND (`pr2`.`pPr:ProgramType` = '1')";
    $sql[] = "         AND (`pr2`.`pPr:SchoolYear`='{$nextYear}')";
    $sql[] = "         AND (`pr2`.`pPr:SemesterCode`='{$nextCode}')";
    $sql[] = "LEFT JOIN `pr:school` AS `sc2` ";
    $sql[] = "    ON `sc2`.`pSc:SchoolId` = `pr2`.`pPr:@SchoolId`";
    $sql[] = "LEFT JOIN `ro:kid` ON `rKd:KidId` = `kp1`.`rKPr:@KidId`";
    $sql[] = "WHERE  (`kp1`.`rKPr:@ProgramId` =  '{$prevSemesterProgramId}')";
    $sql[] = "   AND (";
    $sql[] = "  (`sc1`.`pSc:SchoolId` !=  `sc2`.`pSc:SchoolId`) ";
    $sql[] = "   OR ( (`sc1`.`pSc:SchoolId` = `sc2`.`pSc:SchoolId`) AND (`pr1`.`pPr:DayOfWeek` != `pr2`.`pPr:DayOfWeek`) )";
    $sql[] = ")"; 
    $sql[] = "ORDER BY `firstname`,`lastname`";

    $query = implode( $sql, ' ');
   // deb($query);
    $result = $db->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    while ($row=$result->fetch_array()) {
        $school1Name = $row['schoolname1'];
        $school1Unique = $row['uniquifier1'];
        $school1DOW = rc_getDayOfWeekNameFromNumber($row['dayofweek1']);
        $school2Name = $row['schoolname2'];
        $school2Unique = $row['uniquifier2'];
        $school2DOW = rc_getDayOfWeekNameFromNumber($row['dayofweek2']);
        $kidId = $row['kidid'];
        $kidFirst = $row['firstname'];
        $kidLast = $row['lastname'];
        $sn1 = empty($school1Unique) ? $school1Name : ($school1Name . ' ' . $school1Unique);
        $sn2 = empty($school2Unique) ? $school2Name : ($school2Name . ' ' . $school2Unique);
        $kn = $kidFirst . ' ' . $kidLast;
        $s = $kn . ' transferred to ' . $sn2 ; 
        $transfers[$kidId] = $s;
        
    }
    return $transfers;
}


?>