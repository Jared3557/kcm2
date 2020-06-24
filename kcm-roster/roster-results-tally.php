<?php

//--- roster-results-tally.php ---

ob_start();  // output buffering (needed for redirects, draff-appHeader changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );

include_once( 'roster-system-data-tally.inc.php' );

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

//include_once( 'roster-system-data-games.inc.php' );
//include_once( 'roster-results-game-edit.inc.php' );
include_once( 'roster-system-data-points.inc.php' );
//include_once( 'roster-results-points-edit.inc.php' );
include_once( 'roster-system-data-tally.inc.php' );

const FORM_TALLY_GRID = 1;
const FORM_TALLY_EDIT = 2;

//@@@@@@@@@@@@@@@@@@@@
//@  Step 1
//@@@@@@@@@@@@@@@@@@@@

class appForm_tallyGrid extends Draff_Form {  // specify winner

function drForm_processSubmit ( $scriptData, $appGlobals, $chain ) {
    kernel_processBannerSubmits( $appGlobals, $chain, $submit );
    $chain->chn_form_savePostedData();
   // $chain->chn_ValidateAndRedirectIfError();
    if ( $appChain->chn_submit[0]='@kid' ) {
        $scriptData->sd_grid_submit($appGlobals, $chain, $appChain->chn_submit[1]);
        $chain->chn_launch_newChain(FORM_TALLY_EDIT);
    }
    else {
        $chain->chn_launch_continueChain(FORM_TALLY_GRID);
    }

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $scriptData, $appGlobals, $chain, $emitter ) {
    $appEmitter->set_theme( 'theme-panel' );
    $appEmitter->set_title('Select Active Class Date');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    kcmRosterLib_setBannerSubTitle($emitter,$appGlobals, $scriptData->sd_roster_program,'Results Tally');
    //$appGlobals->gb_appMenu_init($chain, $emitter, $scriptData->sd_roster_program);
    //$emitter->set_menu_customize( $appChain, $appGlobals );
}

function drForm_initFields( $scriptData, $appGlobals, $chain ) {
    $scriptData->sd_grid_loadData($appGlobals);
    $scriptData->sd_tallyGrid_report = new report_tallyGrid;
    $scriptData->sd_tallyGrid_report->taRpt_initControls($scriptData, $appGlobals, $this);
    // need filters ????????
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

function drForm_outputHeader ( $scriptData, $appGlobals, $chain, $emitter ) {
    $emitter->zone_start('draff-zone-filters-default');
    //$this->grid_filterOptions($scriptData,$appGlobals);
    $emitter->zone_end();
}

function drForm_outputContent ( $scriptData, $appGlobals, $chain, $emitter ) {
    $emitter->zone_start('draff-zone-content-default');
    $scriptData->sd_tallyGrid_report->taRpt_out($scriptData, $appGlobals, $emitter );
    //$this->grid_report($chain, $scriptData, $emitter, $this, $appGlobals );
    $emitter->zone_end();
}

function drForm_outputFooter ( $scriptData, $appGlobals, $chain, $emitter ) {
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@
//@  Step 2
//@@@@@@@@@@@@@@@@@@@@

class appForm_tallyEditKid extends Draff_Form {  // specify winner

function drForm_processSubmit ( $scriptData, $appGlobals, $chain, $submit ) {
    kernel_processBannerSubmits( $appGlobals, $chain );

    if ($appChain->chn_submit[0] == 'cancel') {
        $appGlobals->gb_form->chain->chn_step_activate(FORM_TALLY_GRID);
        return;
    }
    if ( $appChain->chn_submit[0] == 'gagid' ) {
        $chain->chn_arg_recordId = $appChain->chn_submit[1];
        //$form->chain->chn_url_registerArgument(URL_SUBMIT,'chRec', $form->chain->chn_arg_recordId);
      //  $appGlobals->gb_form->chain->chn_step_activate(CHAINSTEP_IDX_GAME_EDIT);
        return;
    }
    if ( $appChain->chn_submit[0] == 'pntid' ) {
        $chain->chn_arg_recordId = $appChain->chn_submit[1];
        //$form->chain->chn_url_registerArgument(URL_SUBMIT,'chRec', $form->chain->chn_arg_recordId);
       // $appGlobals->gb_form->chain->chn_step_activate(INC_STEP_POINTS_EDIT);
        return;
    }
   if ($appChain->chn_submit[0] == 'submit') {
        //$kidPeriodId = $appGlobals->gb_form->chain->chn_arg_recordId;
        // start transaction ?????????????????
        $scriptData->points->pnt_tallyRecord_write($appGlobals);
        $scriptData->chess->gap_tallyRecord_write($appGlobals);
        $scriptData->blitz->gap_tallyRecord_write($appGlobals);
        $scriptData->bug->gap_tallyRecord_write($appGlobals);
        // end transaction ??????????????????
        $appGlobals->gb_form->chain->chn_step_activate(FORM_TALLY_GRID);
        return;
    }
    //$chain->chn_form_savePostedData();
    //$chain->chn_ValidateAndRedirectIfError();  //?????????????
    $chain->chn_launch_continueChain(FORM_TALLY_KID);

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $scriptData, $appGlobals, $chain, $emitter ) {
    $appEmitter->set_theme( 'theme-panel' );
    $appEmitter->set_title('Select Active Class Date');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    $roster = $scriptData->sd_roster_program;
    kcmRosterLib_setBannerSubTitle($emitter,$appGlobals, $scriptData->sd_roster_program,'Results Tally');
    //$appGlobals->gb_appMenu_init($chain, $emitter, $roster);
    //$emitter->set_menu_customize( $appChain, $appGlobals );
}

function drForm_initFields( $scriptData, $appGlobals, $chain ) {
    $scriptData->sd_edit_loadData($appGlobals, $chain);
    $scriptData->sd_edit_report = new report_tallyEditKid;
    $scriptData->sd_edit_report->edRpt_initControls($scriptData, $appGlobals, $this, $scriptData->sd_roster_program);
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

function drForm_outputHeader ( $scriptData, $appGlobals, $chain, $emitter ) {
}

function drForm_outputContent ( $scriptData, $appGlobals, $chain, $emitter ) {
   //$kidPeriod = $scriptData->sd_roster_program->rst_cur_period->perd_getKidPeriodObject($scriptData->sd_edit_kidPeriodId);
   // $kid = $kidPeriod->kidPer_kidObject;

    $emitter->zone_start('draff-zone-content-default');
    $scriptData->sd_edit_report->edRpt_out($scriptData, $appGlobals, $emitter, $this );

    //$emitter->table_start('',999);  //??????????????????
    //$this->edit_gamesSection($emitter, $appGlobals,$scriptData);
    //$this->edit_pointsSection($emitter, $appGlobals,$scriptData);
    // $emitter->table_end();

    $emitter->zone_end();
}

function drForm_outputFooter ( $scriptData, $appGlobals, $chain, $emitter ) {
}

}  // end class

//Class kcm2_scores_session_data {
//// retained for duration of form
//public $ses_originCode = NULL;
//
//function __construct() {
//    $this->sesGame = new stdData_gameMatch_group;
//}
//
//} // end class

class report_tallyGrid {

function taRpt_initControls($scriptData, $appGlobals, $form) {
    $roster_program = $scriptData->sd_roster_program;
    $roster_period  = $scriptData->sd_roster_program->rst_get_period();
    foreach ($roster_period->perd_kidPeriodMap as $kidPeriodId => $kidPeriod) {
        $kid = $roster_program->rst_get_kid($kidPeriod->kidPer_kidId);
        $fieldId = '@kidPeriodId_'.$kidPeriodId;
        $form->drForm_define_linkButton($fieldId,$kid->rstKid_firstName);
    }
}

function taRpt_out($scriptData, $appGlobals, $emitter ) {
    $roster_period = $scriptData->sd_roster_program->rst_get_period();
    $roster_program = $scriptData->sd_roster_program;
    $emitter->table_start('draff-report',11);
    $this->grid_heading($emitter);
    foreach ($roster_period->perd_kidPeriodMap as $kidPeriodId => $kidPeriod) {
        $kid = $roster_program->rst_get_kid($kidPeriod->kidPer_kidId);
        $points = isset($kidPeriod->kidPer_tally_points) ? $kidPeriod->kidPer_tally_points->pnt_pointValue : 0;
        $fieldId = '@kidPeriodId_'.$kidPeriodId;
        $chess = $roster_period->perd_force_gameObject($kidPeriod->kidPer_tally_chess);
        $blitz = $roster_period->perd_force_gameObject($kidPeriod->kidPer_tally_blitz);
        $bug   = $roster_period->perd_force_gameObject($kidPeriod->kidPer_tally_bug);
        $emitter->row_start();
        $emitter->cell_block( $kid->rstKid_firstName,'rpt-smallInt border-group-right');
        //$emitter->cell_block( $link,'rpt-nameFirst border-group-left border-group-top');
        $emitter->cell_block( $kid->rstKid_lastName,'rpt-smallInt border-group-right');
        $emitter->cell_block( $points,'rpt-smallInt border-group-right');
        $emitter->cell_block( $chess->game_wins  ,'rpt-smallInt');
        $emitter->cell_block( $chess->game_losses,'rpt-tallyEdit');
        $emitter->cell_block( $chess->game_draws ,'rpt-smallInt border-group-right');
        $emitter->cell_block( $blitz->game_wins ,'rpt-smallInt border-group-left');
        $emitter->cell_block( $blitz->game_losses,'rpt-tallyEdit');
        $emitter->cell_block( $blitz->game_draws,'rpt-smallInt border-group-right');
        $emitter->cell_block( $bug->game_wins ,'rpt-smallInt border-group-left');
        $emitter->cell_block( $bug->game_losses,'rpt-tallyEdit border-group-right');
        $emitter->cell_block( $appEmitter->getString_button('Edit','',$fieldId ) , 'rpt-editLink'  );
        $emitter->row_end();
    }
    $emitter->table_end();
}

function grid_game_columns($gameType, $tallyGameRecord) {
    //??????? NOT USED
    if ($tallyGameRecord == NULL) {
          $emitter->cell_block( '','rpt-tallyEdit border-group-left');
         $emitter->cell_block( '','rpt-smallInt');
        if ($gameType != 2) {
          $emitter->cell_block( '','rpt-smallInt');
        }
         $emitter->cell_block( '','rpt-smallInt border-group-right');
  }
    else {
        $emitter->cell_block( 'Game','rpt-tallyEdit border-group-left');
          $emitter->cell_block( $tallyGameRecord->game_wins,'rpt-smallInt');
         if ($gameType == 2) {
             $emitter->cell_block( $tallyGameRecord->game_losses,'rpt-smallInt border-group-right');
         }
         else {
            $emitter->cell_block( $tallyGameRecord->game_losses,'rpt-smallInt ');
           $emitter->cell_block( $tallyGameRecord->game_draws,'rpt-smallInt border-group-right');
        }
   }
}


function grid_heading($emitter) {
     $emitter->row_start();
    $emitter->emit_nrLine('<td class="report-title rpt-hdr" colspan="99">Tally</td>');
     $emitter->row_end();

       $emitter->row_start();
    $emitter->emit_nrLine('<td class="rpt-hdr draff-text-center border-group-left border-group-right" colspan="2">Name</td>');
    $emitter->emit_nrLine('<td class="rpt-hdr rpt-smallInt draff-text-center border-group-left colRight" rowspan="2">Points</td>');
    $emitter->emit_nrLine('<td class="rpt-hdr draff-text-center border-group-left border-group-right" colspan="3">Chess</td>');
     $emitter->emit_nrLine('<td class="rpt-hdr draff-text-center border-group-left border-group-right" colspan="3">Blitz</td>');
    $emitter->emit_nrLine('<td class="rpt-hdr draff-text-center border-group-left border-group-right" colspan="2">Bughouse</td>');
    $emitter->cell_block( 'Edit','rpt-nameFirst rpt-hdr colLeft','rowspan="2"');
     $emitter->row_end();
      $emitter->row_start();
    $emitter->cell_block( 'First','rpt-nameFirst rpt-hdr colLeft');
    $emitter->cell_block( 'Last','rpt-nameLast rpt-hdr border-group-right');
   $emitter->cell_block( 'W','rpt-smallInt rpt-hdr');
   $emitter->cell_block( 'L','rpt-smallInt rpt-hdr');
    $emitter->cell_block( 'D','rpt-smallInt rpt-hdr border-group-right');
    $emitter->cell_block( 'W','rpt-smallInt rpt-hdr ');
    $emitter->cell_block( 'L','rpt-smallInt rpt-hdr');
    $emitter->cell_block( 'D','rpt-smallInt rpt-hdr border-group-right');
   $emitter->cell_block( 'W','rpt-smallInt rpt-hdr');
    $emitter->cell_block( 'L','rpt-smallInt rpt-hdr border-group-right');
     $emitter->row_end();
}

} // end class

class report_tallyEditKid {

function edRpt_initControls($scriptData, $appGlobals, $form) {
    $roster = $scriptData->sd_roster_program;
    $pointChoices = kcmRosterLib_getCombo_pointValues($appGlobals);
    $gameCount = kcmRosterLib_getCombo_gameCount($appGlobals);
    $this->drForm_addField( new Draff_Combo('@points', $scriptData->sd_edit_pointsTally->pnt_pointValue, $pointChoices) );
    $this->drForm_addField( new Draff_Combo('@chessW', $scriptData->sd_edit_chessTally->game_wins  , $gameCount));
    $this->drForm_addField( new Draff_Combo('@chessL', $scriptData->sd_edit_chessTally->game_losses , $gameCount));
    $this->drForm_addField( new Draff_Combo('@chessD', $scriptData->sd_edit_chessTally->game_draws , $gameCount));
    $this->drForm_addField( new Draff_Combo('@blitzW', $scriptData->sd_edit_blitzTally->game_wins  , $gameCount));
    $this->drForm_addField( new Draff_Combo('@blitzL', $scriptData->sd_edit_blitzTally->game_losses , $gameCount));
    $this->drForm_addField( new Draff_Combo('@blitzD', $scriptData->sd_edit_blitzTally->game_draws , $gameCount));
    $this->drForm_addField( new Draff_Combo('@bugW',   $scriptData->sd_edit_bugTally->game_wins    , $gameCount));
    $this->drForm_addField( new Draff_Combo('@bugL',   $scriptData->sd_edit_bugTally->game_losses   , $gameCount));
    $this->drForm_addField( new Draff_Button( '@submit' , 'Submit') );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
}

function edRpt_out($scriptData, $appGlobals, $emitter, $form ) {
    $roster = $scriptData->sd_roster_program;
    //=====  General Points and Games
    $colData = array('',$scriptData->sd_edit_kid->rstKid_lastName,0,0,0,0,0,0,0,0,0,0,0);
    $emitter->emit_nrLine('<br>');
    $emitter->table_start();

    $this->edit_heading($emitter, $scriptData, $appGlobals);
    $this->edit_tallySection($scriptData, $appGlobals, $emitter, $form);
    $emitter->table_end();
    $this->edit_gamesSection($scriptData, $appGlobals, $emitter);
    $this->edit_pointsSection($scriptData, $appGlobals, $emitter);
}

function edit_heading($emitter, $scriptData, $appGlobals) {
    $roster = $scriptData->sd_roster_program;
    $emitter->row_start();
    $desc = $scriptData->sd_edit_kid->rstKid_firstName . ' ' . $scriptData->sd_edit_kid->rstKid_lastName . '<br>' . ' Week of ' . $roster->rst_classDate;
    $emitter->cell_block( $desc ,'','colspan=99');
    $emitter->row_end();

    $emitter->row_start();
    $emitter->cell_block( 'Points','','rowspan=2');
    $emitter->cell_block(  'Chess','','colspan=3');
    $emitter->cell_block( 'Blitz','','colspan=3');
    $emitter->cell_block( 'Bughouse','','colspan=2');
    $emitter->row_end();
    $emitter->row_start();
    $emitter->cell_block( 'Win');
    $emitter->cell_block( 'Lost');
    $emitter->cell_block( 'Draw');
    $emitter->cell_block( 'Win');
    $emitter->cell_block( 'Lost');
    $emitter->cell_block( 'Draw');
    $emitter->cell_block( 'Win');
    $emitter->cell_block( 'Lost');
    $emitter->row_end();
}

function edit_tallySection($scriptData, $appGlobals, $emitter, $form) {
    $emitter->row_start();
    $emitter->cell_block( '@points' );
    $emitter->cell_block( $form->drForm_gen_field('@chessW' ));
    $emitter->cell_block( $form->drForm_gen_field('@chessL' ));
    $emitter->cell_block( $form->drForm_gen_field('@chessD' ));
    $emitter->cell_block( $form->drForm_gen_field('@blitzW' ));
    $emitter->cell_block( $form->drForm_gen_field('@blitzL' ));
    $emitter->cell_block( $form->drForm_gen_field('@blitzD' ));
    $emitter->cell_block( $form->drForm_gen_field('@bugW'   ));
    $emitter->cell_block( $form->drForm_gen_field('@bugL'   ));
    $emitter->row_end();
    $emitter->row_start();
    $emitter->cell_block( $form->drForm_gen_field( array('@submit','@cancel')),'','colspan=99');
    $emitter->row_end();
}

function edit_gamesAdd($gameSet) {
    foreach ($gameSet->taGameSet_gameArray as $game) {
        if ($game->game_originCode != GAME_ORIGIN_TALLY) {
            $this->edit_gameArray[] = $game;
        }
    }

}

function edit_gamesSection($scriptData, $appGlobals, $emitter) {
    $roster = $scriptData->sd_roster_program;
    $emitter->emit_nrLine('<br><br>');

    $emitter->table_start();
    $gameBatch = new stdData_gameUnit_batch;
    $gameBatch->gameBatch_addGame($scriptData->sd_edit_chessBatch);
    $gameBatch->gameBatch_addGame($scriptData->sd_edit_blitzBatch);
    $gameBatch->gameBatch_addGame($scriptData->sd_edit_bugBatch);
     //$this->edit_gamesAdd($scriptData->sd_edit_chessBatch->gaBatch_gameMap);
    //$this->edit_gamesAdd($scriptData->sd_edit_blitzBatch->gaBatch_gameMap);
    //$this->edit_gamesAdd($scriptData->sd_edit_bugBatch->gaBatch_gameMap  );
    if (count($gameBatch->gaBatch_gameMap) == 0) {
        return;
    }
    //=====  Detailed Games

    $emitter->row_start();
    $emitter->cell_block( 'Games','','colspan=99');
    $emitter->row_end();

    $emitter->row_start();
    $emitter->cell_block( 'When Created');
    $emitter->cell_block( 'Game Type');
    $emitter->cell_block( 'Win');
    $emitter->cell_block( 'Lost');
    $emitter->cell_block( 'Draw');
    $emitter->cell_block( 'Opponents','','colspan="5"');
    $emitter->cell_block( 'Edit');
    $emitter->row_end();
    foreach ($gameBatch->gaBatch_gameMap as $game) {
        $emitter->row_start();
        $emitter->cell_block(draff_dateTimeAsString( $game->game_whenCreated,'M j, g:i a' ));
        $emitter->cell_block( kcmRosterLib_getDesc_gameType($game->game_gameType) );
        $emitter->cell_block( $game->game_wins[0] );
        $emitter->cell_block( $game->game_losses[0] );
        $emitter->cell_block( $game->game_draws[0] );
        $s = '';
        //???????? need all kids loaded for below to work
        //  if ($game->game_opponents_count >= 1) {
        //     $ops = array();
        //     for ($k=0; $k<$game->game_opponents_count; ++$k) {
        //          $kid = $roster->($game->game_opponents_kidPeriodId[$k]);
        //          $kidPeriod = $scriptData->sd_roster_program->rst_cur_period->perd_getKidPeriodObject();
        //          $ops[$k] = $kidPeriod->kidPer_kidObject->rstKid_uniqueName;
        //      }
        //      $s = implode(', ', $ops);
        //  }
        //$s =  $game->game_gameId . ' - ' . . $s;
         $emitter->cell_block( $s,'','colspan="5"');
        // $emitter->cell_block( $game->game_atGameId );
         $emitter->cell_block( $appEmitter->getString_button('???','','gagid_'.$game->game_atGameId) );
       $emitter->row_end();
    }
    $emitter->table_end();
}

function edit_pointsSection($scriptData, $appGlobals, $emitter) {
     //=====  Detailed Points
    $emitter->emit_nrLine('<br><br>');

    $emitter->table_start();
    $emitter->row_start();
    $emitter->cell_block( 'Points','','colspan=99');
    $emitter->row_end();

    $emitter->row_start('<tr>');
    $emitter->cell_block( 'When Created');
    $emitter->cell_block( 'Type');
    $emitter->cell_block( 'Points');
    $emitter->cell_block( 'Category/Notes','','colspan="7"');
    $emitter->cell_block( 'Edit');
    $emitter->row_end('</tr>');

    //????? don't show tally points here
    if (!empty($scriptData->sd_edit_pointsBatch)) {
        foreach ($scriptData->sd_edit_pointsBatch as $points) {
            $emitter->row_start('<tr>');
            $emitter->cell_block(draff_dateTimeAsString( $points->pnt_whenCreated,'M j, g:i a' ));
            $emitter->cell_block( 'Points');
            $emitter->cell_block( $points->pnt_pointValue);
            //if (empty($points->pnt_note)) { //??????????????????????????
            //    $points->pnt_note .= '$$';
            //}
            $emitter->cell_block( $points->pnt_category . '-' . $points->pnt_note,'','colspan="7"');
            $emitter->cell_block(  $appEmitter->getString_button('??','','pntid_'.$points->pnt_pointsId) );
            $emitter->row_end('</tr>');
        }
    }
    $emitter->table_end();

}

} // end class

class appData_tally extends draff_appData {
public $sd_roster_program;
public $sd_roster_period;

public $sd_tallyGrid_report;

public $sd_edit_report;
public $sd_edit_kidPeriodId;
public $sd_edit_kid;
public $sd_edit_kidPeriod;
public $sd_edit_pointsTally;
public $sd_edit_pointsBatch;
public $sd_edit_chessBatch;
public $sd_edit_blitzBatch;
public $sd_edit_bugBatch;
public $sd_edit_chessTally;
public $sd_edit_blitzTally;
public $sd_edit_bugTally;

function __construct($appGlobals) {
    $this->sd_roster_program = new pPr_program_extended_forRoster($appGlobals);
    $this->sd_roster_program->rst_load_kids($appGlobals, $this->sd_edit_kidPeriodId);
    $this->sd_roster_period  = $this->sd_roster_program->rst_get_period();
}

function sd_grid_loadData($appGlobals) {
   // $this->sd_roster_program->rst_load_kids($appGlobals);
    $this->sd_roster_program->rst_load_pointsTally($appGlobals);
    $this->sd_roster_program->perd_load_gameTally($appGlobals);
}

function sd_grid_submit($appGlobals, $chain, $kidPeriodId) {
    $chain->chn_data_posted_set('#kidPeriodId', $kidPeriodId);
}

function sd_edit_loadData($appGlobals, $chain) {
    $this->sd_edit_kidPeriodId  = $chain->chn_data_posted_get('#kidPeriodId');
    $roster_program = $this->sd_roster_program;
    $roster_period = $this->sd_roster_program->rst_get_period();
    $roster_program->rst_load_kids($appGlobals, $this->sd_edit_kidPeriodId);
    $roster_period->rst_load_pointsTally($appGlobals, $roster->rst_classDate, $this->sd_edit_kidPeriodId );
    $roster_period->perd_load_gameTally($appGlobals, $roster->rst_classDate, $this->sd_edit_kidPeriodId );
    $roster_period->perd_load_gameBatch($appGlobals, $roster->rst_classDate, $this->sd_edit_kidPeriodId );
    $roster_period->perd_load_pointUnits($appGlobals, $roster->rst_classDate, $this->sd_edit_kidPeriodId );
    $this->sd_edit_kidPeriod = $roster_period->perd_get_kidPeriod($this->sd_edit_kidPeriodId);
    $this->sd_edit_kid = $roster_program->rst_get_kid($this->sd_edit_kidPeriod->kidPer_kidId);
    $this->sd_edit_pointsTally = $roster_period->perd_force_pointsObject ($this->sd_edit_kidPeriod->kidPer_tally_points);
    $this->sd_edit_pointsBatch = $this->sd_edit_kidPeriod->kidPer_batch_points;
    $this->sd_edit_chessBatch = $this->sd_edit_kidPeriod->kidPer_batch_chess;
    $this->sd_edit_blitzBatch = $this->sd_edit_kidPeriod->kidPer_batch_blitz;
    $this->sd_edit_bugBatch   = $this->sd_edit_kidPeriod->kidPer_batch_bug;
    $this->sd_edit_chessTally = $roster_period->perd_force_gameObject($this->sd_edit_kidPeriod->kidPer_tally_chess);
    $this->sd_edit_blitzTally = $roster_period->perd_force_gameObject($this->sd_edit_kidPeriod->kidPer_tally_blitz);
    $this->sd_edit_bugTally   = $roster_period->perd_force_gameObject($this->sd_edit_kidPeriod->kidPer_tally_bug);
}


} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appGlobals = new kcmRoster_globals();
$appGlobals->gb_forceLogin ();
$scriptData = new appData_tally;
$chain = new Draff_Chain( $scriptData, $appGlobals, 'kcmKernel_emitter' );

$scriptData->sd_roster_program = new pPr_program_extended_forRoster($appGlobals);

$chain->chn_form_register(FORM_TALLY_GRID,'appForm_tallyGrid');
$chain->chn_form_register(FORM_TALLY_EDIT,'appForm_tallyEditKid');
$chain->chn_form_launch(); // proceed to current step

exit;


?>