<?php

//-- kcm-classRoster.inc.php

Class rc_classRoster {

public $program;  // reference to classData->program 
public $school;   // reference to classData->school
public $periodArray;
public $periodCount;
public $kidList;  // not a reference to classData->kidList (but each kid is a reference to the kids in classData) 
public $skillsConfig;
public $classPoints; // points options/configuration and useful functions
public $classGames;   // games options/configuration and useful functions
private $classData;

private $showKidEveryPeriod;  // can be True, False, or PeriodId (one period)
private $sortOrder;  // the sort order of kid $sortOrder[$kidlist Index] is sort order (for cross-tables, etc)
private $sortCodeArray;
private $sortDirecArray;
private $sortGameTypeIndex;  // NULL if none - // to pre-compute sort for section
private $sortTournPercent;  // used by usort
private $sortClassPointsArray;  // used by usort
private $sortClassPointsActive; // used by usort


function __construct() {
    $this->kidList = NULL; 
}

function getSortedKidIndex($pKid) {
    return $this->sortOrder[$pKid->Index];
}

//===================================
//=  Sort Define Functions

function sortDefineStart($pClassData,$pShowKidEveryPeriod) {
    $this->classData = $pClassData;
    $this->showKidEveryPeriod = $pShowKidEveryPeriod; // pShowKidEveryPeriod can be True, False, or PeriodId (one period)
    if ($this->kidList!=NULL)
        $this->kidList->clear();
    else    
        $this->kidList = new kcm_roster_kidPeriod();
    $this->sortGameTypeIndex = NULL;
    $this->program = $pClassData->program; 
    $this->skillsConfig = $pClassData->skillsConfig;
    $this->school = $pClassData->school; 
    $this->periodArray = $pClassData->periodArray; 
    $this->periodCount = $pClassData->periodCount; 
    $this->classPoints = $pClassData->classPoints; 
    $this->classGames = $pClassData->classGames; 
    $this->sortClassPointsActive = FALSE;
    $this->setSortPropertiesStart();
}

function setSortPropertiesStart() {  // only used if multiple sorts on one page
    $this->sortGameTypeIndex = NULL;
    $this->sortCodeArray = array ();
    $this->sortDirecArray = array ();
}
function sortByFirstName($pDirec='u') {
    $this->addSort('n',$pDirec);
}
function sortByKidGroup($pDirec='u') {  // sort within filter
    $this->addSort('kg',$pDirec);
}
function sortByPeriodName($pDirec='u') {  // sort within filter
    $this->addSort('pn',$pDirec);
}
function sortByPeriodCurrent($pDirec='u') {  // sort within filter
    $this->addSort('pc',$pDirec);
}
function sortByPeriodEarliest($pDirec='u') {  // sort within filter
    $this->addSort('pe',$pDirec);
}
function sortByPeriodGroup($pDirec='u') {  // sort within filter
    $this->addSort('pg',$pDirec);
}
function sortByLastName($pDirec='u') {
    $this->addSort('L',$pDirec);
}
function sortByGrade($pDirec='u') {
    $this->addSort('g',$pDirec);
}
function sortByRookie($pDirec='u') {
    $this->addSort('rk',$pDirec);
}
function sortByGradeGroup($pDirec='u') {
    $this->addSort('gg',$pDirec);
}
function sortByTournament($pGameTypeIndex, $pDirec='u') {
    $this->addSort('t',$pDirec);
    $this->sortGameTypeIndex = $pGameTypeIndex;
}
function sortByClassPoints($pDirec='u') {
    $this->addSort('ptc',$pDirec);
    $this->sortClassPointsActive = TRUE;
}
function sortByTotalPoints($pDirec='u') {
    $this->addSort('ptt',$pDirec);
}

function sortByPickup($pDirec='u') {
    $this->addSort('u',$pDirec);
}

function sortDefineEnd() {
    $this->kidList->items = array();
    //--- Filter by Period ---
    for ($i=0; $i<count($this->classData->kidList->items); $i++) {
        $kid = $this->classData->kidList->items[$i]; 
        if ($this->showKidEveryPeriod===TRUE)
           $inc = TRUE;
        else if ($this->showKidEveryPeriod===FALSE) 
           $inc = ($kid->PeriodBitsSinglePeriod===$kid->PeriodComboEarliest);
        else if ($this->showKidEveryPeriod===$kid->AtPeriodId)
           $inc = TRUE;
        else   
            $inc = FALSE;
        if ($inc)   
           $this->kidList->items[] = $kid;
    }   
    $max = 0;
    $count = count($this->kidList->items);
    for ($i=0; $i<$count; $i++) {
        $kid = $this->kidList->items[$i];
        $max = max($max,$kid->Index);
    }        
    if ($this->sortGameTypeIndex!=NULL)
        $this->sortTournPercent = array ($max);
    if ($this->sortClassPointsActive)
        $this->sortClassPointsArray = array ($max);
    $this->sortOrder = array ($max);
    for ($i=0; $i<$count; $i++) {
        $kid = $this->kidList->items[$i];
        if ($this->sortGameTypeIndex!=NULL) {
            $sec=$this->classData->classGames->sectionGetResults($kid, $this->sortGameTypeIndex);
            $this->sortTournPercent[$kid->Index] = $sec->Percent;
        }    
        if ($this->sortClassPointsActive) {
        //??????????????????????????????????????????????????????
            $pt=$this->classData->classPoints->getPointsItemResult($kid);
            $this->sortClassPointsArray[$kid->Index] = $pt->totalClassPoints;
        }    
    }    
    $kidCount = count($this->kidList->items);
    if ($this->sortGameTypeIndex!==NULL) {
        for ($i=0; $i<$kidCount; $i++) {
            $kid = $this->kidList->items[$i];
            $this->sortTournPercent[$kid->Index] = $this->classGames->sectionGetResults($kid, $this->sortGameTypeIndex)->Percent;
        }
    }    
    usort ( $this->kidList->items, array($this,'compare') );
    $count = count($this->kidList->items);
    for ($i=0; $i<$kidCount; $i++) {
        $kid = $this->kidList->items[$i];
        $this->sortOrder[$kid->Index] = $i;
    }    
}

//===================================
//=  Compare and other functions

private function addSort($pCode, $pDirec) {
    $this->sortCodeArray[] = $pCode;
    if ($pDirec===TRUE or $pDirec==='c')
        $this->sortDirecArray[] = -1;
    else
        $this->sortDirecArray[] = 1;
}
private function compare($a, $b) {
    if ($a == $b) 
        return 0;
    $ai = $a->Index;    
    $bi = $b->Index;  
    $cnt = count($this->sortCodeArray);
    for ($i=0; $i<$cnt; $i++) {
        $sc = $this->sortCodeArray[$i];
        $sd = $this->sortDirecArray[$i];
        switch ($sc) {
            case 'pe': // earliest period
                if ($a->PeriodComboEarliest!=$b->PeriodComboEarliest)
                    return ($a->PeriodComboEarliest < $b->PeriodComboEarliest) ? $sd : -$sd;
                break;      
            case 'pc': // single (current) period
                if ($a->PeriodBitsSinglePeriod!=$b->PeriodBitsSinglePeriod)
                    return ($a->PeriodBitsSinglePeriod < $b->PeriodBitsSinglePeriod) ? $sd : -$sd;
                break;      
            case 'pg': // period group
                if ($a->PeriodComboSortCode!=$b->PeriodComboSortCode)
                    return ($a->PeriodComboSortCode < $b->PeriodComboSortCode) ? $sd : -$sd;
                break;      
            case 'pn': // period name (same as combined bits)
                if ($a->PeriodComboAllBits!=$b->PeriodComboAllBits)
                    return ($a->PeriodComboAllBits < $b->PeriodComboAllBits) ? $sd : -$sd;
                break;      
            case 'n':  // first name
                if ($a->FirstName!=$b->FirstName)
                  return ($a->FirstName < $b->FirstName) ? $sd : -$sd;
                break;      
            case 'rk':  // rookie/veteran
                $ar =kcmAsString_Rookie($a);
                $br =kcmAsString_Rookie($b);
                if ($ar!=$br)
                  return ($ar < $br) ? $sd : -$sd;
                break;      
            case 'L':  // last name
                if ($a->LastName!=$b->LastName)
                  return ($a->LastName < $b->LastName) ? $sd : -$sd;
                if ($a->FirstName!=$b->FirstName)
                  return ($a->FirstName < $b->FirstName) ? $sd : -$sd;
                break;      
            case 'g': // grade code
                if ($a->GradeCode!=$b->GradeCode)
                    return ($a->GradeCode < $b->GradeCode) ? $sd : -$sd;
                break;      
            case 'gg': // grade group
                $ag = $a->getGradeGroupCode(); 
                $bg = $b->getGradeGroupCode(); 
                if ($ag!=$bg)
                    return ($ag < $bg) ? $sd : -$sd;
                break;      
            case 'u':  // pickup code
                if ($a->PickupCode!=$b->PickupCode)
                    return ($a->PickupCode < $b->PickupCode) ? $sd : -$sd;
                break;      
            case 'kg':  // kid group
                if ($a->KcmAssignedGroup!=$b->KcmAssignedGroup)
                    return ($a->KcmAssignedGroup < $b->KcmAssignedGroup) ? $sd : -$sd;
                break;      
            case 't':  // tournament result
                $ap = $this->sortTournPercent[$ai];
                $bp = $this->sortTournPercent[$bi];
                if ($ap!=$bp)
                    return ($ap < $bp) ? $sd : -$sd;
                break;      
            case 'ptc':  // class points
                //$ap = $this->sortClassPointsArray[$ai];
                //$bp = $this->sortClassPointsArray[$bi];
                //if ($ap!=$bp)
                //    return ($ap < $bp) ? $sd : -$sd;
                break;      
            case 'ptt':  // Total points
                if ($a->totalPoints!=$b->totalPoints)  //###???????
                  return ($a->totalPoints < $b->totalPoints) ? $sd : -$sd;  //###??????????
                break;      
        }       
    }
    return 0;
}

}


