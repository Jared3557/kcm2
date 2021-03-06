<?php

// pay-reports-accepted.php

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );
include_once( '../../rc_job-appData-functions.inc.php' );

include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-database.inc.php');
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );
include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-menu.inc.php' );
include_once( '../draff/draff-page.inc.php' );

include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'pay-system-payData.inc.php' );
include_once( 'pay-system-appEmitter.inc.php' );
include_once( 'pay-system-globals.inc.php' );

include_once( 'pay-report-checkRegister.inc.php');

//include_once( 'pay-reports.inc.php' );

Class appForm_FinalPayrollReport_main extends kcmKernel_Draff_Form {
public $who_staffBatch;
public $who_staffArray;


//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//}
//
function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-report' );
    $appEmitter->emit_options->set_title('Payroll - ???');
    $appGlobals->gb_ribbonMenu_Initialize( $appChain, $appGlobals );
    $appGlobals->gb_menu->drMenu_customize();
    $appEmitter->emit_options->set_title('Gross Pay Report');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter);
    $appGlobals->gb_menu->drMenu_customize();
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
     $outReport_checkRegister = new stdReport_checkRegister;
    $outReport_checkRegister->chkReg_stdReport_init_styles($appEmitter);  //??????????????
    $appEmitter->emit_options->addOption_styleTag('button.staff',  'margin:4pt 8pt 4pt 8pt; padding: 3pt 12pt 3pt 12pt;background-color:#ddffdd;');
    $appEmitter->zone_start('draff-zone-content-report');
    $outReport_checkRegister->chkReg_stdReport_output($appEmitter, $appGlobals, $appGlobals->gb_period_current;);
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

class application_data extends draff_appData {

//---  user information
public $apd_user_proxy;
public $apd_user_isMaster;  // is payroll master

function __construct( $appGlobals ) {
    $this->user_init( $appGlobals );
}

function apd_formData_get( $appGlobals, $appChain ) {
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

function user_init( $appGlobals ) {
    $this->apd_user_proxy  = $appGlobals->gb_user;
    $this->apd_user_isMaster = $this->apd_user_proxy->krnUser_isPayrollManager;
    $this->user_user   = !$this->apd_user_isMaster;
}

}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appGlobals = new kcmPay_globals();
$appGlobals->gb_forceLogin ();
$appData = new application_data($appGlobals);


//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@         Process Page               @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

$appChain = new Draff_Chain(  'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmPay_globals());
$appChain->chn_register_appData( $appData = new application_data);
$appGlobals->gb_forceLogin ();

$appChain->chn_form_register(1,'appForm_FinalPayrollReport_main');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>