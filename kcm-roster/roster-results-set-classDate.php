<?php

//--- roster-results-set-classDate.php ---

ob_start();  // output buffering (needed for redirects, draff-appHeader changes)

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

include_once( 'roster-system-functions.inc.php' );
include_once( 'roster-system-globals.inc.php' );
include_once( 'roster-system-data-roster.inc.php');

//include_once( 'roster-system-data-games.inc.php' );  //???? need session status from here - should redefine

class appForm_setClassDate_main extends kcmKernel_Draff_Form {  // webPageHeader widget

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//    if (  is_numeric($this->step_init_submit_suffix) ) {
//        $appChain->chn_curStream_Clear();
//        $appChain->chn_message_set('Class date changed');
//        kcmRosterLib_redirect_toMainMenu( $appGlobals, $appChain,array('SdId'=>$this->step_init_submit_suffix));
//    }
//    $appChain->chn_step_executeNext(1);
//}

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    if (  isset($appChain->chn_submit[1]) and is_numeric($appChain->chn_submit[1]) ) {
        $appChain->chn_message_set('Class date changed');
        kcmRosterLib_redirect_toMainMenu( $appGlobals, $appChain,array('SdId'=>$appChain->chn_submit[1]));
    }

    $appChain->chn_form_savePostedData();
    $appChain->chn_ValidateAndRedirectIfError();
    $appChain->chn_launch_continueChain(1);

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-panel' );
    $appEmitter->emit_options->set_title('Select Active Class Date');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize(  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    foreach ($appData->apd_roster_program->rst_classSchedule->schProg_items as $schedDateItem) {
        $d = draff_dateAsString($schedDateItem->cSD_classDate, 'M j');
        $t = ' ' . draff_timeAsString($schedDateItem->cSD_startTime) . '-' . draff_timeAsString($schedDateItem->cSD_endTime);
        $caption = $d . $t;
        $tagProps = array('class'=>'draff-button-select') ;
        if ($schedDateItem->cSD_isHoliday) {
            $desc = '<br>Holiday - ' . $Draff_Emitter_Html::getString_sizedMemo('' . $schedDateItem->cSD_notes,15);

            $caption = $caption . $desc;
            $tagProps['disabled']='';
        }
        else if ( !empty($schedDateItem->cSD_notes)  ){
            $caption .= '<br>' . $schedDateItem->cSD_notes;
        }
        $fieldId = 'sched' . '_' . $schedDateItem->cSD_scheduleDateId;
        $this->drForm_addField( new Draff_Button(  '@' . $fieldId , $caption, $tagProps ) );
   }
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-content-default');
    foreach ($appData->apd_roster_program->rst_classSchedule->schProg_items as $schedDateItem) {
        $fieldId = '@sched' . '_' . $schedDateItem->cSD_scheduleDateId;
        $appEmitter->content_field ($fieldId);
    }
     $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}  // end widget class

class application_data extends draff_appData {
public $apd_roster_program;

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

$appChain = new Draff_Chain(  'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmRoster_globals());
$appChain->chn_register_appData( $appData = new application_data(application_data));
$appGlobals->gb_forceLogin ();

$appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);

$appChain->chn_form_register(1,'appForm_setClassDate_main');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>