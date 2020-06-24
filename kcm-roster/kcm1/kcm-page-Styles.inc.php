<?php

// kcm-page-Styles.php

// these items are defined due to bug in notepad editor which parses
// comments and strings for tokens when analyzing php structure
define('kcmLEFTBRACE','{');
define('kcmRIGHTBRACE','}');  
define('kcmSEMI',';');
define('kcmREGEXP',"@([a-zA-Z][a-zA-Z0-9\-]*)|([0-9][0-9.]*)|[\']([.0-9a-zA-Z\"\/\-]+[\'])*|[\"]([.\'0-9a-zA-Z\/\-]+[\"])*|[\#]([0-9a-fA-F]+)*|([.:;\}\{])@");

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@  Style Side Class
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Class kcmPage_styleSide {
public $ssBorderCode;   // 0=none 1=barely visible, 2=thin, 3=thin black, 4=thick black
public $ssBorderWidth;  // probably not needed
public $ssBorderColor;  // probably not needed
public $ssPadding;
public $ssMargin;

function __construct() {
   $this->ssBorderWidth = NULL;
   $this->ssBorderColor = NULL;
   $this->ssBorderCode = 0;
   $this->ssPadding = 0;
   $this->ssMargin = 0;
}
function updateFrom($pSide) {
    if ($pSide->ssBorderWidth!==NULL) {
        $this->ssBorderWidth = $pSide->ssBorderWidth;
        $this->ssBorderColor = $pSide->ssBorderColor;
        $this->updateBorderCode();    
    }
//    if ($pSide->ssBorderColor!==NULL) {
//        $this->ssBorderColor = $pSide->ssBorderColor;
//        $this->ssBorderCode = $pSide->ssBorderCode;
//    }
    if ($pSide->ssPadding!=0)
        $this->ssPadding = $pSide->ssPadding;
    if ($pSide->ssMargin!=0)
        $this->ssMargin = $pSide->ssMargin;
}
function updateBorderCode() {
    if ($this->ssBorderWidth<1)    
       $this->ssBorderCode = 0;
    else if ($this->ssBorderWidth>=2)    
       $this->ssBorderCode = 4;
    else if ($this->ssBorderColor==='black')    
       $this->ssBorderCode = 3;
    else if ($this->ssBorderColor<'#C')  
       $this->ssBorderCode = 2;
    else  
       $this->ssBorderCode = 1;
    // should enhance more than this !!!  (need to decide what colours are valid, etc) 
}
function addBorder($pValueArray) {
    $ct = count($pValueArray);
    $this->ssBorderWidth = $pValueArray[0]; 
    if ($ct>1) 
        $this->ssBorderColor = $pValueArray[$ct-1]; 
    $this->updateBorderCode();    
}
function debugPrint($sideName) {
   if ($this->ssMargin>0)
      echo '<br>...'.$sideName.'-ssMargin='.$this->ssMargin;
   if ($this->ssPadding>0)
      echo '<br>...'.$sideName.'-ssPadding='.$this->ssPadding;
   if ($this->ssBorderWidth>=1)
      echo '<br>...'.$sideName.'-ssBorderWidth='.$this->ssBorderWidth;
   if ($this->ssBorderColor!=NULL)
      echo '<br>...'.$sideName.'-ssBorderColor='.$this->ssBorderColor;
   echo '<br>...'.$sideName.'-ssBorderCode='.$this->ssBorderCode;
}

}   // Style Side end

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@  Style Item Class
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Class kcmPage_styleItem {
public $stTag;
public $stClass;
public $stWidth;
public $stWidthMin;
public $stWidthMax;
public $stLeft;
public $stRight;
public $stTop;
public $stBottom;
public $stFontHeight;
public $stFontWeight;
public $stLineHeight;
public $stAlignHor;
public $stAlignVer;
public $stBackColor;
public $stHeight;
public $stItemLeft;
public $stItemTop;
public $stPosition;
public $stWrap;
public $stMinFontHeight;

function __construct($pTag,$pClass,$pParent=NULL) {
   $this->element = NULL;
   $this->stTag = $pTag;
   $this->stClass = $pClass;
   $this->stWidth = 0;
   $this->stWidthMin = 0;
   $this->stWidthMax = 0;
   $this->stLeft = new kcmPage_styleSide();
   $this->stRight = new kcmPage_styleSide();
   $this->stTop = new kcmPage_styleSide();
   $this->stBottom = new kcmPage_styleSide();
   $this->stFontHeight = 0;
   $this->stFontWeight = 0;
   $this->stLineHeight = 0;
   $this->stHeight = 0;
   $this->stAlignHor = '';
   $this->stAlignVer = '';
   $this->stBackColor = '';
   $this->stItemLeft = 0;
   $this->stItemTop = 0;
   $this->stPosition = '';
   $this->stWrap = '';
   $this->stMinFontHeight = 0;
   if ($pParent!==NULL) {
       $this->stFontHeight = $pParent->stFontHeight;
       $this->stFontWeight = $pParent->stFontWeight;
       $this->stAlignHor = $pParent->stAlignHor;
       $this->stAlignVer = $pParent->stAlignVer;
   }
}   
function updateFrom($pStyleItem) {
    if ($pStyleItem->stWidth!=0)
         $this->stWidth = $pStyleItem->stWidth;
    if ($pStyleItem->stWidthMin!=0)
        $this->stWidthMin = $pStyleItem->stWidthMin;
    if ($pStyleItem->stWidthMax!=0)
        $this->stWidthMax = $pStyleItem->stWidthMax;
    if ($pStyleItem->stFontHeight!=0)
        $this->stFontHeight = $pStyleItem->stFontHeight;
    if ($pStyleItem->stHeight!=0)
        $this->stHeight = $pStyleItem->stHeight;
    if ($pStyleItem->stFontWeight!='')
        $this->stFontWeight = $pStyleItem->stFontWeight;
    if ($pStyleItem->stLineHeight!=0)
        $this->stLineHeight = $pStyleItem->stLineHeight;
    if ($pStyleItem->stItemTop!=0)
        $this->stItemTop = $pStyleItem->stItemTop;
    if ($pStyleItem->stItemLeft!=0)
        $this->stItemLeft = $pStyleItem->stItemLeft;
    if ($pStyleItem->stAlignHor!='')
        $this->stAlignHor = $pStyleItem->stAlignHor;
    if ($pStyleItem->stAlignVer!='')
        $this->stAlignVer = $pStyleItem->stAlignVer;
    if ($pStyleItem->stBackColor!='')
        $this->stBackColor =$pStyleItem->stBackColor;
    if ($pStyleItem->stPosition!='')
        $this->stPosition =$pStyleItem->stPosition;
    if ($pStyleItem->stWrap!='')
        $this->stWrap =$pStyleItem->stWrap;
    if ($pStyleItem->stMinFontHeight!=0)
        $this->stMinFontHeight =$pStyleItem->stMinFontHeight;
    $this->stLeft->updateFrom($pStyleItem->stLeft);
    $this->stRight->updateFrom($pStyleItem->stRight);
    $this->stTop->updateFrom($pStyleItem->stTop);
    $this->stBottom->updateFrom($pStyleItem->stBottom);
}
function importStyleSpec($pStyleList, $pTag, $pClass) { //???? pS//tyle ????
    $this->stTag = $pTag;
    $this->stClass = $pClass;
    $classes = explode(' ',$pClass);
    // process tags without classes
    if ($pTag!=='') {
        for ($i = 0; $i<$pStyleList->styleCount; $i++) {
            $curStyleItem = $pStyleList->styleArray[$i];
            if ($curStyleItem->stTag===$pTag and $curStyleItem->stClass==='') {
                $this->updateFrom($curStyleItem);    
            }
        }    
    }    
    $classCount = count($classes);
    for ($clsIdx=0; $clsIdx<$classCount; $clsIdx++) {
        $curClass = $classes[$clsIdx];
        for ($i = 0; $i<$pStyleList->styleCount; $i++) {
            $curStyleItem = $pStyleList->styleArray[$i];
            if ($curStyleItem->stTag==='' or $curStyleItem->stTag===$pTag) 
                if ($curStyleItem->stClass===$curClass) {
                    $this->updateFrom($curStyleItem);    
                }    
//                    $this->updateFrom($pS//tyle);    
        }      
    }    
    //if ($pS//tyle!='') {
    //    $pStyleList->styleParser->parseString($pS//tyle,$this);
    //}
    //if ($this->stFontHeight==0)
    //   $this->stFontHeight = 12;
}

function addPadding($pValueArray) {
    $this->stLeft->ssPadding = $pValueArray[0]; 
    $this->stRight->ssPadding = $pValueArray[0];
    $this->stTop->ssPadding = $pValueArray[0];
    $this->stBottom->ssPadding = $pValueArray[0];
}
function addMargin($pValueArray) {
    $this->stLeft->ssMargin = $pValueArray[0]; 
    $this->stRight->ssMargin = $pValueArray[0];
    $this->stTop->ssMargin = $pValueArray[0];
    $this->stBottom->ssMargin = $pValueArray[0];
}
function addBorder($pValueArray) {
    $this->stLeft->addBorder($pValueArray); 
    $this->stRight->addBorder($pValueArray); 
    $this->stTop->addBorder($pValueArray); 
    $this->stBottom->addBorder($pValueArray); 
}


function setProperty($pProperty,$pValueArray) {
    if (count($pValueArray)===0)
        return;  // process properties that have no values here or add a default/zero
    switch ($pProperty) {
       case 'width': $this->stWidth = $pValueArray[0]; break;
       case 'min-width': $this->stWidthMin = $pValueArray[0]; break;
       case 'max-width': $this->stWidthMax = $pValueArray[0]; break;
       case 'padding-left': $this->stLeft->ssPadding = $pValueArray[0]; break;
       case 'padding-right': $this->stRight->ssPadding = $pValueArray[0]; break;
       case 'padding-top': $this->stTop->ssPadding = $pValueArray[0]; break;
       case 'padding-bottom': $this->stBottom->ssPadding = $pValueArray[0]; break;
       case 'margin-left': $this->stLeft->ssMargin = $pValueArray[0]; break;
       case 'margin-right': $this->stRight->ssMargin = $pValueArray[0]; break;
       case 'margin-top': $this->stTop->ssMargin = $pValueArray[0]; break;
       case 'margin-bottom': $this->stBottom->ssMargin = $pValueArray[0]; break;
       case 'border-left':$this->stLeft->addBorder($pValueArray); break;
       case 'border-right': $this->stRight->addBorder($pValueArray); break;
       case 'border-top': $this->stTop->addBorder($pValueArray); break;
       case 'border-bottom': $this->stBottom->addBorder($pValueArray); break;
       case 'padding': $this->addPadding($pValueArray); break;
       case 'margin': $this->addMargin($pValueArray); break;
       case 'border': $this->addBorder($pValueArray); break;
       case 'background-color': $this->stBackColor = $pValueArray[0]; break;
       case 'text-align': $this->stAlignHor = $pValueArray[0]; break;
       case 'font-size': $this->stFontHeight = $pValueArray[0]; break;
       case 'font-weight': $this->stFontWeight = $pValueArray[0]; break;
       case 'line-height': $this->stLineHeight = $pValueArray[0]; break;
       case 'height': $this->stHeight = $pValueArray[0]; break;
       case 'vertical-align': $this->stAlignVer = $pValueArray[0]; break;
       case 'left': $this->stItemLeft = $pValueArray[0]; break;
       case 'top': $this->stItemTop = $pValueArray[0]; break;
       case 'position': $this->stPosition = $pValueArray[0]; break;
       case 'white-space': $this->stWrap = $pValueArray[0]; break;
       case 'kcm-min-font-size': $this->stMinFontHeight = $pValueArray[0]; break;
    }
}

function debugText($desc,$val) {
    if ($val!='')
       echo '<br>...'.$desc.'='.$val;
}
function debugNum($desc,$val) {
    if ($val>0)
       echo '<br>...'.$desc.'='.$val;
}
function debugPrint() {
    echo '<br>===============================';
    echo '<br>=  stTag='.$this->stTag.' Class='.$this->stClass;
    echo '<br>-------------------------------';
    $this->debugNum('Width',$this->stWidth);
    $this->debugNum('Width-Min',$this->stWidthMin);
    $this->debugNum('Width-Max',$this->stWidthMax);
    $this->stLeft->debugPrint('Left');
    $this->stRight->debugPrint('Right');
    $this->stTop->debugPrint('Top');
    $this->stBottom->debugPrint('Bottom');
    $this->debugNum('Font-Size',$this->stFontHeight);
    $this->debugText('Font-Weight',$this->stFontWeight);
    $this->debugText('Align-Hor',$this->stAlignHor);
    $this->debugText('Align-Ver',$this->stAlignVer);
    $this->debugText('Back-Color',$this->stBackColor);
    $this->debugText('Position',$this->stPosition);
}

} // Style Item end

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@  Style List Class
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Class kcmPage_styleList {
public $styleArray;
public $styleCount;
public $styleParser;

function __construct() {
    $this->styleArray = array();
    $this->styleCount = 0;
    $this->styleParser = new kcmPage_styleParser();
}

function findStyle($pTag, $pClass) {
    for ($i = 0; $i<$this->styleCount; $i++) {
       $curStyleItem = $this->styleArray[$i];
       if ($curStyleItem->stTag===$pTag and $curStyleItem->stClass===$pClass)
           return $curStyleItem;
    }       
    return NULL;
}
//function updateStyle($pS//tyle, $pTag, $pClass) {
//    for ($i = 0; $i<$this->styleCount; $i++) {
//        $curStyleItem = $this->styleArray[$i];
//        if ($pTag==='' or $curStyleItem->stTag===$pTag) 
//            if ($pClass==='' or $curStyleItem->stClass===$pClass) 
//                $pSt//yle->updateFrom($style);    
//    }      
//}

function addFile($pFileName) {
    $this->styleParser->parseFile($pFileName,$this);
    $this->styleCount = count($this->styleArray);
}

function debugTest($pStyleFile) {
    $this->addfile($pStyleFile);
    $this->debugPrint();
}    
function debugPrint() {
    $this->addfile('css/kcm-cssReportsTest.css');
    echo 'Selector Count = '.$this->styleCount;    
    for ($i = 0; $i<$this->styleCount; $i++) {
       $curStyleItem = $this->styleArray[$i];
       $curStyleItem->debugPrint();
    }
}

}  // Style List end

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@  Style styleParser Class
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Class kcmPage_styleParser {
private $styleList;
private $source;
private $sourceCnt;
private $sourceIdx;
private $outTok;
private $outIdx;
private $outCnt;
private $tokText;
private $tokType;
private $curClass;
private $curTag;
private $parseError;
private $curStyle;
private $fileName;

function parseFile($pFileName,$pStyleList) {
    $this->FileName = $pFileName;
    $this->source = file($pFileName);
    $this->styleList = $pStyleList; 
    $this->parseAll();
}    
function parseString($pString,$pStyleItem) {
    $this->FileName = ' String ='.$pString;
    $this->source = array ($pString);
    $this->styleList = NULL; 
    $this->curStyle = $pStyleItem; 
    $this->tokenGetFirst();
    $this->parsePropertyList();
}    


function tokenize($str) {
    $out = array();
    $tokens = array();
    $result = preg_match_all(kcmREGEXP,$str, $tokens, PREG_PATTERN_ORDER);
    foreach ($tokens[0] as $s) {
        $out[]=$s;
        }
    return $out;   
}

function tokenPastExpected($pChar) {
    if ($this->tokText===$pChar) {
        $this->tokenGetNext();
        return;
    }    
    //--- error recovery
    echo '-ER->'.$this->tokType.$pChar; 
    $this->parseError ('Expecting "'.$pChar.'"');            
    echo 'skipping->';
    while (TRUE) {
        echo '->'.$this->tokText.'<';
        $this->tokenGetNext();
        if ($this->tokType==='headingNextColumn') 
            return;
        if ($this->tokText===$pChar) {
            $this->tokenGetNext();
            return;
        }    
    }    
    echo '<-- end of skipping';
}

function tokenGetFirst() {
    $this->parseError = FALSE;
    $this->sourceCnt = count($this->source);
    $this->sourceIdx = -1;
    $this->outCnt = 0;
    $this->outIdx = 0;
    $this->tokenGetNext();
}
function tokenGetNext() {
    while (TRUE) {
        if ($this->sourceIdx>=$this->sourceCnt) {
            $this->tokText = '';
            $this->tokType = 'headingNextColumn';
            return '@eof';
        }
        else if ($this->outIdx<$this->outCnt)
            {
            $this->tokText = $this->outTok[$this->outIdx++];
            $c = substr($this->tokText,0,1);
            if (ctype_alpha ($c))
                $this->tokType = 'w';
            else if (is_numeric($c)) 
                $this->tokType = 'n';
            else if ($c==='#') 
                $this->tokType = 'h';
            else if ($c==='"' or $c==="'")
                $this->tokType = 's';
            else if (strlen($this->tokText)>1)
                $this->tokType = 'L';  // should never happen
            else 
                $this->tokType = 'p';
    //echo 'EggButtonBack->'.$this->tokText;    
            return '';
        }    
        else {
            ++$this->sourceIdx;
            if ($this->sourceIdx>=$this->sourceCnt) {
                $this->tokText = '';
                $this->tokType = 'headingNextColumn';
                return '@eof';
            }    
            $s = trim($this->source[$this->sourceIdx]);
            if ($s == '/*.........END-OF-REPORT-STYLES SECTION --- DO NOT CHANGE THIS LINE........*/') {
                $this->tokText = '';
                $this->tokType = 'headingNextColumn';
                $this->sourceIdx = $this->sourceCnt + 1;
                return '@eof';
            }
            if (substr($s,0,3) === '/*@') {
                $s = substr($s,3);
                $i = strpos($s,'*/');
                if ($i>0)
                    $s = substr($s,0,$i-1);
            }            
            $i = strpos($s,'/*');
            if ($i!=FALSE) {
               $s = substr($s,0,$i);
            }
            if ($s!='' and substr($s,0,2) != '/*') {
                $this->outTok = $this->tokenize($this->source[$this->sourceIdx]);
                $this->outCnt = count($this->outTok);
                $this->outIdx = 0;
            }    
            else    
                $this->outIdx = $this->outIdx+1;  // get next line
        }    
    }    
}

function parseError($pMessage) {
    $s = '<br>ERROR: ???????? '.$pMessage . ' ????? line='.$this->sourceIdx.'   File='.$this->FileName.'<br>';
    echo $s;
    $this->parseError = TRUE;
    //exit($s); //?????????????????
}

function parseAll() {
    $this->tokenGetFirst();
    //$this->sourceCnt = count($this->source);
    //$this->sourceIdx = -1;
    //$this->outCnt = 0;
    //$this->outIdx = 0;
    //$this->tokenGetNext();
    //$this->parseError = FALSE;
    while (TRUE) {
        if ($this->tokType==='headingNextColumn') 
            return;
        $this->parseStyle();
    }
}

function parseStyle() {
    //--- limitation: only single stTag and/or stClass allowed
    //--- must get at least one token to prevent infinite loop
    //--- must leave stRight-brace end of Style as current token
    $this->curClass = '';
    $this->curTag = '';
    if ($this->tokType==='w') {
       $this->curTag = $this->tokText;
       $this->tokenGetNext();
    }
    if ($this->tokText==='.') {
       $tok = $this->tokenGetNext();
       if ($this->tokType==='w') {
           $this->curClass = $this->tokText;
           $this->tokenGetNext();
       }
    }
    //echo '=========================<br>';
    //echo 'stTag = ' . $this->curTag.'<br>';
    //echo 'stClass = ' . $this->curClass.'<br>';
    $this->curStyle = new kcmPage_styleItem($this->curTag,$this->curClass);
    $this->styleList->styleArray[] = $this->curStyle;
    $this->tokenPastExpected(kcmLEFTBRACE);
    //if ($this->parseError)
    //    return;
    $this->parsePropertyList();
    $this->tokenPastExpected(kcmRIGHTBRACE);  // will prevent infinite loop
}

function parsePropertyList() {
    // should be at character after kcmLEFTBRACE 
    do {
        $this->parsePropertyItem();
        $tok = $this->tokText;
        if ($tok===kcmSEMI) 
           $this->tokenGetNext();  
    } while ($tok === kcmSEMI and $this->tokText!==kcmRIGHTBRACE);    
    // expected stRight-brace here (do not check for it here)
}

function parsePropertyItem() {
    //-------- property name is required --------------
    if ($this->tokType!='w') {
        $this->parseError ('Expecting name of Property');            
        return;
    }    
    $propName = $this->tokText;
    $this->tokenGetNext();
    $this->tokenPastExpected(':');  // will prevent infinite loop
    //---- property values
    $propVal = array (); 
    $c = $this->tokType;
    while ($c==='w' or $c==='n' or $c==='h' or $c==='s') {
        $propVal[] = $this->tokText;
        $this->tokenGetNext();
        $c = $this->tokType;
    }    
    $this->curStyle->setProperty($propName,$propVal);
    //echo '..... Prop==> '.$propName. ' Values ==> ';    
    //$s = ' ';
    //for ($i = 0; $i<count($propVal); $i++) {
    //    echo $s. $propVal[$i];    
    //    $s = ', '; 
    //}
    //echo '<br>'.PHP_EOL;    
}

}
?>