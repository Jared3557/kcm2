<?php

//--- roster-results-set-classDate.php ---

ob_start();  // output buffering (needed for redirects, draff-appHeader changes)

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

//include_once( 'roster-system-data-games.inc.php' );  //???? need session status from here - should redefine

class appForm_setClassDate_main extends Draff_Form {  // webPageHeader widget

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

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
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
    $appEmitter->set_theme( 'theme-panel' );
    $appEmitter->set_title('Select Active Class Date');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
//    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Select Active Class Date', '$noPeriod');
//    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
 //   $appEmitter->set_menu_customize( $appChain, $appGlobals  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    foreach ($appData->apd_roster_program->rst_classSchedule->schProg_items as $schedDateItem) {
        $d = draff_dateAsString($schedDateItem->cSD_classDate, 'M j');
        $t = ' ' . draff_timeAsString($schedDateItem->cSD_startTime) . '-' . draff_timeAsString($schedDateItem->cSD_endTime);
        $caption = $d . $t;
        $tagProps = array('class'=>'draff-button-select') ;
        if ($schedDateItem->cSD_isHoliday) {
            $desc = '<br>Holiday - ' . $draff_emitter_engine::getString_sizedMemo('' . $schedDateItem->cSD_notes,15);

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

class appData_setClassDate extends draff_appData {
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

$appGlobals = new kcmRoster_globals();
$appGlobals->gb_forceLogin ();
$appData = new appData_setClassDate($appGlobals);
$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );

$appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);

$appChain->chn_form_register(1,'appForm_setClassDate_main');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>