<?php

//--- roster-report-signOutSheet.php ---

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
//include_once( '../draff/draff-appEmitter-dom-engine.inc.php' );

include_once( '../kcm-kernel/kernel-emitter.inc.php');
include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'roster-system-functions.inc.php' );
include_once( '../kcm-kernel/kernel-objects.inc.php');
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

class appForm_signoutSheet_listing extends Draff_Form {  // specify winner

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $appData->apd_reportFilters->rf_form_processExportSubmit( $appData, $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_roster_program = new appData_signoutSheet($appGlobals);
    $appData->apd_roster_program->rst_load_kids($appGlobals, $appData->apd_roster_program->prog_programId);
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-panel' );
    $appEmitter->set_title('Set Class Period');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Sign Out Sheet');
    if ($appData->apd_isExport) {
        return;
    }
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
    $appEmitter->set_menu_customize( $appChain, $appGlobals );
    $appEmitter->addOption_styleFile('kcm-roster/kcm1css/kcm-common_css.css','all','../');
    $appEmitter->addOption_styleFile('kcm-roster/kcm1css/kcm-common_screen.css','all','../');
    $appEmitter->addOption_styleFile('kcm-roster/kcm1css/kcm-common_print.css','all','../');
    $appEmitter->addOption_styleFile('kcm-roster/kcm1css/kcm-rpt-Sign-Out-Sheet.css','all','../');
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

class appData_signoutSheet extends draff_appData {
public $apd_roster_program;
public $apd_tallyReport;
public $apd_isExport = FALSE;
public $apd_exportCode = 'h';
public $apd_reportFilters;

function apd_formData_get( $appGlobals, $appChain ) {
    $this->apd_isExport = ( ($this->apd_exportCode=='p') or ($this->apd_exportCode=='e') );
    $appGlobals->gb_isExport = $this->apd_isExport; //???????????????
    $this->apd_tallyReport = new kcm1Report_signoutSheet($this->apd_roster_program->rst_program,'h');
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

class kcm1Report_signoutSheet {

public $kcr_programObject;
public $kcr_exportCode;
public $kcr_isExport;
public $kcr_page;
public $kcr_kidCount;

function __construct ($programObject, $exportType) {
    $this->kcr_programObject  = $programObject;
    $this->kcr_exportType = $exportType;
}

function kcr_print($exportCode) {
    $this->kcr_exportCode = $exportCode;
    $this->kcr_isExport = (($exportCode=='p') or ($exportCode=='e'));

//*************
//* Read Data *
//*************
$db = new rc_database();

$roster = new kcm_roster($this->kcr_programObject->prog_programId);
$roster->load_roster_headerAndKids();

$mainTitle = 'Print Sign-Out Sheet<br>'.$roster->program->getNameLong($roster);

$roster->sort_periodFilter(0);
$roster->sort_start();
$roster->sort_byPeriodCurrent('c');
$roster->sort_byPickup(FALSE);
$roster->sort_byFirstName(TRUE);
$roster->sort_end();

//***************
//* Final Inits *
//***************
// will need to be different for camps and other programs
$windowTitle = 'KCM: ' . $roster->school->NameShort . ' ' . $roster->schedule->dowDesc. ' Sign-Out Sheet';

//@@@@@@@@@@@@@@
//@ Start Page @
//@@@@@@@@@@@@@@

$this->kcr_page = new kcm_pageEngine();
$this->kcr_page->setIsReportPreview();
$this->kcr_page->setBreakOnNewTable(TRUE);

$this->kcr_pageTitle = 'Sign-Out Sheet';

if ($this->kcr_isExport) {
    $this->kcr_page->openForExport();
    $this->kcr_page->export->domAddStyleFile('kcm1css/kcm-common_css.css');
    $this->kcr_page->export->domAddStyleFile('kcm1css/kcm-rpt-Sign-Out-Sheet.css');
    $this->kcr_page->export->domSetAutoPage(TRUE);
    $this->kcr_page->export->domSetBreakOnNewTable(FALSE);}

if (!$this->kcr_isExport) {
//    $navigate->show();
//    $this->kcr_page->screenOnlyStart();
//    $this->kcr_page->systemExportForm('Sign-Out Sheet Print Preview',$kcmState,$thisPage);
//    $this->kcr_page->textBreak();
//    $this->kcr_page->screenOnlyEnd();
//
//    $this->kcr_page->frmStart('get','options',$thisPage,'kcGuiOptionsForm');
} // end !isExport

//--- start printing report

    //@@@@@@@@@@@@@@@@@@
    //@ Show Kid Table @
    //@@@@@@@@@@@@@@@@@@
    //$this->kcr_page->frmStart('get','kidttbl',$thisPage,'kcGuiDataForm');
$curPeriodId = NULL;
$this->kcr_page->rowSetClasses(array('kgridEven','kgridOdd'));
$count = count($roster->kidCount);
$this->kcr_pageCount = 0;
$this->kcr_kidCount = 0;

$count = 93;  //112  93 works 94 has error

$kidArray = array();
for ($i = 0; $i<$roster->kidCount; $i++) {
    $kid = $roster->kidArray[$i];  // program - no period info
    $kidArray[] = new KidLine($kid);
}
$this->kcr_kidCount = count($kidArray);

$lineCount = 0;
$this->kcr_page->setBreakOnNewTable(TRUE);
for ($i = 0; $i<$roster->kidCount; $i++) {
    $rosterKid = $roster->kidArray[$i];  // program - no period info
    $kid = $kidArray[$i];
    ++$this->kcr_pageCount;
    ++$lineCount;
    //????? not count - should be near page bottom margin
    if ($curPeriodId!=$kid->periodId or $lineCount>40) {
        $lineCount = 1;
        if ($curPeriodId!=$kid->periodId) {
            //$lineCount = 0;
            if ($curPeriodId != NULL) {
                //$this->kcr_page->rpt_tableEnd();
                $this->tableFooter();
                $this->kcr_page->rpt_screenPageBreak(TRUE);
            }
            $this->kcr_kidCount = 0;
        }
        else if ($this->kcr_pageCount>43) {
            if ($curPeriodId != NULL) {
                //$this->kcr_page->rpt_tableEnd();
                $this->kcr_page->rpt_screenPageBreak(TRUE);
            }
        }
        $curPeriodId =$kid->periodId;
        $curPeriod = $roster->getPeriodFromPeriodId($curPeriodId);
        $this->kcr_pageCount = 0;
        $this->kcr_page->headingStart('kpageHeadingPortrait');  //??? is kpage style needed here
        $this->kcr_page->headingText('Sign-Out Sheet');
        $this->kcr_page->headingPeriod($curPeriod);
        $this->kcr_page->headingNextColumn();
        $this->kcr_page->headingProgram($roster);
        $this->kcr_page->headingSemester($roster);
        $this->kcr_page->headingEnd();

        //********* Kid Table - Header row **********
        $this->kcr_page->rpt_tableStart('kgridTable');
        $this->kcr_page->rpt_rowStart();
        $this->kcr_page->rpt_cellOfText('sosFirst sosHead','First Name');
        $this->kcr_page->rpt_cellOfText('sosLast sosHead','Last Name');
        $this->kcr_page->rpt_cellOfText('sosASP sosHead','A/P');
        $this->kcr_page->rpt_cellOfText('sosSig sosHead','Signature');
        $this->kcr_page->rpt_cellOfText('sosNotes sosHead','Pickup Notes');
        $this->kcr_page->rpt_rowEnd();

    }

    //===============================
    //** Row for each kid - Kid Table
    ++$this->kcr_kidCount;
    //if ($i>40)
        //printRow($kid, $rosterKid);
    $this->kcr_page->rpt_rowStart(); // is a data row
    $this->kcr_page->rpt_cellofText('sosFirst',$kid->firstName);
    $this->kcr_page->rpt_cellofText('sosLast',$kid->lastName);
    $this->kcr_page->rpt_cellofText('sosASP',$kid->pickupCodeDesc);
    $this->kcr_page->rpt_cellofText('sosSig','');
    $this->kcr_page->rpt_cellofText('sosNotes',$kid->pickupNotes);
  }

$this->tableFooter();
//$this->kcr_page->rpt_tableEnd();

if (!$this->kcr_isExport) {
    $this->kcr_page->frmAddHidden($kcmState->Id, $kcmState->ksConvertToString());
    $this->kcr_page->frmEnd();
    $this->kcr_page->ScreenOnlyEnd();
    $this->kcr_page->webPageBodyEnd();
}

if ($this->kcr_isExport) {
    $file = $roster->program->getExportName($roster).'SignOutSheet';
    $this->kcr_page->export->exportClose($this->kcr_exportCode,$file);
//    $this->kcr_page->export->domSetAutoPage(TRUE);
//    $this->kcr_page->export->domSetBreakOnNewTable(TRUE);
}

}

function tableFooter() {
    $this->kcr_page->rpt_tableEnd();
}

} // end class


class kidLine {
public $periods;
public $firstName;
public $lastName;
public $pickupCodeDesc;
public $pickupNotes;
public $gradeDesc;
public $periodId;
public $periodDesc;
public $thisPeriodBits;
public $lastPeriodBits;

function __construct($kid) {
    $this->firstName = $kid->prg->FirstName;
    $this->lastName = $kid->prg->LastName;
    if ($kid->per->ParentHelperStatus>=1)  {
        $this->firstName .= ' (PH)';
    }
   $this->gradeDesc = $kid->prg->GradeDesc;
    switch ($kid->prg->PickupCode) {
        case 0:  $this->pickupCodeDesc = ''; break;
        case 1:  $this->pickupCodeDesc = 'A'; break;
        case 2:  $this->pickupCodeDesc = 'P'; break;
        case 3:  $this->pickupCodeDesc = 'W'; break;
        case 90: $this->pickupCodeDesc = ''; break;
        case 91: $this->pickupCodeDesc = 'V'; break;
        default: $this->pickupCodeDesc = '??'; break;
    }
    $this->pickupNotes = $kid->prg->PickupNotes;
    $this->thisPeriodBits = $kid->per->PeriodBitsSinglePeriod;
    $s = ($kid->prg->PeriodComboAllBits & 255);
    if ($s >= 8)
        $this->lastPeriodBits = 8;
    else if ($s >= 4)
        $this->lastPeriodBits = 4;
    else if ($s >= 2)
        $this->lastPeriodBits = 2;
    else if ($s >= 1)
        $this->lastPeriodBits = 1;
    $this->periodDesc = $kid->getPeriodDesc(kcmPERIODFORMAT_TALLY);
    $this->periodId = $kid->per->AtPeriodId;
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appGlobals = new kcmRoster_globals();
$appGlobals->gb_forceLogin ();
$appData = new appData_signoutSheet;
$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );

$appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);

$appChain->chn_form_register(1,'appForm_signoutSheet_listing');
$appChain->chn_form_launch(); // proceed to current step

exit;


?>