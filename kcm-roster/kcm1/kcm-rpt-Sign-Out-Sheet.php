<?php

class report_signOutSheet {
    
public $kcr_programObject;
public $kcr_exportCode;
public $kcr_isExport;
public $kcr_page;

function __construct ($programObject, $exportType) {
    $this->kcr_programObject  = $programObject;
    $this->kcr_exportType = $exportType;
}

function kcr_print($exportCode) {

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

$page = new kcm_pageEngine();
$page->setIsReportPreview();
$page->setBreakOnNewTable(TRUE);

$pageTitle = 'Sign-Out Sheet';

if ($isExport) {
    $page->openForExport();
    $page->export->domAddStyleFile('kcm1css/kcm-common_css.css');
    $page->export->domAddStyleFile('kcm1css/kcm-rpt-Sign-Out-Sheet.css');
    $page->export->domSetAutoPage(TRUE);
    $page->export->domSetBreakOnNewTable(FALSE);}

if (!$isExport) {
//    $navigate->show();
//    $page->screenOnlyStart();
//    $page->systemExportForm('Sign-Out Sheet Print Preview',$kcmState,$thisPage);
//    $page->textBreak();
//    $page->screenOnlyEnd();
//    
//    $page->frmStart('get','options',$thisPage,'kcGuiOptionsForm');
} // end !isExport    

//--- start printing report
function tableFooter() {
    global $page, $kidCount;
    $page->rpt_tableEnd();
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

$count = 93;  //112  93 works 94 has error

$kidArray = array();
for ($i = 0; $i<$roster->kidCount; $i++) {
    $kid = $roster->kidArray[$i];  // program - no period info
    $kidArray[] = new KidLine($kid);
}    
$kidCount = count($kidArray);

$lineCount = 0;
$page->setBreakOnNewTable(TRUE);
for ($i = 0; $i<$roster->kidCount; $i++) {
    $rosterKid = $roster->kidArray[$i];  // program - no period info
    $kid = $kidArray[$i];
    ++$pageCount;
    ++$lineCount;
    //????? not count - should be near page bottom margin
    if ($curPeriodId!=$kid->periodId or $lineCount>40) {  
        $lineCount = 1;
        if ($curPeriodId!=$kid->periodId) {
            //$lineCount = 0;
            if ($curPeriodId != NULL) {
                //$page->rpt_tableEnd();
                $this->tableFooter();    
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
        $curPeriodId =$kid->periodId;
        $curPeriod = $roster->getPeriodFromPeriodId($curPeriodId);
        $pageCount = 0;
        $page->headingStart('kpageHeadingPortrait');  //??? is kpage style needed here
        $page->headingText('Sign-Out Sheet');
        $page->headingPeriod($curPeriod);
        $page->headingNextColumn();
        $page->headingProgram($roster);
        $page->headingSemester($roster);
        $page->headingEnd();

        //********* Kid Table - Header row **********
        $page->rpt_tableStart('kgridTable');
        $page->rpt_rowStart();
        $page->rpt_cellOfText('sosFirst sosHead','First Name');  
        $page->rpt_cellOfText('sosLast sosHead','Last Name');  
        $page->rpt_cellOfText('sosASP sosHead','A/P');  
        $page->rpt_cellOfText('sosSig sosHead','Signature');  
        $page->rpt_cellOfText('sosNotes sosHead','Pickup Notes');
        $page->rpt_rowEnd();

    }    
      
    //===============================
    //** Row for each kid - Kid Table 
    ++$kidCount;
    //if ($i>40)
        //printRow($kid, $rosterKid);
    $page->rpt_rowStart(); // is a data row
    $page->rpt_cellofText('sosFirst',$kid->firstName);
    $page->rpt_cellofText('sosLast',$kid->lastName);
    $page->rpt_cellofText('sosASP',$kid->pickupCodeDesc);
    $page->rpt_cellofText('sosSig','');
    $page->rpt_cellofText('sosNotes',$kid->pickupNotes);
  }    
    
$this->tableFooter();    
//$page->rpt_tableEnd();

if (!$isExport) {
    $page->frmAddHidden($kcmState->Id, $kcmState->ksConvertToString());
    $page->frmEnd();
    $page->ScreenOnlyEnd();
    $page->webPageBodyEnd();
}    

if ($isExport) {
    $file = $roster->program->getExportName($roster).'SignOutSheet';
    $page->export->exportClose($this->kcr_exportCode,$file);
//    $page->export->domSetAutoPage(TRUE);
//    $page->export->domSetBreakOnNewTable(TRUE);
}

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

}

} // end class