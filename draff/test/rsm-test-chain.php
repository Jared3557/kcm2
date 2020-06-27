<?php

//--- rsm-test-chain.php ---

ob_start();  // output buffering (needed for redirects, rsm-appHeader changes)

include_once( '../rsm-functions.inc.php' );
include_once( '../rsm-objects.inc.php' );
include_once( '../rsm-chain.inc.php' );
include_once( '../rsm-emitter.inc.php' );
include_once( '../rsm-form.inc.php' );

include_once( '../../../rc_defines.inc.php' );  // needed for initialization


//@@@@@@@@@@@@@@@@@@@@
//@  Step 1
//@@@@@@@@@@@@@@@@@@@@

class chainStep_testChain_page1 extends rsm_form_object {  // specify winner

function drForm_process_submit ($chain, $scriptData, $appGlobals) {

    $chain->chn_data_posted_acceptSubmitted();

    if (  is_numeric($this->step_init_submit_suffix) ) {
        $scriptData->com_winnerId = $this->step_init_submit_suffix;
        $this->step_setShared('#winnerId',$scriptData->com_winnerId);
        $chain->chn_launch_newChain(2);  // start chain
    }

    $chain->chn_launch_continueChain(1);

}

//function step_init_submit_accept($chain, $scriptData, $appGlobals) {
//    $this->step_updateIfPosted('#winnerId',$scriptData->com_winnerId );
//}
//
//function rsmForm_validate($chain, $scriptData, $appGlobals) {
//}

function rsmForm_init_controls($chain, $scriptData, $appGlobals) {
    $form->rsmForm_define_button('@draw' , 'Report a draw' );
}


function rsmForm_init_output($chain, $scriptData, $emitter, $appGlobals) {
    $scriptData->common_init_output($emitter);
}

function rsmForm_emit_headers ($chain, $scriptData, $emitter, $appGlobals) {  // chainStep_testChain_page1
    $controls = $form->rsmForm_gen_field('@draw');
}

function rsmForm_emit_content ($chain, $scriptData, $emitter, $appGlobals) { // chainStep_testChain_page1
    $emitter->zone_start('rsm-zone-content-select');
    $scriptData->common_init_content($chain,$emitter);
    $emitter->zone_end();
}

function rsmForm_emit_footer ($chain, $scriptData, $emitter, $appGlobals) {
}

}  // end class chainStep_testChain_page1

//@@@@@@@@@@@@@@@@@@@@
//@  Step 2
//@@@@@@@@@@@@@@@@@@@@

class chainStep_testChain_page2 extends rsm_form_object {  // specify loser

function rsmForm_process_submit ($chain, $scriptData, $appGlobals,$submit) {

    if ($submit == '@cancel') {
        $winner = kcmRosterLib_getKidName($appGlobals, $this->step_getShared('#winnerId',0));
        $chain->chn_message_setStatus('Cancelled win for '.$winner); // no longer available (cancel doesn't do getPostedValues, etc??) '.$scriptData->com_kid_firstName . ' ' . $scriptData->com_kid_lastName
        $chain->chn_curStream_Clear();
        $chain->chn_launch_cancel(1,'');
        return;
    }

    $chain->chn_data_posted_acceptSubmitted();
    $chain->chn_ValidateAndRedirectIfError();

    if (  is_numeric($this->step_init_submit_suffix) ) {
        $this->loser_loserrId = $this->step_init_submit_suffix;
        $game = $scriptData->com_gameNew($appGlobals, $scriptData->com_gameType);
        $game->gag_gameRecord_addPlayer($this->loser_winnerId ,1,0,0);
        $game->gag_gameRecord_addPlayer($this->loser_loserrId ,0,0,1);
        $scriptData->com_gameSave($chain, $appGlobals, $game);
        $chain->chn_curStream_Clear();
        $chain->chn_launch_restartAfterRecordSave(1);  // history
        return;
    }

    $chain->chn_launch_continueChain(1);  // no history

}

//function step_init_submit_accept($chain, $scriptData, $appGlobals) {
//}
//
//function rsmForm_validate($chain, $scriptData, $appGlobals) {
//}

function rsmForm_init_controls($chain, $scriptData, $appGlobals) {
    $form->rsmForm_define_button('@draw' , 'Report a draw' );
    $form->rsmForm_define_button('@cancel' , 'Cancel' );
}

function rsmForm_init_output($chain, $scriptData, $emitter, $appGlobals) {
}

function rsmForm_emit_headers ($chain, $scriptData, $emitter, $appGlobals) {  // chainStep_testChain_page2
    $controls = $form->rsmForm_gen_field(array('@cancel')); // '@draw',
}

function rsmForm_emit_content ($chain, $scriptData, $emitter, $appGlobals) {  // chainStep_testChain_page2
    $emitter->zone_start('rsm-zone-content-select');
    $emitter->zone_end();
}

function rsmForm_emit_footer ($chain, $scriptData, $emitter, $appGlobals) {
}

}  // end class chainStep_testChain_page2

//@@@@@@@@@@@@@@@@@@@@
//@  Step 3
//@@@@@@@@@@@@@@@@@@@@

class chainStep_testChain_page3 extends rsm_form_object {  // specify both players of draw

function rsmForm_process_submit ($chain, $scriptData, $appGlobals,$submit) {

    if ($submit == '@cancel') {
        $chain->chn_message_setStatus('Cancelled draw' . $msg);
        $chain->chn_curStream_Clear();
        $chain->chn_launch_cancel(1,'');
        return;
    }

    $chain->chn_data_posted_acceptSubmitted();
    $chain->chn_ValidateAndRedirectIfError();
    if ($submit == '@submit') {
        $scriptData->com_gameSave($chain, $appGlobals, $game);
        $chain->chn_curStream_Clear();
        $chain->chn_launch_restartAfterRecordSave(1);  // cancel chain
    }

    $chain->chn_launch_continueChain(1);  // cancel chain
}

//function step_init_submit_accept($chain, $scriptData, $appGlobals) {
//}

//function rsmForm_validate($chain, $scriptData, $appGlobals) {
//}

function rsmForm_init_controls($chain, $scriptData, $appGlobals) {
    $form->rsmForm_define_button('@submit' , 'Submit' );
    $form->rsmForm_define_button('@cancel' , 'Report a win/loss' );
}

function rsmForm_init_output($chain, $scriptData, $emitter, $appGlobals) {
}

function rsmForm_emit_headers ($chain, $scriptData, $emitter, $appGlobals) {  // chainStep_testChain_page3
    $emitter->zone_start('rsm-zone-header-select');
    $emitter->emit_nrLine('');
    $emitter->div_start('kcmkrn-question-div');
    $emitter->emit_nrLine( '<span class="rsm-text-question-bold">Select the two players that drew</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
    $emitter->content_field('@cancel');
    $emitter->div_end();
    $emitter->emit_nrLine('');
    $emitter->zone_end();
}

function rsmForm_emit_content ($chain, $scriptData, $emitter, $appGlobals) {  // chainStep_testChain_page3
    $emitter->zone_start('rsm-zone-content-select');
    $emitter->zone_end();
}

function rsmForm_emit_footer ($chain, $scriptData, $emitter, $appGlobals) {
    $emitter->zone_start('rsm-zone-footer-select');
    $emitter->div_start('kcmkrn-question-div');
    $emitter->content_field('@submit');
    $emitter->div_end();
    $emitter->zone_end();
}

}  // end class chainStep_testChain_page3

class local_script_data extends rsm_script_data {

   public $kcmEmitter = NULL;
   public $com_gameType;
   public $com_gameTypeDesc = '?';
   public $com_gameTypeMenuKey = '?';
   public $com_winnerId;
   public $com_loserrId;
   public $com_drawerIds;

function __construct() {
}

function sd_formData_get($chain, $appGlobals) {
}

function sd_formData_validate($chain, $appGlobals) {
}

function common_init_output($emitter) {
}

function common_init_content($chain,$emitter) {

   $emitter->table_start('rsm-report',2);
    $emitter->table_head_start();
    $emitter->row_oneCell('Stream');
    $emitter->table_head_end();
    $this->common_row($emitter, 'Index', $chain->chn_stream_cur->stream_key);
    $this->common_row($emitter, 'Token', $chain->chn_stream_cur->stream_key);
     $emitter->table_end();

    $emitter->table_start('rsm-report',2);
    $emitter->table_head_start();
    $emitter->row_oneCell('Steps');
    $emitter->table_head_end();
    foreach ( $chain->chn_step_array as $step) {
        $this->common_row($emitter, $step->rsmForm_key, $step->rsmForm_className);
    }
    $this->common_row($emitter);
    $emitter->table_end();

}

function common_row($emitter) {
    $values = array_slice(func_get_args(),1);
    $emitter->row_start();
    foreach ($values as $value) {
        $emitter->cell_block($value);
    }
    $emitter->row_end();
}

}

class test_emitter extends rsm_emitter_engine {

function __construct ($appGlobals, $form, $bodyStyle='',$exportType='h') {
    $this->appGlobals = $appGlobals;
    parent::__construct($form, $bodyStyle, $exportType);
   $this->gbKrn_add_cssFile('../rsm-styleSheet.css', "all", '');
   // $this->head_add_array($appGlobals->gb_cssFile_htmlCode);
}

function gbKrn_add_cssFile($cssPath, $media="all", $levelStr="") {
    $filename = __DIR__ . "/" . $levelStr . $cssPath;
	$timestamp = filemtime( $filename );
    $this->head_add_line( "<link rel='stylesheet' type='text/css' media='{$media}' href='{$levelStr}{$cssPath}?v={$timestamp}'>");
}

function krnEmit_webPageOutput( $chain, $scriptData, $form,  $appGlobals) {
    // declared abstract in rsm_emitter
	// This function emits the entire web page for all the kcm-systems
	//    so all kcm-systems should have a consistent look
    $this->zone_htmlHead();
    $this->zone_body_start($chain, $form);
        print PHP_EOL . PHP_EOL .'<div class="rsm-zone-banner">';
    print ' Test Chain Banner';
        print PHP_EOL . '</div>';
    print PHP_EOL ;

    $this->zone_messages($chain, $form);
    //$this->zone_menu();
    $this->emit_menu->drMenu_emit_menu($this);
	$form->rsmForm_emit_headers  ($chain, $scriptData, $this,$appGlobals);
	$form->rsmForm_emit_content ($chain, $scriptData, $this, $appGlobals);
	$form->rsmForm_emit_footer ($chain, $scriptData, $this, $appGlobals);
    $this->zone_body_end();
}

function krnEmit_webPageInit_Menu($chain,$scriptData, $appGlobals) {   // sort-of-required "abstract" function
    // declared abstract in rsm_emitter
    $argList = array_slice(func_get_args(),2);
    if ( (!empty($argList)) and (substr($argList[0],0,1)=='$') ) {
            $flag = $argList[0];
            $argList = array_slice($argList,1);
    }
    else {
        $flag = '';
    }
    $appGlobals->gb_initMenu($chain, $this->emit_menu, $flag);
    $argCount =  count($argList);
    if ( $argCount>=1) {
        $this->emit_menu->drMenu_markCurrentItem($argList[0]);
        for ( $i=1; $i<$argCount; ++$i) {
           $this->emit_menu->drMenu_markTopLevelItem($argList[$i]);
        }
    }
}

}

Class testGlobals {

function gb_forceLogin() {
}

}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appChain = new Draff_Chain( 'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmGateway_globals());
$appChain->chn_register_appData( new application_data());
$appGlobals->gb_forceLogin ();

$chain->chn_createAndRegister_formStep(1,'chainStep_testChain_page1');
$chain->chn_createAndRegister_formStep(2,'chainStep_testChain_page2');
$chain->chn_createAndRegister_formStep(3,'chainStep_testChain_page3');
$chain->chn_launch_currentForm(); // proceed to current step

exit;


?>