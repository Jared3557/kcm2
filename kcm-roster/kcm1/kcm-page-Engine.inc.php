<?php

// kcm-page-Engine.inc.php

// Class to print HTML to screen, or export to PDF or Excel File
// that is this class is used for web pages and reports
// and a web page can contain a report

const KCMFLD_DISABLE = 1;
const KCMFLD_AUTOFOCUS = 2;
const KCMFLD_READONLY = 4;
const KCMFLD_REQUIRED = 8;
const KCMFLD_AUTOCOMPLETE = 16;
const KCMFLD_CHECKED = 32;  // mostly for internal use

class kcm_pageEngine {

private $colDef;
private $enabled = TRUE;
public $rowCountAll;
public $rowCountPage;
public $curRowType;
public $report;  // linked report for heading
public $indent;
private $isExport;  // output to PDF or Excel export (always opposite of isWebPage)
private $isWebPage; // output to web page (always opposite of isExport)
private $inReportSection;  // is Report (either to Export or Web Page)
private $isReportPreview;   // is report preview (and not web-page table that is printable or exportable)
public $export;  // export engine object
private $headingColumn;
private $headingClass;
private $rowAlternateClasses;        
private $rowAlternateCount; 
private $curAlternateIndex;  // only used for data rows
private $curAlternateClass; 
public $breakAutoPaging;  // TRUE=normal   FALSE=no auto page break 
public $breakOnNewTable;  // TRUE=new page  FALSE=only if needed
public $pageNum;   // need to know if first page for certain page breaks
public $tablesOnPage;   // need to know if first table on page

function __construct($pColumnDef = NULL) {
    $this->colDef = $pColumnDef;
    $this->enabled = TRUE;
    $this->forceLogin = FALSE;
    $this->rowCountAll = 0;
    $this->rowCountPage = 0;
    $this->report = NULL;
    $this->curRowType = '';
    $this->indent = '';
    $this->isExport = FALSE;
    $this->inReportSection = FALSE;
    $this->isReportPreview = FALSE;
    $this->export = NULL;
    $this->inReportSection = FALSE;
    $this->rowAlternateClasses = NULL;
    $this->headingClass = 'kpageHeadingPortrait';
    $this->pageNum = 0;
    $this->tablesOnPage = 0;
    $this->isWebPage = TRUE;
}

function openForExport($pOrientation='', $pFontName='') {
    if ($pOrientation==='')
        $pOrientation = rpPAGE_PORTRAIT;
    if ($pFontName==='')
        $pFontName = 'helvetica';
    $this->isExport = TRUE;
    $this->export = new kcm_pageDOM_Engine($pOrientation,$pFontName);  
    $this->isWebPage = FALSE;
    $this->export->domSetAutoPage($this->breakAutoPaging);
}

function setAutoPageBreaks($pMode) {  
    $this->breakAutoPaging = $pMode;
}

function setBreakOnNewTable($pMode) {  
    $this->breakOnNewTable = $pMode;
    if ($this->isExport) 
        $this->export->domSetBreakOnNewTable($pMode);
}
function setIsReportPreview() {  
    $this->isReportPreview = TRUE;
}

//  ***************
//  *  Properties *
//  ***************

function changeIndent($inc) {
    $len = strlen($this->indent) + $inc*2;
    if ($len<0)
        $len=0;
    if ($len>10)
        $len=10;
    $this->indent = str_repeat(' ',$len);
}
function lineStart() {
    if (!$this->isExport)
        return;
    print PHP_EOL.$this->indent;
}

//  *****************
//  * Tag Functions *
//  *****************

function getAttributeString($pClass, $pAttributes='', $pFlags=0) {
    $s= '';
    if ($pClass!='') {
        $s .= ' Class="'.$pClass.'"';
    }   
    if ($pAttributes!='') {
        $s .= ' '.$pAttributes;
    }   
    if ($pFlags >= 1) {
        if ( $pFlags & KCMFLD_DISABLE) {
            $s .= ' disabled';
        }
        if ( $pFlags & KCMFLD_AUTOFOCUS) {
            $s .= ' autofocus';
        }
        if ( $pFlags & KCMFLD_READONLY) {
            $s .= ' readonly';
        }
        if ( $pFlags & KCMFLD_REQUIRED) {
            $s .= ' required';
        }
        if ( $pFlags & KCMFLD_AUTOCOMPLETE) {
            $s .= ' autocomplete';
        }
        if ( $pFlags & KCMFLD_CHECKED) {
            $s .= ' checked';
        }
    }  
    return $s;   
}

function tagPrint($pTag, $pClass='', $pAttributes='', $pFlags=0) {
    if ( ! $this->isExport) {
        print PHP_EOL . '<'.$pTag . $this->getAttributeString($pClass, $pAttributes, $pFlags=0) . '>';
    }    
}
function tagPrintAt($pTag, $pClass='',$pAttributes) {
    if ($this->isExport)
        return;
    $s= '<'.$pTag;
    if ($pClass != '')
       $s .= ' Class="'.$pClass.'"';
    if ($pAttributes!='')
       $s .= ' ' . $pAttributes;
    $s .= '>';
    print PHP_EOL.$s;
}

function addRowClass($pClass) {
    if ($this->curAlternateClass==='') 
       return $pClass;
    if ($pClass==='') 
        return $this->curAlternateClass;
    return $pClass . ' ' . $this->curAlternateClass;
}    

//  *****************
//  * DIV Functions *
//  *****************

function divStart($pClass='',$pAttributes='') {
    if ($this->isWebPage) {
        $this->lineStart();
        $this->tagPrintAt('div',$pClass,$pAttributes);
        $this->changeIndent(1);
    }    
}
    
function divEnd() {
    if ($this->isWebPage) {
        $this->changeIndent(-1);
        print '</div>'.PHP_EOL.PHP_EOL;
    }    
}

function screenOnlyStart() {
    if ($this->isWebPage) {
        $this->lineStart();
        print PHP_EOL.PHP_EOL.PHP_EOL.'<div class="ScreenOnly">'.PHP_EOL;
        $this->changeIndent(1);
    }    
}    

function ScreenOnlyEnd() {
    if ($this->isWebPage) {
        $this->changeIndent(-1);
        $this->lineStart();
        print PHP_EOL.'</div><!-- End ScreenOnly -->'.PHP_EOL.PHP_EOL.PHP_EOL;
    }    
}    

//  *******************
//  * TABLE Functions *
//  *******************

function tableStart($pClass) {
    // attributes are very limited when they can appear on a report
    // $pAttr when on a report can only be span attributes (colspan or rowspan)
    // $pAttr when not on a report can be any attribute, including javascript events, etc 
    //~~????????15/08
    if ($this->isWebPage) {   
        print PHP_EOL.PHP_EOL.PHP_EOL;
        $this->lineStart();
        $this->tagPrint('table',$pClass);
        $this->changeIndent(1);
    }    
    else if ($this->inReportSection) 
        $this->export->domTableStart($pClass);
    ++$this->tablesOnPage;
}

function rpt_ScreenPageBreak($endTable=FALSE) {  
    ++$this->pageNum;  // need to know if 1st page for page breaks
    $this->tablesOnPage = 0;
    if ($this->isWebPage and $this->pageNum>=1) {  // and $this->breakOnNewTable 
        if ($endTable) {
            $this->rpt_tableEnd();
        } 
        print '<div class="kpagePageBreak"><hr class="kpagePageBreak"></div>'.PHP_EOL;
    }    
}
function rpt_tableStart($pClass) {
    $this->inReportSection = TRUE;
    //if ($this->breakOnNewTable)
    //    $this->tableStartPage();
    $this->tableStart($pClass,'');
}

function tableEnd() {
    if ($this->isWebPage) {   
        $this->changeIndent(-1);
        $this->lineStart();
        print PHP_EOL.'</table>'.PHP_EOL.PHP_EOL.PHP_EOL;
    }    
    else if ($this->inReportSection) 
        $this->export->domTableEnd();
}

function rpt_tableEnd() {
    $this->tableEnd();
    $this->inReportSection = FALSE;
}

//  *****************
//  * ROW Functions *
//  *****************

function rowStart($pRowType='') {
    if ($this->rowAlternateClasses===NULL or $pRowType!='d') {
        $this->curAlternateIndex = 9999;
        $this->curAlternateClass = '';
    }
    else {
        ++$this->curAlternateIndex;
        if ($this->curAlternateIndex >= $this->rowAlternateCount)
            $this->curAlternateIndex = 0;
        $this->curAlternateClass = $this->rowAlternateClasses[$this->curAlternateIndex];
    }        
    $this->curRowType =$pRowType;
    if ($pRowType==='d') {
       $this->rowCountAll++;
       $this->rowCountPage++;
    }
    if ($this->isWebPage) {   
        $this->lineStart();
        print '<tr>';
        $this->changeIndent(1);
    }    
    else if ($this->inReportSection) 
        $this->export->domRowStart($pRowType);
}

function rpt_rowStart($pRowType='') {
    $this->rowStart($pRowType);
}

function rowEnd() {
    if ($this->isWebPage) {   
        $this->changeIndent(-1);
        $this->lineStart();
        print '</tr>';
    }    
    else if ($this->inReportSection) 
        $this->export->domRowEnd();
}

function rpt_rowEnd() {
    $this->rowEnd();
}

function rowSetClasses($pArray) {
    $this->rowAlternateClasses = $pArray;
    $this->rowAlternateCount = count($this->rowAlternateClasses);
    $this->curAlternateIndex = 9999;
}    

//  ******************
//  * CELL Functions *
//  ******************

function cellStart($pClass='',$pAttributes='') {
    // attributes are very limited when can appear on a report
    // $pAttr when on a report can only be span attributes (colspan or rowspan)
    // $pAttr when not on a report can be any attribute 
    $pClass = $this->addRowClass($pClass);
    if ($this->isWebPage) {   
        $this->lineStart();
        if ($this->curRowType==='h')
            $this->tagPrint('th',$pClass, $pAttributes);
        else
            $this->tagPrint('td',$pClass, $pAttributes);
        $this->changeIndent(1);
    }    
    else if ($this->inReportSection) 
        $this->export->domCellStart($pClass,'',$pAttributes);
}

function cellEnd() {
    if ($this->isWebPage) {   
        $this->changeIndent(-1);
        if ($this->curRowType==='h')
            print '</th>';
        else
            print '</td>';
    }        
    else if ($this->inReportSection) 
        $this->export->domCellEnd();
}

function cellOfText($pClass,$pText,$pAttributes='') {
    // attributes are very limited when they can appear on a report
    // $pAttr when on a report can only be span attributes (colspan or rowspan)
    // $pAttr when not on a report can be any attribute, including javascript events, etc 
    if ($this->isWebPage) {   
        $this->lineStart();
        $this->cellStart($pClass,$pAttributes);
        print $pText;
        $this->cellEnd();
    }    
}

function cellOfLink($pClass,$pText, $pUrl) {
    if ($this->isWebPage) {   
        $this->lineStart();
        $this->cellStart($pClass);
        print '<a href="'.$pUrl.'">'.$pText.'</a>';
        $this->cellEnd();
    }    
}

function cellOfItems($pDivInCellClass,$pClass,$pItems,$pAttr='') {
    if ($this->isWebPage) {   
        $this->lineStart();
        $this->cellStart($pClass,'',$pAttr);
        print '<div class="'.$pDivInCellClass.'">';
        $count = count($pItems);
        for ($i = 0; $i<$count; $i=$i+2) {
            $class = $pItems[$i];
            $text = $pItems[$i+1];
            $tag = substr($class,0,4);
            $class = substr($class,4);
            if ($tag==='div.') 
                print '<div class="'.$class.'">'.$text.'</div>';
            else if ($tag==='img.') 
                print '<img class="'.$class.'" src="'.$text.'">';
        }
        print '</div>';
        $this->cellEnd();
    }    
}

function rpt_cellOfText($pClass,$pText, $pAttributes='') {
    // attributes are very limited when they can appear on a report
    // $pAttr when on a report can only be span attributes (colspan or rowspan)
    // $pAttr when not on a report can be any attribute, including javascript events, etc 
    if ($this->isWebPage) 
        $this->cellOfText($pClass,$pText,$pAttributes);
    else 
        $this->export->domCellofText($this->addRowClass($pClass),$pText,$pAttributes);
}

function rpt_cellOfItems($pDivInCellClass,$pCellClass,$pItems,$pAttributes='') {
    // attributes are very limited when they can appear on a report
    // $pAttr when on a report can only be span attributes (colspan or rowspan)
    // $pAttr when not on a report can be any attribute, including javascript events, etc 
    if ($this->isWebPage) 
        $this->cellOfItems($pDivInCellClass,$pCellClass,$pItems,$pAttributes);
    else
        $this->export->domCellofItems($pCellClass,$pItems,$pAttributes);
}

//function rpt_cellOfLink($pClass,$pText, $pUrl,$pS//tyle) {
//    if ($this->isWebPage) 
//        $this->cellOfLink($pClass,$pText, $pUrl,$pSt//yle);
//    else  
//        $this->export->domCellofText($this->addRowClass($pClass),$pSt//yle,$pText);
//}

//  ******************
//  * TEXT Functions *
//  ******************

function textOut($pText) {
    if ($this->isWebPage)    
        print $pText;
    else if ($this->inReportSection) 
        $this->export->domTextOut($pText);
}

function textLine($pText) {
    $this->textOut($pText);
    $this->textBreak();
}
function textBreak() {  //?????????? not defined if report
    if ($this->isWebPage) {   
        $this->lineStart();
        print '<br>';
    }    
    //--?????? maybe buggy
    //else if ($this->inReportSection) 
    //    $this->export->domLineBreak();  //?????? not defined
    // in DOM two text strings are two lines or can send <br> in text 
}

function textLink($pCaption,$pUrl) {  
    if ($this->isWebPage) {   
        print '<a href="'.$pUrl.'">'.$pCaption.'</a>';
    }    
    else if ($this->inReportSection) 
        $this->export->domTextOut($pCaption);
}

function textKcmLink($pCaption,$pPageName, $pKcmState,$pArgId=NULL,$pArgVal=NULL) {  
    if ($this->isWebPage) {   
        $url =  $pKcmState->convertToUrl($pPageName,$pArgId,$pArgVal);
        print '<a href="'.$url.'">'.$pCaption.'</a>';
    }    
    else if ($this->inReportSection) 
        $this->export->textOut($pCaption);
}

//function textDiv($pClass,$pText,$pS//tyle='') {
//    if (!$this->isWebPage) {   
//        $this->divStart($pClass,$pS//tyle);
//        $this->textOut($pText);
//        $this->divEnd();
//    }    
//    else if ($this->inReportSection) 
//        $this->export->domTextDiv($pClass,$pS//tyle,$pText);
//}

//==========================================
//=  Heading Methods                       =
//=  (Methods duplicated in reports)       = 
//=  (Report method may call page method)  = 
//==========================================

function headingStart($pClass='') {  
    if ($pClass!=='')
        $this->headingClass = $pClass;
    $this->headingColumn = 0;
    $this->inReportSection = TRUE;
    if ($this->isWebPage)  {
         print '<table class="'.$this->headingClass.'">'.PHP_EOL;
         print '<tr><td class="'.$this->headingClass.'1">'.PHP_EOL;
    }      
    else {
        $this->export->domHeadingStart();
    }
    $this->webPageHeaderStyle('css/rc_admin.css');
} 

function headingText($pText) {  
    if ($this->isWebPage) {
        print $pText.'<br>';
    }
    else {
        $this->export->domHeadingText($this->headingColumn, $pText);
    }    
}

function headingSemester($pClassData) {  
   $this->headingText($pClassData->schedule->meetDateDesc);
}

function headingPeriod($pPeriod) {  
    $this->headingText($pPeriod->getNameLong());
}

function headingKid($pKid) {  
    $this->headingText($pKid->getNameLong());
}

function headingProgram($pClassData) {  
    $this->headingText($pClassData->program->getNameLong($pClassData));
}

function headingEnd() {  
    if ($this->isWebPage) {
        print '</td>'.PHP_EOL;
        if (!$this->isReportPreview) {
            print '<td class="'.$this->headingClass.'3">';
            print '</td>'.PHP_EOL;
        }    
        print '</tr></table>';
    }    
    else {
        $this->export->domHeadingEnd();
    }
    $this->inReportSection = FALSE;
}

function headingNextColumn() {  
    $this->headingColumn = 1;
    if ($this->isWebPage) {
        print '</td>'.PHP_EOL;
        print '<td class="'.$this->headingClass.'2">'.PHP_EOL;
    }    
} 


//  ********************
//  * Column Functions *
//  ********************

function rpt_col_FreezeButton($pColumn) {  // some output only to page, some to report
    //if ($this->isExport) $this->export->textOut($arrow);  ?????
    if ($this->isWebPage) {  //????????
        if ( ! $this->colDef->isFreezable($pColumn)) {
            return;
        }    
        if ($this->colDef->getSortDirec($pColumn)=='c')
            $arrow = '&xutri;';  
        else
            $arrow = '&xdtri;';  
        if ($pColumn != $this->colDef->curSortColumn) {
            if ($this->colDef->isFrozen($pColumn)) {
                 //print $arrow.'<br>Column<br>is Frozen';
                 print $arrow.'<br>';
                 $this->inputButton('','Unfreeze', $this->colDef->kcmStateKey.($pColumn+300), 'u');
                 //if ($this->isExport) $this->export->textOut($arrow);  ?????
            }     
            return;     
        }
        if ($this->colDef->isFrozen($pColumn)) {
            //print $arrow.'<br>Column<br>is Frozen';
            print $arrow.'<br>';
            $this->inputButton('','Unfreeze', $this->colDef->kcmStateKey.($pColumn+300), 'u');
        }    
        else {   
            print $arrow.'<br>';
            $this->inputButton('','Freeze', $this->colDef->kcmStateKey.($pColumn+300), 'c');
        }    
    }    
}

function rpt_col_Header($pColumn,$pClass,$pCaption,$pAttributes='') {  
    // attributes are very limited when they can appear on a report
    // $pAttr when on a report can only be span attributes (colspan or rowspan)
    // $pAttr when not on a report can be any attribute, including javascript events, etc 
    if (!$this->colDef->isEnabled($pColumn))
        return;
    if ($this->isExport) {
        //?????????????????????????? need sort indicators, etc
        $this->rpt_cellOfText($pClass,$pCaption,$pAttributes);
        return;
    }
    else if ($this->isWebPage) {
        if ($this->colDef->isFreezable($pColumn) 
           and $this->colDef->isFrozen($pColumn)) 
               //--??????? would be better to get style from css by adding additional class to class param       
               $pAttributes .= ' style="background-color:#D8D8FF;"';  //??????????????????
        $this->tagPrint('th',$pClass, $pAttributes);    //??????????????????
        if ($this->colDef->isSortable($pColumn)) {
            if ($pColumn==$this->colDef->getCurSortColumn())   
                if ($this->colDef->getSortDirec($pColumn)=='c')
                    $pCaption .= '&#9650';  
                else
                    $pCaption .= '&#9660';  
            $url = $this->colDef->getDirecURL($pColumn);        
            $this->textLink($pCaption,$url);   
            }
        else    
            $this->textOut($pCaption);   
        $this->rpt_col_FreezeButton($pColumn);
        print '</th>'.PHP_EOL;                          //???????????????????????
    }    
}

function rpt_col_CellOfText($pColumn,$pClass,$pText,$pAttributes='') {  
    // attributes are very limited when they can appear on a report
    // $pAttr when on a report can only be span attributes (colspan or rowspan)
    // $pAttr when not on a report can be any attribute, including javascript events, etc 
    if (!$this->colDef->isEnabled($pColumn))
        return;
    if ($this->isWebPage)
        $this->cellOfText($pClass,$pText,$pAttributes);   
    else     
        $this->export->domCellOfText($this->addRowClass($pClass),$pText,$pAttributes);
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@ Methods below here are "disabled" when exporting 
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

//  *****************
//  * FORM Functions *
//  *****************

function frmStart($pMethod, $pId, $pAction, $pClass='') {
    if ($this->isWebPage) {
        $this->lineStart();
        $attr = 'name="'.$pId.'" method="'.$pMethod.'" action="'.$pAction.'" ';
        $this->tagPrint('form',$pClass,$attr);
        $this->changeIndent(1);
    }    
}

function frmEnd($pKcmState=NULL) {
    if ($this->isWebPage) {
        if ($pKcmState!=NULL)
            $this->frmAddHidden($pKcmState->Id, $pKcmState->ksConvertToString());
        $this->changeIndent(-1);
        $this->lineStart();
        print '</form>'.PHP_EOL;
    }    
}

function frmAddHidden($pName,$pValue) {
    if ($this->isWebPage) {
        $this->lineStart();
        print '<input type="hidden" name="'.$pName.'" value="'.$pValue.'">';
    }    
}    

function frmAddDupSubmitChecking() {
    if ($this->isWebPage) {
        // assign tran ID to form - and pass as hidden in form
        // This tran ID will only allow first post/get of form to be valid
        $tranId = uniqid( '', TRUE );
        $_SESSION['kcmTranId'][$tranId] = rc_getNow();  // used to detect duplicate submissions
        $this->frmAddHidden('kcmTranId',$tranId);
    //rc_setDebugData( 'DupSubmit-Start', $tranId);
        //echo "<input type='hidden' name='kcmTranId' value='{$tranId}'>";
        return $tranId;
    }    
}    

//  *****************
//  * FORM Controls *
//  *****************

//--- Input functions have standard attributes and flags

function inputButton($pClass,$pCaption, $pId, $pArgValue, $pAttributes='', $pFlags=0) {
    if ($this->isWebPage) {
        $this->lineStart();
        print '<button type="Submit" name="'.$pId.'" value="'.$pArgValue.'" '.$this->getAttributeString($pClass, $pAttributes,$pFlags).'>'.$pCaption.'</button>'.PHP_EOL;
    }    
}

function inputText($pClass, $pValue, $pId, $pVisLen=0, $pStrLen=0, $pAttributes='', $pFlags=0) {
    //~~??? style is used for error in one place
    if ($this->isWebPage) {
        $this->lineStart();
        $s = '<input type="text"'.$this->getAttributeString($pClass,$pAttributes, $pFlags);
        if ($pVisLen>0) {
            $s .= ' size="'.$pVisLen.'"'; 
            if ($pStrLen<1)
                $pStrLen = $pVisLen;
        }    
        $s .= ' name="'.$pId.'" value ="'.$pValue.'"';
        if ($pStrLen>0)
            $s .= ' maxlength="'.$pStrLen.'"';
        $s .= '>';
        print $s;
    }    
}

function inputTextArea($pClass, $pValue, $pId,  $pMaxLen, $pCols, $pRows, $pAttributes='', $pFlags=0) {
    if ($this->isWebPage) {
        $this->lineStart();
        $s = '<textArea '.$this->getAttributeString($pClass,$pAttributes, $pFlags=0);
        $s .= ' name="'.$pId.'" maxlen="'.$pMaxLen.'" cols="'.$pCols.'" rows="'.$pRows.'"';
        $s .= '>'.$pValue.'</textarea>';
        print $s;
    }    
}              

function inputListBox($pClass,$pHeight,$pArgId,$pList,$pCurValue='',$pAttributes='', $pFlags=0) {
    if ($this->isWebPage) {
        $this->lineStart();
        $this->lineStart();
        //$this->newline();
        print '<select'.$this->getAttributeString($pClass,$pAttributes,$pFlags).' name="'.$pArgId.'" size="'.$pHeight.'">'.PHP_EOL;
        $this->changeIndent(1);
        for ($i = 0; $i<count($pList); $i=$i+2) {
            $this->lineStart();
            $code =$pList[$i];
            if ($code==$pCurValue)
                $pVal=' selected';
            else   
                $pVal='';
              print '<option'.$this->getAttributeString($pClass).' value="'.$code.'"'.$pVal.' >'.$pList[$i+1].'</option>';
        }
        $this->changeIndent(-1);
        $this->lineStart();
        print '</select>';
    }    
}

function inputRadioButton($pClass, $pGroupId, $pArgValue, $pCaption, $pCurValue='', $pAttributes='', $pFlags=0) {
    //~~???? also need class for span margins, etc
    if ($this->isWebPage) {
        $this->lineStart();
        if ($pCurValue==$pArgValue) {
            $pFlags = $pFlags | KCMFLD_CHECKED;
        }   
        print '<span><input type="radio" name="'.$pGroupId.'" value="'.$pArgValue.'" '.$this->getAttributeString($pClass,$pAttributes, $pFlags).'>'.$pCaption.'</span>';
    }    
}      

function InputCheckBox($pClass, $pCaption, $pArgId, $pValue, $pCurValue, $pAttributes='', $pFlags = 0) {
    if ($this->isWebPage) {
        $this->lineStart();
        if ($pValue===$pCurValue) {
            $pFlags = $pFlags | KCMFLD_CHECKED;
        }   
        print '<input type="checkbox" '.$this->getAttributeString($pClass,$pAttributes, $pFlags).' name="'.$pArgId.'" value="'.$pValue.'">'.$pCaption; 
    }    
}

//--- ctr functions do not have standard attributes and flags

function ctrNewCheckBox($pClass,$pCaption, $pId, $pChecked) {  
    if ($this->isWebPage) {
        $this->lineStart();
        if ($pChecked===TRUE or $pChecked==='c')
           $checked=' checked';
        else   
           $checked='';
        print '<input type="checkbox"'.$this->getAttributeString($pClass).' name="'.$pId.'" value="c"'.$checked.'>'.$pCaption; 
    }    
}

function ctrColumnCheckBox($pColumnDef, $pColumn, $pClass,$pCaption) {  
    if ($this->isWebPage) {
        $this->ctrNewCheckbox($pClass, $pCaption,$pColumnDef->kcmStateKey.($pColumn+100), $pColumnDef->isEnabled($pColumn));   
    }    
}    

function ctrButtonForm($pClass,$pCaption, $pSubmitVal, $pUrl, $pKcmState, $pHiddenId=NULL, $pHiddenVal=NULL) {
    if ($this->isWebPage) {
        $this->lineStart();
        if ($pSubmitVal=='')
           $pSubmitVal = 'none';
        $attr = $this->getAttributeString($pClass);
        //@@@@ changed
        print '<form '.$attr.' name="input" id = "columnDef" action="'.$pUrl.'" method="get">'.PHP_EOL;
        print '<button type="Submit" name="Submit" value="'.$pSubmitVal.'" >'.$pCaption.'</button>'.PHP_EOL;
        print $pKcmState->convertToHidden().PHP_EOL;
        if ($pHiddenId!=NULL) {
            if (is_array($pHiddenId)) {
                for ($i = 0; $i<count($pHiddenId); $i=$i+2) {
                    print '<input type="hidden" name="'.$pHiddenId[$i].'" value="'.$pHiddenId[$i+1].'">';
                }
            }
            else
                print '<input type="hidden" name="'.$pHiddenId.'" value="'.$pHiddenVal.'">';
        }    
        print '</form>'.PHP_EOL;
    }    
}

//  ****************************
//  * Display Status Functions *
//  ****************************

function unListStart($pClass) {
    if ($this->isWebPage) {
        $this->lineStart();
        $this->tagPrint('ul',$pClass);
        $this->changeIndent(1);
    }    
}

function unListItem($pText) {
    if ($this->isWebPage) {
        $this->lineStart();
        print '<li>'.$pText.'</li>';
    }    
}

function unListEnd() {
    if ($this->isWebPage) {
        $this->changeIndent(-1);
        $this->lineStart();
        print '</ul>';
    }    
}

//=========================
//=  Show Message Methods =
//=    (Not for reports)  = 
//=========================

function MessageError($pString) {
    if ($this->isWebPage) {
        if ($pString!='') {
            $this->lineStart();
            //--??????? would be better to get style from css       
            print '<div style= "color:red;font-weight:bold;font-size:10pt;padding:1px">'.$pString.'</div>'.PHP_EOL;   // need to use error proper style
        }    
    }    
}

function MessageStatus($pString) {
    if ($this->isWebPage) {
        if ($pString!='') {
            $this->lineStart();
            //--??????? would be better to get style from css       
            print '<br><h2 style="font-weight:bold;min-width:800px;background:#FAC8C8">'.$pString.'</h2><br>'.PHP_EOL;   // need to use error proper style
        }    
    }    
}

//  ********************
//  * Web Page Methods *
//  * Print main areas * 
//  * of Web Page      *
//  ********************

function webPageHeaderStyle($pRef, $pMedia='') {
    if ($this->isWebPage) {
        if ($pMedia!='')
            $pMedia = 'media="'.$pMedia.'" ';
        print '<link rel="stylesheet" type="text/css" '.$pMedia.'href="'.$pRef.'"/>'.PHP_EOL;
    }    
}

function webPageBodyEnd($scripts='') {
    if ($this->isWebPage) {
        print rc_getHideBackgroundHtml('regTop','../');
        print PHP_EOL.'</body>';
        if ($scripts != '') {
            print $scripts;
        }
        print '</html>'.PHP_EOL;
    }        
}

//==========================
//=  System Header Methods =
//=    (Not for reports)   = 
//==========================

function systemExportForm($title,$kcmState,$url,$isExcel=TRUE) {
    $this->tableStart('kpageExportOptions');
        $this->rowStart();
            $this->cellStart('kpageExportOptions','colspan=2');
            $this->textOut('<h3>'.$title.'</h3>');
            $this->cellEnd();
        $this->rowEnd();
        $this->rowStart();
            $this->cellStart('kpageExportOptions');
                $this->ctrButtonForm('kcmPageButtonExport','Print (to PDF)', 'period', $url, $kcmState,'Submit','p');  //--- ??? and additional parameter needed ???
            $this->cellEnd();
            $this->cellStart('kpageExportOptions');
            $this->textOut('<h6>on Firefox (and maybe other browsers) upon viewing the PDF<br>you will need to click the download button and then click the print button to print properly</h6>');
            $this->cellEnd();
        $this->rowEnd();
        if ($isExcel) {
            $this->rowStart();
                $this->cellStart('kpageExportOptions','colspan=2');
                $this->ctrButtonForm('kcmPageButtonExport', 'Export (to Excel)', 'period',  $url, $kcmState,'Submit','e');  //--- ??? and additional parameter needed ???
                $this->cellEnd();
            $this->rowEnd();
        }    
    $this->tableEnd();
    $this->textBreak();
}

}
?>