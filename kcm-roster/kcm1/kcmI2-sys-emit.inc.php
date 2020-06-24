<?php

//--- kcmI2-sys-emit.inc.php ---

// This sys unit is an independent stand-alone unit not requiring any other include code
// ... and not referencing anything that is KCM or Raccoon specific

class emit {
   
static function emLine($s) {
    print PHP_EOL . $s;
}   

static function emAsIs($s) {
    print $s;
}   

static function emRow_start($class='') {
    $class = empty($class) ? '' : ' class="'.$class.'"';
    self::emLine('<tr' . $class . '>');
}

static function emRow_end() {
    self::emLine('</tr>');
}

static function emTable_start($class='') {
    $class = empty($class) ? '' : ' class="'.$class.'"';
    self::emLine('');
    self::emLine('<table'.$class.'>');
}   

static function emTable_end() {
    self::emLine('</table>');
    self::emLine('');
}   

static function emDiv($content,$class,$style='') {
    $class = empty($class) ? '' : ' class="' . $class . '"';
    $style = empty($style) ? '' : ' style="' . $style . '"';
    self::emLine('<div' . $class . $style . '>'.$content.'</div>');
}   

static function emDiv_start($class='') {
    $class = empty($class) ? '' : ' class="'.$class.'"';
    self::emLine('');
    self::emLine('<div'.$class.'>');
}   

static function emDiv_end() {
    self::emLine('</div>');
    self::emLine('');
}   

static function emSpan($content,$class,$style='') {
    $class = empty($class) ? '' : ' class="' . $class . '"';
    $style = empty($style) ? '' : ' style="' . $style . '"';
    self::emLine('<span' . $class . $style . '>'.$content.'</span>');
}   

static function emCell( $txt, $class='', $more='') {
    $class = empty($class) ? '' : ' class="'.$class.'"';
    $more = empty($more) ? '' : ' ' . $more.'"';
    self::emLine('<td' . $class . $more . '>' . $txt . '</td>');
}
 
static function emCell_start($class='', $more='') {
    $class = empty($class) ? '' : ' class="'.$class.'"';
    $more = empty($more) ? '' : ' ' . $more.'"';
    self::emLine('<td' . $class . $more . '>');
 }
 
static function emCell_end() {
    self::emLine('</td>');
 }

static function emLink($url,$text,$class='') {
    self::emLine(emitHtmlGen::emGenHtmlLink($url,$text,$class));
}

static function emHeader($title) {
    emitHeader::emPrintHeader($title);
}

static function Messages_error($sysForm) {
   if ( !empty($sysForm->form_message_error) ) {
        self::emLine( '');
        self::emLine( '<div class="sy-message-container">');
        self::emLine( '<div class="sy-message-error">');
        self::emLine( 'Error: ' . $sysForm->form_message_error);
        self::emLine( '</div>');
        self::emLine(  '</div>');
        self::emLine( '');
        $sysForm->set_message_error('');
    }
}

static function Messages_status($sysForm) {
   if ( !empty($sysForm->form_message_status) ) {
        self::emLine('');
        self::emLine( '<div class="sy-message-container">');
        self::emLine( '<div class="sy-message-status">');
        self::emLine( $sysForm->form_message_status);
        self::emLine(  '</div>');
        self::emLine(  '</div>');
        self::emLine('');
        $sysForm->set_message_status('');
    }
}

static function Messages_IfAny($sysForm) {
    self::Messages_status($sysForm);
    self::Messages_error($sysForm);
}

static function Field_button($sysForm, $fieldId) {  //????????????? only used once
    self::emLine($sysForm->getHtml_Button($fieldId));
}

static function emBody_start($class='sy-body-standard') {
   $class = empty($class) ? '' : ' class="'.$class.'"';
   emit::emLine('');
   emit::emLine('<body' . $class . '>');  
}

static function emBody_end() {
    emit::emLine('</body>');
    emit::emLine('</html>');
}

} // end class

class emitContentZone {
static $emConForm;    
    
static function emContainer_start($genreClass, $sysForm=NULL) {
    self::$emConForm = $sysForm;
    emit::emLine('');
    if ($sysForm===NULL) {
         emit::emDiv_start('sy-content-container '. $genreClass);
    }
    else {
        $sysForm->form_print_start('sy-content-container ' . $genreClass); 
    }    
    emitMenu::muEmitMenu();
}

static function emContainer_end($sysForm=NULL) {
    if (self::$emConForm===NULL) {
        emit::emDiv_end();
    }
    else {    
        $sysForm->form_print_end(); 
        emit::emLine('');
   }
}

static function emHeader_start($genreClass='sy-genre-default') {
    emit::emLine('');
    emit::emLine('<div class="sy-content-header">');
}

static function emHeader_end() {
    emit::emLine('</div>');
    emit::emLine('');
}

static function emScrollable_start($genreClass='sy-genre-default') {
    emit::emLine('');
    emit::emLine('<div class="sy-content-scrollable">');
}

static function emScrollable_end() {
    emit::emLine('</div>');
    emit::emLine('');
}

static function emFooter_start($genreClass='sy-genre-default') {
    emit::emLine('');
    emit::emLine('<div class="sy-content-footer">');
}

static function emFooter_end() {
    emit::emLine('</div>');
    emit::emLine('');
}

} // end class


class emitHtmlGen {
    
static function emGenHtmlLink($url,$text,$class='') {
    $class = empty($class) ? '' : ' class="'.$class.'"';
    return '<a href="' . $url . '"' . $class . '>'.$text.'</a>';
}

static function emGenPhoneLink($phoneText,$class='') {
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
    else if (!empty($phoneText)) {
        $phoneText = '<span class="sy-fc-70">' . $phoneText.'</span>';
    }
    return $phoneText;
}

static function emGenDiv($content,$class,$style='') {
    $class = empty($class) ? '' : ' class="' . $class . '"';
    $style = empty($style) ? '' : ' style="' . $style . '"';
    return '<div' . $class . $style . '>'.$content.'</div>';
}   

static function emGenSpan($content,$pClass,$pStyle='') {
    $class = empty($pClass) ? '' : ' class="' . $pClass . '"';
    $style = empty($pStyle) ? '' : ' style="' . $pStyle . '"';
    return '<span' . $class . $style . '>'.$content.'</span>';
}   

static function emGenSubmitButton( $caption, $class='', $value) {
    $class = empty($class) ? '' : ' class="'.$class.'"';
    return '<button type="submit" '. $class . ' name="submit" value="'.$value.'">'  . $caption . '</button>';
}

static function emGenSubmitIconButton( $iconFile, $value) {
    $class = 'class="sy-iconButton" ';
    return '<button type="submit" '. $class . ' name="submit" value="'.$value.'"><img class="sy-iconButton"  src="'.$iconFile.'"></button>';
}

static function emGenSizedSegment( $text, $maxCharWidth) { // no <br> allowed
    $width = strlen($text);
    if ($width<=$maxCharWidth) {
        return $text;
    }    
    $per = ($maxCharWidth * 100) / $width;
    if ($per <= 40) {$cl = 'sy-fc-40';}
    else if ($per <= 50) {$cl = 'sy-fc-50';}
    else if ($per <= 60) {$cl = 'sy-fc-60';}
    else if ($per <= 70) {$cl = 'sy-fc-70';}
    else if ($per <= 80) {$cl = 'sy-fc-80';}
    else if ($per <= 90) {$cl = 'sy-fc-90';}
    else { return $text;}
    return '<span class="'.$cl.'">'.$text.'</span>';
}

static function emGenSizedText( $text, $maxCharWidth) { // only one <br> allowed
    $br = strpos($text,'<br>');
    if ($br===FALSE) {
        return self::emGenSizedSegment($text, $maxCharWidth);
    }
    else {
        return self::emGenSizedSegment(substr($text,0,$br), $maxCharWidth)
               . '<br>' . self::emGenSizedSegment( substr($text,$br+5), $maxCharWidth);
    }  
}
 
} // end class

class emitHeader {
    
static $emHeader_line    = array();    
static $emHeader_style   = array();  // for styles - <style> and </style> not includes  
static $emHeader_js      = array();  

function __construct() {
    self::emAddToggleScript();
}
  
static function emAddLine($line) {
    self::$emHeader_line[] = $line;
}

static function emAddCssFile($cssPath, $media="all", $levelStr="") {  
    // code copied from rc_define - but kcm2 uses rc_define function, not this function
	$timestamp = filemtime( __DIR__ . "/" . $cssPath );
    self::$emHeader_line[] = "<link rel='stylesheet' type='text/css' media='{$media}' href='{$levelStr}{$cssPath}?v={$timestamp}'>";
}

static function emAddStyleLine($styleLine) {
    self::$emHeader_style[] = $styleLine;
}

static function emAddStyle($styleTag, $style) {
    self::$emHeader_style[] = $styleTag . '{' . $style . '}';
}

static function emOutArray(&$lines) {
    for ($i=0; $i<count($lines); ++$i) {
        emit::emLine($lines[$i]);
    }    
}
 
static function emAddToggleScript() {
    emitHeader::emAddLine('<script>');
    emitHeader::emAddLine('function divToggle( moreDivId, buttonId, moreDesc, lessDesc) {');
    emitHeader::emAddLine('var moreDiv = document.getElementById(moreDivId);');
    emitHeader::emAddLine('var button = document.getElementById(buttonId);');
    emitHeader::emAddLine("display=moreDiv.style.display;");
    emitHeader::emAddLine("moreDiv.style.display=(display=='block')?'none':'block';");
    emitHeader::emAddLine("if (lessDesc!='') {button.innerHTML=(display=='block')?moreDesc:lessDesc;}");
    emitHeader::emAddLine('}');
    emitHeader::emAddLine('function divToggleCancel( moreDivId, buttonId) {');
    emitHeader::emAddLine('var moreDiv = document.getElementById(moreDivId);');
    emitHeader::emAddLine('var button = document.getElementById(buttonId);');
    emitHeader::emAddLine("display=moreDiv.style.display;");
    emitHeader::emAddLine("moreDiv.style.display=(display=='block')?'none':'block';");
    emitHeader::emAddLine('}');
    emitHeader::emAddLine('</script>');
} 

static function emPrintHeader($title) {
    self::emAddToggleScript();  // will be printed latter with other saves lines
    // should not be called directly - should call from emit::emHeader;  (all emits should be from sys_emit)
    //$title = $this->wph_pageTitleShort;
    emit::emLine( '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">');
    emit::emLine( '<html>');
    emit::emLine( '<head>');
    emit::emLine( '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
    emit::emLine( '<meta name="viewport" content="width=device-width">');
    emit::emLine( "<title>{$title}</title>");
    self::emOutArray(self::$emHeader_line);
    if (count(emitHeader::$emHeader_style) >= 1) {    
        emit::emLine( '<style>');
        self::emOutArray(self::$emHeader_style);
        emit::emLine( '</style>');
    }        
    if (count(emitHeader::$emHeader_js) >= 1) {    
        emit::emLine( '<script>');
        self::emOutArray(self::$emHeader_js);
        emit::emLine( '</script>');
    }        
    emit::emLine( PHP_EOL.'</head>');
}

} // end class

class emitMenu {
static $mu_caption = array();
static $mu_url = array();
static $mu_type = array();
static $mu_id = array();
static $mu_stat = array();  //1=favorite 2=always (do not show on "show items", already visible)
static $mu_current_item = NULL;

static function muEmitRoot() {
    emit::emLine('<div class="sy-menu-root">');
    $count = count(self::$mu_caption);
    emit::emLine('<fieldset class="sy-menu-fieldset">');
    emit::emLine('<legend class="sy-menu-legend">'.''.'</legend>');
    for ($i=0; $i<$count; ++$i) {
        if ( (self::$mu_type[$i]==2) and (self::$mu_stat[$i] >= 1) ) {
            $caption = self::$mu_caption[$i];
            $class = ($i === self::$mu_current_item) ? ' sy-menu-curItem' : '';
            print PHP_EOL. '<div class="sy-menu-item'.$class.'"><a class="sy-menu-link'.$class.'" href="' . self::$mu_url[$i] . '">'.$caption.'</a></div>';
       }
    }
    emit::emLine('<button id="sy-menu-more" type="button" onclick="divToggle(\'sy-menu-zone\',\'sy-menu-more\',\'Show<br>More\',\'Show<br>Less\')">');
    emit::emLine('Show<br>More');
    emit::emLine('</button>');
    
    emit::emLine('</div>');
}

static function muEmitMenu() {
    print PHP_EOL.'<div id="sy-menu-zone">';
    
    emit::emLine('</fieldset>');
    
    $count = count(self::$mu_caption);
    for ($i=0; $i<$count; ++$i) {
        if (self::$mu_stat[$i] ==2) {
            continue;
        }
        switch (self::$mu_type[$i]) {
            case 1:
               emit::emLine('<fieldset class="sy-menu-fieldset">');
               emit::emLine('<legend class="sy-menu-legend">'.self::$mu_caption[$i].'</legend>');
               break;
           case 2:
                $caption = self::$mu_caption[$i];
                $class = ($i === self::$mu_current_item) ? ' sy-menu-curItem' : '';
                print PHP_EOL. '<div class="sy-menu-item'.$class.'"><a class="sy-menu-link'.$class.'" href="' . self::$mu_url[$i] . '">'.self::$mu_caption[$i].'</a></div>';
                break;
           case 3:
               emit::emLine('</fieldset>');
               break;
               
        }
    }
   print PHP_EOL.'</div>';
}

static function menuAddGroupStart($triggerId,$caption) {
    self::$mu_caption[] = $caption;
    self::$mu_url[] = '';
    self::$mu_type[] = 1;
    self::$mu_id[] = $triggerId;
    self::$mu_stat[] = 0;
}

static function menuAddGroupEnd($triggerId) {
    self::$mu_caption[] = '';
    self::$mu_url[] = '';
    self::$mu_type[] = 3;
    self::$mu_id[] = $triggerId;
    self::$mu_stat[] = 0;
}

static function menuAddItem($triggerId, $caption, $link) {
    self::$mu_caption[] = $caption;
    self::$mu_url[] = $link;
    self::$mu_type[] = 2;
    self::$mu_id[] = $triggerId;
    self::$mu_stat[] = 0;
}

static function menuAddFavorite($id, $status=1) {
    if (substr($id,-1)=='*') {
        $id = substr($id,0,-1);
        $len = strlen($id);
        $count = count(self::$mu_id);
        for ($i=0; $i<$count; ++$i) {
            if (substr(self::$mu_id[$i],0,$len)==$id) {
                self::$mu_stat[$i] = $status;
            }
        }
       
    }
    else {
        $index = array_search($id,self::$mu_id);
        if ($index !== FALSE) {
           self::$mu_stat[$index] = $status;
        }
    }    
}

static function muSetCurrentItem($id) {
    $index = array_search($id,self::$mu_id);
    self::$mu_current_item = ($index !== FALSE) ? $index : NULL;
}
    
static function muMenuStart() {
}

static function muMenuEnd() {
}

} // end class

class emitEditGrid {
static $colCount = 99;    
    
static function syEG_start($caption=NULL, $pColCount=99) {
    self::$colCount = $pColCount;
    emit::emLine('');
    // emit::emLine('<div class="syEditGrid-box">');
   emit::emTable_start('sy-editGrid-table');
    if (!empty($caption)) {        emit::emLine('<tr><td class="sy-editGrid-header" colspan="'.self::$colCount.'">'.$caption.'</td></tr>');
    }
}

static function syEG_titleRow($text) {
    emit::emRow_start();
    emit::emLine('<tr><td class="sy-editGrid-header" colspan="'.self::$colCount.'">'.$text.'</td></tr>');
}


static function syEG_desc($desc) {
    emit::emRow_start();
    emit::emLine(' <td  class="sy-editGrid-desc">'.$desc.'</td>');
}

static function syEG_input($input) {
    emit::emLine(' <td  class="sy-editGrid-field">'.$input.'</td>');
    emit::emRow_end();
}

static function syEG_row($desc, $field) {
    self::syEG_desc($desc);
    self::syEG_input($field);
}

static function syEG_end($controls=NULL) {
    if (!empty($controls)) {
        emit::emLine('<tr><td  class="sy-editGrid-footer" colspan="'.self::$colCount.'">'.$controls.'</td></tr>');
    }
    emit::emTable_end();
    emit::emLine('');
}

}

?>