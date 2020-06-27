<?php

// kernel-debug.php

ob_start();  // output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );

include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-database.inc.php');
include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );
include_once( '../draff/draff-menu.inc.php' );
include_once( '../draff/draff-page.inc.php' );

include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

Class form_kernelDebug_main extends kcmKernel_Draff_Form {

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-report' );
    $appEmitter->emit_options->set_title('KCM Debug');
    $appGlobals->gb_menu->drMenu_customize();
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    // no controls on form
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-header theme-select');
    $appEmitter->emit_nrLine('KCM Debug');
    $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-report');
    $session = draff_get_session();
    $session->ses_dbg('KCM Debug');
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

class appData_kernelDebug extends draff_appData {

function __construct() {
}

function apd_formData_get( $appGlobals, $appChain ) {
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

function com_htmlOut_startOfPage( $appGlobals, $appChain, $appEmitter, $form ) {
  //  $appEmitter->gwyEmit_kernelOverride_webPage_init( $appGlobals, $appChain,'ls_ul','ls*');  // includes adding of css files
    $appEmitter->emit_options->addOption_styleTag('a.loc-link', 'display:inline-block;padding: 6pt 4pt 6pt 4pt; background-color:white; border:1px solid #8888ff;margin: 5pt 5pt 5pt 3pt;');
    $appEmitter->krnEmit_webPageOutput_start( $appGlobals, $appChain, $form,'Useful Links');

}

}

class local_kcmKernel_globals  extends kcmKernel_globals {
function gb_ribbonMenu_Initialize($chain, $emitter, $overrides=NULL) {
}
} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
rc_session_initialize();

$appChain = new Draff_Chain(  'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new local_kcmKernel_globals());
$appChain->chn_register_appData( new appData_kernelDebug());
$appGlobals->gb_forceLogin ();

$appChain->chn_form_register(1,'form_kernelDebug_main');
$appChain->chn_form_launch(); // proceed to current step

exit;


?>