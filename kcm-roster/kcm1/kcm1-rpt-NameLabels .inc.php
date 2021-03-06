<?php

//--- kcm1-rpt-NameLabels.inc.php

class kcr_report_roster {
public $kcr_programObject;
public $kcr_exportCode;
public $kcr_isExport;
public $kcr_page;
public $kcr_argFormat;

function __construct ($programId, $exportType) {
    $this->kcr_programObject  = $programId;
    $this->kcr_exportType = $exportType;
}

function kcr_print($exportCode) {
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
$navigate->setClassData($roster);
$navigate->setTitleStandard('Name Labels',$mainTitle);

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
    $page->export->domAddStyleFile('css/kcm-common_css.css');
    $page->export->domAddStyleFile('css/kcm/kcm-rpt-NameLabels.css');
}

$pageTitle = 'Name Labels';

if (!$this->kcr_isExport) {
    $navigate->show();
    $page->systemExportForm('Name Labels Print Preview',$kcmState,'kcm-rpt-NameLabels.php',FALSE);
    $page->textBreak();
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
                       $pickupImage = "images/kcm-reports/ASP_1st.jpg";
                    else
                       $pickupImage = "images/kcm-reports/ASP_2nd.jpg";
                }
                if ($kid->prg->PickupCode==2) { // parent
                    if ($kid->prg->PeriodComboAllBits==1)
                       $pickupImage = "images/kcm-reports/CarPool_1st.jpg";
                    else
                       $pickupImage = "images/kcm-reports/CarPool_2nd.jpg";
                }
                // @JPR-2019-01-21 11:44 additional code - START
                if ($kid->prg->PickupCode==3) { // walker
                    if ($kid->prg->PeriodComboAllBits==1)
                       $pickupImage = "images/kcm-reports/Walker_1st.jpg";    
                    else
                       $pickupImage = "images/kcm-reports/Walker_2nd.jpg";
                }
                if ($kid->prg->PickupCode==90) { // other
                   if ($kid->prg->PeriodComboAllBits==1)
                      $pickupImage = "images/kcm-reports/Other_1st.jpg";
                   else//@JPR-2019-01-21 11:44 
                      $pickupImage = "images/kcm-reports/Other_2nd.jpg";
                }
                if ($kid->prg->PickupCode==91) { // varies
                    if ($kid->prg->PeriodComboAllBits==1)
                       $pickupImage = "images/kcm-reports/Varies_1st.jpg";
                    else
                       $pickupImage = "images/kcm-reports/Varies_2nd.jpg";
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
                    'img.nlbLogo','images/kcm-reports/LogoForLabels.jpg',
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
                    $items[] = 'images/kcm-reports/NameLabel_Camera.jpg';        
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
    $page->export->exportClose($argSubmit,$file,'comic');
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
?>