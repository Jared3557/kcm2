<?php

//--- roster-results-games.php ---

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
// include_once( 'roster-system-data-games.inc.php' );

//include_once( 'roster-results-include.inc.php' );


const FORM_CHESS_WINNER     = 1;
const FORM_CHESS_LOSER      = 2;
const FORM_CHESS_DRAW       = 3;
const FORM_BUGHOUSE_PLAYERS = 11;
const FORM_BUGHOUSE_GAMES   = 12;
const FORM_GAME_EDIT        = 21;
const FORM_GAME_HISTORY     = 41;
const FORM_GAME_CROSSTABLE  = 51;

//@@@@@@@@@@@@@@@@@@@@
//@  Step 1
//@@@@@@@@@@@@@@@@@@@@

class appForm_chess_winner extends kcmKernel_Draff_Form {  // specify winner

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    if ($this->appChain->chn_submit[0] == '@edit') {
        $appData->apd_chessWinner_submit( $appGlobals, $appChain, $appChain->chn_submit[1]);
        // coming from button to edit the previouselly saved record
        $appChain->chn_launch_continueChain(CHAINSTEP_IDX_GAME_EDIT);
        return;
    }

    $appChain->chn_form_savePostedData();

    if ($appChain->chn_submit[0] == '@draw') {
        $appChain->chn_launch_newChain(FORM_CHESS_DRAW);  // start appChain
        return;
    }
    if ($appChain->chn_submit[0] == '@history') {
        $appChain->chn_launch_newChain(FORM_GAME_HISTORY);  // start appChain
        return;
    }

    if ($appChain->chn_submit[0] == '@crosstable') {
        $appChain->chn_launch_newChain(FORM_GAME_CROSSTABLE);  // start appChain
        return;
    }


    if (  isset($appChain->chn_submit[1]) and is_numeric($appChain->chn_submit[1]) ) {
        $appChain->chn_status->ses_set('#winnerId',$appChain->chn_submit[1]);
        $appChain->chn_launch_newChain(FORM_CHESS_LOSER);  // start appChain
    }
    $appChain->chn_form_launch ();
    //$appChain->chn_launch_continueChain(FORM_CHESS_WINNER);

}

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $this->step_updateIfPosted('#winnerId',$appData->com_winnerId );
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//}


function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Enter '.$appData->apd_chess_gameTypeDesc.' Results');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize('$results', $appData->apd_chess_gameTypeMenuKey );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Enter '.$appData->apd_chess_gameTypeDesc.' Results');
 }

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    kcmRosterLib_kidList_buttons_define($this, $appGlobals, $appData->apd_roster_program,  'winnerId' );
    $this->drForm_addField( new Draff_Button( '@draw' , 'Report a draw' ) );
    $this->drForm_addField( new Draff_Button( '@history' , 'History' ) );
    $this->drForm_addField( new Draff_Button( '@crosstable' , 'Crosstable' ) );
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {  // appForm_chess_winner
    $controls = $this->drForm_gen_field('@draw');
    $appData->apd_chess_headerOutput($appChain,$appEmitter,$appGlobals, 'Won',$controls);
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) { // appForm_chess_winner
    $appEmitter->zone_start('draff-zone-content-select');
    kcmRosterLib_kidList_buttons_emit($appGlobals, $appData->apd_roster_program, $appEmitter, 'winnerId');
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-footer-select');
    $appEmitter->content_field('@history');
    $appEmitter->content_field('@crosstable');
    $appEmitter->zone_end();
}

}  // end class appForm_chess_winner

//@@@@@@@@@@@@@@@@@@@@
//@  Step 2
//@@@@@@@@@@@@@@@@@@@@

class appForm_chess_loser extends kcmKernel_Draff_Form {  // specify loser

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {

    kernel_processBannerSubmits( $appGlobals, $appChain );
    $appData->apd_formData_get( $appGlobals, $appChain );

    if ($appChain->chn_submit[0] == '@cancel') {
        $winner = kcmRosterLib_getKidName($appGlobals, $appData->apd_roster_program, $appData->apd_chessWinner_playerId);
        $message =  'Cancelled win for '.$winner; // no longer available
        $appChain->chn_launch_cancelChain(FORM_CHESS_WINNER,$message);
        return;
    }
     if ($appChain->chn_submit[0] == '@history') {
        $appChain->chn_launch_newChain(FORM_GAME_HISTORY);  // start appChain
        return;
    }

    if ($appChain->chn_submit[0] == '@crosstable') {
        $appChain->chn_launch_newChain(FORM_GAME_CROSSTABLE);  // start appChain
        return;
    }


    $appChain->chn_form_savePostedData();
    //$appChain->chn_ValidateAndRedirectIfError();

    if (  isset($appChain->chn_submit[1]) and is_numeric($appChain->chn_submit[1]) ) {
        $appData->apd_chessLoser_submit( $appGlobals, $appChain, $appChain->chn_submit[1]);
        $appChain->chn_launch_restartAfterRecordSave(FORM_CHESS_WINNER);  // history
   }

    $appChain->chn_launch_continueChain(FORM_CHESS_WINNER);  // no history

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Enter '.$appData->apd_chess_gameTypeDesc.' Results');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize('$results', $appData->apd_chess_gameTypeMenuKey );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Enter '.$appData->apd_chess_gameTypeDesc.' Results');
 }

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_chessLoser_getData( $appGlobals, $appChain );
    kcmRosterLib_kidList_buttons_define($this, $appGlobals, $appData->apd_roster_program,  'loserId',$appData->apd_chessWinner_playerId );
    $this->drForm_addField( new Draff_Button( '@draw' , 'Report a draw' ) );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel' ) );
    $this->drForm_addField( new Draff_Button( '@history' , 'History' ) );
    $this->drForm_addField( new Draff_Button( '@crosstable' , 'Crosstable' ) );
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {  // appForm_chess_loser
    $kidPeriod = $appData->apd_roster_program->rst_cur_period->perd_getKidPeriodObject($appData->apd_chessWinner_playerId);
    $winnerKid = $appData->apd_roster_program->rst_get_kid($kidPeriod->kidPer_kidId);
    $s2 = ' (<span class="font-weight-bigBold">' . $winnerKid->rstKid_uniqueName .'</span> won) ';
    $controls = $this->drForm_gen_field(array('@cancel')); // '@draw',
    $appData->apd_chess_headerOutput($appChain,$appEmitter,$appGlobals, 'Lost',$s2 . $controls);
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {  // appForm_chess_loser
    $appEmitter->zone_start('draff-zone-content-select');
    kcmRosterLib_kidList_buttons_emit($appGlobals, $appData->apd_roster_program, $appEmitter, 'loserId',$appData->apd_chessWinner_playerId);
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-footer-select');
    $appEmitter->content_field('@history');
    $appEmitter->content_field('@crosstable');
    $appEmitter->zone_end();
}

}  // end class appForm_chess_loser

//@@@@@@@@@@@@@@@@@@@@
//@  Step 3
//@@@@@@@@@@@@@@@@@@@@

class appForm_chess_draw extends kcmKernel_Draff_Form {  // specify both players of draw
private $draw_playerList;

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {

    kernel_processBannerSubmits( $appGlobals, $appChain );

    if ($appChain->chn_submit[0] == '@cancel') {
        $appChain->chn_message_set('Cancelled draw' . $msg);
        $appChain->chn_curStream_Clear();
        $message = '';
        $appChain->chn_launch_cancelChain(FORM_CHESS_WINNER,$message);
        return;
    }
    if ($appChain->chn_submit[0] == '@history') {
        $appChain->chn_launch_newChain(FORM_GAME_HISTORY);  // start appChain
        return;
    }

    if ($appChain->chn_submit[0] == '@crosstable') {
        $appChain->chn_launch_newChain(FORM_GAME_CROSSTABLE);  // start appChain
        return;
    }


    $appChain->chn_form_savePostedData();
    if ($submit == '@submit') {
        $isValid = $sdData->apd_chessDraw_submit( $appGlobals, $appChain);
        if (!$isValid) {
            $appChain->chn_ValidateAndRedirectIfError();
        }
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_restartAfterRecordSave(FORM_CHESS_WINNER);  // cancel appChain
    }

    $appChain->chn_launch_continueChain(FORM_CHESS_WINNER);  // cancel appChain
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Enter '.$appData->apd_chess_gameTypeDesc.' Results');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize('$results', $appData->apd_chess_gameTypeMenuKey );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Enter '.$appData->apd_chess_gameTypeDesc.' Results');
 }

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    kcmRosterLib_kidList_checkboxes_define($this, $appGlobals, $appData->apd_roster_program ); //????--with-draw-array
    $this->drForm_addField( new Draff_Button( '@submit' , 'Submit' ) );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Report a win/loss' ) );
    $this->drForm_addField( new Draff_Button( '@history' , 'History' ) );
    $this->drForm_addField( new Draff_Button( '@crosstable' , 'Crosstable' ) );
}

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $this->draw_playerList = kcmRosterLib_kidList_checkboxes_getChecked($appGlobals, $appData->apd_roster_program, $this);
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//    if ( count($this->draw_playerList) != 2) {
//        $appChain->chn_message_set('','You must select 2 players');
//        return;
//    }
//}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {  // appForm_chess_draw
    $appEmitter->zone_start('draff-zone-header-select');
    $appEmitter->emit_nrLine('');
    $appEmitter->div_start('kcmkrn-question-div');
    $appEmitter->emit_nrLine( '<span class="font-weight-bigBold">Select the two players that drew</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
    $appEmitter->content_field('@cancel');
    $appEmitter->div_end();
    $appEmitter->emit_nrLine('');
    $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {  // appForm_chess_draw
    $appEmitter->zone_start('draff-zone-content-select');
    kcmRosterLib_kidList_checkboxes_emit($appEmitter, $appGlobals, $appData->apd_roster_program, 'drawer');
    $appEmitter->div_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-footer-select');
    $appEmitter->div_start('kcmkrn-question-div');
    $appEmitter->content_field('@submit');
    $appEmitter->div_end();
    $appEmitter->content_field('@history');
    $appEmitter->content_field('@crosstable');
    $appEmitter->zone_end();
}

}  // end class appForm_chess_draw

class appForm_chess_edit extends kcmKernel_Draff_Form {

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );

    if ($appChain->chn_submit[0] == '@cancel') {
        // ??? would like more details as to which game is being cancelled
        $message = 'Cancelled';
        $appChain->chn_launch_cancelChain(FORM_CHESS_WINNER,$message);
        return;
    }
    $appChain->chn_form_savePostedData();
    if ($appChain->chn_submit[0] == '@delete') {
        $this->edit_gamegag_gameRecord_delete($appGlobals);
        $form->chn_theme-message-error-error_setStatus( kcmRosterLib_getDesc_gameType($appGlobals->gb_form->appChain->chn_posted_object->sesGame->game_gameType) . ' Game Deleted: ??? need better theme-message-error-error' );
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_continueChain(FORM_CHESS_WINNER);
        return;
    }
    if ($appChain->chn_submit[0] == '@save') {
        $appChain->chn_ValidateAndRedirectIfError();
        $atGameId = $appData->apd_chessEdit_game->gaMatch_save($appGlobals);
        $editUrl =  $appChain->chn_url_build_chained_url(NULL, TRUE, array('chRec'=>$atGameId,'chStep'=>11) );
        $s = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . $editUrl . '">Edit</a>';
        $savedGame = new stdData_gameMatch_group;
        $savedGame->gaMatch_read($appGlobals, $atGameId);
        $ss = $savedGame->gaMatch_getSavedString($appGlobals);
        $appChain->chn_message_set( $savedGame->gaMatch_getSavedString($appGlobals) . $s );
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_restartAfterRecordSave(FORM_CHESS_WINNER);
        return;
    }
    $appChain->chn_launch_continueChain(FORM_CHESS_WINNER);

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Enter '.$appData->apd_chess_gameTypeDesc.' Results');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize( '$results', $appData->apd_chess_gameTypeMenuKey );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Edit Game');
 }

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $gameEditId = $appChain->chn_data_posted_get('#editGameId');
    $source = $appChain->chn_data_posted_get('#editSource');
    $appData->apd_chessEdit_game =  new stdData_gameMatch_group;
 	$appData->apd_chessEdit_game->gaMatch_read($appGlobals, $gameEditId);
   $appData->apd_chessEdit_gameType =  array( '0' => 'Chess', '1' => 'Blitz');
    //???? or just bughouse depending on original game type
    $appData->apd_chessEdit_comboScore    = array(0=>'0', 1=>'1', 2=>'2', 3=>'3', 4=>'4', 5=>'5');
    $appData->apd_chessEdit_comboPlayer = array();
    $rst_cur_period = $appData->apd_roster_program->rst_cur_period;
    foreach ($rst_cur_period->perd_kidPeriodMap as $kidPeriod) {
        $appData->apd_chessEdit_comboPlayer[$kidPeriod->kidPer_kidPeriodId] = $kidPeriod->kidPer_kidObject->rstKid_uniqueName;
    }
    for ($i=0; $i<$appData->apd_chessEdit_game->game_opponents_count; ++$i) {
        $key = $i+1;
        $this->drForm_addField( new Draff_Combo('@kid_'.$key,  $appData->apd_chessEdit_game->game_opponents_kidPeriodId[$i], $appData->apd_chessEdit_comboPlayer ) );
        $this->drForm_addField( new Draff_Combo('@win_'.$key, $appData->apd_chessEdit_game->game_opponents_wins[$i]    , $appData->apd_chessEdit_comboScore ) );
        $this->drForm_addField( new Draff_Combo('@lost_'.$key, $appData->apd_chessEdit_game->game_opponents_losts[$i]   , $appData->apd_chessEdit_comboScore ) );
        $this->drForm_addField( new Draff_Combo('@draw_'.$key,  $appData->apd_chessEdit_game->game_opponents_draws[$i]   , $appData->apd_chessEdit_comboScore ) );
    }
    if ($appData->apd_chessEdit_game->game_gameType == ROSTER_BUGHOUSE) {
        $typeCombo = array(ROSTER_BUGHOUSE=>'Bughouse');
    }
    else {
        $typeCombo = array(ROSTER_CHESS=>'Chess',ROSTER_BLITZ=>'Blitz');
    }
    $this->drForm_addField( new Draff_Combo('@type', $appData->apd_chessEdit_game->game_gameType, $typeCombo ) );
    $this->drForm_addField( new Draff_Button( '@save','Save') );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
    $this->drForm_addField( new Draff_Button(  '@delete','Delete') );
}

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $atGameId =  $this->step_getShared('#gameId',NULL);
//    krnLib_assert($atGameId<>0,__FILE__,__LINE__);
//    $appData->apd_chessEdit_game = new stdData_gameMatch_group;
//    $appData->apd_chessEdit_game->gaMatch_read($appGlobals, $atGameId);
//    for ($i=0; $i<$appData->apd_chessEdit_game->game_opponents_count; ++$i) {
//        $key = $i+1;
//        $this->step_updateIfPosted('@kid_'.$key,  $appData->apd_chessEdit_game->game_opponents_kidPeriodId[$i]);
//        $this->step_updateIfPosted('@kid_'.$key,  $appData->apd_chessEdit_game->game_opponents_kidPeriodId[$i]);
//        $this->step_updateIfPosted('@win_'.$key,  $appData->apd_chessEdit_game->game_opponents_wins[$i]    );
//        $this->step_updateIfPosted('@lost_'.$key, $appData->apd_chessEdit_game->game_opponents_losts[$i]   );
//        $this->step_updateIfPosted('@draw_'.$key, $appData->apd_chessEdit_game->game_opponents_draws[$i]   );
//    }
//    if ($appData->apd_chessEdit_game->game_gameType != ROSTER_BUGHOUSE) {
//       $this->step_updateIfPosted('@type', $appData->apd_chessEdit_game->game_gameType );
//    }
//}
//
//function drForm_validate($appChain, $formData, $appGlobals) {
//    // need to check each player is only used one time
//    for ($i=0; $i<$appData->apd_chessEdit_game->game_opponents_count-1; ++$i) {
//        $key = '@kid_'.($i+1);
//        for ($j=i+1; $j<$appData->apd_chessEdit_game->game_opponents_count; ++$j) {
//            if ($appData->apd_chessEdit_game->game_opponents_kidPeriodId[$i] == $appData->apd_chessEdit_game->game_opponents_kidPeriodId[$j]) {
//                $appChain->chn_message_set($key,'Each player must be different');
//            }
//        }
//    }
//    for ($i=0; $i<$appData->apd_chessEdit_game->game_opponents_count-1; ++$i) {
//        if ( ($appData->apd_chessEdit_game->game_opponents_wins[$i]==0)
//            and ($appData->apd_chessEdit_game->game_opponents_losts[$i]==0)
//            and ($appData->apd_chessEdit_game->game_opponents_draws[$i]==0) ) {
//                $key = '@kid_'.($i+1);
//                $appChain->chn_message_set($key,'All players must have a result');
//            }
//    }
//return; // ??????
//    // need to check wins = game_losses and number of draws make sense (but allow work-around) - and maybe only one draw difference
//}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
v}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-select');
    $sesGame = $appData->apd_chessEdit_game;

    $gameType =  '???? '.$sesGame->game_gameType;//($sesGame->game_gameType==2) ? 'Bughouse' : $form->getHtml_combo('gameType', $appGlobals->gb_form->appChain->chn_posted_object->sesGame->game_gameType );
    $origin = kcmRosterLib_getDesc_originCode( $sesGame->game_originCode);

    $tableLayout = new rsmp_emitter_table_layout('sc', array(25,6,6,6));
    $appEmitter->table_start('draff-edit', $tableLayout);

    krnEmit_recordEditTitleRow($appEmitter,'Edit Game', 4);
    //appEmitter->row_start();
    //$appEmitter->cell_block('Edit Game','draff-edit-head','colspan="4"');
    //$appEmitter->row_end();

    $appEmitter->row_start();
    $appEmitter->cell_block('Origin','draff-edit-fieldDesc');
    switch ($appData->apd_chessEdit_game->game_originCode) {
        case GAME_ORIGIN_CLASS: $s='Class';  break;
        case GAME_ORIGIN_TALLY: $s = 'Tally'; break;
        default: $s = '????'; break;
    }
    $appEmitter->cell_block($s,'draff-edit-fieldData','colspan="3"');
    $appEmitter->row_end();

    $appEmitter->row_start();
    $appEmitter->cell_block('Game Type','draff-edit-fieldDesc');
    $appEmitter->cell_block($this->drForm_gen_field('@type'),'draff-edit-fieldData','colspan="3"');
    $appEmitter->row_end();

    $appEmitter->row_start();
    $appEmitter->cell_block('Player','draff-edit-head');
    $appEmitter->cell_block('Wins','draff-edit-head');
    $appEmitter->cell_block('Lost','draff-edit-head');
    $appEmitter->cell_block('Draws','draff-edit-head');
    $appEmitter->row_end();

    for ($i=0; $i<$appData->apd_chessEdit_game->game_opponents_count; ++$i) {
        $key = $i+1;
        $appEmitter->row_start();
        $appEmitter->cell_block($this->drForm_gen_field('@kid_'.$key),'draff-edit-fieldData');
        $appEmitter->cell_block($this->drForm_gen_field('@win_'.$key),'draff-edit-fieldData');
        $appEmitter->cell_block($this->drForm_gen_field('@lost_'.$key),'draff-edit-fieldData');
        $appEmitter->cell_block($this->drForm_gen_field('@draw_'.$key),'draff-edit-fieldData');
        $appEmitter->row_end();
    }

    $appEmitter->row_start();
    $appEmitter->cell_block('Other','draff-edit-head','colspan="4"');
    $appEmitter->row_end();

    $appEmitter->row_start();
    $appEmitter->cell_block('Class Date:','draff-edit-fieldDesc');
    $appEmitter->cell_block(draff_dateAsString( $appData->apd_chessEdit_game->game_classDate, 'M j'),'draff-edit-fieldData','colspan="3"');
    $appEmitter->row_end();

    $appEmitter->row_start();
    $appEmitter->cell_block('When Created:','draff-edit-fieldDesc');
    $appEmitter->cell_block(draff_dateTimeAsString( $appData->apd_chessEdit_game->game_whenCreated, 'M j, g:ia' ),'draff-edit-fieldData','colspan="3"');
    $appEmitter->row_end();

    $appEmitter->row_start();
    $appEmitter->cell_block('When Modified:','draff-edit-fieldDesc');
    $appEmitter->cell_block(draff_dateTimeAsString( $appData->apd_chessEdit_game->game_modWhen, 'M j, g:ia') ,'draff-edit-fieldData','colspan="3"');
    $appEmitter->row_end();

    $appEmitter->row_start();
    $appEmitter->cell_block($this->drForm_gen_field(['@save','@cancel','@delete']),'draff-edit-head','colspan="4"');
    $appEmitter->row_end();

    $appEmitter->table_end();

    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@
//@  Form
//@@@@@@@@@@@@@@@@@@@@

class appForm_bughouse_players extends kcmKernel_Draff_Form {

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    //$appChain->chn_data_posted_clearAll();
   // $appChain->chn_data_status_clearAll();
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
    // ??? process history, ct buttons, etc
    $appChain->chn_form_savePostedData();
    $isValid = $appData->apd_bugPlayers_submit( $appGlobals, $appChain);
    if ($isValid) {
        $appChain->chn_launch_forceChain(FORM_BUGHOUSE_GAMES);
    }
    else {
        $appChain->chn_launch_forceChain(FORM_BUGHOUSE_PLAYERS);
    }

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Bughouse');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize( '$results', 'resS_B' );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Bughouse');
  

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_bugPlayers_getPosted( $appGlobals, $appChain );
    $this->drForm_addField( new Draff_Combo( '@isCrazyHouse', ' Crazy House (Only 3 players played)' , $appData->apd_bugPlayers_isCrazy,'1','0') );
    $this->drForm_addField( new Draff_Button( '@next' , 'Next' ) );
    $this->drForm_addField( new Draff_Button( '@history' , 'History' ) );
    $this->drForm_addField( new Draff_Button( '@crosstable' , 'Crosstable' ) );
    $appData->apd_bugPlayers_getPosted( $appGlobals, $appChain );
    kcmRosterLib_kidList_checkboxes_define($this, $appGlobals, $appData->apd_roster_program, $appData->apd_bugPlayers_kidPeriodIdList);
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-header-select');
    $appEmitter->div_start('kcmkrn-question-div');
    $appEmitter->emit_nrLine( 'Select four players &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
    $appEmitter->content_field( '@isCrazyHouse');
    $appEmitter->div_end();
    $appEmitter->zone_end();

}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-content-select');
    $rst_cur_period = $appData->apd_roster_program->rst_cur_period;
    kcmRosterLib_kidList_checkboxes_emit($appEmitter, $appGlobals, $appData->apd_roster_program, 'gaBug');
    $appEmitter->zone_end();

}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-footer-select');
    $appEmitter->content_field('@next');
    $appEmitter->content_field('@history');
    $appEmitter->content_field('@crosstable');
    $appEmitter->zone_end();
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@
//@  Form
//@@@@@@@@@@@@@@@@@@@@

class appForm_bughouse_games extends kcmKernel_Draff_Form {

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );

    if ($appChain->chn_submit[0] == '@back') {
         $appChain->chn_launch_continueChain(FORM_BUGHOUSE_PLAYERS);
         return;
    }
    if ($appChain->chn_submit[0] == '@cancel') {
        $appChain->chn_launch_unchained(FORM_BUGHOUSE_PLAYERS,'');
        return;
    }
    $appChain->chn_form_savePostedData();
    $appData->apd_bugPlayers_getPosted( $appGlobals, $appChain );
    $appData->apd_bugGames_initialize( $appGlobals, $appChain );
    $appData->apd_bugGames_getSubmitted( $appGlobals, $appChain );
    //$appData->apd_formData_get( $appGlobals, $appChain );
    $appData->apd_bugGames_validate( $appGlobals, $appChain );
//    $appChain->chn_ValidateAndRedirectIfError();
    //if ($appChain->chn_submit[0] == '@submit') {
	//	$appData->apd_formData_get( $appGlobals, $appChain );
	//	$appData->apd_get_gameData( $appGlobals, $appChain );
    //    $appData->apd_save_start();
    //    $appData->apd_save_addResult($this->apd_norm_resultKey);
    //    $appData->apd_save_process( $appGlobals, $appChain );
    //    $appChain->chn_launch_continueChain(FORM_CHESS_WINNER);
    //    return;
    //}
    $appChain->chn_launch_continueChain(FORM_BUGHOUSE_PLAYERS);  //??????????????? for testing

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Enter '.$appData->apd_chess_gameTypeDesc.' Results');
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Bughouse');
   $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize($appChain, $appGlobals, '$results', 'resS_B');
    $appEmitter->emit_options->addOption_styleTag('div.lc-tables','max-width:95%;');
    $appEmitter->emit_options->addOption_styleTag('table.lc-table','margin: 10px;width:100%;margin-bottom:0;');
    $appEmitter->emit_options->addOption_styleTag('table.lc-tableCrazy','margin-top:0;');
    $appEmitter->emit_options->addOption_styleTag('td.lc-td','padding: 2px 10px;font-size: 1.4rem;line-height:1.7rem;vertical-align:middle;text-align:left;');
    $appEmitter->emit_options->addOption_styleTag('td.lc-td-vs','padding: 10px;font-size:1.5rem;vertical-align:middle;');
    $appEmitter->emit_options->addOption_styleTag('td.lc-td-left','border-right:0px;text-align:left;padding-left:4px;');
    $appEmitter->emit_options->addOption_styleTag('td.lc-td-middle','border-left:0px;border-right:0px;');
    $appEmitter->emit_options->addOption_styleTag('td.lc-td-right','text-align:left;border-left:0px;border-right:1px;');
    $appEmitter->vaddOption_styleTag('span.lc-name','font-size:1.0em;font-weight:bold;');
    $appEmitter->emit_options->addOption_styleTag('span.lc-result','font-size:1.0em;font-weight:bold;margin:0;padding:0;');
    $appEmitter->emit_options->addOption_styleTag('td.lc-td-4','border-left:0px;');
    $appEmitter->emit_options->addOption_styleTag('td.lc-td-unusual','border:1px; text-align:left;');
    $appEmitter->emit_options->addOption_styleTag('span.lc-and','display:inline-block; font-size:1.5em;font-weight:bold;padding:2px 10px;');
    $appEmitter->emit_options->addOption_styleTag('span.lc-radioText','display:inline-block;padding:2px 2px;');
    $appEmitter->emit_options->addOption_styleTag('span.lc-versus','display:inline-block;padding:2px 2px;font-size:1.2em;font-weight:bold;margin:1px 1px 4px 1px;');
    $appEmitter->emit_options->addOption_styleTag('span.lc-hint','display:inline-block;padding:2px 2px;font-size:0.7em;margin:1px 1px 4px 1px;width:30em;');
    $appEmitter->emit_options->addOption_styleTag('span.lc-block','display:inline-block;padding:2px 2px;text-align:left;');
    $appEmitter->emit_options->addOption_styleTag('span.lc-noneOfAbove','font-size:1.5em;font-weight:bold;');
	$appEmitter->emit_options->addOption_styleTag('td.lc-td-unusualLeft','width:30px;border-right:0px solid gray;');
    //$appEmitter->emit_options->addOption_styleTag('label','vertical-align:middle;');
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {

    //$appData->apd_formData_get( $appGlobals, $appChain );
    $appData->apd_bugPlayers_getPosted( $appGlobals, $appChain );
    $appData->apd_bugGames_initialize( $appGlobals, $appChain );
    $appData->apd_bugGames_getSubmitted( $appGlobals, $appChain );
	$this->drForm_addField( new Draff_RadioItem ('@groupNormal',   $appData->apd_bugGames_radioResults[0], 0);
	$this->drForm_addField( new Draff_RadioItem ('@groupUnusual1', $appData->apd_bugGames_radioResults[1], 0);
	$this->drForm_addField( new Draff_RadioItem ('@groupUnusual2', $appData->apd_bugGames_radioResults[2], 0);
	$this->drForm_addField( new Draff_RadioItem ('@groupUnusual3', $appData->apd_bugGames_radioResults[3], 0);
    if ($appData->apd_bugPlayers_count==4) {
		for ($i=1; $i<=8; ++$i) {
			$game = $appData->apd_bugGames_normalGames[$i];
			$left = $appData->apd_resultHtml($game->bg_left);
			$this->drForm_addField( new Draff_RadioItem ('@resultsNormal_'.($i),$appData->apd_bugGames_radioResults[0], '@groupNormal',$left,$i ));
		}
	}
    $s = '<span class="lc-radioText">'.emit_span('None of the above','lc-noneOfAbove') . ' - Enter unusual results below</span>';
    $this->drForm_addField( new Draff_RadioItem ('@resultsNormal_9', $appData->apd_bugGames_radioResults[0], '@groupNormal',$s,9 ) );
    for ($i=1; $i<=3; ++$i) {
		$j = $i*3-3;
		$gameLeft = $appData->apd_bugGames_unusualGames[$j+1];
		$left = $appData->apd_resultHtml($gameLeft->bg_left);
		$gameRight = $appData->apd_bugGames_unusualGames[$j+2];
		$right = $appData->apd_resultHtml($gameRight->bg_right);
        $s2 = '<span class="lc-radioText">'.'Did not play</span>';
        $this->drForm_addField( new Draff_RadioGroup ('@result'.$i.'g', 'kkk', '' ));
        $this->drForm_addField( new Draff_RadioItem ('@resultUnusual_'.$i.'_1',  $appData->apd_bugGames_radioResults[1], '@groupUnusual'.$i,$left,$j+1 ) );
        $this->drForm_addField( new Draff_RadioItem ('@resultUnusual_'.$i.'_2',  $appData->apd_bugGames_radioResults[3], '@groupUnusual'.$i,$right,$j+2 ) );
        $this->drForm_addField( new Draff_RadioItem ('@resultUnusual_'.$i.'_3',  $appData->apd_bugGames_radioResults[2], '@groupUnusual'.$i,$s2,$j+3) ) );
    }

    $this->drForm_addField( new Draff_Button( '@submit','Submit') );

    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
    $this->drForm_addField( new Draff_Button( '@back', 'Back' ));
}


function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-header-edit');
    $appEmitter->content_block( 'Enter Bughouse Result');
    $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
     $appEmitter->zone_start('zone-content-scrollable theme-select');

    $appEmitter->div_start('lc-tables');
	if (!$appData->apd_bugPlayers_isCrazy) {
        $appEmitter->table_start('lc-table',3);
		for ($i=1; $i<=8; ++$i) {
			$game = $appData->apd_bugGames_normalGames[$i];
			$right = $appData->apd_resultHtml($game->bg_right);
			$appEmitter->row_start();
			$appEmitter->cell_block (  $this->drForm_gen_field( '@resultsNormal_' . ($i)),'lc-td lc-td-left' );
			$appEmitter->cell_block ( emit_span('AND','lc-and'),'lc-td lc-td-vs lc-td-middle');
			$appEmitter->cell_block ( $right,"lc-td lc-td-right");
			$appEmitter->row_end();
		}
        $appEmitter->table_end();
	}


    $appEmitter->table_start('lc-table lc-tableCrazy',5);
	$appEmitter->row_start();
	if ($appData->apd_bugPlayers_isCrazy) {
		$appEmitter->cell_block (  'Enter Crazy House Results','lc-td-unusual', 'colspan="5"' );
	}
	else {
		$appEmitter->cell_block (  $this->drForm_gen_field( '@resultsNormal_9'),'lc-td','colspan="5"');
	}
	$appEmitter->row_end();
    for ($i=1; $i<=3; ++$i) {
        $appEmitter->row_start();
        $appEmitter->cell_block ( '', "lc-td lc-td-unusualLeft");
        $appEmitter->cell_block ( $this->drForm_gen_field( '@resultUnusual_'.$i.'_1'), "lc-td lc-td-middle");
        $appEmitter->cell_block ( emit_span( 'VS' , 'lc-and'), 'lc-td lc-td-vs lc-td-middle');
        $appEmitter->cell_block ( $this->drForm_gen_field( '@resultUnusual_'.$i.'_2'), "lc-td lc-td-middle");
        $appEmitter->cell_block ( $this->drForm_gen_field( '@resultUnusual_'.$i.'_3').'</span>', "lc-td lc-td-right");
        $appEmitter->row_end();
    }
	$appEmitter->table_end();

    $appEmitter->div_end();

    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-footer-edit');
	$appEmitter->content_block ( array('@submit','@back','@cancel'));
    $appEmitter->zone_end();
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@
//@
//@@@@@@@@@@@@@@@@@@@@

class appForm_game_history  extends kcmKernel_Draff_Form {  // specify winner

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );

    $appChain->chn_form_savePostedData();
    if ($appChain->chn_submit[0] == 'edit') {
        $appChain->chn_data_posted_set('#editGameId',$appChain->chn_submit[1]);
        $appChain->chn_data_posted_set('#editSource',$appChain->chn_submit[2]);
        $appChain->chn_launch_newChain(FORM_GAME_EDIT);  // start appChain
        return;
    }

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Enter '.$appData->apd_chess_gameTypeDesc.' Results');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize( '$results', $appData->apd_chess_gameTypeMenuKey );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Game History Report');
    $appData->apd_gameHistory_report = new report_gameHistory;
    $appData->apd_gameHistory_report->stdRpt_initStyles($appEmitter);
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_hist_gameBundle = new stdData_gameMatch_batch();
    $appData->apd_hist_gameBundle->gagBu_filterKidPeriodId = $appData->apd_hist_kidFilter;
    $appData->apd_hist_gameBundle->gagBu_filterClassDate = $appData->apd_hist_dateFilter;
    $appData->apd_hist_gameBundle->gagBu_filterGameType = $appData->apd_hist_gameTypeFilter;
    $appData->apd_hist_gameBundle->gagMatchBundle_read($appData->apd_roster_program, $appGlobals);
    //$sesGame = $appData->sesGame;
    //$sesGame->gaMatch_clear($appGlobals,NULL);  //???????????????????
    //foreach ($appData->apd_hist_gameBundle->gagBu_list as $game) {
    //    $fieldId = '@gameId_' . $game->game_atGameId;
    //    $form->drForm_define_linkButton($fieldId,'Edit');
    //}
    $this->drForm_addField( new Draff_Combo( '@kidCombo' ,  $appData->apd_hist_kidFilter, $appData->apd_hist_list_kid_array) );
    $this->drForm_addField( new Draff_Combo( '@dateCombo' ,  $appData->apd_hist_dateFilter, $appData->apd_hist_list_date_array) );
    $this->drForm_addField( new Draff_Combo( '@gameTypeCombo' ,  $appData->apd_hist_gameTypeFilter, $appData->apd_hist_list_gameType_array) );
    $this->drForm_addField( new Draff_Button( '@back' , 'Back' ) );

}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
  //  $appEmitter->zone_start('draff-zone-header-default');
   //$appEmitter->emit_nrLine('<div class="frame-report-header rpt-hdr">');
  //  $appEmitter->zone_end();
    $appEmitter->zone_start('draff-zone-filters-default');
    $this->outFilterOptions($appGlobals);
      $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-content-report');
    $appData->apd_gameHistory_report->stdRpt_output($appData, $appEmitter,$appGlobals);
    //$this->outReport($appChain,$appData, $appEmitter, $form,$appGlobals);
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-footer-select');
    $appEmitter->content_field('@back');
    $appEmitter->zone_end();
}

function outFilterOptions($appGlobals) {
    //kcmKernel_emitter::kcm_filter_divStart('Game History Filters');
    //kcmKernel_emitter::kcm_filter_control('Kids: ',$form->getHtml_combo( 'kidCombo', $appData->apd_hist_kidFilter ));
    //kcmKernel_emitter::kcm_filter_control('Class Date: ', $form->getHtml_combo( 'dateCombo', $appData->apd_hist_dateFilter ) );
    //kcmKernel_emitter::kcm_filter_control('Game Type: ', $form->getHtml_combo( 'gameTypeCombo', $appData->apd_hist_gameTypeFilter ));
    //kcmKernel_emitter::kcm_filter_divEnd($form->getHtml_Button('Update'));

}

}  // end class

//@@@@@@@@@@@@@@@@@@@@
//@  Form
//@@@@@@@@@@@@@@@@@@@@

class appForm_crossTable extends kcmKernel_Draff_Form {  // specify winner

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {

    kernel_processBannerSubmits( $appGlobals, $appChain );
    if ($appChain->chn_submit[0] == '@back') {
        $message = '';
        $appChain->chn_launch_cancelChain(FORM_CHESS_WINNER);
        return;
    }
    $appChain->chn_form_savePostedData();
    if ($appChain->chn_submit[0] == 'edit') {
        $appChain->chn_data_posted_set('#editGameId',$appChain->chn_submit[1]);
        $appChain->chn_data_posted_set('#editSource',$appChain->chn_submit[2]);
        $appChain->chn_launch_newChain(FORM_GAME_EDIT);  // start appChain
        return;
    }
    //$appChain->chn_form_launch ();
    //$appChain->chn_launch_continueChain(FORM_CHESS_WINNER);

}


function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Enter '.$appData->apd_chess_gameTypeDesc.' Results');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize( '$results', $appData->apd_chess_gameTypeMenuKey );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,$appData->apd_chess_gameTypeDesc.' CrossTable');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_css.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_screen.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-common_print.css','all','../');
    $appEmitter->emit_options->addOption_styleFile('kcm-roster/kcm1css/kcm-gameEntry.css','all','../');
    $appEmitter->emit_options->addOption_styleTag('button.lc-edit','padding:2pt; border-radius:0;border:1px solid #aaf;background-color:#eef');
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $this->drForm_addField( new Draff_Button( '@back' , 'Back' ) );
}


function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {  // appForm_chess_winner
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) { // appForm_chess_winner
   // $crossTable = new report_crosstable;
    $appEmitter->zone_start('draff-zone-content-report');
    $ct = new report_cross_table;
    $ct->ctr_print($appData, $appGlobals, $appEmitter, NULL, $appData->apd_chess_gameTypeCode);
   // $crossTable->ctr_print($appGlobals, $appEmitter, $appData->apd_roster_program->rst_program->prog_programId, $appData->apd_roster_program->rst_cur_period->perd_periodId, $appData->apd_chess_gameTypeCode, 'h');
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-footer-select');
    $appEmitter->content_field('@back');
    $appEmitter->zone_end();
}

}  // end class

class application_data extends draff_appData {

public $apd_roster_program;
public $apd_roster_period;

// public $apd_backForm = NULL;  // form back key goes to (from history, edit, crosstable)
public $apd_bugPlayers_kidPeriodIdList  = array();
public $apd_bugPlayers_nameList = array();
public $apd_bugPlayers_count    = 0;
public $apd_bugPlayers_isCrazy  = FALSE;  //????? code or boolean ?????
public $apd_bugPlayers_isValid  = FALSE;  //????? code or boolean ?????

public $apd_bugGames_normalGames  = array();  // list of possible normal game results
public $apd_bugGames_unusualGames = array();  // list of possible unusual game results
public $apd_bugGames_radioResults = array(0,0,0,0); // 1 for normal result and 3 for the three unusual result games
public $apd_bugGames_validated    = array(); // games validated as worthy of saving

public $apd_save_player;  //????? should be eliminated

public $apd_chessWinner_playerId = NULL;
public $apd_chessLoser_playerId = NULL;
public $apd_chess_gameTypeCode;
public $apd_chess_gameTypeDesc = '?';
public $apd_chess_gameTypeMenuKey = '?';
public $apd_chessEdit_game;
public $apd_chessEdit_gameType;
public $apd_chessEdit_comboScore;
public $apd_chessEdit_comboPlayer;
public $apd_gameHistory_report;
public $apd_hist_kidFilter = '@all';
public $apd_hist_dateFilter = '@all';
public $apd_hist_gameTypeFilter = '@all';
public $apd_hist_gameBundle = NULL;
public $apd_hist_list_kid_array;
public $apd_hist_list_gameType_array;
public $apd_hist_list_date_array;


function __construct($appGlobals) {
    $this->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
    $this->apd_roster_program->rst_load_kids($appGlobals);  //???? or later ????
    $this->apd_roster_period  = $this->apd_roster_program->rst_get_period();
}

function apd_all_getData($appChain) {
    $this->apd_chess_gameTypeCode = draff_urlArg_getRequired('drfMode',NULL);
    $this->apd_chess_gameTypeCode = $this->apd_chess_gameTypeCode - 1;
    if ($this->apd_chess_gameTypeCode==0) {
       $this->apd_chess_gameTypeDesc = 'Chess';
       $this->apd_chess_gameTypeMenuKey = 'resS_C';
    }
    else if ($this->apd_chess_gameTypeCode==1) {
       $this->apd_chess_gameTypeDesc = 'Blitz';
       $this->apd_chess_gameTypeMenuKey = 'resS_S';
    }
}

function  apd_chessWinner_getData( $appGlobals, $appChain) {
   $this->apd_chessWinner_playerId = $this->status->get('#winnerId',NULL);
}

function  apd_chessWinner_submit( $appGlobals, $appChain, $winnerKidPeriodId ) {
    $this->step_setShared('#gameId',$winnerKidPeriodId);
}

function  apd_chessLoser_getData( $appGlobals, $appChain ) {
   $this->apd_chessWinner_playerId = $this->status->get('#winnerId',NULL);
   $this->apd_chessLoser_playerId  = $this->status->get('#loserId',NULL);
}

function  apd_chessLoser_submit( $appGlobals, $appChain, $loserKidPeriodId) {
    $this->status->set('#loserId',$appChain->chn_submit[1]);  //??????
    $this->apd_chessLoser_getData( $appGlobals, $appChain );
    $gameMatch = new stdData_gameMatch_group;
    $gameMatch->gaMatch_clear($appGlobals,$this->apd_chess_gameTypeCode);
    $gameMatch->gaMatch_addPlayerResult($this->apd_chessWinner_playerId ,1,0,0);
    $gameMatch->gaMatch_addPlayerResult($this->apd_chessLoser_playerId ,0,0,1);
    $this->apd_gameMatch_save( $appGlobals, $appChain, $gameMatch);
    return;
}

function  apd_chessDraw_getData( $appGlobals, $appChain) {
}

function  apd_chessDraw_submit( $appGlobals, $appChain) {
    $this->draw_playerList = kcmRosterLib_kidList_checkboxes_getChecked($appGlobals, $appData->apd_roster_program, $this);
    if ( count($this->draw_playerList) != 2 ) {
        $appChain->chn_message_set('There must be two players for a draw');
        return FALSE;
    }
    $gameMatch = new stdData_gameMatch_group;
    $gameMatch->gaMatch_clear($appGlobals,$appData->apd_chess_gameTypeCode);
    $gameMatch->gaMatch_addPlayerResult($this->draw_playerList[0] ,0,1,0);
    $gameMatch->gaMatch_addPlayerResult($this->draw_playerList[1] ,0,1,0);
    $appData->apd_gameMatch_save( $appGlobals, $appChain, $gameMatch);
    return TRUE;
}

function  apd_bugPlayers_getData( $appGlobals, $appChain) {
}

function apd_bugPlayers_getPosted( $appGlobals, $appChain ) {
    $this->apd_bugPlayers_count = $appChain->chn_data_posted_get('#bugPlayerCount','0');

    if (empty($this->apd_bugPlayers_count)) {
        return;
    }
    $this->apd_bugPlayers_isCrazy = $appChain->chn_data_posted_get('#bugPlayerCrazy','0');
	$this->apd_bugPlayers_isCrazy = empty($this->apd_bugPlayers_isCrazy) ? 0 : 1;
    for ($i=0; $i <$this->apd_bugPlayers_count; ++$i) {
        $this->apd_bugPlayers_kidPeriodIdList[]   = $appChain->chn_data_posted_get('#bugPlayerId_'.$i);
        $this->apd_bugPlayers_nameList[]  = $appChain->chn_data_posted_get('#bugPlayerName_'.$i);
    }
}

function  apd_bugPlayers_submit( $appGlobals, $appChain) {
    //$roster_program = $this->apd_roster_program;
    $this->apd_bugPlayers_nameList = array();
    $this->apd_bugPlayers_isCrazy = $appChain->chn_data_posted_get('@isCrazyHouse','0');
    $this->apd_bugPlayers_kidPeriodIdList = kcmRosterLib_kidList_checkboxes_getChecked($appGlobals, $this->apd_roster_program, $appChain);
    $this->apd_bugPlayers_count = count($this->apd_bugPlayers_kidPeriodIdList);
    for ($i=0; $i<$this->apd_bugPlayers_count; ++$i) {
        $kidPeriodId = $this->apd_bugPlayers_kidPeriodIdList[$i];
        $kidPeriod = $this->apd_roster_period->perd_get_kidPeriod($kidPeriodId);
        $kid = $this->apd_roster_program->rst_get_kid($kidPeriod->kidPer_kidId);
        $kidName = $kid->rstKid_uniqueName;
        $this->apd_bugPlayers_nameList[] = $kidName;
        $appChain->chn_data_posted_set('#bugPlayerId_'.$i,$kidPeriodId);
        $appChain->chn_data_posted_set('#bugPlayerName_'.$i,$kidName);
    }
    $appChain->chn_data_posted_set('#bugPlayerCount',$this->apd_bugPlayers_count);
    $appChain->chn_data_posted_set('#bugPlayerCrazy',$kidCount==3);
    return $this->apd_bugPlayers_validate( $appGlobals, $appChain );
}

function apd_bugPlayers_validate( $appGlobals, $appChain ) {
    if ($this->apd_bugPlayers_isCrazy==1) {
        if ($this->apd_bugPlayers_count!=3) {
            $appChain->chn_message_set('There must be three players for Crazy House');
            $this->apd_bugPlayers_isValid = FALSE;
            return FALSE;
       }
    }
    else {
        if ($this->apd_bugPlayers_count!=4) {
            $appChain->chn_message_set('There must be four players for Bughouse');
            $this->apd_bugPlayers_isValid = FALSE;
            return FALSE;
        }
    }
    $this->apd_bugPlayers_isValid = TRUE;
    return TRUE;
}

function apd_bugPlayers_setPosted( $appGlobals, $appChain ) {
}

function  apd_bugGames_getData( $appGlobals, $appChain) {
}

function  apd_bugGames_submit( $appGlobals, $appChain) {
}

function apd_bugGames_initialize( $appGlobals, $appChain ) {
    $this->apd_norm_keys = array();
    $this->apd_norm_left_names = array();
    $this->apd_norm_right_names = array();
    $this->apd_norm_result_desc1 = array();
    $this->apd_norm_result_desc2 = array();
	$this->apd_bugGames_normalGames[] = NULL;
	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(1),3,0,array(2,3,4),1,2);
	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(2),3,0,array(1,3,4),1,2);
	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(3),3,0,array(1,2,4),1,2);
	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(4),3,0,array(1,2,3),1,2);
	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(1),0,3,array(2,3,4),2,1);
	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(2),0,3,array(1,3,4),2,1);
	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(3),0,3,array(1,2,4),2,1);
	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(4),0,3,array(1,2,3),2,1);
	$this->apd_bugGames_unusualGames[] = NULL;
	if ($this->apd_bugPlayers_isCrazy==0) {
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,2),1,0,array(3,4),0,1);
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,2),0,1,array(3,4),1,0);
	    $this->apd_bugGames_unusualGames[] = NULL;
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,3),1,0,array(2,4),0,1);
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,3),0,1,array(2,4),1,0);
	    $this->apd_bugGames_unusualGames[] = NULL;
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,4),1,0,array(2,3),0,1);
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,4),0,1,array(2,3),1,0);
	    $this->apd_bugGames_unusualGames[] = NULL;
	}
	else {
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1),1,0,array(2,3),0,1);
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1),0,1,array(2,3),1,0);
	    $this->apd_bugGames_unusualGames[] = NULL;
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(2),1,0,array(1,3),0,1);
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(2),0,1,array(1,3),1,0);
	    $this->apd_bugGames_unusualGames[] = NULL;
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(3),1,0,array(1,2),0,1);
	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(3),0,1,array(1,2),1,0);
	    $this->apd_bugGames_unusualGames[] = NULL;
	}
	$this->apd_bugGames_radioResults[0] = $appChain->chn_data_posted_get('@groupNormal');
	$this->apd_bugGames_radioResults[1] = $appChain->chn_data_posted_get('@groupUnusual1');
	$this->apd_bugGames_radioResults[2] = $appChain->chn_data_posted_get('@groupUnusual2');
	$this->apd_bugGames_radioResults[3] = $appChain->chn_data_posted_get('@groupUnusual3');
}

function apd_bugGames_getSubmitted( $appGlobals, $appChain ) {
    $appChain->chn_posted_read('@groupNormal',$this->apd_bugGames_radioResults[0]);
    $appChain->chn_posted_read('@groupUnusual1',$this->apd_bugGames_radioResults[1]);
    $appChain->chn_posted_read('@groupUnusual2',$this->apd_bugGames_radioResults[2]);
    $appChain->chn_posted_read('@groupUnusual3',$this->apd_bugGames_radioResults[3]);
}

function apd_bugGames_validate( $appGlobals, $appChain ) {
    $this->apd_bugGames_validated = array();
    if (empty($this->apd_bugGames_radioResults[0])) {
        $appChain->chn_message_set('You must select a result');
    }
    if ($this->apd_bugGames_radioResults[0]!=9) {
        $gameIndex = $this->apd_bugGames_radioResults[0];
        $this->apd_bugGames_validated[] = $this->apd_bugGames_normalGames[$gameIndex];
        $this->apd_bugGames_save( $appGlobals, $appChain );
        return;
    }
    $havegame = FALSE;
    for ($i=1; $i<=3; ++$i) {
        if (empty($this->apd_bugGames_radioResults[$i])) {
            $appChain->chn_message_set('You must select a result (or unplayed) for all three games');
            return;
        }
        $gameIndex = $this->apd_bugGames_radioResults[$i];
        if ($this->apd_bugGames_unusualGames[$gameIndex]!==NULL) {
            $havegame = TRUE;
            $this->apd_bugGames_validated[] = $this->apd_bugGames_unusualGames[$gameIndex];
        }
    }
    if (!$havegame) {
        $this->apd_bugGames_validated = array();
        $appChain->chn_message_set('You must select a result at least one game');
    }
 if (!empty($this->apd_bugGames_validated)) {
   $this->apd_bugGames_save( $appGlobals, $appChain );
 }
}

function apd_bugGames_save( $appGlobals, $appChain ) {
    $partners = array();
    foreach ($this->apd_bugGames_validated as $bugGame) {
        $partners[] = $bugGame->bg_left;
        $partners[] = $bugGame->bg_right;
    }
    $player_id   = array();
    $player_win  = array();
    $player_lost = array();
    foreach ($partners as $partnership) {
        $count = count($partnership->bp_ids);
        for ($i=0; $i<$count; ++$i) {
            $playerName = $partnership->bp_names[$i];
            $playerId = $partnership->bp_ids[$i];
            $win      = $partnership->bp_wins;
            $lost     = $partnership->bp_loses;
            if (isset($player_id[$playerId])) {
                $player_id[$playerId]   = $playerId;
                $player_win[$playerId]  += $win;
                $player_lost[$playerId] += $lost;
            }
            else {
                $player_id[$playerId]   = $playerId;
                $player_win[$playerId]  = $win;
                $player_lost[$playerId] = $lost;
            }
        }
    }
    $game = new stdData_gameMatch_group;
    $game->gaMatch_clear($appGlobals,ROSTER_BUGHOUSE);
    foreach ($player_id as $playerId => $value) {
        $win = $player_win[$playerId];
        $lost = $player_lost[$playerId];
        $game->gaMatch_addPlayerResult($playerId,$win ,0,$lost);
    }
    $atGameId = $game->gaMatch_save($appGlobals);
    //$editUrl =  $appChain->chn_url_build_chained_url(NULL,TRUE,array('chRec'=>$atGameId,'chStep'=>11) );
    //$s = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . $editUrl . '">Edit</a>';
    $savedGame = new stdData_gameMatch_group;
    $savedGame->gaMatch_read($appGlobals, $atGameId);
    $saveDesc= $savedGame->gaMatch_getSavedString($appGlobals);
    $class='';
    $value = '@bugEdit_' . $atGameId;
    $button =  '<button type="submit" '. $class . ' name="submit" value="'.$value.'">BugEdit</button>';
    $appChain->chn_message_set( $saveDesc . ' ' . $button);
    $appChain->chn_launch_newChain(FORM_CHESS_WINNER);
}

function  apd_gameHistory_getData( $appGlobals, $appChain) {
}

function  apd_gameHistory_submit( $appGlobals, $appChain) {
}

function  apd_crossTable_getData( $appGlobals, $appChain) {
}

function  apd_crossTable_submit( $appGlobals, $appChain) {
}

function apd_formData_get( $appGlobals, $appChain ) {
   $this->apd_chessWinner_playerId = $this->status->get('#winnerId',NULL);
   $this->apd_chessLoser_playerId  = $this->status->get('#loserId',NULL);
   $this->apd_bugGames_normalGames = array();
}

function apd_formData_validate( $appGlobals, $appChain ) {
}


function apd_chess_headerOutput($appChain, $appEmitter,$appGlobals, $resultDesc, $statusDesc) {
    $appEmitter->zone_start('draff-zone-header-select');
    $appEmitter->div_start('kcmkrn-question-div');
    $appEmitter->emit_nrLine( '<span class="font-weight-big">Who <span class="font-weight-bigBold">'.$resultDesc.'</span> the <span class="font-weight-bigBold">'.kcmRosterLib_getDesc_gameType($this->apd_chess_gameTypeCode).'</span> Game?</span>&nbsp;&nbsp;'.$statusDesc);
    $appEmitter->div_end();
    $appEmitter->zone_end();
}

function apd_gameMatch_save( $appGlobals, $appChain, $game) {
    $atGameId = $game->gaMatch_save($appGlobals);
    $savedGameMatch = new stdData_gameMatch_group;
    $savedGameMatch->gaMatch_read($appGlobals, $atGameId);
    $ss = $savedGameMatch->gaMatch_getSavedString($appGlobals);
    $value = '@edit_' . $atGameId;
    $button =  '<button type="submit" name="submit" value="'.$value.'">Edit</button>';
    $appChain->chn_message_set( $savedGameMatch->gaMatch_getSavedString($appGlobals) . $button);
}

function apd_addPartners($partnership, $partners,$win, $lost) {
	foreach ($partners as $index) {
		if (isset($this->apd_bugPlayers_kidPeriodIdList[$index-1])) {
	       $partnership->bp_ids[]    = $this->apd_bugPlayers_kidPeriodIdList[$index-1];
	       $partnership->bp_names[]  = $this->apd_bugPlayers_nameList[$index-1];
		}
//		else {
//	       $partnership->bp_ids[]    = $this->apd_bugPlayers_kidPeriodIdList[$index-1];
//	       $partnership->bp_names[]  = $this->apd_bugPlayers_nameList[$index-1];
//		}
	}
	$partnership->bp_wins = $win;
	$partnership->bp_loses = $lost;
}

function apd_addGame($mode, $partners1,$win1, $lost1, $partners2,$win2, $lost2) {
		$bughouseGame = new bug_game;
		$bughouseGame->bg_mode = $mode;
	    $this->apd_addPartners($bughouseGame->bg_left,$partners1,$win1, $lost1);
	    $this->apd_addPartners($bughouseGame->bg_right,$partners2,$win2, $lost2);
		return $bughouseGame;
}

function apd_save_start() {
    $this->apd_save_player = array();
    //$this->apd_save_win    = array();
    //$this->apd_save_lost   = array();
}

function apd_save_addResult($result) {
    $r = explode('_',$result);
    $rc = count($r);
    if ($rc < 3) {
        return;
    }
    for ($i=0; $i<$rc; $i=$i+3) {
        $player = $r[$i];
        $wins   = $r[$i+1];
        $lost   = $r[$i+2];
        $pos = array_search($player,$this->apd_save_player);
        if ($pos===FALSE) {
            $this->apd_save_player[] = $player;
            $this->apd_save_wins[] = $wins;
            $this->apd_save_lost[]  = $lost;
        }
        else {
            $this->apd_save_wins[$pos] += $wins;
            $this->apd_save_lost[$pos] += $lost;
        }
    }
}

function apd_resultHtml($partners) {
    $nameArray = array();
	foreach ($partners->bp_names  as $name) {
		$nameArray[] = emit_span($name,'lc-name');
	}
	switch (count($nameArray)) {
		case 3: $names = implode(' and ', $nameArray); break;
		case 2: $names = 'Partners ' . implode(' and ', $nameArray); break;
		case 1: $names = $nameArray[0]; break;
	}
	if ($partners->bp_wins==3) {
		$result = 'Won all 3 games';
	}
	else if ($partners->bp_loses==3) {
		$result = 'Lost all 3 games';
	}
	else if ($partners->bp_wins==2) {
		$result = 'Won 2 games and lost 1 game';
	}
	else if ($partners->bp_loses==2) {
		$result = 'Won 1 game and lost 2 games';
	}
	else if ($partners->bp_wins==1) {
		$result = 'Won Game';
	}
	else if ($partners->bp_loses==1) {
		$result = 'Lost Game';
	}
	else {
		$result = '???';
	}
	return $names . ' - ' . $result;
//	return emit_span($names . ' ' . $result,'lc-block');;
}

}

class report_cross_table {
//public $ctr_programObject;
public $ctr_exportType;
public $ctr_isExport;
public $ctr_pageCount;
public $ctr_roster;
public $ctr_gameGroup;
public $ctr_gameType;
public $ctr_rowNumber = array();
//public $ctr_page;
//public $ctr_argFormat;


function __construct () {
    //$this->ctr_programObject  = $programId;
}

function ctr_print($appData, $appGlobals, $appEmitter, $periodId, $gameTypeIndex) {
    $roster = $appData->apd_roster_program;
    $roster->perd_load_gameBatch($appGlobals, NULL, NULL, $gameTypeIndex,NULL,'a');

    //$this->ctr_exportType = $exportCode;
    //$this->ctr_isExport   = ( ($this->ctr_exportType=='p') or ($this->ctr_exportType=='e'));
    //
    //$this->ctr_gameType = $gameTypeIndex;
    //$db = $appGlobals->gb_db;

    //$db = new rc_database();
    //$this->ctr_roster = new kcm_roster($programId);
    //$this->ctr_roster->load_roster_headerAndKids();
    //$this->ctr_roster->sort_periodFilter(0);
    //$this->ctr_roster->sort_start();
    //$this->ctr_roster->sort_byPeriodCurrent('c');
    //$this->ctr_roster->sort_byFirstName();
    //$this->ctr_roster->sort_end();


   // $section = new kcm_crosstable_section($db, $this->ctr_roster, $periodId, $gameTypeIndex);
   // $section->cts_loadThis_section();
    // $geEngine->processPage($kcmState,$this->ctr_roster, $section, $db);

    // added jpr - not in original code here
    ////*************
    ////* Read Data *
    ////*************
    //$db = new rc_database();
    //
    //$this->ctr_roster = new kcm_roster($this->ctr_programObject->prog_programId);
    //$this->ctr_roster->load_roster_headerAndKids();
    //
    //$mainTitle = 'Name Labels<br>'.$this->ctr_roster->program->getNameLong($this->ctr_roster);

    $kcmState = NULL;
    $appEmitter->table_body_start('');

    //$page->frmStart('get','crstbl','kcm-game_entry.php','kcGuiDataForm');
    //if (! $this->ctr_isExport) {
    //    $page->frmStart('get','crstbl','kcm-game_entry.php','kcGuiDataForm');
    //}
    $pageTitle = 'Enter Games';
  //  $page = new kcm_pageEngine(NULL);
 //   $page->setIsReportPreview();
  //  $page->setBreakOnNewTable(TRUE);
    $this->ctr_pageCount = 0;
    $appEmitter->table_start('kgridTable');
    $this->ctr_pageHeader($appEmitter,NULL,NULL);
    $row = ' kgridEven';
    //$cnt=count($this->ctr_rosterKids);
    $lineCount = 0;
    $period = $roster->rst_cur_period;
    $i=0;
    foreach ($period->perd_kidPeriodMap as $kidPeriodId => $kidPeriod ) {
       ++$i;
       $this->ctr_rowNumber[$kidPeriodId] = $i;
    }
    $i=0;
    foreach ($period->perd_kidPeriodMap as $kidPeriodId => $kidPeriod ) {
        ++$i;
        $kid = $roster->rst_get_kid($kidPeriod->kidPer_kidId);
        $gameBatch = $kidPeriod->kidPeriod_getGameBatch($gameTypeIndex);
 ++$lineCount;
        //if ($lineCount > 40) {
        //    $this->ctr_pageHeader($appEmitter,$page,$columnDef);
        //    $lineCount = 0;
        //}
        $appEmitter->row_start();
        $appEmitter->cell_block($i+1,'geNum');
        $appEmitter->cell_block($kid->rstKid_firstName,'geFirstName');
        $appEmitter->cell_block($kid->rstKid_lastName,'geLastName');
     //   $appEmitter->cell_block($curKid->per->getGradeGroupName(),'geGrade');
        $appEmitter->cell_block($kid->rstKid_gradeDesc,'geGrade');
   //     $appEmitter->cell_block(kcmAsString_Rookie($curKid,$this->ctr_roster),'geGrade');
        $appEmitter->cell_block('??','geGrade');
//            $page->cell_block($curKid->KcmClassSubGroup);
        $appEmitter->cell_block($gameBatch->game_wins,'geWon');
        $appEmitter->cell_block($gameBatch->game_losses,'geLost');
        $appEmitter->cell_block($gameBatch->game_draws,'geDraw');
        $appEmitter->cell_block($gameBatch->getPercent(),'gePercent');
        $r1 = $this->ctg_getFormatted_results($gameBatch);
        $r2 = '';
        //if ($curKid->trn->ctp_gameCount>=1) {
        //    $sep1 = '';
        //    $sep2 = '';
        //    for ($j = 0; $j<$curKid->trn->ctp_gameCount; ++$j) {
        //        $game = $curKid->trn->ctp_gameArray[$j];
        //        if ($game->ctg_classDate == $this->ctr_roster->schedule->entryDateSql) {
        //            $r1 .= $sep1 . $game->ctg_getFormatted_result($this->ctr_isExport);
        //            $sep1 = ' , ';
        //        }
        //        else {
        //            $r2 .= $sep2 . $game->ctg_getFormatted_result($this->ctr_isExport);
        //            $sep2 = ' , ';
        //        }
        //    }
        //}
        ////$s = $curKid->trn->getFormatted_results();
        $appEmitter->cell_block($r1,'geGameList1');
        //$appEmitter->cell_block($r2,'geGameList2');
        $appEmitter->row_end();
    }
    $appEmitter->table_body_end('');
    $appEmitter->table_end();

//    if ($this->ctr_isExport) {
//        $file = $this->ctr_roster->program->getExportName($this->ctr_roster).'-Program';
//        $page->export->exportClose($this->ctr_exportType,$file);
//    //    $page->export->domSetAutoPage(TRUE);
//    //    $page->export->domSetBreakOnNewTable(TRUE);
//        $page->frmEnd();
//        $page->webPageBodyEnd();
//    }
//    else {
//        //??? $page->frmAddHidden($kcmState->Id, $kcmState->ksConvertToString());
//        $page->frmEnd();
//        $page->ScreenOnlyEnd();
//       // $s = outJavaScript($geEngine);
//        $page->webPageBodyEnd($s);
//    }

}

function ctr_pageHeader($appEmitter, $page,$columnDef) {
    // ++$this->ctr_pageCount;
    // if ($this->ctr_pageCount > 1) {
    //     if ( !$this->ctr_isExport )
    //         return;
    //     $page->rpt_tableEnd();
    //     $page->rpt_screenPageBreak(TRUE);
    // }
    // $appEmitter->table_start('kgridTable');
    //
    // //if (! $this->ctr_isExport) {
    //     echo '<tr>';
    //     $page->rowStart('h');
    //    'fix below');
    //     // $s = $this->ctr_gameGroup->gameTypeDesc.' Tournament';
    //     $s = ' ??? Tournament';
    //     $appEmitter->cell_block('geGuiGridHeadGame', $s, 'colspan="10"');
    //     //$page->cellStart('geGuiGridHeadGame', 'colspan="10"');
    //     //$page->textOut($this->ctr_gameGroup->gameTypeDesc.' Tournament');
    //     //$page->cellEnd();
    //     $appEmitter->row_end();
    //     echo '</tr>';
    // //}

    //$page->rowSetClasses(array('kgridEven','kgridOdd'));
    //********* Cross Table - Print Header row **********

    $appEmitter->table_head_start();
    $appEmitter->row_start();
    $appEmitter->cell_block ('Num','geNum kgridHead');
    $appEmitter->cell_block ('First Name','geFirstName kgridHead');
    $appEmitter->cell_block ('Last Name','geLastName kgridHead');
    //$page->cell_block ('Grade<br>Group','geGradeGroup kgridHead');
    $appEmitter->cell_block ('Grade','geGrade kgridHead');
    $appEmitter->cell_block ('R<br>V','geRookie kgridHead');
    //$page->rpt_col_Header ('Class<br>Sub-Group','geSubGroup kgridHead');
    $appEmitter->cell_block ('Win','geWon kgridHead');
    $appEmitter->cell_block ('Loss','geLost kgridHead');
    $appEmitter->cell_block ('Draw','geDraw kgridHead');
    $appEmitter->cell_block ('Percent','gePercent kgridHead');
    $dateDesc  = '???'; // $this->ctr_roster->schedule->entryDateObject->format( "F j" );
   // $appEmitter->cell_block ('Games this Week<br>'.$dateDesc,'geGameList1 kgridHead');
    $appEmitter->cell_block ('Games','geGameList2 kgridHead');
    $appEmitter->row_end();
    $appEmitter->table_head_end();
}

function ctg_getFormatted_results($batch) {
    $all = '';
    $allPunc = '';
    foreach ($batch->gaBatch_gameMap as $gameId => $game) {
        $s = '';
        for ($i=0; $i < $game->game_wins; ++$i) {
           $s .= 'W';
        }
        for ($i=0; $i < $game->game_losses; ++$i) {
           $s .= 'L';
        }
        for ($i=0; $i < $game->game_draws; ++$i) {
           $s .= 'D';
        }
        // Need to change playerId to row number on crosstable
        if (!empty($game->game_opponents)) {
            $a = array();
            foreach( $game->game_opponents as $opp) {
                $a[] = $this->ctr_rowNumber[$opp];
            }
            $s .= implode(',',$a);
       }
        $all .= $allPunc . edit_button_submit('edit_'.$gameId, $s,'lc-edit');
        $allPunc = ' ';
    }
    return $all;
}

function ctg_getFormatted_opponents($batch) {
    $s = '';
    $sep = '';
    for ($i = 0; $i < $this->ctg_oppGameCount; ++$i) {
        $opGame = $this->ctg_oppGameArray[$i];
        $opKid = $opGame->ctg_gameKid;
        $s .= $sep;
        $sep = ',';
        $s .= ($opKid->per->sortIndex + 1);
    }
    return $s;
}

function ctg_getFormatted_result($isExport) {
    $formattedPoints = $this->ctg_getFormatted_points() . $this->ctg_getFormatted_opponents();
    //if (! $isExport) {
        //$url = 'need-to-fix'; // self::$kcmState->convertToUrl('kcm-game_entry.php',array('Submit','ctlink','GEct',$this->ctg_atGameId));
        //$s = '<a href="'.$url.'">'.$s.'</a>';
        $button = edit_button_submit('edit_'.$this->ctg_atGameId.'_crt',$formattedPoints,'lc-edit');
    //}
    return $button;
}

} // end class

class report_gameHistory {

function __construct() {
}

function stdRpt_initStyles($appEmitter) {
}

function stdRpt_output($appData, $appEmitter,$appGlobals) {
    $appEmitter->table_start('draff-report',5);
    $appEmitter->row_start();
    $appEmitter->emit_nrLine('<td class="report-title rpt-hdr" colspan="99">Game History</td>');  //@?@?@?@?@
    $appEmitter->row_end();
    $appEmitter->row_start();
    $appEmitter->cell_block( 'Source','rpt-integer rpt-hdr');
    $appEmitter->cell_block( 'Class Date','rpt-date rpt-hdr');
    $appEmitter->cell_block( 'Game Type','rpt-gameType rpt-hdr');
    $appEmitter->cell_block( 'Game Result','rpt-gameResult rpt-hdr');
    $appEmitter->cell_block( 'Edit','rpt-editLink rpt-hdr');
    $appEmitter->row_end();
    $matchCount = count($appData->apd_hist_gameBundle->gagBu_list);
    if ($matchCount < 1) {
        $appEmitter->cell_block( 'No Records','rpt-gameType','colspan="99"');
    }
    else {
        foreach ($appData->apd_hist_gameBundle->gagBu_list as $game) {
            if ($game->game_gameType==0)
                $gameType = 'Chess';
            else if ($game->game_gameType==1)
                $gameType = 'Blitz';
            else if ($game->game_gameType==2)
                $gameType = 'Bughouse';
            else
                 $gameType = 'Unknown';
            $appEmitter->row_start();
            $org = kcmRosterLib_getDesc_originCode( $game->game_originCode);
             $appEmitter->cell_block( $org);
            $appEmitter->cell_block( draff_dateAsString($game->game_classDate, 'M j'),'rpt-date' );
            $appEmitter->cell_block( $gameType,'rpt-gameType' );
            $res = '??resultString??'; // $game->gaMatch_getResultString($appGlobals,$appData->db_roster,'<br>');
            $appEmitter->emit_nrLine('<td class="rpt-gameResult">' . $res . '</td>');
            $button = edit_button_submit('edit_'.$game->game_atGameId.'_hst','Edit','lc-edit');
            $appEmitter->cell_block(  $button , 'rpt-editLink'  );
            $appEmitter->row_end();
        }
    }
    $appEmitter->table_end();
}

function rpt_getData($appGlobals) {
}

function rpt_outRow_detail($appEmitter,$row,$isOffice=FALSE) {
}

} // end class

class bug_partnership {
public $bp_ids = array();
public $bp_names = array();
public $bp_wins;
public $bp_loses;

function __construct() {
}

}

class bug_game {
public $bg_left;
public $bg_right;
public $bg_mode;   //1 = usual result   2=unusual result

function __construct() {
	$this->bg_left  = new bug_partnership;
	$this->bg_right = new bug_partnership;
}

}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

//--   //  class data_bughouse extends draff_appData {
//--   //
//--   //  public $apd_bugPlayers_kidPeriodIdList  = array();
//--   //  public $apd_bugPlayers_nameList = array();
//--   //  public $apd_bugPlayers_count    = 0;
//--   //  public $apd_bugPlayers_isCrazy  = FALSE;  //????? code or boolean ?????
//--   //  public $apd_bugPlayers_isValid  = FALSE;  //????? code or boolean ?????
//--   //
//--   //  public $apd_bugGames_normalGames  = array();  // list of possible normal game results
//--   //  public $apd_bugGames_unusualGames = array();  // list of possible unusual game results
//--   //  public $apd_bugGames_radioResults = array(0,0,0,0); // 1 for normal result and 3 for the three unusual result games
//--   //  public $apd_bugGames_validated    = array(); // games validated as worthy of saving
//--   //
//--   //  public $apd_save_player;  //????? should be eliminated
//--   //
//--   //  //public $apd_save_win;
//--   //  //public $apd_save_lost;
//--   //
//--   //  function __construct($appGlobals) {
//--   //  	$this->apd_bugGames_normalGames = array();
//--   //  }
//--   //
//--   //  function apd_formData_get( $appGlobals, $appChain ) {
//--   //  }
//--   //
//--   //  function apd_formData_validate( $appGlobals, $appChain ) {
//--   //  }
//--   //
//--   //  function apd_bugPlayers_getSubmitted( $appGlobals, $appChain ) {
//--   //      $this->apd_bugPlayers_nameList = array();
//--   //      $this->apd_bugPlayers_isCrazy = $appChain->chn_data_posted_get('@isCrazyHouse','0');
//--   //      $this->apd_bugPlayers_kidPeriodIdList = kcmRosterLib_kidList_checkboxes_getChecked($appGlobals, $appData->apd_roster_program, $appChain);
//--   //      $this->apd_bugPlayers_count = count($this->apd_bugPlayers_kidPeriodIdList);
//--   //  }
//--   //
//--   //  function apd_bugPlayers_validate( $appGlobals, $appChain ) {
//--   //      if ($this->apd_bugPlayers_isCrazy==1) {
//--   //          if ($this->apd_bugPlayers_count!=3) {
//--   //              $appChain->chn_message_set('There must be three players for Crazy House');
//--   //              $this->apd_bugPlayers_isValid = FALSE;
//--   //              return;
//--   //         }
//--   //      }
//--   //      else {
//--   //          if ($this->apd_bugPlayers_count!=4) {
//--   //              $appChain->chn_message_set('There must be four players for Bughouse');
//--   //              $this->apd_bugPlayers_isValid = FALSE;
//--   //              return;
//--   //          }
//--   //      }
//--   //      $this->apd_bugPlayers_isValid = TRUE;
//--   //  }
//--   //
//--   //  function apd_bugPlayers_setPosted( $appGlobals, $appChain ) {
//--   //      for ($i=0; $i<$this->apd_bugPlayers_count; ++$i) {
//--   //          $kidId = $this->apd_bugPlayers_kidPeriodIdList[$i];
//--   //          $kidName = $appData->apd_roster_program->rst_cur_period->perd_getKidPeriodObject($kidId)->kidPer_kidObject->rstKid_uniqueName;
//--   //          $this->apd_bugPlayers_nameList[] = $kidName;
//--   //          $appChain->chn_data_posted_set('#bugPlayerId_'.$i,$kidId);
//--   //          $appChain->chn_data_posted_set('#bugPlayerName_'.$i,$kidName);
//--   //      }
//--   //      $appChain->chn_data_posted_set('#bugPlayerCount',$this->apd_bugPlayers_count);
//--   //      $appChain->chn_data_posted_set('#bugPlayerCrazy',$kidCount==3);
//--   //  }
//--   //
//--   //  function apd_bugPlayers_getPosted( $appGlobals, $appChain ) {
//--   //      $this->apd_bugPlayers_count = $appChain->chn_data_posted_get('#bugPlayerCount','0');
//--   //
//--   //      if (empty($this->apd_bugPlayers_count)) {
//--   //          return;
//--   //      }
//--   //      $this->apd_bugPlayers_isCrazy = $appChain->chn_data_posted_get('#bugPlayerCrazy','0');
//--   //  	$this->apd_bugPlayers_isCrazy = empty($this->apd_bugPlayers_isCrazy) ? 0 : 1;
//--   //      for ($i=0; $i <$this->apd_bugPlayers_count; ++$i) {
//--   //          $this->apd_bugPlayers_kidPeriodIdList[]   = $appChain->chn_data_posted_get('#bugPlayerId_'.$i);
//--   //          $this->apd_bugPlayers_nameList[]  = $appChain->chn_data_posted_get('#bugPlayerName_'.$i);
//--   //      }
//--   //  }
//--   //
//--   //  function apd_bugGames_initialize( $appGlobals, $appChain ) {
//--   //      $this->apd_norm_keys = array();
//--   //      $this->apd_norm_left_names = array();
//--   //      $this->apd_norm_right_names = array();
//--   //      $this->apd_norm_result_desc1 = array();
//--   //      $this->apd_norm_result_desc2 = array();
//--   //  	$this->apd_bugGames_normalGames[] = NULL;
//--   //  	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(1),3,0,array(2,3,4),1,2);
//--   //  	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(2),3,0,array(1,3,4),1,2);
//--   //  	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(3),3,0,array(1,2,4),1,2);
//--   //  	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(4),3,0,array(1,2,3),1,2);
//--   //  	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(1),0,3,array(2,3,4),2,1);
//--   //  	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(2),0,3,array(1,3,4),2,1);
//--   //  	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(3),0,3,array(1,2,4),2,1);
//--   //  	$this->apd_bugGames_normalGames[] = $this->apd_addGame(1,array(4),0,3,array(1,2,3),2,1);
//--   //  	$this->apd_bugGames_unusualGames[] = NULL;
//--   //  	if ($this->apd_bugPlayers_isCrazy==0) {
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,2),1,0,array(3,4),0,1);
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,2),0,1,array(3,4),1,0);
//--   //  	    $this->apd_bugGames_unusualGames[] = NULL;
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,3),1,0,array(2,4),0,1);
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,3),0,1,array(2,4),1,0);
//--   //  	    $this->apd_bugGames_unusualGames[] = NULL;
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,4),1,0,array(2,3),0,1);
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1,4),0,1,array(2,3),1,0);
//--   //  	    $this->apd_bugGames_unusualGames[] = NULL;
//--   //  	}
//--   //  	else {
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1),1,0,array(2,3),0,1);
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(1),0,1,array(2,3),1,0);
//--   //  	    $this->apd_bugGames_unusualGames[] = NULL;
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(2),1,0,array(1,3),0,1);
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(2),0,1,array(1,3),1,0);
//--   //  	    $this->apd_bugGames_unusualGames[] = NULL;
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(3),1,0,array(1,2),0,1);
//--   //  	    $this->apd_bugGames_unusualGames[] = $this->apd_addGame(2,array(3),0,1,array(1,2),1,0);
//--   //  	    $this->apd_bugGames_unusualGames[] = NULL;
//--   //  	}
//--   //  	$this->apd_bugGames_radioResults[0] = $appChain->chn_data_posted_get('@groupNormal');
//--   //  	$this->apd_bugGames_radioResults[1] = $appChain->chn_data_posted_get('@groupUnusual1');
//--   //  	$this->apd_bugGames_radioResults[2] = $appChain->chn_data_posted_get('@groupUnusual2');
//--   //  	$this->apd_bugGames_radioResults[3] = $appChain->chn_data_posted_get('@groupUnusual3');
//--   //  }
//--   //
//--   //  function apd_bugGames_getSubmitted( $appGlobals, $appChain ) {
//--   //      $appChain->chn_posted_read('@groupNormal',$this->apd_bugGames_radioResults[0]);
//--   //      $appChain->chn_posted_read('@groupUnusual1',$this->apd_bugGames_radioResults[1]);
//--   //      $appChain->chn_posted_read('@groupUnusual2',$this->apd_bugGames_radioResults[2]);
//--   //      $appChain->chn_posted_read('@groupUnusual3',$this->apd_bugGames_radioResults[3]);
//--   //  }
//--   //
//--   //  function apd_bugGames_validate( $appGlobals, $appChain ) {
//--   //      $this->apd_bugGames_validated = array();
//--   //      if (empty($this->apd_bugGames_radioResults[0])) {
//--   //          $appChain->chn_message_set('You must select a result');
//--   //      }
//--   //      if ($this->apd_bugGames_radioResults[0]!=9) {
//--   //          $gameIndex = $this->apd_bugGames_radioResults[0];
//--   //          $this->apd_bugGames_validated[] = $this->apd_bugGames_normalGames[$gameIndex];
//--   //          $this->apd_bugGames_save( $appGlobals, $appChain );
//--   //          return;
//--   //      }
//--   //      $havegame = FALSE;
//--   //      for ($i=1; $i<=3; ++$i) {
//--   //          if (empty($this->apd_bugGames_radioResults[$i])) {
//--   //              $appChain->chn_message_set('You must select a result (or unplayed) for all three games');
//--   //              return;
//--   //          }
//--   //          $gameIndex = $this->apd_bugGames_radioResults[$i];
//--   //          if ($this->apd_bugGames_unusualGames[$gameIndex]!==NULL) {
//--   //              $havegame = TRUE;
//--   //              $this->apd_bugGames_validated[] = $this->apd_bugGames_unusualGames[$gameIndex];
//--   //          }
//--   //      }
//--   //      if (!$havegame) {
//--   //          $this->apd_bugGames_validated = array();
//--   //          $appChain->chn_message_set('You must select a result at least one game');
//--   //      }
//--   //   if (!empty($this->apd_bugGames_validated)) {
//--   //     $this->apd_bugGames_save( $appGlobals, $appChain );
//--   //   }
//--   //  }
//--   //
//--   //  function apd_bugGames_save( $appGlobals, $appChain ) {
//--   //      $partners = array();
//--   //      foreach ($this->apd_bugGames_validated as $bugGame) {
//--   //          $partners[] = $bugGame->bg_left;
//--   //          $partners[] = $bugGame->bg_right;
//--   //      }
//--   //      $player_id   = array();
//--   //      $player_win  = array();
//--   //      $player_lost = array();
//--   //      foreach ($partners as $partnership) {
//--   //          $count = count($partnership->bp_ids);
//--   //          for ($i=0; $i<$count; ++$i) {
//--   //              $playerName = $partnership->bp_names[$i];
//--   //              $playerId = $partnership->bp_ids[$i];
//--   //              $win      = $partnership->bp_wins;
//--   //              $lost     = $partnership->bp_loses;
//--   //              if (isset($player_id[$playerId])) {
//--   //                  $player_id[$playerId]   = $playerId;
//--   //                  $player_win[$playerId]  += $win;
//--   //                  $player_lost[$playerId] += $lost;
//--   //              }
//--   //              else {
//--   //                  $player_id[$playerId]   = $playerId;
//--   //                  $player_win[$playerId]  = $win;
//--   //                  $player_lost[$playerId] = $lost;
//--   //              }
//--   //          }
//--   //      }
//--   //      $game = new stdData_gameMatch_group;
//--   //      $game->gaMatch_clear($appGlobals,ROSTER_BUGHOUSE);
//--   //      foreach ($player_id as $playerId => $value) {
//--   //          $win = $player_win[$playerId];
//--   //          $lost = $player_lost[$playerId];
//--   //          $game->gaMatch_addPlayerResult($playerId,$win ,0,$lost);
//--   //      }
//--   //      $atGameId = $game->gaMatch_save($appGlobals);
//--   //      //$editUrl =  $appChain->chn_url_build_chained_url(NULL,TRUE,array('chRec'=>$atGameId,'chStep'=>11) );
//--   //      //$s = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . $editUrl . '">Edit</a>';
//--   //      $savedGame = new stdData_gameMatch_group;
//--   //      $savedGame->gaMatch_read($appGlobals, $atGameId);
//--   //      $saveDesc= $savedGame->gaMatch_getSavedString($appGlobals);
//--   //      $class='';
//--   //      $value = '@bugEdit_' . $atGameId;
//--   //      $button =  '<button type="submit" '. $class . ' name="submit" value="'.$value.'">BugEdit</button>';
//--   //      $appChain->chn_message_set( $saveDesc . ' ' . $button);
//--   //      $appChain->chn_launch_newChain(FORM_CHESS_WINNER);
//--   //  }
//--   //
//--   //  function apd_addPartners($partnership, $partners,$win, $lost) {
//--   //  	foreach ($partners as $index) {
//--   //  		if (isset($this->apd_bugPlayers_kidPeriodIdList[$index-1])) {
//--   //  	       $partnership->bp_ids[]    = $this->apd_bugPlayers_kidPeriodIdList[$index-1];
//--   //  	       $partnership->bp_names[]  = $this->apd_bugPlayers_nameList[$index-1];
//--   //  		}
//--   //  //		else {
//--   //  //	       $partnership->bp_ids[]    = $this->apd_bugPlayers_kidPeriodIdList[$index-1];
//--   //  //	       $partnership->bp_names[]  = $this->apd_bugPlayers_nameList[$index-1];
//--   //  //		}
//--   //  	}
//--   //  	$partnership->bp_wins = $win;
//--   //  	$partnership->bp_loses = $lost;
//--   //  }
//--   //
//--   //  function apd_addGame($mode, $partners1,$win1, $lost1, $partners2,$win2, $lost2) {
//--   //  		$game = new bug_game;
//--   //  		$game->bg_mode = $mode;
//--   //  	    $this->apd_addPartners($game->bg_left,$partners1,$win1, $lost1);
//--   //  	    $this->apd_addPartners($game->bg_right,$partners2,$win2, $lost2);
//--   //  		return $game;
//--   //  }
//--   //
//--   //  function apd_save_start() {
//--   //      $this->apd_save_player = array();
//--   //      //$this->apd_save_win    = array();
//--   //      //$this->apd_save_lost   = array();
//--   //  }
//--   //
//--   //  function apd_save_addResult($result) {
//--   //      $r = explode('_',$result);
//--   //      $rc = count($r);
//--   //      if ($rc < 3) {
//--   //          return;
//--   //      }
//--   //      for ($i=0; $i<$rc; $i=$i+3) {
//--   //          $player = $r[$i];
//--   //          $wins   = $r[$i+1];
//--   //          $lost   = $r[$i+2];
//--   //          $pos = array_search($player,$this->apd_save_player);
//--   //          if ($pos===FALSE) {
//--   //              $this->apd_save_player[] = $player;
//--   //              $this->apd_save_wins[] = $wins;
//--   //              $this->apd_save_lost[]  = $lost;
//--   //          }
//--   //          else {
//--   //              $this->apd_save_wins[$pos] += $wins;
//--   //              $this->apd_save_lost[$pos] += $lost;
//--   //          }
//--   //      }
//--   //  }
//--   //
//--   //  function apd_resultHtml($partners) {
//--   //      $nameArray = array();
//--   //  	foreach ($partners->bp_names  as $name) {
//--   //  		$nameArray[] = emit_span($name,'lc-name');
//--   //  	}
//--   //  	switch (count($nameArray)) {
//--   //  		case 3: $names = implode(' and ', $nameArray); break;
//--   //  		case 2: $names = 'Partners ' . implode(' and ', $nameArray); break;
//--   //  		case 1: $names = $nameArray[0]; break;
//--   //  	}
//--   //  	if ($partners->bp_wins==3) {
//--   //  		$result = 'Won all 3 games';
//--   //  	}
//--   //  	else if ($partners->bp_loses==3) {
//--   //  		$result = 'Lost all 3 games';
//--   //  	}
//--   //  	else if ($partners->bp_wins==2) {
//--   //  		$result = 'Won 2 games and lost 1 game';
//--   //  	}
//--   //  	else if ($partners->bp_loses==2) {
//--   //  		$result = 'Won 1 game and lost 2 games';
//--   //  	}
//--   //  	else if ($partners->bp_wins==1) {
//--   //  		$result = 'Won Game';
//--   //  	}
//--   //  	else if ($partners->bp_loses==1) {
//--   //  		$result = 'Lost Game';
//--   //  	}
//--   //  	else {
//--   //  		$result = '???';
//--   //  	}
//--   //  	return $names . ' - ' . $result;
//--   //  //	return emit_span($names . ' ' . $result,'lc-block');;
//--   //  }
//--   //
//--   //  } // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appChain = new Draff_Chain(  'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmRoster_globals());
$appChain->chn_register_appData( $appData = new application_data());
$appGlobals->gb_forceLogin ();

$appData->apd_all_getData($appChain);  //????? should be moved to appData get

//$appData->apd_roster_program = new roster_type_kidSelect($appGlobals);
//$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);

$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);


$appChain->chn_form_register(FORM_CHESS_WINNER,'appForm_chess_winner');
$appChain->chn_form_register(FORM_CHESS_LOSER,'appForm_chess_loser');
$appChain->chn_form_register(FORM_CHESS_DRAW,'appForm_chess_draw');
$appChain->chn_form_register(FORM_BUGHOUSE_PLAYERS,'appForm_bughouse_players');
$appChain->chn_form_register(FORM_BUGHOUSE_GAMES,'appForm_bughouse_games');
$appChain->chn_form_register(FORM_GAME_EDIT,'appForm_chess_edit');
$appChain->chn_form_register(FORM_GAME_HISTORY,'appForm_game_history');
$appChain->chn_form_register(FORM_GAME_CROSSTABLE,'appForm_crossTable');

$appChain->chn_form_launch(); // proceed to current form

exit;


?>