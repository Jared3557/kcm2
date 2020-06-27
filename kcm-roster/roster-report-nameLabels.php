<?php

//--- roster-report-nameLabels.php ---

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

class appForm_nameLabels_listing extends kcmKernel_Draff_Form {  // specify winner

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $appData->apd_reportFilters->rf_form_processExportSubmit( $appData->apd_tallyReport, $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
    $appData->apd_roster_program->rst_load_kids($appGlobals, $appData->apd_roster_program->prog_programId);
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-panel' );
    $appEmitter->emit_options->set_title('Set Class Period');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize();
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Name Labels');
    if ($appData->apd_isExport) {
        return;
    }
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_css.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_screen.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_print.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-rpt-NameLabels.css','all','../');
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

class application_data extends draff_appData {
public $apd_roster_program;

public $apd_tallyReport;
public $apd_isExport = FALSE;
public $apd_exportCode = 'h';
public $apd_reportFilters;

function __construct() {
}

function apd_formData_get( $appGlobals, $appChain ) {
    $this->apd_isExport = ( ($this->apd_exportCode=='p') or ($this->apd_exportCode=='e') );
    $appGlobals->gb_isExport = $this->apd_isExport; //???????????????
    $this->apd_tallyReport = new kcm1Report_nameLabels($this->apd_roster_program,'h');
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

class kcm1Report_nameLabels {
public $kcr_programObject;
public $kcr_exportCode;
public $kcr_isExport;
public $kcr_page;
public $kcr_argFormat;

function __construct ($programId) {
    $this->kcr_programObject  = $programId;
}

function kcr_print($exportCode) {

$this->kcr_exportType =$exportCode;
$this->kcr_isExport   = ( ($this->kcr_exportType=='p') or ($this->kcr_exportType=='e'));

//*************
//* Read Data *
//*************
$db = new rc_database();

$roster = new kcm_roster($this->kcr_programObject->prog_programId);
$roster->load_roster_headerAndKids();

$mainTitle = 'Name Labels<br>'.$roster->program->getNameLong($roster);

//***********************
//* Menu and Page Title *
//***********************
// $navigate->setClassData($roster);
// $navigate->setTitleStandard('Name Labels',$mainTitle);

//************************
//* Init and Sort Roster *
//************************
$roster->sort_periodFilter(0,TRUE);
$roster->sort_start(FALSE);
$roster->sort_byPeriodCurrent('c');
$roster->sort_byFirstName('c');
$roster->sort_end();


//***************
//* Final Inits *
//***************
// will need to be different for camps and other programs
$windowTitle = 'KCM: ' . $roster->school->NameShort . ' ' . $roster->schedule->dowDesc. ' Name Labels';

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
    $page->export->domAddStyleFile('kcm1css/kcm-rpt-NameLabels.css');
}

$pageTitle = 'Name Labels';

if (!$this->kcr_isExport) {
//    $navigate->show();
//    $page->systemExportForm('Name Labels Print Preview',$kcmState,'kcm-rpt-NameLabels.php',FALSE);
//    $page->textBreak();
} // end !isExport




//===========================================================

// Name Labels Report


$progName = $roster->program->ProgramName;

//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%???????????????????????????????
$kid = $roster->kidArray[0];  // program - not period //????? problem if none
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
while($kidIndex<=$roster->kidCount) {
    $page->rpt_tableStart('nlbTable', '');
    ++$pageNum;
    $page->rpt_rowStart();
    $sem = $roster->schedule->meetDateDesc;
    $prg = $roster->program->getNameLong($roster);
    $page->rpt_cellOfText('nlbTopCell','Name Labels - '.$prg);
    $page->rpt_cellOfText('nlbTopCell',$sem);
    $page->rpt_rowEnd();
    for ($row=1; $row<=5; $row++) {
        $page->rpt_rowStart();
        for ($col=1; $col<=2; $col++) {
            if ($kidIndex<$roster->kidCount) {
                $kid = $roster->kidArray[$kidIndex];  // program
                //$k = $kp->kidProgram;
                //###$period = kcmAsString_PeriodLong($kp->PeriodBitsWithFeatures,$k->PeriodComboAllBits,$roster->program->ProgramType, FALSE, TRUE);
                //$period = kcmAsString_PeriodLong($kid->prg->PeriodComboAllBits,$kid->prg->PeriodComboAllBits,$roster->program->ProgramType, FALSE, TRUE);
                // do lunch later
                $pickupImage =NULL;
                if ($kid->prg->PickupCode==1) { // ASP
                    if ($kid->per->PeriodComboAllBits==1)
                       $pickupImage = "../../images/kcm-reports/ASP_1st.jpg";
                    else
                       $pickupImage = "../../images/kcm-reports/ASP_2nd.jpg";
                }
                if ($kid->prg->PickupCode==2) { // parent
                    if ($kid->prg->PeriodComboAllBits==1)
                       $pickupImage = "../../images/kcm-reports/CarPool_1st.jpg";
                    else
                       $pickupImage = "../../images/kcm-reports/CarPool_2nd.jpg";
                }
                // @JPR-2019-01-21 11:44 additional code - START
                if ($kid->prg->PickupCode==3) { // walker
                    if ($kid->prg->PeriodComboAllBits==1)
                       $pickupImage = "../../images/kcm-reports/Walker_1st.jpg";
                    else
                       $pickupImage = "../../images/kcm-reports/Walker_2nd.jpg";
                }
                if ($kid->prg->PickupCode==90) { // other
                   if ($kid->prg->PeriodComboAllBits==1)
                      $pickupImage = "../../images/kcm-reports/Other_1st.jpg";
                   else//@JPR-2019-01-21 11:44
                      $pickupImage = "../../images/kcm-reports/Other_2nd.jpg";
                }
                if ($kid->prg->PickupCode==91) { // varies
                    if ($kid->prg->PeriodComboAllBits==1)
                       $pickupImage = "../../images/kcm-reports/Varies_1st.jpg";
                    else
                       $pickupImage = "../../images/kcm-reports/Varies_2nd.jpg";
                }
                // @JPR-2019-01-21 11:44 additional code - END
                if ($roster->program->ProgramType==1)
                    $teacher = $kid->prg->Teacher;
                else
                    $teacher = $kid->prg->TeamName;
                if ($kid->prg->Lunch)
                    $note='Lunch';
                else
                    $note='';
                $perDesc = $kid->getPeriodDesc(kcmPERIODFORMAT_LONG);
                $items = array (
                    'img.nlbLogo','../../images/kcm-reports/LogoForLabels.jpg',
                    'div.nlbFirstName',$kid->prg->FirstName,
                    'div.nlbLastName',$kid->prg->LastName,
                    'div.nlbGrade',$kid->prg->GradeDesc.' Grade',
                    'div.nlbTeacher',$teacher,
                    'div.nlbPeriod',$perDesc,
                    'div.nlbNote',$note,
                );
                $perCount = count($kid->prg->kidPeriodArray);
                $haveSubGroup = FALSE;
                $sgCount = 0;
                $sgArray = array();
                for ($j=0; $j<$perCount; ++$j) {
                    $kp = $kid->prg->kidPeriodArray[$j];
                    if ($this->noParan($kp->KcmClassSubGroup) != '') {
                        ++$sgCount;
                        $sgArray[] = $this->noParan($kp->KcmClassSubGroup);
                        $haveSubGroup = TRUE;
                    }
                }
                if ($haveSubGroup) {
                    if ($perCount == 1 ) {
                        $s = $this->noParan($kp->KcmClassSubGroup);
                    }
                    else if ( ($sgCount == 2) and ($perCount==2) and ($sgArray[0]==$sgArray[1]) ) {
                        $s = $this->noParan($kp->KcmClassSubGroup);
                    }
                    else {
                        $s = '';
                        $sep = '';
                        for ($j=0; $j<$perCount; ++$j) {
                            $kp = $kid->prg->kidPeriodArray[$j];
                            if ( $this->noParan($kp->KcmClassSubGroup) != '') {
                                $s .= $sep . substr($kp->period->PeriodName,0,3) . '-' . $this->noParan($kp->KcmClassSubGroup);
                                $sep = ' : ';
                            }
                        }
                    }
                    $items[] = 'div.nlbSubGroup';
                    $items[] = $s;
                }
                if ($pickupImage!=NULL) {
                    $items[] =  'img.nlbAspImg';
                    $items[] =  $pickupImage;
                }
                // Start of added //@JPR-2019-03-25 22:12
                if ($kid->prg->PhotoReleaseStatus == 1) {
                    $items[] = 'img.nlbCameraImg';
                    $items[] = '../../images/kcm-reports/NameLabel_Camera.jpg';
                }
                // else if ($kid->prg->PhotoReleaseStatus == -1) {
                //     $items[] = 'img.nlbCameraImg';
                //     $items[] = 'images/kcm-reports/NameLabel_CameraMaybe.jpg';
                // }
                // End of added //@JPR-2019-03-25 22:12
                $page->rpt_cellOfItems('nlbLabelDiv','nlbLabelCell', $items);
           }
           else
               $page->rpt_cellOfText('nlbLabelCell', '');
           $kidIndex = $kidIndex +1;
        } // end col
        $page->rpt_rowEnd();
    } // end row
    $page->rpt_tableEnd();
    $page->rpt_screenPageBreak();
} // end kidIndex

if (!$this->kcr_isExport)
    $page->webPageBodyEnd();
//    $page->export->exportClose($argSubmit,$claxxxssData->program->programName.'-PointTally');

if ($this->kcr_isExport) {
    $file = $roster->program->getExportName($roster).'-NameLabels';
    $page->export->exportClose('p',$file,'comic');
}

}

function noParan($s) {
    $match1 = strpos($s,'(');
    if ($match1===FALSE)
        return $s;
    $match2 = strpos($s,')');
    if ($match2===FALSE)
        return $s;
    if ($match1==0)
        $s1 = '';
	else
		$s1 = substr($s,0,$match1);
	$s2 = substr($s,$match2+1);
    if ($s1=='')
        return $s2;
    else if ($s2=='')
        return $s1;
    else
        return $s1 . '-' . $s2;
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

$appChain->chn_form_register(1,'appForm_nameLabels_listing');
$appChain->chn_form_launch(); // proceed to current step

exit;


?>