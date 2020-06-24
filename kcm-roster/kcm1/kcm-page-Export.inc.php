<?php
//   kcm-pageExport.inc.php

// to do - add font function for name labels

//include_once('lib/reportClasses.inc.php');

const kcm_MINFONTHEIGHT = 7;

const rpOUTPUT_HTML = 1;
const rpOUTPUT_EXCEL = 2;
const rpOUTPUT_PDF = 3;

const rpPAGE_PORTRAIT = 22;
const rpPAGE_LANDSCAPE = 23;

const rpSTYLE_FONT_NORMAL = 2;
const rpSTYLE_FONT_BOLD   = 3;
const rpSTYLE_ALIGN_LEFT = 7;
const rpSTYLE_ALIGN_CENTER = 8;
const rpSTYLE_ALIGN_RIGHT  = 9;
const rpSTYLE_ALIGN_TOP    = 10;
const rpSTYLE_ALIGN_MIDDLE = rpSTYLE_ALIGN_CENTER;
const rpSTYLE_ALIGN_BOTTOM = 11;

const rpBORDER_NONE = 1;   // (same effect as null - but parent values are overridden)
const rpBORDER_THIN = 2;
const rpBORDER_GRAY = 3;
const rpBORDER_BLACK = 4;
const rpBORDER_THICK = 5;


//*****************************************************    
//*****************************************************    
//** Export Class
     
Class kcmPage_export {
public $exIsPdf;
public $exIsExcel;
public $exIsDebug;
public $exDestFile;
public $exCurTableRowStart;
public $exCurTableRowEnd;
public $exCurExcelRow;
public $exCurPdfPosY;
private $exPdfLib;
private $exExcelLib;
public $exCurExcelPageTop;
public $exCurPageNum;
private $exCurFontName;
private $exCurFontHeight;
private $exCurFontWeight;
private $debLine;
private $borderLineColor;
private $borderLineWidth;
private $charSizeNormal; 
private $charSizeBold; 

function __construct($pFontName = '') {
    $this->exIsPdf = FALSE;
    $this->exIsExcel = FALSE;
    $this->exIsDebug = FALSE;
    $this->exCurFontHeight = 0;
    $this->exCurFontWeight = '';
    $this->debLine = 0;
    $this->exCurExcelPageTop = 0;
    if ($pFontName == '')
        $pFontName = 'helvetica';
    $this->exCurFontName = $pFontName;
}

function openPdf($pDestFile, $pOrientation=rpPAGE_PORTRAIT) {
    // require_once('../lib/fpdf/fpdf.php');  //@JPR-2019-11-04 21:55 
    $this->borderLineColor = array (255, 220, 170,  40,   0);
    //$this->borderLineColor = array (255, 255, 255,  255,   0);
    $this->borderLineWidth = array (0, 0.7, 0.8, 1.5, 2.0);
    $this->exIsPdf = TRUE;
    $this->exDestFile = $pDestFile;
    $this->pOrientation = $pOrientation;
    define('FPDF_FONTPATH',"lib/fpdf/font");
    if ($pOrientation==rpPAGE_LANDSCAPE)
        $orCode="L";
    else
        $orCode="P";
    $this->exPdfLib = new FPDF($orCode,'pt','Letter');
    $this->exPdfLib->setMargins(1,1);
    $this->exPdfLib->setDisplayMode(75);
    $this->exPdfLib->addPage();  // start of first page
    $this->exPdfLib->SetLeftMargin(12);
    $this->exPdfLib->SetTopMargin(12);
    $this->reportAddFont($this->exCurFontName);
    $this->charSizeNormal = array_fill ( 0, 255, 0);
    $this->charSizeBold = array_fill ( 0, 255, 0);
    // set font as current info - height of 10
    $this->exPdfLib->SetFont($this->exCurFontName,'',10);
    for ($i=32; $i<=127; $i++)
        $this->charSizeNormal[$i] = $this->exPdfLib->GetStringWidth(chr($i));
    $this->exPdfLib->SetFont($this->exCurFontName,'b',10);
    for ($i=32; $i<=127; $i++)
        $this->charSizeBold[$i] = $this->exPdfLib->GetStringWidth(chr($i));
}
function openExcel($pDestFile, $pOrientation=rpPAGE_PORTRAIT) {
    $excelLineColor = array ('FFFFFF','555555','333333','222222','000000');
    $this->borderLineWidth = array (
            PHPExcel_Style_Border::BORDER_THIN,
            PHPExcel_Style_Border::BORDER_THIN,
            PHPExcel_Style_Border::BORDER_THIN,
            PHPExcel_Style_Border::BORDER_MEDIUM,
            PHPExcel_Style_Border::BORDER_THICK
            );
    $this->borderLineColor = array(5);
    for ($i = 0; $i<5; $i++) 
        $this->borderLineColor[$i] = new PHPExcel_Style_Color($excelLineColor[$i]);
    $this->exIsExcel = TRUE;
    $this->exDestFile = $pDestFile;
    $this->pOrientation = $pOrientation;
    $this->exExcelLib = new rc_Excel;
    $this->exExcelLib->openForWriting();
    $this->exExcelLib->newSheet('Page 1');
    //for ($i=1; $i<$this->layout->columnCount; $i++)
    //    $this->output->exExcelLib->columnSetWidth($i-1,intval($this->layout->columnList[$i]->colWidthInPoints / 5) );
//$this->exportText('','abc', 0, 0, 0, 0, 2, 2);    
//$this->close();
//exit;    
}
function excelCellCord($pColumn, $pRow) {
    $c = chr(ord('A')+$pColumn-1);
    //????? need to fix for more than 26 columns
    $n = strval($pRow);
    return $c.$n;
}   

function openDebug($pDestFile, $pOrientation=rpPAGE_PORTRAIT) {
    $this->exIsDebug = TRUE;
    $this->exDestFile = $pDestFile;
    //for ($i=1; $i<$this->layout->columnCount; $i++)
    //    $this->output->exExcelLib->columnSetWidth($i-1,intval($this->layout->columnList[$i]->colWidthInPoints / 5) );
}
function close () {
    //$this->pageEnd();
    if ($this->exIsPdf) {
        $this->exPdfLib->output($this->exDestFile.'.pdf','I');
    }    
    if ($this->exIsExcel) {
        $this->exExcelLib->saveToDownload($this->exDestFile.'.xlsx');
    }    
}


//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@  Report Methods (start, end, etc)
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function reportAddFont($pFontName, $pBold=FALSE) {
    if (!$this->exIsPdf) 
        return;
    if ($pBold)
        $attr = "EggButtonBack";
    else
        $attr = "";
    if ($pFontName==='helvetica') {    
        $this->exPdfLib->AddFont('helvetica','','helvetica.php');
        $this->exPdfLib->AddFont('helvetica','B','helveticab.php');
    }    
    if ($pFontName==='times') {    
        $this->exPdfLib->AddFont('times','','times.php');
        $this->exPdfLib->AddFont('times','B','timesb.php');
    }    
    if ($pFontName==='comic') {    
        $this->exPdfLib->AddFont('comic','','comic.php');
        $this->exPdfLib->AddFont('comic','B','comicbd.php');
    }    
}
function reportDefineEnd() {
    if ($this->exIsExcel) {
        $this->exExcelLib = new rc_Excel;
        $this->exExcelLib->openForWriting();
        $this->exExcelLib->newSheet('Page 1');
        //for ($i=1; $i<$this->layout->columnCount; $i++)
        //    $this->exExcelLib->columnSetWidth($i-1,intval($this->layout->columnList[$i]->colWidthInPoints / 5) );
    }        
    if ($this->exIsPdf) {
        $this->exPdfLib->addPage();  // start of first page
        $this->exPdfLib->SetFont('helvetica','',12);
        $this->exPdfLib->SetLeftMargin($this->page->printAreaLeft);
        $this->exPdfLib->SetTopMargin($this->page->printAreaTop);
    }    
}
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@  Page Methods 
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function PageStart() {
    if ($this->exIsExcel) {
        $this->exCurExcelPageTop = ++$this->exCurExcelRow;
        if ($this->exCurPageNum>=1) {
            $start = $this->excelCellCord(1, $this->exCurExcelPageTop-1);
            $this->exExcelLib->activeSheet->setBreak($start, PHPExcel_Worksheet::BREAK_ROW );
        }
    }    
    if ($this->exIsPdf) {
        if ($this->exCurPageNum>=1) {
            $this->exPdfLib->AddPage();
        }    
    }        
}

function PageEnd() {
    //if (function_exists ('rpt_OnFooter'))
    //    rpt_OnFooter();
    //$this->drawBuffer->flushAll();
   // page break is at start of page - nothing to do (subject to change)
}

function exportWidth($pColumn, $pWidth) {
    if ($this->exIsExcel) {
        if ($pWidth < 10)  //???????????????????
            $wid = 50;
        else if ($pWidth < 20) //???????????????????
            $wid = 35;
        else    
            $wid = 20;
        $this->exExcelLib->columnSetWidth($pColumn,intval(($pWidth * $wid)/ 100) );  //????? 24
    }
}

function exportMerge($pLeft, $pRight, $pTop, $pBottom) {
    if ($this->exIsExcel) {
        $c=$this->excelCellCord($pLeft+1,$pTop+1).':'.$this->excelCellCord($pRight+1, $pBottom+1);
    //$this->exExcelLib->cellSet(0,0, $c);
        
        $this->exExcelLib->activeSheet->mergeCells($c);
    }
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@  Table Methods 
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function justify($pText) {
    if (!$this->exIsPdf)
        return;
    $pText->tiType = 't';
    $width = $pText->tiRight - $pText->tiLeft+4;  // a little wiggle room    
    $height = $pText->tiBottom - $pText->tiTop;    
    if ($pText->tiFontHeight !== $this->exCurFontHeight or $pText->tiFontWeight !== $this->exCurFontWeight) 
        $this->exPdfLib->setFont($this->exCurFontName,$pText->tiFontWeight,$pText->tiFontHeight);
    $textWidth = $this->exPdfLib->GetStringWidth($pText->tiText);
    if ($textWidth < $width)
        return; 
    $newHeight = ($pText->tiFontHeight * $width) / $textWidth;
    if ($pText->tiMinFontHeight >= 4)
       $minFontHeight = $pText->tiMinFontHeight;
    else   
       $minFontHeight = kcm_MINFONTHEIGHT;
    if ($newHeight >= $minFontHeight) {  
        // string fits if  smaller font is used 
        $pText->tiFontHeight = $newHeight;
        return; 
    }    
    if ($pText->tiWrap==='nowrap') {  // truncate string to fit (approximate based on ratio)
        $newWidth = ($minFontHeight * $textWidth) / $pText->tiFontHeight;
        $pText->tiFontHeight = $minFontHeight;
        $newLen = ($width * strlen($pText->tiText)) / $newWidth;
        $pText->tiText = substr($pText->tiText,0,round($newLen));
        return;
    }
    if ($pText->tiFontWeight==='b')
        $sizes = $this->charSizeBold;
    else    
        $sizes = $this->charSizeNormal;
    $srcStr = $pText->tiText . ' ';
    $srcLen = strlen($srcStr);
    $wordStart = 999999;
    $wordWidth = 0;
    $wordEnd = 0;
    $dest = array();
    $fitStr = '';
    $fitWidth = '';
    // calculate normalized width (based on font height of 10)
    $normWidth = (10 * $width) / $pText->tiFontHeight;
    for ($i = 0; $i<$srcLen; ++$i) {
        $c = substr($srcStr,$i,1);
        if ($c>' ') {
            if ($wordStart===999999)
               $wordStart = $i;
            $wordEnd = $i;
            $wordWidth += $sizes[ord($c)];
            if ($wordWidth>=$normWidth) {
                if ($fitStr!='') {
                    $dest[] = $fitStr;
                    $fitStr = '';
                    $fitWidth = 0;
                }
                $wordStr = substr($srcStr,$wordStart,$wordEnd-$wordStart+1);
                $dest[] = $wordStr;
                $wordWidth = 0;
                $wordStart = 999999;
            }    
        }
        else if ($wordStart!==999999) {
            $wordStr = substr($srcStr,$wordStart,$wordEnd-$wordStart+1);
            if ($fitWidth===0 and $wordWidth>=$normWidth) {
                //$dest[] = $wordStr;
                $fitStr = $wordStr;
                $fitWidth = $wordWidth;
                $wordStr = '';
                $wordWidth = 0;
                $wordStart = 999999;
            }
            if ($fitStr==='')
                $spaceWidth = 0;
            else    
                $spaceWidth = $sizes[ord(' ')];
            $newWidth = $fitWidth + $wordWidth + $spaceWidth;    
            if ($newWidth <= $normWidth+4) {
                if ($fitStr==='')
                    $fitStr .= $wordStr;
                else    
                    $fitStr .= ' ' . $wordStr;
                $fitWidth = $newWidth;
            }
            else {
                $dest[] = $fitStr;
                $fitStr = $wordStr;
                $fitWidth = $wordWidth;
            }
            $wordWidth = 0;
            $wordStart = 999999;
        }    
        
    $pText->tiLines = $dest;
    $pText->tiType = 'm';  // multi-line array
    $pText->tiHeight = ($pText->tiFontHeight+1) * count($dest);  // multi-line array
    }
}

function exportImage($pText, $pExcelCol, $pExcelRow) {
    if ($this->exIsExcel) 
        return;
    $width = $pText->tiRight - $pText->tiLeft;    
    $height = $pText->tiBottom - $pText->tiTop;    
    $this->exPdfLib->image($pText->tiText,$pText->tiLeft,$pText->tiTop,$width, $height);
}

function exportMultiLinePdf($pText) {
    $top = $pText->tiTop;
    for ($i = 0; $i<count($pText->tiLines); ++$i) {
       $s = $pText->tiLines[$i];
       $this->exPdfLib->text($pText->tiLeft, $top+$pText->tiFontHeight, $s);
       $top += $pText->tiFontHeight+1;
       }
}

function exportText($pText,  $pExcelCol, $pExcelRow) {
    if ($this->exIsExcel) {
        //if ($this->status->column_Span>1) {
        //    $start = $this->excelCellCord($this->status->column_Index, $this->status->row_Index+1);
        //    $end   = $this->excelCellCord($this->status->column_Index+$this->status->column_Span-1, $this->status->row_Index+1);
        //    $this->exExcelLib->activeSheet->mergeCells($start.":".$end);
        //}    
        $fontBold = ($pText->tiFontWeight==='bold');
        $c = $this->excelCellCord($pExcelCol+1,$pExcelRow+1);
        $this->exExcelLib->activeSheet->getStyle($c)->getFont()->setSize($pText->tiFontHeight); 
        $this->exExcelLib->activeSheet->getStyle($c)->getFont()->setBold($fontBold); 
        if ($pText->tiAlignHor==='right') {
            $this->exExcelLib->activeSheet->getStyle($c)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        }
        else if ($pText->tiAlignHor==='center') {
            $this->exExcelLib->activeSheet->getStyle($c)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        $this->exExcelLib->cellSet($pExcelCol,$pExcelRow, $pText->tiText);
    }    
    else if ($this->exIsPdf) {
        if ($pText->tiType==='i') 
            return;
        $cellWidth = $pText->tiRight - $pText->tiLeft + 1;
        //$this->justify($pText);  ???? moved to update
        $fontHeight = $pText->tiFontHeight;
        if ( $fontHeight !== $this->exCurFontHeight or $pText->tiFontWeight !== $this->exCurFontWeight) 
            $this->exPdfLib->setFont($this->exCurFontName,$pText->tiFontWeight,$fontHeight);
        $i = 0; 
        $top = $pText->tiTop;
        do  {      
            if ($pText->tiType==='m')
                $s = $pText->tiLines[$i];
            else    
                $s = $pText->tiText;
            $left = $pText->tiLeft;    
            if ($pText->tiAlignHor==='right') {
                $textWidth = $this->exPdfLib->GetStringWidth($s);
                $left = max($left, $pText->tiRight - $textWidth - 5);  //???? 5 is finagle factor
            }
            else if ($pText->tiAlignHor==='center') {
                $textWidth = $this->exPdfLib->GetStringWidth($s);
                $left = max( $left, $pText->tiLeft + (($cellWidth - $textWidth) / 2));
            }
            $this->exPdfLib->text($left, $top+$pText->tiFontHeight, $s);
            $top += $pText->tiFontHeight+1;
            $i++;
        } while ($pText->tiType==='m' and $i<count($pText->tiLines));    
    }    
    else if ($this->exIsDebug) {
       echo '<br>TEXT'; 
       $this->exDebug('text',$pText->tiText); 
       $this->exDebug('excel_Col',$pExcelCol); 
       $this->exDebug('excel_Row',$pExcelRow); 
       $this->exDebug('left',$pText->tiLeft); 
       $this->exDebug('right',$pText->tiRight); 
       $this->exDebug('top',$pText->tiTop); 
       $this->exDebug('bottom',$pText->tiBottom); 
       $this->exDebug('font-Height',$pText->tiFontHeight); 
    }   
}

function CustomCell_Text_Pdf() {
    $fontSize = $this->temp_custCellFont;
    $maxWidth = $this->temp_custCellWidth;
    if ($fontSize!=NULL) {
        $this->output->exPdfLib->setFontSize($fontSize);
        if ($maxWidth!=0) {
            $stringWidth = $this->output->exPdfLib->GetStringWidth($this->temp_custcellText);
            if ($stringWidth > $maxWidth) {
            $fontSize = max (7,  ($fontSize * $maxWidth) / $stringWidth);
            $this->output->exPdfLib->setFontSize($fontSize);
            }
        }
    }
    $left = $this->temp_custCellLeft + $this->status->column_LeftX;
    $top = $this->temp_custCellTop + $this->status->row_TopY + $this->styleCombined->pad->top;
    $this->exPdfLib->text($left, $top, $this->temp_custcellText);
}

function CustomCell_Image_Pdf() {
    $left = $this->temp_ImageLeft + $this->status->column_LeftX; // $this->temp_custCellLeft; //
    $top  = $this->temp_ImageTop +  $this->status->row_TopY; // + $this->status->row_TopY;
    $this->exPdfLib->image($this->temp_ImageFileName,$left,$top,$this->temp_ImageWidth,$this->temp_ImageHeight);
    $left = $this->temp_custCellLeft;
    $top = $this->temp_custCellTop;
}
function exportBorderVer($pCode, $pX, $pTop, $pBottom) {
    // PDF: coordinates are X, Y positions
    // Excel: coordinated are cell col.row indexes
    $this->drawBorder($pCode, $pX, $pX, $pTop, $pBottom);
}
function exportBorderHor($pCode, $pLeft, $pRight, $pY) {  
    // PDF: coordinates are X, Y positions
    // Excel: coordinated are cell col.row indexes
    $this->drawBorder($pCode, $pLeft, $pRight, $pY, $pY); 
}
function exDebug($pDesc, $pVal) {
    echo  ' ... '.$pDesc . '= ' . $pVal;
}
function drawBorder($pBorderCode, $pLeft, $pRight, $pTop, $pBottom) {
    if ($pBorderCode<=0)
       return;
    if ($pTop===NULL or $pBottom===NULL)
        return;
    if ($pLeft===NULL or $pRight===NULL)
        return;
    if ($pTop==0 and $pBottom==0)
        return;
    if ($pLeft==0 and $pRight==0)
        return;
    if ($pBottom<$pTop)
        return;
    if ($pRight<$pLeft)
        return;
    if ($pBorderCode>4)
        $pBorderCode=4;    
    $borderColor = $this->borderLineColor [$pBorderCode];
    $borderWidth = $this->borderLineWidth [$pBorderCode];
    if ($this->exIsPdf) {
//if ($pTop != $pBottom)    
//deb($pBorderCode,$borderColor,$borderWidth,$pLeft, $pRight, $pTop, $pBottom);    
        if ($pBorderCode==1) {   //@@@ jpr 2015-08-13 Kludge
            $borderWidth = $borderWidth / 2;
            if ( $borderColor == 220 ) {
                $borderColor = 80;
                $borderWidth = 0.5;  // was 0.7 / 2
            }    
        }
        if ($pBorderCode==3) {  //@@@ jpr 2015-08-13 Kludge
            $borderWidth = 1; // not as heavy line
        }
        $this->exPdfLib->setLineWidth($borderWidth);
        $this->exPdfLib->setDrawColor($borderColor);
        $this->exPdfLib->line($pLeft,$pTop,$pRight,$pBottom);
    }    
    else if ($this->exIsExcel) {
        $color = new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_RED);
        if ($pLeft===$pRight) {
            $c=$this->excelCellCord($pLeft+1,$pTop+4).':'.$this->excelCellCord($pLeft+1, $pBottom+4);
            $this->exExcelLib->activeSheet->getStyle($c)->getBorders()->getLeft()->setBorderStyle($borderWidth);
            $this->exExcelLib->activeSheet->getStyle($c)->getBorders()->getLeft()->setColor($borderColor);
            //if ($pLeft>=1) {
            //    $c=$this->excelCellCord($pLeft,$pTop+1).':'.$this->excelCellCord($pRight, $pBottom+1);
            //    $this->exExcelLib->activeSheet->getStyle($c)->getBorders()->getRight()->setBorderStyle($border);
            //}
        }    
        else if ($pTop===$pBottom) {
            $c=$this->excelCellCord($pLeft+1,$pTop+3).':'.$this->excelCellCord($pRight+1, $pTop+3);
            $this->exExcelLib->activeSheet->getStyle($c)->getBorders()->getBottom()->setBorderStyle($borderWidth);
            $this->exExcelLib->activeSheet->getStyle($c)->getBorders()->getBottom()->setColor($borderColor);
        }    
       //$this->exExcelLib->cellSet(0,$this->debLine++, $c);
    }        
    else if ($this->exIsDebug) {
       echo '<br>BORDER'; 
       $this->exDebug('code',$pCode); 
       if ($pLeft===$pRight)
          $direc = 'Vertical';
       else if ($pTop===$pBottom)
          $direc = 'Horizontal';
       else
          $direc = 'Diagonal';
       $this->exDebug('direc',$direc); 
       $this->exDebug('left',$pLeft); 
       $this->exDebug('right',$pRight); 
       $this->exDebug('top',$pTop); 
       $this->exDebug('bottom',$pBottom); 
    }
}


}
?>