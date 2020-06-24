<?php
// kcm-page-ColumnDef.inc.php
class kcm_ColumnDef {
private $paramKcmState;
private $paramPageUrl;
public $kcmStateKey;
private $colType;
private $colSubType;  // as for check box - subtype 'f'=freeze checkbox
private $colValue;
private $colVal2;
private $colCheckbox;   // true/false (not part of argument)
private $colEnabled;    // c=yes 
private $colSortable;   // c=yes
private $colSortDirec;  // c=checked (low to high)
private $colFreezable; // c=yes
private $colFreezeState;  
private $colLastIndex;
private $colParentIndex;  
public $curSortColumn;
public $curFreezeColumn;

function __construct($pKcmStateKey,$pMaxColumns,$pkcmState,$pPageUrl) {
    $this->paramPageUrl = $pPageUrl;
    $this->paramKcmState = $pkcmState;
    $this->kcmStateKey = $pKcmStateKey;
    $this->curSortColumn = 1;  // default
    $this->curFreezeColumn = 0;  // default of none
    $this->colLastIndex = 1;
    $pMaxColumns++;  // 1 based, not zero base 
    $this->colType = array($pMaxColumns);
    $this->colValue = array($pMaxColumns);
    $this->colCheckBox = array($pMaxColumns);
    $this->colEnabled = array($pMaxColumns);
    $this->colFreezable = array($pMaxColumns);
    $this->colFreezeState = array($pMaxColumns);
    $this->colSortable = array($pMaxColumns);
    $this->colSortDirec = array($pMaxColumns);
    $this->colParentIndex = array($pMaxColumns);
    for ($i = 0; $i<=$pMaxColumns; $i++) {
        $this->colType[$i] = NULL;  
        $this->colValue[$i] = 'x';  // cannot be null so explode/implode will work
        $this->colCheckbox [$i] = FALSE;  
        $this->colEnabled[$i] = 'c';  
        $this->colSortable[$i] = 'u';  
        $this->colSortDirec[$i] = 'c';  
        $this->colFreezable[$i] = FALSE;  
        $this->colFreezeState[$i] = 'x';  
        $this->colParentIndex[$i] = $i;  
    }
}
function initColumn($pDefine, $pColumn, $pOp, $pEn, $pSe, $pDi, $pFe=FALSE, $pFs=FALSE) {  
    $pSe = FALSE;  // ?? features not supported in kcm2
    $pDi = TRUE;
    $peFe = FALSE;
    $peFs = FALSE;
    $space = strpos($pDefine,' ');
    if ($space>=1) {
       $parentIdx = constant(substr($pDefine,$space+1));
       $pDefine = substr($pDefine,0,$space);
    }
    else
       $parentIdx = $pColumn;
    $this->colParentIndex[$pColumn]  = $parentIdx; 
    define($pDefine, $pColumn);  
    $this->colCheckbox[$pColumn]  = $pOp ? TRUE : FALSE; 
    $this->colEnabled[$pColumn]  = $pEn ? 'c' : 'u'; 
    $this->colSortable[$pColumn] = $pSe ? 'c' : 'u'; 
    $this->colSortDirec[$pColumn] = $pDi ? 'c' : 'u'; 
    $this->colFreezable[$pColumn] = $pFe ? TRUE : FALSE; 
    $this->colFreezeState[$pColumn] = $pFs ? 'c' : 'u'; 
    $this->colLastIndex = max($this->colLastIndex,$pColumn);    
}    
function initFinalize($pSortCol = 0) {  
return; //@JPR-2019-11-04 20:52 
    //--- get kcmstate param - use defaults if none
    $optionsParam = $this->paramKcmState->getState($this->kcmStateKey,NULL);
    $this->curSortColumn = $pSortCol;    // params will override this
    if ($optionsParam!=NULL) 
         $this->convertFromString($optionsParam);
    //--- get submitted values which take priority    
    $submit = kcm_getParam('Submit',NULL);
    if ($submit==='OptSubmit') {   
        for ($i=1; $i<=$this->colLastIndex; $i++)
             if ($this->colCheckbox[$i]) {       
                 $this->colEnabled[$i] = 'u';
             }    
    }             
    $parLen = strlen($this->kcmStateKey);
    foreach($_GET as $name => $value) {
        //print "$name : $value<br>";
        $nam = "$name";
        $val = "$value";
        if (substr($nam,0,$parLen)==$this->kcmStateKey) {
            $key = substr($nam,$parLen);
            if ($key>100 and $key<199) 
                $this->colEnabled[$key-100] = $val;
            else if ($key>200 and $key<299) {
                $this->colSortDirec[$key-200] = $val;
                $this->curSortColumn = $key-200;
            }    
            else if ($key>300 and $key<399) 
                $this->setFreezeColumn($key-300, $val);     
        }        
    }    
    $this->refreshKcmState();
}    
function convertToString() {  
$ar = array ();
$ar[] = $this->curSortColumn;
$ar[] = $this->curFreezeColumn;
for ($i=1; $i<=$this->colLastIndex; $i++) {
  $ar[] = $this->colEnabled[$i] 
        . $this->colSortable[$i]
        . $this->colSortDirec[$i]
        . $this->colFreezeState[$i];
}
$s = implode('~',$ar);
return $s;
}

function convertFromString($pParamValue) {  
    $ar = explode('~',$pParamValue);
    $this->curSortColumn = $ar[0];
    $this->curFreezeColumn = $ar[1];
    $this->colLastIndex = count($ar) - 2;
    for ($i=1; $i<$this->colLastIndex; $i++) { 
        $s = $ar[$i+1];
        $this->colEnabled[$i]  = substr($s,0,1); 
        $this->colSortable[$i] = substr($s,1,1);
        $this->colSortDirec[$i] = substr($s,2,1);
        $this->colFreezeState[$i] = substr($s,3,1);
    }        
}

function getDirecURL($pNewSortCol=0) {
    $newDirec = $this->colSortDirec[$pNewSortCol];
    if ($this->curSortColumn==$pNewSortCol) {
       if ($newDirec =='u')
           $newDirec = 'c';
       else
           $newDirec = 'u';
    }       
    $url = $this->paramKcmState->convertToUrl($this->paramPageUrl,
           $this->kcmStateKey.($pNewSortCol+200), $newDirec);
    return $url;
}
function getCurSortColumn() {  
    return $this->curSortColumn;
}
function getCurFreezeColumn() {  
    return $this->curFreezeColumn;
}
function getSortDirec($pColumn) {  
    return $this->colSortDirec[$pColumn];
}
function setEnabled($pColumn,$pEnabled=TRUE) {  
    if ($pEnabled)
        $this->colEnabled[$pColumn]='c';
    else    
        $this->colEnabled[$pColumn]='u';
}
function isEnabled($pColumn) {  
    $parent = $this->colParentIndex[$pColumn];
    return ($this->colEnabled[$parent]==='c');
}
function isFreezable($pColumn) {  
    return($this->colFreezable[$pColumn]);
}
function isFrozen($pColumn) {  
    return ($this->colFreezeState[$pColumn]==='c');
}
function isSortable($pColumn) {  
    return ($this->colSortable[$pColumn]==='c');
}
function setCheckbox($pColumn,$pIsCheckBox) {  
    // is TRUE/FALSE, not 'c' or 'u' - not a parameter
    $this->colCheckbox[$pColumn]=$pIsCheckBox;
}
function SetFreezeColumn($pColumn, $pValue=NULL) {  
    for ($i=1; $i<=$this->colLastIndex; $i++) 
         $this->colFreezeState[$i] = 'u';
    if ($pValue==='c') {
        $this->curFreezeColumn = $pColumn;
        $this->colFreezeState[$pColumn] = 'c';
    }    
    else    
        $this->curFreezeColumn = 0;  // none
}    
function setSortable($pColumn,$pEnabled) {  
    $this->colSortable[$pColumn]=$pEnabled;
}

function refreshKcmState() {
    $this->paramKcmState->ksSetArg($this->kcmStateKey,$this->convertToString());
}
}
?>