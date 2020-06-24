<?php

// pay-setup-proxy.php

ob_start();  // output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );
include_once( '../../rc_job-appData-functions.inc.php' );

include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-objects.inc.php' );
include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );

include_once( '../kcm-kernel/kernel-globals.inc.php');
include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-commonForm-setProxy.inc.php' ); // just for this script

include_once( 'pay-system-emitter.inc.php' );
include_once( 'pay-system-globals.inc.php' );
include_once( 'pay-system-payData.inc.php' );


Class appForm_proxy_edit extends Draff_Form {

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

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
    $appData->apd_setProxy->sharedStep_processSubmit( $appGlobals, $appChain, $submit, $this);
    //$appData->com_setProxy_step->drForm_processSubmit( $appData, $appGlobals, $appChain, $submit, $this);
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-report' );
    $appEmitter->set_title('Payroll - ???');
    $appEmitter->set_menu_standard( $appChain, $appGlobals );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    $appEmitter->set_title('');
    $appEmitter->set_menu_customize( $appChain, $appGlobals );

}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_setProxy->sharedStep_formDefine($appChain, $this, $appGlobals);
    //$appData->com_setProxy_step->drForm_initFields( $appData, $appGlobals, $appChain );
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

$appGlobals = new kcmPay_globals();
$appGlobals->gb_forceLogin ();
$appData = new local_appData($appGlobals);

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@         Process Page               @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );
$appChain->chn_form_register(1,'appForm_proxy_edit');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>