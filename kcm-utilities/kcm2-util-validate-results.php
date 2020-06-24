<?php

// kcm2-util-validate-results.php

ini_set("memory_limit","800M");  

ob_start();  // output buffering (needed for redirects, header changes)

include_once( '../rc_defines.inc.php' );
include_once( '../rc_messages.inc.php' );
include_once( '../rc_database.inc.php' );
include_once( '../rc_admin.inc.php' );
//include_once( 'kcm-libKcmFunctions.inc.php' );
//include_once( 'kcm-roster.inc.php' );
//include_once( 'kcm-roster_objects.inc.php' );
//include_once( 'rcSys_functions.inc.php' );
include_once( 'rsm-emitter.inc.php' ); 
include_once( 'rsm-functions.inc.php' ); 

include_once( 'kcm2-util-validate-results-data-inc.php'); 
include_once( 'kcm2-util-validate-results-process-inc.php'); 

rc_session_initialize();

//rcSys_Start('KCM Validate Data','KCM - Validate and Fix Games and Points');
local::out_htmlHead();

$dbSuccess = rc_openGlobalDatabase();
if ( ! $dbSuccess) {
	print  "Database connection error.";
    exit;
}

$db = rc_getGlobalDatabaseObject();

$data = new kcm2_data_global($db);

local::getAndShowFilters($data);

$readData = new kcm2_read_data;
$readData->rd_read_data($data);

$validateAndTotal = new kcm2_validate_and_total;
$validateAndTotal->validate_all($data);

$report = new kcm2_report_all;
$report->report_out($data);

//$validateResults = new kcm2_validateResults;
//$validateResults->process($db);
exit;

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Local functions
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

class local {

function pointsArray_toString(&$pointsArray) {
    if ( is_array($pointsArray) ) {
        $c = count($pointsArray);
        $last = 0;
        for ($i=1; $i<$c; ++$i) {
            if ($pointsArray[$i] != 0) {
                $last = $i;
            }
        }
        $pre = '';
        $s = '[';
        for ($i=0; $i<=$last; ++$i) {
            $s .= $pre . $pointsArray[$i];
            $pre = ',';
        }
        return $s . ']';
    }
    else
        return $pointsArray; // should never happen
}    

function errorCheck_set(&$errors, $message) {
    $errors = ( ($errors=='') ? ($message) : ($errors . ', AND ' . $message) );
}    

function errorStyle($style, $isError, $errStyle=' lc-error') {
    return $isError ? ($style . $errStyle) : $style;
}    

function errorCheck_id(&$errors, $index, $message) {
    if ( $index < 1 ) {
        self::errorCheck_set($errors, $message);
    }    
}

function concatSep($str, $end, $sep=' ') {
    return  ($str=='') ? $end : ($str . $sep . $end);
}    

function errorCheck_gameType( &$errors, $gameType, $message= 'Invalid Game Type') {
    if (($gameType<0) or ($gameType>2) ) {
        self::errorCheck_set($errors, $message);
    }    
}

function getBoolParam($pTag, $pDefault=NULL) {
    if (isset($_GET[$pTag])) {
        return TRUE;
    }    
    return $pDefault;
}
function getParam($pTag, $pDefault='') {
    if (isset($_GET[$pTag])) {
        return $_GET[$pTag];
    }    
    return $pDefault;
}


function checkbox($checked,$name,$desc, $more='') {
if ($checked)
    print '<input type="checkbox" name="'.$name.'" value="T" checked'.$more.'>'.$desc.'<br>';
else    
    print '<input type="checkbox" name="'.$name.'" value="T"'.$more.'>'.$desc.'<br>';
}

function radio($value,$name, $key, $desc) {
    $checked = ($value==$key) ? ' checked':'';
    $emitter->emit_line('<input type="radio" name="' . $name . '" value="' . $key. '"'.$checked.'>'.$desc);
}

function getKey($program, $period, $kidPeriod, $gameType) {
     return   $program .'-'. $period .'-'. $kidPeriod .'-'. $gameType;
}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Validate Results
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

function out_htmlHead() {    
print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
print PHP_EOL. '<html>';
print PHP_EOL. '<head>';
print PHP_EOL. '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
print PHP_EOL. '<title>KCM Validate Data [JARED-LOCAL]</title>';
//print PHP_EOL. '<link rel="icon" type="image/x-icon" href="images/kidchess-icon.jpg">';
//print PHP_EOL. '<link rel="stylesheet" type="text/css" href="../css/rc_common.css"/>';
//print PHP_EOL. '<link rel="stylesheet" type="text/css" href="../css/rc_admin.css"/><style type="text/css">html {background-image: url("../kcm-kernel/images/raccoon_jared-background.jpg");background-repeat:repeat;}</style>';
kcm2_results_validation_report::vr_print_report_styles();
print PHP_EOL. '<style>';
print PHP_EOL. 'table {border-collapse:collapse;border-spacing:0;empty-cells:show;border-style:none}';
print PHP_EOL. 'td {border:1px solid #555555; padding:2px 8px 2px 6px;vertical-align:text-top;}';
print PHP_EOL. 'td.lc-left {border-left: 3px solid black}';
print PHP_EOL. 'td.lc-right {border-right:3px solid black}';
print PHP_EOL. 'td.lc-top {border-top: 3px solid black}';
print PHP_EOL. 'td.lc-bot {border-bottom:3px solid black}';
print PHP_EOL. 'td.lc-left2 {border-left: 6px double black}';
print PHP_EOL. 'td.lc-right2 {border-right:6px double black}';
print PHP_EOL. 'td.lc-bot2 {border-bottom:6px double black}';
print PHP_EOL. 'td.lc-r1c1 {background-color:#eeeeee}';
print PHP_EOL. 'td.lc-r1c2 {background-color:#eeeeee}';
print PHP_EOL. 'td.lc-r2c1 {background-color:#ffffff}';
print PHP_EOL. 'td.lc-r2c2 {background-color:#ffffff}';
print PHP_EOL. 'td.lc-sep {background-color:blue}';
print PHP_EOL. 'td.lc-r3c1 {background-color:#ccffcc}';
print PHP_EOL. 'td.lc-hd {background-color:aqua; font-size:10pt;}';
print PHP_EOL. 'td.lc-hdL {background-color:aqua; font-size:14pt; font-weight:bold;}';
print PHP_EOL. 'td.lc-gamPer {background-color:lime}';
print PHP_EOL. 'td.lc-error {background-color: #ff8888;}';
print PHP_EOL. 'td.lc-warn {background-color: #ffcccc;}';
print PHP_EOL. 'td.lc-changed {background-color: lime;}';
print PHP_EOL. 'td.lc-title {font-size:16pt; font-weight:bold}';
print PHP_EOL. 'hr {left: 0pt;margin-top:5pt;margin-bottom:15pt;margin-left:0pt;}';
print PHP_EOL. 'div.sectionTitle {font-size:14pt;font-weight:bold;background-color:#cccc; padding: 3pt 5pt 3pt 5pt}';
print PHP_EOL. '</style>';
print PHP_EOL. '</head>';
print PHP_EOL. '<body class="rsm-zone-body-normal">';
}

function lc_calc_game_points($gameType, $win, $draw) {
    switch ($gameType) {
        case 0:  $wp = 10; $dp =  5; break;
        case 1:  $wp =  5; $dp =  3; break;
        case 2:  $wp =  3; $dp =  2; break;
        default: $wp =  0; $dp =  0; break;
    }
    return ($wp * $win) + ($dp * $draw); 
}

function getAndShowFilters($data) {
    $data->gbd_doFix_games = local::getBoolParam('fixGames',FALSE);
    $data->gbd_doFix_points = local::getBoolParam('fixPoints',FALSE);
    $data->gbd_doFix_invalid = local::getBoolParam('fixInvalid',FALSE);
    $data->gbd_viewRepairs = local::getBoolParam('ViewRep',TRUE);
    $data->gbd_viewDetails = local::getBoolParam('ViewDet',FALSE);
    $data->gbd_viewSummary = local::getBoolParam('ViewSum',FALSE);
    $data->gbd_sem_option = local::getParam('sem',$data->gbd_sem_option);
    print PHP_EOL.'<form action="kcm2-util-validate-results.php">';

    $emitter->emit_table_start();
    
    $emitter->emit_row_start();
    $emitter->emit_cell('Validation Options','lc-hd lc-title','colspan="99"'); 
    $emitter->emit_row_end();
    
    $emitter->emit_row_start();
    
    $emitter->emit_cell_start(); 
    $emitter->emit_line('<div class="sectionTitle">Repair (Fix) Options</div>');
    local::checkbox($data->gbd_doFix_games,'fixGames',   'Fix Game-Accum Records');
    local::checkbox($data->gbd_doFix_points,'fixPoints',   'Fix Kid-Period (Point) Records');
    local::checkbox($data->gbd_doFix_invalid,'fixInvalid',   'Delete Invalid Records');
    $emitter->emit_cell_end(); 
    
    $emitter->emit_cell_start(); 
    $emitter->emit_line('<div class="sectionTitle">Semester Options</div>');
    local::radio($data->gbd_sem_option, "sem", "cur", 'Current Semester<br>');
    //local::radio($data->gbd_sem_option, "sem", "fs", 'Fall-Spring<br>');
    //local::radio($data->gbd_sem_option, "sem", "all", 'All<br>');
    //local::radio($data->gbd_sem_option, "sem", "se", 'Specific Event');
    //$emitter->emit_line('<input type="radio" name="sem" value="sc"> Current Semester, Specific School');
    $emitter->emit_cell_end(); 
    
    $emitter->emit_cell_start(); 
    $emitter->emit_line('<div class="sectionTitle">View Options</div>');
    local::checkbox($data->gbd_viewRepairs,'ViewRep',   'View error repairs',' disabled readonly');
    local::checkbox($data->gbd_viewDetails,'ViewDet',   'View error details');
    local::checkbox($data->gbd_viewSummary,'ViewSum',   'View complete summary');
    $emitter->emit_cell_end(); 
    
    $emitter->emit_row_end();
    
    $emitter->emit_row_start();
    $emitter->emit_cell_start('lc-hd','colspan="99"'); 
    print '<input type="submit" name="submit" value="Submit">';
    $emitter->emit_cell_end(); 
    $emitter->emit_row_end();
    
    $emitter->emit_table_end();
    print '</form>'; 
}

function getCurrentEvents($data) {
}

} // end class

class notsurewhathappened {
    
function doit() {
    
$games_validate_oneKidPeriod = local::getBoolParam('gameVal',TRUE);

if (local::getBoolParam('submit', FALSE)) {
    $gameValidate = local::getBoolParam('gameVal',FALSE);
    $gameGood = local::getBoolParam('gameGood',FALSE);
    $gameBad = local::getBoolParam('gameBad',FALSE);
    $gameFix = local::getBoolParam('gameFix',FALSE);
    $pointValidate = local::getBoolParam('pointVal',FALSE);
    $pointGood = local::getBoolParam('pointGood',FALSE);
    $pointBad = local::getBoolParam('pointBad',FALSE);
    $pointFix = local::getBoolParam('pointFix',FALSE);
}
else {
    $gameValidate = TRUE;
    $gameGood = FALSE;
    $gameBad = TRUE;
    $gameFix = FALSE;
    $pointValidate = TRUE;
    $pointGood = FALSE;
    $pointBad = TRUE;
    $pointFix = FALSE;
}
print PHP_EOL.'<form action="util-validate-results.php">';
print '<hr style="margin-left:0px;margin-top:10px;margin-bottom:10px;width:100%;">';
local::checkbox($gameValidate, 'gameVal',   'Validate Games');
local::checkbox($gameGood,'gameGood',  'Show good Game Total Records');
local::checkbox($gameBad,'gameBad',   'Show bad Game Total Records');
local::checkbox($gameFix,'gameFix',   'Fix bad Game Records');
print '<hr style="margin-left:0px;margin-top:10px;margin-bottom:10px;width:100%;">';
local::checkbox($pointValidate,'pointVal',  'Validate Points');
local::checkbox($pointGood,'pointGood', 'Show good Points Total Records');
local::checkbox($pointBad,'pointBad',  'Show bad Points Total Records');
local::checkbox($pointFix,'pointFix', 'Fix bad Points Records');
print '<hr style="margin-left:0px;margin-top:10px;margin-bottom:10px;width:100%;">';

print '<input type="submit" name="submit" value="Submit">';
print '</form>'; 
print '<br><br>';

if ($gameValidate) {
    $games = new kcm_validateGames;
    $games->setDisplayGood($gameGood);
    $games->setDisplayBad($gameBad);
    $games->setFixBad($gameFix);
    $games->setProgramIdFilter(0);  //0=all
    $games->validateGameTotals ();
    print '<br>';
}    

if ($pointValidate) {
    $points = new kcm_validatePoints;
    $points->setDisplayGood($pointGood);
    $points->setDisplayBad($pointBad);
    $points->setFixBad($pointFix);
    $points->setProgramIdFilter(0);  //0=all
    $points->validatePointTotals();
}    

}

}  // end class

class kcm2_game_report {
    
public $gameTotals_kidPeriod;
public $gdb_grand_totals;

function __construct() {
    $this->gameTotals_kidPeriod = new kcm2_standard_totals_group;
    $this->gdb_grand_totals     = new kcm2_standard_totals_group;
}    

function vr_games_report($data) {
    foreach ($data->gbd_list_programs as $curProgramObj) {
        $this->vr_games_program($data, $curProgramObj);
    }
    print '<br><br>Total Gme Kids = '.$this->tot_games_kids.'<br><br>';
}

function vr_games_program($data, $program) {
    $this->prg_stdTotals->sTot_clear();
    if ( count($program->prg_list_periods)  == 0) {
        return;
    }
    foreach ($program->prg_list_periods as $curPeriodObj) {
        $this->vr_games_period($data, $curPeriodObj);
    }
}
    
function vr_games_period($data, $period) {
    $this->per_stdTotals->sTot_clear();
    if ( count($period->per_list_kidPeriod)  == 0) {
        return;
    }
    foreach ($period->per_list_kidPeriod as $curKidPeriodObj) {
        $this->vr_games_kidPeriod($data, $curKidPeriodObj);
    }
}
    
function vr_games_kidPeriod($data, $kidPeriod) {
}

function vr_games_kid_init_gamePoints($data, $kidPeriod) {
    $this->per_stdTotals->sTot_add_totals($this->gameTotals_kidPeriod);
    $this->prg_stdTotals->sTot_add_totals($this->gameTotals_kidPeriod);
    $this->gdb_grand_totals->sTot_add_totals($this->gameTotals_kidPeriod);
}
    
function vr_games_kid_validation($data, $kidPeriod) {
    // printed only if there is a problem with the validations
    $this->vr_games_kid_init_gamePoints($data, $kidPeriod);    
    
    $this->vr_games_validation_gameType($kidPeriod, $kidPeriod->kdPer_games_chess, $error_points);
    $this->vr_games_validation_gameType($kidPeriod, $kidPeriod->kdPer_games_blitz, $error_points);
    $this->vr_games_validation_gameType($kidPeriod, $kidPeriod->kdPer_games_bug, $error_points);
    
    $this->gameCount = 0;
    
     $this->countChess += $this->games_validate_oneGameArray($kidPeriod,$kidPeriod->kdPer_games_chess,$kidPeriod->kdPer_accum_chess, 'Chess',10);
    $this->countBlitz += $this->games_validate_oneGameArray($kidPeriod,$kidPeriod->kdPer_games_blitz,$kidPeriod->kdPer_accum_blitz, 'Blitz',5);
    $this->countBug += $this->games_validate_oneGameArray  ($kidPeriod,$kidPeriod->kdPer_games_bug,  $kidPeriod->kdPer_accum_bug, 'Bughouse',3);
    if ($kidPeriod->kp_gamePointsTotal != $kidPeriod->kdPer_points_kcm2_total) {
        if ($this->doFix) {
            $query = "UPDATE `ro:kid_period` SET `rkPe:KcmGamePoints`='".$kidPeriod->kp_gamePointsTotal. "' WHERE `rKPe:kdPer_kidPeriodId`='".$kidPeriod->kdPer_kidPeriodId."'";
            $result = $this->db->rc_query($query);
            if ($result === FALSE) {
                print 'Error 201 - save game points ' . $query;
                exit;
            }
        }
        $this->games_outRow_error(' Game Points do not match game points in kid Period Record (Fix assumes game records are valid, but it could be invalid game records)');
        $this->games_outRow_TotalGamePointsComputed($kidPeriod);
        $this->games_outRow_TotalGamePointsRecord($kidPeriod);
     //   print('<br>error in game points '.$kidPeriod->kp_gamePointsTotal .' - ' . $kidPeriod->kdPer_points_kcm2_total);
    }
}

function vr_games_validation_gameType($data, $kidPeriod, $gameArray, $gameTotals, $gameDesc) {
    // printed only if there is a problem with the validations
    // possible errors: (1) record messages (2) game totals<>gameTotals (3) GamePoints<>kidPeriodGamePoints (4) >1 total records
    $error_records = FALSE;
    $error_games = FALSE;
    $error_points = FALSE;
    $totWin = 0;
    $totLost = 0;
    $totDraw = 0;
    $totPoints = 0;
    foreach ( $gameArray as $game) {
        if ($game->gm_errors!==FALSE) {
            $error_records = TRUE;
        }
        $totWin += $game->gm_win;
        $totLost += $game->gm_lost;
        $totDraw += $game->gm_draw;
        $totPoints += $game->gm_points;
    }
    $error_games = ($totWin!=$gameTot->gac_win) or ($totLost!=$gameTot->gac_lost) or ($totDraw!=$gameTot->gac_draw);
    
    $countTotals = count($gameTotals);
    $countGames = count($gameArray);
    if ( ($countTotals==0) and ($countGames==0) ) {
        // no game or gameTotal records
        return 0;
    }
    if ($countGames==0 ) {
        return 0;   // not sure if this is error when there is a total record (depends which semester???)
    }
    if ( ($countTotals==0) and ($countGames>=1) ) {
        $this->games_outRow_mainHeader();  // not if repeat (once per report)
        $this->games_outRow_kidPeriodHeader($kidPeriod);  // none if repeat (once per kid-period)
        games_outRow_errorSerious($gameDesc . ' Totals - Serious error - No '.$gameDesc.' totals record when there are '.$gameDesc.' games ');
        $this->games_outRows_gameRecordArray($kidPeriod,$gameArray,$gameDesc);
        return 1;
    }
    $gameTot = $gameTotals[0];
    foreach($gameArray as $game) {
    }
    if ( $totError ) {
        $this->games_outRow_mainHeader();  // not if repeat (once per report)
        $this->games_outRow_kidPeriodHeader($kidPeriod);  // none if repeat (once per kid-period)
        $this->games_outRows_gameRecordArray($kidPeriod,$gameArray,$gameDesc);
 		$this->games_outRow_error(' Game Total Record is incorrect - it is not the total of '.$gameDesc.' game records (Fix assumes game records are valid, but it could be invalid game records)');
        $this->games_outRow_totalsComputed($gameDesc,$totWin,$totLost,$totDraw);
        $this->games_outRow_totalsRecord($kidPeriod,$gameTotals, $gameDesc);
        if ($this->doFix) {
            $query = "UPDATE `gp:gametotals` SET `gpGT:GamesWon`='{$totWin}', `gpGT:GamesLost`='{$totLost}', `gpGT:GamesDraw`='{$totDraw}' WHERE `gpGT:GameTotalId`='{$gameTot->gameTotalId}'";
           $result = $this->db->rc_query($query);
            if ($result === FALSE) {
                print 'Error 201 - save game points ' . $query;
                exit;
            }
        }
        return 1;
    }
    if ($kidPeriod->kp_gamePointsTotal != $kidPeriod->kdPer_points_kcm2_total) {
        // need to print games even when no errors due to future error of total game points inconsistent with game records
        $this->games_outRow_mainHeader();  // not if repeat (once per report)
        $this->games_outRow_kidPeriodHeader($kidPeriod);  // none if repeat (once per kid-period)
        $this->games_outRows_gameRecordArray($kidPeriod,$gameArray,$gameDesc);
    }
   return 0;
}

function games_out_gameGroup($kidPeriod,$gameArray,$gameTotals, $gameDesc,$errMessage) {
    $curPeriodId = $kidPeriod->periodId;
    $curPeriodObj = $this->periods->periodArray[$curPeriodId];
    $this->games_outRow_mainHeader();  // not if repeat (once per report)
    $this->games_outRow_kidPeriodHeader($kidPeriod);  // none if repeat (once per kid-period)
    if (count($gameArray) >=1) {
        $win = 0;
        $lost = 0;
        $draw = 0;   
        foreach($gameArray as $game) {
            $win += $game->win;
            $lost += $game->lost;
            $draw += $game->draw;
        } 
        $this->games_outRows_gameRecordArray($kidPeriod,$gameArray, $gameDesc);        
    }    
    if ($gameTotals<>NULL) {
 		$this->games_outRow_error(' Game Total Record is incorrect - it is not the total of '.$gameDesc.' game records (Fix assumes game records are valid, but it could be invalid game records)');
        $this->games_outRow_totalsComputed($gameDesc,$win,$lost,$draw);
        $this->games_outRow_totalsRecord($kidPeriod,$gameTotals, $gameDesc);
   }
}

function games_validate_addGamePoints($kidPeriod, $gameArray) {
    foreach ($gameArray as $game) {
        $kidPeriod->kp_gamePointsTotal += $game->gi_gamePoints;
    }
}

function games_outRow_kidPeriodHeader($kidPeriod) {
     // printed only if there is a problem with the validations and only once for each kid
   if ($kidPeriod->kdPer_kidPeriodId == $this->out_curKidPeriodId)
        return;
    $this->out_curKidPeriodId = $kidPeriod->kdPer_kidPeriodId;
    $curPeriodId = $kidPeriod->periodId;
    $curPeriodObj = $this->periods->periodArray[$curPeriodId];
    $this->outRowStart();    
    $this->outCell('<span style="display:inline-block; font-size:18pt; font-weight:bold;padding: 4pt 8pt 4pt 0pt;">'.$kidPeriod->kdPer_kidName . ' -  ' . $curPeriodObj->schoolName . ' - ' . $curPeriodObj->periodName . ' - ' . rc_getSemesterNameFromCode($curPeriodObj->semCode) . ' - ' . rc_getYearNameFromYearAndSemesterCodes($curPeriodObj->schoolYear, $curPeriodObj->semCode) . '</span>' , ' colspan="99" style="background-color:yellow"');
    $this->outRowEnd();
    $this->outRowStart();
    //$this->outCell($errMessage, ' colspan="99" style="background-color:yellow"');
    //$this->outRowEnd();
}

function games_outRows_gameRecordArray($kidPeriod,$gameArray, $gameDesc) {
    foreach($gameArray as $game) {
        $this->outRowStart();
        $this->outCell($gameDesc . ' Game');
        $this->outCell($game->gameId);
        $this->outCell($kidPeriod->kdPer_kidName);
        $this->outCell($gameDesc);
        $this->outCell($game->win);
        $this->outCell($game->lost);
        $this->outCell($game->draw);
        $this->outCell($game->gi_gamePoints);
        $this->outCell($game->classDate);
        $this->outCell($game->whenCreated);
        $this->outRowEnd();
    }
}

function games_outRow_mainHeader() {
   if ($this->out_didMainHeader) {
       return;
   }    
    $this->out_didMainHeader = TRUE;
    $style=' style=" background-color:#ccccff;font-weight:bold;"';
    $this->outRowStart();
    $this->outCell('Record<br>Type', $style);
    $this->outCell('Record<br>id', $style);
    $this->outCell('Kid Name', $style);
    $this->outCell('Game<br>Type', $style);
    $this->outCell('Wins', $style);
    $this->outCell('Lost', $style);
    $this->outCell('Draw', $style);
    $this->outCell('Points', $style);
    $this->outCell('Class<br>Date', $style);
    $this->outCell('When<br>Created', $style);
    $this->outRowEnd();
}

function games_outRow_totalsComputed($gameDesc,$win,$lost,$draw) {
        $this->outRowStart();
        $this->outCell($gameDesc . ' (total)');
        $this->outCell('');
        $this->outCell($gameDesc . ' (computed)');
        $this->outCell('');
        $this->games_out_fixToCell($win);
        $this->games_out_fixToCell($lost);
        $this->games_out_fixToCell($draw);
        $this->outCell('*');
        $this->outCell('');
        $this->outCell('');
        $this->outRowEnd();
}

function games_validate_all() {
   $this->out_curKidPeriodId = NULL;
   $this->out_didMainHeader = FALSE;
   print '<br>Starting validation';
    $this->countChess = 0;
    $this->countBlitz = 0;
    $this->countBug = 0;
    foreach ($this->kidPeriods->kidPeriodArray as $kidPeriod) {
        $this->games_validate_oneKidPeriod($kidPeriod);
    }
   $this->outEnd();
    print '<br>';
    print '<br>Error Count Chess = ' . $this->countChess ;
    print '<br>Error Count Blitz = ' . $this->countBlitz ;
    print '<br>Error Count Bug = ' . $this->countBug ;
    $this->outRowCount=0;
    $countPoints = 0;
    foreach ($this->kidPeriods->kidPeriodArray as $kidPeriod) {
         $countPoints += $this->validatePoints($kidPeriod);
   }
    print '<br>Error Count Points = ' . $countPoints ;
}
function games_outRow_totalsRecord($kidPeriod,$gameTotals, $gameDesc) {
    $gameTotCount = count($gameTotals);
    if ($gameTotCount == 0) {
        return;
    }    
    if ($gameTotCount > 1) {
        $this->games_outRow_errorSerious($gameDesc . ' Totals - Serious error - more than one game Totals for game Type');
    }
    foreach($gameTotals as $gameTot) {
        $this->outRowStart();
        $this->outCell($gameDesc . ' Game Total');
        $this->games_out_fixToCell($gameTot->gameTotalId);
        $this->outCell('');
        $this->outCell($gameDesc);
        $this->games_out_fixFromCell($gameTot->win);
        $this->games_out_fixFromCell($gameTot->lost);
        $this->games_out_fixFromCell($gameTot->draw);
        $this->outCell('*');
        $this->outCell('');
        $this->outCell('');
        $this->outRowEnd();
    }
}

function games_outRow_TotalGamePointsComputed($kidPeriod) {
    $this->outRowStart();
    $this->outCell(' Computed Game Points');
    $this->outCell('');
    $this->outCell('');
    $this->outCell('');
    $this->outCell('');
    $this->outCell('');
    $this->outCell('');
    $this->games_out_fixToCell($kidPeriod->kp_gamePointsTotal);
    $this->outCell('');
    $this->outCell('');
    $this->outRowEnd();
}

function games_outRow_TotalGamePointsRecord($kidPeriod) {
    $this->outRowStart();
    $this->outCell('Kid Period Game Points');
    $this->games_out_fixToCell($kidPeriod->kdPer_kidPeriodId);
    $this->outCell('');
    $this->outCell('');
    $this->outCell('');
    $this->outCell('');
    $this->outCell('');
    $this->games_out_fixFromCell($kidPeriod->kdPer_points_kcm2_total);
  //  $this->outCell($kidPeriod->kdPer_points_kcm2_total,'style="background-color:#ffcccc"');
    $this->outCell('');
    $this->outCell('');
    $this->outRowEnd();
}

function games_outRow_error($errMessage) {
 		$this->outRowStart();
		$this->outCell($errMessage, ' colspan="99" style="background-color:#ffcccc"');
		$this->outRowEnd();
}

function games_outRow_errorSerious($errMessage) {
 		$this->outRowStart();
		$this->outCell($errMessage, ' colspan="99" style="background-color:red;color:yellow;font-size:18pt;font-weight:bold');
		$this->outRowEnd();
}
function games_out_fixToCell($content) {
$this->outCell('<span style="display:inline-block; border:1px solid red; padding: 2px; font-size:18pt;background-color:#88ff88">' . $content . '</span>');    
}
function games_out_fixFromCell($content) {
$this->outCell('<span style="display:inline-block; border:1px solid red; padding: 2px; font-size:18pt;background-color:#ffcccc">' . $content . '</span>');    
}

}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Results Report
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

class kcm2_results_validation_report {
    
function __construct() {
}    

function vr_print_report_styles() {
}
    
function vr_print_report($data) {
    $this->gamePoints_gamesTotal= 0;
    $this->gamePoints_kidsTotal = 0;
    print '<br>Report<br>';
    if ( count($data->gbd_list_programs)  == 0) {
        return;
    }
    $this->vr_games_report($data);
    $this->vr_points_report($data);
    
}
    
function vr_points_report($data) {
    print '<br>Points Report<br>';
    foreach ($data->gbd_list_programs as $curProgramObj) {
        $this->vr_points_program($data, $curProgramObj);
    }
    print '<br><br>Total Point Kids = '.$this->tot_points_kids.'<br><br>';
}

function vr_points_program($data, $program) {
    if ( count($program->prg_list_periods)  == 0) {
        return;
    }
    foreach ($program->prg_list_periods as $curPeriodObj) {
        $this->vr_points_period($data, $curPeriodObj);
    }
}

function vr_points_period($data, $period) {
   if ( count($period->per_list_kidPeriod)  == 0) {
        return;
    }
    foreach ($period->per_list_kidPeriod as $curKidPeriodObj) {
        $this->vr_points_kidPeriod($data, $curKidPeriodObj);
    }
}
    
function vr_points_kidPeriod($data, $kidPeriod) {
    //print 'p';
    ++$this->tot_points_kids;
}
    
function outPointGroup($kidPeriod,$errMessage) {
    $curPeriodId = $kidPeriod->periodId;
    $curPeriodObj = $this->periods->periodArray[$curPeriodId];
    if ($this->outRowCount==0) {
        $style=' style=" background-color:#ccccff;font-weight:bold;"';
        $this->outRowStart();
        $this->outCell('Record<br>Type', $style);
        $this->outCell('Record<br>id', $style);
        $this->outCell('Kid Name', $style);
        $this->outCell('Point<br>Value', $style);
        $this->outCell('Note', $style);
        $this->outCell('Class<br>Date', $style);
        $this->outCell('When<br>Created', $style);
        $this->outRowEnd();
    }
    $this->outRowStart();
    $this->outCell($curPeriodObj->schoolName . ' - ' . $curPeriodObj->periodName . ' - ' . rc_getSemesterNameFromCode($curPeriodObj->semCode) . ' - ' . rc_getYearNameFromYearAndSemesterCodes($curPeriodObj->schoolYear, $curPeriodObj->semCode)  , ' colspan="99" style="background-color:yellow"');
    $this->outRowEnd();
    $this->outRowStart();
    $this->outCell($errMessage, ' colspan="99" style="background-color:yellow"');
    $this->outRowEnd();

    if (count($curKidPeriod->kdPer_points_array) >=1) {
        $totPoints = 0;
        foreach($curKidPeriod->kdPer_points_array as $points) {
            $totPoints += $points->pointValue;
            $this->outRowStart();
            $this->outCell('Points');
            $this->outCell($points->pointsId);
            $this->outCell($kidPeriod->kdPer_kidName);
            $this->outCell($points->pointValue);
            $this->outCell($points->pointNote);
            $this->outCell($points->classDate);
            $this->outCell($points->whenCreated);
            $this->outRowEnd();
        }
        $this->outRowStart();
        $this->outCell('(total)');
        $this->outCell('');
        $this->outCell('(computed)');
        $this->games_out_fixToCell($totPoints);
        $this->outCell('');
        $this->outCell('');
        $this->outCell('');
        $this->outRowEnd();
    }  
    $this->outRowStart();
    $this->outCell('Kid-Period');
    $this->games_out_fixToCell($kidPeriod->kdPer_kidPeriodId);
    $ver = ($period->kcmVersion==2) ? ' KCM-2' : ' KCM-1';
    $this->outCell('(totals Record)' . $ver );
    $this->games_out_fixFromCell($kidPeriod->pointTotal);
    $this->outCell('');
    $this->outCell('');
    $this->outCell('');
    $this->outRowEnd();
            
    if ($this->doFix) {
        if  ( ($period->kcmVersion == 2) and ($kidPeriod->generalPoints != $totPoints) ) {
           $kcm2Update = "`rKPe:KcmGeneralPoints` = '{$totPoints}'";
        }
        $query = "UPDATE `ro:kid_period` SET `rkPe:KcmGamePoints`='".$kidPeriod->kp_gamePointsTotal. "' WHERE `rKPe:kdPer_kidPeriodId`='".$kidPeriod->kdPer_kidPeriodId."'";
        $result = $this->db->rc_query($query);
        if ($result === FALSE) {
            print 'Error 201 - save points ' . $query;
            exit;
        }
    }
    $this->games_outRow_error(' Points in point records do not match points in kid Period Record (Fix assumes pointsrecords are valid, but it could be invalid point records)');
   
 }

function validatePoints($kidPeriod) {
    $countPoints = count($kidPeriod->kdPer_points_array);
    if ( $countPoints==0) {
        return 0;
    }
    $totPoints = 0;
    foreach($kidPeriod->kdPer_points_array as $points) {
       $totPoints += $points->pointValue;
    }
    $curPeriodId = $kidPeriod->periodId;
    $curPeriodObj = $this->periods->periodArray[$curPeriodId];
    $kidPeriod->pointTotal = $curPeriodObj->kcmVersion==2 ? $kidPeriodObj->generalPoints : $kidPeriodObj->kdPer_points_kcm1_total;
    $kidPeriod->kcmVersion = $curPeriodObj->kcmVersion;
    if ($totPoints != $kidPeriod->pointTotal ) {
       $this->outPointGroup($kidPeriod,'error in points vs. point totals');
       return 1;
   }
   return 0;
}

function getProgramName($db,$kidPeriod) {
    //????????????????????
       print '<br>';
       print $kidPeriod->kdPer_kidName . ' - ';
        print $gameDesc . ' - ';
      print $message;
}

function outRowStart() {
    if ($this->outRowCount==0) {
        print PHP_EOL.PHP_EOL.'<table>';
    }
    ++$this->outRowCount;
    print PHP_EOL.'<tr>';
}

function outRowEnd() {
    print PHP_EOL.'</tr>';
}

function outCell($content, $more='') {
    print PHP_EOL.'<td ' .$more . '>'.$content.'</td>';
}

function outEnd() {
    if ($this->outRowCount>=1) {
        print PHP_EOL.'</table>'.PHP_EOL;
    }
}

function error($kidPeriod, $gameDesc, $message) {
       print '<br>';
       print $kidPeriod->kdPer_kidName . ' - ';
        print $gameDesc . ' - ';
      print $message;
}


} // end class

?>
