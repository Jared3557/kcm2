<?php

// to do - add font function for name labels

include_once('reportClasses.inc.php');

// ???? todo: fix row height - eliminate parameter - use style
// ???? todo: fix row height - need to keep tack of largest value and set at end of row
// ???? todo: paging
// ???? question: is padding one global setting or a style (per cell)?
// ???? todo: set body padding to page borders
//  ??? todo: have column style classes in html header 
//  ??? todo: should some column vars be move to status
// done: set cell style at end of previous cell with column info (so can be overridden)


// Style Notes  ===>
// ----------------------- (public) -----------------------------
// styleGlobal   - applies to everything on report - can be overriden
// [] styleColumn- non-null values override styleGlobal for just the one column
// syleRow       - non-null values override the above for just the current row
// styleCell     - non-null values override the above for just the current cell
// styleDiv      - non-null values override the above for just the current division
// styleOverride - non-null values override all of the above until cleared
// ----------------------- Internal (private) -----------------------------
// styleCombined - the result of merging (the non-null values) all the above styles
// styleCurrent - the (default) style if the styleCombined is not used 
// styleDifferent - the styles that need to be set (changed)
//    this differs for each output option
//    in HTML this will be the style of the parent component
//    in PDF and Excel this will be the style last printed (parent style irrelevant)
//    in either case the difference between styleCombined and styleCurrent needs to be updated 
//        via styleDifferent, and then styleCurrent needs to be set to parent for HTML
//        or StyleCombined for PDF or excel

require_once('lib/fpdf/fpdf.php');
require_once('lib/excel/PHPExcel/PHPExcel.php');
require_once('lib/excel/rc_excel/rc_PHPExcel.inc.php');


//==================================================================================
//===========================     Rc_reportEngine
//==================================================================================
    
Class rc_reportEngine {

public $page;   // page and report layout
public $layout;
private $htmlLib;
private $output;

public  $styleGlobal;  // global style (should not change once global settings have ended)
public  $styleRow; // overrides global (except if null)
public  $styleCell; // overrides row and global (except if null)
public  $styleDiv;  // overrides cell, row and global (except if null)
public  $styleOverride;  // override all of the above until cleared
private $styleCombined;  // combination of above styles
private $styleCurrent;   // current style (html: parent object, pdf, excel: last printed object)
private $styleDifferent;  // difference between current style and combined style (only print necessary values - no need to print if combined same as current)

public $status;   //?? private
private $drawBuffer;

//--- properties have only one value
//public $propRowHeight;

//private $curInFooter;  // ??? needed ???
//private $curInHeader;  // ?? needed ??  or InStartRow

//--- Temporary values - for passing values from one function to another ???? or use style ???
//   ????? add properties for one time non-hierarchy styles/parameters such as images 
private $temp_custCellLeft;  //???? eliminate - use styleDiv instead
private $temp_custCellTop;  //???? eliminate - use styleDiv instead
private $temp_custCellText;  //???? eliminate - use styleDiv instead
private $temp_custCellFont;  //???? eliminate - use styleDiv instead
private $temp_custCellWeight;  //???? eliminate - use styleDiv instead
private $temp_custImageName;  //???? maybe use a new property class instead
private $temp_custImageWidth;  //???? maybe use a new property class instead
private $temp_custImageHeight;  //???? maybe use a new property class instead

function __construct($pOutParam,$pOrientation=rpPAGE_PORTRAIT, $pDestFile="") {
    define('FPDF_FONTPATH',"lib/fpdf/font");
    date_default_timezone_set('America/New_York');
    $this->output = new rc_output($pOutParam,$pDestFile);
    $this->page    = new rc_page($pOrientation,$pDestFile);
    $this->layout  = new rc_layout;
    $this->htmlLib = new rc_htmlLib;
    $this->status         = new rc_status;
    $this->styleGlobal    = new rc_style;
    $this->styleRow       = new rc_style;
    $this->styleCell      = new rc_style;
    $this->styleDiv       = new rc_style;
    $this->styleOverride  = new rc_style; 
    $this->styleCombined  = new rc_style;
    $this->styleCurrent   = new rc_style; 
    $this->styleDifferent = new rc_style; 
    $this->styleGlobal->setDefaults();
    $this->output_Method('ReportDefine');   // opens the output - no styles yet (they can be overridden before header closes) 
}

//====================
//=  Report Methods  =
//====================

function globalSettings_Start () {   //??????????????????????????
}

function globalSettings_End($pNormalizeColumns=NULL) { // to be called after report (global) overrides are set
    $this->layout->normalize_Columns($this->page, $this->styleGlobal,$pNormalizeColumns);
    $this->posRowHeight = 0;  // set before start of row, added to page_CurY after row is completed
    $this->posColumnIndex = 0;
    $this->posColumnObject = $this->layout->columnList[$this->posColumnIndex];
//    $this->curInFooter = FALSE;  // ??? needed ???
//    $this->curInHeader = FALSE;  // if true <th> used instead of <tr>
    $this->propRowHeight = NULL;
    $this->status->cur_PageNumber = 0;
    $this->status->cell_PosX = $this->page->printAreaLeft;
    $this->status->cell_PosY = $this->page->printAreaTop;
    $this->status->column_Index = 0;
    $this->status->column_Object = NULL;
    $this->status->row_Number = 0; 
    $this->status->row_Height = 0;  
    $this->status->row_TopY = $this->page->printAreaTop;     
    $this->status->row_BottomY = $this->page->printAreaTop;  
    $this->output_Method('ReportStart');
//????    $this->styleCurrent->copyFrom($this->styleGlobal);  // for html  ???? move to html table function
    $this->drawBuffer = new rc_drawBuffer($this->layout->columnCount,$this->output);
    $this->pageHeader();

}
function report_Close () {
//    PageEnd();
    $this->pageFooter();
    $this->output_Method('ReportEnd');
}


//===========================================================================================
//=  Out methods - a method for each type of output and each "file" format (excelLib, html, pdfLib)
//============================================================================================

//=====  Report Define (open the output - not much else) 

function ReportDefine_Html() {
    $this->htmlLib->print_Line('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">');
    $this->htmlLib->print_Line('<html>');
    $this->htmlLib->print_Line('<head>');
    $this->htmlLib->print_Line('<link rel="stylesheet" type="text/css" href="lib/reportEngine.css" />');
}
function ReportDefine_Excel() {
//    $title = basename(dirname(__FILE__)).'/';  //?????????????
$this->output->excelLib = new rc_Excel;
$this->output->excelLib->openForWriting();
}
function ReportDefine_Pdf() {
    if ($this->page->orientation==rpPAGE_LANDSCAPE)
        $orCode="L";
    else
        $orCode="P";
    $this->output->pdfLib = new FPDF($orCode,'pt','Letter');
    $this->output->pdfLib->setMargins(1,1);
    //$fh = 18; //$this->styleGlobal->fontHeight;
    //if ($fh!=NULL)
    //    $this->output->pdfLib->setFontSize($fh);
}

//=====  Report Start (start the body of the report - may still need to put styles, etc in the "header")

function ReportStart_Html() {
    //--- properties which go in header (or could be table start)
    if ($this->styleGlobal->pad->left!=NULL or $this->styleGlobal->pad->top!=NULL 
          or $this->styleGlobal->pad->right!=NULL or $this->styleGlobal->pad->bottom!=NULL)  {
        // ?????? use htmlLib to produce style ?????
        $s="";
        if ($this->styleGlobal->pad->left!=NULL)
            $s='padding-left:'.$this->styleGlobal->pad->left.'pt;';
        if ($this->styleGlobal->pad->top!=NULL)
            $s=$s.'padding-top:'.$this->styleGlobal->pad->top.'pt;';
        if ($this->styleGlobal->pad->right!=NULL)
            $s=$s.'padding-right:'.$this->styleGlobal->pad->right.'pt;';
        if ($this->styleGlobal->pad->bottom!=NULL)
            $s=$s.'padding-bottom:'.$this->styleGlobal->pad->bottom.'pt;';
        print('<STYLE type="text/css">');
        print('td {'.$s.'}');
        print('</STYLE>');    
    }
    $this->htmlLib->print_Line('</head>');
    $this->htmlLib->print_Line('<body>');
//    $this->PageStart_Html();
//    $this->htmlLib->tag_Open("table");
//    $this->htmlLib->tag_AddAttr("cellspacing","0");
//    $this->htmlLib->tag_AddStyle("empty-cells","show");
//    $this->htmlLib->tag_AddStyle("width",$this->page->printAreaWidth,'pt');
//    $this->htmlLib->set_AllStyles($this->styleGlobal);
//    $this->htmlLib->tag_PrintLine();
}
function ReportStart_Excel() {
$this->output->excelLib = new rc_Excel;
$this->output->excelLib->openForWriting();
$this->output->excelLib->newSheet('Page 1');
for ($i=1; $i<$this->layout->columnCount; $i++) 
    $this->output->excelLib->columnSetWidth($i-1,intval($this->layout->columnList[$i]->colWidthInPoints / 5) );
}
function ReportStart_Pdf() {
    $this->output->pdfLib->addPage();
    $this->output->pdfLib->SetFont('Helvetica','',12);
    $this->output->pdfLib->SetLeftMargin($this->page->printAreaLeft);
    $this->output->pdfLib->SetTopMargin($this->page->printAreaTop);
}
//=====  Report End (close the output) 

function Report_AddFont($pFontName, $pBold=FALSE) {
    if ($this->output->isPdf) {
        if ($pBold)
            $attr = "B";
        else    
            $attr = "";
        $this->output->pdfLib->AddFont($pFontName, $attr);
    }    
}
function ReportEnd_Excel() {
$this->output->excelLib->saveToDownload($this->output->destFile.'.xlsx');
}
function ReportEnd_Pdf() {
    $this->output->pdfLib->output($this->output->destFile.'.pdf','I');
}
function ReportEnd_Html() {
    $this->PageEnd_Html();
    $this->htmlLib->print_Line('</body>');
    $this->htmlLib->print_Line('</html>');
}

//==================================================================================
//=================
//=  Page         =
//=================
//?????????????????????????????????????????????

function pageBreak() {
    if ($this->status->page_Number>=1) 
       $this->pageFooter(); 
    //$this->status->cur_PageNumber = $this->status->cur_PageNumber + 1;
    //$curStyle = $reportStyle;
    //$this->status->cell_PosX = $this->page->printAreaLeft;
    //$this->status->cell_PosY = $this->page->printAreaTop;
    // reset curTableY
    // call header procedure if procedure exists 
    $this->pageHeader();
    //    $this->$curInHeader = TRUE;
    //    onReportHeader();        
    //    $this->$curInHeader = FALSE;
    //
}

function pageFooter() {
//echo "--c-".$this->status->page_Number;
    $this->drawBuffer->flush();
    if (function_exists ('rpt_OnFooter')) 
        rpt_OnFooter();
    $this->output_Method('PageEnd');
    // make sure page was started (take care of page end occurring or not occurring before close)
    // end table on this page
    // (2) for pdfLib print table border here
    //if (function_exists ('onReportFooter')) {
    //    $this->$curInFooter = TRUE;
    //    onReportFooter();        
    //    $this->$curInFooter = FALSE;
    //}    
}    

//==================================================================================
//=  Page Start and End
//==================================================================================
function pageHeader() {
//echo "---***---";
    $this->status->page_Number= $this->status->page_Number + 1;  //?????????
    $this->output_Method('PageStart');
    if (function_exists ('rpt_OnHeading')) 
        rpt_OnHeading();
}
function PageStart_Excel() {
}
function PageStart_Pdf() {
    if ($this->status->page_Number>1) 
        $this->output->pdfLib->AddPage();
    $this->status->cell_PosX = $this->page->printAreaLeft;
    $this->status->cell_PosY = $this->page->printAreaTop;
    $this->status->row_Number = 0; 
    $this->status->row_Height = 0;  
    $this->status->row_TopY = $this->page->printAreaTop;     
    $this->status->row_BottomY = $this->page->printAreaTop;  
    if ($this->styleGlobal->fontName!=NULL)
        $this->output->pdfLib->SetFont($this->styleGlobal->fontName);
}
function PageStart_Html() {
//    $this->status->page_Number= $this->status->page_Number + 1;  //?????????
//echo '--->'.$this->status->page_Number.'<br>';    
    if ($this->status->page_Number>1) {
        $this->htmlLib->print_Line('</table>');
        $this->htmlLib->print_Line('<div style="page-break-before:always;"></div>');
    }    
    $this->htmlLib->tag_Open("table");
    $this->htmlLib->tag_AddAttr("cellspacing","0");
    $this->htmlLib->tag_AddStyle("empty-cells","show");
    $this->htmlLib->tag_AddStyle("width",$this->page->printAreaWidth,'pt');
    $this->htmlLib->set_AllStyles($this->styleGlobal);
    $this->htmlLib->tag_PrintLine();
}
function PageEnd_Excel() {
}
function PageEnd_Pdf() {
//        $this->output->pdfLib->AddPage();
}
function PageEnd_Html() {
    $this->htmlLib->print_Line('</table>');
}

// Row Prestart
//===================
function row_PreStart() {  // can set row height after this
    $this->styleRow->clear();
}


// Row Start
//===================
function row_Start($pRowHeight=0) {  // ????????? is row-height a parameter or a property ????????
    $this->status->row_Height = $this->getStyle($this->styleGlobal->rowHeight,$this->styleRow->rowHeight);
    if ($this->status->row_Height==NULL)  //????? is 4 the best increment ???
        $this->status->row_Height = 4 + $this->getStyle($this->styleGlobal->fontHeight,$this->styleRow->fontHeight);
    $this->status->row_TopY = $this->status->row_BottomY+1; 
    $this->status->row_BottomY = $this->status->row_TopY + $this->status->row_Height;  // subject to change as cells are printed
    $this->status->row_Number = $this->status->row_Number + 1;
    $this->status->cell_PosX = $this->page->printAreaLeft;  //???????????????????????
    $this->status->cell_PosY = $this->status->row_BottomY;  //????????????
    $this->status->column_Index = 0;   // cell-start will increment this - skip first "border only" column
    $this->output_Method('RowStart'); // ???????????????? ,$pRowHeight);
    $this->cell_PreStart();
}
function RowStart_Html() {
    $this->htmlLib->print_Line("<tr>");
}
function RowStart_Pdf() {
}
function RowStart_Excel() {
}


//  Row End
//===================
public function row_End() {
    $this->drawBuffer->flush();
    $this->output_Method('RowEnd');
    $this->row_PreStart();
}
function RowEnd_Html() {
    $this->htmlLib->print_Line("  </tr>");
}
function RowEnd_Excel() {

}
function RowEnd_Pdf() {
}

// Cell PreStart
//===================
function cell_PreStart() { // can set border, etc after this point
    $this->status->column_Index = $this->status->column_Index + 1;   // first cell is column 1 (skip false "border only" zero column)
    $this->status->column_Object = $this->layout->columnList[$this->status->column_Index];
    $this->status->column_Object->clearCell();
    $this->styleCell->clear();
}

// Cell Start
//===================
function cell_Start($pColSpan=1) {
    if ($pColSpan<1 or $pColSpan==NULL)
        $pColSpan = 1;
    $this->status->column_Object->cellSpan = $pColSpan;
    $prevColumn = $this->layout->columnList[$this->status->column_Index - 1];
    if ($pColSpan<=1)
        $rightColumn = $this->status->column_Object;  // current column unless if colspan<>1
    else   
        $rightColumn = $this->layout->columnList[$this->status->column_Index + $pColSpan - 1]; // current column unless if colspan<>1
    $this->status->column_Object->curCellRect->left   =  $this->status->column_Object->colPosLeftX; 
    $this->status->column_Object->curCellRect->right  =  $rightColumn->colPosRightX; 
    $this->status->column_Object->curCellRect->top    =  $this->status->row_TopY; 
    $this->status->column_Object->curCellRect->bottom =  $this->status->row_BottomY; 
//echo "--->".$this->status->row_TopY."===".$this->status->row_BottomY."===".$this->status->row_Height."<br>";    
    $this->curColumnWidth =  $rightColumn->colPosRightX - $this->status->column_Object->colPosLeftX; 
    $this->status->cell_PosX = $this->layout->columnList[$this->status->column_Index]->colPosLeftX;
    $this->styleCombined->copyFrom($this->styleGlobal); 
    $this->styleCombined->copyMergeFrom($this->status->column_Object->styleColumn); 
    if ($pColSpan>1) 
       if ($rightColumn->styleColumn->border->right!=NULL)
           $this->styleCombined->border->right = $rightColumn->styleColumn->border->right;
    $this->styleCombined->copyMergeFrom($this->styleRow); 
    $this->styleCombined->copyMergeFrom($this->styleCell); 
    $this->styleCombined->copyMergeFrom($this->styleOverride); 
//    echo '**'.$this->styleCombined->pad->left,'<br>';
    $this->styleDifferent->dif($this->styleCombined,$this->styleCurrent); 
//    $this->styleCombined->copyFrom($this->styleDifferent);  //????? should use style different in function calls 
    $this->output_Method('CellStart');
}

// Cell End
//===================
function cell_End() {
//    if not html
//    if ($this->status->column_Index==1) 
//@@@      $this->drawBuffer->drawVerLine($pColIndex,$pX,$this->styleCombined->border->left,$this->styleCombined->border->right)
//        draw ver line
//    if ($this->status->column_Index>=$this->layout->columnCount-1) 
//        draw ver line - last column  
//    else 
//        draw ver line - middle combined columns
//    draw hor line - top and bottom 
//  on last cell flush row
    $this->drawBuffer->drawCellBorders($this->status->column_Index,$this->status->row_Number,$this->styleCombined->border,$this->status->column_Object->curCellRect);
    $this->output_Method('CellEnd');
    $this->status->column_Index = $this->status->column_Index + $this->status->column_Object->cellSpan - 1;   // skip cells if multiple columns 
    $this->propWidth = NULL;  //?????????????
    if ($this->status->column_Index < $this->layout->columnCount-1)
        $this->cell_PreStart();
}
function cell_Text($pText) {
    $this->output_Method('CellText',$pText);
}

//=  Cell Of Text (single text item allowed in cell)   
//==================================================
function cellOfText($pText, $pColSpan=1) {
    $this->cell_Start($pColSpan);
    $this->cell_Text($pText);
    $this->cell_End(); 
}

//=================
//=  Cell         =
//=================

function cellText($text) {
    print($text);
}
function cellTextDiv($px, $py, $text) {
    $this->tag->start('<div');
    $this->tag->addStyle("position","relative");
    $this->tag->addStyle("left",($px*2)."pt");
    $this->tag->addStyle("top",($py*2)."pt");
    if ($this->textSize!=0)
        $this->tag->addStyle("font-size",$this->textSize.'pt');
    $this->tag->tagPrint();
    print($text.'</div>'. PHP_EOL);  //????????????????????????????????????
}
function cellImageDiv($px, $py, $img, $width, $height ) {
    $this->tag->start('<img src="'.$img.'" width="'.$width.'" height="'.$height.'"');
    $this->tag->addStyle("position","relative");
    $this->tag->addStyle("left",($px*2)."pt");
    $this->tag->addStyle("top",($py*2)."pt");
    $this->tag->tagPrint();
    print(PHP_EOL);
}

//==================================================================================
//=  Cell 
//==================================================================================

//=====  Cell Start
function CellStart_Excel() {
}
function CellStart_Html() {
    $this->htmlLib->tag_Open("td");  
    $this->htmlLib->set_ColSpan($this->status->column_Object->cellSpan);
//    if ($this->status->cur_RowHeightToSet!=NULL) {
//        $this->htmlLib->set_RowHeight($this->status->cur_RowHeightToSet);
//        $this->status->cur_RowHeightToSet =NULL;
//    }
    $this->posRowHeight = NULL;  //???????????????????
    if ($this->status->column_Object->cellSpan==1) {
        $this->htmlLib->set_ColWidth($this->status->column_Object->colWidthToSet);
        $this->status->column_Object->colWidthToSet = NULL;
    }    
//    $this->styleHtmlDif->dif($this->styleGlobal,$this->styleHtmlTable);
//    $this->styleHtmlDif->dif($this->styleGlobal,$this->styleCombined);   //????????????
//    $this->htmlLib->set_AllStyles($this->styleHtmlDif);
    $this->htmlLib->set_AllStyles($this->styleDifferent);  //?????????????????
//    $this->htmlValue_SetRowHeight(rpLEVEL_CELL);

    $this->htmlLib->set_BorderSide("Lft",$this->styleDifferent->border->left);
    $this->htmlLib->set_BorderSide("Top",$this->styleDifferent->border->top);
    $this->htmlLib->set_BorderSide("Rgt",$this->styleDifferent->border->right);
    $this->htmlLib->set_BorderSide("Bot",$this->styleDifferent->border->bottom);
    $this->htmlLib->tag_PrintText("  ");
}
function CellStart_Pdf() {
}

//=====  Cell End
function CellEnd_Html() {
    $this->htmlLib->print_Line("</td>");
}
function CellEnd_Pdf() {
}
function CellEnd_Excel() {
}

//=====  Cell Text
function CellText_Excel($pText) {
$this->output->excelLib->cellSet($this->status->column_Index-1,$this->status->row_Number, $pText);
}
function CellText_Html($pText) {
    $this->htmlLib->print_Text($pText);
}   
function CellText_Pdf($pText) {
    $fh = $this->styleDifferent->fontHeight;
    if ($fh!=NULL)
        $this->output->pdfLib->setFontSize($fh);
    $left = $this->status->cell_PosX + $this->styleCombined->pad->left;
    $top = $this->status->cell_PosY + $this->styleCombined->pad->top;
    $this->output->pdfLib->text($left, $top, $pText);
}

//  Custom Cell Start
//===================
function CustomCell_Start($pcolSpan=1) {
    $this->cell_Start($pcolSpan);
    $this->output_Method('CustomCell_Start');
}
function CustomCell_Start_Excel() {
}
function CustomCell_Start_Html() {
    $this->htmlLib->tag_Open("div");  
    $this->htmlLib->tag_AddStyle("position","relative");
    $this->htmlLib->tag_AddStyle("left",0,"pt");
    $this->htmlLib->tag_AddStyle("top",0,"pt");
    $this->htmlLib->tag_PrintText("  ");
}
function CustomCell_Start_Pdf() {
}

//  Custom Cell End
//===================
function CustomCell_End() {
    $this->cell_End(); 
    $this->output_Method('CustomCell_End');
}
function CustomCell_End_Excel() {
}
function CustomCell_End_Html() {
    $this->htmlLib->tag_Open("div");  
}
function CustomCell_End_Pdf() {
}

//  Custom Cell Text
//===================
function CustomCell_Text($pLeft, $pMaxWidth, $pTop, $pText, $pFont=NULL, $pWeight=NULL) {
    $this->temp_custCellLeft   = $pLeft;
    $this->temp_custCellTop    = $pTop;
    $this->temp_custCellText   = $pText;
    $this->temp_custCellFont   = $pFont;
    $this->temp_custCellWeight = $pWeight;
    $this->temp_custCellWidth  = $pMaxWidth;
    $this->output_Method('CustomCell_Text');
}
function CustomCell_Text_Excel() {
}
function CustomCell_Text_Html() {
    $this->htmlLib->tag_Open("div");  
//    $this->tag_AddStyle("position","relative");
    $this->htmlLib->tag_AddStyle("position","absolute");
    $this->htmlLib->tag_AddStyle("left",$this->temp_custCellLeft,"pt");
    $this->htmlLib->tag_AddStyle("top",$this->temp_custCellTop,"pt");
    $this->htmlLib->tag_AddStyle("font-size",$this->temp_custCellFont,"pt");
    $this->htmlLib->tag_PrintText("  ");
//     <div style="position:relative;left:0pt;top:0pt;">0-0</div>
    $this->htmlLib->print_Text($this->temp_custCellText);
    $this->htmlLib->print_Line("</div>");
}   
function CustomCell_Text_Pdf() {
    $fontSize = $this->temp_custCellFont;
    $maxWidth = $this->temp_custCellWidth;
    if ($fontSize!=NULL) {
        $this->output->pdfLib->setFontSize($fontSize);
        if ($maxWidth!=0) {
            $stringWidth = $this->output->pdfLib->GetStringWidth($this->temp_custCellText);
            if ($stringWidth > $maxWidth) {
            $fontSize = max (7,  ($fontSize * $maxWidth) / $stringWidth);
            $this->output->pdfLib->setFontSize($fontSize);
            }
        }
    }    
    $left = $this->temp_custCellLeft + $this->status->cell_PosX;
    $top = $this->temp_custCellTop + $this->status->row_TopY;
    $this->output->pdfLib->text($left, $top, $this->temp_custCellText);
}

//  Custom Cell Image
//===================
function CustomCell_Image($pLeft, $pTop, $pWidth, $pHeight, $pFileName) {
    $this->temp_ImageLeft     = $pLeft;
    $this->temp_ImageTop      = $pTop;
    $this->temp_ImageWidth    = $pWidth;
    $this->temp_ImageHeight   = $pHeight;
    $this->temp_ImageFileName = $pFileName;
    $this->output_Method('CustomCell_Image');
}
function CustomCell_Image_Excel() {
}
function CustomCell_Image_Html() {
    $this->htmlLib->tag_Open('<img src="'.$this->temp_ImageFileName.'" width="'.$this->temp_ImageWidth.'" height="'.$this->temp_ImageHeight.'"');
    $this->htmlLib->tag_AddStyle("position","relative");
    $this->htmlLib->tag_AddStyle("left",($this->temp_ImageLeft),"pt");
    $this->htmlLib->tag_AddStyle("top",($this->temp_ImageTop),"pt");
    $this->htmlLib->tag_PrintLine();
}
function CustomCell_Image_Pdf() {
    $cellLeft = $this->status->cell_PosX; // + $this->styleCombined->pad->left;
    $cellTop = $this->status->cell_PosY; // + $this->styleCombined->pad->top;
    $this->output->pdfLib->image($this->temp_ImageFileName,$this->temp_ImageLeft+$cellLeft,$this->temp_ImageTop+$cellTop,$this->temp_ImageWidth,$this->temp_ImageHeight);
}

//==================================================================================
//=   Misc Methods 
//==================================================================================

function output_Method ($pMethod, $pArg1="@@", $pArg2="@@", $pArg3="@@") {
    $s = "" . $pMethod . '_' . $this->output->destDesc ;
    if ($pArg3!="@@")
       $this->$s($pArg1,$pArg2,$pArg3);
    else if ($pArg2!="@@")
       $this->$s($pArg1,$pArg2);
    else if ($pArg1!="@@")
       $this->$s($pArg1);
    else   
       $this->$s();
}

function getStyle($pGlobal, $pRow, $pCell=NULL) {
    if ($pCell!=NULL) 
        return $pCell;
    if ($pRow!=NULL) 
        return $pRow;
    else    
        return $pGlobal;
}

}


