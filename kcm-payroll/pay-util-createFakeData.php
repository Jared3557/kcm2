<?php

// pay-util-createFakeData.php

ob_start();  // output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );
include_once( '../../rc_job-appData-functions.inc.php' );

include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );
include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-objects.inc.php' );

include_once( '../kcm-kernel/kernel-emitter.inc.php');
include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'pay-system-payData.inc.php' );
include_once( 'pay-system-appEmitter.inc.php' );
include_once( 'pay-system-globals.inc.php' );

Class appForm_createFakeData extends Draff_Form {
public $staffRateBatch;

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//}

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    $appChain->chn_form_savePostedData();
    //$staffId = $this->step_init_submit_suffix;
    if ( $submit == '@create' ) {
        $appData->createFakeData( $appGlobals );
        exit;
        $appChain->chn_message_set('Saved Fake Data');
        $appChain->chn_curStream_Clear();
        $appChain->chn_redirect_toUrl(1);
    }
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-report' );
    $appEmitter->set_title('Payroll - ???');
    $appEmitter->set_menu_standard( $appChain, $appGlobals );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    $appEmitter->set_title('Create Fake Data');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter);
    $appEmitter->set_menu_customize( $appChain, $appGlobals );
    //$report = new report_staffRates_all;
    //$report->report_setStyles($appEmitter);
     $appEmitter->addOption_styleTag('button.large', 'background-color:#ffcccc;border:6px solid red; margin:20pt; padding:20pt; font-size:20pt;');

}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $this->drForm_addField( new Draff_Button( '@create','Create Fake Data',array('class'=>'large')) );
    // $this->drForm_addField( new Draff_Button( '@mutate','Mutate Staff Wages<br>(Staff-Rate Table)',array('class'=>'large')) );
}

function drForm_outputPage ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->krnEmit_output_htmlHead  ( $appData, $appGlobals, $appChain, $appEmitter );
    $appEmitter->krnEmit_output_bodyStart ( $appData, $appGlobals, $appChain, $this );
    $appEmitter->krnEmit_output_ribbons  ( $appData, $appGlobals, $appChain, $this );
    $this->drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter );
    $this->drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter );
    $this->drForm_outputFooter  ( $appData, $appGlobals, $appChain, $appEmitter );
    $appEmitter->krnEmit_output_bodyEnd  ( $appData, $appGlobals, $appChain, $this );
}

function drForm_outputHeader ( $appGlobals, $appChain, $appEmitter, $form ) {
}

function drForm_outputContent ( $appGlobals, $appChain, $appEmitter, $form ) {
     $appEmitter->zone_start('draff-zone-content-report');


    //$appEmitter->payZone_htmlHead_emit($appChain, $form, $appGlobals, 'Create Fake Data');
   // $appEmitter->payZone_bodyWithForm_start($appChain, $form, $appGlobals, 'Create Fake Data','');
    $appEmitter->content_block('@create');
   //$s = $form->drForm_field_out('@create');

    //print '<br><br>';
    //$s = $form->drForm_field_out('@mutate');
    //$report->report_print($this->staffRateBatch,$appChain, $appData, $appEmitter, $form, $appGlobals);
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appGlobals, $appChain, $appEmitter, $form ) {
}

} // end class


class appData_createFakeData extends draff_appData {

public $apd_tskItem_batch;
public $apd_staff_batch;
public $apd_staff_rateClass = array();
public $apd_staff_amount = array();
public $apd_rateClass_batch;
public $apd_db;
public $apd_rc_salaried;
public $apd_rc_field_coach;
public $apd_rc_field_sm;
public $apd_rc_office;
public $apd_rc_sick;
public $apd_rc_vacation;
public $apd_rc_bonus;

//public $staffRateItem_record;
//public $staffRateItem_id;

//public $edit_staffId;
//public $edit_defaultGroup;
//public $edit_staffRateGroup;

function __construct() {
}

function apd_formData_get( $appGlobals, $appChain ) {
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

function mangle_staffRate($amount) {
        if ( $amount>400) {
            $factor = 4;
        }
        else if ( $amount>100) {
             $factor = 3;
        }
        else if ( $amount>50) {
             $factor = 2.5;
        }
        else if ( $amount>20) {
             $factor = 2;
        }
        else if ( $amount>10) {
             $factor = 1.8;
        }
        else {
             $factor = 1.5;
        }
        $low = $amount / $factor;
        $high = $amount * $factor;
        return mt_rand($low, $high );
}

function createFakeData( $appGlobals ) {
    $this->apd_db = $appGlobals->gb_db;
    //$this->apd_rateClass_batch = new payData_rateClass_batch;
    //$this->apd_rateClass_batch->rateClass_getBatch( $appGlobals );
    $this->apd_staff_batch = new payData_employee_batch;
    $this->apd_staff_batch->epyBat_read_all( $appGlobals );
    foreach ($this->apd_staff_batch->epyBat_empoyeeArray as $staffId => $staff) {
        $staff->emp_rateField = $this->mangle_staffRate(20);
        $staff->emp_rateAdmin = $this->mangle_staffRate(10);
        $staff->emp_saveRecord( $appGlobals );
    }
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$loginAuthorization = new kcmKernel_login_authorization;
$loginAuthorization->krnLogin_forceLogin ('Kcm-Payroll', 'kcmPay_emitter');

$appChain        = new Draff_Chain();
$db           = rc_getGlobalDatabaseObject();
$appGlobals   = new kcmPay_globals($appChain,$db);
$appData       = new appData_createFakeData;

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@         Process Page               @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

$appChain->chn_form_register(1,'appForm_createFakeData');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>