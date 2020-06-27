<?php

//--- roster-report-tallySheet.php ---

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
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( 'roster-system-globals.inc.php' );
include_once( 'roster-system-data-roster.inc.php');

//include_once( 'roster-system-data-games.inc.php' );
//include_once( 'roster-results-game-edit.inc.php' );
include_once( 'roster-system-data-points.inc.php' );
//include_once( 'roster-results-points-edit.inc.php' );


// include_once( 'kcm1/kcm1-rpt-PointTally.inc.php');
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

class appForm_tallySheet_listing extends kcmKernel_Draff_Form {  // specify winner

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $appData->reportFilters->rf_form_processExportSubmit( $appData->apd_tallyReport, $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Setup Point Categories');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program );
    $appGlobals->gb_menu->drMenu_customize( );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Results Tally');
    if ($appData->apd_isExport) {
        return;
    }
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize( );
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_css.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_screen.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_print.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-rpt-PointTally.css','all','../');
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $appData->reportFilters->rf_form_initControls($this);
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    if ($appData->apd_isExport) {
        return;
    }
    $appEmitter->zone_start('draff-zone-filters-default');
    $appData->reportFilters->rf_form_outputHeader($appEmitter);
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
public $reportFilters;

function apd_formData_get( $appGlobals, $appChain ) {
    $this->apd_isExport = ( ($this->apd_exportCode=='p') or ($this->apd_exportCode=='e') );
    $appGlobals->gb_isExport = $this->apd_isExport; //???????????????
    $this->apd_tallyReport = new kcm1Report_tallySheet($this->apd_roster_program->prog_programId,'h');
    $this->reportFilters = new report_filters('PointTally');;
    $this->reportFilters->rf_export_pdf = TRUE;
    $this->reportFilters->rf_export_excel = TRUE;
    $this->reportFilters->rf_period_options = FALSE;
    $this->reportFilters->rf_sort_list =array('fn'=>'First Name','ln'=>'Last Name');
    $this->reportFilters->rf_group_list =array('fn'=>'(none)','gr'=>'Grade');
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

} // end class

class kcm1Report_tallySheet {
public $kcr_programId;
public $kcr_exportCode;
public $kcr_sortCode;
public $kcr_isExport;
public $kcr_page;
public $kcr_kidCount;
public $kcr_roster;

function __construct ($programId, $exportType) {
    $this->kcr_programId  = $programId;
    $this->kcr_exportType = $exportType;
}

function kcr_addStyleSheets() {
    $navigate->addStyleSheet('css/rc_common.css');
    $navigate->addStyleSheet('css/rc_admin.css');
    $navigate->addStyleSheet('css/kcm-common_css.css');
    $navigate->addStyleSheet('css/kcm-common_screen.css','screen');
    $navigate->addStyleSheet('css/kcm-common_print.css','print');
    $navigate->addStyleSheet('css/kcm/kcm-rpt-PointTally.css','');
}

function kcr_print($exportCode) {
    $this->kcr_exportCode = $exportCode;
    $this->kcr_isExport = (($exportCode=='p') or ($exportCode=='e'));

    //***************
    //* Set Columns *
    //***************
    $kcmState = NULL;
    $thisPage = ''; // url
    $columnDef = new kcm_ColumnDef('OPopt',30,$kcmState,$thisPage); //,array(srtChess,srtBug,srtBlitz)
    //                                       option enabl  sort   direc freeze
    $columnDef->initColumn('colPERIOD'  , 1, FALSE, TRUE,  TRUE,  TRUE);
    $columnDef->initColumn('colFINAME'  , 2, FALSE, TRUE,  TRUE,  TRUE);  // 1 is always default sort column
    $columnDef->initColumn('colLANAME'  , 3, FALSE, TRUE,  TRUE,  TRUE);
    $columnDef->initColumn('colGRADE'   , 4, FALSE, TRUE,  TRUE,  TRUE, TRUE);
    $columnDef->initColumn('colROOKIE'  , 7, TRUE,  TRUE,  TRUE,  TRUE, TRUE);
    $columnDef->initFinalize(colFINAME);

    //*************
    //* Read Data *
    //*************
    $db = new rc_database();
    //kcm_authorizeProgramId($kcmState->ksProgramId->, $db);

    $this->kcr_roster = new kcm_roster($this->kcr_programId);
    $this->kcr_roster->load_roster_headerAndKids();

    $mainTitle = 'Print Point Tally<br>'.$this->kcr_roster->program->getNameLong($this->kcr_roster);

    //***********************
    //* Menu and Page Title *
    //***********************
    //$navigate->setClassData($this->kcr_roster);
    //$navigate->setTitleStandard('Print Point Tally',$mainTitle);

    //************************
    //* Init and Sort Roster *
    //************************
    //$this->kcr_roster = new rc_classRoster();
    //$this->kcr_roster->sortDefineStart($claxxxssData,$argPeriodId);
    //$kcmState->ksSetArg('Mode','per');

    $this->kcr_roster->sort_periodFilter(0);
    $this->kcr_roster->sort_start();
    $this->kcr_roster->sort_byPeriodCurrent('c');
    //$this->kcr_roster->sort_byFirstName('c');
    //$this->kcr_roster->sort_end();

    //if ($columnDef->isFrozen(colGRADEGRP) or $columnDef->getCurSortColumn() == colGRADEGRP) {
    //     $this->kcr_roster->sort_byGradeGroup($columnDef->getSortDirec(colGRADEGRP));
    //}
    if ($columnDef->isFrozen(colGRADE))
           $this->kcr_roster->sort_byGrade($columnDef->getSortDirec(colGRADE));
    switch ($columnDef->getCurSortColumn()) {
        case colGRADE:  // sorted above as could be frozen
           if (!$columnDef->isFrozen(colGRADE))
               $this->kcr_roster->sort_byGrade($columnDef->getSortDirec(colGRADE));
           break;
        case colPERIOD:
            $this->kcr_roster->sort_byPeriodName($columnDef->getSortDirec(colPERIOD));
            break;
        case colROOKIE:
            $this->kcr_roster->sort_byRookie($columnDef->getSortDirec(colROOKIE));
            break;
        case colLANAME:
            $this->kcr_roster->sort_byLastName($columnDef->getSortDirec(colLANAME));
            break;
        default:
            $this->kcr_roster->sort_byFirstName($columnDef->getSortDirec(colFINAME));
            break;
    }
    //$this->kcr_roster->sortByFirstName($columnDef->getSortDirec(colFINAME));
    //$this->kcr_rosterKids = $this->kcr_roster->kidList->items;
    $this->kcr_roster->sort_end();

    //***************
    //* Final Inits *
    //***************
    // will need to be different for camps and other programs
    $windowTitle = 'KCM: ' . $this->kcr_roster->school->NameShort . ' ' . $this->kcr_roster->schedule->dowDesc. ' Point Tally';

    //@@@@@@@@@@@@@@
    //@ Start Page @
    //@@@@@@@@@@@@@@

    $this->kcr_page = new kcm_pageEngine($columnDef);
    $this->kcr_page->setIsReportPreview();
    $this->kcr_page->setBreakOnNewTable(TRUE);

    $this->kcr_pageTitle = 'Point Tally';

    if ($this->kcr_isExport) {
        $this->kcr_page->openForExport();
        $this->kcr_page->export->domAddStyleFile('kcm1css/kcm-common_css.css');
        $this->kcr_page->export->domAddStyleFile('kcm1css/kcm-rpt-PointTally.css');
    }

    $curPeriodId = NULL;
    $this->kcr_page->rowSetClasses(array('kgridEven','kgridOdd'));
    $count = count($this->kcr_roster->kidCount);
    $this->kcr_pageCount = 0;
    $this->kcr_kidCount = 0;
    $count = 93;  //112  93 works 94 has error

    for ($i = 0; $i<$this->kcr_roster->kidCount; $i++) {
        $kid = $this->kcr_roster->kidArray[$i];  // program - no period info
        $nextPeriodId =$kid->per->AtPeriodId;
        $nextPeriod = $this->kcr_roster->getPeriodFromPeriodId($nextPeriodId);
        ++$this->kcr_pageCount;
        if ($curPeriodId!=$kid->per->AtPeriodId or $this->kcr_pageCount>40) {
            if ($curPeriodId!=$kid->per->AtPeriodId) {
                if ($curPeriodId != NULL) {
                    $this->kcr_tableFooter();
                    $this->kcr_page->rpt_screenPageBreak(TRUE);
                }
                $this->kcr_kidCount = 0;
            }
            else if ($this->kcr_pageCount>43) {
                if ($curPeriodId != NULL) {
                    $this->kcr_page->rpt_screenPageBreak(TRUE);
                }
            }
            $curPeriodId =$kid->per->AtPeriodId;
            $curPeriod = $this->kcr_roster->getPeriodFromPeriodId($curPeriodId);
            $this->kcr_pageCount = 0;
            $this->kcr_page->headingStart('kpageHeadingPortrait');  //??? is kpage style needed here
            $this->kcr_page->headingText('Point Tally');
            $this->kcr_page->headingPeriod($curPeriod);
            $this->kcr_page->headingNextColumn();
            $this->kcr_page->headingProgram($this->kcr_roster);
            $this->kcr_page->headingSemester($this->kcr_roster);
            $this->kcr_page->headingEnd();

            //********* Kid Table - Header row **********
            $this->kcr_page->rpt_tableStart('kgridTable');
            $this->kcr_page->rpt_rowStart('h');
            $this->kcr_page->rpt_cellofText('ptyPoints ptyHead','Hour','rowspan=2');
            $this->kcr_page->rpt_cellofText('ptyPoints ptyHead','First Name','rowspan=2');
            $this->kcr_page->rpt_cellofText('ptyPoints ptyHead','Last Name','rowspan=2');
            $this->kcr_page->rpt_cellofText('ptyPoints ptyHead','GR','rowspan=2');
            $this->kcr_page->rpt_cellofText('ptyPoints ptyHead','N/V','rowspan=2');
            $this->kcr_page->rpt_cellofText('ptyPoints ptyHead','Points','rowspan=2');
            $this->kcr_page->rpt_cellofText('ptyChessGroup ptyHead','Regular Chess','colspan=3');
            $this->kcr_page->rpt_cellofText('ptyBugGroup ptyHead','Bughouse','colspan=2');
            $this->kcr_page->rpt_cellofText('ptyBlitzGroup ptyHead','Blitz Chess','colspan=3');
            $this->kcr_page->rpt_rowEnd();

            $this->kcr_page->rpt_rowStart('h');
            $this->kcr_page->rpt_cellofText('ptyWon ptyHead','W');
            $this->kcr_page->rpt_cellofText('ptyLost ptyHead','L');
            $this->kcr_page->rpt_cellofText('ptyDraw ptyHead','D');
            $this->kcr_page->rpt_cellofText('ptyWon ptyHead','W');
            $this->kcr_page->rpt_cellofText('ptyLost ptyHead','L');
            $this->kcr_page->rpt_cellofText('ptyWon ptyHead','W');
            $this->kcr_page->rpt_cellofText('ptyLost ptyHead','L');
            $this->kcr_page->rpt_cellofText('ptyDraw ptyHead','D');
            $this->kcr_page->rpt_rowEnd();
        }

        ++$this->kcr_kidCount;
        //if ($i>40)
            $this->kcr_printRow($kid);
    }

    $this->kcr_tableFooter();

    if (!$this->kcr_isExport) {
        $this->kcr_page->frmEnd();
        $this->kcr_page->ScreenOnlyEnd();
        $this->kcr_page->webPageBodyEnd();
    }

    if ($this->kcr_isExport) {
        $file = $this->kcr_roster->program->getExportName($this->kcr_roster).'-PointTally';
        $this->kcr_page->export->exportClose($this->kcr_exportCode,$file);
     }

}

//--- start printing report
function kcr_tableFooter() {
    $this->kcr_printRow(NULL,NULL);
    $this->kcr_printRow(NULL,NULL);
    $this->kcr_page->rpt_rowStart(' ');
//    $this->kcr_page->rpt_cellofText('ptyRookie ptyNoGrid','');
    $this->kcr_page->rpt_cellofText('ptyStudentCount ptyNoGrid','Student Count: '.$this->kcr_kidCount,'colspan=14');
    $this->kcr_page->rpt_rowEnd();
    $this->kcr_page->rpt_tableEnd();
}


function kcr_printRow($kid) {
    if ($kid!==NULL) {
        $periodDesc = $kid->getPeriodDesc(kcmPERIODFORMAT_TALLY);
        if ($kid->prg->Lunch) {
           $periodDesc .= '(L)';
        }
        $firstName = $kid->prg->FirstName;
        if ($kid->per->ParentHelperStatus>=1)  {
            $firstName = $firstName . ' (PH)';
        }
        $lastName = $kid->prg->LastName;
        $gradeDesc = $kid->prg->GradeDesc;
        $rookieDesc = kcmAsString_Rookie($kid,$this->kcr_roster);
    }
    else {
        $periodDesc = '';
        $firstName = '';
        $lastName = '';
        $gradeDesc = '';
        $rookieDesc ='.';
    }

    $this->kcr_page->rpt_rowStart('d'); // is a data row
    $this->kcr_page->rpt_cellofText('ptyPeriod',$periodDesc);
    $this->kcr_page->rpt_cellofText('ptyFirstName',$firstName);
    $this->kcr_page->rpt_cellofText('ptyLastName',$lastName);
    $this->kcr_page->rpt_cellofText('ptyGrade',$gradeDesc);
    $this->kcr_page->rpt_cellofText('ptyRookie',$rookieDesc);
    $this->kcr_page->rpt_cellofText('ptyPoints',''); // points
    $this->kcr_page->rpt_cellofText('ptyWon',''); // w
    $this->kcr_page->rpt_cellofText('ptyLost',''); // l
    $this->kcr_page->rpt_cellofText('ptyDraw',''); // d
    $this->kcr_page->rpt_cellofText('ptyWins',''); // w
    $this->kcr_page->rpt_cellofText('ptyLost',''); // l
    $this->kcr_page->rpt_cellofText('ptyWon',''); // w
    $this->kcr_page->rpt_cellofText('ptyLost',''); // l
    $this->kcr_page->rpt_cellofText('ptyDraw',''); // d
    $this->kcr_page->rpt_rowEnd();
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

$appChain->chn_form_register(1,'appForm_tallySheet_listing');
$appChain->chn_form_launch(); // proceed to current step

exit;


?>