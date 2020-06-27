<?php

//--- roster-report-pairingLabels.php ---

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

//include_once( 'roster-system-data-games.inc.php' );
//include_once( 'roster-results-game-edit.inc.php' );
//include_once( 'roster-system-data-points.inc.php' );
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

class appForm_pairingLabels_listing extends kcmKernel_Draff_Form {  // specify winner

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $appData->apd_tallyReport = new kcm1Report_pairingLabels($appData->apd_roster_program->prog_programId,'h');
    $appData->apd_reportFilters->rf_form_processExportSubmit( $appData->apd_tallyReport, $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
    $appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);
    $appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
    $appData->apd_roster_program->rst_load_kids($appGlobals, $appData->apd_roster_program->prog_programId);
    $appData->apd_tallyReport = new kcm1Report_pairingLabels($appData->apd_roster_program->prog_programId,'h');
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-panel' );
    $appEmitter->emit_options->set_title('Set Class Period');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize();
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Pairing Labels');
    if ($appData->apd_isExport) {
        return;
    }
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_css.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_screen.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_print.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-rpt-PairingLabels.css','all','../');
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $appData->apd_reportFilters->rf_form_initControls($this);
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
v}

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

class application_data extends draff_appData {
public $apd_roster_program;
public $apd_tallyReport;
public $apd_isExport = FALSE;
public $apd_exportCode = 'h';
public $apd_reportFilters;

function apd_formData_get( $appGlobals, $appChain ) {
    $this->apd_isExport = ( ($this->apd_exportCode=='p') or ($this->apd_exportCode=='e') );
    $appGlobals->gb_isExport = $this->apd_isExport; //???????????????
    $this->apd_reportFilters = new report_filters('Roster');;
    $this->apd_reportFilters->rf_export_pdf = TRUE;
    $this->apd_reportFilters->rf_export_excel = FALSE;
    $this->apd_reportFilters->rf_period_options = FALSE;
    $this->apd_reportFilters->rf_sort_list =array('fn'=>'First Name','ln'=>'Last Name');
    $this->apd_reportFilters->rf_group_list =array('fn'=>'(none)','gr'=>'Grade');
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

} // end class

class kcm1Report_pairingLabels {

public $kcr_programId;
public $kcr_exportCode;
public $kcr_isExport;
public $kcr_page;

function __construct ($programId, $exportType) {
    $this->kcr_programId  = $programId;
    $this->kcr_exportType = $exportType;
}

function kcr_print($exportCode) {
    $this->kcr_exportCode = $exportCode;
    $this->kcr_isExport = (($exportCode=='p') or ($exportCode=='e'));

//*************
//* Read Data *
//*************
$db = new rc_database();
$roster = new kcm_roster($this->kcr_programId,NULL,NULL);
$roster->load_roster_headerAndKids();

//***********************
//* Menu and Page Title *
//***********************
$mainTitle = 'Pairing Labels<br>'.$roster->program->getNameLong($roster);
//$navigate->setClassData($roster);
//$navigate->setTitleStandard('Pairing Labels','Pairing Labels');

//************************
//* Init and Sort Roster *
//************************
$roster->sort_periodFilter(0);
$roster->sort_start(FALSE);
$roster->sort_byPeriodCurrent('c');
$roster->sort_byFirstName('c');
$roster->sort_end();

//***************
//* Final Inits *
//***************
// will need to be different for camps and other programs
$windowTitle = 'KCM: ' . $roster->school->NameShort . ' ' . $roster->schedule->dowDesc. ' Pairing Labels';

//@@@@@@@@@@@@@@
//@ Start Page @
//@@@@@@@@@@@@@@

$page = new kcm_pageEngine();
$page->setIsReportPreview();
$page->setAutoPageBreaks(FALSE);
$page->setBreakOnNewTable(TRUE);

if ($this->kcr_isExport) {
    $page->openForExport('comic');
    $page->export->domAddStyleFile('kcm1css/kcm-common_css.css');
    $page->export->domAddStyleFile('kcm1css/kcm-rpt-PairingLabels.css');
}

$pageTitle = 'Pairing Labels';

if (!$this->kcr_isExport) {
//    $navigate->show();
//    $page->systemExportForm('Pairing Labels Print Preview',$kcmState,'kcm-rpt-PairingLabels.php',FALSE);
//    $page->textBreak();
} // end !isExport

$progName = $roster->program->ProgramName;

$kid = $roster->kidArray[0];   //????? problem if none
//###$periodName = $k->PeriodName;
$curPeriodId   = -1;

//--- Start Report

$prevPeriod = "";
$borderCount = 0;
$prevPeriod = "";
$periodName = "1st";
$altCount = 1;
$gridCount = 0;
$curItem = 0;
$curRow = 99;
$curIndex = 0;
$pageNum = 0;
$kidIndex = 0;
$number = 0;

//@JPR-2018-08-11 15:20 ==========================
//@JPR-2018-08-11 15:20 start of changes

$maxPairingNumber = 0;
for ($i=0;  $i < $roster->kidCount; ++$i) {
    $kidProg = $roster->kidArray[$i]->prg;
    //deb($i,$kidProg->PairingLabelNumber);
    $maxPairingNumber = max($maxPairingNumber,$kidProg->PairingLabelNumber);
}
for ($i=0;  $i < $roster->kidCount; ++$i) {
    $kidProg = $roster->kidArray[$i]->prg;
    //deb($i,$kidProg->PairingLabelNumber);
    if ($kidProg->PairingLabelNumber < 1) {
        ++$maxPairingNumber;
        $query = "UPDATE `ro:kid_program` SET `rKPr:KcmPairingLabelId`='{$maxPairingNumber}' WHERE `rKPr:KidProgramId` = '{$kidProg->KidProgramId}'";
        $result = $db->rc_query( $query );
        if ($result === FALSE) {
            kcm_db_CriticalError( __FILE__,__LINE__);
        }
        $kidProg->PairingLabelNumber = $maxPairingNumber;
    }
}


//@JPR-2018-08-11 15:20 end of changes
//@JPR-2018-08-11 15:20 ==========================

while($kidIndex<=$roster->kidCount) {
    $page->rpt_tableStart('plbTable');
    ++$pageNum;
    $page->rpt_rowStart();
    $sem = $roster->schedule->meetDateDesc;
    $prg = $roster->program->getNameLong($roster);
    $page->rpt_cellOfText('plbTopCell','Pairing Labels - '.$prg);
    $page->rpt_cellOfText('plbTopCell',$sem);
    $page->rpt_rowEnd();
    for ($row=1; $row<=15; $row++) {
        $page->rpt_rowStart();
        for ($col=1; $col<=2; $col++) {
            if ($kidIndex<$roster->kidCount) {
                $kid = $roster->kidArray[$kidIndex];
                $pickupImage =NULL;
                $perDesc = $kid->getPeriodDesc(kcmPERIODFORMAT_ONE);
                if ($roster->program->ProgramType==1)
                    $teacher = '';
                else
                    $teacher = $kid->prg->TeamName;
                $number = $kid->prg->PairingLabelNumber;  //@JPR-2018-08-11 15:42
                // ++$number;  //@JPR-2018-08-11 15:42
                $items = array (
                    'div.plbFullName',$kid->prg->FirstName . ' ' .$kid->prg->LastName,
                    'div.plbGrade',$kid->prg->GradeDesc,
                    'div.plbTeam',$teacher,
                    'div.plbPeriod',$perDesc,
                    'div.plbNumber',$number
                );
               $page->rpt_cellOfItems('plbLabelDiv','plbLabelCell', $items);
           }
           else
               $page->rpt_cellOfText('plbLabelCell', '');
           $kidIndex = $kidIndex +1;
        } // end col
        $page->rpt_rowEnd();
    } // end row
    $page->rpt_tableEnd();
    $page->rpt_screenPageBreak();
} // end kidIndex

if (!$this->kcr_isExport)
    $page->webPageBodyEnd();
//    $page->export->exportClose($argSubmit,$classxxxData->program->programName.'-PointTally');

if ($this->kcr_isExport) {
    $file = $roster->program->getExportName($roster).'-PairingLabels';
    $page->export->exportClose($this->kcr_exportCode,$file,'comic');
}

}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appChain = new Draff_Chain( 'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmRoster_globals());
$appChain->chn_register_appData( new application_data());
$appGlobals->gb_forceLogin ();

$appChain->chn_form_register(1,'appForm_pairingLabels_listing');
$appChain->chn_form_launch(); // proceed to current step

exit;


?>