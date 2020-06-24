<?php

ob_start();  // output buffering (needed for redirects, header changes)

include_once( 'rc_admin.inc.php' );
include_once( 'rc_defines.inc.php' );
include_once( 'rc_database.inc.php' );
include_once( 'rc_messages.inc.php' );
include_once( 'kcm-game_entry.inc.php' );
include_once( 'kcm-libAsString.inc.php' );
include_once( 'kcm-libKcmState.inc.php' );
include_once( 'kcm-libKcmFunctions.inc.php' );
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
$argSubmit = kcm_getParam('Submit', '');
$argFormat = kcm_getParam('Format', 'c');  // mode is type of report c=coach s=site leader p=parent info
$isExport = ($argSubmit==='e' or $argSubmit==='p');
$pageTitle = 'Roster';
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
$navigate->addStyleSheet('css/kcm/kcm-rpt-Roster.css');
$navigate->setTitle($pageTitle,$pageTitle);
$navigate->processLoginLogout($argSubmit,TRUE);
rc_showMessages();

//*************
//* Read Data *
//*************
$db = new rc_database();
kcm_authorizeProgramId($kcmState->ksProgramId, $db);
$roster = new kcm_roster($kcmState->ksProgramId,0,TRUE); 
$roster->load_roster_headerAndKids();  

//***********************
//* Menu and Page Title *
//***********************
$mainTitle = $pageTitle.'<br>'.$roster->program->getNameLong($roster);
$navigate->setClassData($roster);
$navigate->setTitleStandard($pageTitle,$mainTitle);

//************************
//* Init and Sort Roster *
//************************
//$roster = new rc_classRoster();
//$roster->sortDefineStart($classxxxData,FALSE);
//$roster->sortByPeriodGroup('c');
//$roster->sortByFirstName('c');
//$roster->sortDefineEnd($classxxxData);
//$rosterKids = $roster->kidList->items;
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

$page = new kcm_pageEngine();
$page->setIsReportPreview();


if ($isExport) {
    $page->openForExport(rpPAGE_LANDSCAPE);
    $page->export->domAddStyleFile('css/kcm-common_css.css');
    $page->export->domAddStyleFile('css/kcm/kcm-rpt-Roster.css');
    $page->export->domSetAutoPage(TRUE);
    $page->export->domSetBreakOnNewTable(FALSE);
}

if (!$isExport) {
    $navigate->show();

    $page->screenOnlyStart();
    $page->systemExportForm('Roster Print Preview',$kcmState,'kcm-rpt-Roster.php');
    $page->textBreak();
} // end !isExport    

//===========================================================
// Roster

$page->headingStart('kpageHeadingLandscape'); //??? is kpage style needed here
$page->headingText($pageTitle);
$page->headingNextColumn();
$page->headingProgram($roster);
$page->headingSemester($roster);
$page->headingEnd();


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

$topInfoText = array();
$topInfoFormat = array();
addTopInfo('Top',$dowDesc);
addTopInfo('Middle','Start Day: ' . substr($roster->program->DateClassFirst,5));
addTopInfo('Bottom','Trophy Day: ' . substr($roster->program->DateClassLast,5));
for ($i = 0; $i<$roster->periodCount ; $i++) {
    $period = $roster->periodArray[$i];
    if ($period->PeriodSequenceBits < 4096) { 
        $periods[] = $period;
    }    
}        
$periodCount = count($periods);

for ($i = 0; $i<$periodCount ; $i++) {
    $period = $roster->periodArray[$i];
    addTopInfo('Top',$period->PeriodName); 
    addTopInfo('Middle','Time: '.TimeToString($period->TimeStart).'-'.TimeToString($period->TimeEnd));
    addTopInfo('Bottom','Enrolled: ' . $period->kidThisPeriodCount);
    for ($j = 0; $j<$roster->unfilteredKidCount; $j++) {
        $kid = $roster->unfilteredKidArray[$j];
        if ( ($kid->per->ParentHelperStatus>=1) and ($kid->per->AtPeriodId == $period->PeriodId) ) {
           setPrevTopInfoFormat('Middle');
           $phName = $kid->per->ParentHelperName;
           if ($phName == '') {
               $phName = $kid->prg->parent1->FirstName . ' ' . $kid->prg->parent1->LastName;
           }
           addTopInfo('Bottom','PH: ' . $phName);
        }
    }    
}        
addTopInfo('Top','Available'); 
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
    addTopInfo($format,$s);
}
$lunchCount = 0;
for ($j = 0; $j<$roster->unfilteredKidCount; $j++) {
    $kid = $roster->unfilteredKidArray[$j];
    if ( ($kid->prg->Lunch)  and ($kid->per->PeriodBitsSinglePeriod == 1) ) {
        ++$lunchCount;
    }    
}
if ($lunchCount >= 1) {
    addTopInfo('Bottom','Lunches: ' . $lunchCount); 
}    

//=============================================

$topNotesText = array();
$topNotesDesc = array();
$topNotesFormat = array();
//$topNotesText[] = 'Notes:';
//$topNotesFormat[] = '';
$ad = $roster->school->Address . ', ' . $roster->school->City
     . ', ' . $roster->school->State  . ', ' . $roster->school->Zip;
addTopNote('Address',$ad);
addTopNote('Phone',$roster->school->SchoolPhone);
addTopNote('Contacts',$roster->school->NotesContacts);
addTopNote('School Notes',$roster->school->NotesOther);
addTopNote('Room',$roster->program->NotesRoomInfo);
addTopNote('Equipment',$roster->school->NotesEquipment);
addTopNote("Upon Arriving",$roster->program->NotesUponArriving);
addTopNote("Before Leaving",$roster->program->NotesBeforeLeaving);
addTopNote("ASP Notes",$roster->program->NotesASPInstructions);
addTopNote("Parent Pickup",$roster->program->NotesParentPickup);
addTopNote("Coach Notes",$roster->program->NotesForCoach);
addTopNote("Site Leader",$roster->program->NotesForSiteLeader);

// coach notes
addTopNote('','');
$desc = 'Kid Notes';
for ($i = 0; $i<$roster->kidCount; $i++) {
    $kid = $roster->kidArray[$i];
    if ( $kid->prg->NotesForCoach!='') {
        $name = $kid->prg->FirstName . ' ' . $kid->prg->LastName;
        addTopNote($desc,$name.': '.$kid->prg->NotesForCoach);
        $desc = '';
    }    
}    
// ASP notes
for ($i = 0; $i<$roster->kidCount; $i++) {
    $kid = $roster->kidArray[$i];
    if ( $kid->prg->PickupNotes!='') {
        $s = $kid->prg->FirstName . ' ' . $kid->prg->LastName . ': ' . $kid->prg->PickupNotes;
        addTopNote('ASP Note',$s);
    }    
}    
//    $page->rpt_cellofText('rstPickupNote',$kid->prg->PickupNotes);

$topNotesCount = count($topNotesText);
$topInfoCount = count($topInfoText);

$maxCount = max($topInfoCount,$topNotesCount);
$page->rpt_tableStart('rstTopTable');
for ($i = 0; $i< $maxCount; $i++) {
    $page->rpt_rowStart('');
    if ($i < $topInfoCount) {
        $page->rpt_cellofText($topInfoFormat[$i],''.$topInfoText[$i]);
    }    
    else {
        $page->rpt_cellofText('rstInfo','');
    }    
    if ($i < $topNotesCount) {
        $page->rpt_cellofText('rstTopDesc',$topNotesDesc[$i]);
        $page->rpt_cellofText('rstTopNotes',$topNotesText[$i]);
    }    
    else {
        $page->rpt_cellofText('rstTopDesc','');
        $page->rpt_cellofText('rstTopNotes','');
    }    
    $page->rpt_rowEnd();
}

$page->rpt_rowStart(' ');
$page->rpt_cellofText('rstEndRow','');
$page->rpt_cellofText('rstEndRow','');
$page->rpt_rowEnd(' ');

$page->rpt_tableEnd();

$page->rpt_tableStart('rstTable');

//---- Main  section  
    
//    $page->rpt_rowStart('h');   // default for borders is now table default
    $page->rpt_rowStart('');   // default for borders is now table default
    $page->rpt_cellofText('rstFirstName knogridHead',"First");
    $page->rpt_cellofText('rstLastName knogridHead',"Last Name");
    $page->rpt_cellofText('rstGrade knogridHead',"Grade");
    $page->rpt_cellofText('rstPeriod knogridHead',"Period");
    $page->rpt_cellofText('rstRookie knogridHead',"Status");
    $page->rpt_cellofText('rstHome knogridHead',"Home");
    $page->rpt_cellofText('rstCell knogridHead',"Cell");
    $page->rpt_cellofText('rstBusiness knogridHead',"Business");
    $page->rpt_cellofText('rstEmergency knogridHead',"Emergency");
    $page->rpt_cellofText('rstTeacher knogridHead',"Teacher");
    $page->rpt_cellofText('rstAsp knogridHead',"ASP");
    $page->rpt_rowEnd();

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
        blankLine();
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
    $page->rpt_rowStart();
    $page->rpt_cellofText('rstFirstName',$kid->prg->FirstName);
    $page->rpt_cellofText('rstLastName',$kid->prg->LastName);
    $page->rpt_cellofText('rstGrade',$kid->prg->GradeDesc);
    $page->rpt_cellofText('rstPeriod',$periodName);
    $page->rpt_cellofText('rstRookie',kcmAsString_Rookie($kid,$roster));
    $page->rpt_cellofText('rstHome',$kid->prg->parent1->HomePhone.$p1);
    $page->rpt_cellofText('rstCell',$kid->prg->parent1->CellPhone.$p2);
    $page->rpt_cellofText('rstBusiness',$kid->prg->parent1->WorkPhone.$p3);
    $page->rpt_cellofText('rstEmergency',$kid->prg->family->EmergencyPhone);
    if ($roster->program->ProgramType==1)
       $s = $kid->prg->Teacher;
    else           
       $s = $kid->prg->TeamName;
    $page->rpt_cellofText('rstTeacher',$s);
    $page->rpt_cellofText('rstAsp',$kid->prg->PickupDesc);
    $page->rpt_rowEnd();
}
$page->rpt_tableEnd();
$page->webPageBodyEnd();

if ($isExport) {
    $file = $roster->program->getExportName($roster).'-Roster';
    $page->export->exportClose($argSubmit,$file);
}

exit;

//=================================================
//==   Main code ends here
//==   Functions Start Here 
//=================================================

function addTopNote($pDesc, $pNote) {
    global $topNotesText, $topNotesDesc, $topNotesFormat;
    $pNote = trim($pNote);
    $n = preg_split ('/$\R?^/m', $pNote);
    if ($pDesc != '') {
       $pDesc = $pDesc . ':';
    }   
    $topNotesText[] = $n[0];
    $topNotesDesc[] = $pDesc;
    $topNotesFormat[] = '';
    for ($i = 1; $i<count($n); $i++) {
        if ($n[$i] != '') {
            $topNotesText[] = '....'.$n[$i];
            $topNotesDesc[] = '';
            $topNotesFormat[] = '';
        }    
    }    
}

function addTopInfo($pClass, $pText) {
     //~~?????? should use css, not $pStyle
    global $topInfoFormat, $topInfoText;
    $topInfoText[] = $pText;
    $topInfoFormat[] = 'rstInfo rstInfo'. $pClass;
}
function setPrevTopInfoFormat($pFormat) {
    global $topInfoFormat;
    $topInfoFormat[count($topInfoFormat)-1] = 'rstInfo rstInfo'. $pFormat;
}

function blankLine() {
    global $page,$argFormat;
    $page->rpt_rowStart('d');   // default for borders is now table default
    $page->rpt_cellofText('rstFirstName','',"");
    $page->rpt_cellofText('rstLastName','',"");
    $page->rpt_cellofText('rstGrade','');
    $page->rpt_cellofText('rstPeriod','');
    $page->rpt_cellofText('rstRookie','');
    $page->rpt_cellofText('rstHome','');
    $page->rpt_cellofText('rstCell','');
    $page->rpt_cellofText('rstBusiness','');
    $page->rpt_cellofText('rstEmergency','');
    $page->rpt_cellofText('rstTeacher','');
    $page->rpt_cellofText('rstAsp','');
    if ($argFormat==='c') {
        $page->rpt_cellofText('rstPickupNote','');
    }    
    if ($argFormat==='s') {
        $page->rpt_cellofText('rstPickupNote','');
    }    
    $page->rpt_rowEnd();
}

function TimeToString($pTime) {
 $timestamp = strtotime( $pTime );
 return date( 'g:i', $timestamp ); 
}

?>