<?php

//--- rsm-form-test.php ---

const TEST_FILE_NAME = 'rsm-test-form.data';

ob_start();  // output buffering (needed for redirects, rsm-zone-header changes)

//include_once( '../rc_defines.inc.php' );
//include_once( '../rc_admin.inc.php' );
//include_once( '../rc_database.inc.php' );
//include_once( '../rc_rsm-message-errors.inc.php' );

include_once( '../rsm-emitter.inc.php' );
include_once( '../rsm-chain.inc.php' );
include_once( '../rsm-form.inc.php' );
include_once( '../rsm-functions.inc.php' );


//@@@@@@@@@@@@@@@@@@@@
//@  Step 1
//@@@@@@@@@@@@@@@@@@@@

class chainStep_select extends rsm_step_object {  // specify winner

function rsmStep_initAlways($chain, $common, $kcmGlobals) {
    $common->com_initialize();  //????????????????????? not here - but where ???????
    $this->rsmStep_updateIfPosted('@contactId', $common->com_contactId);
}

function rsmStep_initThisStep($chain, $common, $kcmGlobals) {
}

function rsmStep_processValidate($chain, $common, $kcmGlobals) {
}

function rsmStep_processSubmit ($chain, $common, $kcmGlobals, $submit) {
    if ( $submit == '@cancel') {
        //$chain->rsmChain_curStream_Clear();
        $chain->rsmChain_message_setStatus('Cancelled');
        $chain->rsmChain_backToSameUrl();
    }
    if ( $submit == '@edit') {
        $chain->rsmChain_CopyPostedDataToSession();
        $chain->rsmChain_message_setStatus('Edit');
        $chain->rsmChain_backToSameUrl(2);
    }
}

function rsmStep_formDefine($chain, $common, $form, $kcmGlobals) {
    $common->com_init_step1();
    $cb = $common->com_contactDataBase;
    foreach ($cb->cb_items as $key=>$c) {
        $sel[$c->cn_id] = $c->cn_first . ' ' . $c->cn_last;
    }
    $form->rsmForm_define_field ('@contactId',RSM_COMBO,  $common->com_contactId, array('#list'=>$common->com_list_contact_array));
    $form->rsmForm_define_button ('@edit','Edit' );
    $form->rsmForm_define_buttonCancel();
}

function rsmStep_formOutput($chain, $common, $form, $kcmGlobals) {
    $emitter = new emitter_engine($form);
    $common->out_htmlPageStart($chain, $form,$kcmGlobals, $emitter);
    $url = $chain->rsmChain_url_buildUrl();
    $emitter->zone_core_container_start('sy-genre-default', $chain, $form,$url);
    $emitter->zone_core_scrollArea_start('sy-genre-default');

    $sel = array();
    $cb = $common->com_contactDataBase;
    foreach ($cb->cb_items as $key=>$c) {
        $sel[$c->cn_id] = $c->cn_first . ' ' . $c->cn_last;
    }
    $emitter->content_field('@contactId');
    print '<br><br>';
    $emitter->content_field(array('@edit','@cancel'));

    $common->out_htmlPageEnd($kcmGlobals, $form, $emitter);
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@
//@  Step 2
//@@@@@@@@@@@@@@@@@@@@

class chainStep_loginInfo extends rsm_step_object {
private $loserKid = NULL;
private $winnerKid = NULL;

function rsmStep_initAlways($chain, $common, $kcmGlobals) {
    if ( !empty($common->com_contactId)) {
        // read values from database (in test program the database is flat file, cached in memory)
        $common->com_contact = $common->com_contactDataBase->cb_items[$common->com_contactId]->createCopy();
    }
    $contact = $common->com_contact;
    // retrieve posted variables
    $this->rsmStep_updateIfPosted('@id'      ,$common->com_contact->cn_id );
    $this->rsmStep_updateIfPosted('@email'   ,$common->com_contact->cn_email);
    $this->rsmStep_updateIfPosted('@first'   ,$common->com_contact->cn_first,'xxx');
    $this->rsmStep_updateIfPosted('@last'    ,$common->com_contact->cn_last );
    $this->rsmStep_updateIfPosted('@pw'      ,$common->com_contact->cn_pw );
    $this->rsmStep_updateIfPosted('@comment' ,$common->com_contact->cn_comment );
}

function rsmStep_initThisStep($chain, $common, $kcmGlobals) {
}

function rsmStep_processValidate($chain, $common, $kcmGlobals) {
    $contact = $common->com_contact;
    if ( strpos($common->com_contact->cn_first,'x')!==FALSE) {
        $chain->rsmChain_form_setError('@first','First name cannot have x');
    }
    if ( strpos($contact->cn_last,'z')!==FALSE) {
        $chain->rsmChain_form_setError('@last','Last name cannot have z. To fix it use another name.');
    }
}

function rsmStep_formDefine($chain, $common, $form, $kcmGlobals) {
    $contact = $common->com_contact;
    $form->rsmForm_define_field('@id'     , RSM_TEXT     , $contact->cn_id,array('disabled'=>TRUE));
    $form->rsmForm_define_field('@email'  , RSM_TEXT     , $contact->cn_email);
    $form->rsmForm_define_field('@pw'     , RSM_TEXT     , $contact->cn_pw);
    $form->rsmForm_define_field('@first'  , RSM_TEXT     , $contact->cn_first);
    $form->rsmForm_define_field('@last'   , RSM_TEXT     , $contact->cn_last);
    $form->rsmForm_define_field('@comment', RSM_TEXTAREA , $contact->cn_comment);
    $form->rsmForm_define_button('@next'   , 'Next');
    $form->rsmForm_define_button('@cancel' , 'Cancel');
}

function rsmStep_formOutput($chain, $common, $form, $kcmGlobals) {
    $emitter = new kcmRoster_emitter($kcmGlobals, $form);
    $contact = $common->com_contact;
    //$chain->rsmChain_curStream_setValue('resultType','w');
    $common->out_htmlPageStart($chain, $form,$kcmGlobals, $emitter);
    $url = $chain->rsmChain_url_buildUrl();
    $emitter->zone_core_container_start('sy-genre-default', $chain, $form,$url);
    $emitter->zone_core_scrollArea_start('sy-genre-default');
    
    $emitter->xxpanel_start($contact->cn_first . ' ' . $contact->cn_last . ' Step 1 of 3');
    $emitter->xxpanel_descDataRow('ID',        '@id');
    $emitter->xxpanel_descDataRow('eMail',     '@email');
    $emitter->xxpanel_descDataRow('Password',  '@pw');
    $emitter->xxpanel_descDataRow('First Name','@first');
    $emitter->xxpanel_descDataRow('Last Name', '@last');
    $emitter->xxpanel_descDataRow('Comment',   '@comment');
    $emitter->xxpanel_end(array('@next','#sep', '@cancel'));
    
    $common->out_htmlPageEnd($kcmGlobals, $form, $emitter);
}

function rsmStep_processSubmit ($chain, $common, $kcmGlobals,$submit) {
    if ( $submit == '@cancel') {
        $chain->rsmChain_curStream_Clear();
        $chain->rsmChain_message_setStatus('Cancelled');
        $chain->rsmChain_backToSameUrl(1);
    }
    $chain->rsmChain_CopyPostedDataToSession();
    $chain->rsmChain_ValidateAndRedirectIfError();
    if ( $submit == '@next') {
        $chain->rsmChain_backToSameUrl(3);
    }
    $chain->rsmChain_backToSameUrl(NULL);
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@
//@  Step 3
//@@@@@@@@@@@@@@@@@@@@

class chainStep_phones extends rsm_step_object {

function rsmStep_initAlways($chain, $common, $kcmGlobals) {
   // retieve posted variables
    $contact = $common->com_contact;
    $this->rsmStep_updateIfPosted('@phone2',  $contact->cn_phone_2);
    $this->rsmStep_updateIfPosted('@phone3',  $contact->cn_phone_3);
    $this->rsmStep_updateIfPosted('@pType1',  $contact->cn_phone_1_type);
    $this->rsmStep_updateIfPosted('@pType2',  $contact->cn_phone_2_type);
    $this->rsmStep_updateIfPosted('@pType3H', $contact->cn_phone_3_typeH,' ');
    $this->rsmStep_updateIfPosted('@pType3C', $contact->cn_phone_3_typeC,' ');
    $this->rsmStep_updateIfPosted('@pType3W', $contact->cn_phone_3_typeW,'x');
    $this->rsmStep_updateIfPosted('@conDate', $contact->cn_contact_date);
    $this->rsmStep_updateIfPosted('@conTime', $contact->cn_contact_time);
}

function rsmStep_initThisStep($chain, $common, $kcmGlobals) {
}

function rsmStep_processValidate($chain, $common, $kcmGlobals) {
}

function rsmStep_processSubmit ($chain, $common, $kcmGlobals,$submit) {
    if ( $submit == '@cancel') {
        $chain->rsmChain_message_setStatus('Cancelled');
        $chain->rsmChain_curStream_Clear();
        $chain->rsmChain_backToSameUrl(1);
    }
    $chain->rsmChain_CopyPostedDataToSession();
    if ( $submit == '@back') {
        $chain->rsmChain_backToSameUrl(2);
    }
    if ( $submit == '@next') {
        $chain->rsmChain_backToSameUrl(4);
    }
    $chain->rsmChain_backToSameUrl(NULL);
}

function rsmStep_formDefine($chain, $common, $form, $kcmGlobals) {
    $contact = $common->com_contact;
    $comboList = array('h'=>'Home','c'=>'Cell','w'=>'Work');
    $form->rsmForm_define_field ('@phone1', RSM_TEXT,     $contact->cn_phone_1);
    $form->rsmForm_define_field ('@pType1', RSM_COMBO,    $contact->cn_phone_1_type, array('#list'=>$comboList) );
    $form->rsmForm_define_field ('@phone2', RSM_TEXT,     $contact->cn_phone_2);
    $form->rsmForm_define_field ('@pType2',  RSM_GROUP,   $contact->cn_phone_2_type,array('#label'=>'Phone Type','#unchecked'=>'x') );
    $form->rsmForm_define_field ('@pType2a', RSM_RADIO,   $contact->cn_phone_2_type,array('#groupid'=>'@pType2','#caption'=>'Home','#checked'=>'h') );
    $form->rsmForm_define_field ('@pType2b', RSM_RADIO,   $contact->cn_phone_2_type,array('#groupid'=>'@pType2','#caption'=>'Cell','#checked'=>'c') );
    $form->rsmForm_define_field ('@pType2c', RSM_RADIO,   $contact->cn_phone_2_type,array('#groupid'=>'@pType2','#caption'=>'Work','#checked'=>'w') );
    $form->rsmForm_define_field ('@phone3', RSM_TEXT,     $contact->cn_phone_3);
    $form->rsmForm_define_field ('@pType3H',RSM_CHECKBOX, $contact->cn_phone_3_typeH, array('#caption'=>'Home','#checked'=>'h','#unchecked'=>' '));
    $form->rsmForm_define_field ('@pType3C',RSM_CHECKBOX, $contact->cn_phone_3_typeC, array('#caption'=>'Cell','#checked'=>'c','#unchecked'=>' '));
    $form->rsmForm_define_field ('@pType3W',RSM_CHECKBOX, $contact->cn_phone_3_typeW, array('#caption'=>'Work','#checked'=>'w','#unchecked'=>'x'));
    $form->rsmForm_define_field ('@conDate',RSM_DATE,     $contact->cn_contact_date);
    $form->rsmForm_define_field ('@conTime',RSM_TIME,     $contact->cn_contact_time);
    $form->rsmForm_define_button ('@back', 'Back');
    $form->rsmForm_define_button ('@next', 'Next');
    $form->rsmForm_define_button ('@cancel', 'Cancel');
}

function rsmStep_formOutput($chain, $common, $form, $kcmGlobals) {
    $emitter = new kcmRoster_emitter($kcmGlobals, $form);
    $contact = $common->com_contact;
    $common->out_htmlPageStart($chain, $form, $kcmGlobals, $emitter);
    $url = $chain->rsmChain_url_buildUrl();
    $emitter->zone_core_container_start('sy-genre-default', $chain, $form, $url);
    $emitter->emit_nrLine('');
    $emitter->emit_nrLine('');
    $emitter->zone_core_scrollArea_start('sy-genre-default');
    $sep = '&nbsp;&nbsp;&nbsp;';
    
    $emitter->xxpanel_start($contact->cn_first . ' ' . $contact->cn_last . ' Step 2 of 3');
    $emitter->xxpanel_descDataRow('@Phone 1',array ('@phone1', '#sep', '@pType1'));
    $emitter->xxpanel_descDataRow('@Phone 2',array ('@phone2', '#sep' , '@pType2a', '#sep' , '@pType2b', '#sep' , '@pType2c') );
    $emitter->xxpanel_descDataRow('@Phone 3', array( '@pType3H', '#sep','@pType3C', '#sep', '@pType3W') );
    $emitter->xxpanel_descDataRow('Contact<br>Date',   '@conDate');
    $emitter->xxpanel_descDataRow('Contact<br>Time','@conTime');
    $emitter->xxpanel_end(array('@next','@back','@cancel'));
    
    $common->out_htmlPageEnd($kcmGlobals, $form, $emitter);
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@
//@  Step 4
//@@@@@@@@@@@@@@@@@@@@

class chainStep_address extends rsm_step_object {

function rsmStep_initAlways($chain, $common, $kcmGlobals) {
    $contact = $common->com_contact;
    $this->rsmStep_updateIfPosted('@street',$contact->cn_addr_street);
    $this->rsmStep_updateIfPosted('@city',  $contact->cn_addr_city);
    $this->rsmStep_updateIfPosted('@state', $contact->cn_addr_state);
    $this->rsmStep_updateIfPosted('@zip',   $contact->cn_addr_zip);
    $common->com_initialize();  //????????????????????? not here - but where ???????
}

function rsmStep_initThisStep($chain, $common, $kcmGlobals) {
}

function rsmStep_processValidate($chain, $common, $kcmGlobals) {
}

function rsmStep_processSubmit ($chain, $common, $kcmGlobals,$submit) {
    if ( $submit == '@cancel') {
        $chain->rsmChain_message_setStatus('Cancelled');
        $chain->rsmChain_backToSameUrl(1);
    }
    $chain->rsmChain_CopyPostedDataToSession();
    if ( $submit == '@back') {
        $chain->rsmChain_backToSameUrl(3);
    }
    if ( $submit == '@save') {
        $common->com_contactDataBase->cb_saveRecord($common->com_contact);
        $chain->rsmChain_message_setStatus($common->com_contact->cn_first . ' ' . $common->com_contact->cn_last . ' updated');
        $chain->rsmChain_curStream_Clear();
        $chain->rsmChain_backToSameUrl(1);
    }
    $chain->rsmChain_backToSameUrl(NULL);
}

function rsmStep_formDefine($chain, $common, $form, $kcmGlobals) {
    $contact = $common->com_contact;
    $form->rsmForm_define_field ('@street', RSM_TEXT,   $contact->cn_addr_street,array('#label'=>'Street','size'=>'50'));
    $form->rsmForm_define_field ('@city',   RSM_TEXT,   $contact->cn_addr_city,array('#label'=>'City','size'=>'16'));
    $form->rsmForm_define_field ('@state',  RSM_TEXT,   $contact->cn_addr_state,array('#label'=>'State','size'=>'2','maxlength'=>'2'));
    $form->rsmForm_define_field ('@zip',    RSM_TEXT,   $contact->cn_addr_zip,array('#label'=>'Zip','size'=>'10','maxlength'=>'10'));
    $form->rsmForm_define_button ('@back', 'Back');
    $form->rsmForm_define_buttonSubmit ('@save','Save');
    $form->rsmForm_define_buttonCancel('@cancel');
}

function rsmStep_formOutput($chain, $common, $form, $kcmGlobals) {
    $emitter = new kcmRoster_emitter($kcmGlobals, $form);
    $contact = $common->com_contact;
    $common->out_htmlPageStart($chain, $form, $kcmGlobals, $emitter);
    $url = $chain->rsmChain_url_buildUrl();
    $emitter->zone_core_container_start('sy-genre-default', $chain, $form, $url);
    $emitter->emit_nrLine('');
    $emitter->emit_nrLine('');
    $emitter->zone_core_scrollArea_start('sy-genre-default');

    $emitter->rsmPanel_start($contact->cn_first . ' ' . $contact->cn_last . ' Step 3 of 3');
    $emitter->rsmPanel_descDataRow('Address', array( '@street','<br>', '@city', '#sep', '@state','#sep','@zip' ));
    $emitter->rsmPanel_end(array('@save','@back','@cancel'));
    
    $common->out_htmlPageEnd($kcmGlobals, $form, $emitter);
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Data                           @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

class contact_item {
public $cn_id;
public $cn_email;
public $cn_first;
public $cn_last;
public $cn_pw;
public $cn_comment;
public $cn_phone_1;
public $cn_phone_2;
public $cn_phone_3;
public $cn_phone_1_type;
public $cn_phone_2_type;
public $cn_phone_3_typeH;
public $cn_phone_3_typeC;
public $cn_phone_3_typeW;
public $cn_contact_date;
public $cn_contact_time;
public $cn_addr_street;
public $cn_addr_city;
public $cn_addr_state;
public $cn_addr_zip;

function __construct() {
}

function createCopy() {
    $x = serialize($this);
    return unserialize($x);
}

function set_login($id, $email, $first,$last,$pw, $comment='') {
    $this->cn_id = $id;
    $this->cn_email = $email;
    $this->cn_first = $first;
    $this->cn_last = $last;
    $this->cn_pw = $pw;
    $this->cn_comment = $comment;
}

function set_phone ($phone1, $phone2, $phone3, $type1, $type2, $type3, $date, $time) {
    $this->cn_phone_1 = $phone1;
    $this->cn_phone_2 = $phone2;
    $this->cn_phone_3 = $phone3;
    $this->cn_phone_1_type = $type1;
    $this->cn_phone_2_type = $type2;
    $this->cn_phone_3_typeH = substr($type3,0,1);
    $this->cn_phone_3_typeC = substr($type3,1,1);
    $this->cn_phone_3_typeW = substr($type3,2,1);
    $this->cn_contact_date = $date;
    $this->cn_contact_time = $time;
}

function set_address($street, $city, $state, $zip) {
    $this->cn_addr_street = $street;
    $this->cn_addr_city = $city;
    $this->cn_addr_state = $state;
    $this->cn_addr_zip = $zip;
}

function convert_toString() {
    $s =    $this->cn_id
    . ',' . $this->cn_email
    . ',' . $this->cn_first
    . ',' . $this->cn_last
    . ',' . $this->cn_pw
    . ',' . $this->cn_phone_1
    . ',' . $this->cn_phone_2
    . ',' . $this->cn_phone_3
    . ',' . $this->cn_phone_1_type
    . ',' . $this->cn_phone_2_type
    . ',' . $this->cn_phone_3_typeH
    . ',' . $this->cn_phone_3_typeC
    . ',' . $this->cn_phone_3_typeW
    . ',' . $this->cn_contact_date
    . ',' . $this->cn_contact_time
    . ',' . $this->cn_addr_street
    . ',' . $this->cn_addr_city
    . ',' . $this->cn_addr_state
    . ',' . $this->cn_addr_zip
    . ',' . $this->cn_comment;
    return $s;
}

function convert_fromString($s) {
    if ( empty($s)) {  // need this check ??????, unsure why, maybe due to PHP_EOL ???????
        return FALSE;
    }
    $a = explode(',',$s);
    if ( count($a)!=20) {  // need this check ??????, unsure why, maybe due to PHP_EOL ???????
        return FALSE;
    }
    
    $this->cn_id               = $a[0];
    $this->cn_email            = $a[1];
    $this->cn_first            = $a[2];
    $this->cn_last             = $a[3];
    $this->cn_pw               = $a[4];
    $this->cn_phone_1          = $a[5];
    $this->cn_phone_2          = $a[6];
    $this->cn_phone_3          = $a[7];
    $this->cn_phone_1_type     = $a[8];
    $this->cn_phone_2_type     = $a[9];
    $this->cn_phone_3_typeH     = $a[10];
    $this->cn_phone_3_typeC     = $a[11];
    $this->cn_phone_3_typeW     = $a[12];
    $this->cn_contact_date     = $a[13];
    $this->cn_contact_time     = $a[14];
    $this->cn_addr_street      = $a[15];
    $this->cn_addr_city        = $a[16];
    $this->cn_addr_state      = $a[17];
    $this->cn_addr_zip        = $a[18];
    $this->cn_comment         = $a[19];
    return TRUE;
}

} // end class

class contact_batch {
public $cb_items = array();

function __construct() {
    $this->cb_items = array();
    if ( file_exists(TEST_FILE_NAME) ) {
        $a = file(TEST_FILE_NAME);
        for ($i=0; $i<count($a); ++$i) {
            $c = new contact_item;
            if ( $c->convert_fromString($a[$i])) {
                $this->cb_items[$c->cn_id] = $c;
            }
        }
    }
    else {
        $c = new contact_item;
        $c->set_login('1','abe@test.com','Abe','Abbot','Apassword','System Admin');
        $c->set_phone('111-111-1111','111-222-2222','111-333-3333','c','h','h  ','2018-01-01','01:01:01');
        $c->set_address('1 Acorn Street','Austin','AL','11111');
        $this->cb_items[$c->cn_id] = $c;
        $c = new contact_item;
        $c->set_login('2','bob@test.com','Bob','Benson','Bpassword');
        $c->set_phone('222-111-1111','222-222-2222','222-333-3333','c','h',' c ','2018-02-02','02:02:02');
        $c->set_address('1 Birch Street','Birmingham','Pa','22222');
        $this->cb_items[$c->cn_id] = $c;
        $c = new contact_item;
        $c->set_login('3','charles@test.com','Charles','Cox','Cpassword','Works two days a week from home');
        $c->set_phone('333-111-1111','333-222-2222','333-333-3333','c','h','hcw','2018-03-03','03:03:03');
        $c->set_address('1 Acorn Street','Chicago','CA','33333');
        $this->cb_items[$c->cn_id] = $c;
        $this->cb_saveFile();
    }
}

function cb_saveFile() {
    $s = '';
    $sep = '';
    foreach ($this->cb_items as $key=>$c) {
        $s .= $sep . $c->convert_toString();
        $sep = PHP_EOL;
    }
    $file = fopen(TEST_FILE_NAME,"w");
    fwrite($file,$s);
    fclose($file);
}

function cb_saveRecord($contact) {
    $this->cb_items[$contact->cn_id] = $contact->createCopy();
    $this->cb_saveFile();
}

} // end class

Class common_object {
public $com_contact;  // current contact object
public $com_contactDataBase;  // a fake database, actually an array of all the contact objects, stored in flat file
public $com_contactId;  // Id selected in step 1
public $com_list_contact_array;
public $com_list_contact_value;

function __construct() {
    $this->com_contactDataBase = new contact_batch;
    $this->com_contact = new contact_item;
}

function com_clear() {
    //$this->com_initialize();
}

function com_init_step1() {
    $this->com_contactId = NULL;
    $this->com_list_contact_array = array();
    $cb = $this->com_contactDataBase;
    foreach ($cb->cb_items as $key=>$c) {
        $this->com_list_contact_array[$c->cn_id] = $c->cn_first . ' ' . $c->cn_last;
    }
}

function com_initialize() {
}

function out_htmlPageStart($chain, $form,$kcmGlobals, $emitter) {
$emitter->head_add_cssFile('kcm/rsm/rsm-styleSheet.css','all','../../../');
$emitter->head_add_cssFile('kcm/kcm-kernel/kcm-kernel-styleSheet.css','all','../../../');
$emitter->zone_htmlHead();
$emitter->zone_body_start('rsm-zone-body-standard');
print '<h2>Test RSM Form, Chain, etc&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Edit Contact Information<hr></h2>';

$emitter->zone_messages($chain, $form);
}

function out_htmlPageEnd($kcmGlobals, $form, $emitter) {
    $emitter->zone_core_scrollArea_end();
    $emitter->zone_formContainer_end_new($form);
    $emitter->zone_body_end();
}

function saveValidatedGame($kcmGlobals,$chain) {  //--- also in bughouse
}

}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

//rc_session_initialize();

session_start();

$chain        = new rsm_chain_object();
$kcmGlobals   = NULL;
$common       = new common_object;

// process the form
$chain->rsmChain_define_initialize($common, $kcmGlobals);
$chain->rsmChain_define_step(1,'chainStep_select');
$chain->rsmChain_define_step(2,'chainStep_loginInfo');
$chain->rsmChain_define_step(3,'chainStep_phones');
$chain->rsmChain_define_step(4,'chainStep_address');
$chain->rsmChain_activate_step(); // proceed to current step


exit;


?>