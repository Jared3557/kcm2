<?php

Class rc_event_HtmlLib {
private $ClassName;
private $InTd;
private $tabInCell;
private $tabInRow;

function __construct() {
    $this->ClassName = '';
    $this->InTd = FALSE;
    $this->NewLine = FALSE;
}
private function addClassName() {
    if ($this->ClassName=='')
        return '';
    else   
        return 'Class="'.$this->ClassName.'" ';
}
private function newline() {
if ($this->NewLine)
    print '<br>';
$this->NewLine = TRUE;
}
public function text($pText) {
    print $pText;
}
//public function addColumnStart($pHtml='') {
//if ($this->InTd)
    //print '</td>'.PHP_EOL; 
//print '<td '.$this->addClassName().$pHtml.'>'; 
//$this->InTd = TRUE;
//$this->NewLine = FALSE;
//}
public function addHtml($pText) {
print $pText; 
}
public function genTag($pTag,$pClass,$pAttr) {
if ($pClass=='')
   print '<'.$pTag.' '.$pAttr.'>';
else 
   print '<'.$pTag.' class="'.$pClass.'" '.$pAttr.'>';
}
public function HyperButton($pCaption, $pUrl, $pArgList=array(), $pArgString='') { //@@@ eliminate ???
$args = '';
$sym = '?';
for ($i = 0; $i<count($pArgList); $i=$i+2) {
    $args = $args . $sym . $pArgList[$i] . '=' . $pArgList[$i+1];
    $sym = '&';
}    
$args=$args.$pArgString;
print '<a href="'.$pUrl.$args.'">'.$pCaption.'</a>'.PHP_EOL;
}

public function HyperLink($pClass,$pStyle,$pCaption, $pUrl, $pArgList=array(), $pArgString='') {
$args = '';
$sym = '?';
for ($i = 0; $i<count($pArgList); $i=$i+2) {
    $args = $args . $sym . $pArgList[$i] . '=' . $pArgList[$i+1];
    $sym = '&';
}    
$args=$args.$pArgString;
print '<a href="'.$pUrl.$args.'">'.$pCaption.'</a>'.PHP_EOL;
}

//=============================
// table
public function tableStart($pClass='', $pAttr='') {
$this->tabInCell = FALSE;    
$this->tabInRow = FALSE;    
$this->genTag('table',$pClass,$pAttr); 
print PHP_EOL;
}
public function tableRow($pClass='', $pStyle='') {
if ($this->tabInCell)
    print '</td>';
if ($this->tabInRow)
    print '</tr>'.PHP_EOL;
print '<tr>'.PHP_EOL;
$this->tabInCell = FALSE;    
$this->tabInRow = TRUE;    
}
public function tableCell($pClass='', $pAttr='') {
if ($this->tabInCell)
    print '</td>'.PHP_EOL;
$this->genTag('td',$pClass,$pAttr); 
$this->tabInCell = TRUE;    
}
public function tableCellText($pClass, $pAttr, $pText) {
if ($this->tabInCell)
    print '</td>';
$this->tabInCell = FALSE;    
$this->genTag('td',$pClass,$pAttr); 
print $pText;
print '</td>'.PHP_EOL;
}
public function tableEnd() {
if ($this->tabInCell)
    print '</td>';
if ($this->tabInRow)
    print '</tr>';
print '</table>';
print PHP_EOL;
}

//===============================
//=  form - misc
public function getComboBoxValue($pArgValue) {
if (getParam($pArgValue,'')=='')
    return '';
else
    return $_GET[$pArgValue];   
}   
public function getCheckValue($pArgValue) {
if (getParam($pArgValue,'')=='')
    return FALSE;
else
    return TRUE;   
}   

//===============================
//=  form
public function formStart($pFormName, $pClassName, $pURL,$pPost=false) {
    $this->ClassName = $pClassName;
    //$this->InTd = FALSE;
    print '<div '.$this->addClassName().'>'.PHP_EOL;
    if ($pPost)
        print '<form name="'.$pFormName.'" action="'.$pURL.'" method="post">'.PHP_EOL;
    else    
        print '<form name="'.$pFormName.'" action="'.$pURL.'" method="get">'.PHP_EOL;
    //print '<table '.$this->addClassName().'><tr>'.PHP_EOL;
    //$this->InTd = TRUE;
    $this->NewLine = FALSE;
    //$this->tabInRow = TRUE;    
    //$this->tabInCell = TRUE;    
}
public function formEnd() {
    //if ($this->InTd)
    //    print '</td>'.PHP_EOL; 
    //print '</tr></table>'.PHP_EOL;
    print '</form>'.PHP_EOL;
    print '</div>'.PHP_EOL;
}
public function formCheckBox($pClass,$pStyle,$pArgValue,$pCaption, $pIsChecked) {
//$this->newline();
    if ($pIsChecked)
       $pVal='checked';
    else   
       $pVal='';
    print '<input type="checkbox"'.$this->addClassName().' name="'.$pArgValue.'" value="'.$pArgValue.'"'.$pVal.'>'.$pCaption.'<br>'.PHP_EOL; 
}
public function formButton($pClass,$pStyle,$pCaption, $pId, $pArgValue, $pAttr='') {
    $this->genTag('button type="submit" name="'.$pId.'" value="'.$pArgValue.'"',$this->ClassName,$pAttr); 
    print $pCaption.'</button>'.PHP_EOL;
}
public function formHidden($pArgName,$pValue) {
    print '<input type="hidden" name="'.$pArgName.'" value="'.$pValue.'">'.PHP_EOL;
}
public function tableCellComboBox($pClass, $pAttr, $desc, $pArgId,$pList, $pCodeValue, $pWidth=0, $pDisabled=FALSE) {
      $this->tableCell($pClass, $pAttr);
      $this->formListBox ($pClass,$pAttr,$desc,$pArgId,$pList, $pCodeValue, 1, $pWidth, $pDisabled);
}
public function formComboBox($pClass,$pStyle,$desc,$pArgId,$pList, $pCodeValue, $pWidth=0, $pDisabled=FALSE) {
    $this->formListBox ($pClass,$pStyle,$desc,$pArgId,$pList, $pCodeValue, 1, $pWidth, $pDisabled);
}
public function formRadio($pClass,$pStyle,$pGroup, $pArgValue, $pCaption, $pCheckedArg='') {
      if ($pCheckedArg==$pArgValue) {
          $ch = ' checked ';
      }
      else
          $ch = '';      
      print '<input type="radio" name="'.$pGroup.'" value="'.$pArgValue.'"'.$ch.'>'.$pCaption.'<br>';
}      
public function tableCellRadio($pClass, $pAttr, $pGroup, $pArgValue, $pCaption, $pCheckedArg='') {
      $this->tableCell($pClass, $pAttr);
      $this->formRadio('','',$pGroup, $pArgValue, $pCaption, $pCheckedArg);
}      

public function formListBox($pClass,$pStyle,$desc,$pArgId,$pList, $pCodeValue, $pHeight, $pWidth=0, $pDisabled=FALSE) {
    $this->newline();
    if ($desc!='')
        print $desc;
    if ($pWidth!='')    
        $widthAttr = ' width="'.$pWidth.'px"';
    else    
        $widthAttr = '';
    if ($pDisabled)
       $dis='Disabled ';
    else   
       $dis='';
    print '<select '.$dis.'name="'.$pArgId.'" '.$widthAttr.'size="'.$pHeight.'">'.PHP_EOL;
    for ($i = 0; $i<count($pList); $i=$i+2) {
        $code =$pList[$i];
        if ($code==$pCodeValue)
            $pVal=' selected';
        else   
            $pVal='';
          print '<option '.$this->addClassName().' value="'.$code.'"'.$pVal.' '.$widthAttr.'>'.$pList[$i+1].'</option>'.PHP_EOL;
    }
    print '</select>'.PHP_EOL;
}
}

?>