<?php

//--- kcm-rpt-PointTally.php ---

ob_start();  // output buffering (needed for redirects, header changes)

include_once( 'rc_defines.inc.php' );
include_once( 'rc_admin.inc.php' );
include_once( 'rc_database.inc.php' );
include_once( 'rc_messages.inc.php' );
include_once( 'kcm-libAsString.inc.php' );
include_once( 'kcm-libKcmState.inc.php' );
include_once( 'kcm-libKcmFunctions.inc.php' );
include_once( 'kcm-page-ColumnDef.inc.php' );
include_once( 'kcm-page-Engine.inc.php' );
include_once( 'kcm-page-Styles.inc.php' );
include_once( 'kcm-page-DOM.inc.php' );
include_once( 'kcm-page-Export.inc.php' );
include_once( 'kcm-libNavigate.inc.php' );
include_once( 'kcm-roster.inc.php' );
include_once( 'kcm-roster_objects.inc.php' );

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
$navigate->addStyleSheet('css/kcm/kcm-rpt-PointTally.css','');
$navigate->setTitle('Print Point Tally','Print Point Tally');
$navigate->processLoginLogout($argSubmit,TRUE);

//***************
//* Set Columns *
//***************
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
kcm_authorizeProgramId($kcmState->ksProgramId, $db);

$roster = new kcm_roster($kcmState->ksProgramId); 
$roster->load_roster_headerAndKids();  

$mainTitle = 'Print Point Tally<br>'.$roster->program->getNameLong($roster);

//***********************
//* Menu and Page Title *
//***********************
$navigate->setClassData($roster);
$navigate->setTitleStandard('Print Point Tally',$mainTitle);

//************************
//* Init and Sort Roster *
//************************
//$roster = new rc_classRoster(); 
//$roster->sortDefineStart($claxxxssData,$argPeriodId);
$kcmState->ksSetArg('Mode','per'); 

$roster->sort_periodFilter(0);
$roster->sort_start();
$roster->sort_byPeriodCurrent('c');
//$roster->sort_byFirstName('c');
//$roster->sort_end();

//if ($columnDef->isFrozen(colGRADEGRP) or $columnDef->getCurSortColumn() == colGRADEGRP) { 
//     $roster->sort_byGradeGroup($columnDef->getSortDirec(colGRADEGRP));
//}     
if ($columnDef->isFrozen(colGRADE)) 
       $roster->sort_byGrade($columnDef->getSortDirec(colGRADE));

switch ($columnDef->getCurSortColumn()) {    
    case colGRADE:  // sorted above as could be frozen
       if (!$columnDef->isFrozen(colGRADE))
           $roster->sort_byGrade($columnDef->getSortDirec(colGRADE));
       break;
    case colPERIOD:
        $roster->sort_byPeriodName($columnDef->getSortDirec(colPERIOD));
        break;
    case colROOKIE: 
        $roster->sort_byRookie($columnDef->getSortDirec(colROOKIE));
        break;
    case colLANAME:
        $roster->sort_byLastName($columnDef->getSortDirec(colLANAME));
        break;
    default:
        $roster->sort_byFirstName($columnDef->getSortDirec(colFINAME));
        break;
}    
//$roster->sortByFirstName($columnDef->getSortDirec(colFINAME));
//$rosterKids = $roster->kidList->items;
$roster->sort_end();

//***************
//* Final Inits *
//***************
// will need to be different for camps and other programs
$windowTitle = 'KCM: ' . $roster->school->NameShort . ' ' . $roster->schedule->dowDesc. ' Point Tally';

//@@@@@@@@@@@@@@
//@ Start Page @
//@@@@@@@@@@@@@@

$page = new kcm_pageEngine($columnDef);
$page->setIsReportPreview();
$page->setBreakOnNewTable(TRUE);

$pageTitle = 'Point Tally';

if ($isExport) {
    $page->openForExport();
    $page->export->domAddStyleFile('css/kcm-common_css.css');
    $page->export->domAddStyleFile('css/kcm/kcm-rpt-PointTally.css');
}

if (!$isExport) {
    $navigate->show();
    $page->screenOnlyStart();
    $page->systemExportForm('Point Tally Print Preview',$kcmState,$thisPage);
    $page->textBreak();
    $page->screenOnlyEnd();
    
    $page->frmStart('get','options',$thisPage,'kcGuiOptionsForm');
} // end !isExport    

//--- start printing report
function tableFooter() {
    global $page, $kidCount;
    printRow(NULL,NULL);
    printRow(NULL,NULL);
    $page->rpt_rowStart(' ');
//    $page->rpt_cellofText('ptyRookie ptyNoGrid','');
    $page->rpt_cellofText('ptyStudentCount ptyNoGrid','Student Count: '.$kidCount,'colspan=14');
    $page->rpt_rowEnd();
    $page->rpt_tableEnd();
}

function printRow($kid) {
    global $page, $roster;

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
        $rookieDesc = kcmAsString_Rookie($kid,$roster);
    }
    else {
        $periodDesc = '';
        $firstName = '';
        $lastName = '';
        $gradeDesc = '';
        $rookieDesc ='.';
    }
    
    $page->rpt_rowStart('d'); // is a data row
    $page->rpt_cellofText('ptyPeriod',$periodDesc);
    $page->rpt_cellofText('ptyFirstName',$firstName);
    $page->rpt_cellofText('ptyLastName',$lastName);
    $page->rpt_cellofText('ptyGrade',$gradeDesc);
    $page->rpt_cellofText('ptyRookie',$rookieDesc);
    //~~15/08 $page->rpt_cellofText('ptyTotal','');
    $page->rpt_cellofText('ptyPoints',''); // points
    $page->rpt_cellofText('ptyWon',''); // w
    $page->rpt_cellofText('ptyLost',''); // l
    $page->rpt_cellofText('ptyDraw',''); // d
    $page->rpt_cellofText('ptyWins',''); // w
    $page->rpt_cellofText('ptyLost',''); // l
    $page->rpt_cellofText('ptyWon',''); // w
    $page->rpt_cellofText('ptyLost',''); // l
    $page->rpt_cellofText('ptyDraw',''); // d
    $page->rpt_rowEnd();
}

    //@@@@@@@@@@@@@@@@@@
    //@ Show Kid Table @
    //@@@@@@@@@@@@@@@@@@
    //$page->frmStart('get','kidttbl',$thisPage,'kcGuiDataForm');
$curPeriodId = NULL;    
$page->rowSetClasses(array('kgridEven','kgridOdd'));
$count = count($roster->kidCount);
$pageCount = 0;
$kidCount = 0;

// Fatal error: Allowed memory size of 134,217,728 bytes exhausted (tried to allocate 24 bytes) 
// Fatal error: Allowed memory size of PHPExcel\PHPExcel\CachedObjectStorage\CacheBase.php on line 110
$count = 93;  //112  93 works 94 has error

for ($i = 0; $i<$roster->kidCount; $i++) {
    $kid = $roster->kidArray[$i];  // program - no period info
    $nextPeriodId =$kid->per->AtPeriodId;
    $nextPeriod = $roster->getPeriodFromPeriodId($nextPeriodId);
    ++$pageCount;
    //????? not count - should be near page bottom margin
    if ($curPeriodId!=$kid->per->AtPeriodId or $pageCount>40) {  
        if ($curPeriodId!=$kid->per->AtPeriodId) {
            if ($curPeriodId != NULL) {
                //$page->rpt_tableEnd();
                tableFooter();    
                $page->rpt_screenPageBreak(TRUE);
            }    
            $kidCount = 0;
        }
        else if ($pageCount>43) {
            if ($curPeriodId != NULL) {  
                //$page->rpt_tableEnd();
                $page->rpt_screenPageBreak(TRUE);
            }    
        }
        $curPeriodId =$kid->per->AtPeriodId;
        $curPeriod = $roster->getPeriodFromPeriodId($curPeriodId);
        $pageCount = 0;
        $page->headingStart('kpageHeadingPortrait');  //??? is kpage style needed here
        $page->headingText('Point Tally');
        $page->headingPeriod($curPeriod);
        $page->headingNextColumn();
        $page->headingProgram($roster);
        $page->headingSemester($roster);
        $page->headingEnd();

        //********* Kid Table - Header row **********
        $page->rpt_tableStart('kgridTable');
        $page->rpt_rowStart('h');
        $page->rpt_col_Header (colPERIOD,'ptyPeriod ptyHead','Hour','rowspan=2');  
        $page->rpt_col_Header (colFINAME,'ptyFirstName ptyHead','First Name','rowspan=2');  
        $page->rpt_col_Header (colLANAME,'ptyLastName ptyHead','Last Name','rowspan=2');  
        $page->rpt_col_Header (colGRADE,'ptyGrade ptyHead','GR','rowspan=2');  
        $page->rpt_col_Header (colROOKIE,'ptyRookie ptyHead','N/V','rowspan=2');  
        //~~15/08 $page->rpt_cellofText ('ptyTotal ptyHead','Total','rowspan=2');  
        $page->rpt_cellofText('ptyPoints ptyHead','Points','rowspan=2');
        $page->rpt_cellofText('ptyChessGroup ptyHead','Regular Chess','colspan=3');
        $page->rpt_cellofText('ptyBugGroup ptyHead','Bughouse','colspan=2');
        $page->rpt_cellofText('ptyBlitzGroup ptyHead','Blitz Chess','colspan=3');
        $page->rpt_rowEnd();

        $page->rpt_rowStart('h');
        $page->rpt_cellofText('ptyWon ptyHead','W');
        $page->rpt_cellofText('ptyLost ptyHead','L');
        $page->rpt_cellofText('ptyDraw ptyHead','D');
        $page->rpt_cellofText('ptyWon ptyHead','W');
        $page->rpt_cellofText('ptyLost ptyHead','L');
        $page->rpt_cellofText('ptyWon ptyHead','W');
        $page->rpt_cellofText('ptyLost ptyHead','L');
        $page->rpt_cellofText('ptyDraw ptyHead','D');
        $page->rpt_rowEnd();
    }    
      
    //===============================
    //** Row for each kid - Kid Table 
    ++$kidCount;
    //if ($i>40)
        printRow($kid);
    }    
    
tableFooter();    
//$page->rpt_tableEnd();

if (!$isExport) {
    $page->frmAddHidden($kcmState->Id, $kcmState->ksConvertToString());
    $page->frmEnd();
    $page->ScreenOnlyEnd();
    $page->webPageBodyEnd();
}    

if ($isExport) {
    $file = $roster->program->getExportName($roster).'-PointTally';
    $page->export->exportClose($argSubmit,$file);
//    $page->export->domSetAutoPage(TRUE);
//    $page->export->domSetBreakOnNewTable(TRUE);
}


?>