<?php

// pay-setup-proxy.php

ob_start();  // output buffering (needed for redirects, content changes)

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

include_once( '../kcm-kernel/kernel-globals.inc.php');
include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-commonForm-setProxy.inc.php' ); // just for this script

include_once( 'pay-system-emitter.inc.php' );
include_once( 'pay-system-globals.inc.php' );
include_once( 'pay-system-payData.inc.php' );


Class appForm_proxy_edit extends kcmKernel_Draff_Form {

// function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//     $appData->apd_setProxy->sharedStep_initAlways( $appGlobals, $appChain );
//     $appData->apd_setProxy->sharedStep_initThisStep( $appGlobals, $appChain, $this);
//     //$appData->com_setProxy_step->step_init_submit_accept( $appData,$appGlobals,  $appChain, $this);
// }
//
// function drForm_validate( $appData, $appGlobals, $appChain ) {
//     $appData->apd_setProxy->sharedStep_processValidate( $appGlobals, $appChain );
//     //$appData->com_setProxy_step->drForm_validate( $appData, $appGlobals, $appChain );
// }

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
    $appData->apd_setProxy->sharedStep_processSubmit( $appGlobals, $appChain, $submit, $this);
    //$appData->com_setProxy_step->drForm_process_submit( $appData, $appGlobals, $appChain, $submit, $this);
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-report' );
    $appEmitter->emit_options->set_title('Payroll - ???');
    $appGlobals->gb_ribbonMenu_Initialize( $appChain, $appGlobals );
    $appGlobals->gb_menu->drMenu_customize();
    $appEmitter->emit_options->set_title('');

}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_setProxy->sharedStep_formDefine($appChain, $this, $appGlobals);
    //$appData->com_setProxy_step->drForm_initFields( $appData, $appGlobals, $appChain );
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $outReport_sharedProxyEdit = new appData_commonSetProxy();
    $outReport_sharedProxyEdit->stdReport_init_styles($appEmitter);
    $appEmitter->zone_start('draff-zone-content-report');

    $outReport_sharedProxyEdit->stdReport_output($appEmitter, $appGlobals, $form );

    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

class appData_proxy extends draff_appData {
//public $appGlobals;
public $apd_setProxy;
//public $com_setProxy_step;
//public $com_setProxy_common;

function __construct() {
    //$this->com_setProxy_step = new rgb_step_setProxy;
    //$this->com_setProxy_common = new chainStep_setProxy_common('pay-home.php');
    $this->apd_setProxy = new appForm_shared_setProxy_edit('pay-home.php','pmu-setProxy');
}

function apd_formData_get( $appGlobals, $appChain ) {
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appChain = new Draff_Chain(  'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmPay_globals());
$appChain->chn_register_appData( new appData_proxy());
$appGlobals->gb_forceLogin ();

$appChain->chn_form_register(1,'appForm_proxy_edit');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>