<?php

// kcm2-util-validate-results-data-inc.php

//@@@@@@
//@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@                                              Data
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@
//@@@@@@

// error bits in totals object 
const ERB_PNT_ACCUM     =  1;  // points from point records don't equal points in kid-period record
const ERB_PNT_KCM1_KCM2 =  2;  // kcm1 totals don't equal kcm2 totals
const ERB_PNT_ERROR     =  3;  // any point error
const ERB_GAME_POINTS   =  8;  // points from game records don't equal points in kid-period record
const ERB_GAME_ACCUM    = 16;  // game results from game records don't equal games in game-total (accum) record
const ERB_GAME_ERROR    = 24;  // any game problem
// error bits in totals game-type object 
const ERB_GAM_WIN       = 1;    // game results from game records don't equal games in game-total (accum) record
const ERB_GAM_LOST      = 2;   // game results from game records don't equal games in game-total (accum)record
const ERB_GAM_DRAW      = 4;  // game results from game records don't equal games in game-total (accum)record


class kcm2_data_global {
public $gbd_db;    
public $gdb_grand_totals;   
public $gbd_list_programs = array();
public $gbd_fix_kidPeriod = array();
public $gbd_fix_gameAcm = array();
public $gbd_fix_invalid = array();
public $gbd_filter_programId;    
public $gbd_filter_dateStart;
public $gbd_filter_dateEnd;
public $gbd_doFix_invalid = FALSE;
public $gbd_doFix_games   = FALSE;
public $gbd_doFix_points = FALSE;
public $gbd_viewRepairs = TRUE;
public $gbd_viewDetails = TRUE;
public $gbd_viewSummary = TRUE;
public $gbd_sem_option = 'cur';

function __construct($db) {
    $this->gbd_db = $db;
    $this->gbd_filter_programId = 0;    
    $this->gbd_filter_dateEnd = rc_getNowDate();
    $year = substr($this->gbd_filter_dateEnd,0,4);
    $month = substr($this->gbd_filter_dateEnd,5,2);
    $this->gbd_filter_dateStart = ( ($month>=8) ? ( $year . '-08-01' ) : ( ($year-1) . '-08-01') );
    //$this->gbd_filter_dateStart = '2017-01-01';
    $this->gdb_grand_totals = new kcm2_standard_totals_group;
    //??????? for debugging
    $this->gbd_filter_dateStart = '2018-02-08';
    $this->gbd_filter_dateEnd = '2018-05-30';
}

function gbd_getProgram($programId) {
    return isSet($this->gbd_list_programs[$programId]) ? $this->gbd_list_programs[$programId] : NULL;
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Program
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

class kcm2_data_program {
public $prg_programId;
public $prg_semester;
public $prg_type;
public $prg_year;
public $prg_dow;
public $prg_dateFirst;
public $prg_dateLast;
public $prg_kcm1PointCats;
public $prg_kcm2PointCats;
public $prg_kcmVersion;
public $prg_progName;
public $prg_list_periods;
public $prg_list_kidProgram; 
public $prg_stdTotals; 

function __construct($row) {
    $this->prg_stdTotals = new kcm2_standard_totals_group;
    $this->prg_list_periods = array();
    $this->prg_list_kidProgram = array();
    $this->prg_programId = $row['pPr:ProgramId'];
    $this->prg_semester = $row['pPr:SemesterCode'];
    $this->prg_type = $row['pPr:ProgramType'];
    $this->prg_ = $row['pPr:SchoolNameUniquifier'];
    $this->prg_year = $row['pPr:SchoolYear'];
    $this->prg_dow = $row['pPr:DayOfWeek'];
    $this->prg_dateFirst = $row['pPr:DateClassFirst'];
    $this->prg_dateLast = $row['pPr:DateClassLast'];
    $this->prg_kcm1PointCats = $row['pPr:KcmPointCategories'];
    $this->prg_kcm2PointCats = $row['pPr:KcmPointCatList'];
    $this->prg_kcmVersion = $row['pPr:KcmVersion'];
    $this->prg_progName = $row['SchoolName'];
}

function prg_getPeriod($periodId) {
    return isSet($this->prg_list_periods[$periodId]) ? $this->prg_list_periods[$periodId] : NULL;
}

}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Period 
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

class kcm2_data_period {
public $per_periodId;   
public $per_seqBits;    
public $per_periodName; 
public $per_list_kidPeriod; 
public $per_stdTotals; 

function __construct($row) {
    $this->prg_stdTotals = new kcm2_standard_totals_group;
    $this->per_list_kidPeriod = array(); 
    $this->per_list_kidProgram = array(); 
    $this->per_seqBits    = $row['pPe:PeriodSequenceBits'];
    $this->per_periodId = $row['pPe:PeriodId'];
    $this->per_periodName = $row['pPe:PeriodName'];
    $this->per_stdTotals    = new kcm2_standard_totals_group;
}

function per_getKidPeriod($kidPeriodId) {
    return isset($this->per_list_kidPeriod[$kidPeriodId]) ? $this->per_list_kidPeriod[$kidPeriodId] : NULL;
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Kid-Program
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

class kcm2_data_kidProgram {

function __construct($row) {
}

}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Kid-Period 
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

class kcm2_data_kidPeriod {
public $kdPer_stdTotals; 
public $programId;   //???????? 
public $periodId;    //???????? 
public $kdPer_kidPeriodId;    
public $kdPer_kidProgram;    // object
public $kdPer_period;    // object
public $kdPer_program;   // object 
//public $generalPoints;    //???????? 
//--- Points
public $kdPer_points_kcm1_array;      // from kidPeriod record  //????? problem - it's an array - (also) need total points
public $kdPer_points_kcm1_total;      // from kidPeriod record  //????? problem - it's an array - (also) need total points
public $kdPer_points_kcm2_total;      // from kidPeriod record
public $kdPer_points_games;     // from kidPeriod record
//public $kdPer_gamePointsTotal;  // calculated from games
//public $pointTotal;  //????????  // to be filled in later after getting kcm version
// added information when joining information
public $kdPer_games_chess = array();
public $kdPer_games_blitz = array();
public $kdPer_games_bug = array();
//public $kdPer_games_all = array();  // all of chess, blitz, and bug
public $kdPer_accum_chess = NULL;
public $kdPer_accum_blitz = NULL;  // should only be zero or one record
public $kdPer_accum_bug = NULL;    // should only be zero or one record
public $kdPer_points_array = array();

function __construct($kidProgramObject, $row) {
    $this->kdPer_stdTotals = new kcm2_standard_totals_group;
    $this->kdPer_kidProgram = $kidProgramObject;
    $this->kdPer_periodId = $row['pPe:PeriodId'];   
    $this->kdPer_program = $row['pPr:ProgramId'];  
    $this->kdPer_kidPeriodId = $row['rKPe:KidPeriodId'];    
    $this->kdPer_points_games = $row['rKPe:KcmGamePoints'];  
    $this->kidPer_game_totals = new kcm2_standard_totals_group;
    $this->kdPer_points_kcm1_array = explode('~',$row['rKPe:KcmPerPointValues']);    
    $this->kdPer_points_kcm1_total = array_sum($this->kdPer_points_kcm1_array);    
    $this->kdPer_points_kcm2_total = $row['rKPe:KcmGeneralPoints'];    
    //$this->kdPer_kidName = $row['rKd:FirstName'] . ' ' . $row['rKd:LastName'];    
    $this->programId = $row['pPr:ProgramId'];    
    $this->periodId = $row['pPe:PeriodId'];
    //$str1 = $row['rKPe:KcmPerPointValues'];    
    //$str2 = $row['rKPr:KcmPrgPointValues'];
    //$this->kdPer_points_kcm1_array = (empty($str1) ? 0 : array_sum(explode('~', $str1))) + (empty($str2) ? 0 : array_sum(explode('~', $str2)));
    // = $row['Kd:NickName'];  
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Points
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

class kcm2_data_points {
public $pointsId;
//public $kidId;  // not in use
public $periodId;
public $programId;
public $kidPeriodId;
public $pointValue;
public $pointNote;
public $errors;
public $classDate;
public $whenCreated;
public $pt_errors;

function __construct($row) {
    $this->pointsId    = $row['gpPo:PointsId'];
    $this->programId   = $row['gpPo:@ProgramId'];
    $this->kidPeriodId = $row['gpPo:@KidPeriodId'];
    $this->pointValue  = $row['gpPo:PointValue'];
    $this->pointNote   = $row['gpPo:Note'];
    $this->classDate   = $row['gpPo:ClassDate'];
    $this->whenCreated = $row['gpPo:WhenCreated'];
    $this->periodId    = $row['rKPe:@PeriodId'];

    $this->pt_errors = FALSE;
    local::errorCheck_id($this->pt_errors,$this->programId,'Invalid Program Id');
    local::errorCheck_id($this->pt_errors,$this->periodId,'Invalid Period Id in points');
    local::errorCheck_id($this->pt_errors,$this->kidPeriodId,'Invalid kidPeriod Id in points');  
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Games
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

class kcm2_data_game_record {
public $gm_gameId;
public $gm_programId;
public $gm_periodId;
public $gm_kidPeriodId;    
public $gm_gameType;
public $gm_win;
public $gm_lost;
public $gm_draw;
public $gm_gamePoints;
public $gm_classDate;
public $gm_whenCreated;
public $gm_errors;

function __construct($row) {
    $this->gm_gameId =      $row['gpGa:GameId'];
    $this->gm_programId =   $row['gpGa:@ProgramId'];
    $this->gm_periodId =    $row['gpGa:@PeriodId'];
    $this->gm_kidPeriodId = $row['gpGa:@KidPeriodId'];    
    $this->gm_gameType =    $row['gpGa:GameTypeIndex'];
    $this->gm_win =         $row['gpGa:GamesWon'];
    $this->gm_lost =        $row['gpGa:GamesLost'];
    $this->gm_draw =        $row['gpGa:GamesDraw'];
    $this->gm_classDate =   $row['gpGa:ClassDate'];
    $this->gm_whenCreated = $row['gpGa:WhenCreated'];
    //$key =  local::getKey($this->programId, $this->periodId, $this->kidPeriodId, $this->gameType);
    //$this->key = $key;
    switch ($this->gm_gameType) {
        case 0:  $wp = 10; $dp =  5; break;
        case 1:  $wp =  5; $dp =  3; break;
        case 2:  $wp =  3; $dp =  2; break;
        default: $wp =  0; $dp =  0; break;
    }
    $this->gm_gamePoints += ($this->gm_win * $wp) + ($this->gm_draw * $dp);
    
    $this->gm_errors = FALSE;
    local::errorCheck_id($this->gm_errors,$this->gm_programId,'Invalid Program Id');
    local::errorCheck_id($this->gm_errors,$this->gm_periodId,'Invalid Period Id');
    local::errorCheck_id($this->gm_errors,$this->gm_kidPeriodId,'Invalid kidPeriod Id in Game');
    local::errorCheck_gameType($this->gm_errors,$this->gm_gameType,'Invalid Game Type');
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Game Totals
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

class kcm2_data_game_accum {
public $gac_gameTotalId;
public $gac_programId;
public $gac_periodId;
public $gac_kidPeriodId;    
public $gac_gameType;
public $gac_win;
public $gac_lost;
public $gac_draw;
public $gac_errors;

function __construct($row) {
    $this->gac_gameTotalId= $row['gpGT:GameTotalId'];
    $this->gac_programId = $row['gpGT:@ProgramId'];
    $this->gac_periodId = $row['gpGT:@PeriodId'];
    $this->gac_kidPeriodId = $row['gpGT:@kidPeriodId'];    
    $this->gac_gameType = $row['gpGT:GameTypeIndex'];
    $this->gac_win = $row['gpGT:GamesWon'];
    $this->gac_lost = $row['gpGT:GamesLost'];
    $this->gac_draw = $row['gpGT:GamesDraw'];
    
    $this->gac_errors = FALSE;
    local::errorCheck_id($this->gac_errors,$this->gac_programId,'Invalid Program Id');
    local::errorCheck_id($this->gac_errors,$this->gac_periodId,'Invalid Period Id');
    local::errorCheck_id($this->gac_errors,$this->gac_kidPeriodId,'Invalid kidPeriod Id in gameTotals');
    local::errorCheck_gameType($sTot_this->errors,$this->gac_gameType,'Invalid Game Type');
}

} // end class


//@@@@@@
//@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@                                   Standard Totals
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@
//@@@@@@

class kcm2_fix_gameAccum_rec {
public $fixGam_gameTotalId;
public $fixGam_programDesc;  // not really needed but nice to know
public $fixGam_kidPeriodId;  // not really needed but nice to know
public $fixGam_gameType;  // not really needed but nice to know
public $fixGam_prevWin;
public $fixGam_prevLost;
public $fixGam_prevDraw;
public $fixGam_newWin;
public $fixGam_newLost;
public $fixGam_newDraw;

function fixGam_fix_Record($db) {
    $query = "UPDATE `gp:gametotals` SET `gpGT:GamesWon`='{$this->fixGam_newWin}', `gpGT:GamesLost`='{$this->fixGam_newLost}', `gpGT:GamesDraw`='{$this->fixGam_newDraw}' WHERE `gpGT:GameTotalId`='{$this->fixGam_gameTotalId}'";
    $result = $db->rc_query($query);
    if ($result === FALSE) {
        print 'Error 201 - save game points ' . $query;
        exit;
    }
    return 'Game-Accum Record Repaired (has new values)';
}

} // end class

class kcm2_fix_kidPeriod_rec {
public $fixKP_kidPeriodId;
public $fixkp_programDesc;  // not really needed but nice to know
public $fixKP_prevGamePoints;
public $fixKP_prevKcm1Array;
public $fixKP_prevKcm1Points;
public $fixKP_prevkcm2Points;
public $fixKP_newGamePoints;
public $fixKP_newKcm1Array;
public $fixKP_newKcm1Points;
public $fixKP_newKcm2Points;
public $fixKP_pointRecTotal;  // null if n/a
public $fixKP_gameRecTotal;  // null if n/a

function fixKP_fix_Record($db) {
    $sql = array();
    $sql[] = "UPDATE `ro:kid_period`";
    $sql[] = "SET `rkPe:KcmGamePoints`='".$this->fixKP_newGamePoints. "'";
    $sql[] = ",`rKPe:KcmPerPointValues`='".implode('~',$this->fixKP_newKcm1Array). "'";
    $sql[] = ",`rKPe:KcmGeneralPoints`='".$this->fixKP_newKcm2Points. "'";
    $sql[] = "WHERE `rKPe:kidPeriodId`='".$this->fixKP_kidPeriodId."'";
    $query = implode( $sql, ' ');
    $result = $db->rc_query($query);
    if ($result === FALSE) {
        print 'Error 201 - save game points ' . $query;
        exit;
    }
    return 'Kid-Period Record Repaired (has new values)';
}
    
}

class kcm2_fix_invalid_rec {
public $fixInv_recordType;  // 'g'=game, 'p'=point, 'a'=gameTotal
public $fixInv_recordId;
public $fixInv_programDesc;  
public $fixInv_programId;  
public $fixInv_periodId;
public $fixInv_KidPeriodId;
public $fixInv_classDate;
public $fixInv_whenCreated;
public $fixInv_otherData;

function fixInv_fix_Record($db) {
    switch ($this->fixInv_recordType) {
        case 'g': 
            $typeDesc = 'Game Record';
            $tableName = 'gp:games';
            $indexName = 'gpGa:GameId';
            break;
        case 'a': 
           $typeDesc = 'Game-Totals Record';
           $tableName = 'gp:gametotals';
           $indexName = 'gpGT:GameTotalId';
           break;
        case 'p': 
           $typeDesc = 'Points Record';
           $tableName = 'gp:points';
           $indexName = 'gpPo:PointsId';
           break;
        default: return '???????';   
    }
    $query = "DELETE FROM `{$tableName}` WHERE `{$indexName}` = '{$this->fixInv_recordId}';";
    $result = $db->rc_query($query);
    if ($result === FALSE) {
        print 'Error 201 - delete invalid record ' . $query;
        exit;
    }
    return $typeDesc . ' Record repaired by deleting it';
}
    
}

//@@@@@@
//@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@                                   Standard Totals
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@
//@@@@@@

class kcm2_standard_totals_gameType {
public $sGamTot_gamRec_win      = 0;   
public $sGamTot_gamRec_lost     = 0;
public $sGamTot_gamRec_draw     = 0; 
public $sGamTot_gamRec_count    = 0;   
public $sGamTot_gamAccum_win    = 0;    // error bit 1
public $sGamTot_gamAccum_lost   = 0;    // error bit 2
public $sGamTot_gamAccum_draw   = 0;    // error bit 4
public $sGamTot_gameRec_points  = 0; 
public $sGamTot_gameAcm_points  = 0; 
public $sGamTot_game_points     = 0;    // what is considered the best source
public $sGamTot_error_gamAcmCount  = 0; 
public $sGamTot_error_bits      = 0; 

function sGamTot_clear() {
    $this->sGamTot_gamRec_win      = 0;
    $this->sGamTot_gamRec_lost     = 0;
    $this->sGamTot_gamRec_draw     = 0; 
    $this->sGamTot_gamRec_count    = 0; 
    $this->sGamTot_gamAccum_win    = 0;
    $this->sGamTot_gamAccum_lost   = 0;
    $this->sGamTot_gamAccum_draw   = 0; 
    $this->sGamTot_gameRec_points  = 0;
    $this->sGamTot_gameAcm_points  = 0;
    $this->sGamTot_game_points     = 0;
    $this->sGamTot_error_gamAcmCount  = 0;   // game totals in accum vs totals from game records  (0 if no game records - historical data)
    $this->sGamTot_error_bits      = 0; 
}

function sGamTot_add($source) {
    $this->sGamTot_gamRec_win      += $source->sGamTot_gamRec_win;
    $this->sGamTot_gamRec_lost     += $source->sGamTot_gamRec_lost;
    $this->sGamTot_gamRec_draw     += $source->sGamTot_gamRec_draw; 
    $this->sGamTot_gamRec_count    += $source->sGamTot_gamRec_count; 
    $this->sGamTot_gamAccum_win    += $source->sGamTot_gamAccum_win;
    $this->sGamTot_gamAccum_lost   += $source->sGamTot_gamAccum_lost;
    $this->sGamTot_gamAccum_draw   += $source->sGamTot_gamAccum_draw; 
    $this->sGamTot_game_points     += $source->sGamTot_game_points;
    $this->sGamTot_gameRec_points  += $source->sGamTot_gameRec_points;
    $this->sGamTot_gameAcm_points  += $source->sGamTot_gameAcm_points;
    $this->sGamTot_error_gamAcmCount  += $source->sGamTot_error_gamAcmCount; 
    $this->sGamTot_error_bits   = ($this->sGamTot_error_bits | $source->sGamTot_error_bits); 
}

function sGamTot_validate($gameType) {
    // This must only be done once to the first child otherwise error count will be duplicated multiple times
    // i.e. First child is validated, parents are summed with child from previous level
    $this->sGamTot_gameAcm_points = local::lc_calc_game_points($gameType, $this->sGamTot_gamAccum_win, $this->sGamTot_gamAccum_draw);
    if ($this->sGamTot_gamRec_count >= 1) {
        if ($this->sGamTot_gamAccum_win!=$this->sGamTot_gamRec_win) {
            ++$this->sGamTot_error_gamAcmCount;
            $this->sGamTot_error_bits = ($this->sGamTot_error_bits | ERB_GAM_WIN);
        }
        if ($this->sGamTot_gamAccum_lost!=$this->sGamTot_gamRec_lost) {
            ++$this->sGamTot_error_gamAcmCount;
            $this->sGamTot_error_bits = ($this->sGamTot_error_bits | ERB_GAM_LOST);
        }
        if ($this->sGamTot_gamAccum_draw!=$this->sGamTot_gamRec_draw) {
            ++$this->sGamTot_error_gamAcmCount;
            $this->sGamTot_error_bits = ($this->sGamTot_error_bits | ERB_GAM_DRAW);
        }
        $this->sGamTot_gameRec_points = local::lc_calc_game_points($gameType, $this->sGamTot_gamRec_win, $this->sGamTot_gamRec_draw);
        $this->sGamTot_game_points = $this->sGamTot_gameRec_points;
    }
    else {
        $this->sGamTot_game_points = $this->sGamTot_gameAcm_points;
    }
}

}  // end class

class kcm2_standard_totals_group {

public $sTot_kid_count     = 0; 
public $sTot_points_kcm1_array = array(0,0,0,0,0,0,0,0,0,0,0,0); // error bit 4
public $sTot_points_kcm1_total = 0;   // error bit 1
public $sTot_points_kcm2_total = 0;   // error bit 2
public $sTot_points_pointRecs  = 0;   // error bit 2
public $sTot_gamePoints_games   = 0;  // from all the game types
public $sTot_gamePoints_kidPer  = 0;  // from kid-period record(s) //  error bit 8 (from all the game types)
public $sTot_game_count    = 0;  // from all the game types
public $sTot_chess;    
public $sTot_blitz;
public $sTot_bug;
public $sTot_error_gamAcmCount = 0; 
public $sTot_error_gamPntCount = 0; 
public $sTot_error_kcmCount = 0;    // difference in Kcm1 vs kcm2 points
public $sTot_error_pntCount = 0;    // difference in kidPer points vs point records
public $sTot_error_bits    = 0; 

function __construct() {
    $this->sTot_chess = new kcm2_standard_totals_gameType;
    $this->sTot_blitz = new kcm2_standard_totals_gameType;
    $this->sTot_bug   = new kcm2_standard_totals_gameType;
    $this->sTot_clear();
} 
   
function sTot_clear() {
    $this->sTot_kid_count     = 0; 
    $this->sTot_points_pointRecs = 0;
    $this->sTot_points_kcm1_array   = array(0,0,0,0,0,0,0,0,0,0,0,0); 
    $this->sTot_points_kcm1_total   = 0; 
    $this->sTot_points_kcm2_total   = 0; 
    $this->sTot_gamePoints_games  = 0;  
    $this->sTot_gamePoints_kidPer  = 0;  
    $this->sTot_game_count   = 0;  
    $this->sTot_chess->sGamTot_clear();
    $this->sTot_blitz->sGamTot_clear();
    $this->sTot_bug->sGamTot_clear();
    $this->sTot_error_gamAcmCount = 0; 
    $this->sTot_error_gamPntCount = 0; 
    $this->sTot_error_pntCount = 0; 
    $this->sTot_error_kcmCount = 0; 
    $this->sTot_error_bits     = 0; 
}    

function sTot_add_totals($source, $level) {
    $this->sTot_kid_count         += $source->sTot_kid_count; 
    $this->sTot_points_kcm1_total += $source->sTot_points_kcm1_total; 
    $this->sTot_points_kcm2_total += $source->sTot_points_kcm2_total; 
    $this->sTot_gamePoints_games  += $source->sTot_gamePoints_games;  
    $this->sTot_gamePoints_kidPer += $source->sTot_gamePoints_kidPer;  
    $this->sTot_game_count        += $source->sTot_game_count ;  
    $this->sTot_points_pointRecs  += $source->sTot_points_pointRecs;
    $this->sTot_chess->sGamTot_add($source->sTot_chess);
    $this->sTot_blitz->sGamTot_add($source->sTot_blitz);
    $this->sTot_bug->sGamTot_add($source->sTot_bug);
    //if ($this->sTot_gamePoints_games != $this->sTot_gamePoints_kidPer) {
    //    $this->sTot_error_bits    = ( $this->sTot_error_bits | 8); 
    //}
    for ($i=0; $i<count($source->sTot_points_kcm1_array); ++$i) {
        $this->sTot_points_kcm1_array[$i]   += $source->sTot_points_kcm1_array[$i]; 
    }    
    //echo $level . '=' . $this->sTot_error_gamAcmCount . ' - ';
    $this->sTot_error_gamAcmCount += $source->sTot_error_gamAcmCount; 
    $this->sTot_error_gamPntCount   += $source->sTot_error_gamPntCount; 
    $this->sTot_error_pntCount   += $source->sTot_error_pntCount; 
    $this->sTot_error_kcmCount   += $source->sTot_error_kcmCount; 
    $this->sTot_error_bits        = ( $this->sTot_error_bits | $source->sTot_error_bits); 
}

function sTot_validate() {
    // This must only be done once to the first child otherwise error count, point totals, etc could be duplicated multiple times
    // i.e. First child is validated, parents are summed with child from previous level
    $this->sTot_chess->sGamTot_validate(0);
    $this->sTot_blitz->sGamTot_validate(1);
    $this->sTot_bug->sGamTot_validate(2);
    $this->sTot_game_count   = $this->sTot_chess->sGamTot_gamRec_count + $this->sTot_blitz->sGamTot_gamRec_count + $this->sTot_bug->sGamTot_gamRec_count;
    $this->sTot_error_gamAcmCount   = $this->sTot_chess->sGamTot_error_gamAcmCount + $this->sTot_blitz->sGamTot_error_gamAcmCount + $this->sTot_bug->sGamTot_error_gamAcmCount;
    $this->sTot_gamePoints_games = $this->sTot_chess->sGamTot_game_points + $this->sTot_blitz->sGamTot_game_points + $this->sTot_bug->sGamTot_game_points;
    if ($this->sTot_gamePoints_games != $this->sTot_gamePoints_kidPer) {
        $this->sTot_error_bits = ($this->sTot_error_bits | ERB_GAME_POINTS);
        $this->sTot_error_gamPntCount += 1;
    }
     if ($this->sTot_error_gamAcmCount >=1) {
        $this->sTot_error_bits = ($this->sTot_error_bits | ERB_GAME_ACCUM );
    }    
   if ( ($this->sTot_points_pointRecs != $this->sTot_points_kcm1_total) 
       and ($this->sTot_points_pointRecs != $this->sTot_points_kcm2_total) ) {  //?????????????????????????????????????
        $this->sTot_error_bits = ($this->sTot_error_bits | ERB_PNT_ACCUM);
        $this->sTot_error_pntCount += 1;
    }
    if ($this->sTot_points_kcm1_total != $this->sTot_points_kcm2_total) {
        $this->sTot_error_bits = ($this->sTot_error_bits | ERB_PNT_KCM1_KCM2);
        $this->sTot_error_kcmCount += 1;
    }
  //  if ($this->sTot_error_gamAcmCount!=0) echo '['.$this->sTot_error_gamAcmCount.']';
}

} // end class

?>