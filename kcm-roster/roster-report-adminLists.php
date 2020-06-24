<?php

//--- roster-report-adminLists.php ---

ob_start();  // output buffering (needed for redirects, draff-appHeader changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );

//include_once( 'roster-system-data-tally.inc.php' );

include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-objects.inc.php' );
include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );
//include_once( '../draff/draff-emitter-dom-engine.inc.php' );

include_once( '../kcm-kernel/kernel-emitter.inc.php');
include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'roster-system-functions.inc.php' );
include_once( 'roster-system-globals.inc.php' );
include_once( 'roster-system-data-roster.inc.php');

//include_once( 'roster-system-data-games.inc.php' );
//include_once( 'roster-results-game-edit.inc.php' );
include_once( 'roster-system-data-points.inc.php' );
//include_once( 'roster-results-points-edit.inc.php' );

// include_once( 'kcm1/kcm1-rpt-NameLabels.inc.php');
//include_once( 'kcm2_data-tallyGrid_week.inc.php' );

include_once( 'kcm1/kcm-libAsString.inc.php' );
include_once( 'kcm1/kcm-libKcmState.inc.php' );
include_once( 'kcm1/kcm-libKcmFunctions.inc.php' );
include_once( 'kcm1/kcm-page-ColumnDef.inc.php' );
include_once( 'kcm1/kcm-page-Engine.inc.php' );
include_once( 'kcm1/kcm-page-Styles.inc.php' );
include_once( 'kcm1/kcm-page-DOM.inc.php' );
include_once( 'kcm1/kcm-page-Export.inc.php' );
include_once( 'kcm1/kcm-libNavigate.inc.php' );
include_once( 'kcm1/kcm-roster.inc.php' );
include_once( 'kcm1/kcm-roster_objects.inc.php' );
include_once('../../lib/fpdf/fpdf.php');  //@JPR-2019-11-04 21:55
include_once('../../lib/excel/PHPExcel/PHPExcel.php');
include_once('../../lib/excel/rc_excel/rc_PHPExcel.inc.php');

//@@@@@@@@@@@@@@@@@@@@
//@  Step 1
//@@@@@@@@@@@@@@@@@@@@

class appForm_kcm1_tallyReport extends Draff_Form {  // specify winner

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $appData->apd_reportFilters->rf_form_processExportSubmit( $appData->apd_tallyReport, $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_tallyReport = new report_adminListReports($appData->apd_roster_program->prog_programId,'h',$appChain);
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-select' );
    $appEmitter->set_title('Setup Point Categories');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Admin Lists');
    if ($appData->apd_isExport) {
        return;
    }
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
    $appEmitter->set_menu_customize( $appChain, $appGlobals );
    $appEmitter->addOption_styleFile('kcm-roster/kcm1css/kcm-common_css.css','all','../');
    $appEmitter->addOption_styleFile('kcm-roster/kcm1css/kcm-common_screen.css','all','../');
    $appEmitter->addOption_styleFile('kcm-roster/kcm1css/kcm-common_print.css','all','../');
    $appEmitter->addOption_styleFile('kcm-roster/kcm1css/kcm-rpt-NameLabels.css','all','../');
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $appData->apd_reportFilters->rf_form_initControls($this);
}

function drForm_outputPage ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->krnEmit_output_htmlHead  ( $appData, $appGlobals, $appChain, $appEmitter );
    $appEmitter->krnEmit_output_bodyStart ( $appData, $appGlobals, $appChain, $this );
    $appEmitter->krnEmit_output_ribbons  ( $appData, $appGlobals, $appChain, $this );
    $this->drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter );
    $this->drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter );
    $this->drForm_outputFooter  ( $appData, $appGlobals, $appChain, $appEmitter );
    $appEmitter->krnEmit_output_bodyEnd  ( $appData, $appGlobals, $appChain, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    if ($appData->apd_isExport) {
        return;
    }
    $appEmitter->zone_start('draff-zone-filters-default');
    $appData->apd_reportFilters->rf_form_outputHeader($appEmitter);
    $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    if ($appData->apd_isExport) {
        $appData->apd_tallyReport->kcr_print($appData->apd_exportCode);
        return;
    }
    $appEmitter->zone_start('draff-zone-content-default');
    $appData->apd_tallyReport->kcr_print($appData->apd_exportCode);
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    if ($appData->apd_isExport) {
        return;
    }
}

}  // end class

class appData_AdminReports extends draff_appData {
public $apd_roster_program;
public $apd_tallyReport;
public $apd_isExport = FALSE;
public $apd_exportCode = 'h';
public $apd_reportFilters;

function apd_formData_get( $appGlobals, $appChain ) {
    $this->apd_isExport = ( ($this->apd_exportCode=='p') or ($this->apd_exportCode=='e') );
    $appGlobals->gb_isExport = $this->apd_isExport; //???????????????
    $this->apd_reportFilters = new report_filters('Roster');;
    $this->apd_reportFilters->rf_export_pdf = FALSE;
    $this->apd_reportFilters->rf_export_excel = FALSE;
    $this->apd_reportFilters->rf_period_options = FALSE;
    $this->apd_reportFilters->rf_sort_list =array('fn'=>'First Name','ln'=>'Last Name');
    $this->apd_reportFilters->rf_group_list =array('fn'=>'(none)','gr'=>'Grade');
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

} // end class

// const SEMESTER_NOW_KEY = '000000';

class report_adminListReports {

public $kcr_programId;
public $kcr_exportCode;
public $kcr_isExport;
public $kcr_page;
public $kcr_kidCount;
public $kcr_argReportMode;

function __construct ($programId, $exportType, $appChain) {
    $this->kcr_programId  = $programId;
    $this->kcr_exportType = $exportType;
    $this->kcr_argReportMode = draff_urlArg_getOptional('mode');
}

function kcr_print($exportCode) {
    $this->kcr_exportCode = $exportCode;
    $this->kcr_isExport = (($exportCode=='p') or ($exportCode=='e'));

// open the global database
$dbSuccess = rc_openGlobalDatabase();
if ( ! $dbSuccess) {
	rc_queueError("Database connection error.");
}
$db = rc_getGlobalDatabaseObject();

//***************
//* Final Inits *
//***************


switch ($this->kcr_argReportMode) {
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

$prevProgramId = $this->getPrevProgramId($db, $this->kcr_programId);
$transferList = $this->getTransferredKids($db, $prevProgramId);

if ($this->kcr_argReportMode == 'wait') {
    $newRoster = new kcm_roster($this->kcr_programId,0,TRUE);
    $this->loadRoster($newRoster,'w');
    $reportTitle = 'Wait List Report';
    //$navigate->setClassData($newRoster);
    //$navigate->setTitleStandard($reportTitle,$reportTitle);
    //$navigate->show();  //??????????????????? not for export
    $this->printReportTitle($this->kcr_argReportMode,$page, 'w',$reportTitle ,$newRoster);
    $page->rpt_tableStart('');
    $this->printKidListSection($this->kcr_argReportMode,$page, 'w','Wait List',$newRoster,NULL,NULL);
    $page->rpt_tableEnd();
    exit;
}

if ($this->kcr_argReportMode == 'email') {
    $newRoster = new kcm_roster($this->kcr_programId,0,TRUE);
    $this->loadRoster($newRoster);
    $reportTitle = 'Mail List Report';
    //$navigate->setClassData($newRoster);
    //$navigate->setTitleStandard($reportTitle,$reportTitle);
    //$navigate->show();  //??????????????????? not for export
    $this->printReportTitle($this->kcr_argReportMode,$page, 'm',$reportTitle ,$newRoster);
    $page->rpt_tableStart('');
    $this->printKidListSection($this->kcr_argReportMode,$page, 'm','Mail List',$newRoster,NULL,NULL);
    $page->rpt_tableEnd();
    exit;
}

$newRoster = new kcm_roster($this->kcr_programId,0,TRUE);
$this->loadRoster($newRoster);

if ($includeOld) {
    if ($prevProgramId == 0) {
        $oldRoster = NULL;
    }
    else {
        $oldRoster = new kcm_roster($prevProgramId,0,TRUE);
        $this->loadRoster($oldRoster, '', $newRoster->program->SchoolYear);
    }
}

//***********************
//* Menu and Page Title *
//***********************
//$navigate->setClassData($newRoster);
//$navigate->setTitleStandard($reportTitle,$reportTitle);
//$navigate->show();  //??????????????????? not for export
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
$this->printReportTitle($this->kcr_argReportMode,$page, 'd',$reportTitle,$newRoster);
$page->rpt_tableStart('');

$this->printKidListSection($this->kcr_argReportMode,$page, 'd','Drop List',$oldRoster,$oldDropped,$oldDupCount,$transferList);
$this->printKidListSection($this->kcr_argReportMode,$page, 'r','Renewals (Veterans)',$newRoster,$newDropped,NULL);
$this->printKidListSection($this->kcr_argReportMode,$page, 'n','New Kids (Rookies) and Veterans not enrolled previous semester',$newRoster,$newDropped,NULL);
$this->printKidListSection($this->kcr_argReportMode,$page, '6','Kids in Grade 6 or higher',$oldRoster,$newDropped,NULL);
$page->rpt_tableEnd();

//rc2_htmlPage_AdminBodyEnd();

}

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

function printHeading($rsMode, $page, $section, $pTitle) {
    $page->rpt_rowStart('');
    $page->rpt_cellOfText('pgrHeading',$pTitle,'colspan="99"');
    $page->rpt_rowEnd('');
    $page->rpt_rowStart('');
    if ($rsMode=='email') {
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
        if ($rsMode=='wait') {
             $page->rpt_cellofText('pgrInvoiceNum','Invoice No');
             $page->rpt_cellofText('pgrInvoiceDate','Invoice Date');
        }
    }
    $page->rpt_rowEnd('');
}

function printKid($rsMode, $page, $kid, $pRoster, $transferList=NULL) {
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
    if ($rsMode=='email') {
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
        if ($rsMode=='wait') {
             $page->rpt_cellofText('pgrInvoiceNum',$kid->per->InvoiceNo);
             $page->rpt_cellofText('pgrInvoiceDate',$kid->per->InvoiceDate);
        }
    }
    $page->rpt_rowEnd('');
}

function printKidListSection($rsMode, $page, $section, $pTitle,$pRoster,$pDropped, $dupList,$transferList=NULL) {

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
    $this->printHeading($rsMode, $page, $section, $pTitle);
    $count = 0;
    for ($i = 0; $i < $pRoster->kidCount; $i++) {
        $kid = $pRoster->kidArray[$i];
        $ok = FALSE;
        if ( ($dupList != NULL) and ($dupList[$i]>=0) ) { // jpr 2017-06-29 was 1
            $ok = FALSE;
        }
        else if ($rsMode == 'wait') {
            $ok = TRUE;
        }
        else if ($rsMode == 'email') {
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
            $this->printKid($rsMode,$page,$kid, $pRoster, $transferList);
            ++ $count;
        }
    }
    $page->rpt_rowStart('');
    $page->rpt_cellOfText('','Count = ' . $count,'colspan="99"');
    $page->rpt_rowEnd('');
}

function loadRoster($pRoster,$rsMode='',$curYear = NULL) {
    if ($rsMode == 'w') {
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
    $maxProgId = $this->getPrevProgramSemesterId($db, $pProgramId, $schoolId,  $curProgType,  $curYearSem, $dow);


    if ($maxProgId == 0) {
        $maxProgId = $this->getPrevProgramSemesterId($db, $pProgramId, $schoolId,  $curProgType,  $curYearSem, NULL);
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

} // end class


//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appGlobals = new kcmRoster_globals();
$appGlobals->gb_forceLogin ();
$appData = new appData_AdminReports;
$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );

$appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);

$appChain->chn_form_register(1,'appForm_kcm1_tallyReport');
$appChain->chn_form_launch(); // proceed to current step

exit;


?>