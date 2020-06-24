<?php

//--- draff-emitter.inc.php ---

// This sys unit is an independent stand-alone unit not requiring any other include code
// ... and not referencing anything that is KCM or Raccoon specific

//const DRAFF_TYPE_EMIT_HTML     = 'h';
//const DRAFF_TYPE_EMIT_PDF      = 'p';
//const DRAFF_TYPE_EMIT_EXCEL    = 'e';
//const DRAFF_TYPE_EMIT_PREVIEW  = 'v';
//

const EMIT_SEP_SMALL   = '@@S';
const EMIT_SEP_MEDIUM  = '@@M';  // between close buttons, etc
const EMIT_SEP_LARGE   = '@@L';  // between buttons, etc
const EMIT_SEP         = EMIT_SEP_LARGE;
const EMIT_EOL         = '@@E';

abstract class Draff_Emitter_Engine {

public $emit_isIn_report = FALSE;
public $emit_report_mode = 1;  //1=all, 2=print html, 3=excel 4=pdf

public $emit_menu = NULL;
public $emit_options = NULL;
public $emit_form = NULL;
public $emit_export;

public $emit_column_count;  // of current table
public $emit_column_index;  // of current table
public $emit_column_widths = array();  // of current table

public $emit_altRow_classes;
public $emit_altRow_last;
public $emit_altRow_index;

private $emit_htmlStyle = '';  // this determines many descendent styles

//abstract protected function krnEmit_webPageOutput( $scriptData,  $appGlobals, $chain, $form );
abstract protected function set_menu_customize( $appChain, $appGlobals );

function __construct($form,$bodyStyle='',$exportType='h') {
    $this->emit_form = $form;  // form can be NULL
    if (!empty($bodyStyle)) {
        $this->emit_htmlStyle = $bodyStyle;
    }
    $this->emit_options = new Draff_Emitter_Options;
    switch ($exportType) {
       case 'h': $this->emit_export = new rsmp_emitter_export_html($form); break;
       case 'p': $this->emit_export = new draff_report_export_pdf($this->emit_options); break;
       case 'e': $this->emit_export = new draff_report_export_excel($this->emit_options); break;
       default: $this->emit_export  = new rsmp_emitter_export_html($form); break;
    }
    $this->emit_menu = new rsmp_emitter_menu;
    $this->emit_altRow_classes = NULL;
    $this->emit_altRow_last = 0;
    $this->emit_altRow_index = 0;
    $this->report_start();
}

function table_start($class='', $columnInfo=0) {
    if ( is_numeric($columnInfo) ) {
        $this->emit_export->exp_table_start($class, $columnInfo);
        $this->emit_column_count = $columnInfo;
    }
    else if ( is_a ($columnInfo,'rsmp_emitter_table_layout') ) {
        $columnInfo->table_start($this, $class);
    }
    //$this->emit_nrLine('');
    //$this->emit_nrLine('<table'. emit_getString_attribute( 'class=', $class,$this->emit_cssClass_table).'>');
}

function table_end() {
    $this->emit_export->exp_table_end();
}

function table_head_start($class='') {
    $this->emit_export->exp_table_head_start($class);
    //$this->emit_nrLine('<thead'. emit_getString_attribute( 'class=', $class,$this->emit_cssClass_table).'>');
}

function table_head_end() {
    $this->emit_export->exp_table_head_end();
}

function table_body_start($class='') {
    $this->emit_export->exp_table_body_start($class);
}

function table_body_end() {
    $this->emit_export->exp_table_body_end();
}

function table_foot_start($class='') {
    $this->emit_export->exp_table_foot_start($class);
}

function table_foot_end() {
    $this->emit_export->exp_table_foot_end();
}

function row_start($class='') {
    //$this->emit_nrLine('<tr' . emit_getString_attribute( 'class=', $class,$this->emit_cssClass_table) . '>');
    $this->emit_column_index = -1;
    if($this->emit_altRow_last > 0) {
        $this->emit_altRow_index = $this->emit_altRow_index < $this->emit_altRow_last ? ($this->emit_altRow_index+1) : 0;
        $class = $class . ' ' . $this->emit_altRow_classes[$this->emit_altRow_index];
    }
    $this->emit_export->exp_row_start($class);
}

function row_end() {
    $this->emit_export->exp_row_end();
}

function row_oneCell($content,$class='') {
    $this->row_start($class);
    $this->cell_block( $content, $class, 'colspan="'.$this->emit_column_count.'"');
    $this->row_end($class);
}

function cell_block( $content, $class='', $more='') {
    $more = empty($more) ? '' : ' ' . $more;
    $this->cell_start($class,$more);
    $this->content_block($content);
    $this->cell_end();
}

function cell_start($class='', $more='') {
    ++$this->emit_column_index;  // not accurate for colspans
    $more = empty($more) ? '' : ' ' . $more;
    $this->emit_nrLine('<td' . emit_getString_attribute( 'class=', $class) . $more . '>');
}

function cell_end() {
    //  print PHP_EOL .'</td>';
    $this->emit_export->exp_cell_end();
}

//--  function cell_fieldArray($fields, $class='') {
//--      $this->cell_block($this->emit_form->drForm_gen_field($fields), $class);
//--      // ???????????????????
//--  }

function div_block($content,$cssClasses) {
    $this->div_start($cssClasses);
    $this->content_block($content);
    $this->div_end();
}

function div_start($cssClasses='') {
    //print PHP_EOL .'';
    $this->emit_export->exp_div_start($cssClasses);
}

function div_end() {
    $this->emit_export->exp_div_end();
}

function div_toggled_start($cssId, $class='') {
    // ???????? no export function
    $class = ' class="' . draff_concatWithSpaces( 'draff-toggled-div',$class) . '"';
    $this->emit_export->expf_htmlLine_display('');
    $this->emit_export->expf_htmlLine_display('<div id="'.$cssId.'"'.$class.'><!--Toggled-Start-->');
}

function div_toggled_end() {
     // ???????? no export function
   $this->emit_export->expf_htmlLine_display( '</div><!--Toggled-End-->');
}

function div_toggled_buttonEmit($divId, $buttonId, $moreCaption, $lessCaption) {
    // ???????? no export function
    $this->emit_export->expf_htmlLine_display( PHP_EOL . $this->div_toggled_buttonGen($divId, $buttonId, $moreCaption, $lessCaption));
}
function div_toggled_buttonGen($divId, $buttonId, $moreCaption, $lessCaption) {
    // ???????? no export function
   $this->emit_export->expf_htmlLine_display( '<button id="'.$buttonId.'" type="button" onclick="divToggle(\''.$divId.'\',\'draff-menu-item-more\',\''.$moreCaption.'\',\''.$lessCaption.'\')">'.$moreCaption.'</button>');
}

function span_block($content,$cssClasses) {
    $this->span_start($cssClasses);
    $this->content_block($content);
    $this->span_end();
}

function span_start($class='') {
    $this->emit_export->exp_span_start($class);
}

function span_end() {
    $this->emit_export->exp_span_end();
}

function content_field($fieldId) {  // can be mulitple parameters or one array
    $this->emit_export->exp_content_field($fieldId);
}
function content_link($link, $caption, $class) {
    $this->emit_export->exp_content_link($link, $caption, $class);
}
function content_text($content) {
    $this->emit_export->exp_content_text($content);
}
function content_display($htmlText) {
    $this->emit_export->expf_htmlLine_display($htmlText);
}


function content_block() {  // can be multiple parameters or one array
    $params  = func_get_args();
    if ( (count($params)==1) and is_array($params[0]) ) {
        $params = $params[0];
    }
    foreach ($params as $t) {
        if ( is_array($t)) {
            $this->content_block($t);
        }
        else if ( substr($t,0,1)=='@') {
            $fieldId = substr($t,1);
            $this->emit_export->exp_content_field($fieldId);  // if invalid field-id will print as text
        }
        else if ( substr($t,0,1)=='#') {
            $command = substr($t,1,3);
            $count   = substr($t,4);
            if ( ($command=='sep') or ($command=='eol')){
                $this->emit_export->exp_content_seperator($command, $count);
            }
       }
        else {
            $this->emit_export->exp_content_text($t);
        }
    }
}

function content_button_submit ($fieldId, $caption, $class) {
    $fieldId = Draff_Field::drField_normalizeFieldIdParameter($fieldId);
    return '<button class="'.$class.'" type="submit" name="submit" value="'.$fieldId.'">'.$caption.'</button>';
}

function emit_nrLine($s) {  // html non-report line (will not be exported, or appear for html export)
    // ???????? no export function
    print PHP_EOL . $s;
}

function emit_nrAsIs($s) {  // html non-report line (will not be exported, or appear for html export)
    // ???????? no export function
    print $s;
}

// --   function emit_htLine($s) {  // html non-report line (will not be exported, or appear for html export unless critical html for <head>..</..head>, <body></body>, etc)
// --       print PHP_EOL . $s;
// --   }

function toString_phone($phone) {
    // ???????? no export function
    return $this->emit_export->expf_toString_phone($phone);
}

function zone_body_start($chain, $form, $pClass='') {
    // ???????? no export function
    global $deb_inDebug;
    if ($deb_inDebug) {
        $pClass='';
    }
    $class = $this->emit_htmlStyle . ( empty($pClass) ? '' : ( ' ' . $pClass ) );
    $class = ' class="' .  $class . '"';
    $this->emit_export->expf_htmlLine_kernel('') ;
    $this->emit_export->expf_htmlLine_kernel( '<body class="draff-zone-body-normal">');
    if ( $form==NULL) {
        $class = 'draff-zone-noForm ' . ( empty($genreClass) ? '' : (' ' . $genreClass) );
        $this->emit_export->expf_htmlLine_display( '<div class="' . $class .'">');
        $this->zone_isForm = FALSE;
    }
    else {
        $url = $chain->chn_url_getString();
        // $form->drForm_form_start();
        $this->emit_export->expf_htmlLine_display('');
        $class=' class="draff-zone-container sy-genre-default"';  //????????????????????????
        $this->emit_export->expf_htmlLine_display( '<form class="draff-zone-form-normal" action="' . $url . '" method="post" id="form" name="form">');
        $this->zone_isForm = TRUE;
    }
   // $this->zone_menu_toggled();
}

function zone_body_end() {
    $this->emit_export->expf_htmlLine_kernel($this->zone_isForm ? '</form>' : '</div>');
    // ???????? no export function
    $this->emit_export->expf_htmlLine_kernel('</body>');
    $this->emit_export->expf_htmlLine_kernel('</html>');
}

function zone_start($class) {
    $this->emit_export->expf_htmlLine_display( '');
    $this->emit_export->expf_htmlLine_display( '<div class="'.$class.'">');
}

function zone_end($class='') {
    $this->emit_export->expf_htmlLine_display( '</div>');
    $this->emit_export->expf_htmlLine_display( '');
}

function getString_phone($phoneText,$class='') {
    //###-###-####
    //012345678901234
    //#-###-###-####
    $phoneText = rc_cleanPhoneNumber($phoneText);
    $start = substr($phoneText,1)=='-' ? 5 : 3;
    if ( (substr($phoneText,$start,1)=='-') and (substr($phoneText,$start+4,1)=='-') ) {
        $phone = substr($phoneText,0,$start+9);
        $rem = substr($phoneText,$start+9);
        $phoneText = '<a href="tel:'. $phone . '">'.$phone.' </a>' . '<br><span class="fc-40">'.$rem.'</span>' ;
    }
    else if ( !empty($phoneText)) {
        $phoneText = '<span class="font-autoSize-70">' . $phoneText.'</span>';
    }
    return $phoneText;
}

function getString_link($url,$text,$class='') {
    $class = empty($class) ? '' : ' class="'.$class.'"';
    return '<a href="' . $url . '"' . $class . '>'.$text.'</a>';
}

function getString_button( $caption, $class='', $value) {
    $class = empty($class) ? '' : ' class="'.$class.'"';
    return '<button type="submit" '. $class . ' name="submit" value="'.$value.'">'  . $caption . '</button>';
}

static function getString_sizedString( $text, $maxCharWidth) { // no <br> allowed
    $width = strlen($text);
    if ( $width<=$maxCharWidth) {
        return $text;
    }
    $per = ($maxCharWidth * 100) / $width;
    if ( $per <= 40) {$cl = 'font-autoSize-40';}
    else if ( $per <= 50) {$cl = 'font-autoSize-50';}
    else if ( $per <= 60) {$cl = 'font-autoSize-60';}
    else if ( $per <= 70) {$cl = 'font-autoSize-70';}
    else if ( $per <= 80) {$cl = 'font-autoSize-80';}
    else if ( $per <= 90) {$cl = 'font-autoSize-90';}
    else { return $text;}
    return '<span class="'.$cl.'">'.$text.'</span>';
}

static function getString_sizedMemo( $text, $maxCharWidth) {
    // only one <br> allowed - should be better written to handle multiple <br> and maybe eof/eol
    $br = strpos($text,'<br>');
    if ( $br===FALSE) {
        return Draff_Emitter_Engine::getString_sizedString($text, $maxCharWidth);
    }
    else {
        return Draff_Emitter_Engine::getString_sizedString(substr($text,0,$br), $maxCharWidth)
               . '<br>' . Draff_Emitter_Engine::getString_sizedString( substr($text,$br+5), $maxCharWidth);
    }
}
    
    function report_start() {
        $this->emit_isIn_report = TRUE;
        $this->emit_export->exp_report_start($this->emit_report_mode);
    }
    
    function report_end() {
        $this->emit_isIn_report = FALSE;
        $this->emit_export->exp_report_end();
    }
    
    function zone_htmlHead($shortTitle) {
        global $deb_inDebug;
        $this->addOption_toggledDivScript();  // will be printed latter with other saves lines   //~~~~~~~~~~~~~~~
        $this->emit_export->expf_htmlLine_kernel('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">');
        $class = ($deb_inDebug) ? '' : ' class="draff-zone-html-normal"';
        $this->emit_export->expf_htmlLine_kernel('<html'.$class.'>');
        $this->emit_export->expf_htmlLine_kernel('<head>');
        $this->emit_export->expf_htmlLine_kernel('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
        $this->emit_export->expf_htmlLine_kernel('<meta name="viewport" content="width=device-width", initial-scale=1.0">');
        $this->emit_export->expf_htmlLine_kernel( "<title>{$shortTitle}</title>");
        $this->zone_htmlHeadArray($this->emit_options->emtSetting_htmlHead_lines);    //~~~~~~~~~~~~~~~
        if ( count($this->emit_options->emtSetting_cssRules) >= 1) {
            $this->emit_export->expf_htmlLine_kernel( '<style>');
            $this->zone_htmlHeadArray($this->emit_options->emtSetting_cssRules);    //~~~~~~~~~~~~~~~
            $this->emit_export->expf_htmlLine_kernel('</style>');
        }
        if ( count($this->emit_options->emtSetting_jsScripts) >= 1) {
            $this->emit_export->expf_htmlLine_kernel( '<script>');
            $this->zone_htmlHeadArray($this->emit_options->emtSetting_jsScripts);   //~~~~~~~~~~~~~~~
            $this->emit_export->expf_htmlLine_kernel( '</script>');
        }
        $this->emit_export->expf_htmlLine_kernel('</head>');
    }
    
    private function zone_htmlHeadArray(&$lines) {
        for ($i=0; $i<count($lines); ++$i) {
            $this->emit_export->expf_htmlLine_kernel( $lines[$i]);
        }
    }
    
    function zone_menu() {
        // Menu is split so menu can be easily adjusted for additional items
        $this->zone_menu_visible_start();
        $this->zone_menu_visible_items();
        $this->zone_menu_visible_end();
    }
    
    function zone_menu_visible_start() {
        if ($this->emit_menu->menu_containsToggledItems) {
            $this->zone_menu_toggled();
        }
        $this->zone_start('zone-ribbon theme-menu');
    }
    function zone_menu_visible_items() {
        foreach ( $this->emit_menu->menu_list as $menuKey => $menuItem ) {
            if ( ( !$menuItem->menuItem_toggled) or ($menuKey == $this->emit_menu->menu_currentPageKey) ) {
                if ($menuItem->menuItem_type==2) {
                    $caption = $menuItem->menuItem_caption;
                    $class = ($menuKey === $this->emit_menu->menu_currentPageKey) ? ' draff-menu-item-curent' : '';
                    $this->emit_export->expf_htmlLine_display( '<div class="draff-menu-item'.$class.'"><a class="draff-menu-item'.$class.'" href="' . $menuItem->menuItem_url . '">'.$caption.'</a></div>');
                }
                else if ($menuItem->menuItem_type==5) {
                    $this->emit_export->expf_htmlLine_display( '<div class="draff-menu-banner-extension">'. $menuItem->menuItem_caption  . '</div>'  );
                }
            }
        }
        if ( $this->emit_menu->menu_containsToggledItems) {
            $this->div_toggled_buttonEmit('draff-toggled-menu','draff-menu-item-more','Show<br>More','Show<br>Less');
        }
    }
    function zone_menu_visible_end() {
        $this->zone_end();
    }
    
    function zone_menu_toggled() {  // generate code for the menu that is usually hidden unless the 'more' menu item is clicked
        $this->div_toggled_start('draff-toggled-menu');
        foreach($this->emit_menu->menu_list as $menuKey=>$menuItem) {
            if ( ! $menuItem->menuItem_toggled) {
                continue;
            }
            if ( $menuItem->menuItem_option ==2) {
                continue;
            }
            $caption = $menuItem->menuItem_caption;
            switch ($menuItem->menuItem_type) {
                case 1:
                    $this->emit_export->expf_htmlLine_display( '<div class="draff-menu-line-block">');
                    $this->emit_export->expf_htmlLine_display( '<div class="draff-menu-line-title">'.$caption.'</div>');
                    //   print PHP_EOL . '<legend class="draff-menu-legend">'.$this->emit_menu->menu_itemCaption[$i].'</legend>';
                    break;
                case 2:
                    $class = ($menuItem->menuItem_key === $this->emit_menu->menu_currentPageKey) ? ' draff-menu-item-curent' : '';
                    $this->emit_export->expf_htmlLine_display( '<div class="draff-menu-item'.$class.'"><a class="draff-menu-item'.$class.'" href="' . $menuItem->menuItem_url . '">'.$caption.'</a></div>');
                    break;
                case 3:
                    $this->emit_export->expf_htmlLine_display( '</div>');
                    break;
                
                case 4:
                    $class = ($i === $this->emit_menu->menu_currentPageKey) ? ' draff-menu-item-curent' : '';
                    $this->emit_export->expf_htmlLine_display( '<div class="draff-menu-item'.$class.'"><a class="draff-menu-item'.$class.'" target="_blank" href="' . $this->emit_menu->menu_itemUrl[$i] . '">'.$caption.'</a></div>');
                    break;
            }
        }
        //print PHP_EOL.'</div>';
        $this->div_toggled_end();
    }
    
    
    function zone_messages($chain, $form) {   //~~~~~~~~~~~~~~~
        // ???????? no export function
        // $session = draff_get_session();
        if ($chain->chn_messages->ses_arrayIsEmpty()) {
            return;  // no messages
        }
        $isFirst = TRUE;
        $messages = $chain->chn_messages->ses_arrayGet(); //must be a reference if deleting selected messages
        $htmlArray = array();
        foreach ($messages as $messageArray) {
            $message = $messageArray['message'];
            $errType = $messageArray['type'];
            $fieldId = $messageArray['fieldId'];
            if ($errType==DRAFF_TYPE_MSG_FIELD) {
                if ($isFirst) {
                    $htmlArray[] = 'Please correct the following:<ul>';
                    $isFirst = FALSE;
                }
                $s = '<li>'.$message.'</li>';
                $htmlArray[] = '<li>'.$s.'</li>';
                $form->drForm_field_setError ($fieldId, $message);
                $class='';
            }
            else {
                switch ($errType) {
                    case DRAFF_TYPE_MSG_STATUS:
                        $class= 'theme-message-status';
                        break;
                    case DRAFF_TYPE_MSG_ERROR:
                        $class= 'theme-message-error-error';
                        break;
                    default:
                        $class= 'theme-message-error-error';
                        break;
                }
                $htmlArray[] = '<div class="'.$class.'">'.$message . '</div>';
            }
        }
        $this->emit_export->expf_htmlLine_display(  '');
        $this->emit_export->expf_htmlLine_display( '<div class="draff-theme-message-error-zone-container">');
        for ($i=0; $i < count($htmlArray); ++$i) {
            $this->emit_export->expf_htmlLine_display(  $htmlArray[$i]);
        }
        $this->emit_export->expf_htmlLine_display(  '</div>');
        $this->emit_export->expf_htmlLine_display(  '');
    }

//function zone_menu_emitItem() {
//    $this->emit_menu
//    $caption = $this->emit_menu->menu_itemCaption[$i];
//    $class = ($i === $this->emit_menu->menu_currentPageKey) ? ' draff-menu-item-curent' : '';
//    $this->emit_export->expf_htmlLine_display( '<div class="draff-menu-item'.$class.'"><a class="draff-menu-item'.$class.'" href="' . $this->emit_menu->menu_itemUrl[$i] . '">'.$this->emit_menu->menu_itemCaption[$i].'</a></div>');
//}
    
    function addOption_styleLine($styleLine) {
        // ???????? no export function
        $this->emit_options->emtSetting_cssRules[] = $styleLine;
    }
    
    function addOption_styleTag($styleTag, $style) {
        $this->emit_options->emtSetting_cssRules[] = $styleTag . '{' . $style . '}';
    }
    
    function addOption_styleFile($cssPath, $media="all", $levelStr="") {
        $timestamp = filemtime( __DIR__ . "/" . $levelStr . $cssPath );
        $this->emit_options->emtSetting_htmlHead_lines[] = "<link rel='stylesheet' type='text/css' media='{$media}' href='{$levelStr}{$cssPath}?v={$timestamp}'>";
    }
    
    function addOption_htmlHeadLines($lines) {
        foreach ($lines as $s) {
            $this->emit_options->emtSetting_htmlHead_lines[] = $s;
        }
    }
    
    function addOption_htmlHeadLine($line) {
        $this->emit_options->emtSetting_htmlHead_lines[] = $line;
    }
    
    function addOption_toggledDivScript() {
        if ($this->emit_options->emtSetting_jsToggledScript) {
            return; // already included
        }
        $this->emtSetting_jsToggledScript = TRUE;
        $js = array();
        $js[] = '<script>';
        $js[] = 'function divToggle( moreDivId, buttonId, moreDesc, lessDesc) {';
        $js[] = 'var moreDiv = document.getElementById(moreDivId);';
        $js[] = 'var button = document.getElementById(buttonId);';
        $js[] = "display=moreDiv.style.display;";
        $js[] = "moreDiv.style.display=(display=='block')?'none':'block';";
        $js[] = "if ( lessDesc!='') {button.innerHTML=(display=='block')?moreDesc:lessDesc;}";
        $js[] = '}';
        $js[] = 'function divToggleCancel( moreDivId, buttonId) {';
        $js[] = 'var moreDiv = document.getElementById(moreDivId);';
        $js[] = 'var button = document.getElementById(buttonId);';
        $js[] = "display=moreDiv.style.display;";
        $js[] = "moreDiv.style.display=(display=='block')?'none':'block';";
        $js[] = "button.form.submit();";
        $js[] = '}';
        $js[] = '</script>';
        $this->emit_options->emtSetting_htmlHead_lines = array_merge($this->emit_options->emtSetting_htmlHead_lines, $js);
        //$this->htmlHead_addLine("alert('got here');");
        //$this->htmlHead_addLine("form=document.forms[0].id;");
    }
    
} // end class

// class emitHtml {
//
// function emGen_span($content,$pClass,$pStyle='') {
//     $class = empty($pClass) ? '' : ' class="' . $pClass . '"';
//     $style = empty($pStyle) ? '' : ' style="' . $pStyle . '"';
//     return '<span' . $class . $style . '>'.$content.'</span>';
// }
//
// function emGen_iconButton( $iconFile, $value) {
//     $class = 'class="draff-button-icon" ';
//     return '<button type="submit" '. $class . ' name="submit" value="'.$value.'"><img class="draff-button-icon"  src="'.$iconFile.'"></button>';
// }

// } // end class

class Draff_Emitter_Options {

public $emtSetting_htmlHead_lines = array();
public $emtSetting_cssFilePath     = array();
public $emtSetting_cssFileMedia    = array();
public $emtSetting_cssFileLevel    = array();
public $emtSetting_cssRules        = array();
public $emtSetting_jsScripts       = array();
public $emtSetting_jsToggledScript = FALSE;

function __construct() {
}

}

class rsmp_emitter_menu_item {
public $menuItem_key;
public $menuItem_caption;
public $menuItem_url;
public $menuItem_type;
public $menuItem_option;  //1=favorite 2=always (do not show on "show items", already visible)
public $menuItem_toggled;   // 1=visible menu 2=toggled menu

function __constuct() {
}

} // end class

class rsmp_emitter_menu {
public $menu_list = array();
public $menu_currentPageKey = NULL;
public $menu_containsVisibleItems = FALSE;
public $menu_containsToggledItems = FALSE;
private $menu_currentToggled = FALSE;
private $menu_curSript;

function __construct() {
   $this->menu_curSript = '$' . basename($_SERVER["SCRIPT_FILENAME"]);
}


function menu_addLevel_top_start() {
$this->menu_currentToggled = FALSE;
}

function menu_addLevel_top_end() {
$this->menu_currentToggled = NULL;
}

function menu_addLevel_toggled_start() {
$this->menu_currentToggled = TRUE;
}

function menu_addLevel_toggled_end() {
$this->menu_currentToggled = NULL;
}

function menu_setItemState($newItem) {
    $newItem->menuItem_toggled = $this->menu_currentToggled;
    if ($this->menu_currentToggled) {
        $this->menu_containsToggledItems = TRUE;
   }
    else {
        $this->menu_containsVisibleItems = TRUE;
    }
}

function menu_addGroup_start($triggerId,$caption) {
    $triggerId .= '$start';
    $newItem = new rsmp_emitter_menu_item;
    $this->menu_list[$triggerId] = $newItem;
    $newItem->menuItem_caption = $caption;
    $newItem->menuItem_key = $triggerId;
    $newItem->menuItem_url = '';
    $newItem->menuItem_type = 1;
    $newItem->menuItem_option = 0;
    $this->menu_setItemState($newItem);
}

function menu_addGroup_end($triggerId) {
    $triggerId .= '$end';
    $newItem = new rsmp_emitter_menu_item;
    $this->menu_list[$triggerId] = $newItem;
    $newItem->menuItem_caption = '';
    $newItem->menuItem_url = '';
    $newItem->menuItem_type = 3;
    $newItem->menuItem_key = $triggerId;
    $newItem->menuItem_option = 0;
    $this->menu_setItemState($newItem);
}

function menu_addTitleExtension ($triggerId, $caption) {
    $newItem = new rsmp_emitter_menu_item;
    $this->menu_list[$triggerId] = $newItem;
    $newItem->menuItem_key = $triggerId;
    $newItem->menuItem_caption = $caption;
    $newItem->menuItem_url = '';
    $newItem->menuItem_type = 5;
    $newItem->menuItem_option = 2;
    $this->menu_setItemState($newItem);
}

function menu_addItem($chain, $triggerId, $caption, $url, $params=NULL, $inheritedParams=TRUE) {
    // if ( ($inheritedParams===TRUE) ) { //  or ($params==='??')
    //     $url = $chain->chn_url_getString($url, TRUE, $params);
    // }
    // else if ( is_array($params) ) {
    //      $url = $chain->chn_url_getString($url, FALSE, $params);
    // }
    $url = $chain->chn_url_getString($url, $inheritedParams, $params);
    $newItem = new rsmp_emitter_menu_item;
    $this->menu_list[$triggerId] = $newItem;
    $newItem->menuItem_key = $triggerId;
    $newItem->menuItem_caption = $caption;
    $newItem->menuItem_url = $url;
    $newItem->menuItem_type = 2;
    $newItem->menuItem_option = 1;
    $this->menu_setItemState($newItem);
    $urlBase = '$' . basename($url);
    if ( strpos( $urlBase, $this->menu_curSript) !== FALSE) {
        $this->menu_currentPageKey = ($this->menu_currentPageKey==NULL) ? $triggerId : '';  // if same script has multiple menu options script must specify current page
    }
}

function menu_getItem($menuItemKey) {
    $menuItem = isset ($this->menu_list[$menuItemKey]) ? $this->menu_list[$menuItemKey] : NULL;
    return $menuItem;
}

function menu_markTopLevelItem($keyMask, $status=1) {
    if ( substr($keyMask,-1)=='*') {
        $keyMask = substr($keyMask,1,-1);
        $len = strlen($keyMask);
        $count = count($this->menu_itemId);
        foreach ($this->menu_list as $key->$item) {
            if ( substr($item->menuItem_key,0,$len)==$keyMask) {
                $item->menuItem_option = $status;
            }
        }
    }
    else {
        $item = $this->menu_getItem($keyMask);
        if ( !empty($item)) {
            $item->menuItem_option = $status;
        }
    }
}

function menu_markCurrentItem($itemKey) {
    $item = $this->menu_getItem($itemKey);
    $this->menu_currentPageKey = empty($item) ? NULL : $itemKey;
}

} // end class

class rsmp_emitter_export_html {
private $emitOut_form;
public  $emitOut_inReport = NULL;
public  $emitOut_reportMode = NULL;

function __construct($form) {
    $this->emitOut_form = $form;
}

function exp_report_start($reportMode) {
    $this->emitOut_inReport = TRUE;
    $this->emitOut_reportMode = $reportMode;
}

function exp_report_end() {
    $this->emitOut_inReport = FALSE;
}

function exp_content_text($txt) {
    $this->expf_emit_hLine($txt);
}

function exp_content_field($field) {
    $this->expf_emit_hLine($this->emitOut_form->drForm_gen_field($field));  // field can be field id or text (if invalid field id)
}

function exp_content_seperator($command, $count) {
    $htmlChar = ($command=='eol') ? '<br>' : '&nbsp;';
    $html = str_repeat( $htmlChar, empty($count) ? 1 : $count);
    $this->expf_emit_hLine($html);
}

function exp_content_link($link, $caption, $class) {
    $s =  '<a class="'.$class.'" href="'.$link.'">'. $caption . ' </a>';
    $this->expf_emit_hLine($s);
}

function exp_table_start($class='', $colCount) {  // $colcount not needed for html export (but may be useful for other exports)
    $this->expf_emit_hLine('');
    $this->expf_emit_hLine('<table'. emit_getString_attribute( 'class=', $class).'>');
}

function exp_table_end() {
    $this->expf_emit_hLine('</table>');
    $this->expf_emit_hLine('');
}

function exp_table_head_start($class='') {
    $this->expf_emit_hLine('<thead'. emit_getString_attribute( 'class=', $class).'>');
}

function exp_table_head_end() {
    $this->expf_emit_hLine('</thead>');
 }

function exp_table_body_start($class='') {
    $this->expf_emit_hLine('<tbody'. emit_getString_attribute( 'class=', $class).'>');
}

function exp_table_body_end() {
    $this->expf_emit_hLine('</tbody>');
}

function exp_table_foot_start($class='') {
    $this->expf_emit_hLine('<tfoot'. emit_getString_attribute( 'class=', $class).'>');
}

function exp_table_foot_end() {
    $this->expf_emit_hLine('</tfoot>');
}

function exp_row_start($class='') {
    $this->expf_emit_hLine('<tr' . emit_getString_attribute( 'class=', $class) . '>');
}

function exp_row_end() {
    $this->expf_emit_hLine('</tr>');
}

function exp_cell_start($class='', $more='') {
    ++$this->emit_column_index;  // not accurate for colspans
    $more = empty($more) ? '' : ' ' . $more.'"';
    $this->expf_emit_hLine('<td' . emit_getString_attribute( 'class=', $class) . $more . '>');
 }

function exp_cell_end() {
    $this->expf_emit_hLine('</td>');
}

function exp_div_start($class='') {
    $this->expf_emit_hLine('');
    $this->expf_emit_hLine('<div'.emit_getString_attribute( 'class=', $class).'>');
}

function exp_div_end() {
    $this->expf_emit_hLine('</div>');
    $this->expf_emit_hLine('');
}

function exp_span_start($class='') {
    $this->expf_emit_hLine('');
    $this->expf_emit_hLine('<span'.emit_getString_attribute( 'class=', $class).'>');
}

function exp_span_end() {
    $this->expf_emit_hLine('</span>');
    $this->expf_emit_hLine('');
}

private function expf_emit_hLine($s) {
    if ( $this->emitOut_inReport===FALSE) {
        return;
    }
    print PHP_EOL . $s;
}
function expf_htmlLine_kernel($s) {
    // needed for print reports
    print PHP_EOL . $s;
}
function expf_htmlLine_display($s) {
    // not needed for print reports - javascript, menus, banners, etc
    if ( $this->emitOut_reportMode==1) {
        print PHP_EOL . $s;
    }
}

// function content_button_submit ($fieldId, $caption, $class) {
//     // to print submit buttons which are not defined - class is required
//     $this->emit_nrLine('<button class="'.$class.'" type="submit" name="submit" value="'.Draff_Field::drField_normalizeFieldIdParameter($fieldId).'">'.$caption.'</button>');
// }
function expf_toString_phone($phoneText) {  // ??????  defined else where (prefer not to duplicate)
    //###-###-####
    //012345678901234
    //#-###-###-####
    $phoneText = rc_cleanPhoneNumber($phoneText);
    $start = substr($phoneText,1)=='-' ? 5 : 3;
    if ( (substr($phoneText,$start,1)=='-') and (substr($phoneText,$start+4,1)=='-') ) {
        $phone = substr($phoneText,0,$start+9);
        $rem = substr($phoneText,$start+9);
        $phoneText = '<a href="tel:'. $phone . '">'.$phone.' </a>' . '<br><span class="fc-40">'.$rem.'</span>' ;
    }
    else if ( !empty($phoneText)) {
        $phoneText = '<span class="font-autoSize-70">' . $phoneText.'</span>';
    }
    return $phoneText;
}

} // end class


class rsmp_emitter_table_layout {
public $emTable_id;
public $emTable_col_count = 0;
public $emTable_col_widths = array();
public $emTable_col_total;

function __construct($id, $widths) {
    $this->emTable_id = $id;
    $this->emTable_col_count  = count($widths);
    $this->emTable_col_widths = $widths;
    $this->emTable_col_total = array_sum($widths);

}

private function emTab_toAmount($width) {
    return round( ( ($width+1) * 0.55), 3) . 'em';
}

function emTab_emitStyles($emitter) {
    for ($i=0; $i<$this->emTable_col_count; ++$i) {
        $colWidth = emTab_toAmount($this->emTable_col_widths[$i]);
        $colKey = $this->emTable_id . ($i+1);
        $emitter->addOption_styleTag('col.' . $colKey,'width:'.$colWidth.';');
    }
}

function table_start($emitter, $class='') {
    //  $tableWidths = array ('t1',1,2,3,4,5);
    //  $emitter->addWidthStyles($tableKey, $columnWidth, $columnEnabled);
    // $emitter->tableStart('',$tableWidths);
    $tableWidth = $this->emTab_toAmount($this->emTable_col_total);
    $tableStyle = 'style=" width:' . $this->emTab_toAmount($this->emTable_col_total+1) .';"';
    $emitter->emit_nrLine('<table'. emit_getString_attribute( 'class=', $class).$tableStyle.'>');
    $emitter->emit_nrLine('<colgroup>');
    $total = 0;
    for ($i=0; $i<$this->emTable_col_count; ++$i) {
        $colKey = $this->emTable_id . ($i+1);
        $width = $this->emTab_toAmount($this->emTable_col_widths[$i]);
        $emitter->emit_nrLine('<col style="width:'.$width.';">');
    }
    $emitter->emit_nrLine('</colgroup>');
    $emitter->emit_column_count = $this->emTable_col_count;
}

} // end class

// the emit_ functions should never be used for reports

function emit_span($content,$class) {
	return '<span class="'.$class.'">'.$content.'</span>';
}

function emit_div($content,$class) {
	return '<div class="'.$class.'">'.$content.'</div>';
}

function edit_button_submit ($buttonId, $caption, $class) {
    return '<button class="'.$class.'" type="submit" name="submit" value="'.$buttonId.'">'.$caption.'</button>';
}

function emit_getString_attribute( $atrStart, $pClass1, $pClass2 = '') {
    if ( empty($pClass1) ) {
        $c = $pClass2;
    }
    else if ( empty($pClass2) ) {
        $c = $pClass1;
    }
    else {
        $c = $pClass1 . ' ' . $pClass2;
    }
    return (empty($c)) ? ('') : (' '.$atrStart.'"'.$c.'"');
}

?>