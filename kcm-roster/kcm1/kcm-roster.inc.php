<?php

// kcm-roster.inc.php

//define('kcmMAX_POINT_CATEGORIES',12); //@JPR-2019-11-04 20:36 

//define('kcmGAME_CHESS_INDEX',0);//@JPR-2019-11-04 20:36 
//define('kcmGAME_BLITZ_INDEX',1);//@JPR-2019-11-04 20:36 
//define('kcmGAME_BUGHOUSE_INDEX',2);//@JPR-2019-11-04 20:36 

define('kcmLOAD_ROSTER'  , 1);

//======
//============
//==================
//========================
//=   Roster
//========================
//==================
//============
//======

Class kcm_roster {

private $db;

public $school;
public $program;
public $schedule;
public $periodArray;  // always read all periods so can switch periods 
public $periodCount;
public $pointCats; // point categories read as parent of roster, even though it's in the program table

public $kidArray;
public $kidCount;

//--- should almost never be used outside of this object
public $unfilteredKidArray;  // one element per kid-period - all kid-periods
public $unfilteredKidCount;

private $loadPeriodLunchId;    // feature period ID for lunch
private $loadModeBits;
private $loadProgramId;
private $loadPeriodId;
private $loadFamilyInfo;

private $sortOrder;  // the sort order of kid $sortOrder[$kidlist Index] is sort order (for cross-tables, etc)
private $sortCodeArray;
private $sortDirecArray;
private $sortGameTypeIndex;  // NULL if none - // to pre-compute sort for section
private $sortTournPercent;  // used by usort
private $sortClassPointsArray;  // used by usort
private $sortClassPointsActive; // used by usort


function __construct($pProgramId, $pPeriodId=0, $pLoadFamilyInfo=FALSE) {
$this->loadProgramId = $pProgramId;
$this->loadPeriodId = $pPeriodId;  // NULL for all periods
$this->loadFamilyInfo = $pLoadFamilyInfo;
$this->loadModeBits = 0;
$this->db = rc_getGlobalDatabaseObject();
$this->loadPeriodLunchId = 0;
$this->program = new kcm_roster_program();
$this->school = new kcm_roster_school();
$this->pointCats = new kcm_pointCategories();
$this->periodArray = array();
$this->periodCount = 0;
kcm_roster_kid::$roster = $this;
}

public function getKidByKidPeriodId($pId) {
    //--- roster does not need to be filtered as only one result is possible
    for ($i = 0; $i<$this->unfilteredKidCount; ++$i) {
        $curKid = $this->unfilteredKidArray[$i];
        if ($curKid->per->KidPeriodId == $pId) {
            return $curKid;
        }
    }
    return NULL; 
}

public function getKidByKidProgramId($pId) {
    //--- roster needs to be filtered for only one period before using this function
    for ($i = 0; $i<$this->kidCount; ++$i) {
        $curKid = $this->kidArray[$i];
        if ($curKid->prg->KidProgramId == $pId) {
            return $curKid;
        }
    }
    return NULL; 
}

public function getKidByKidId($pKidId) {
    //--- roster needs to be filtered for only one period before using this function
    for ($i = 0; $i<$this->kidCount; ++$i) {
        $curKid = $this->kidArray[$i];
        if ($curKid->prg->KidId == $pKidId) {
            return $curKid;
        }
    }
    return NULL; 
}

function getPeriodFromPeriodId($pPeriodId) {
    for ($i = 0; $i<$this->periodCount; $i++) {
        if ($this->periodArray[$i]->PeriodId==$pPeriodId)
            return $this->periodArray[$i];
    }        
    return NULL;        
}
        

function load_object_program() {
    $fieldArray = array();
    $this->program->db_setFieldArray_program($fieldArray);
    $fieldList = "`".implode("`,`",$fieldArray)."`";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
    $sql[] = "FROM `pr:program`";
    $sql[] ="WHERE `pPr:ProgramId` ='".$this->loadProgramId."'";
    $query = implode( $sql, ' '); 
    $result = $this->db->rc_query( $query );
    if (($result === FALSE) || ($result->num_rows == 0)) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    $row=$result->fetch_array(); 
    $this->program->db_loadRow_program($row,$this->loadModeBits);
    $catString = $row['pPr:KcmPointCategories'];
    $this->pointCats->ptcConvertFromString($catString);
    if ($catString == '') {
        $this->pointCats->ptcWriteData($this->db,$this->program->ProgramId);
        //$this->program->ProgramName = $this->program->ProgramName . '.'; //????? temporary -f or Jared's Debugging
    }

}

function load_object_school() {
    $fieldArray = array();
    $this->school-> db_setFieldArray_school($fieldArray);
    $fieldList = "`".implode("`,`",$fieldArray)."`";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
    $sql[] = "FROM `pr:school`";
    $sql[] = "WHERE `pSc:SchoolId` ='".$this->program->AtSchoolId."'";
    $query = implode( $sql, ' '); 
    $result = $this->db->rc_query( $query );
    if (($result === FALSE) || ($result->num_rows == 0)) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    $row=$result->fetch_array(); 
    $this->school->db_loadRow_school($row);
}

function load_object_periodArray() {
    //--- Always read all periods so navigation can switch periods 
    $this->periodArray = array();
    $this->periodCount = 0;
    $fieldArray = array();
    kcm_roster_period:: db_setFieldArray_period($fieldArray,$this->loadModeBits);
    $fieldList = "`".implode("`,`",$fieldArray)."`";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
    $sql[] = "FROM `pr:period`";
    $sql[] = "WHERE `pPe:@ProgramId` ='".$this->loadProgramId."'";
    $sql[] = "ORDER BY `pPe:PeriodSequenceBits`";
    $query = implode( $sql, ' '); 
    $result = $this->db->rc_query( $query );
    if (($result === FALSE) || ($result->num_rows == 0)) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    while($row=$result->fetch_array()) {
        if ($row['pPe:PeriodSequenceBits'] == 4096) {
            $this->loadPeriodLunchId = $row['pPe:PeriodId'];
        }
        else {
            $newPeriod = new kcm_roster_period;
            $newPeriod->db_loadRow_period($row);
            $this->periodArray[] = $newPeriod;
            ++$this->periodCount;
        }    
    }
}

function load_gameTotals($pGameType = NULL) {
    //???? may be faster/better to do all of this as an optional join in the kid-period query
    for ($i = 0; $i<$this->unfilteredKidCount; ++$i) {
        $curKid = $this->unfilteredKidArray[$i];
        if ($curKid != NULL) { // rare but kidperiod record can be hidden
            $curKid->per->KcmGamePoints = 0;
        }    
    }    
    $fieldList = "*";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
    $sql[] = "FROM `gp:gametotals`";
    if ($this->loadPeriodId >= 1) {
        $sql[] = "WHERE  `gpGT:@PeriodId` ='" . $this->loadPeriodId."'";
    }    
    else {   
        $sql[] = "WHERE `gpGT:@ProgramId` ='" . $this->loadProgramId . "'";
    }    
    if ($pGameType !== NULL) {
        $sql[] = "   AND `gpGT:GameTypeIndex` ='".$pGameType."'";
    }
    $query = implode( $sql, ' '); 
    $result = $this->db->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    while($row = $result->fetch_array()) {
        $kidPeriodId = $row['gpGT:@KidPeriodId'];
        $kid = $this->getKidByKidPeriodId($kidPeriodId);
        if ($kid != NULL) { // rare but kidperiod record can be hidden
            $gameTypeIndex = $row['gpGT:GameTypeIndex'];
            $curTotals = $kid->ttt[$gameTypeIndex];
            $curTotals->db_readRow_gameTotals($row);
            switch ($gameTypeIndex) {
                case 0: $kid->per->KcmGamePoints += $curTotals->totWon * 10 + $curTotals->totDraw * 5;
                        break;
                case 1: $kid->per->KcmGamePoints += $curTotals->totWon * 5 + $curTotals->totDraw * 3;
                        break;
                case 2: $kid->per->KcmGamePoints += $curTotals->totWon * 3;
                        break;
            }
        }
    }
}

function load_roster_header() {
    $this->load_object_program();
    $this->load_object_school(); 
    $this->program->ProgramName = $this->school->NameShort;
    if ($this->program->SchoolNameUniquifier != '') {
        $this->program->ProgramName .= ' ' . $this->program->SchoolNameUniquifier; 
    }    
    $this->load_object_periodArray(); 
    $this->schedule = new kcm_roster_schedule($this->db,$this);
}
function load_roster_headerAndKids() {
    $this->load_roster_header(); 
    // a few pages need to do special things here so
    // separating into these two function calls allows this    
    $this->load_roster_kids();
}

function load_roster_kids($pMode='') {   // w=wait list only
    $fieldArray = array();
    kcm_roster_kidProgram:: db_setFieldArray_kidProgram($fieldArray);   //also includes kidProgram
    kcm_roster_kidPeriod:: db_setFieldArray_kidPeriod($fieldArray,$this->loadModeBits);
    if ($this->loadFamilyInfo) {
        kcm_roster_family:: db_setFieldArray_family($fieldArray);   //???????????
        kcm_roster_parent:: db_setFieldArray_parent($fieldArray);   //???????????
    }    
    if ($pMode == 'w') {
        $fieldArray[] = 'fPI:PurchaseItemId';
        $fieldArray[] = 'fPI:@PurchaseId';
        $fieldArray[] = 'fPu:PurchaseId';
        $fieldArray[] = 'fPu:CreationDateTime';
    }
    if ($pMode == 'p') {
        $fieldArray[] = 'kKS:Rating';
        $fieldArray[] = 'kKS:PuzzlesAttempted';
        $fieldArray[] = 'kKS:PuzzlesSolved';
    }
    $fieldArray[] = 'pPe:PeriodSequenceBits'; // jpr 2017-06-01
    $fieldList = "`" . implode("`,`", $fieldArray) . "`";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
    $sql[] = "FROM `ro:kid_period`";
    $sql[] = "INNER JOIN `ro:kid` ON `rKd:KidId` = `rKPe:@KidId`";
    if ($this->loadFamilyInfo) {
        $sql[] = "INNER JOIN `ro:family` ON `rFa:FamilyId` = `rKd:@FamilyId`";
        $sql[] = "INNER JOIN `ro:parentalunit` ON `rPU:@FamilyId` = `rFa:FamilyId`";
    }    
    $sql[] = "INNER JOIN `pr:period` ON `pPe:PeriodId` = `rKPe:@PeriodId`";
    if ($pMode == 'p') {
        $sql[] = "LEFT JOIN `kp:kidstats` ON `kKS:@KidId` = `rKd:KidId`";
    }
    if ($pMode == 'w') {
        $sql[] = "INNER JOIN `fi:purchaseitem` ON `fPI:PurchaseItemId` = `rKPe:@PurchaseItemId`";
        $sql[] = "INNER JOIN `fi:purchase` ON `fPu:PurchaseId` = `fPI:@PurchaseId`";
    }
    $sql[] = "LEFT JOIN `ro:kid_program` ON ( `rKPr:@KidId` = `rKPe:@KidId` )";
    $sql[] = "AND (`rKPr:@ProgramId` = `pPe:@ProgramId`)";
    //if ($this->loadPeriodId >= 1) {
    //    $sql[] = "WHERE `rKPe:@PeriodId` ='".$this->loadPeriodId."'";
    //}    
    //else {   
        $sql[] = "WHERE `pPe:@ProgramId` ='".$this->loadProgramId."'";
    //}
    $sql[] = "AND `rKPe:HiddenStatus` = '0'";
    if ($pMode == 'w') {
        $sql[] = "AND `rKPe:InactiveStatus` = '1'";
    }
    else {
        $sql[] = "AND `rKPe:InactiveStatus` = '0'";
    }    
    $sql[] = "ORDER BY `rKPr:KidProgramId`, `pPe:PeriodSequenceBits`, `rKPe:KidPeriodId`";  // jpr 2017-06-01
    $query = implode( $sql, ' '); 
    //echo  '<hr>' , $query, '<hr>';
    $result = $this->db->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    $curKidProgramId = 0;
    $curKidProgram   = NULL;
    $curKidPeriodId  = 0;
    $curKidPeriod    = NULL;
    $saveProgram = FALSE;
    while($row=$result->fetch_array()) {
        $newKidProgramId = $row['rKPr:KidProgramId']; //????? must be same as order
        $newKidPeriodId = $row['rKPe:KidPeriodId'];
        $curPeriod = $row['rKPe:@PeriodId'];
        if ( ($curPeriod == $this->loadPeriodLunchId) 
           and ($row['rKd:KidId']==$curKidProgram->KidId) ) {
                $curKidProgram->Lunch = TRUE;

        }
        else {
            if ($newKidProgramId != $curKidProgramId) {
                $curKidProgramId = $newKidProgramId;
                $curKidProgram = new kcm_roster_kidProgram;
                $curKidProgram->db_loadRow_kidProgram($row,$this->loadModeBits);
                $saveProgram = TRUE;
                if ( stripos($curKidProgram->Notes,'*LUNCH') !== FALSE )
                    $curKidProgram->Lunch = TRUE;
                    
            }    
            if ($this->loadFamilyInfo) {
                $parentPriority = $row['rPU:ContactPriority'];
                if ($curKidProgram->family == NULL) {
                    $curKidProgram->family = new kcm_roster_family;
                    $curKidProgram->family->db_loadRow_family($row,$this->loadModeBits);
                }    
                if (($parentPriority==0) and ($curKidProgram->parent1 == NULL) ) {
                    $curKidProgram->parent1 = new kcm_roster_parent;
                    $curKidProgram->parent1->db_loadRow_parent($row,$this->loadModeBits);
                }    
                if (($parentPriority==1) and ($curKidProgram->parent2 == NULL) ) {
                    $curKidProgram->parent2 = new kcm_roster_parent;
                    $curKidProgram->parent2->db_loadRow_parent($row,$this->loadModeBits);
                }    
            }    
            if ($newKidPeriodId != $curKidPeriodId) {
                $curKidPeriodId = $newKidPeriodId;
                $curKid = new kcm_roster_kid($curKidProgram);
                $curKid->per->db_loadRow_kidPeriod($row,$curKidProgram,$this->loadModeBits);
                if ($pMode == 'w') {
                    $curKid->per->InvoiceNo   = 'KC-' . $row['fPu:PurchaseId'];
                    $curKid->per->InvoiceDate = $row['fPu:CreationDateTime'];
                }
                if ($pMode == 'p') {
                    $curKid->prg->kpRating    = $row['kKS:Rating'];
                    $curKid->prg->kpAttempted = $row['kKS:PuzzlesAttempted'];
                    $curKid->prg->kpSolved    = $row['kKS:PuzzlesSolved'];
                }
                $this->unfilteredKidArray[] = $curKid;
                ++$this->unfilteredKidCount;
                if ($saveProgram) {
                    $saveProgram = FALSE;
                }
                $newPeriodId = $curKid->per->AtPeriodId;
                for ($i = 0; $i<$this->periodCount; ++$i) {
                    $curPeriod = $this->periodArray[$i];
                    if ( $curPeriod->PeriodId == $newPeriodId) {
                        $curKid->per->period = $curPeriod;
                        $curKid->per->program = $this->program;
                        $curKid->per->PeriodBitsSinglePeriod = $curPeriod->PeriodSequenceBits;
                        if ($curKid->prg->PeriodComboEarliest == 0)
                            $curKid->prg->PeriodComboEarliest = $curPeriod->PeriodSequenceBits;
                        else    
                            $curKid->prg->PeriodComboEarliest = min($curKid->prg->PeriodComboEarliest,$curPeriod->PeriodSequenceBits);
                            ++$curPeriod->kidThisPeriodCount;
                        $curKid->prg->PeriodComboAllBits = $curKid->prg->PeriodComboAllBits 
                                   | $curKid->per->PeriodBitsSinglePeriod;
                        break;
                    }
                }
            }    
        }    
    }
    $this->afterLoad_updates();
}

function afterLoad_updateNames() {
    //--- check for two kids with same or similar names
    //--- maybe can change code to check whether name confict is in different period
    //---   ... and if so have different symbol to indicate this 
    for ($i1=0; $i1<$this->unfilteredKidCount; $i1++) {
        $k1 = $this->unfilteredKidArray[$i1];
        for ($i2=$i1+1; $i2<$this->unfilteredKidCount; $i2++) {
            $k2 = $this->unfilteredKidArray[$i2];
            if ($k1->prg->KidId != $k2->prg->KidId) {
                if (metaphone($k1->prg->FirstName)==metaphone($k2->prg->FirstName)) { 
                     $match = 1;
                     $n1 = strtolower ($k1->prg->FirstName);
                     $n2 = strtolower ($k2->prg->FirstName);
                     $dif = levenshtein($n1,$n2);  // the number of characters you have to replace, insert or delete to transform str1 into str2   
                     if ($dif==0) 
                         $match = 9;
                     else if ($dif==1) 
                         $match = 7;
                     else if ($dif==2) 
                         $match = 5;
                     else if ($dif==3) 
                         $match = 3;
                     $k1->NameConflict=$match;
                     $k2->NameConflict=$match;
                     if (strpos($k1->prg->FirstName,'-->')==0)
                         $k1->prg->FirstName = $k1->prg->FirstName . '-->';
                     if (strpos($k2->prg->FirstName,'-->')==0)
                         $k2->prg->FirstName = $k2->prg->FirstName . '-->';
                }    
            }
        }    
    }
}

function afterLoad_updateSubGroups() {
    for ($i = 0; $i<$this->unfilteredKidCount; $i++) {
        $kid = $this->unfilteredKidArray[$i];
        if ($kid->per->KcmClassSubGroup != '') {
            $kid->per->period->kidSubGroupsActive = TRUE;
        }    
    }    
}

function afterLoad_updatePeriods() {
    for ($i = 0; $i<$this->unfilteredKidCount; $i++) {
        $kid = $this->unfilteredKidArray[$i];
        switch ($kid->prg->PeriodComboAllBits) {
           case 1: $per = '1';  break;
           case 2: $per = '4';  break;
           case 3: $per = '2';  break;
           case 4: $per = '5';  break;
           case 5: $per = '3';  break;
           case 6: $per = '6';  break;
           default: $per=2;  //????
        }   
        $kid->prg->PeriodComboSortCode = $per;
        $this->afterLoad_updateSubGroups();
    }
}
function afterLoad_updatePoints() {
    for ($i = 0; $i<$this->unfilteredKidCount; $i++) {
        $kid = $this->unfilteredKidArray[$i];
        $per = $kid->per;
        $prg = $kid->prg;
        $per->totalCatPoints = array_sum($per->KcmPerPointValues) + array_sum($prg->KcmPrgPointValues);
        $per->totalAllPoints = $per->totalCatPoints + $per->KcmGamePoints;
    }    
}
function afterLoad_updates() {
    $this->afterLoad_updateNames();
    $this->afterLoad_updatePeriods();
    $this->afterLoad_updatePoints();
}

//%%%%%%%%%%%%%%%%%%%%%%%%%%%????????????????????????  review sort/filter logic - can it be simplified

function sort_periodFilter($pPeriodId, $onePerKid=FALSE) {
    $this->kidArray = array();
    $this->kidCount = 0;
    for ($i = 0; $i < $this->unfilteredKidCount; ++$i) {
       $kid = $this->unfilteredKidArray[$i];
       if ($pPeriodId != 0) {
            if ($kid->per->AtPeriodId == $pPeriodId) {
                $this->kidArray[] = $kid;
            }    
       }
       else if ($onePerKid) {
            if ($kid->per->PeriodBitsSinglePeriod == $kid->prg->PeriodComboEarliest) {
                $this->kidArray[] = $kid;
            }    
       }
       else {
            $this->kidArray[] = $kid;
       }
    }
    $this->kidCount = count($this->kidArray);
}

public function getPointCategoryObject($pCategoryIndex) {
    return $this->program->KcmPointCategories->ptcAllItems[$pCategoryIndex];
}

function sort_Start() {
    $this->sortGameTypeIndex = NULL;
    $this->sortClassPointsActive = FALSE;
    $this->sortGameTypeIndex = NULL;
    $this->sortCodeArray = array ();
    $this->sortDirecArray = array ();
}

function sort_byFirstName($pDirec='u') {
    $this->sort_addField('n',$pDirec);
}
function sort_byClassSubGroup($pDirec='u') {  // sort within filter
    $this->sort_addField('kg',$pDirec);
}
function sort_byPeriodName($pDirec='u') {  // sort within filter
    $this->sort_addField('pn',$pDirec);
}
function sort_byPeriodCurrent($pDirec='u') {  // sort within filter
    $this->sort_addField('pc',$pDirec);
}
function sort_byPeriodEarliest($pDirec='u') {  // sort within filter
    $this->sort_addField('pe',$pDirec);
}
function sort_byPeriodGroup($pDirec='u') {  // sort within filter
    $this->sort_addField('pg',$pDirec);
}
function sort_byLastName($pDirec='u') {
    $this->sort_addField('L',$pDirec);
}
function sort_byGrade($pDirec='u') {
    $this->sort_addField('g',$pDirec);
}
function sort_byPhotoReleaseStatus($pDirec='u') {
    $this->sort_addField('prs',$pDirec);
}
function sort_byRookie($pDirec='u') {
    $this->sort_addField('rk',$pDirec);
}
function sort_byGradeGroup($pDirec='u') {
    $this->sort_addField('gg',$pDirec);
}
function sort_byTournament($pGameTypeIndex, $pDirec='u') {
    $this->sort_addField('t',$pDirec);
    $this->sortGameTypeIndex = $pGameTypeIndex;
}
function sort_byClassPoints($pDirec='u') {
    $this->sort_addField('ptc',$pDirec);
    $this->sortClassPointsActive = TRUE;
}
function sort_byTotalPoints($pDirec='u') {
    $this->sort_addField('ptt',$pDirec);
}

function sort_byPickup($pDirec='u') {
    $this->sort_addField('u',$pDirec);
}

private function sort_addField($pCode, $pDirec) {
    $this->sortCodeArray[] = $pCode;
    if ($pDirec===TRUE or $pDirec==='c')
        $this->sortDirecArray[] = -1;
    else
        $this->sortDirecArray[] = 1;
}
function sort_end() {
    usort ( $this->kidArray, array($this,'sort_compare') );
    for ($i = 0; $i<$this->kidCount; $i++) {
        $this->kidArray[$i]->per->sortIndex = $i; 
    }
}

private function sort_compare($aKid, $bKid) {
    if ($aKid == $bKid) 
        return 0;
    $cnt = count($this->sortCodeArray);
    for ($i = 0; $i<$cnt; $i++) {
        $sc = $this->sortCodeArray[$i];
        $sd = $this->sortDirecArray[$i];
        switch ($sc) {
            case 'pe': // earliest period
                $aVal = $aKid->prg->PeriodComboEarliest;
                $bVal = $bKid->prg->PeriodComboEarliest;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'pc': // single (current) period
                $aVal = $aKid->per->PeriodBitsSinglePeriod;
                $bVal = $bKid->per->PeriodBitsSinglePeriod;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'pg': // period group
                $aVal = $aKid->prg->PeriodComboSortCode;
                $bVal = $bKid->prg->PeriodComboSortCode;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'pn': // period name (same as combined bits)
                $aVal = $aKid->prg->PeriodComboAllBits;
                $bVal = $bKid->prg->PeriodComboAllBits;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'n':  // first name
                $aVal = $aKid->prg->FirstName;
                $bVal = $bKid->prg->FirstName;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                $aVal = $aKid->prg->LastName;
                $bVal = $bKid->prg->LastName;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'rk':  // rookie/veteran
                $aVal =kcmAsString_Rookie($aKid, $this);
                $bVal =kcmAsString_Rookie($bKid, $this);
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'L':  // last name
                $aVal = $aKid->prg->LastName;
                $bVal = $bKid->prg->LastName;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                $aVal = $aKid->prg->FirstName;
                $bVal = $bKid->prg->FirstName;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'g': // grade code
                $aVal = $aKid->prg->GradeCode;
                $bVal = $bKid->prg->GradeCode;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'gg': // grade group
                $aVal = $aKid->per->getGradeGroupCode(); 
                $bVal = $bKid->per->getGradeGroupCode(); 
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'u':  // pickup code
                $aVal = $aKid->prg->PickupCode;
                $bVal = $bKid->prg->PickupCode;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'kg':  // kid group
                $aVal = $aKid->per->KcmClassSubGroup;
                $bVal = $bKid->per->KcmClassSubGroup;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 't':  // tournament result
                $aVal = $aKid->ttt[$this->sortGameTypeIndex]->Percent;
                $bVal = $bKid->ttt[$this->sortGameTypeIndex]->Percent;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'ptc':  // class points
                $aVal = $aKid->per->totalCatPoints;
                $bVal = $bKid->per->totalCatPoints;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'ptt':  // Total points
                $aVal = $aKid->per->totalAllPoints;
                $bVal = $bKid->per->totalAllPoints;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
            case 'prs':  // PhotoReleaseStatus
                $aVal = $aKid->prg->PhotoReleaseStatus;
                $bVal = $bKid->prg->PhotoReleaseStatus;
                if ( $aVal != $bVal )
                    return ($aVal < $bVal) ? $sd : -$sd;
                break;      
         }       
   }
    return 0;
}

        
}

?>