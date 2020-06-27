<?php

//--- draff-emitter.inc.php ---

// This sys unit is an independent stand-alone unit not requiring any other include code
// ... and not referencing anything that is KCM or Raccoon specific

//const DRAFF_TYPE_EMIT_HTML     = 'h';
//const DRAFF_TYPE_EMIT_PDF      = 'p';
//const DRAFF_TYPE_EMIT_EXCEL    = 'e';
//const DRAFF_TYPE_EMIT_PREVIEW  = 'v';
//

const EMIT_SEP_SMALL = '@@S';
const EMIT_SEP_MEDIUM = '@@M';  // between close buttons, etc
const EMIT_SEP_LARGE = '@@L';  // between buttons, etc
const EMIT_SEP = EMIT_SEP_LARGE;
const EMIT_EOL = '@@E';

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
    
    protected $emit_htmlStyle = '';  // this determines many descendent styles
    
    private $emitOut_form;  // from original emit_html
    public $emitOut_inReport = NULL;  // from original emit_html
    public $emitOut_reportMode = NULL;  // from original emit_html
    
    function __construct($form, $bodyStyle = '', $exportType = 'h') {
        $this->emit_form = $form;  // form can be NULL
        $this->emitOut_form = $form;
        if (!empty($bodyStyle)) {
            $this->emit_htmlStyle = $bodyStyle;
        }
        $this->emit_options = new Draff_Emitter_Options;
        $this->emit_menu = new Draff_Menu_Engine;
        $this->emit_altRow_classes = NULL;
        $this->emit_altRow_last = 0;
        $this->emit_altRow_index = 0;
        $this->report_start();
    }

//cell -----------------------
    protected function cell_start($class = '', $more = '') {
        ++$this->emit_column_index;  // not accurate for colspans
        $more = empty($more) ? '' : ' ' . $more;
    }
    
    final function cell_block($content, $class = '', $more = '') {
        $more = empty($more) ? '' : ' ' . $more;
        $this->cell_start($class, $more);
        $this->content_block($content);
        $this->cell_end();
    }
    
    abstract function cell_end();

//div  -----------------------
    function div_block($content, $cssClasses) {
        $this->div_start($cssClasses);
        $this->content_block($content);
        $this->div_end();
    }
    abstract function div_start($cssClasses = '');
    abstract function div_end();
    abstract function div_toggled_start($cssId, $class = '');
    abstract function div_toggled_end();
    abstract function div_toggled_buttonEmit($divId, $buttonId, $moreCaption, $lessCaption);
    abstract function div_toggled_buttonGen($divId, $buttonId, $moreCaption, $lessCaption);

//report  -----------------------
    function exp_report_start($reportMode) {
        $this->emitOut_inReport = TRUE;
        $this->emitOut_reportMode = $reportMode;
    }
    
    function exp_report_end() {
        $this->emitOut_inReport = FALSE;
    }
    
    function report_start() {
        $this->emit_isIn_report = TRUE;
    }
    
    function report_end() {
        $this->emit_isIn_report = FALSE;
    }

//row  -----------------------
    protected function row_start ($class = '') {
        //$this->emit_nrLine('<tr' . emit_getString_attribute( 'class=', $class,$this->emit_cssClass_table) . '>');
        $this->emit_column_index = -1;
        if ($this->emit_altRow_last > 0) {
            $this->emit_altRow_index = $this->emit_altRow_index < $this->emit_altRow_last ? ($this->emit_altRow_index + 1) : 0;
            $class = $class . ' ' . $this->emit_altRow_classes[$this->emit_altRow_index];
        }
    }
    
    final function row_oneCell($content, $class = '') {
        $this->row_start($class);
        $this->cell_block($content, $class, 'colspan="' . $this->emit_column_count . '"');
        $this->row_end($class);
    }

    abstract function row_end();
    
//table  -----------------------
    protected function table_start ($class = '', $columnInfo = 0) {
        if (is_numeric($columnInfo)) {
            $this->emit_column_count = $columnInfo;
        } else if (is_a($columnInfo, 'rsmp_emitter_table_layout')) {
            $columnInfo->table_start($this, $class);
        }
    }
    
    abstract function table_end();
    abstract function table_head_start($class = '');
    abstract function table_head_end();
    abstract function table_body_start($class = '');
    abstract function table_body_end();
    abstract function table_foot_start($class = '');
    abstract function table_foot_end();

//span  -----------------------
    function span_block($content, $cssClasses) {
        $this->span_start($cssClasses);
        $this->content_block($content);
        $this->span_end();
    }
    
    abstract function span_start($class = '');
    abstract function span_end();

//content  -----------------------
    abstract function content_field($fieldId);
    abstract function content_link($link, $caption, $class);
    abstract function content_text($content);
    abstract function content_display($htmlText);
    abstract function content_block();
    abstract function content_button_submit($fieldId, $caption, $class);

//zone  -----------------------
    abstract function zone_body_start($chain, $form, $pClass = '');
    abstract function zone_body_end();
    abstract function zone_start($class);
    abstract function zone_end($class = '');
    abstract function zone_htmlHead();
    abstract function zone_htmlHeadArray(&$lines);
    abstract function zone_messages($chain, $form);

//misc
    abstract function toString_phone($phone);
    function getString_phone($phoneText, $class = '') {
        //###-###-####
        //012345678901234
        //#-###-###-####
        $phoneText = rc_cleanPhoneNumber($phoneText);
        $start = substr($phoneText, 1) == '-' ? 5 : 3;
        if ((substr($phoneText, $start, 1) == '-') and (substr($phoneText, $start + 4, 1) == '-')) {
            $phone = substr($phoneText, 0, $start + 9);
            $rem = substr($phoneText, $start + 9);
            $phoneText = '<a href="tel:' . $phone . '">' . $phone . ' </a>' . '<br><span class="fc-40">' . $rem . '</span>';
        } else if (!empty($phoneText)) {
            $phoneText = '<span class="font-autoSize-70">' . $phoneText . '</span>';
        }
        return $phoneText;
    }
    
    function getString_link($url, $text, $class = '') {
        $class = empty($class) ? '' : ' class="' . $class . '"';
        return '<a href="' . $url . '"' . $class . '>' . $text . '</a>';
    }
    
    function getString_button($caption, $class = '', $value) {
        $class = empty($class) ? '' : ' class="' . $class . '"';
        return '<button type="submit" ' . $class . ' name="submit" value="' . $value . '">' . $caption . '</button>';
    }
    
    //abstract function getString_phone($phoneText, $class = '');
    //abstract function getString_link($url, $text, $class = '');
    //abstract function getString_button($caption, $class = '', $value);
    static function getString_sizedString($text, $maxCharWidth) { // no <br> allowed
        $width = strlen($text);
        if ($width <= $maxCharWidth) {
            return $text;
        }
        $per = ($maxCharWidth * 100) / $width;
        if ($per <= 40) {
            $cl = 'font-autoSize-40';
        } else if ($per <= 50) {
            $cl = 'font-autoSize-50';
        } else if ($per <= 60) {
            $cl = 'font-autoSize-60';
        } else if ($per <= 70) {
            $cl = 'font-autoSize-70';
        } else if ($per <= 80) {
            $cl = 'font-autoSize-80';
        } else if ($per <= 90) {
            $cl = 'font-autoSize-90';
        } else {
            return $text;
        }
        return '<span class="' . $cl . '">' . $text . '</span>';
    }
    
    static function getString_sizedMemo($text, $maxCharWidth) {
        // only one <br> allowed - should be better written to handle multiple <br> and maybe eof/eol
        $br = strpos($text, '<br>');
        if ($br === FALSE) {
            return Draff_Emitter_Html::getString_sizedString($text, $maxCharWidth);
        } else {
            return Draff_Emitter_Html::getString_sizedString(substr($text, 0, $br), $maxCharWidth)
                . '<br>' . Draff_Emitter_Html::getString_sizedString(substr($text, $br + 5), $maxCharWidth);
        }
    }
    function drOutputIfNotReport($s) {
        if ($this->emitOut_inReport === FALSE) {
            return;
        }
        print PHP_EOL . $s;
    }
    function expf_toString_phone($phoneText) {  // ??????  defined else where (prefer not to duplicate)
        //###-###-####
        //012345678901234
        //#-###-###-####
        $phoneText = rc_cleanPhoneNumber($phoneText);
        $start = substr($phoneText, 1) == '-' ? 5 : 3;
        if ((substr($phoneText, $start, 1) == '-') and (substr($phoneText, $start + 4, 1) == '-')) {
            $phone = substr($phoneText, 0, $start + 9);
            $rem = substr($phoneText, $start + 9);
            $phoneText = '<a href="tel:' . $phone . '">' . $phone . ' </a>' . '<br><span class="fc-40">' . $rem . '</span>';
        } else if (!empty($phoneText)) {
            $phoneText = '<span class="font-autoSize-70">' . $phoneText . '</span>';
        }
        return $phoneText;
    }
    
    
    
} // end class

class Draff_Emitter_Html extends Draff_Emitter_Engine {
    
    function __construct($form, $bodyStyle = '', $exportType = 'h') {
        parent::__construct($form, $bodyStyle, $exportType);
    }

//Cell ===================================

    function cell_start($class = '', $more = '') {
        parent::cell_start($class,$more);
        $more = empty($more) ? '' : ' ' . $more;
        $this->emit_nrLine('<td' . emit_getString_attribute('class=', $class) . $more . '>');
    }
    
    function cell_end() {
        $this->drHtml_output('</td>');
    }
    // function cell_block($content, $class = '', $more = '') {

//--  function cell_fieldArray($fields, $class='') {
//--      $this->cell_block($this->emit_form->drForm_gen_field($fields), $class);
//--      // ???????????????????
//--  }

//Content ===================================

    function content_field($fieldId) {  // can be mulitple parameters or one array
        $this->drHtml_output($this->emit_form->drForm_gen_field($fieldId));  // field can be field id or text (if invalid field id)
    }
    
    function content_link($link, $caption, $class) {
        $s = '<a class="' . $class . '" href="' . $link . '">' . $caption . ' </a>';
        $this->drHtml_output( PHP_EOL . $s );
    }
    
    function content_text($content) {
        $this->drHtml_output($content);
    }
    
    function content_display($htmlText) {
        $this->emit_export->expf_htmlLine_display($htmlText);
    }
    
    
    function content_block() {  // can be multiple parameters or one array
        $params = func_get_args();
        if ((count($params) == 1) and is_array($params[0])) {
            $params = $params[0];
        }
        foreach ($params as $t) {
            if (is_array($t)) {
                $this->content_block($t);
            } else if (substr($t, 0, 1) == '@') {
                $fieldId = substr($t, 1);
                $this->content_field($fieldId);  // if invalid field-id will print as text
            } else if (substr($t, 0, 1) == '#') {
                $command = substr($t, 1, 3);
                $count = substr($t, 4);
                if (($command == 'sep') or ($command == 'eol')) {
                    $htmlChar = ($command == 'eol') ? '<br>' : '&nbsp;';
                    $html = str_repeat($htmlChar, empty($count) ? 1 : $count);
                    $this->drHtml_output($html);
                }
            } else {
                $this->content_text($t);
            }
        }
    }
    
    function content_button_submit($fieldId, $caption, $class) {
        $fieldId = Draff_Field::drField_normalizeFieldIdParameter($fieldId);
        return '<button class="' . $class . '" type="submit" name="submit" value="' . $fieldId . '">' . $caption . '</button>';
    }

//Div ===================================

    function div_start($cssClasses = '') {
        $this->drHtml_output('');
        $this->drHtml_output('<div' . emit_getString_attribute('class=', $cssClasses) . '>');
    }
    
    function div_end() {
        $this->drHtml_output('</div>');
        $this->drHtml_output('');
    }
    
    function div_toggled_start($cssId, $class = '') {
        $class = ' class="' . draff_concatWithSpaces('draff-toggled-div', $class) . '"';
        $this->drHtml_output('');
        $this->drHtml_output('<div id="' . $cssId . '"' . $class . '><!--Toggled-Start-->');
    }
    
    function div_toggled_end() {
        $this->drHtml_output('</div><!--Toggled-End-->');
    }
    
    function div_toggled_buttonEmit($divId, $buttonId, $moreCaption, $lessCaption) {
        $this->drHtml_output(PHP_EOL . $this->div_toggled_buttonGen($divId, $buttonId, $moreCaption, $lessCaption));
    }
    
    function div_toggled_buttonGen($divId, $buttonId, $moreCaption, $lessCaption) {
        // ???????? no export function
        $this->drHtml_output('<button id="' . $buttonId . '" type="button" onclick="divToggle(\'' . $divId . '\',\'draff-menu-item-more\',\'' . $moreCaption . '\',\'' . $lessCaption . '\')">' . $moreCaption . '</button>');
    }

//Row ===================================

    function row_start($class = '') {
        parent::row_start($class);
        $this->drHtml_output('<tr' . emit_getString_attribute('class=', $class) . '>');
    }
    
    function row_end() {
        $this->drHtml_output('</tr>');
    }

//Span ===================================

    function span_start($class = '') {
        $this->drHtml_output('');
        $this->drHtml_output('<span' . emit_getString_attribute('class=', $class) . '>');
    }
    
    function span_end() {
        $this->drHtml_output('</span>');
        $this->drHtml_output('');
    }

//Table ===================================
    
    function table_start($class = '', $columnInfo = 0) {
        parent::table_start($class = '', $columnInfo = 0);
        $this->drHtml_output('');
        $this->drHtml_output('<table' . emit_getString_attribute('class=', $class) . '>');
    }
    
    function table_end() {
        $this->drHtml_output('</table>');
        $this->drHtml_output('');
    }
    
    function table_head_start($class = '') {
        $this->drHtml_output('</thead>');
    }
    
    function table_head_end() {
        $this->drHtml_output('</tbody>');
    }
    
    function table_body_start($class = '') {
        $this->drHtml_output('<tbody' . emit_getString_attribute('class=', $class) . '>');
    }
    
    function table_body_end() {
        $this->drHtml_output('</tbody>');
    }
    
    function table_foot_start($class = '') {
        $this->drHtml_output('<tfoot' . emit_getString_attribute('class=', $class) . '>');
    }
    
    function table_foot_end() {
        $this->emit_export->exp_table_foot_end();
    }


//Zone ===================================
    function zone_body_start($chain, $form, $pClass = '') {
        // ???????? no export function
        global $deb_inDebug;
        if ($deb_inDebug) {
            $pClass = '';
        }
        $class = $this->emit_htmlStyle . (empty($pClass) ? '' : (' ' . $pClass));
        $class = ' class="' . $class . '"';
        $this->drHtml_output('');
        $this->drHtml_output('<body class="draff-zone-body-normal">');
        if ($form == NULL) {
            $class = 'draff-zone-noForm ' . (empty($genreClass) ? '' : (' ' . $genreClass));
            $this->drHtml_output('<div class="' . $class . '">');
            $this->zone_isForm = FALSE;
        } else {
            $url = $chain->chn_url_getString();
            // $form->drForm_form_start();
            $this->drHtml_output('');
            $class = ' class="draff-zone-container sy-genre-default"';  //????????????????????????
            $this->drHtml_output('<form class="draff-zone-form-normal" action="' . $url . '" method="post" id="form" name="form">');
            $this->zone_isForm = TRUE;
        }
        // $this->zone_menu_toggled();
    }
    
    function zone_body_end() {
        $this->drHtml_output($this->zone_isForm ? '</form>' : '</div>');
        // ???????? no export function
        $this->drHtml_output('</body>');
        $this->drHtml_output('</html>');
    }
    
    function zone_start($class) {
        $this->drHtml_output('');
        $this->drHtml_output('<div class="' . $class . '">');
    }
    
    function zone_end($class = '') {
        $this->drHtml_output('</div>');
        $this->drHtml_output('');
    }
    
    function zone_htmlHead() {
        global $deb_inDebug;
        $this->emit_options->addOption_toggledDivScript();  // will be printed latter with other saves lines   //~~~~~~~~~~~~~~~
        $this->drHtml_output('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">');
        $class = ($deb_inDebug) ? '' : ' class="draff-zone-html-normal"';
        $this->drHtml_output('<html' . $class . '>');
        $this->drHtml_output('<head>');
        $this->drHtml_output('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">');
        $this->drHtml_output('<meta name="viewport" content="width=device-width", initial-scale=1.0">');
        $this->drHtml_output("<title>{$this->emit_options->emtTitleShort}</title>");
        $this->zone_htmlHeadArray($this->emit_options->emtSetting_htmlHead_lines);    //~~~~~~~~~~~~~~~
        if (count($this->emit_options->emtSetting_cssRules) >= 1) {
            $this->drHtml_output('<style>');
            $this->zone_htmlHeadArray($this->emit_options->emtSetting_cssRules);    //~~~~~~~~~~~~~~~
            $this->drHtml_output('</style>');
        }
        if (count($this->emit_options->emtSetting_jsScripts) >= 1) {
            $this->drHtml_output('<script>');
            $this->zone_htmlHeadArray($this->emit_options->emtSetting_jsScripts);   //~~~~~~~~~~~~~~~
            $this->emit_export->expf_htmlLine_kernel('</script>');
        }
        $this->drHtml_output('</head>');
    }
    
    function zone_htmlHeadArray(&$lines) {
        for ($i = 0; $i < count($lines); ++$i) {
            $this->drHtml_output($lines[$i]);
        }
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
            if ($errType == DRAFF_TYPE_MSG_FIELD) {
                if ($isFirst) {
                    $htmlArray[] = 'Please correct the following:<ul>';
                    $isFirst = FALSE;
                }
                $s = '<li>' . $message . '</li>';
                $htmlArray[] = '<li>' . $s . '</li>';
                $form->drForm_field_setError($fieldId, $message);
                $class = '';
            } else {
                switch ($errType) {
                    case DRAFF_TYPE_MSG_STATUS:
                        $class = 'theme-message-status';
                        break;
                    case DRAFF_TYPE_MSG_ERROR:
                        $class = 'theme-message-error-error';
                        break;
                    default:
                        $class = 'theme-message-error-error';
                        break;
                }
                $htmlArray[] = '<div class="' . $class . '">' . $message . '</div>';
            }
        }
        $this->emit_export->expf_htmlLine_display('');
        $this->emit_export->expf_htmlLine_display('<div class="draff-theme-message-error-zone-container">');
        for ($i = 0; $i < count($htmlArray); ++$i) {
            $this->emit_export->expf_htmlLine_display($htmlArray[$i]);
        }
        $this->emit_export->expf_htmlLine_display('</div>');
        $this->emit_export->expf_htmlLine_display('');
    }

//Misc ===================================

     function emit_nrLine($s) {  // html non-report line (will not be exported, or appear for html export)
        // ???????? no export function
        print PHP_EOL . $s;
    }
    
    function emit_nrAsIs($s) {  // html non-report line (will not be exported, or appear for html export)
        // ???????? no export function
        print $s;
    }

    function toString_phone($phone) {
        // ???????? no export function
        return $this->getString_phone($phone);
    }
    
    private function drHtml_output($s) {  // was expf_emit_hLine
        if ($this->emitOut_inReport === FALSE) {
            return;
        }
        print PHP_EOL . $s;
    }
    
} // end class

abstract class Draff_Emitter_Export extends Draff_Emitter_Engine {
    public $curTable;
    public $curRow;
    public $curCell;
    
    function __construct($form, $bodyStyle = '', $exportType = 'h') {
    }
    
    function table_start($class = '', $columnInfo = 0) {
    }
    
    function table_end() {
    }
    
    function table_head_start($class = '') {
     }
    
    function table_head_end() {
    }
    
    function table_body_start($class = '') {
    }
    
    function table_body_end() {
    }
    
    function table_foot_start($class = '') {
    }
    
    function table_foot_end() {
    }
    
    function row_start($class = '') {
        parent::row_start($class);
        $this->curRow = $this->drNode_add($this->curTable,'row');
    }
    
    function row_end() {
        $this->curRow = NULL;
    }
    
     function cell_start($class = '', $more = '') {
        $this->curCell = $this->drNode_start($this->curRow,'cell');
    }
    
    function cell_end() {
        $this->curCell = NULL;
    }

    function div_start($cssClasses = '') {
    }
    
    function div_end() {
    }
    
    function div_toggled_start($cssId, $class = '') {
    }
    
    function div_toggled_end() {
    }
    
    function div_toggled_buttonEmit($divId, $buttonId, $moreCaption, $lessCaption) {
        $this->drHtml_output(PHP_EOL . $this->div_toggled_buttonGen($divId, $buttonId, $moreCaption, $lessCaption));
    }
    
    function div_toggled_buttonGen($divId, $buttonId, $moreCaption, $lessCaption) {
    }
    
    function span_start($class = '') {
    }
    
    function span_end() {
    }
    
    function content_field($fieldId) {  // can be mulitple parameters or one array
    }
    
    function content_link($link, $caption, $class) {
    }
    
    function content_text($content) {
    }
    
    function content_display($htmlText) {
     }
    
    
    function content_block() {  // can be multiple parameters or one array
    }
    
    function content_button_submit($fieldId, $caption, $class) {
    }
    
    function emit_nrLine($s) {  // html non-report line (will not be exported, or appear for html export)
    }
    
    function emit_nrAsIs($s) {  // html non-report line (will not be exported, or appear for html export)
    }
    
    function zone_body_start($chain, $form, $pClass = '') {
    }
    
    function zone_body_end() {
     }
    
    function zone_start($class) {
    }
    
    function zone_end($class = '') {
    }
    
    function zone_htmlHead() {
    }
    
    function zone_htmlHeadArray(&$lines) {
    }
    
    function zone_messages($chain, $form) {   //~~~~~~~~~~~~~~~
     }

    private function drHtml_output($s) {  // was expf_emit_hLine
    }
    
    private function drNode_start($s, $type=NULL) {  // was expf_emit_hLine
    }
    
    private function drNode_end($s, $type=NULL) {  // was expf_emit_hLine
    }
    private function drNode_add($parent, $type) {
        $newChild = new Emitter_Node;
        $parent->content[$type] = $newChild;
        return $newChild;
    }
    
}

class Emitter_Node {
    public $content = array();  // indexed, can be multiple rows, etc
    public $attributes = array();  // css attributes
}

abstract class Draff_Emitter_Pdf extends Draff_Emitter_Export {

}

abstract class Draff_Emitter_Excel extends Draff_Emitter_Export {

}


class rsmp_emitter_table_layout {
    public $emTable_id;
    public $emTable_col_count = 0;
    public $emTable_col_widths = array();
    public $emTable_col_total;
    
    function __construct($id, $widths) {
        $this->emTable_id = $id;
        $this->emTable_col_count = count($widths);
        $this->emTable_col_widths = $widths;
        $this->emTable_col_total = array_sum($widths);
        
    }
    
    private function emTab_toAmount($width) {
        return round((($width + 1) * 0.55), 3) . 'em';
    }
    
    function emTab_emitStyles($emitter) {
        for ($i = 0; $i < $this->emTable_col_count; ++$i) {
            $colWidth = emTab_toAmount($this->emTable_col_widths[$i]);
            $colKey = $this->emTable_id . ($i + 1);
            $emitter->emit_options->addOption_styleTag('col.' . $colKey, 'width:' . $colWidth . ';');
        }
    }
    
    function table_start($emitter, $class = '') {
        //  $tableWidths = array ('t1',1,2,3,4,5);
        //  $emitter->addWidthStyles($tableKey, $columnWidth, $columnEnabled);
        // $emitter->tableStart('',$tableWidths);
        $tableWidth = $this->emTab_toAmount($this->emTable_col_total);
        $tableStyle = 'style=" width:' . $this->emTab_toAmount($this->emTable_col_total + 1) . ';"';
        $emitter->emit_nrLine('<table' . emit_getString_attribute('class=', $class) . $tableStyle . '>');
        $emitter->emit_nrLine('<colgroup>');
        for ($i = 0; $i < $this->emTable_col_count; ++$i) {
            $colKey = $this->emTable_id . ($i + 1);
            $width = $this->emTab_toAmount($this->emTable_col_widths[$i]);
            $emitter->emit_nrLine('<col style="width:' . $width . ';">');
        }
        $emitter->emit_nrLine('</colgroup>');
        $emitter->emit_column_count = $this->emTable_col_count;
    }
    
} // end class

// the emit_ functions should never be used for reports


function emit_span($content, $class) {
    return '<span class="' . $class . '">' . $content . '</span>';
}

function emit_div($content, $class) {
    return '<div class="' . $class . '">' . $content . '</div>';
}

function edit_button_submit($buttonId, $caption, $class) {
    return '<button class="' . $class . '" type="submit" name="submit" value="' . $buttonId . '">' . $caption . '</button>';
}

function emit_getString_attribute($atrStart, $pClass1, $pClass2 = '') {
    if (empty($pClass1)) {
        $c = $pClass2;
    } else if (empty($pClass2)) {
        $c = $pClass1;
    } else {
        $c = $pClass1 . ' ' . $pClass2;
    }
    return (empty($c)) ? ('') : (' ' . $atrStart . '"' . $c . '"');
}

class Draff_Emitter_Options {
    
    public $emtSetting_htmlHead_lines = array();
    public $emtSetting_cssFilePath = array();
    public $emtSetting_cssFileMedia = array();
    public $emtSetting_cssFileLevel = array();
    public $emtSetting_cssRules = array();
    public $emtSetting_jsScripts = array();
    public $emtSetting_jsToggledScript = FALSE;
    public $emtTheme = '';
    public $emtTitleLong = '';
    public $emtTitleShort = '';
    
    function __construct() {
    }
    
    function addOption_styleLine($styleLine) {
        // ???????? no export function
        $this->emtSetting_cssRules[] = $styleLine;
    }
    
    function addOption_styleTag($styleTag, $style) {
        $this->emtSetting_cssRules[] = $styleTag . '{' . $style . '}';
    }
    
    function addOption_styleFile($cssPath, $media = "all", $levelStr = "") {
        $timestamp = filemtime(__DIR__ . "/" . $levelStr . $cssPath);
        $this->emtSetting_htmlHead_lines[] = "<link rel='stylesheet' type='text/css' media='{$media}' href='{$levelStr}{$cssPath}?v={$timestamp}'>";
    }
    
    function set_theme($theme) {
        $this->emtTheme = $theme;
    }
    function set_title($titleShort,$titleLong=NULL) {
        $this->emtTitleShort = $titleShort;
        $this->emtTitleLong = ($titleLong===NULL) ? $titleShort : $titleLong;
    }
    function addOption_htmlHeadLines($lines) {
        foreach ($lines as $s) {
            $this->emtSetting_htmlHead_lines[] = $s;
        }
    }
    
    function addOption_htmlHeadLine($line) {
        $this->emtSetting_htmlHead_lines[] = $line;
    }
    
    function addOption_toggledDivScript() {
        if ($this->emtSetting_jsToggledScript) {
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
        $this->emtSetting_htmlHead_lines = array_merge($this->emtSetting_htmlHead_lines, $js);
        //$this->htmlHead_addLine("alert('got here');");
        //$this->htmlHead_addLine("form=document.forms[0].id;");
    }
    
}

?>