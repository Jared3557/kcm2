<?php

// gateway-setup-proxy.php

ob_start();  // output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );

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

include_once( 'gateway-system-globals.inc.php');

include_once( '../kcm-kernel/kernel-commonForm-setProxy.inc.php' ); // just for this script

//===================================
//=     End of main program        ==
//=   Below are funcs and classes  ==
//===================================

class appForm_setupProxy_edit extends appForm_shared_setProxy_edit {  // which extends kcmKernel_Draff_Form

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    parent::drForm_process_submit( $appData, $appGlobals, $appChain);
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
     parent::drForm_outputContent($appData, $appGlobals, $appChain, $appEmitter);
//    $outReport_sharedProxyEdit = new appData_commonSetProxy();
//    $outReport_sharedProxyEdit->stdReport_init_styles($appEmitter);
//    $appEmitter->zone_start('draff-zone-content-report');
//
//    $outReport_sharedProxyEdit->stdReport_output($appEmitter, $appGlobals, $this );
//
//    $appEmitter->zone_end();
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
	$appEmitter->emit_options->set_title('Setup Proxy');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appGlobals);
    $appGlobals->gb_menu->drMenu_customize();
    parent::drForm_initHtml($appData, $appGlobals, $appChain, $appEmitter);
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    parent::drForm_initFields($appData, $appGlobals, $appChain);
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    parent::drForm_process_output($appData, $appGlobals, $appChain, $appEmitter);
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    parent::drForm_outputHeader($appData, $appGlobals, $appChain, $appEmitter);
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    parent::drForm_outputFooter($appData, $appGlobals, $appChain, $appEmitter);
}

} // end class

class application_data extends appData_commonSetProxy { // Unusual extends
public $apd_edit_commonProxy;

//function sd_edit_getData( $appGlobals, $appChain ) {
//    $this->apd_edit_commonProxy = new appForm_shared_setProxy_edit('gateway.php');
//}

function com_get_element($appChain, $key) {
    return $appChain->chn_data_joint_get($key,'@none');
}

function com_save_element($appChain, $key, $value) {
    if ( $value == '@none')  {
        $appChain->chn_data_joint_unset($key);
    }
    else {
        $appChain->chn_data_joint_set($key,$value);
    }
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
rc_session_initialize();

$appChain = new Draff_Chain(  'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmGateway_globals());
$appChain->chn_register_appData( new application_data());
$appGlobals->gb_forceLogin ();

$appChain->chn_form_register(1,'appForm_setupProxy_edit');
$appChain->chn_form_launch(NULL); // proceed to current step


exit;


?>