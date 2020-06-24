<?php

//--- draff-form.inc.php ---

const DRAFF_FIELD_DISABLED       = 1;
const DRAFF_FIELD_RO             = 2;
const  DRAFF_FIELD_REQUIRED      = 3;
const DRAFF_FIELD_AUTOCOMPLETE   = 4;
const DRAFF_FIELD_AUTOFOCUS      = 5;
const DRAFF_FIELD_FORMNOVALIDATE = 6;

const DRAFF_VALID_OPTIONS = array ('height'=>'n','max'=>'n', 'maxlength'=>'n','min'=>'n'
    ,'pattern'=>'s' ,'size'=>'n','width'=>'n','rows'=>'n','cols'=>'n'
    ,'value'=>'s','checked'=>'b'   //?????? for checkbox - value is checked value, not current value
    ,'class'=>'s', 'style'=>'s', 'name'=>'s', 'type'=>'s', 'id'=>'s'
    ,'disabled'=>'b','readonly'=>'b','required'=>'b','autocomplete'=>'b'
    ,'autofocus'=>'b','formnovalidate'=>'b','spellcheck'=>'b');  // b=blank s=string n=number

abstract class Draff_Form {
    private $drForm_orgRef;   //??????????????????????????????????????????????????
    private $drForm_fields = array();
    public  $drForm_seperator = '&nbsp;&nbsp;&nbsp;';  // default seporator (for between fields), can be changed
    private $drForm_errors = array();
    private $drForm_session;
    public  $drForm_key;
    public  $drForm_className;
    
    function __construct($formStepToken='', $stepName='') {
        //$this->drForm_session   = draff_get_session();
        $this->drForm_key       = $formStepToken;
        $this->drForm_className = $stepName;
    }
    
    function drForm_addField ( $field ) {
        $this->drForm_fields[$field->drField_id ] = $field;
    }
    
    function drForm_disable($fieldId=NULL) {
        //????? maybe should make more general - need ro option
        // used to payroll to view form in disabled mode
        if ( $fieldId===NULL) {
            foreach ($this->drForm_fields as $fieldId=>$field) {
                if ( is_a(Draff_Button, $field ) ) {
                    $field->drField_attrib_list['disabled'] = TRUE;
                }
            }
        }
        else if ( is_array($fieldId)) {
            foreach ($fieldId as $fid) {
                $this->drForm_fields[$fid]->drField_attrib_list['disabled'] = TRUE;
            }
        }
        else {
            $this->drForm_fields[$fieldId]->drField_attrib_list['disabled'] = TRUE;
        }
    }
    
    function drForm_field_setError ($fieldId, $message) {
        $this->drForm_errors[$fieldId] = $message;  // save - fields not defined yet
    }
    
    private function drForm_field_orgValue ( $fieldId, $fValue) {
        $orgValue = NULL;
        if ( $this->drForm_orgRef!=NULL) {
            if ( isset($this->drForm_orgRef[$fieldId])) {
                $orgValue = $this->drForm_orgRef[$fieldId];
            }
            else {
                $this->drForm_orgRef[$fieldId] = $fValue;
                $orgValue = $fValue;
            }
        }
        return $orgValue;
    }
    
    function drForm_form_addErrors ($chain) {  //????? rename to something more meaningful, and eliminate parameter ????
        // only used once by chain - probably better way of doing it
        // done after all of the fields have been defined
        if ( $chain->chn_formErrors->ses_arrayIsEmpty() ) {
            return;  //??????????? should debug and not have this if clause here
        }
        $errorArray = $chain->chn_formErrors->ses_arrayGet();
        foreach ($errorArray as $fieldId => $message) {
            if ( !isset($this->drForm_fields[$fieldId]) ) {
                continue;
            }
            $field = $this->drForm_fields[$fieldId];
            $field->drField_error = $message;
        }
        $chain->chn_formErrors->ses_arrayClear();
    }
    
    function drForm_define_linkButton ($fieldId, $caption) {
        $fieldId = Draff_Field::drField_normalizeFieldIdParameter($fieldId);
        // not used a lot
        // maybe move to emit
        $standardAttributes = array_slice(func_get_args(),2);
        $specialAttributes = array('class' => 'draff-button-asLink');
        //????????????????????????????????????? does not work
        $field = $this->drForm_createAndGet_field ($fieldId, DRAFF_TYPE_BUTTON, NULL, $standardAttributes, $specialAttributes);
    }
    
    function drForm_gen_button ($fieldId, $caption, $class) {
        $fieldId = Draff_Field::drField_normalizeFieldIdParameter($fieldId);
        // used in payroll 3 time to generate button
        // maybe move to emit
        // to print submit buttons which are not defined - class is required
        return '<button class="'.$class.'" type="submit" name="submit" value="'.$fieldId.'">'.$caption.'</button>';
    }
    function drForm_gen_field ($fieldId) { // field Id can be an array of field Ids and html code (code cannot be field id)
        // frequently used - mostly in cell_block
        if ( is_array($fieldId)) {
            $s = '';
            $fCount = count($fieldId);
            for ($i=0; $i<$fCount; ++$i) {
                $fid = $fieldId[$i];
                $fid = Draff_Field::drField_normalizeFieldIdParameter($fid);
                $s .= $this->drForm_field_genItem ($fid);
            }
        }
        else {
            $fieldId = Draff_Field::drField_normalizeFieldIdParameter($fieldId);
            $s = $this->drForm_field_genItem ($fieldId);
        }
        return $s;
    }
    
    private function drForm_field_genItem ($fieldId) { // field Id can be an array of field Ids and html code (code cannot be field id)
        // used twice by gen_field function
        if ( isset($this->drForm_fields[$fieldId])) {
            $field = $this->drForm_fields[$fieldId];
            return $field->drField_generateOutput();
        }
        else if ( $fieldId=='#sep') {
            return $this->drForm_seperator;
        }
        else if ( $fieldId=='#sep') {
            return $fieldId ;
        }
        else  {
            return $fieldId ;
        }
    }
    
    abstract protected function drForm_processSubmit ( $scriptData, $appGlobals, $chain );
    abstract protected function drForm_initData( $appData, $appGlobals, $appChain );
    abstract protected function drForm_initFields( $scriptData, $appGlobals, $chain );
    abstract protected function drForm_initHtml( $scriptData, $appGlobals, $chain, $emitter );
    abstract protected function drForm_outputPage ( $scriptData, $appGlobals, $chain, $emitter );
    abstract protected function drForm_outputHeader ( $scriptData, $appGlobals, $chain, $emitter );
    abstract protected function drForm_outputContent ( $scriptData, $appGlobals, $chain, $emitter );
    abstract protected function drForm_outputFooter ( $scriptData, $appGlobals, $chain, $emitter );
    
} // end class

Class Draff_Field {
public $drField_id;
public $drField_value = NULL;  // does not apply to button
public $drField_error = NULL;
// below used when generating tag
public $drField_tagOptions = array();
public $drField_tagIncludeValue = TRUE;
public $drField_tagIsSubmit = TRUE;
    
    function __construct($fieldId, $value , ...$optionalArguments) {
        $this->drField_id = self::drField_normalizeFieldIdParameter($fieldId);
        $this->drField_value = $value;
        $this->drField_tagOptions['name'] = $fieldId;
        foreach ($optionalArguments as $param ) {
            if (is_array($param) ) {
                foreach ($param as $key => $value) {
                    if ( $key=='class') {
                        $this-> drField_setOptionAppended('class', $value);
                    }
                    else {
                        if ( isset(DRAFF_VALID_OPTIONS[$key] ) ) {
                            if (DRAFF_VALID_OPTIONS[$key]=='b') {
                                $value = '';  // maybe add mechanism to turn this option off
                            }
                            $this->drField_tagOptions[$key] = $value;
                        }
                    }
                }
            }
            else if ( is_string($param) ) {
                $this-> drField_setOptionAppended('class', $param);
            }
            else if ( is_numeric($param)) {
                $isEnabled = ($param >= 0);
                switch ( abs($param) ) {
                    case  DRAFF_FIELD_RO:
                        $this->drField_tagOptions['readonly'] = $isEnabled; break;
                    case  DRAFF_FIELD_REQUIRED:
                        $this->drField_tagOptions['required'] = $isEnabled; break;
                    case DRAFF_FIELD_DISABLED:
                        $this->drField_tagOptions['disabled'] = $isEnabled; break;
                    case DRAFF_FIELD_AUTOCOMPLETE:
                        $this->drField_tagOptions['autocomplete'] = $isEnabled; break;
                    case DRAFF_FIELD_AUTOFOCUS:
                        $this->drField_tagOptions['autofocus'] = $isEnabled; break;
                    case DRAFF_FIELD_FORMNOVALIDATE:
                        $this->drField_tagOptions['formnovalidate'] = $isEnabled; break;
                }
        
            }
        }
    }
    
    static function drField_normalizeFieldIdParameter($fieldId) {
        return is_array($fieldId) ? implode ('_',$fieldId ) : $fieldId;
    }
    
    function drField_setOptionDefault( $optionName, $value ) {
        if ( !isset($this->drField_tagOptions[$optionName]) ) {
            $this->drField_tagOptions[$optionName] = $value;
        }
    }
    function drField_setOptionAppended($optionName, $value, $seperator = ' ') {
        if ( isset($this->drField_tagOptions[$optionName]) ) {
            $this->drField_tagOptions[$optionName]  =  $this->drField_tagOptions['value'] .  $seperator .  $value;
        }
        else {
            $this->drField_tagOptions[$optionName]  = $value;        }
    }
    
//    protected  function drField_get_option($attribKey, $default=NULL) {
//        return isset($this->drField_tagOptions[$attribKey]) ? $this->drField_tagOptions[$attribKey] : $default;
//    }
//
//-- function drField_orgValue_changed () {
//--     if ( $this->drField_orgValue==NULL) {
//--         return FALSE;
//--     }
//--     else if ( $this->drField_type != DRAFF_TYPE_TEXTAREA) {
//--         return ($this->drField_value != $this->drField_orgValue);
//--     }
//--     else {
//--         // standardize cr/lf before comparing
//--         return str_replace ( "\r\n", "\n", $this->drField_value) == str_replace ( "\r\n", "\n", $this->drField_orgValue);
//--     }
//-- }

    function drField_generateOutput() {
            // used once by form
        // maybe need override options for read-only and disable for entire form
        //    if so need way to leave some buttons enabled
        //$this->drForm_orgValue_setOnce($fieldId, $varValue);
        //if ( $this->drField_orgValue_changed($fieldId, $varValue)) {
        //    $paramList[] = array('class'=>'changed');
        //}
        //if ( $this->field_type == DRAFF_TYPE_READONLY) {
        //    $this->attrib_set('readonly','');   // readonly sent to server (disable does not send to server) if XHTML readonly should also be 2nd argument
        //}
        //???? how to handle label/caption mode ???????
        $isError = FALSE;
        if ( $this->drField_error!==NULL) {
            $this->drField_define_itemMerge('class', 'draff-fieldErr', ' ');
            $this->drField_attrib_class = draff_concatWithSpaces($this->drField_tagOptions, 'draff-fieldErr');
            $isError = TRUE;
        }
         $html = $this->drField_output();
         if ($isError) {
            $html = '<div style="display:inline-block;border:1px solid black;background-color:#eee;margin:4px; padding:2px;font-size:9px;text-align:center;color:red;font-weight:bold;">' . $html  . '<br>' . $this->drField_error . '</div>';
         }
//        $label = $this->drField_get_option('#label',NULL);
//        if ( $label !==NULL) {
//            $html = PHP_EOL.'<div class="draff-field-block">'. $html . '<div class="draff-field-subLabel">'.$label.'</div></div>';
//        }
            //if ( $isError) {
        // //    $html .=  PHP_EOL . '<span class="error">' ;  //?????? float, <br> ????????
        //   $html = '<div class="draff-fieldErr">'.$html.'<br>'. $this->drField_error . '</div>';
        //}
        return $html;
    }

    protected function drField_generateTag($tagType) {
        if ($tagType=='submit') {
            $this->drField_tagOptions['name'] =  'submit';
            $this->drField_tagOptions['value'] = $this->drField_id;
        }
        else {
            if ($this->drField_tagIncludeValue) {
                $this->drField_tagOptions['value'] = $this->drField_value;
            }
            else {
                unset($this->drField_tagOptions['value']);
            }
        }
    
        $html = $tagType . ' ';
//        if ( $incValue and $this->drField_tagIncludeValue and (!empty($this->drField_value)) ) {
//            $html .= ' value="'.$this->drField_value.'"';
//        }
//        if ( !is_array($this->drField_tagOptions)) {
//            return $html;
//        }
        foreach($this->drField_tagOptions as $optionKey => $optionValue) {
            if ( !isset(DRAFF_VALID_OPTIONS[$optionKey] ) ) {
                continue;
            }
            if ( ($optionValue===TRUE) or ($optionValue==='') ){
                $html .= ' ' . $optionKey;
            }
            else if ( ($optionValue!==FALSE) and ($optionValue!==NULL) ){
                $html .= ' ' . $optionKey . '="' . $optionValue . '"';
            }
        }
        return '<'.$html . '>';
    }
    
} // end class

class Draff_Button extends Draff_Field {
public $drField_caption;

    function __construct($fieldId, $caption, ...$optionalArguments) {
        parent::__construct($fieldId, $fieldId, ...$optionalArguments);  // value is field id
        $this->drField_caption = $caption;
        $this->drField_tagOptions['name'] = 'submit';
    }
    
    function drField_output() {
        return $this->drField_generateTag('button') . $this->drField_caption . '</button>';
    }
    
} // end class

class Draff_Checkbox extends Draff_Field {
    public $drField_caption;
    public $drField_uncheckedValue;
    
    function __construct($fieldId, $value, $caption, $uncheckedValue, $checkedValue, ...$optionalArguments) {
        parent::__construct($fieldId, $checkedValue , ...$optionalArguments );
        $this->drField_caption = $caption;
        $this->drField_uncheckedValue = $uncheckedValue;
        $this->drField_tagOptions['type'] = 'checkbox';
        $this->drField_tagOptions['value'] = $checkedValue;
        $this->drField_tagOptions['checked'] = ($value == $checkedValue);
        $this->drField_tagOptions['value'] = $checkedValue;
    }
    
    function drField_output() {
        $hiddenField = '<input type="hidden" name="rsmCB_'.$this->drField_id.'" value="'.$this->drField_uncheckedValue.'">';
        // a caption gives a larger target to click on - enhancement: make sure big clickable area if no caption
        return '<label>' . $this->drField_generateTag('input' ) . $this->drField_caption . '</label>'.$hiddenField;
    }
    
} // end class

class Draff_Combo extends Draff_Field {
    public $drField_selectMap;

function __construct($fieldId, $value, $selectMap, ...$optionalArguments) {
    parent::__construct($fieldId, $value, ...$optionalArguments );
    $this->drField_selectMap = $selectMap;
    $this->drField_setOptionDefault('size', 1);
}

function drField_output() {
    // validate $list is set (ok if empty)
    if ( count($this->drField_selectMap)==0) {
        $this->drField_tagOptions['disabled'] = TRUE;
        $options = PHP_EOL . '  <option value="@none" selected>No Choices</option>';
    }
    else {
        $options = '';
        $valIsNumber = is_numeric($this->drField_value);
        foreach ($this->drField_selectMap as $opValue=>$opDesc) {
            if ( substr($opValue,0,9)=='#groupEnd') {
                $options .= PHP_EOL . '  </optgroup>';
            }
            else if ( substr($opValue,0,6)=='#group') {
                $options .= PHP_EOL . '  <optgroup label="'.$opDesc.'">';
            }
            else {
                $opIsNumber = is_numeric($opValue);
                $same = FALSE;
                if ( $valIsNumber == $opIsNumber) {
                    $same = ($valIsNumber) ? ($this->drField_value == $opValue) : ($this->drField_value === $opValue);
                }
                $sel = ($same) ? ' selected' : '';
                if (substr($opDesc,0,3) == '#d#') {
                    $sel = ' disabled';
                    $opDesc = substr($opDesc,3);
                }
                $options .= PHP_EOL . '  <option value="'.$opValue.'"'.$sel.'>'.$opDesc.'</option>';
            }
        }
    }
    if ( isset($this->drField_attrib_list['disabled'])) {
        $this->drField_attrib_list['style'] = 'background-color:#eeeeee;';  //???? should expand if already a style
    }
    $selectTag = $this->drField_generateTag('select' );
    return $selectTag . $options . PHP_EOL . '</select>';
}

} // end class

class Draff_Date extends Draff_Field {
    
    function __construct($fieldId,  $value, ...$optionalArguments) {
        parent::__construct($fieldId, $value, ...$optionalArguments );
        $this->drField_tagOptions['type'] = 'date';
    }
    
    function drField_output() {
        return $this->drField_generateTag('input');
    }
    
} // end class
class Draff_DateTime extends Draff_Field {
    
    function __construct($fieldId, $value, ...$optionalArguments) {
        parent::__construct($fieldId, $value );
        $this->drField_tagOptions['type'] = 'datetime';
    }
    
    function drField_output() {
        return $this->drField_generateTag('input');
    }
    
} // end class

class Draff_Hidden extends Draff_Field {
    
    function __construct($fieldId, $value, ...$optionalArguments) {
        parent::__construct($fieldId, $value , ...$optionalArguments);
        $this->drField_tagOptions['type'] = 'number';
    }
    
    function drField_output() {
        return $this->drField_generateTag('input');
    }
    
} // end class


class Draff_Number extends Draff_Field {
    
    function __construct($fieldId, $value, ...$optionalArguments) {
        parent::__construct($fieldId, $value, ...$optionalArguments );
        $this->drField_tagOptions['type'] = 'number';
    }
    
    function drField_output() {
        return $this->drField_generateTag('input');
    }
    
} // end class

class Draff_RadioGroup extends Draff_Field {
    
    function __construct($fieldId, $value, $uncheckedValue, ...$optionalArguments) {
        parent::__construct($fieldId, $uncheckedValue, ...$optionalArguments );
        $this->drField_tagOptions['#unchecked'] = $uncheckedValue;  //????????????
    }
    
    function drField_output() {
        // group is not displayed (maybe can enhance to put in optional group <div>
    }
    
} // end class

class Draff_RadioItem extends Draff_Field {
    public $frField_caption;

    function __construct($fieldId, $value,$groupId,$caption,$checkedValue, ...$optionalArguments) {
        // fieldId is id for the individual radio button (not used in generated html)
        // groupId is id for the variable that holds the result from the active radio button
        parent::__construct($fieldId, $checkedValue, ...$optionalArguments );
        $this->frField_caption = $caption;
        $this->drField_tagOptions['type'] = 'radio';
        $this->drField_tagOptions['value'] = $checkedValue;
        $this->drField_tagOptions['checked'] = ($value == $checkedValue);
    }

    function drField_output() {
        return '<label>' . $this->drField_generateTag('input' ) . $this->drField_caption . '</label>';
    }
} // end class

class Draff_Password extends Draff_Field {
    
    function __construct($fieldId, $value, ...$optionalArguments) {
        parent::__construct($fieldId,$value  , ...$optionalArguments );
        $this->drField_tagOptions['type'] = 'password';
    }
    
    function drField_output() {
        return $this->drField_generateTag('input');
    }
    
} // end class

class Draff_Phone extends Draff_Field {
    
    function __construct($fieldId, $value, ...$optionalArguments) {
        parent::__construct($fieldId,$value, ...$optionalArguments );
        $this->drField_tagOptions['type'] = 'tel';
    }
    
    function drField_output() {
        return $this->drField_generateTag('input');
    }
    
} // end class
class Draff_Text extends Draff_Field {
    
    function __construct($fieldId, $value, ...$optionalArguments) {
        parent::__construct($fieldId, $value, ...$optionalArguments );
        $this->drField_tagOptions['type'] = 'text';
    }
    
    function drField_output() {
        return $this->drField_generateTag('input');
    }
    
} // end class
class Draff_TextArea extends Draff_Field {
    public $drField_textAreaValue;
    
    function __construct($fieldId, $value, ...$optionalArguments) {
        parent::__construct($fieldId, $value, ...$optionalArguments);
        $this->drField_tagOptions['value'] = '';
        $this->drField_textAreaValue = $value;
        $this->drField_setOptionDefault('rows', 4);
        $this->drField_setOptionDefault('cols', 50);
    }
    
    function drField_output() {
        return $this->drField_generateTag('textarea') . $this->drField_textAreaValue .'</textarea>';
    }
} // end class

class Draff_Time extends Draff_Field {

    function __construct($fieldId,  $value, ...$optionalArguments) {
        parent::__construct($fieldId, $value, ...$optionalArguments);
        $this->drField_tagOptions['type'] = 'time';
    }

    function drField_output() {
        return $this->drField_generateTag('input');
    }

} // end class

?>