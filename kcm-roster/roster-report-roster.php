<?php

//--- roster-report-roster.php ---

ob_start();  // output buffering (needed for redirects, draff-appHeader changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );

//include_once( 'roster-system-data-tally.inc.php' );

include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-database.inc.php');
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );
include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-menu.inc.php' );
include_once( '../draff/draff-page.inc.php' );

include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'roster-system-functions.inc.php' );
include_once( 'roster-system-globals.inc.php' );
include_once( 'roster-system-data-roster.inc.php');

//include_once( 'kcm1/kcm1-rpt-Roster.inc.php');
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

class appForm_rosterSheet_listing extends kcmKernel_Draff_Form {  // specify winner

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $appData->apd_reportFilters->rf_form_processExportSubmit( $appData->apd_tallyReport, $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Setup Point Categories');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program );
    $appGlobals->gb_menu->drMenu_customize();
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Roster');
    if ($appData->apd_isExport) {
        return;
    }
     $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_css.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_screen.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_print.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-rpt-Roster.css','all','../');
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $appData->apd_reportFilters->rf_form_initControls($this);
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
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

class appData_rosterSheet extends draff_appData {
public $apd_roster_program;
public $apd_tallyReport;
public $apd_isExport = FALSE;
public $apd_exportCode = 'h';
public $apd_reportFilters;

function apd_formData_get( $appGlobals, $appChain ) {
    $this->apd_isExport = ( ($this->apd_exportCode=='p') or ($this->apd_exportCode=='e') );
    $appGlobals->gb_isExport = $this->apd_isExport; //???????????????
    $this->apd_tallyReport = new kcm1Report_rosterSheet($this->apd_roster_program->prog_programId,'h');
    //??? prog_programId
    $this->apd_reportFilters = new report_filters('Roster');;
    $this->apd_reportFilters->rf_export_pdf = TRUE;
    $this->apd_reportFilters->rf_export_excel = TRUE;
    $this->apd_reportFilters->rf_period_options = FALSE;
    $this->apd_reportFilters->rf_sort_list =array('fn'=>'First Name','ln'=>'Last Name');
    $this->apd_reportFilters->rf_group_list =array('fn'=>'(none)','gr'=>'Grade');
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

} // end class

class kcm1Report_rosterSheet {
public $kcr_programObject;
public $kcr_exportCode;
public $kcr_isExport;
public $kcr_page;
public $kcr_argFormat;
public $kcr_topInfoFormat;
public $kcr_topInfoText;
public $kcr_topNotesText;
public $kcr_topNotesDesc;
public $kcr_topNotesFormat;
public $kcr_programId;

function __construct ($programId, $exportType) {
    $this->kcr_exportType = $exportType;
    $this->kcr_programId = $programId;
}

function kcr_print($exportCode) {
    $this->kcr_exportCode = $exportCode;
    $this->kcr_isExport = (($exportCode=='p') or ($exportCode=='e'));

//*************
//* Read Data *
//*************
//$db = new rc_database();
//kcm_authorizeProgramId($kcmState->ksProgramId, $db);
$roster = new kcm_roster($this->kcr_programId,0,TRUE);
$roster->load_roster_headerAndKids();

$roster->sort_periodFilter(NULL,TRUE);
$roster->sort_start(FALSE);
$roster->sort_byPeriodGroup('c');
$roster->sort_byFirstName('c');
$roster->sort_end();

//***************
//* Final Inits *
//***************
// will need to be different for camps and other programs
$windowTitle = 'KCM: ' . $roster->school->NameShort . ' ' . $roster->schedule->dowDesc. ' Roster';


//@@@@@@@@@@@@@@
//@ Start Page @
//@@@@@@@@@@@@@@

$this->kcr_page = new kcm_pageEngine();
$this->kcr_page->setIsReportPreview();


if ($this->kcr_isExport) {
    $this->kcr_page->openForExport(rpPAGE_LANDSCAPE);
    $this->kcr_page->export->domAddStyleFile('kcm1css/kcm-common_css.css');
    $this->kcr_page->export->domAddStyleFile('kcm1css/kcm-rpt-Roster.css');
    $this->kcr_page->export->domSetAutoPage(TRUE);
    $this->kcr_page->export->domSetBreakOnNewTable(FALSE);
}

if (!$this->kcr_isExport) {
//    $navigate->show();
//
//    $this->kcr_page->screenOnlyStart();
//    $this->kcr_page->systemExportForm('Roster Print Preview',$kcmState,'kcm-rpt-Roster.php');
//    $this->kcr_page->textBreak();
} // end !isExport

//===========================================================
// Roster

$this->kcr_page->headingStart('kpageHeadingLandscape'); //??? is kpage style needed here
$this->kcr_page->headingText('Roster');
$this->kcr_page->headingNextColumn();
$this->kcr_page->headingProgram($roster);
$this->kcr_page->headingSemester($roster);
$this->kcr_page->headingEnd();


//--- Top Info (box on left hand side of report)

if ($roster->program->ProgramType==1) {
    $dowDesc = rc_getDayOfWeekNameFromNumber($roster->program->DayOfWeek);
}
else {
    $date = date_create_from_format( 'Y-m-d', $roster->program->DateClassFirst );
	$dow1 = date_format( $date, 'D' );
    $date = date_create_from_format( 'Y-m-d', $roster->program->DateClassLast );
	$dow2 = date_format( $date, 'D' );
    if ($dow1==$dow2)
        $dowDesc = $dow1;
    else
        $dowDesc = $dow1 . ' - ' . $dow2;
}

$this->kcr_topInfoText = array();
$this->kcr_topInfoFormat = array();
$this->addTopInfo('Top',$dowDesc);
$this->addTopInfo('Middle','Start Day: ' . substr($roster->program->DateClassFirst,5));
$this->addTopInfo('Bottom','Trophy Day: ' . substr($roster->program->DateClassLast,5));
for ($i = 0; $i<$roster->periodCount ; $i++) {
    $period = $roster->periodArray[$i];
    if ($period->PeriodSequenceBits < 4096) {
        $periods[] = $period;
    }
}
$periodCount = count($periods);

for ($i = 0; $i<$periodCount ; $i++) {
    $period = $roster->periodArray[$i];
    $this->addTopInfo('Top',$period->PeriodName);
    $this->addTopInfo('Middle','Time: '.$this->TimeToString($period->TimeStart).'-'.$this->TimeToString($period->TimeEnd));
    $this->addTopInfo('Bottom','Enrolled: ' . $period->kidThisPeriodCount);
    for ($j = 0; $j<$roster->unfilteredKidCount; $j++) {
        $kid = $roster->unfilteredKidArray[$j];
        if ( ($kid->per->ParentHelperStatus>=1) and ($kid->per->AtPeriodId == $period->PeriodId) ) {
           $this->setPrevTopInfoFormat('Middle');
           $phName = $kid->per->ParentHelperName;
           if ($phName == '') {
               $phName = $kid->prg->parent1->FirstName . ' ' . $kid->prg->parent1->LastName;
           }
           $this->addTopInfo('Bottom','PH: ' . $phName);
        }
    }
}
$this->addTopInfo('Top','Available');
for ($i = 0; $i<$periodCount; $i++) {
    $period = $roster->periodArray[$i];
    if ($period->EnrollmentLimit==0) {
        $j = 'No limit';
    }
    else {
        $j = $period->EnrollmentLimit - $period->kidThisPeriodCount;
    }
    $s = $period->PeriodName . ': ' . $j;
    if ($i+1==$periodCount) {
        $format ='Bottom';
    }
    else {
        $format ='Middle';
    }
    $this->addTopInfo($format,$s);
}
$lunchCount = 0;
for ($j = 0; $j<$roster->unfilteredKidCount; $j++) {
    $kid = $roster->unfilteredKidArray[$j];
    if ( ($kid->prg->Lunch)  and ($kid->per->PeriodBitsSinglePeriod == 1) ) {
        ++$lunchCount;
    }
}
if ($lunchCount >= 1) {
    $this->addTopInfo('Bottom','Lunches: ' . $lunchCount);
}

//=============================================

$this->kcr_topNotesText = array();
$this->kcr_topNotesDesc = array();
$this->kcr_topNotesFormat = array();
//$this->kcr_topNotesText[] = 'Notes:';
//$this->kcr_topNotesFormat[] = '';
$ad = $roster->school->Address . ', ' . $roster->school->City
     . ', ' . $roster->school->State  . ', ' . $roster->school->Zip;
$this->addTopNote('Address',$ad);
$this->addTopNote('Phone',$roster->school->SchoolPhone);
$this->addTopNote('Contacts',$roster->school->NotesContacts);
$this->addTopNote('School Notes',$roster->school->NotesOther);
$this->addTopNote('Room',$roster->program->NotesRoomInfo);
$this->addTopNote('Equipment',$roster->school->NotesEquipment);
$this->addTopNote("Upon Arriving",$roster->program->NotesUponArriving);
$this->addTopNote("Before Leaving",$roster->program->NotesBeforeLeaving);
$this->addTopNote("ASP Notes",$roster->program->NotesASPInstructions);
$this->addTopNote("Parent Pickup",$roster->program->NotesParentPickup);
$this->addTopNote("Coach Notes",$roster->program->NotesForCoach);
$this->addTopNote("Site Leader",$roster->program->NotesForSiteLeader);

// coach notes
$this->addTopNote('','');
$desc = 'Kid Notes';
for ($i = 0; $i<$roster->kidCount; $i++) {
    $kid = $roster->kidArray[$i];
    if ( $kid->prg->NotesForCoach!='') {
        $name = $kid->prg->FirstName . ' ' . $kid->prg->LastName;
        $this->addTopNote($desc,$name.': '.$kid->prg->NotesForCoach);
        $desc = '';
    }
}
// ASP notes
for ($i = 0; $i<$roster->kidCount; $i++) {
    $kid = $roster->kidArray[$i];
    if ( $kid->prg->PickupNotes!='') {
        $s = $kid->prg->FirstName . ' ' . $kid->prg->LastName . ': ' . $kid->prg->PickupNotes;
        $this->addTopNote('ASP Note',$s);
    }
}
//    $this->kcr_page->rpt_cellofText('rstPickupNote',$kid->prg->PickupNotes);

$topNotesCount = count($this->kcr_topNotesText);
$topInfoCount = count($this->kcr_topInfoText);

$maxCount = max($topInfoCount,$topNotesCount);
$this->kcr_page->rpt_tableStart('rstTopTable');
for ($i = 0; $i< $maxCount; $i++) {
    $this->kcr_page->rpt_rowStart('');
    if ($i < $topInfoCount) {
        $this->kcr_page->rpt_cellofText($this->kcr_topInfoFormat[$i],''.$this->kcr_topInfoText[$i]);
    }
    else {
        $this->kcr_page->rpt_cellofText('rstInfo','');
    }
    if ($i < $topNotesCount) {
        $this->kcr_page->rpt_cellofText('rstTopDesc',$this->kcr_topNotesDesc[$i]);
        $this->kcr_page->rpt_cellofText('rstTopNotes',$this->kcr_topNotesText[$i]);
    }
    else {
        $this->kcr_page->rpt_cellofText('rstTopDesc','');
        $this->kcr_page->rpt_cellofText('rstTopNotes','');
    }
    $this->kcr_page->rpt_rowEnd();
}

$this->kcr_page->rpt_rowStart(' ');
$this->kcr_page->rpt_cellofText('rstEndRow','');
$this->kcr_page->rpt_cellofText('rstEndRow','');
$this->kcr_page->rpt_rowEnd(' ');

$this->kcr_page->rpt_tableEnd();

$this->kcr_page->rpt_tableStart('rstTable');

//---- Main  section

//    $this->kcr_page->rpt_rowStart('h');   // default for borders is now table default
    $this->kcr_page->rpt_rowStart('');   // default for borders is now table default
    $this->kcr_page->rpt_cellofText('rstFirstName knogridHead',"First");
    $this->kcr_page->rpt_cellofText('rstLastName knogridHead',"Last Name");
    $this->kcr_page->rpt_cellofText('rstGrade knogridHead',"Grade");
    $this->kcr_page->rpt_cellofText('rstPeriod knogridHead',"Period");
    $this->kcr_page->rpt_cellofText('rstRookie knogridHead',"Status");
    $this->kcr_page->rpt_cellofText('rstHome knogridHead',"Home");
    $this->kcr_page->rpt_cellofText('rstCell knogridHead',"Cell");
    $this->kcr_page->rpt_cellofText('rstBusiness knogridHead',"Business");
    $this->kcr_page->rpt_cellofText('rstEmergency knogridHead',"Emergency");
    $this->kcr_page->rpt_cellofText('rstTeacher knogridHead',"Teacher");
    $this->kcr_page->rpt_cellofText('rstAsp knogridHead',"ASP");
    $this->kcr_page->rpt_rowEnd();

$prevPeriod = "";
$borderCount = 0;
$prevPeriod = "";
$periodName = "1st";
$altCount = 1;
$gridCount = 0;
$twice = FALSE;
$schoolName = $roster->school->NameFull;
$prevPeriodName = '';
for ($i = 0; $i<$roster->kidCount; $i++) {
    $kid = $roster->kidArray[$i];  // program - no period info
    $periodName = '?????';  //### $k->PeriodName;
    //---- Print Row of Data
    $firstName = $kid->prg->FirstName;
    $lastName = $kid->prg->LastName;
    $periodName = $kid->getPeriodDesc(kcmPERIODFORMAT_SHORT);
    if ($prevPeriodName!='' and $periodName!=$prevPeriodName) {
        $this->blankLine();
    }
    $prevPeriodName= $periodName;
    if ($kid->prg->Lunch) {
        $periodName .= ' -L';
    }

    if ( $kid->prg->parent2!=NULL) {
        $p1= $kid->prg->parent2->HomePhone;
        $p2= $kid->prg->parent2->CellPhone;
        $p3= $kid->prg->parent2->WorkPhone;
        if ( $p1 == $kid->prg->parent1->HomePhone )
            $p1 = '';
        if ( $p1 == $kid->prg->parent1->CellPhone )
            $p1 = '';
        if ($p1!='')
           $p1 = '<br>' . $p1;
        if ($p2!='')
           $p2 = '<br>' . $p2;
        if ($p3!='')
           $p3 = '<br>' . $p3;
    }
    else {
        $p1= '';
        $p2= '';
        $p3= '';
    }
    //-- print line
    $this->kcr_page->rpt_rowStart();
    $this->kcr_page->rpt_cellofText('rstFirstName',$kid->prg->FirstName);
    $this->kcr_page->rpt_cellofText('rstLastName',$kid->prg->LastName);
    $this->kcr_page->rpt_cellofText('rstGrade',$kid->prg->GradeDesc);
    $this->kcr_page->rpt_cellofText('rstPeriod',$periodName);
    $this->kcr_page->rpt_cellofText('rstRookie',kcmAsString_Rookie($kid,$roster));
    $this->kcr_page->rpt_cellofText('rstHome',$kid->prg->parent1->HomePhone.$p1);
    $this->kcr_page->rpt_cellofText('rstCell',$kid->prg->parent1->CellPhone.$p2);
    $this->kcr_page->rpt_cellofText('rstBusiness',$kid->prg->parent1->WorkPhone.$p3);
    $this->kcr_page->rpt_cellofText('rstEmergency',$kid->prg->family->EmergencyPhone);
    if ($roster->program->ProgramType==1)
       $s = $kid->prg->Teacher;
    else
       $s = $kid->prg->TeamName;
    $this->kcr_page->rpt_cellofText('rstTeacher',$s);
    $this->kcr_page->rpt_cellofText('rstAsp',$kid->prg->PickupDesc);
    $this->kcr_page->rpt_rowEnd();
}
$this->kcr_page->rpt_tableEnd();
$this->kcr_page->webPageBodyEnd();

if ($this->kcr_isExport) {
    $file = $roster->program->getExportName($roster).'-Roster';
    $this->kcr_page->export->exportClose($this->kcr_exportCode,$file);
}

// exit;

}

//=================================================
//==   Main code ends here
//==   Functions Start Here
//=================================================

function addTopNote($pDesc, $pNote) {
    $pNote = trim($pNote);
    $n = preg_split ('/$\R?^/m', $pNote);
    if ($pDesc != '') {
       $pDesc = $pDesc . ':';
    }
    $this->kcr_topNotesText[] = $n[0];
    $this->kcr_topNotesDesc[] = $pDesc;
    $this->kcr_topNotesFormat[] = '';
    for ($i = 1; $i<count($n); $i++) {
        if ($n[$i] != '') {
            $this->kcr_topNotesText[] = '....'.$n[$i];
            $this->kcr_topNotesDesc[] = '';
            $this->kcr_topNotesFormat[] = '';
        }
    }
}

function addTopInfo($pClass, $pText) {
     //~~?????? should use css, not $pStyle
    $this->kcr_topInfoText[] = $pText;
    $this->kcr_topInfoFormat[] = 'rstInfo rstInfo'. $pClass;
}
function setPrevTopInfoFormat($pFormat) {
    $this->kcr_topInfoFormat[count($this->kcr_topInfoFormat)-1] = 'rstInfo rstInfo'. $pFormat;
}

function blankLine() {
    $this->kcr_page->rpt_rowStart('d');   // default for borders is now table default
    $this->kcr_page->rpt_cellofText('rstFirstName','',"");
    $this->kcr_page->rpt_cellofText('rstLastName','',"");
    $this->kcr_page->rpt_cellofText('rstGrade','');
    $this->kcr_page->rpt_cellofText('rstPeriod','');
    $this->kcr_page->rpt_cellofText('rstRookie','');
    $this->kcr_page->rpt_cellofText('rstHome','');
    $this->kcr_page->rpt_cellofText('rstCell','');
    $this->kcr_page->rpt_cellofText('rstBusiness','');
    $this->kcr_page->rpt_cellofText('rstEmergency','');
    $this->kcr_page->rpt_cellofText('rstTeacher','');
    $this->kcr_page->rpt_cellofText('rstAsp','');
    if ($this->kcr_argFormat==='c') {
        $this->kcr_page->rpt_cellofText('rstPickupNote','');
    }
    if ($this->kcr_argFormat==='s') {
        $this->kcr_page->rpt_cellofText('rstPickupNote','');
    }
    $this->kcr_page->rpt_rowEnd();
}

function TimeToString($pTime) {
 $timestamp = strtotime( $pTime );
 return date( 'g:i', $timestamp );
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appChain = new Draff_Chain( 'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmRoster_globals());
$appChain->chn_register_appData( $appData = new application_data());
$appGlobals->gb_forceLogin ();

$appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);

$appChain->chn_form_register(1,'appForm_rosterSheet_listing');
$appChain->chn_form_launch(); // proceed to current step

exit;


?>