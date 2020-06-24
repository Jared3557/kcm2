<?php

// roster-results-set-period.php

ob_start();  // output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );

include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-objects.inc.php' );
include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );

include_once( '../kcm-kernel/kernel-emitter.inc.php');
include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'roster-system-functions.inc.php' );
include_once( 'roster-system-globals.inc.php' );
include_once( 'roster-system-data-roster.inc.php');

//===================================
//=     End of main program        ==
//=   Below are funcs and classes  ==
//===================================

Class appForm_selectPeriod_main extends Draff_Form {

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    if ($appChain->chn_submit[0] == '@cancel') {
        $appChain->chn_curStream_Clear();
        $appChain->chn_message_set('Period unchanged');
        kcmRosterLib_redirect_toMainMenu( $appGlobals, $appChain );
    }

    $appChain->chn_form_savePostedData();
    //$appChain->chn_ValidateAndRedirectIfError();

    if ( isset($appChain->chn_submit[1]) and  is_numeric($appChain->chn_submit[1]) ) {
        //$appChain->chn_curStream_Clear();
        $appChain->chn_message_set('Period changed');
        kcmRosterLib_redirect_toMainMenu( $appGlobals, $appChain,array('PeId'=>$appChain->chn_submit[1]));
    }
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-panel' );
    $appEmitter->set_title('Set Class Period');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
   // kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Set Class Period');  // '$noPeriod'  ????
   // $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
   // $appEmitter->set_menu_customize( $appChain, $appGlobals  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    foreach ($appData->apd_roster_program->rst_map_period as $period) {
        $time = ' (' . $period->perd_timeStartDesc . '-' . $period->perd_timeEndDesc . ')';
        $fieldId = 'period' . '_' . $period->perd_periodId;
        $this->drForm_addField( new Draff_Button( '@' . $fieldId , $period->perd_periodName . $time, array( 'class'=>'periodButton') ) );
    }
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel' ) );
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
    $appEmitter->zone_start('draff-zone-filters-default');
    $appEmitter->emit_nrLine('<h2>Select Period</h2>');
    $appEmitter->content_field ('@cancel');
    $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-content-default');

    $appEmitter->emit_nrLine('');
    foreach ($appData->apd_roster_program->rst_map_period as $period) {
        $fieldId = '@period' . '_' . $period->perd_periodId;
        $appEmitter->content_field ($fieldId);
    }
    $appEmitter->emit_nrLine('<br><br>');
    $appEmitter->zone_end();

}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

Class local_session {
}

class appData_selectPeriod extends draff_appData {
public $apd_roster_program;
public $kcmEmitter = NULL;

function __construct() {
}

function apd_formData_get( $appGlobals, $appChain ) {
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
rc_session_initialize();

$appGlobals = new kcmRoster_globals();
$appGlobals->gb_forceLogin ();
$appData = new appData_selectPeriod();
$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );

$appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);

$appChain->chn_form_register(1,'appForm_selectPeriod_main');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>