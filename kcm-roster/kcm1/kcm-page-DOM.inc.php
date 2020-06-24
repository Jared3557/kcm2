<?php

// kcm-page-DOM.inc.php    (document object model)

define('kcmATTRREGEXP',"@([a-zA-Z][a-zA-Z0-9\-]*)|([0-9][0-9]*)@");

//******************************************************************
//*  pageDOM Engine

Class kcm_pageDOM_Engine {
//--- table
public $tableArray;
public $tablesOnPage;
public $curTable;
//-----
public $heading;
public $paper;
//----
public $curRow;
public $rowExcelIndex;  // row of entire report (for excel cell), not row of table
public $rowTableIndex;  
public $curColIndex;  // col of last cell created on current row, not current cell column if spans
public $styleList;

public $curCell;  // very tricky if merged cells (first cell is used, other cells used for borders) 
public $curDiv;  // stored in cell - a cell can be (multiple) text/lf and/or multiple divisions
public $curBottom;
public $breakAutoPaging;  // TRUE=normal   FALSE=no auto page break 
public $breakOnNewTable;  // TRUE=new page  FALSE=only if needed

function __construct($pOrientation=rpPAGE_PORTRAIT) {
    $this->tableArray = array();  
    $this->styleList = new kcmPage_styleList();
    $this->breakOnNewTable = TRUE;
    $this->paper = new kcm_pageDOM_Paper($pOrientation);
    $this->heading = NULL;
    $this->curRow = NULL;
    $this->rowExcelIndex = 0;
    $this->curColIndex = 0;
    $this->curTable = NULL;
    $this->curRow = NULL;
    $this->curCell = NULL;
    $this->curDiv = NULL; 
    $this->taColWidths = array(50);
    $this->taColCount = 0;
    $this->breakAutoPaging = TRUE;
}

//  ******************************
//  * Body/Paper/Heading Methods *
//  ******************************

function domSetAutoPage($pMode) {  
    $this->breakAutoPaging = $pMode;
}

function domSetBreakOnNewTable($pMode) {  
    $this->breakOnNewTable = $pMode;
}

function domAddStyleFile($pFileName) {  
    $this->styleList->addfile($pFileName);
}

//function domBodyStart($pClass,$pSt//yle) {  
//    $this->paper->paBodySet($this->styleList, $pClass, $pS//tyle);
//}

function domHeadingStart() {  
   $this->heading = new kcm_pageDOM_Heading($this->paper->paStyle);
    $this->heading->heStart($this->styleList, 'kpageHeading');
    // if more than one, then previous heading should be part of previous table
    // cannot add to table now, as tableStart will come after the headingStart 
}
function domHeadingText($pColumn, $pText) {  
    $this->heading->heAddText($pColumn, $pText);
}
function domHeadingEnd() {  
    $this->heading->heEnd();
}

//********************************************************************
//* Table Methods 

function domTableStart($pClass) {   // only report tables call this method
    $this->curTable = new kcm_pageDOM_Table($this->styleList, $pClass);
    $this->tableArray[] =   $this->curTable;   
    $this->tablesOnPage =  count($this->tableArray);   
    $this->curTable->taHeading = $this->heading;   
}

function domTableEnd() {
    //$this->curTable->domTableEnd();
}

//********************************************************************
//* Row Methods 

function domRowStart($pDataType,$pClass='') {    
    //$this->curRow = new kcm_pageDOM_Row();
    $this->curRow = $this->curTable->tableAddRow($this->styleList, $pDataType, $pClass);
}

function domRowEnd() {
    //$this->curRow->rowEnd();
    //$this->curBottom = $this->curRow->bottom;
}

//********************************************************************
//* Cell Methods

function domCellStart($pClass,$pAttr) { 
    $this->curCell = new kcm_pageDOM_Cell($this->curRow->roDataType, $this->styleList, $pClass, $pAttr);
    $this->curCell->addSpans($pAttr);
    $this->curRow->roAddCell($this->curCell);
}
function domCellEnd() {
    // need to set row height, master column width ?
}
function domCellOfText($pClass,$pText,$pAttr='') {
    $this->domCellStart($pClass,$pAttr);   
    $this->domTextOut($pText); 
    $this->domCellEnd();   
}
function domCellOfItems($pClass,$pItems,$pAttr='') {
    $this->domCellStart($pClass,$pAttr);   
    $count = count($pItems);
    for ($i=0; $i<$count; $i=$i+2) {
        $class = $pItems[$i];
        $text = $pItems[$i+1];
        $tag = substr($class,0,3);
        $class = substr($class,4);
        $style = new kcmPage_styleItem($tag,$class,$this->curCell->ceStyle); 
        $style->importStyleSpec($this->styleList, $tag, $class, '');
        if ($tag==='div') 
            $this->curCell->addItemOut($style,$text);
        else if ($tag==='img') 
            $this->curCell->addImageOut($style,$text);
    }
    $this->domCellEnd();   
}

//  ****************
//  * Text Methods *
//  ****************
function domTextOut($pText) {
    $this->curCell->addTextOut($pText);
}
//function domTextDiv() {
//    $this->curCell->addTextDiv($pText, $this->styleList, $pClass, $pS//tyle);
//}

// **********************************************
//* Update

function domUpdate($pExport) {  //????? portrait or landscape ???????
    $this->paper->paUpdate($pExport);
    for ($tabIdx=0; $tabIdx<$this->tablesOnPage; $tabIdx++) {
       $curTable = $this->tableArray[$tabIdx];  
       $curTable->taUpdate($pExport,$this->paper);
    }   
}

function domPrintPageStart($pExport, $pTable) {
    $pExport->PageStart();
    // need page break if page>1 
    ++$pExport->exCurPageNum;
    $pExport->exCurPdfPosY = $this->paper->paTop;
    if ($pTable->taHeading!=NULL) {
        $pTable->taHeading->hePrint($pExport, $this->paper);
        //$pExport->exCurPdfPosY += $pTable->taHeading->hePdfHeight;
        //$pExport->exCurExcelRow += 1; //?????? 
    }    
    $this->domPrintTableStart($pExport, $pTable);
}
function domPrintTableStart($pExport, $pTable) {
    if ($pTable!=NULL) {  //????? maybe for only when page break ????
        for ($rowIdx=0; $rowIdx < $pTable->taHeaderExcelHeight; $rowIdx++) {
            $this->domPrintTableRow($pExport, $pTable, $rowIdx);
        }
    }    
}

// **********************************************
//* Print

function domPrintPageEnd($pExport, $pTable) {
}

function domPrintTableGrids($pExport, $pTable, $pFirstRow, $pLastRow) {
     //????? excel should use excel row not row for output ???????
    // print horizontal grids
    $lastRow = $pLastRow + 1;
    for ($rowIdx = $pFirstRow; $rowIdx <= $lastRow; $rowIdx++) {
        $startBorder = $pTable->taHorGrid[0][$rowIdx];
        $startX = ($pExport->exIsExcel) ? 0 : $pTable->taColLeftX[0];
        $endBorder = $startBorder;
        $endX      = ($pExport->exIsExcel) ? 1 : $pTable->taColLeftX[1];
        if ($rowIdx===$lastRow and $rowIdx>=1) {  //??????
            $curRow = $pTable->taRowArray[$rowIdx-1];  
            $y = ($pExport->exIsExcel) ? $rowIdx : $curRow->roPdfBottom;
        }    
        else {   
            $curRow = $pTable->taRowArray[$rowIdx];  
            $y = ($pExport->exIsExcel) ? $rowIdx : $curRow->roPdfTop;
        }    
        // $pExport->exCurExcelPageTop will be zero if not excel
        for ($colIdx=0; $colIdx < $pTable->taColCount; $colIdx++) {
            $nextBorder = $pTable->taHorGrid[$colIdx][$rowIdx];
            $nextX      = ($pExport->exIsExcel) ? $colIdx : $pTable->taColLeftX[$colIdx];
            if ($nextBorder!=$startBorder) {
                if ($startBorder!=0)
                    $pExport->exportBorderHor($startBorder,$startX,$endX,$y+$pExport->exCurExcelPageTop);
                $startBorder = $nextBorder;
                $startX = $nextX;
            }
            $endBorder = $nextBorder;
            $endX  = ($pExport->exIsExcel) ? $colIdx : $pTable->taColLeftX[$colIdx+1];
        }
        if ($startBorder!=0)
            $pExport->exportBorderHor($startBorder,$startX,$endX,$y+$pExport->exCurExcelPageTop);
    }
    // print vertical grids
    for ($colIdx=0; $colIdx <= $pTable->taColCount; $colIdx++) {
        $curRow = $pTable->taRowArray[0];  
        $startBorder = $pTable->taVerGrid[$colIdx][0];
        $startY = ($pExport->exIsExcel) ? 0 : $curRow->roPdfTop;
        $endBorder = $startBorder;
        $endY = ($pExport->exIsExcel) ? 1 : $curRow->roPdfBottom;
        for ($rowIdx = $pFirstRow; $rowIdx <= $pLastRow; $rowIdx++) {
            $nextBorder = $pTable->taVerGrid[$colIdx][$rowIdx];
            $curRow = $pTable->taRowArray[$rowIdx];  
            $nextY = ($pExport->exIsExcel) ? $rowIdx :$curRow->roPdfTop;
            if ($nextBorder!=$startBorder) {
                $left = ($pExport->exIsExcel) ? $colIdx : $pTable->taColLeftX[$colIdx];
                if ($startBorder!=0)
                    $pExport->exportBorderVer($startBorder,$left,$startY+$pExport->exCurExcelPageTop,$endY+$pExport->exCurExcelPageTop);
                $startBorder = $nextBorder;
                $startY = $nextY;
            }
            $endBorder = $nextBorder;
            $endY      = ($pExport->exIsExcel) ? $rowIdx :$curRow->roPdfBottom;
        }
        $left = ($pExport->exIsExcel) ? $colIdx : $pTable->taColLeftX[$colIdx];
        if ($startBorder!=0)
            $pExport->exportBorderVer($startBorder,$left,$startY,$endY);
    }
}

function domPrintTableRow($pExport, $pTable, $pRowIndex) {
    $curRow = $pTable->taRowArray[$pRowIndex];  
    $curRow->roPrint($pExport, $pTable);  //???????????? no such function 
    //$pExport->exCurExcelRow += 1; // $curRow->roPdfHeight;
}

function domPrintReport($pExport) {
    $this->domUpdate($pExport); // update table widths, grids, etc
    $pExport->exCurPageNum = 0;
    $pExport->exCurExcelRow = 0;
    $pExport->exCurPdfPosY = $this->paper->paBottom + 9999;
    //$pExport->exCurPdfPosY = $this->paper->paTop;
    for ($tabIdx=0; $tabIdx<$this->tablesOnPage; $tabIdx++) {
        $curTable = $this->tableArray[$tabIdx];  
        for ($colIdx=0; $colIdx < $curTable->taColCount; $colIdx++) {
            //????????????   doesn't work if multiple tables ?????????????? 
            // only relevant for excel
            $pExport->exportWidth($colIdx,$curTable->taColWidths[$colIdx]);
        }    
        $pExport->exCurTableRowStart = 0;
        $pExport->exCurTableRowEnd = 0; // $curTable->taRowCount;
        if ($pExport->exIsExcel) {  // no paging for excel (except one table per page)
            $pExport->exCurTableRowStart = 0;
            $pExport->exCurPdfPosY = $this->paper->paTop;
        }
        if ($pExport->exCurPageNum===0)   
            $this->domPrintPageStart($pExport,$curTable); 
        else if ($this->breakOnNewTable) 
            $this->domPrintPageStart($pExport,$curTable);
        for ($rowIdx = $curTable->taHeaderExcelHeight; $rowIdx < $curTable->taRowCount; $rowIdx++) {
            $curRow = $curTable->taRowArray[$rowIdx];  
            if (!$pExport->exIsExcel and $this->breakAutoPaging) { // no paging for excel
                if ($pExport->exCurPdfPosY + $curRow->roPdfHeight > $this->paper->paBottom) {
                    if ($pExport->exCurPageNum>=1) {
                        $this->domPrintTableGrids($pExport, $curTable, 0, $curTable->taHeaderExcelHeight-1);
                        $this->domPrintTableGrids($pExport, $curTable, $pExport->exCurTableRowStart, $pExport->exCurTableRowEnd);
                        $this->domPrintPageEnd($pExport,$curTable);  //??????????????
                        $pExport->exCurTableRowStart = $rowIdx; //???? or $pExport->exCurTableRowEnd + 1;  //??? or 
                    } 
                //    $pExport->exCurTableRowStart = $rowIndex;
                //    $pExport->exCurTableRowStart = $rowIndex - 1;
                    $pExport->exCurPdfPosY = $this->paper->paTop;
                    $this->domPrintPageStart($pExport,$curTable);
                }
            }    
            $this->domPrintTableRow($pExport, $curTable, $rowIdx);
            $pExport->exCurTableRowEnd = $rowIdx;    
        }    
        if (!$pExport->exIsExcel) {
            $this->domPrintTableGrids($pExport, $curTable, 0, $curTable->taHeaderExcelHeight-1);
        }    
        $this->domPrintTableGrids($pExport, $curTable, $pExport->exCurTableRowStart, $pExport->exCurTableRowEnd);
    }       
}

function exportClose($pFormat, $pDestFile, $pFontName='') {
    $export = new kcmPage_export($pFontName);
    if ($pFormat==='p') {
       $export->openPdf($pDestFile,$this->paper->paOrientation);
    }    
    else if ($pFormat==='e') {
        $export->openExcel($pDestFile);
    }
    else    
        $export->openDebug($pDestFile);
    //$this->domUpdate($pExport);
    $this->domPrintReport($export);
    if ($export!=NULL)
        $export->close();
}

}
//******************************************************************
//*  dom Paper (page) Class  // paper/page layout - not report layout
//* ==============

Class kcm_pageDOM_Paper {

public $paOrientation;
public $paStyle;
public $paLeft;   // start of printable area
public $paRight;  // end of printable area
public $paTop;    // start of printable area 
public $paBottom; // end of printable area
public $paPdfWidth;
public $paPdfHeight;

function __construct($pOrientation = rpPAGE_PORTRAIT) {
    $this->paStyle = new kcmPage_styleItem('body','');
    $this->paOrientation = $pOrientation;
}

//function paBodySet($pStyleList, $pClass, $pS//tyle='', $pInherited=NULL) {
//    $this->paStyle->importStyleSpec($pStyleList, 'body', $pClass, $pS//tyle);
//}

function paUpdate($pExport) {
    if ($this->paStyle->stWidth > 700) {
        $this->paOrientation = rpPAGE_LANDSCAPE;
        //$this->pOrientation = rpPAGE_LANDSCAPE;  //????? don't do both ways
        }
    $this->paPdfWidth = 612; // 72*8.5;
    $this->paPdfHeight = 792; // 72*11;
    if ($this->paOrientation===rpPAGE_LANDSCAPE) {
        $x = $this->paPdfWidth;
        $this->paPdfWidth = $this->paPdfHeight;
        $this->paPdfHeight = $x;
    }
    $this->paLeft = $this->paStyle->stLeft->ssMargin;
    if ($this->paLeft==0)
        $this->paLeft = 18; // 72 * .25
    $rightMargin = $this->paStyle->stRight->ssMargin;
    if ($rightMargin==0)
        $rightMargin = 18; // 72 * .25
    $this->paRight = $this->paPdfWidth - $rightMargin;
    $this->paTop = $this->paStyle->stTop->ssMargin;
    if ($this->paTop==0)
        $this->paTop = 18; // 72 * .25
    $botMargin = $this->paStyle->stBottom->ssMargin;
    if ($botMargin==0)
        $botMargin = 18; // 72 * .25
    $this->paBottom = $this->paPdfHeight - $botMargin;
}

}

//******************************************************************
//*  dom Heading
//* ==============

Class kcm_pageDOM_Heading {

public $heStyle;
public $hePdfHeight;     
public $heExcelHeight;     
public $heTextGroup0;  // column 1
public $heTextGroup1;  // column 2
public $heTextGroups;  
public $heLeft;
public $heRight;
     

function __construct($pInherited=NULL) {
    $this->heStyle = new kcmPage_styleItem('','',$pInherited);
    $this->heTextGroup0 = new kcm_pageDOM_textGroup($this->heStyle,TRUE);
    $this->heTextGroup1 = new kcm_pageDOM_textGroup($this->heStyle,TRUE);
    $this->heTextGroups = array ($this->heTextGroup0,$this->heTextGroup1);
    $this->heLeft = array(2);
    $this->heRight = array(2);
}

function heStart($pStyleList, $pClass) {
    //?????? 'kpageHeading'
    $this->heStyle->importStyleSpec($pStyleList, 'kpageHeading', $pClass);
}

function heEnd() {
    $this->heTextGroup0->tgAddItem($this->heStyle, '');
    $this->heTextGroup1->tgAddItem($this->heStyle, '');
}

function heAddText($pColumn,$pText) {
    $this->heTextGroups[$pColumn]->tgAddItem($this->heStyle, $pText);
}

function heUpdate($pExport,$pPaper) {
    $mid = $pPaper->paLeft + round( ($pPaper->paRight - $pPaper->paLeft) / 2 );
    $this->heLeft[0] = $pPaper->paLeft;
    $this->heLeft[1] = $mid+5;
    $this->heRight[0] = $mid - 5;
    $this->heRight[1] = $pPaper->paRight;
    $this->heTextGroup0->tgUpdate($pExport,$this->heLeft[0],$this->heRight[0]);
    $this->heTextGroup1->tgUpdate($pExport,$this->heLeft[1],$this->heRight[1]);
    $this->hePdfHeight = max($this->heTextGroup0->tgPdfHeight, $this->heTextGroup1->tgPdfHeight) 
          + $this->heStyle->stTop->ssPadding + $this->heStyle->stBottom->ssPadding;
    $this->heExcelHeight = max($this->heTextGroup0->tgExcelHeight, $this->heTextGroup1->tgExcelHeight);
}      

function hePrint($pExport,$pPaper) {
    $mid = $pPaper->paLeft + round( ($pPaper->paRight - $pPaper->paLeft) / 2 );
    //$this->heLeft[0] = $pPaper->paLeft;
    //$this->heLeft[1] = $mid+5;
    //$this->heRight[0] = $mid - 5;
    //$this->heRight[1] = $pPaper->paRight;
//???? Excel column index should be better    
//???? rect bottom should be better    
    $this->heTextGroup0->tgPrint($pExport,$pPaper->paLeft,$mid-10, $pExport->exCurPdfPosY+$this->heStyle->stTop->ssPadding, 999,1, $pExport->exCurExcelRow);
    $this->heTextGroup1->tgPrint($pExport,$mid+5, $pPaper->paRight, $pExport->exCurPdfPosY+$this->heStyle->stTop->ssPadding, 999, 2, $pExport->exCurExcelRow);
    $pExport->exCurPdfPosY += $this->hePdfHeight;
    $pExport->exCurExcelRow += $this->heExcelHeight;
}

}

//******************************************************************
//*  dom Table Class
//* ==============

Class kcm_pageDOM_Table {

public $taStyle;
public $taRowArray;
public $taRowCount;
public $taHeaderExcelHeight;
public $taHeaderPdfHeight;
public $taColWidths;
public $taColCount;
public $taColLeftX;
public $taGridHor;
public $taGridVer;
public $taHeading;
public $taPdfHeight;  // height of entire table - may be useful so can shrink if slight overflow

function __construct($pStyleList, $pClass) {
    $this->taStyle = new kcmPage_styleItem('',''); 
    $this->taStyle->importStyleSpec($pStyleList, 'table', $pClass);
    $this->taRowArray = array ();
    $this->taRowCount = 0;
    $this->taHeaderExcelHeight = 0;
}
function tableAddRow($pStyleList, $pType, $pClass) {
    $newRow = new kcm_pageDOM_Row($pStyleList, $pType, $pClass, $this->taStyle);
    $this->taRowArray[] = $newRow;
    if ($pType === 'h' and $this->taRowCount === $this->taHeaderExcelHeight) {
        ++$this->taHeaderExcelHeight;
    }    
    ++$this->taRowCount;
    return $newRow;
}

function taUpdateColCount($pExport) {
    // update number of columns (look at all the rows to get max columns)
    $this->taColCount = 0;
    for ($rowIdx = 0; $rowIdx < $this->taRowCount; $rowIdx++) {
        $rowCol = $this->taRowArray[$rowIdx]->roCellCount;
        if ($rowCol > $this->taColCount) {
            $this->taColCount = $rowCol;
        }    
    }        
    // one extra column at end for right border (so no boundary condition)
    $this->taColWidths = array($this->taColCount + 2);
    $this->taColLeftX = array($this->taColCount + 2);
}

function taUpdateHeader($pExport) {
    $this->taHeaderPdfHeight = 0;
    for ($rowIdx = 0; $rowIdx < $this->taHeaderExcelHeight; $rowIdx++) {
        $curRow = $this->taRowArray[$rowIdx];  
        $curRow->roUpdate($pExport,$this);  
        $this->taHeaderPdfHeight += $curRow->roPdfHeight;
    }    
}

function taUpdateCellSpans($pExport) {
    $spanReserved = array_fill ( 0, $this->taColCount+1, 0);
    for ($rowIdx=0; $rowIdx < $this->taRowCount; $rowIdx++) {
        $curRow = $this->taRowArray[$rowIdx];  
        $curCol = 0;
        for ($cellIdx = 0; $cellIdx < $curRow->roCellCount; $cellIdx++) {
            $curCell = $curRow->roCellArray[$cellIdx];
            while ( $spanReserved[$curCol]>=1) {
                --$spanReserved[$curCol];
                ++$curCol;
            }    
            $curCell->ceExcelColumn = $curCol; 
            //????? should catch error if extends past number of columns
            if ($curCell->ceRowSpan > 1) {
                 for ($i = $curCol; $i < $curCol + $curCell->ceColSpan; $i++) {
                      $spanReserved[$i] = $curCell->ceRowSpan - 1;
                 }
            }
            $curCol = $curCol  + $curCell->ceColSpan;
        }
    }
}    

function taUpdateColWidths($pExport,$pPaper) {
    // update taColWidths - look at each row 
    $this->taColWidths = array_fill(0, $this->taColCount+1, 0); 
    for ($rowIdx = 0; $rowIdx < $this->taRowCount; $rowIdx++) {
        $curRow = $this->taRowArray[$rowIdx];  
        for ($cellIdx = 0; $cellIdx < $curRow->roCellCount; $cellIdx++) {
            $curCell = $curRow->roCellArray[$cellIdx];
            if ($curCell->ceColSpan === 1) {
                $curCol = $curCell->ceExcelColumn;
                if ($curCell->ceStyle->stWidth > $this->taColWidths[$curCol])  {
                    $this->taColWidths[$curCol] = $curCell->ceStyle->stWidth;
                }    
            }           
        }
    }
    // if desired could shrink column widths here to fit paper
    $curX = $pPaper->paLeft;
    for ($colIdx = 0; $colIdx < $this->taColCount; $colIdx++) {
        $this->taColLeftX[$colIdx] = $curX;       
        //$this->taColLeftX[$colIdx+2] = $curX + $this->taColWidths[$colIdx];       
        $curX += $this->taColWidths[$colIdx];
        $this->taColLeftX[$colIdx+1] = $curX;       
        // the adjacent vertical borders of adjacent columns are the same vertical position
        // the same border line is shared among two adjacent columns
    }
}

function taUpdateGridHor($pExport,$pCol, $pRow, $pBorderCode) {
    $this->taHorGrid[$pCol][$pRow] = max($this->taHorGrid[$pCol][$pRow], $pBorderCode);
//    print '<br>.....r='.$pRow.' c= '. $pCol.' ,b= '. $pBorderCode;
}
function taUpdateGridVer($pExport,$pCol,$pRow,$pBorderCode) {
    $this->taVerGrid[$pCol][$pRow] = max($this->taVerGrid[$pCol][$pRow], $pBorderCode);
}

function taUpdateGrids($pExport) {
    //--- Create Grid arrays for all cells  
    //--- one larger than necessary so no boundary condition on last row and index past last 
    $this->taHorGrid = array_fill(0, $this->taColCount+1, array_fill(0, $this->taRowCount+1, 0));
    $this->taVerGrid = array_fill(0, $this->taColCount+1, array_fill(0, $this->taRowCount+1, 0));
    //--- Fill in the grids with borders
    for ($rowIdx = 0; $rowIdx < $this->taRowCount; $rowIdx++) {
        $curRow = $this->taRowArray[$rowIdx];  
        for ($cellIdx = 0; $cellIdx < $curRow->roCellCount; $cellIdx++) {
            $curCell = $curRow->roCellArray[$cellIdx];
            $leftCol = $curCell->ceExcelColumn;
            $rightCol = $leftCol + $curCell->ceColSpan;
            $topRow = $rowIdx;
            $bottomRow = $topRow + $curCell->ceRowSpan;
//print '<br>t= '. $topRow.' ,b= '. $bottomRow.' ,l= '. $leftCol.' ,r= '. $rightCol;
//print '<br>r='.$rowIdx. ' ,c= '.$cellIdx .' ,h= '. ($bottomRow- $topRow) .' ,w= '.($rightCol-$leftCol);            
            for ($cellColIdx = $leftCol; $cellColIdx < $rightCol; $cellColIdx++) {
                $this->taUpdateGridHor($pExport,$cellColIdx, $topRow, $curCell->ceStyle->stTop->ssBorderCode);
                $this->taUpdateGridHor($pExport,$cellColIdx, $bottomRow, $curCell->ceStyle->stBottom->ssBorderCode);
            }
            for ($cellRowIdx=$topRow; $cellRowIdx < $bottomRow; $cellRowIdx++) {
                $this->taUpdateGridVer($pExport,$leftCol, $cellRowIdx, $curCell->ceStyle->stLeft->ssBorderCode);
                $this->taUpdateGridVer($pExport,$rightCol, $cellRowIdx, $curCell->ceStyle->stRight->ssBorderCode);
            }
        }
    }    
}

function taUpdate($pExport,$pPaper) {
    $this->taUpdateHeader($pExport);
    $this->taUpdateColCount($pExport);
    $this->taUpdateCellSpans($pExport);
    $this->taUpdateColWidths($pExport,$pPaper);
    $this->taUpdateGrids($pExport);
    $this->taPdfHeight = 0;
    if ($this->taHeading!=NULL) {
        $this->taHeading->heUpdate($pExport,$pPaper);
        $this->taPdfHeight += $this->taHeading->hePdfHeight;
    }    
    for ($rowIdx=0; $rowIdx < $this->taRowCount; $rowIdx++) {
        $curRow = $this->taRowArray[$rowIdx];  
        $curRow->roUpdate($pExport,$this);  
        $this->taPdfHeight += $curRow->roPdfHeight;
    }    
}

}

//******************************************************************
//*  dom Row Class
//* ==============

Class kcm_pageDOM_Row {
public $roCellArray;
public $roCellCount;  // number of actual cells, spanned cells count as 1
public $roStyle;
public $roDataType;   // d=data, h=header   (enhancement: o=optional footer)
// computed later
public $roPdfHeight;   
public $roPdfTop;
public $roPdfBottom;
public $roExcelHeight; 
public $roExcelTop;
public $roExcelBottom;
  
function __construct($pStyleList, $pType, $pClass, $pInherited=NULL) {
    $this->roStyle = new kcmPage_styleItem('tr','',$pInherited); 
    $this->roStyle->importStyleSpec($pStyleList, 'tr', $pClass);
    $this->roCellArray = array();
    $this->roDataType = $pType;
}

function roAddCell($pCell) {
    $this->roCellArray[] = $pCell;
    $this->roCellCount = count($this->roCellArray);
}
function roUpdate($pExport, $pTable) { 
    $this->roPdfHeight = 0;
    $this->roExcelHeight = 0;
    for ($cellIdx = 0; $cellIdx < $this->roCellCount; $cellIdx++) {
        $curCell = $this->roCellArray[$cellIdx];
//            $pTable->taColLeftX[$col1],
//            $pTable->taColLeftX[$col2+1],
        $leftCol = $curCell->ceExcelColumn;  
        $rightCol = $curCell->ceExcelColumn + $curCell->ceColSpan;  
        $curCell->ceUpdate($pExport, $pTable->taColLeftX[$leftCol],$pTable->taColLeftX[$rightCol]);  
        $this->roPdfHeight = max($this->roPdfHeight,$curCell->cePdfHeight);
        $this->roExcelHeight = max($this->roExcelHeight,$curCell->ceExcelHeight);
    }    
}
function roPrint($pExport, $pTable) { 
    $this->roPdfTop = $pExport->exCurPdfPosY;
    $this->roPdfBottom = $this->roPdfTop + $this->roPdfHeight;
    $this->roExcelTop = $pExport->exCurExcelRow;
    $this->roExcelBottom = $pExport->exCurExcelRow + 1; // ????? max excelHeight????
//            $this->roPdfBottom =  $this->roPdfTop + $this->roPdfHeight;
    for ($cellIdx = 0; $cellIdx < $this->roCellCount; $cellIdx++) {
        $curCell = $this->roCellArray[$cellIdx];
        $col1 = $curCell->ceExcelColumn;
        $col2 = $col1 + $curCell->ceColSpan - 1;
        $curCell->cePrintCell ($pExport,
            $pTable->taColLeftX[$col1],
            $pTable->taColLeftX[$col2+1],
            $this->roPdfTop, 
            $this->roPdfBottom, 
            //$pExport->exCurPdfPosY,   //???? different cells may have different top padding
            //$pExport->exCurPdfPosY + $curCell->cePdfHeight,  //???? or row height ???
            $col1,
            $pExport->exCurExcelRow);       
    }    
    $pExport->exCurPdfPosY += $this->roPdfHeight;
    $pExport->exCurExcelRow  += $this->roExcelHeight; 
}

}



//******************************************************************
//*  dom Cell Class
//* ==============

Class kcm_pageDOM_Cell {
public $ceStyle;
public $ceSpanIsActive;   //true if any spans   
public $ceTextGroup;  // text section(s)
public $ceColSpan;  // usually 1  unless colspan
public $ceRowSpan; // usually 1  unless colspan
public $cePdfHeight;     
public $ceExcelHeight;     
public $ceLeft;    // position on page
public $ceRight;   // position on page
public $ceTop;     // position on page
public $ceBottom;  // position on page
public $ceExcelRow;  
public $ceExcelColumn;   // set when table is "columized", same as column
   
function __construct($pRowType, $pStyleList, $pClass, $pAttr='', $pInherited=NULL) {
   $this->ceStyle = new kcmPage_styleItem('td','',$pInherited); 
   if ($pRowType==='h')
       $tag = 'th';
   else   
       $tag = 'td';
   $this->ceStyle->importStyleSpec($pStyleList, $tag, $pClass);
   //--- computer later
   $this->ceSpanIsActive = FALSE;
   $this->ceColSpan  = 1;
   $this->ceRowSpan = 1;
   $this->ceSpanIsActive = FALSE;
   $this->ceTextGroup = new kcm_pageDOM_textGroup($this->ceStyle);
}
function parseAttr($pAttr) {
    $out = array(1,1);
    $tokens = array();
    $result = preg_match_all(kcmATTRREGEXP,$pAttr, $tokens, PREG_PATTERN_ORDER);
    $a = array_slice($tokens[0],0);
    for ($i=0; $i<count($a); $i=$i+2) {
       $c=$a[$i];
       if ($c==='colspan')
          $out[0] = $a[$i+1];
       else if ($c==='rowspan')
          $out[1] = $a[$i+1];
    }
    return $out;   
}

function addSpans($pAttr='') {
    if ($pAttr==='') 
        return;
    $out = $this->parseAttr($pAttr);
    if ($out[0]<=1 and $out[1]<=1)  
        return;
    $this->ceSpanIsActive = TRUE;
    $this->ceColSpan  =  $out[0];
    $this->ceRowSpan =  $out[1];
}

function addTextOut($pText) {
   $this->ceTextGroup->tgAddItem($this->ceStyle,$pText);
}
function addItemOut($pStyleItem,$pText) {
   $this->ceTextGroup->tgAddItem($pStyleItem,$pText);
}
function addImageOut($pStyleItem,$pImageUrl) {
   $this->ceTextGroup->tgAddImage($pStyleItem,$pImageUrl);
}
function addTextBreak() {
   $this->ceTextGroup->tgAddItem($this->ceStyle,'');
}
//function addTextDiv($pText, $pStyleList, $pClass, $pS//tyle, $pInherited=NULL) {
////   $curDiv = new kcm_pageDOM_TextItem();
//   $this->textArray[] = $curDiv;
//   $this->textCount = count($this->textArray);
//   $curDiv->command = 't';
//   $curDiv->text = $pText;
//   $curDiv->style = new kcmPage_styleItem($pInherited); 
//   $curDiv->style->importStyleSpec($pStyleList, 'div', $pClass, $pS//tyle);
//}
//
function ceUpdate($pExport, $pLeft, $pRight) {
   $this->ceTextGroup->tgUpdate($pExport, $pLeft, $pRight);
   if ($this->ceStyle->stHeight>0) {
       $this->cePdfHeight = $this->ceStyle->stHeight;
   }    
   else    
       $this->cePdfHeight = $this->ceTextGroup->tgPdfHeight;
   $this->ceExcelHeight = $this->ceTextGroup->tgExcelHeight;
}

function cePrintCell ($pExport, $pLeft, $pRight, $pTop, $pBottom, $pExcelCol, $pExcelRow ) { 
    // borders will be printed later
    $this->ceLeft = $pLeft;   
    $this->ceRight = $pRight; 
    $this->ceTop = $pTop;     
    $this->ceBottom = $pBottom;
    $this->ceExcelColumn = $pExcelCol;
    $this->ceExcelRow = $pExcelRow;
    if ($pExport->exIsExcel) {
        if ($this->ceColSpan!=1 or $this->ceRowSpan!=1) {
            $pExport->exportMerge($this->ceExcelColumn,$this->ceExcelColumn+$this->ceColSpan-1,
                      $this->ceExcelRow,$this->ceExcelRow+$this->ceRowSpan-1);
        }    
    }
    $this->ceTextGroup->tgPrint( $pExport,
        $pLeft + $this->ceStyle->stLeft->ssPadding,   
        $pRight - $this->ceStyle->stRight->ssPadding, 
        $pTop + $this->ceStyle->stTop->ssPadding,     
        $pBottom - $this->ceStyle->stBottom->ssPadding,
        $pExcelCol,
        $pExcelRow);
}

}

//******************************************************************
//*  dom Cell Text Group Class
//* ==============

Class kcm_pageDOM_textGroup {
public $tgItemArray;
public $tgStyle;
public $tgItemsAreRows;  // currently only used for heading
public $tgPdfHeight;
public $tgExcelHeight;  // for excel 


function __construct($pCellStyle, $pItemsAreRows=FALSE) {
    $this->tgItemArray = array ();
    $this->tgStyle = $pCellStyle;
    $this->tgItemsAreRows = $pItemsAreRows;
    $this->tgExcelHeight = 0;
}

function tgAddItem ($pStyleItem,$pText) {
    if (strPos($pText,'<br>') === FALSE) {
        $this->tgItemArray[] = new kcm_pageDOM_textItem ($pStyleItem, $pText);
    }    
    else {
        $ar = explode('<br>', $pText);
        for ($i = 0; $i < count($ar); $i++) {
            $this->tgItemArray[] = new kcm_pageDOM_textItem ($pStyleItem, $ar[$i]);
        }    
    }    
}

function tgAddImage ($pStyleItem,$pUrl) {
    $this->tgItemArray[] = new kcm_pageDOM_textItem ($pStyleItem, $pUrl,'i');
}

function tgUpdate($pExport, $pLeft, $pRight) {
    $this->tgPdfHeight = $this->tgStyle->stTop->ssPadding + $this->tgStyle->stBottom->ssPadding;
    $pLeft += $this->tgStyle->stLeft->ssPadding;
    $pRight -= $this->tgStyle->stRight->ssPadding;
    for ($i = 0; $i < count($this->tgItemArray); $i++) {
        $textItem = $this->tgItemArray[$i];
        $textItem->tiUpdate($pExport, $pLeft, $pRight);
        // enhancement - if float then put next text item horizontally (will need to calc text width)
        // enhancement - support word wrap if specific style is specified  (will need to calc text height)
        $this->tgPdfHeight += $textItem->tiHeight;
        if ($this->tgItemsAreRows)    
            $this->tgExcelHeight = count($this->tgItemArray);
        else   
            $this->tgExcelHeight = 1;
    }
}

function tgPrint($pExport, $pLeft, $pRight, $pTop, $pBottom, $pExcelCol, $pExcelRow) {
    $nextTop = $pTop;
    $left = $pLeft;
    $right = $pRight;
    $top = $pTop;
    $bottom = $pBottom;
    for ($i = 0; $i < count($this->tgItemArray); $i++) {
        $txItem = $this->tgItemArray[$i];
        if ($txItem->tiStyle->stPosition==='absolute') {
            $y = $pTop - 25;   //???????????????????????????????
        }    
        else {
            $y = $top; 
            $top += $txItem->tiHeight; 
        }
        $txItem->tiPrint($pExport, $left, $right, $y, $y+$txItem->tiHeight, $pExcelCol, $pExcelRow+$i);
        // enhancement - if float then put next text item horizontally (will need to calc text width)
        // enhancement - support word wrap if specific style is specified  (will need to calc text height)
    }
}

}

//******************************************************************
//*  dom border Class
//* ?????? not yet used 
//* ==============

Class kcm_pageDOM_Borders {
    public $horX1;
    public $horX2;
    public $horY;
    public $horStyle;
    public $horCount;
    public $verX;
    public $verY1;
    public $verY2;
    public $verStyle;
    public $verCount;
    
function __construct() {
    $this->boClear();
}

function boClear() {
    $this->horX1 = array();
    $this->horX2 = array();
    $this->horY = array();
    $this->horStyle = array();
    $this->horCount = 0;
    $this->verX = array();
    $this->verY1 = array();
    $this->verY2 = array();
    $this->verStyle = array();
    $this->verCount = 0;
}

function boAddHorBorder($y, $x1, $x2, $style) {
    if ( ($style==0) or ($y==0) or ($x1==0) or ($x2==0))
        return;
    $this->horX1[] = $x1;
    $this->horX2[] = $x2;
    $this->horY[] = $y;
    $this->horStyle[] = $style;
    ++$this->horCount;
}
function boAddVerBorder($x, $y1, $y2, $style) {
    if ( ($style==0) or ($x==0) or ($y1==0) or ($y2==0))
        return;
    $this->verY1[] = $y1;
    $this->verY2[] = $y2;
    $this->verX[] = $x;
    $this->verStyle[] = $style;
    ++$this->horCount;
}
function boPageMerge() {
    // will work without merging - just larger when lines not combined 
    //array_multisort($this->horY,$this->horX1,$this->horX2,$this->horStyle);
    //array_multisort($this->verX,$this->verY1,$this->verY2,$this->horStyle);
    //for ($i=0; $i<$this->horCount; ++$i) {
    //}
}

}

//******************************************************************
//*  dom Cell Text Item Class
//* ==============

Class kcm_pageDOM_textItem {
    public $tiText;
    public $tiLines;  // text array for multi-line type
    public $tiStyle;
    
    public $tiFontHeight;
    public $tiFontWeight;
    public $tiLineHeight;
    public $tiType;  // t=text, i=image, m=multi-line text (already justified)
    
    public $tiHeight; 
    public $tiLeft; 
    public $tiRight;
    public $tiTop;    
    public $tiBottom; 
    public $tiPosLeft; 
    public $tiPosRight;
    public $tiPosWidth;    
    public $tiPosHeight; 
    public $tiAlignHor;
    public $tiAlignVer;
    public $tiWrap;
    public $tiMinFontHeight;

function __construct($pStyleItem, $pText, $pType='t') {
    $this->tiText = $pText;
    $this->tiStyle = $pStyleItem;
    $this->tiType = $pType;
    $this->tiLines = NULL;
    $this->tiFontHeight = $pStyleItem->stFontHeight;
    $this->tiMinFontHeight = $pStyleItem->stMinFontHeight;
    if ($pStyleItem->stFontWeight==='bold')
        $this->tiFontWeight = 'b';
    else    
        $this->tiFontWeight = '';
    $this->tiPosLeft = $this->tiStyle->stItemLeft;
    $this->tiPosTop  = $this->tiStyle->stItemTop;
    $this->tiPosHeight = $this->tiStyle->stHeight;
    $this->tiPosWidth = $this->tiStyle->stWidth;
    $this->tiAlignHor = $this->tiStyle->stAlignHor;
    $this->tiAlignVer = $this->tiStyle->stAlignVer;
    $this->tiWrap = $this->tiStyle->stWrap;
    if ($this->tiStyle->stLineHeight>=1)
        $this->tiLineHeight = $this->tiStyle->stLineHeight;
    else   
        $this->tiLineHeight = $this->tiStyle->stFontHeight;
}

function tiUpdate($pExport, $pLeft, $pRight) {
    $this->tiLeft = $pLeft;
    if ($this->tiPosWidth!=0)
        $this->tiRight = $pLeft + $this->tiPosWidth; 
    else
        $this->tiRight = $pRight; 
    $this->tiHeight = $this->tiLineHeight; 
    if ($pExport->exIsPdf and $this->tiType!=='i')
        $pExport->justify($this);
    //$this->tiHeight = $this->tiLineHeight; 
//if (is_array($this->tiText))
//    $s=$this->tiText[0];
//else   
//    $s=$this->tiText;
    // ???? will need to compute multiple lines (word wrap)
}

function tiPrint($pExport, $pLeft, $pRight, $pTop, $pBottom, $pExcelCol, $pExcelRow) {
    $top = $this->tiStyle->stItemTop;
    if ($top!==0) {
        $pTop = $pTop + $top;
    }
    $this->tiTop = $pTop;
    $left = $this->tiPosLeft;
    if ($left!==0) {
        $pLeft = $pLeft + $left;
    }
    $this->tiLeft = $pLeft;
    $width = $this->tiPosWidth;
    if ($width!==0) {
        $pRight = $pLeft + $width;
    }
    else if ($this->tiType==='i')
        $pRight = $pLeft;  // so width will be zero
    $this->tiRight = $pRight;
    $height = $this->tiPosHeight;
    if ($height!==0) {
        $pBottom = $pTop + $height;
    }
    else if ($this->tiType==='i')
        $pBottom = $pTop;   // so height will be zero
    $this->tiBottom = $pBottom;
    if ($this->tiType==='i') 
        $pExport->exportImage($this, $pExcelCol, $pExcelRow);
    else
        $pExport->exportText($this, $pExcelCol, $pExcelRow);
}

}
?>