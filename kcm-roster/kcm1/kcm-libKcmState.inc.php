<?php

// kcm-libKcmState.inc.php

// $kcmState->ksProgramId;
// $kcmState->ksPeriodId;
//$kcmState->ksCatchBadProgramId();
//$kcmState->ksCatchBadPeriodId();



Class kcm_kcmState {  // can be url-Query-String, hidden field, or posted data
public $Id;
private $argKey;  // array of keys
private $argValue;  // array of values that go with each key
private $isPost;
public $ksProgramId;
public $ksPeriodId;
public $ksEntryDateSql;
//public $ksDateEntrySql;
//public $ksDateEntryDesc;
//public $ksDateClassSql;
//public $ksDateClassDesc;
//public $ksDateTimeReportSql;
//public $ksDateTimeReportDesc;

function __construct() {
    $this->Id = 'kcmp';
    $this->argTempId = array(20);
    $this->argTempVal = array(20);
    $this->isPost = ($_SERVER['REQUEST_METHOD'] === 'POST'); 
    $this->ksImport();
}
private function ksImport() {
    $this->argKey = array();
    $this->argValue = array();
    if (isset($_GET['kcmp'])) 
        $param = $_GET['kcmp'];
    else if (isset($_POST['kcmp'])) 
        $param = $_POST['kcmp'];
    else
        $param = '';    
    if ($param!='')    
        $this->ksConvertFromString($param);    
    $this->ksProgramId  = $this->getState('PrId',0);
    $this->ksPeriodId   = $this->getState('PeId',0);
    $this->ksEntryDateSql   = $this->getState('ClDate',NULL);
}
private function ksConvertFromString($pString) {
    $pieces = explode("_", $pString);  //first element is before first _
    $cnt = count($pieces);
    for ($i=1; $i<$cnt; $i++) {
       $s = $pieces[$i];
       $sep = strpos ( $s, '-');
       $id = substr ( $s, 0, $sep);
       $val = substr ( $s, $sep+1);
       $this->ksSetArg($id,$val);
    }
}
function ksConvertToString() {
    $s = '';
    $argCount = count($this->argKey);
    for ($i = 0; $i<$argCount; $i++) {
       if ($this->argKey[$i]!='' and $this->argValue[$i]!='') 
           $s = $s .'_'.$this->argKey[$i].'-'.$this->argValue[$i];
    }
    return $s;
}
private function getArgIndex($pArgKey) {
    $argCount = count($this->argKey);
    for ($i = 0; $i<$argCount; $i++) {
       if ($this->argKey[$i]==$pArgKey)
           return $i;
    }
    return -1;
}
function ksCatchBadProgramId() {
    if ($this->ksProgramId==0) 
        rc_redirectToURL('kcm.php', NULL, true);
}        
function ksCatchBadPeriodId() {
    if ($this->ksPeriodId==0) 
        rc_redirectToURL('kcm.php', NULL, true);
}        

function convertToUrl($pPageFile, $pArg=NULL, $pValue=NULL) {
    if (strpos($pPageFile,'?')===FALSE)
        $s = $pPageFile . '?'. $this->Id. '=';
    else    
        $s = $pPageFile . '&'. $this->Id. '=';
    $argCount = count($this->argKey);
    for ($i = 0; $i<$argCount; $i++) {
       if ($this->argKey[$i]!='' and $this->argValue[$i]!='') 
           $s = $s .'_'.$this->argKey[$i].'-'.$this->argValue[$i];
    }
    if ($pArg!=NULL) {
        if (is_array($pArg)) {
            for ($i = 0; $i<count($pArg); $i=$i+2) 
                $s = $s . '&'. $pArg[$i] . '='.$pArg[$i+1];
        }
        else
            $s = $s . '&'.$pArg.'='.$pValue;
    }    
    return $s;
}
function convertToHidden() {
    return '<input type="hidden" name="kcmp" value="'.$this->ksConvertToString().'">';
}
function getState($pArgKey,$pArgDefault=NULL) {
    $param = NULL;
    $idx = $this->getArgIndex($pArgKey);
    if (isset($_GET[$pArgKey])) 
        $param= $_GET[$pArgKey];
    else if (isset($_POST[$pArgKey])) 
        $param= $_POST[$pArgKey];
    if ($param!=NULL) { // save new value
        if ($idx>=0) 
            $this->argValue[$idx] = $param;
        else if ($param!=='')   
            $idx = $this->ksSetArg($pArgKey,$param);       
    }
    if ($param==NULL and $idx>=0) 
        $param = $this->argValue[$idx];
    if ($param==NULL) 
        return $pArgDefault;
    return $param;
}
public function ksClearMost() {
    //$this->ksEntryDateSql = NULL;
    //$this->ksClearArg('ClDate');
    //return;
    $keepKey = array();
    $keepVal = array();
    $argCount = count($this->argKey);
    for ($i = 0; $i<$argCount; $i++) {
        $s = $this->argKey[$i];
        if ( ($s=='PrId') or  ($s=='PeId') or ($s=='SemCombo') or ($s=='Mode') ){
            $keepKey[] = $this->argKey[$i];
            $keepVal[] = $this->argValue[$i];
        }        
    }    
    $this->argKey = $keepKey;
    $this->argValue = $keepVal;
}
//private function ksClearArg($pArgKey) {
//    $idx = $this->getArgIndex($pArgKey);
//    if ($idx>=0) {
//        unset($this->argKey[$idx]);
//        unset($this->argValue[$idx]);
//    }
//}
function ksSetArg($pArgKey,$pArgValue) {
    $idx = $this->getArgIndex($pArgKey);
    if ($idx>=0)
        $this->argValue[$idx] =$pArgValue;
    else    
        {
        $this->argKey[] =$pArgKey;
        $this->argValue[] =$pArgValue;
        $idx = count($this->argValue)-1;
        }
    return $idx;    
}

}

//%%%%%%%%%%%%%%%%%%%%%%%%%%%??????????????????????????? not in use yet ????????????

class kcm_ReportOptions {
public $optionsId = array();
public $optionsValue = array();
public $optionsCount = 0;
public $reportKey;

function __construct($pReportKey) {
    $this->reportKey = $pReportKey;
}

function add($pOptionId, $pDefaultValue) {
    $this->optionsId[] = $pOptionId;
    $this->optionsValue[] = $pDefaultValue;
    ++$this->optionsCount;
}

function read() {
    if ($staffId===NULL)
        $staffId = rc_getStaffId();
    $this->db = rc_getGlobalDatabaseObject();
    $sql = array();
    $sql[] = "SELECT *";
    $sql[] = "FROM `op:reportoptions`";
    $sql[] = "WHERE `oRO:ReportKey`= '{$this->reportKey}' AND `oRO:@StaffId`= '{$staffId}'";
    $query = implode( $sql, ' ');
    $this->dbResult = $this->db->rc_query( $query );
    if ($this->dbResult === FALSE) {
        rc2_dbError( __FILE__,__LINE__);
        exit;
    }
    if ($this->dbResult->num_rows == 0) {
        $this->isFromDb = FALSE;
        return FALSE;
    }
    else {
        $this->isFromDb = TRUE;
        $row=$this->dbResult->fetch_array();
        $this->staffId= $row['oRO:@StaffId'];
        $optionIds = explode('[',$row['oRO:ImplodedIds']);
        $optionValues = explode('[',$row['oRO:ImplodedValues']);
        $optionCount = min(count($optionIds),count($optionValues)); // should be the same
        for ($i = 0; $i<$optionCount; ++$i) {
            $id = $optionIds[$i];
            $val = $optionValues[$i];
            for ($j=0; $j < $this->optionsCount; ++$j) {
                if ($this->optionsId[$j] == $id) {
                    $this->optionsValue[$j] = $val;
                }
            }
        }    
        return TRUE;
    }    
}

function save() {
    $staffId = rc_getStaffId();
    $this->db = rc_getGlobalDatabaseObject();
    $sql = array();
    $sql[] = 'REPLACE `op:reportoptions`';
    $sql[] = "SET";
    $sql[] = "`oRO:ReportKey` = '".$this->reportKey."'";
    $sql[] = ",`oRO:@StaffId` = '".$staffId."'";
    $sql[] = ",`oRO:ImplodedIds` = '".implode("[",$this->optionsId)."'";
    $sql[] = ",`oRO:ImplodedValues` = '".implode("[",$this->optionsValue)."'";
    $query = implode( $sql, ' ');
    $this->db = rc_getGlobalDatabaseObject();
    $result = $this->db->rc_query($query );
    if ($result === FALSE) {
        rc2_dbError( __FILE__,$lineNum);
        exit;
    }    
    
    
}

}
?>