<?php

// roster-home.php

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

//include_once( 'roster-system-data-games.inc.php' );
//include_once( 'roster-results-game-edit.inc.php' );

include_once( 'roster-system-data-tally.inc.php' );
include_once( '../draff/draff-emitter-dom-engine.inc.php' );

Class appForm_rosterHome extends Draff_Form {
    private $reportEmit;
    public $semesterTally;

function drForm_processSubmit ( $scriptData, $appGlobals, $chain, $submit ) {
    kernel_processBannerSubmits( $appGlobals, $chain, $submit );
    $chain->chn_form_savePostedData();
    $chain->chn_ValidateAndRedirectIfError();
    $chain->chn_launch_continueChain(1);

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

public function drForm_initHtml( $scriptData, $appGlobals, $chain, $emitter ) {
    $appEmitter->set_theme( 'theme-select' );
    $appEmitter->set_title('Enter '.$appData->apd_chess_gameTypeDesc.' Results');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
    $appEmitter->set_menu_customize( $appChain, $appGlobals, '$results', $appData->apd_chess_gameTypeMenuKey );
    kcmRosterLib_setBannerSubTitle($emitter,$appGlobals, $scriptData->sd_roster_program,'Home');
    $appGlobals->gb_appMenu_init($chain, $emitter, $scriptData->sd_roster_program);
    $emitter->set_menu_customize( $appChain, $appGlobals );
    $emitter->addOption_styleTag('.co-period','width:10pt;border:1pt;');
    $emitter->addOption_styleTag('.co-first','width:30pt;border:1pt;');
    $emitter->addOption_styleTag('.co-last','width:30pt;border:1pt;');
    $emitter->addOption_styleTag('.co-grade','width:10pt;border:1pt;');
    $emitter->addOption_styleTag('.co-games','width:10pt;border:1pt;');
    $emitter->addOption_styleTag('.co-percent','width:10pt;border:1pt;');

 //   $scriptData->com_htmlOut_endOfPage($chain, $scriptData,$emitter, $form, $appGlobals);

}

function drForm_initFields( $scriptData, $appGlobals, $chain ) {
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
    $this->outMenuLink( $emitter, $chain->chn_url_build_chained_url('../kcm-gateway/kcm-gateway.php', TRUE) , 'Choose Another Program');
    $emitter->zone_end();
}

function drForm_outputContent ( $scriptData, $appGlobals, $chain, $emitter ) {
    $this->semesterTally = new kcm2_tallyBatch_semester($appGlobals,$form);
   //$this->semesterTally->tally_gui_sort_init($form,'srFirst','First');
   //$this->semesterTally->tally_gui_sort_init($form,'srLast','Last');
   //$this->semesterTally->tally_gui_sort_init($form,'srGrade','Grade',TRUE);
   //$this->semesterTally->tally_gui_sort_init($form,'srChess','Chess');
   //$this->semesterTally->tally_gui_sort_init($form,'srBlitz','Blitz');
   //$this->semesterTally->tally_gui_sort_init($form,'srBug','Bughouse');
    $this->semesterTally->tallyCT_readBundle($appGlobals,$scriptData->db_roster, $scriptData->sd_roster_program->rst_cur_period->perd_periodId);
    $emitter->zone_start('draff-zone-content-report');
    $this->periodHomeReport($emitter,$appGlobals);
    $emitter->zone_end();
}

function drForm_outputFooter ( $scriptData, $appGlobals, $chain, $emitter ) {
}

//function step_init_submit_accept( $scriptData, $appGlobals, $chain ) {
//}
//
//public function drForm_validate( $scriptData, $appGlobals, $chain ) {
//   //$this->semesterTally->tally_gui_filter_validate($appGlobals);
//    //$this->semesterTally->tally_gui_filter_validate($appGlobals);
//    $chain->chn_step_executeNext(1);
//}

function outMenuLink( $emitter, $pUrl, $pName, $pDesc = '') {
   $emitter->emit_nrLine( '<div><a class="draff-link-as-button" href="' . $pUrl . '">' . $pName . '</a></div>' );
}

function unNull(&$n) {
    if ($n === NULL) {
        $n = 0;
    }
}
function periodHomeReport($emitter, $appGlobals) {

    $emitter->table_start('draff-report',16);
    $emitter->table_body_start('');

    //$emitter->row_start('rpt-grid-row');
    //$emitter->cell_block('Period List');
    //$emitter->row_end();
    //$emitter->table_body_end('');
    //$emitter->table_end();

    //$emitter->table_start('@periodTable','draff-report');

    $emitter->table_head_start('draff-report');
    // ???? need forst line of Chess Blitz Bughouse

    $emitter->row_start();

    $emitter->cell_block('Per');
    $emitter->cell_block('First');
    $emitter->cell_block('Last');
    $emitter->cell_block('Grade');
    $emitter->cell_block('W');
    $emitter->cell_block('L');
    $emitter->cell_block('D');
    $emitter->cell_block('%');
    $emitter->cell_block('W');
    $emitter->cell_block('L');
    $emitter->cell_block('D');
    $emitter->cell_block('%');
    $emitter->cell_block('W');
    $emitter->cell_block('L');
    $emitter->cell_block('D');
    $emitter->cell_block('%');
    $emitter->row_end();
    $emitter->table_head_end();

    $emitter->table_body_start('');
    $sortedList = $this->semesterTally->tally_getSort();
    //$this->semesterTally->tally_kidList
    foreach ($sortedList as $sck) {
        $kidPeriod = $sck->taKid_kidPeriod;
        $kid = $kidPeriod->kidPer_kidObject;
        $ch = $sck->taKid_chess;
        $bz = $sck->taKid_blitz;
        $bu = $sck->taKid_bug;
        $this->unNull( $ch->taGameSet_win );
        $this->unNull( $ch->taGameSet_lost);
        $this->unNull( $ch->taGameSet_draw);
        $this->unNull( $ch->taGameSet_percent);
        $this->unNull( $bz->taGameSet_win);
        $this->unNull( $bz->taGameSet_lost);
        $this->unNull( $bz->taGameSet_draw);
        $this->unNull( $bz->taGameSet_percent);
        $this->unNull( $bu->taGameSet_win);
        $this->unNull( $bu->taGameSet_lost);
        $this->unNull( $bu->taGameSet_draw);
        $this->unNull( $bu->taGameSet_percent);

        $emitter->row_start('rpt-grid-row');
        $emitter->cell_block('*');
        $emitter->cell_block($kid->rstKid_firstName,'');
        $emitter->cell_block($kid->rstKid_lastName,'');
        $emitter->cell_block($kid->rstKid_gradeDesc,'');
        $emitter->cell_block($ch->taGameSet_win,'');
        $emitter->cell_block($ch->taGameSet_lost,'');
        $emitter->cell_block($ch->taGameSet_draw,'');
        $emitter->cell_block($ch->taGameSet_percent,'');
        $emitter->cell_block($bz->taGameSet_win,'');
        $emitter->cell_block($bz->taGameSet_lost,'');
        $emitter->cell_block($bz->taGameSet_draw,'');
        $emitter->cell_block($bz->taGameSet_percent,'');
        $emitter->cell_block($bu->taGameSet_win,'');
        $emitter->cell_block($bu->taGameSet_lost,'');
        $emitter->cell_block($bu->taGameSet_draw,'');
        $emitter->cell_block($bu->taGameSet_percent,'');
        $emitter->row_end();
        //$reportEmit->row_start('rpt-tr');
        //$reportEmit->cell_block('*','co-period');
        //$reportEmit->cell_block($kid->rstKid_firstName,'co-first');
        //$reportEmit->cell_block($kid->rstKid_lastName,'co-last');
        //$reportEmit->cell_block($kid->rstKid_gradeDesc,'co-grade');
        ////$this->outGame($this->semesterTally->tally_show_chess,$reportEmit,$sck->taKid_chess);
        ////$this->outGame($this->semesterTally->tally_show_blitz,$reportEmit,$sck->taKid_blitz);
        ////$this->outGame($this->semesterTally->tally_show_bug,$reportEmit,$sck->taKid_bug);
        //$this->outGame(TRUE,$reportEmit,$sck->taKid_chess);
        //$this->outGame(TRUE,$reportEmit,$sck->taKid_blitz);
        //$this->outGame(TRUE,$reportEmit,$sck->taKid_bug);
        //$reportEmit->row_end();
    }
    $emitter->table_body_end();
    $emitter->table_end();

    // $emitter->export_as_html();

    //      $reportEmit->emit_rptHeader_start();
    //      $reportEmit->row_start();
    //      $reportEmit->cell_block('Per','co-period','rowspan="2"');
    //      $reportEmit->cell_block('First','co-first','rowspan="2"');
    //      $reportEmit->cell_block('Last','co-last','rowspan="2"');
    //      $reportEmit->cell_block('Grade','co-grade','rowspan="2"');
    //      //if ($this->semesterTally->tally_show_chess) {
    //          $reportEmit->cell_block('Chess','loc-thickLeft loc-thickRight co-grade','colspan="4"');
    //      //}
    //      //if ($this->semesterTally->tally_show_blitz) {
    //          $reportEmit->cell_block('Blitz','loc-thickLeft loc-thickRight co-grade','colspan="4"');
    //      //}
    //      //if ($this->semesterTally->tally_show_bug) {
    //          $reportEmit->cell_block('Bughouse','loc-thickLeft loc-thickRight co-grade','colspan="4"');
    //      //}
    //      $reportEmit->row_end();
    //
    //      $reportEmit->row_start();
    //      //if ($this->semesterTally->tally_show_chess) {
    //          $reportEmit->cell_block('W','loc-thickLeft co-games');
    //          $reportEmit->cell_block('L','co-games');
    //          $reportEmit->cell_block('D','co-games');
    //          $reportEmit->cell_block('%','loc-thickRight co-percent');
    //      //}
    //      //if ($this->semesterTally->tally_show_blitz) {
    //          $reportEmit->cell_block('W','loc-thickLeft co-games');
    //          $reportEmit->cell_block('L','co-games');
    //          $reportEmit->cell_block('D','co-games');
    //          $reportEmit->cell_block('%','loc-thickRight co-percent');
    //      //}
    //      //if ($this->semesterTally->tally_show_bug) {
    //          $reportEmit->cell_block('W','loc-thickLeft co-games');
    //          $reportEmit->cell_block('L','co-games');
    //          $reportEmit->cell_block('D','co-games');
    //          $reportEmit->cell_block('%','loc-thickRight co-percent');
    //      //}
    //      $reportEmit->row_end();
    //      $reportEmit->emit_rptHeader_end();
    //      $rst_cur_period = $appGlobals->gbx_roster->rst_cur_period;
    //      $sortedList = $this->semesterTally->tally_getSort();
    //      //$this->semesterTally->tally_kidList
    //      foreach ($sortedList as $sck) {
    //          $kidPeriod = $sck->taKid_kidPeriod;
    //          $kid = $kidPeriod->kidPer_kidObject;
    //          $reportEmit->row_start('rpt-tr');
    //          $reportEmit->cell_block('*','co-period');
    //          $reportEmit->cell_block($kid->rstKid_firstName,'co-first');
    //          $reportEmit->cell_block($kid->rstKid_lastName,'co-last');
    //          $reportEmit->cell_block($kid->rstKid_gradeDesc,'co-grade');
    //          //$this->outGame($this->semesterTally->tally_show_chess,$reportEmit,$sck->taKid_chess);
    //          //$this->outGame($this->semesterTally->tally_show_blitz,$reportEmit,$sck->taKid_blitz);
    //          //$this->outGame($this->semesterTally->tally_show_bug,$reportEmit,$sck->taKid_bug);
    //          $this->outGame(TRUE,$reportEmit,$sck->taKid_chess);
    //          $this->outGame(TRUE,$reportEmit,$sck->taKid_blitz);
    //          $this->outGame(TRUE,$reportEmit,$sck->taKid_bug);
    //         $reportEmit->row_end();
    //      }
    //      //foreach ($rst_cur_period->perd_kidPeriodMap as $kidPeriod) {
    //      //    $kid = $kidPeriod->kidPer_kidObject;
    //      //    $reportEmit->row_start();
    //      //    $reportEmit->cell_block('*','co-period');
    //      //    $reportEmit->cell_block($kid->rstKid_firstName,'co-first');
    //      //    $reportEmit->cell_block($kid->rstKid_lastName,'co-last');
    //      //    $reportEmit->cell_block($kid->rstKid_gradeDesc,'co-grade');
    //      //    $reportEmit->row_end();
    //      //}
    //      $reportEmit->table_end();
}

function outGame($emitter, $show, $scgt) {
    if ( $show ) {
        $emitter->cell_block($scgt->taGameSet_win ,'loc-thickLeft co-games');
        $emitter->cell_block($scgt->taGameSet_lost ,'co-games');
        $emitter->cell_block($scgt->taGameSet_draw ,'co-games');
        $emitter->cell_block($scgt->taGameSet_percent .'%','loc-thickRight co-percent');
    }
}

} // end class

class appData_rosterHome extends draff_appData {
//public  $com_scores;
public $sd_roster_program;
public $kcmEmitter = NULL;

function __construct() {
}

function sd_formData_get( $appGlobals, $chain ) {
}

function sd_formData_validate( $appGlobals, $chain ) {
}


} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
rc_session_initialize();

$appGlobals = new kcmRoster_globals();
$appGlobals->gb_forceLogin ();

$scriptData = new appData_rosterHome();
$chain = new Draff_Chain( $scriptData, $appGlobals, 'kcmKernel_emitter' );

$scriptData->sd_roster_program = new pPr_program_extended_forRoster($appGlobals);
$scriptData->sd_roster_program->rst_load_rosterData($appGlobals, $chain);

$chain->chn_form_register(1,'appForm_rosterHome');
$chain->chn_form_launch(); // proceed to current step

exit;

?>