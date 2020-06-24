<?php

//--- roster-setup-pointCategories.php ---

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

//===================================
//=     End of main program        ==
//=   Below are funcs and classes  ==
//===================================

class form_setupPointCategories_main extends Draff_Form {

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
    if ($submit == '@cancel') {
        $appChain->chn_curStream_Clear();
        $appChain->chn_message_set('Cancelled point category changes');
        kcmRosterLib_redirect_toMainMenu( $appGlobals, $appChain );
        $appChain->chn_launch_cancelChain(1,'');
     //   $appChain->chn_launch_continueChain(1);
    }
    $appChain->chn_form_savePostedData();

    if ($submit == '@submit') {
        $appChain->chn_ValidateAndRedirectIfError();
        $this->cat_save_data( $appGlobals, $appChain,  $appData->apd_roster_program);
        $appChain->chn_launch_restartAfterRecordSave(1);
        $appChain->chn_curStream_Clear();
        kcmRosterLib_redirect_toMainMenu( $appGlobals, $appChain );
        return;
    }
    $appChain->chn_launch_continueChain(1);

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-select' );
    $appEmitter->set_title('Setup Point Categories');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Point Categories', '$bothPeriods');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    for ($i=0; $i < $appData->apd_catCount; ++$i) {
        $fieldId = '@cat' . '_' . $i;
        $this->_addField( new Draff_Text( $fieldId, $appData->apd_catArray[$i] ) );
    }
    $this->drForm_addField( new Draff_Button( '@submit', 'Submit') );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
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
    for ($i=0; $i < $appData->apd_catCount; ++$i) {
         $fieldId = '@cat' . '_' . $i;
         $appEmitter->content_field ($fieldId);
         //$form->define_input( array('cat',$i), $this->catArray[$i] );
         $appEmitter->emit_nrLine('<br>');
         //$appEmitter->emit_nrLine($form->getHtml_Input(array('cat',$i), $this->catArray[$i] ) );
    }
    $appEmitter->emit_nrLine('<br>');
    $appEmitter->content_field (array('@submit','@cancel'));
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $program = $appData->apd_roster_program->rst_program;
//    $appData->apd_catArray = array(12);
//    for ($i=0; $i<count($program->prog_pointCategories); ++$i) {
//        $appData->apd_catArray[$i] = $program->prog_pointCategories[$i];
//    }
//    for ($i=0; $i < $appData->apd_catCount; ++$i) {
//        $fieldId = '@cat' . '_' . $i;
//        $this->step_updateIfPosted($fieldId,  $appData->apd_catArray[$i]);
//    }
//    //$this->prog_pointCategories = explode(',',$row['pPr:KcmPointCatList']);
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//    if ($this->step_init_submit_fieldId == 'submit') {
//        $err = FALSE;
//        $msg = '';
//        for ($i=0; $i<$appData->apd_catCount; ++$i) {
//            $fieldId = '@cat' . '_' . $i;
//            if (strpos($appData->apd_catArray[$i],',')) {
//                $err=TRUE;
//                $appChain->chn_message_set($fieldId, 'You must remove the commas');
//            }
//        }
//        //if ($msg !='') {
//        //    $form->form_theme-message-error-error_error = $msg;
//        //    return;
//        //}
//        //  $newCat = array();
//        //  for ($i=0; $i<$appData->catCount; ++$i) {
//        //      $s = trim($appData->catArray[$i]);
//        //      if (!empty($s)) {
//        //           $newCat[] = $s;
//        //      }
//        //  }
//        //  $program = $appData->apd_roster_program->rst_program;
//        //  $pointCatString = implode(',',$newCat);
//        //  $fields = array();
//        //  $fields['pPr:KcmPointCatList'] = $pointCatString;
//        //  $query = kcmRosterLib_db_update($appGlobals->gb_db,'pr:program', $fields, "WHERE `pPr:ProgramId`='{$program->prog_programId}'");
//        //  //echo 'Program save cats query:_ ' . $query;
//        //  $result = $appGlobals->gb_db->rc_query( $query );
//        //  if ($result === FALSE) {
//        //      $appGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
//        //  }
//        //  $this->catArray = $newCat;
//        //  while( $this->catCount < 12) {
//        //      $this->catArray[] = '';
//        //      $this->catCount = count($this->catArray);
//        //  }
//   }
//    //if (  is_numeric($appGlobals->gb_form->appChain->chn_submit_index) ) {
//    //   kcmRosterLib_redirect_toMainMenu( $appGlobals, $appChain,array('SdId'=>$appGlobals->gb_form->appChain->chn_submit_index));
//    //}
//    //$appChain->chn_step_executeNext(1);
//}

function cat_save_data( $appGlobals, $appChain, $roster) {
        $program = $roster->rst_program;
        $data['pPr:KcmPointCatList'] = implode(',',$appData->apd_catArray);
        $appGlobals->gb_sql->sql_saveRecord( 'pr:program', 'pPr:', 'pPr:ProgramId', $program->prog_programId, $data);
        $appChain->chn_message_set('Updated Point Categories');
        //$this->catArray = $newCat;
        //while( $this->catCount < 12) {
        //    $this->catArray[] = '';
        //    $this->catCount = count($this->catArray);
        //}
}

}  // end class

class appData_setupPointCategories extends draff_appData {
public $apd_roster_program;
public $apd_catCount = 12;
public $apd_catArray;

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
$appData = new appData_setupPointCategories;
$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );

$appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);

$appChain->chn_form_register(1,'form_setupPointCategories_main');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>