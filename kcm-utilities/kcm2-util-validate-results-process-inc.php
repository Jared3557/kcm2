<?php

// kcm2-util-validate-results-process-inc.php

//@@@@@@
//@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@                                             Read
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@
//@@@@@@

class kcm2_read_data {
    
public $rda_count_programs = 0;    
public $rda_count_periods = 0;    
public $rda_count_kidPrograms = 0;    
public $rda_count_kidPeriods = 0;    
public $rda_count_games = 0;    
public $rda_count_gameTotals = 0;    
public $rda_count_points = 0; 
public $rda_data; 

function rd_read_data($data) {   
    $this->rda_data = $data;
    $this->rd_read_programs($data);
    $this->rd_read_kids($data);
    $this->rd_read_games($data);
    $this->rd_read_gameTotals($data);
    $this->rd_read_points($data);
    $this->rd_out_status();
}

function rd_out_status() {
    return;
    print '<br><br><br><br><hr><hr>---- Read Data Statistics ----';
    print '<br>Program count = ' . $this->rda_count_programs;
    print '<br>Period count = ' . $this->rda_count_periods;
    print '<br>Kid-Program count = ' . $this->rda_count_kidPrograms;
    print '<br>Kid-Period count = ' . $this->rda_count_kidPeriods;
    print '<br>Game Totals count = ' . $this->rda_count_gameTotals;
    print '<br>Game count = ' . $this->rda_count_games;
    print '<br>Points count = ' . $this->rda_count_points;
    print '<br><hr><hr><br>';
}

function rd_read_getQuery($data, $query) {
    // print '<hr>' . $query . '<hr><br>';
    //return FALSE;
    $result = $data->gbd_db->rc_query( $query );
    if ($result === FALSE) {
        print 'error 121 ' . $query;
    }
    return $result;
}
    
function rd_read_get_program_filter($data, $prefix = '' ) {
    $typeFilter = "( `pPr:ProgramType` BETWEEN '1' AND '3')";
    if ($data->gbd_filter_programId > 0) {
        return $prefix . "`pPr:ProgramId` = '{$this->gbd_filter_programId}'";
    }
    if ($this->rda_data->gbd_sem_option == 'all') {
         return $prefix . $typeFilter;
   }    
    if ( (!empty($data->gbd_filter_dateStart)) and (!empty($data->gbd_filter_dateEnd)) ) {
        return $prefix . "(`pPr:DateClassFirst` <= '{$data->gbd_filter_dateEnd}') AND (`pPr:DateClassLast` >= '{$data->gbd_filter_dateStart}') AND ". $typeFilter;
    }
    
    
    return $typeFilter; // no filter (all programs)
}

function rd_read_programs($data) {
    $field = array();
    // pr:program
    $field[] = 'pPr:ProgramId';
    $field[] = 'pPr:@SchoolId';
    $field[] = 'pPr:SemesterCode';
    $field[] = 'pPr:ProgramType';
    $field[] = 'pPr:SchoolNameUniquifier';
    $field[] = 'pPr:SchoolYear';
    $field[] = 'pPr:DayOfWeek';
    $field[] = 'pPr:DateClassFirst';
    $field[] = 'pPr:DateClassLast';
    $field[] = 'pPr:KcmPointCategories';
    $field[] = 'pPr:KcmPointCatList';
    $field[] = 'pPr:KcmVersion';
    // pr:period
    $field[] = 'pPe:PeriodId';
    $field[] = 'pPe:@ProgramId';
    $field[] = 'pPe:PeriodSequenceBits';
    $field[] = 'pPe:PeriodName';
    // pr:school
    $field[] = 'pSc:SchoolId';
    $field[] = 'pSc:NameShort';
    $fldList = "`" . implode($field,"`, `") . "`";
    $sql = array();  
    $sql[] = "Select ".$fldList;
    $sql[] = ", CONCAT_WS(' ',`pSc:NameShort`,`pPr:SchoolNameUniquifier`) AS `SchoolName`";
    $sql[] = "FROM `pr:program`";
    $sql[] = "JOIN `pr:period` ON (`pPe:@ProgramId` = `pPr:ProgramId`)";  
    $sql[] = "JOIN `pr:school` ON (`pSc:SchoolId` = `pPr:@SchoolId`)";  
    $sql[] = $this->rd_read_get_program_filter($data, 'WHERE ');
    $sql[] = "ORDER BY `pSc:NameShort`, `pPr:SchoolNameUniquifier`, `pPe:PeriodSequenceBits`";
    $query = implode( $sql, ' ');
    $result = $this->rd_read_getQuery($data, $query);
    if ($result===FALSE) {
        return;  // for debugging
    }
    while ($row=$result->fetch_array()) {
        $curProgramId = $row['pPr:ProgramId'];
        $curPeriodId = $row['pPe:PeriodId'];
        // add program
        if ( !isset($data->gbd_list_programs[$curProgramId]) ) {
            $data->gbd_list_programs[$curProgramId] = new kcm2_data_program($row);
            ++$this->rda_count_programs; 
        }
        $curProgram = $data->gbd_list_programs[$curProgramId];
        // add period
        if (!isset($curProgram->prg_list_periods[$curPeriodId])) {
            // should always get here
            $curPeriodObj = new kcm2_data_period($row);
            $curProgram->prg_list_periods[$curPeriodId] = $curPeriodObj;
            ++$this->rda_count_periods;
        }    
    }
}

function rd_read_kids($data) {
    $field = array();
    // pr:program
    $field[] = 'pPr:ProgramId';
    $field[] = 'pPe:PeriodId';
    $field[] = 'rKPr:KidProgramId';
    $field[] = 'rKPe:KidPeriodId';
    // ro:kid_program
    $field[] = 'rKPr:@ProgramId';
    $field[] = 'rKPr:@KidId';
    $field[] = 'rKPe:KcmPerPointValues';
    // ro:kid_period
    $field[] = 'rKPe:KidPeriodId';
    $field[] = 'rKPe:@PeriodId';
    $field[] = 'rKPe:@KidProgramId';
    $field[] = 'rKPe:@KidId';
    $field[] = 'rKPe:KcmGamePoints';
    $field[] = 'rKPe:KcmPerPointValues'; // kcm1
    $field[] = 'rKPe:KcmGeneralPoints';  // kcm2
    $fldList = "`" . implode($field,"`, `") . "`";
    $sql = array();  
    $sql[] = "Select ".$fldList;
    $sql[] = "FROM `ro:kid_period`";
    $sql[] = "JOIN `ro:kid_program` ON `rKPr:KidProgramId` = `rKPe:@KidProgramId`";  
    $sql[] = "JOIN `pr:period` ON `pPe:PeriodId` = `rKPe:@PeriodId`";  
    $sql[] = "JOIN `pr:program` ON (`pPr:ProgramId` = `rKPr:@ProgramId`)";  
    $sql[] = $this->rd_read_get_program_filter($data, 'WHERE ');
    $sql[] = "ORDER BY `rKPe:KidPeriodId`";
    $query = implode( $sql, ' ');
    $result = $this->rd_read_getQuery($data, $query);
    if ($result===FALSE) {
        return;  // for debugging
    }
    while($row=$result->fetch_array()) {
        $curProgramId = $row['rKPr:@ProgramId'];
        $curPeriodId = $row['pPe:PeriodId'];
        $curKidProgramId = $row['rKPr:KidProgramId'];
        $curKidPeriodId = $row['rKPe:KidPeriodId'];
        $curProgramObj = $data->gbd_getProgram($curProgramId);
       // add kid program
        if ( !isSet($curProgramObj->prg_list_kidProgram[$curKidProgramId]) ) {
            $curProgramObj->prg_list_kidProgram[$curKidProgramId] = new kcm2_data_kidProgram($row);
            ++$this->rda_count_kidPrograms;
        }
        $curKidProgramObj = $curProgramObj->prg_list_kidProgram[$curKidProgramId];
        // add kid period
        $curPeriodObj = $curProgramObj->prg_getPeriod($curPeriodId);
        if ( !isSet($curPeriodObj->per_list_kidPeriod[$curKidPeriodId]) ) {
            // should always get here
            //echo '<br>'.$curKidPeriodId.'@-'.$curProgramId.'-'.$curPeriodId;
            $curPeriodObj->per_list_kidPeriod[$curKidPeriodId] = new kcm2_data_kidPeriod($curKidProgramObj,$row);
            ++$this->rda_count_kidPeriods;
        }
    }
}

function rd_read_games($data) {
    $field = array();
    $field[] = 'gpGa:@ProgramId';
    $field[] = 'gpGa:@PeriodId';
    $field[] = 'gpGa:@KidPeriodId';
    $field[] = 'gpGa:GameId';
    $field[] = 'gpGa:GameTypeIndex';
    $field[] = 'gpGa:GamesWon';
    $field[] = 'gpGa:GamesLost';
    $field[] = 'gpGa:GamesDraw';
    $field[] = 'gpGa:ClassDate';
    $field[] = 'gpGa:WhenCreated';
    $field[] = 'pPr:DateClassFirst';
    $field[] = 'pPr:DateClassLast';
    $fldList = "`" . implode($field,"`, `") . "`";
    $sql = array();  
    $sql[] = "Select ".$fldList;
    $sql[] = "FROM `gp:games`";
    $sql[] = "JOIN `pr:program` ON `pPr:ProgramId` = `gpGa:@ProgramId`";  
    $sql[] = $this->rd_read_get_program_filter($data, 'WHERE ');
    //$sql[] = "GROUP BY `gpGa:GameId`";
    $sql[] = "ORDER BY `gpGa:@ProgramId`,`gpGa:@PeriodId`";
    $query = implode( $sql, ' ');
    $result = $this->rd_read_getQuery($data, $query);
    if ($result===FALSE) {
        return;  // for debugging
    }
    while($row=$result->fetch_array()) {
        $curProgramId = $row['gpGa:@ProgramId'];
        $curPeriodId = $row['gpGa:@PeriodId'];
        $curKidPeriodId = $row['gpGa:@KidPeriodId'];
        $curProgramObj = $data->gbd_getProgram($curProgramId);
        $curPeriodObj = $curProgramObj->prg_getPeriod($curPeriodId);
        $curKidPeriodObj = $curPeriodObj->per_getKidPeriod($curKidPeriodId);
        $curGame = new kcm2_data_game_record($row);
        ++$this->rda_count_games;
        if ($curGame->gm_errors !== FALSE) {
            $fg = new kcm2_fix_invalid_rec;
            $data->gbd_fix_invalid[] = $fg;
            $fg->fixInv_recordType   = 'g'; 
            $fg->fixInv_recordId     = $curGame->gm_gameId ;
            $fg->fixInv_programDesc  = '';
            $fg->fixInv_programId    = $curGame->gm_programId;  
            $fg->fixInv_periodId     = $curGame->gm_periodId;
            $fg->fixInv_KidPeriodId  = $curGame->gm_kidPeriodId;
            $fg->fixInv_classDate    = $curGame->gm_classDate;
            $fg->fixInv_whenCreated  = $curGame->gm_whenCreated;
            $fg->fixInv_otherData    = $curGame->gm_gameType . '-' . $curGame->gm_win . '-' . $curGame->gm_lost . '-' . $curGame->gm_draw;
            continue;
        }
        switch ($curGame->gm_gameType) {
            case 0: $curKidPeriodObj->kdPer_games_chess[] = $curGame;
                    break;
            case 1: $curKidPeriodObj->kdPer_games_blitz[] = $curGame;
                    break;
            case 2: $curKidPeriodObj->kdPer_games_bug[] = $curGame;
                    break;
        }
    }    
}

function rd_read_gameTotals($data) {
    $field = array();
    $field[] = 'gpGT:GameTotalId';
    $field[] = 'gpGT:@ProgramId';
    $field[] = 'gpGT:@PeriodId';
    $field[] = 'gpGT:@kidPeriodId';
    $field[] = 'gpGT:GameTypeIndex';
    $field[] = 'gpGT:GamesWon';
    $field[] = 'gpGT:GamesLost';
    $field[] = 'gpGT:GamesDraw';
    $field[] = 'pPr:DateClassFirst';
    $field[] = 'pPr:DateClassLast';
    $fldList = "`" . implode($field,"`, `") . "`";
    $sql = array();  
    $sql[] = "Select ".$fldList;
    $sql[] = "FROM `gp:gametotals`";
    $sql[] = "JOIN `pr:program` ON (`pPr:ProgramId` = `gpGT:@ProgramId`)";  
    //$sql[] = "JOIN `pr:period` ON (`pPr:ProgramId` = `gpGT:@PeriodId`)";  
    //$sql[] = "JOIN `pr:kidPeriod` ON (`pPr:PKidPeriodId` = `gpGT:@PeriodId`)";  
    //$sql[] = "JOIN `pr:kid` ON (`pPr:KidId` = `gpGT:@PeriodId`)";  
    $sql[] = $this->rd_read_get_program_filter($data, 'WHERE ');
    $sql[] = "ORDER BY `gpGT:@ProgramId`,`gpGT:@PeriodId`";
    $query = implode( $sql, ' ');
    $result = $this->rd_read_getQuery($data, $query);
    if ($result===FALSE) {
        return;  // for debugging
    }
    $count = 0;
    $dupKeys = FALSE;
    while($row=$result->fetch_array()) {
        $curProgramId = $row['gpGT:@ProgramId'];
        $curProgramObj = $data->gbd_getProgram($curProgramId);
        $curPeriodId = $row['gpGT:@PeriodId'];
        $curPeriodObj = $curProgramObj->prg_getPeriod($curPeriodId);
        $curKidPeriodId = $row['gpGT:@kidPeriodId'];
        $curKidPeriodObj = $curPeriodObj->per_getKidPeriod($curKidPeriodId);
        $curGameTotals = new kcm2_data_game_accum($row);
        if ($curGameTotals->gac_errors !== FALSE) {
            $fa = new kcm2_fix_invalid_rec;
            $data->gbd_fix_invalid[] = $fa;
            $fa->fixInv_recordType   = 'a'; 
            $fa->fixInv_recordId     = $curGameTotals->gac_gameTotalId ;
            $fa->fixInv_programDesc  = '';
            $fa->fixInv_programId    = $curGameTotals->gac_programId;  
            $fa->fixInv_periodId     = $curGameTotals->gac_periodId;
            $fa->fixInv_KidPeriodId  = $curGameTotals->gac_kidPeriodId;
            $fa->fixInv_classDate    = '';
            $fa->fixInv_whenCreated  = '';
            $fa->fixInv_otherData    = $curGameTotals->gac_gameType . '-' .$curGameTotals->gac_win . '-' . $curGameTotals->gac_lost . $curGameTotals->gac_draw;
            continue;
        }
        //if ($curKidPeriodObj==NULL) {
        //    $data->gbd_invalid_gamAccum[] = $curGameTotals;
        //    //???? problem - will have a kidperiodId that looks valid but no matching record
        //    continue;
        //}
        switch ($curGameTotals->gac_gameType) {
            case 0: $curKidPeriodObj->kdPer_accum_chess = $curGameTotals;
                    break;
            case 1: $curKidPeriodObj->kdPer_accum_blitz = $curGameTotals;
                    break;
            case 2: $curKidPeriodObj->kdPer_accum_bug = $curGameTotals;
                    break;
        }
        ++$this->rda_count_gameTotals;
     }  
}

function rd_read_points($data) {
    $field = array();
    $field[] = 'gpPo:PointsId';
    $field[] = 'gpPo:@ProgramId';
    $field[] = 'rKPe:@PeriodId';
    $field[] = 'gpPo:@KidProgramId';
    $field[] = 'gpPo:@KidPeriodId';
    $field[] = 'gpPo:PointValue';
    $field[] = 'pPr:DateClassFirst';
    $field[] = 'pPr:DateClassLast';
    $field[] = 'gpPo:CategoryIndex';
    $field[] = 'gpPo:CategoryClue';
    $field[] = 'gpPo:Note';
    $field[] = 'gpPo:OriginCode';
    $field[] = 'gpPo:ClassDate';
    $field[] = 'gpPo:WhenCreated';
    $fldList = "`" . implode($field,"`, `") . "`";
    $sql = array();  
    $sql[] = "Select ".$fldList;
    $sql[] = "FROM `gp:points`";
    $sql[] = "JOIN `pr:program` ON `pPr:ProgramId` = `gpPo:@ProgramId`";  
    $sql[] = "LEFT JOIN `ro:kid_period` ON `rKPe:KidPeriodId` = `gpPo:@KidPeriodId`";  
    //$sql[] = "JOIN `ro:kid` ON `rKd:KidId` = `rKPe:@KidId`";  
    $sql[] = "LEFT JOIN `ro:kid_program` ON `rKPr:KidProgramId` = `rKPe:@KidProgramId`";  //??????  need program filter
    $sql[] = $this->rd_read_get_program_filter($data, 'WHERE ');
    $sql[] = "ORDER BY `gpPo:@ProgramId`,`rKPe:@PeriodId`";
    $query = implode( $sql, ' ');
    $result = $this->rd_read_getQuery($data, $query);
    if ($result===FALSE) {
        return;  // for debugging
    }
     while($row=$result->fetch_array()) {
        $curProgramId = $row['gpPo:@ProgramId'];
        $curProgramObj = $data->gbd_getProgram($curProgramId);
        $curPeriodId = $row['rKPe:@PeriodId'];
        $curPeriodObj = $curProgramObj->prg_getPeriod($curPeriodId);
        $curPoints = new kcm2_data_points($row);
        if ($curPoints->pt_errors !== FALSE) {
            $fp = new kcm2_fix_invalid_rec;
            $data->gbd_fix_invalid[] = $fp;
            $fp->fixInv_recordType   = 'p'; 
            $fp->fixInv_recordId     = $curPoints->pointsId ;
            $fp->fixInv_programDesc  = '';
            $fp->fixInv_programId    = $curPoints->programId;  
            $fp->fixInv_periodId     = $curPoints->periodId;
            $fp->fixInv_KidPeriodId  = $curPoints->kidPeriodId;
            $fp->fixInv_classDate    = $curPoints->classDate;
            $fp->fixInv_whenCreated  = $curPoints->whenCreated;
            $fp->fixInv_otherData    = $curPoints->pointValue . '-' . $curPoints->pointNote;
            continue;
        }
        $curKidPeriodId = $row['gpPo:@KidPeriodId'];
        $curKidPeriod = $curPeriodObj->per_getKidPeriod($curKidPeriodId);
        if ($curKidPeriod==NULL) {
            print '<br>'.$curKidPeriodId.'$-'.$curProgramId.'-'.$curPeriodId;
            continue;
        }
        $curKidPeriod->kdPer_points_array[] = $curPoints;
        ++$this->rda_count_points;
    }    
     //print '<br>Kid Period Table: Count = ' . count($this->kidPeriodArray) . ' - Date Min = ' . $this->dateFirst . ' - Date Max = '  .  //$this->dateLast . ' - (count only includes records within game and points date ranges)';
}

}  // end class  
 
 
//@@@@@@
//@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@                                          Validate
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@
//@@@@@@

class kcm2_validate_and_total {

function validate_all($data) {
   $data->gdb_grand_totals->sTot_clear();
   foreach ($data->gbd_list_programs as $curProgramObj) {
        $this->validate_program($data, $curProgramObj);
        $data->gdb_grand_totals->sTot_add_totals($curProgramObj->prg_stdTotals,'gnd');
    }
}

function validate_program($data, $program) {
    $program->prg_stdTotals->sTot_clear();
    foreach ($program->prg_list_periods as $curPeriodObj) {
        $this->validate_period($data, $curPeriodObj);
        $program->prg_stdTotals->sTot_add_totals($curPeriodObj->per_stdTotals,'prg'.$program->prg_programId.':'.$curPeriodObj->per_periodId);
    }
}
    
function validate_period($data, $period) {
    $period->per_stdTotals->sTot_clear();
    foreach ($period->per_list_kidPeriod as $curKidPeriodObj) {
        $this->validate_kidPeriod($data, $curKidPeriodObj);
        $period->per_stdTotals->sTot_add_totals($curKidPeriodObj->kdPer_stdTotals,'pr'.$period->per_periodId.':'.$curKidPeriodObj->kdPer_kidPeriodId);
    }
}
    
function validate_kidPeriod($data, $kidPeriod) {
    $kidPeriod->kdPer_stdTotals->sTot_clear();
    $totals = $kidPeriod->kdPer_stdTotals;
    $totals->sTot_kid_count = 1;
    $totals->sTot_points_kcm1_array = is_array($kidPeriod->kdPer_points_kcm1_array)?$kidPeriod->kdPer_points_kcm1_array : array($kidPeriod->kdPer_points_kcm1_array); 
    $totals->sTot_points_kcm1_total = $kidPeriod->kdPer_points_kcm1_total; 
    $totals->sTot_points_kcm2_total = $kidPeriod->kdPer_points_kcm2_total; 
    $totals->sTot_gamePoints_kidPer = $kidPeriod->kdPer_points_games;
    //--- validate game counts
    $this->validate_gameType($data, $kidPeriod, $kidPeriod->kdPer_games_chess, $kidPeriod->kdPer_accum_chess, $kidPeriod->kdPer_stdTotals->sTot_chess);
    $this->validate_gameType($data, $kidPeriod, $kidPeriod->kdPer_games_blitz, $kidPeriod->kdPer_accum_blitz, $kidPeriod->kdPer_stdTotals->sTot_blitz);
    $this->validate_gameType($data, $kidPeriod, $kidPeriod->kdPer_games_bug,   $kidPeriod->kdPer_accum_bug, $kidPeriod->kdPer_stdTotals->sTot_bug);
    $this->validate_pointRecords($data, $kidPeriod);
    $kidPeriod->kdPer_stdTotals->sTot_validate();
    //return;  //??????????????????????????????????????????????????????
    //if ( ($kidPeriod->kp_gamePointsTotal != $kidPeriod->kdPer_stdTotals->sTot_gamePoints_games) 
    //   or ($kidPeriod-> != $kidPeriod->kdPer_stdTotals->sTot_points_kcm1_total ) ) {
    //return;  //??????????????????????????????????????????????????????
    $errGamePt = ( ($totals->sTot_game_count >=1) and ($kidPeriod->kdPer_points_games != $totals->sTot_gamePoints_games));
    $errPointRec = ( (count($kidPeriod->kdPer_points_array) >= 1) and ($kidPeriod->kdPer_points_kcm1_total != $kidPeriod->kdPer_stdTotals->sTot_points_pointRecs) );
    $errKcm = ($kidPeriod->kdPer_points_kcm1_total != $kidPeriod->kdPer_points_kcm2_total); 
    if ($errGamePt or $errPointRec or $errKcm) {
        $correct = new kcm2_fix_kidPeriod_rec;
        $data->gbd_fix_kidPeriod[] = $correct;
        $correct->fixKP_kidPeriodId = $kidPeriod->kdPer_kidPeriodId;
        $correct->fixGam_programDesc = '';  // not really needed but nice to know
        $correct->fixKP_prevGamePoints  = $kidPeriod->kdPer_points_games;
        $correct->fixKP_prevKcm1Array   = $kidPeriod->kdPer_points_kcm1_array;
        $correct->fixKP_prevKcm1Points  = $kidPeriod->kdPer_points_kcm1_total;
        $correct->fixKP_prevkcm2Points  = $kidPeriod->kdPer_points_kcm2_total;
        $correct->fixKP_newGamePoints   = $kidPeriod->kdPer_stdTotals->sTot_gamePoints_games;
        $correct->fixKP_newKcm1Array   = $kidPeriod->kdPer_points_kcm1_array;
        $correct->fixKP_newKcm1Points  = $kidPeriod->kdPer_points_kcm1_total;
        if ($errPointRec) {
            $correct->fixKP_newKcm1Array[0] = $correct->fixKP_newKcm1Array[0] + $kidPeriod->kdPer_stdTotals->sTot_points_pointRecs - $kidPeriod->kdPer_points_kcm1_total;
        }
        //if ($errKcm) {
        //    $correct->fixKP_newKcm2Points  = $correct->fixKP_newKcm1Points;
        //}
        //else {
        //     $correct->fixKP_newKcm2Points  = $kidPeriod->kdPer_points_kcm2_total;
        //}
        $correct->fixKP_newKcm1Points = array_sum($correct->fixKP_newKcm1Array);
        $correct->fixKP_newKcm2Points  = $correct->fixKP_newKcm1Points;
        $correct->fixKP_pointRecTotal = (count($kidPeriod->kdPer_points_array) == 0) ? NULL : $totals->sTot_points_pointRecs;
        $correct->fixKP_gameRecTotal  = (count($totals->sTot_game_count) == 0 ) ? NULL : $totals->sTot_gamePoints_games;      
    }
}

function validate_pointRecords ($data, $kidPeriod) {
    $totals = $kidPeriod->kdPer_stdTotals;
    foreach ( $kidPeriod->kdPer_points_array as $points) {
        $totals->sTot_points_pointRecs += $points->pointValue;
    }
}

function validate_gameType($data, $kidPeriod, &$gameArray, $accum, $gameTotals) {
     // possible errors: (1) record messages (2) game totals<>gameTotals (3) GamePoints<>kidPeriodGamePoints 
    if ($accum != NULL) {
        $gameTotals->sGamTot_gamAccum_win    = $accum->gac_win;
        $gameTotals->sGamTot_gamAccum_lost   = $accum->gac_lost;
        $gameTotals->sGamTot_gamAccum_draw   = $accum->gac_draw; 
    }    
    foreach ( $gameArray as $game) {
        $gameTotals->sGamTot_gamRec_win    += $game->gm_win;
        $gameTotals->sGamTot_gamRec_lost   += $game->gm_lost;
        $gameTotals->sGamTot_gamRec_draw   += $game->gm_draw; 
        $gameTotals->sGamTot_gamRec_count  += 1;
    }
    if ($accum == NULL) {
        return;
    }
    if ( ($accum->gac_win != $gameTotals->sGamTot_gamRec_win) or ($accum->gac_lost != $gameTotals->sGamTot_gamRec_lost) 
        or ($accum->gac_draw != $gameTotals->sGamTot_gamRec_draw) ) {
        $correct = new kcm2_fix_gameAccum_rec;
        $data->gbd_fix_gameAcm[] = $correct;
        $correct->fixGam_gameTotalId = $accum->gac_gameTotalId;
        $correct->fixGam_programDesc = '';  
        $correct->fixGam_kidPeriodId = $kidPeriod->kdPer_kidPeriodId;  
        $correct->fixGam_gameType    = $accum->gac_gameType; 
        $correct->fixGam_prevWin     = $accum->gac_win;
        $correct->fixGam_prevLost    = $accum->gac_lost;
        $correct->fixGam_prevDraw    = $accum->gac_draw;
        $correct->fixGam_newWin      = $gameTotals->sGamTot_gamRec_win;
        $correct->fixGam_newLost     = $gameTotals->sGamTot_gamRec_lost;
        $correct->fixGam_newDraw     = $gameTotals->sGamTot_gamRec_draw;
    }
    //$gameTotals->sGamTot_validate();
}

} // end class

//@@@@@@
//@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@                                        Report All
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@
//@@@@@@

class kcm2_report_all {

function report_out ($data) {
    if ($data->gbd_viewRepairs) {
        $emitter->emit_line('<br><br><br>');
        $report_invalid = new kcm2_report_invalidRecords;
        $report_invalid->reInv_report_out($data);
        
        $emitter->emit_line('<br><br><br>');
        $report_gamCorrect = new kcm2_report_correctGameCounts;
        $report_gamCorrect->coGam_report_out($data);
        
        $emitter->emit_line('<br><br><br>');
        $report_pntCorrect = new kcm2_report_correctPoints;
        $report_pntCorrect->coPnt_report_out($data);
    }

    if ($data->gbd_viewDetails) {
        $emitter->emit_line('<br><br><br>');
        $report_details = new kcm2_report_details;
        $report_details->reDet_report_out($data);
    }
    
    if ($data->gbd_viewSummary) {
        $emitter->emit_line('<br><br><br>');
        $report_summary = new kcm2_report_summary;
        $report_summary->reSum_report_out($data);
    }
}
    
}

class kcm2_report_invalidRecords {

function reInv_report_out($data) {
    $emitter->emit_table_start();
    $errCount = count($data->gbd_fix_invalid);
    if ($errCount == 0) {
        $emitter->emit_row_start();
        $emitter->emit_cell('There are no Game or Point Records with critical errors','lc-hd'); 
        $emitter->emit_row_end();
    }
    else {
        $emitter->emit_row_start();
        $emitter->emit_cell('There are '.$errCount.' Game, Game-Total, or Point Records with critical errors<br>These records will be ignored in further validations<br>and FIX will delete these records with record Id\'s marked in red','lc-hd','colspan="9"'); 
        $emitter->emit_row_end();
        $emitter->emit_row_start();
        $emitter->emit_cell('Record Type','lc-hd'); 
        $emitter->emit_cell('Record<br>Id','lc-hd'); 
        $emitter->emit_cell('Program<br>Id','lc-hd'); 
        $emitter->emit_cell('Period<br>Id','lc-hd'); 
        $emitter->emit_cell('Kid-Period<br>Id','lc-hd'); 
        $emitter->emit_cell('Class<br>Date','lc-hd'); 
        $emitter->emit_cell('When<br>Created','lc-hd'); 
        $emitter->emit_cell('Other data<br>in record','lc-hd'); 
        $emitter->emit_cell('Note','lc-hd'); 
        $emitter->emit_row_end();
        foreach($data->gbd_fix_invalid as $inv) {
            switch ($inv->fixInv_recordType) {
                case 'g': $s = 'Game Record'; break;
                case 'a': $s = 'Game-Totals Record';break;
                case 'p': $s = 'Points Record';break;
            }
            $emitter->emit_row_start();
            $emitter->emit_cell($s); 
            $emitter->emit_cell($inv->fixInv_recordId,'lc-error'); 
            $emitter->emit_cell($inv->fixInv_programId); 
            $emitter->emit_cell($inv->fixInv_periodId); 
            $emitter->emit_cell($inv->fixInv_KidPeriodId); 
            $emitter->emit_cell($inv->fixInv_classDate); 
            $emitter->emit_cell($inv->fixInv_whenCreated); 
            $emitter->emit_cell($inv->fixInv_otherData); 
            $s = '';
             if ($data->gbd_doFix_games) {
                $s = $inv->fixInv_fix_Record($data->gbd_db);
            }
            $emitter->emit_cell($s,local::errorStyle('',$s!='','lc-changed')); 
            $emitter->emit_row_end();
        }             
        // print totals - all programs
    }    
    $emitter->emit_table_end();
}

} // end class

//@@@@@@
//@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@                         Report - Game Corrections
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@
//@@@@@@

class kcm2_report_correctGameCounts {
    
function coGam_report_out($data) {
    $emitter->emit_table_start();
    $emitter->emit_row_start();
    $emitter->emit_cell('Game total records needing corrections<br>FIX will change the values marked in red','lc-hd','colspan="99"'); 
    $emitter->emit_row_end();
    
    $emitter->emit_row_start();
    $emitter->emit_cell('Game-Tot<br>Id','lc-hd','rowspan="2"'); 
    $emitter->emit_cell('Kid-Per<br>Id','lc-hd','rowspan="2"'); 
    $emitter->emit_cell('Desc','lc-hd','rowspan="2"'); 
    $emitter->emit_cell('Type','lc-hd','rowspan="2"'); 
    $emitter->emit_cell('Prev Values','lc-hd','colspan="3"'); 
    $emitter->emit_cell('New (Corrected) Values','lc-hd','colspan="3"'); 
    $emitter->emit_cell('Note','lc-hd','rowspan="2"'); 
    $emitter->emit_row_end();
    
    $emitter->emit_row_start();
    $emitter->emit_cell('Win','lc-hd'); 
    $emitter->emit_cell('Lost','lc-hd'); 
    $emitter->emit_cell('Draw','lc-hd'); 
    $emitter->emit_cell('Win','lc-hd'); 
    $emitter->emit_cell('Lost','lc-hd'); 
    $emitter->emit_cell('Draw','lc-hd'); 
    $emitter->emit_row_end();

    foreach($data->gbd_fix_gameAcm as $gaCor) {
        $emitter->emit_row_start();
        $emitter->emit_cell($gaCor->fixGam_gameTotalId,''); 
        $emitter->emit_cell($gaCor->fixGam_kidPeriodId,'');
        $emitter->emit_cell($gaCor->fixGam_programDesc,''); 
        $emitter->emit_cell($gaCor->fixGam_gameType ,''); 
        $emitter->emit_cell($gaCor->fixGam_prevWin,''); 
        $emitter->emit_cell($gaCor->fixGam_prevLost,''); 
        $emitter->emit_cell($gaCor->fixGam_prevDraw,''); 
        $emitter->emit_cell($gaCor->fixGam_newWin,local::errorStyle('',$gaCor->fixGam_prevWin!=$gaCor->fixGam_newWin)); 
        $emitter->emit_cell($gaCor->fixGam_newLost,local::errorStyle('',$gaCor->fixGam_prevLost!=$gaCor->fixGam_newLost)); 
        $emitter->emit_cell($gaCor->fixGam_newDraw,local::errorStyle('',$gaCor->fixGam_prevDraw!=$gaCor->fixGam_newDraw)); 
        $s = '';
        if ($data->gbd_doFix_games) {
            $s = $gaCor->fixGam_fix_Record($data->gbd_db);
        }
        $emitter->emit_cell($s,local::errorStyle('',$s!='','lc-changed')); 
        $emitter->emit_row_end();
    }    
    
    $emitter->emit_table_end();
} 

}  // end class
   
//@@@@@@
//@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@ Report - Point Corrections (in Kid-Period Record)
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@
//@@@@@@

class kcm2_report_correctPoints {  //  (in Kid-Period Record)
    
function coPnt_report_out($data) {
    $emitter->emit_table_start();
    $emitter->emit_cell('Kid-Period (Point) records needing corrections<br>FIX will change the values marked in red','lc-hd','colspan="99"'); 
    $emitter->emit_row_end();
    
    $emitter->emit_row_start();
    $emitter->emit_cell('Kid-Per<br>Id','lc-hd','rowspan="2"'); 
    $emitter->emit_cell('Desc','lc-hd','rowspan="2"'); 
    $emitter->emit_cell('Point-Rec<br>Total','lc-hd','rowspan="2"'); 
    $emitter->emit_cell('Game-Rec<br>Total','lc-hd','rowspan="2"'); 
    $emitter->emit_cell('Prev Values','lc-hd','colspan="4"'); 
    $emitter->emit_cell('New (Corrected) Values','lc-hd','colspan="4"'); 
    $emitter->emit_cell('Note','lc-hd','rowspan="2"'); 
    $emitter->emit_row_end();
    
    
    $emitter->emit_row_start();
    $emitter->emit_cell('Kcm-1<br>Array','lc-hd'); 
    $emitter->emit_cell('Kcm-1<br>Points','lc-hd'); 
    $emitter->emit_cell('Kcm-2<br>Points','lc-hd'); 
    $emitter->emit_cell('Game<br>Points','lc-hd'); 
    $emitter->emit_cell('Kcm-1<br>Array','lc-hd'); 
    $emitter->emit_cell('Kcm-1<br>Points','lc-hd'); 
    $emitter->emit_cell('Kcm-2<br>Points','lc-hd'); 
    $emitter->emit_cell('Game<br>Points','lc-hd'); 
    $emitter->emit_row_end();

    foreach($data->gbd_fix_kidPeriod as $kidPer) {
        $stGame = local::errorStyle('',$kidPer->fixKP_prevGamePoints != $kidPer->fixKP_newGamePoints);
        $emitter->emit_row_start();
        $emitter->emit_cell($kidPer->fixKP_kidPeriodId ); 
        $emitter->emit_cell($kidPer->fixGam_programDesc   ); 
        $s1 = ($kidPer->fixKP_pointRecTotal === NULL) ? 'n/a' : $kidPer->fixKP_pointRecTotal;
        $s2 = ($kidPer->fixKP_gameRecTotal === NULL) ? 'n/a' : $kidPer->fixKP_gameRecTotal;
        $emitter->emit_cell($s1 );
        $emitter->emit_cell($s2 );
        $emitter->emit_cell(local::pointsArray_toString($kidPer->fixKP_prevKcm1Array) );
        $emitter->emit_cell($kidPer->fixKP_prevKcm1Points );
        $emitter->emit_cell($kidPer->fixKP_prevkcm2Points );
        $emitter->emit_cell($kidPer->fixKP_prevGamePoints );
        $emitter->emit_cell(local::pointsArray_toString($kidPer->fixKP_newKcm1Array) );
        $emitter->emit_cell($kidPer->fixKP_newKcm1Points );
        $emitter->emit_cell($kidPer->fixKP_newKcm2Points );
        $emitter->emit_cell($kidPer->fixKP_newGamePoints , $stGame);
        $s = '';
        if ($data->gbd_doFix_points) {
            $s = $kidPer->fixKP_fix_Record($data->gbd_db);
        }
        $emitter->emit_cell($s,local::errorStyle('',$s!='','lc-changed')); 
        $emitter->emit_row_end();
    }    
    
    $emitter->emit_table_end();
} 

}  // end class
   
//@@@@@@
//@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@                                  Report - report_summary
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@
//@@@@@@

class kcm2_report_summary {
private $temp = 0;
private $rowColor = '2';
private $colColor = '1';

function reSum_report_out($data) {
    $headCount = 99;
    $emitter->emit_table_start();
    $emitter->emit_row_start();
    if (count($data->gdb_grand_totals->sTot_error_gamAcmCount == 0)) {  //????????
        $emitter->emit_cell('report_summary - There are no errors or inconsistencies','','colspan="99"'); 
    }
    else {
   }
    $emitter->emit_row_end();
    // if no errors then print message ?
    foreach ($data->gbd_list_programs as $curProgramObj) {
        ++$headCount;
        if ($headCount >= 10) {
            $this->reSum_heading($data);
            $headCount = 0;
        }    
        $this->reSum_program($data, $curProgramObj);
    }
    // print totals - all programs
    $this->rowColor = '3';  // must be last one
    $this->reSum_out_line(0, 0, $data->gdb_grand_totals, 'Grand Totals' , '');
    $emitter->emit_table_end();
}

function reSum_heading($data) {
    $emitter->emit_row_start();
    $emitter->emit_cell('Desc','lc-hd','rowspan="3"'); 
    $emitter->emit_cell('','lc-hd','rowspan="3"'); 
    $emitter->emit_cell('Kid<br>Cnt','lc-hd','rowspan="3"'); 
    $emitter->emit_cell('General<br>Points', 'lc-hd lc-left2','colspan="4"'); 
    $emitter->emit_cell('Game<br>Points', 'lc-hd lc-left2','colspan="2"'); 
    $emitter->emit_cell('Chess','lc-hd lc-left2 lc-right2','colspan="6"'); 
    $emitter->emit_cell('Blitz','lc-hd lc-left2 lc-right2','colspan="6"'); 
    $emitter->emit_cell('Bughouse','lc-hd lc-left2 lc-right2','colspan="6"'); 
    $emitter->emit_row_end();
    $emitter->emit_row_start();
    $emitter->emit_cell('Pnt<br>Recs',  'lc-hd lc-left2','rowspan="2"'); 
    $emitter->emit_cell('Kcm1<br>Pnts',  'lc-hd','rowspan="2"'); 
    $emitter->emit_cell('Kcm2<br>Pnts',  'lc-hd','rowspan="2"'); 
    $emitter->emit_cell('Kcm1<br>Tots',  'lc-hd lc-right2','rowspan="2"'); 
    $emitter->emit_cell('Kid<br>Per',  'lc-hd lc-left2','rowspan="2"'); 
    $emitter->emit_cell('Gam<br>Recs', 'lc-hd lc-right2','rowspan="2"'); 
    $emitter->emit_cell('Game Records', 'lc-hd lc-left2','colspan="3"'); 
    $emitter->emit_cell('Total Records','lc-hd lc-right2 lc-left','colspan="3"'); 
    $emitter->emit_cell('Game Records','lc-hd lc-left2','colspan="3"'); 
    $emitter->emit_cell('Total Records','lc-hd lc-left2 lc-right lc-left','colspan="3"'); 
    $emitter->emit_cell('Game Records','lc-hd lc-left2','colspan="3"'); 
    $emitter->emit_cell('Total Records','lc-hd lc-right2 lc-left','colspan="3"'); 
    $emitter->emit_row_end();
    $emitter->emit_row_start();
    $emitter->emit_cell('Win', 'lc-hd lc-left2'); 
    $emitter->emit_cell('Lost','lc-hd'); 
    $emitter->emit_cell('Draw','lc-hd'); 
    $emitter->emit_cell('Win', 'lc-hd lc-left' ); 
    $emitter->emit_cell('Lost','lc-hd'); 
    $emitter->emit_cell('Draw','lc-hd lc-right2'); 
    $emitter->emit_cell('Win', 'lc-hd lc-left2'); 
    $emitter->emit_cell('Lost','lc-hd'); 
    $emitter->emit_cell('Draw','lc-hd'); 
    $emitter->emit_cell('Win', 'lc-hd lc-left'); 
    $emitter->emit_cell('Lost','lc-hd'); 
    $emitter->emit_cell('Draw','lc-hd lc-right2'); 
    $emitter->emit_cell('Win', 'lc-hd lc-left2'); 
    $emitter->emit_cell('Lost','lc-hd'); 
    $emitter->emit_cell('Draw','lc-hd'); 
    $emitter->emit_cell('Win', 'lc-hd lc-left'); 
    $emitter->emit_cell('Lost','lc-hd'); 
    $emitter->emit_cell('Draw','lc-hd lc-right2'); 
    $emitter->emit_row_end();
    
    //$emitter->emit_cell('Game<br>Pnt','','rowspan="3"'); 
    //$emitter->emit_cell('Game<br>Cnt','','rowspan="3"'); 
    //$emitter->emit_cell('Game<br>Err','','rowspan="3"'); 
    //$emitter->emit_cell('Pnt<br>Kcm1','','rowspan="3"'); 
    //$emitter->emit_cell('Pnt-Ary<br>Kcm1','','rowspan="3"'); 
    //$emitter->emit_cell('Pnt<br>Kcm2','','rowspan="3"'); 
    //$emitter->emit_row_end();
    //$emitter->emit_cell('Program','','rowspan="3"'); 
    //$emitter->emit_cell('Period','','rowspan="3"'); 
    //$emitter->emit_cell('Errors','','rowspan="3"'); 
    //$emitter->emit_cell('Game Records','','colspan="9"'); 
    //$emitter->emit_cell('Game Totals','','colspan="9"'); 
    //$emitter->emit_cell('Game Points','','colspan="3"'); 
    //$emitter->emit_cell('General Points','','colspan="10"'); 
    //$emitter->emit_row_end();
    
    //$emitter->emit_row_start();
    //$emitter->emit_cell('Chess','','colspan="3"'); 
    //$emitter->emit_cell('Blitz','','colspan="3"'); 
    //$emitter->emit_cell('Bughouse','','colspan="3"'); 
    //$emitter->emit_cell('Chess','','colspan="3"'); 
    //$emitter->emit_cell('Blitz','','colspan="3"'); 
    //$emitter->emit_cell('Bughouse','','colspan="3"'); 
    //$emitter->emit_row_end();
    //
    //$emitter->emit_cell('Game<br>Points','', 'rowspan="2"'); 
    //$emitter->emit_cell('Game-Tot<br>Points','', 'rowspan="2"'); 
    //$emitter->emit_cell('Game<br>Kid Points','', 'rowspan="2"'); 
    //$emitter->emit_cell('Point<br>Records', '','rowspan="2"'); 
    //$emitter->emit_cell('Kcm1<br>Value','', 'rowspan="2"'); 
    //$emitter->emit_cell('Kcm2<br>Value','', 'rowspan="2"'); 
}

function reSum_program($data, $program) {
    $progDesc = $program->prg_progName . '<br>' . rc_getYearNameFromYearAndSemesterCodes($program->prg_year,$program->prg_semester) . '-' . rc_getSemesterNameFromCode($program->prg_semester);
    $progTot = $program->prg_stdTotals;
    $this->rowColor = ($this->rowColor=='1') ? '2' : '1';
    // Maybe Period totals only if error
    //if ($program->sTot_error_gamAcmCount != 0) {
    $lineTot = 1 + count($program->prg_list_periods);  
    $lineCur = 0;    
    foreach ($program->prg_list_periods as $curPeriodObj) {
        ++$lineCur;
        $this->reSum_period($lineCur, $lineTot,  $curPeriodObj, $progDesc);
        $progDesc = '';
    }
    //}
    // Program totals
    $this->reSum_out_line($lineTot, $lineTot, $progTot, $progDesc , ' Total');
}
    
function reSum_period($lineCur, $lineTot,  $period, $progDesc) {
    // only get here if errors in at least one period (or maybe depending of filter)
    $this->reSum_out_line($lineCur, $lineTot, $period->per_stdTotals, $progDesc, 'Period');
    //foreach ($period->per_list_kidPeriod as $curKidPeriodObj) {
    //     $this->reSum_out_line($curKidPeriodObj->kdPer_stdTotals, 'Kid');
    //}
}
    
function reSum_out_line($lineCur, $lineTot, $totals, $desc1, $desc2) {
    $cellColor = 'lc-r' . $this->rowColor . 'c' . $this->colColor;
    ++$this->temp;
    //if ($this->temp >= 100) {
    //    exit('<br><br>Incomplete listing');
    //}
    $st = $cellColor;
    if ($lineTot > 1) {
        if ($lineCur == 1) {
            $st = local::concatSep($st, 'lc-top');
        }
        if ($lineCur == $lineTot) {
            $st = local::concatSep($st, 'lc-bot');
        }
    }
    $emitter->emit_row_start();
    if ( ($lineCur==1) and ($lineTot>1) ) {
         $emitter->emit_cell($desc1, 'lc-top lc-bot '.$cellColor,'rowspan="'.$lineTot.'"');  // school/event desc
    }
    else if ( ($lineCur<=1) and ($lineTot<=1) ) {
        $emitter->emit_cell($desc1, $cellColor);  // school/event desc
    }    
    $emitter->emit_cell($desc2, $st);  // period desc  (can make fancy with col and/or rowspan if desired)
    $emitter->emit_cell($totals->sTot_kid_count, $st);  // total game points from games
    $this->reSum_out_generalPoints($totals, $st);
    $this->reSum_out_gamePoints($totals, $st);
    $this->reSum_out_gameType($totals->sTot_chess, $st);
    $this->reSum_out_gameType($totals->sTot_blitz, $st);
    $this->reSum_out_gameType($totals->sTot_bug, $st);
    //$emitter->emit_cell($totals->sTot_gamePoints_games);  
    //$emitter->emit_cell($totals->sTot_game_count);  
    //$emitter->emit_cell($totals->sTot_error_gamAcmCount);  
    //$emitter->emit_cell($totals->sTot_points_kcm1_total);  
    //$emitter->emit_cell(implode('-',$s));  
    //$emitter->emit_cell($totals->sTot_points_kcm2_total); 
    $emitter->emit_row_end();
}

function reSum_out_generalPoints($totals, $st) {
    $len = 1;
    $c = count($totals->sTot_points_kcm1_array);
    for ($i=1; $i<$c; ++$i) {
        $x = $totals->sTot_points_kcm1_array[$i];
        if ( ($x>1) or ($x<-1) ) { 
            $len = $i + 1;
        }    
    }    
    $s = array_slice ( $totals->sTot_points_kcm1_array, 0,  $len); 
    $kcm1PointArray = implode('-',$s);
    $emitter->emit_cell($totals->sTot_points_pointRecs, $st. ' lc-left2');  
    $emitter->emit_cell($totals->sTot_points_kcm1_total, local::errorStyle($st, ($totals->sTot_error_bits & ERB_PNT_ACCUM) == ERB_PNT_ACCUM));  
    $emitter->emit_cell($totals->sTot_points_kcm2_total, local::errorStyle($st, ($totals->sTot_error_bits & ERB_PNT_KCM1_KCM2) == ERB_PNT_KCM1_KCM2,' lc-warn' ));  
    $emitter->emit_cell($kcm1PointArray, $st. ' lc-right2');  
}

function reSum_out_gamePoints($totals, $st) {
    if ( ($totals->sTot_error_bits & ERB_GAME_POINTS) == ERB_GAME_POINTS ) {
        $stgp = $st . ' lc-error';
    }
    else {
        $stgp = $st;
    }
    $emitter->emit_cell($totals->sTot_gamePoints_kidPer, $stgp . ' lc-left2');  
    $emitter->emit_cell($totals->sTot_gamePoints_games, $st. ' lc-right2');  
}

function reSum_out_gameType($gameTot, $st) {
    //$emitter->emit_cell($gameTot->sGamTot_error_gamAcmCount  );  // possible error
    //$emitter->emit_cell($gameTot->sGamTot_gamRec_count ); 
    //$emitter->emit_cell($gameTot->sGamTot_gamRec_points);
        //?????? also need accum points maybe
    $emitter->emit_cell($gameTot->sGamTot_gamRec_win   , 'lc-left2 ' . $st);  // (can be n/a if historical data)
    $emitter->emit_cell($gameTot->sGamTot_gamRec_lost, $st  );  // (can be n/a if historical data)
    $emitter->emit_cell($gameTot->sGamTot_gamRec_draw, 'lc-right ' . $st);   // (can be n/a if historical data)
    $stw = $st;
    $stl = $st;
    $std = $st;
    if ( ($gameTot->sGamTot_error_bits) & ERB_GAM_WIN == ERB_GAM_WIN) {
        $stw = $st . ' lc-error'; 
    }    
    if ( ($gameTot->sGamTot_error_bits) & ERB_GAM_LOST == ERB_GAM_LOST) {
        $stl = $st . ' lc-error'; 
    }    
    if ( ($gameTot->sGamTot_error_bits) & ERB_GAM_DRAW == ERB_GAM_DRAW) {
        $std = $st . ' lc-error'; 
    }    
    $emitter->emit_cell($gameTot->sGamTot_gamAccum_win  , 'lc-left '.$stw);  // possible error
    $emitter->emit_cell($gameTot->sGamTot_gamAccum_lost, $stl );  // possible error
    $emitter->emit_cell($gameTot->sGamTot_gamAccum_draw, 'lc-right2 ' . $std);  // possible error
}

} // end class

//@@@@@@
//@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@                             Report - Game Details
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@@@@@@@
//@@@@@@

class kcm2_report_details {
public $repDet_curData;    
public $repDet_curProgram;    
public $repDet_curProgramDesc;    
public $repDet_curPeriod;    
public $repDet_curKidPeriod;    
public $repDet_curKidPeriodDesc;    
public $repDet_curGameDesc;    
public $repDet_needGameHeader;    

function reDet_report_out($data) {
    $this->repDet_curData = $data;
    $emitter->emit_table_start();
    if ( ($data->gdb_grand_totals->sTot_error_bits & ERB_GAME_ERROR) == 0 ){
        $emitter->emit_row_start();
        $emitter->emit_cell('Game Details - There are no Game errors or inconsistencies'); 
        $emitter->emit_row_end();
    }
    else {
        $emitter->emit_row_start();
        $s = 'Game Error Details';
        if ($data->gdb_grand_totals->sTot_error_gamAcmCount >= 1) {
            $s .= '<br>There are '.$data->gdb_grand_totals->sTot_error_gamAcmCount.' Game Record vs Game Totals inconsistencies'; 
        }   
        if ($data->gdb_grand_totals->sTot_error_gamPntCount >= 1) {
            $s .= '<br>There are '.$data->gdb_grand_totals->sTot_error_gamPntCount.' Game points from Game records vs Kid-Period record inconsistencies'; 
        }    
        $emitter->emit_cell($s,'','colspan="99"'); 
        $emitter->emit_row_end();
        foreach ($data->gbd_list_programs as $curProgramObj) {
            $this->reDet_program($data, $curProgramObj);
        }
    }   
    $emitter->emit_table_end();
}

function reDet_program($data, $program) {
    $this->repDet_curProgram = $program;
    $this->repDet_curProgramDesc = $program->prg_progName . ' ' . rc_getYearNameFromYearAndSemesterCodes($program->prg_year,$program->prg_semester) . '-' . rc_getSemesterNameFromCode($program->prg_semester);
    if ( ($program->prg_stdTotals->sTot_error_bits & ERB_GAME_ERROR) >= 1) {
       // echo '<br>'.$program->prg_progName;
        foreach ($program->prg_list_periods as $curKidPeriodObj) {
            $this->reDet_period($data, $curKidPeriodObj);
        }
    }    
}
    
function reDet_period($data, $period) {
    $this->repDet_curPeriod = $period;
    if ( ($period->per_stdTotals->sTot_error_bits & ERB_GAME_ERROR) >= 1) {
        foreach ($period->per_list_kidPeriod as $curKidPeriodObj) {
            $this->reDet_kidPeriod($data, $curKidPeriodObj);
        }
    }    
}
    
function reDet_kidPeriod($data, $kidPeriod) {
    $this->repDet_curKidPeriod = $kidPeriod;
    // if no errors then return
    $stdTotals = $kidPeriod->kdPer_stdTotals;
    if ( ($stdTotals->sTot_error_bits & ERB_GAME_ERROR) == 0) {
        return;
    }
    $this->repDet_curKidPeriodDesc = 'Kid-Period Id: ' . $kidPeriod->kdPer_kidPeriodId;
     $emitter->emit_row_start();
    $emitter->emit_cell(' <br> ','lc-sep','colspan="99"'); 
    $emitter->emit_row_end();
    $emitter->emit_row_start();
     $emitter->emit_cell($this->repDet_curProgramDesc . ' - ' . $this->repDet_curKidPeriodDesc ,'lc-gamPer','colspan="99"'); 
    $emitter->emit_row_end();
    //$this->reDet_out_heading();
    $this->repDet_needGameHeader = TRUE;
    $pointError = ($stdTotals->sTot_error_bits & ERB_GAME_POINTS) == ERB_GAME_POINTS;
    $this->reDet_gameType($data, $kidPeriod, $kidPeriod->kdPer_games_chess, $kidPeriod->kdPer_accum_chess, $stdTotals->sTot_chess, $pointError, 'Chess');
    $this->reDet_gameType($data, $kidPeriod, $kidPeriod->kdPer_games_blitz, $kidPeriod->kdPer_accum_blitz, $stdTotals->sTot_blitz, $pointError,'Blitz');
    $this->reDet_gameType($data, $kidPeriod, $kidPeriod->kdPer_games_bug, $kidPeriod->kdPer_accum_bug, $stdTotals->sTot_bug, $pointError,'Bughouse');
    //if ( ($stdTotals->sTot_error_bits & ERB_PNT_ACCUM) == ERB_PNT_ACCUM) {
        if (count($kidPeriod->kdPer_points_array >= 1)) {
           $this->reDet_out_point_header();
            foreach ($kidPeriod->kdPer_points_array as $points) {
                $this->reDet_out_point_record($points);
            }    
        }   
    //}
    // need to do points and grand totals
    $this->reDet_out_total_header();
    $this->reDet_out_total_games($kidPeriod);
    $this->reDet_out_total_points($kidPeriod);
    $note = '@@';
    $this->reDet_out_total_kidPeriod($kidPeriod);
    return;    //????????????????
    if ($kidPeriod->kp_gamePointsTotal != $kidPeriod->kdPer_points_kcm2_total) {
        if ($this->doFix) {
            $query = "UPDATE `ro:kid_period` SET `rkPe:KcmGamePoints`='".$kidPeriod->kp_gamePointsTotal. "' WHERE `rKPe:kdPer_kidPeriodId`='".$kidPeriod->kdPer_kidPeriodId."'";
            $result = $this->db->rc_query($query);
            if ($result === FALSE) {
                print 'Error 201 - save game points ' . $query;
                exit;
            }
        }
    }
}

function reDet_gameType($data, $kidPeriod, &$gameArray, $accum, $gameTotals, $pointError, $gameDesc) {
    // if no errors then return
     // possible errors: (1) record messages (2) game totals<>gameTotals (3) GamePoints<>kidPeriodGamePoints 
    if ( ( $gameTotals->sGamTot_error_bits == 0) and (!$pointError) ) {
        return;
    }
    if (count($gameArray)==0) {
        return;
    }
    $this->repDet_curGameDesc = $gameDesc;
    $this->reDet_out_game_header($gameDesc);
    foreach ( $gameArray as $game) {
        $this->reDet_out_game_record($game, $kidPeriod, $gameDesc);
        //$gameTotals->sGamTot_gamRec_win    += $game->gm_win;;
        //$gameTotals->sGamTot_gamRec_lost   += $game->gm_lost;
        //$gameTotals->sGamTot_gamRec_draw   += $game->gm_draw; 
// $this->reDet_out_line ('Game-Record');   
    }
    $this->reDet_out_game_total($gameTotals,$kidPeriod);
    if ($accum != NULL) {
        $this->reDet_out_game_accum($accum, $gameTotals, $kidPeriod, $gameDesc);
    }    
    return;
        //$accum->sGamTot_gamAccum_win    += $accum->gac_win;
        //$accum->sGamTot_gamAccum_lost   += $accum->gac_lost;
        //$accum->sGamTot_gamAccum_draw   += $accum->gac_draw; 
 // $this->reDet_out_line ('Game-Total');   
  // $this->reDet_out_line ('Kid-Period');   
// $this->reDet_out_line ('Chess Game Record Totals');   
// $this->reDet_out_line ('Chess Total Record');   
}

function reDet_totals($data, $kidPeriod, &$gameArray, $accum, $gameTotals, $pointError, $gameDesc) {
// $this->reDet_out_line ('Game-Records Total');   
// $this->reDet_out_line ('Game-Total Records Total');   
// $this->reDet_out_line ('Kid Period Record');  // if points error 
}

function reDet_out_game_header($gameDesc) {
    if ($this->repDet_needGameHeader) {
        $this->repDet_needGameHeader = FALSE;
        $emitter->emit_row_start();
        $emitter->emit_cell('Game Records','lc-hdL','colspan="99"'); 
        $emitter->emit_row_end();
        $emitter->emit_row_start();
        $emitter->emit_cell('Record Type','lc-hd lc-bot2'); 
        $emitter->emit_cell('Record Id','lc-hd lc-bot2'); 
        $emitter->emit_cell('Win','lc-hd lc-bot2'); 
        $emitter->emit_cell('Lost','lc-hd lc-bot2'); 
        $emitter->emit_cell('Draw','lc-hd lc-bot2'); 
        $emitter->emit_cell('Gam<br>Points','lc-hd lc-bot2'); 
        $emitter->emit_cell('Gen<br>Points','lc-hd lc-bot2'); 
        $emitter->emit_cell('Note','lc-hd lc-bot2'); 
        $emitter->emit_row_end();
    }    
}

function reDet_out_game_record( $game, $kidPeriod, $gameDesc ) {
    $emitter->emit_row_start();
    $emitter->emit_cell($gameDesc . ' Record'); 
    $emitter->emit_cell($game->gm_gameId); 
    $emitter->emit_cell($game->gm_win); 
    $emitter->emit_cell($game->gm_lost); 
    $emitter->emit_cell($game->gm_draw); 
    $emitter->emit_cell($game->gm_gamePoints); 
    $emitter->emit_cell(''); 
    $emitter->emit_cell(''); 
    $emitter->emit_row_end();
}

function reDet_out_game_accum($accum, $gameTot, $kidPeriod, $gameDesc) {
    $errW = ($gameTot->sGamTot_gamRec_win!=$accum->gac_win);
    $errL = ($gameTot->sGamTot_gamRec_lost!=$accum->gac_lost);
    $errD = ($gameTot->sGamTot_gamRec_draw!=$accum->gac_draw);
    $stw = local::errorStyle('lc-bot2',$errW);
    $stl = local::errorStyle('lc-bot2',$errL);
    $std = local::errorStyle('lc-bot2',$errD);
    $note = '';
    $stdp = 'lc-bot2';
    $stdn = 'lc-bot2';
    if ($errW or $errL or $errD) {
        $note = 'Game Accum Total Record can be repaired';
        $stdp = 'lc-bot2 lc-warn';
        $stdn = 'lc-bot2 lc-error';
   }    
    $emitter->emit_row_start();
    $emitter->emit_cell($gameDesc . ' Total Record','lc-bot2'); 
    $emitter->emit_cell($accum->gac_gameTotalId,'lc-bot2'); 
    $emitter->emit_cell($accum->gac_win, $stw ); 
    $emitter->emit_cell($accum->gac_lost, $stl ); 
    $emitter->emit_cell($accum->gac_draw, $std ); 
    $emitter->emit_cell($gameTot->sGamTot_gameAcm_points,$stdp); 
    $emitter->emit_cell('','lc-bot2'); 
    $emitter->emit_cell($note,$stdn); 
    $emitter->emit_row_end();
}

function reDet_out_game_total($gameTot, $kidPeriod) {
    $emitter->emit_row_start();
    $emitter->emit_cell($this->repDet_curGameDesc . ' Records Total'); 
    $emitter->emit_cell(''); 
    $emitter->emit_cell($gameTot->sGamTot_gamRec_win); 
    $emitter->emit_cell($gameTot->sGamTot_gamRec_lost); 
    $emitter->emit_cell($gameTot->sGamTot_gamRec_draw); 
    $emitter->emit_cell($gameTot->sGamTot_game_points); 
    $emitter->emit_cell(''); 
    $emitter->emit_cell(''); 
    $emitter->emit_row_end();
}


function reDet_out_point_header() {
   $emitter->emit_row_start();
    $emitter->emit_cell('Point Records','lc-hdL','colspan="99"'); 
    $emitter->emit_row_end();
   $emitter->emit_row_start();
     $emitter->emit_cell('Record Type','lc-hd'); 
    $emitter->emit_cell('Record Id','lc-hd'); 
    $emitter->emit_cell('','lc-hd'); 
    $emitter->emit_cell('','lc-hd'); 
    $emitter->emit_cell('Gam<br>Points','lc-hd'); 
    $emitter->emit_cell('Gen<br>Points','lc-hd'); 
    $emitter->emit_cell('','lc-hd'); 
    $emitter->emit_cell('','lc-hd'); 
    $emitter->emit_row_end();
}

function reDet_out_point_record($points) {
    $emitter->emit_row_start();
    $emitter->emit_cell('Points Record'); 
    $emitter->emit_cell($points->pointsId); 
    $emitter->emit_cell(''); 
    $emitter->emit_cell(''); 
    $emitter->emit_cell(''); 
    $emitter->emit_cell(''); 
    $emitter->emit_cell($points->pointValue); 
    $emitter->emit_cell(''); 
    $emitter->emit_row_end();
}

function reDet_out_total_header() {
   $emitter->emit_row_start();
    $emitter->emit_cell('Kid-Period Record (Game and Points and Reconciliation) ' . $this->repDet_curProgramDesc . ' - ' . $this->repDet_curKidPeriodDesc,'lc-hdL','colspan="99"'); 
    $emitter->emit_row_end();
   $emitter->emit_row_start();
     $emitter->emit_cell('Record Type','lc-hd'); 
    $emitter->emit_cell('Record Id','lc-hd'); 
    $emitter->emit_cell('Kcm1<br>Array','lc-hd'); 
    $emitter->emit_cell('','lc-hd'); 
    $emitter->emit_cell('Kcm2<br>Points','lc-hd'); 
    $emitter->emit_cell('Gam<br>Points','lc-hd'); 
    $emitter->emit_cell('Kcm1<br>Points','lc-hd'); 
    $emitter->emit_cell('Note','lc-hd'); 
    $emitter->emit_row_end();
}

function reDet_out_total_games($kidPeriod) {
   $emitter->emit_row_start();
     $emitter->emit_cell('Total Game-Rec Points',''); 
    $emitter->emit_cell('',''); 
    $emitter->emit_cell('',''); 
    $emitter->emit_cell('',''); 
    $emitter->emit_cell('',''); 
    $emitter->emit_cell($kidPeriod->kdPer_stdTotals->sTot_gamePoints_games); 
    $emitter->emit_cell('',''); 
    $emitter->emit_cell(''); 
    $emitter->emit_row_end();
}

function reDet_out_total_points($kidPeriod) {
   $emitter->emit_row_start();
     $emitter->emit_cell('Total Point-Rec Points',''); 
    $emitter->emit_cell('',''); 
    $emitter->emit_cell('',''); 
    $emitter->emit_cell('',''); 
    $emitter->emit_cell('',''); 
    $emitter->emit_cell('',''); 
   $emitter->emit_cell($kidPeriod->kdPer_stdTotals->sTot_points_pointRecs); 
    $emitter->emit_cell('',''); 
    $emitter->emit_row_end();
}

function reDet_out_total_kidPeriod($kidPeriod) {
    $gamePointError = ( $kidPeriod->kdPer_stdTotals->sTot_gamePoints_games != $kidPeriod->kdPer_points_games);
    $stg = local::errorStyle('',$gamePointError);
    $genPointError = ( ($kidPeriod->kdPer_stdTotals->sTot_error_bits & ERB_PNT_ACCUM) == ERB_PNT_ACCUM);
    $stp = local::errorStyle('',$genPointError);
    $kcmPointError = ( ($kidPeriod->kdPer_stdTotals->sTot_error_bits & ERB_PNT_KCM1_KCM2) == ERB_PNT_KCM1_KCM2);
    $stk = local::errorStyle('',$kcmPointError,' lc-warn');
    $note = '';
    if ($gamePointError) {
        $note = 'Kid-Period record can be repaired';
    }
    $emitter->emit_row_start();
    $emitter->emit_cell('Kid-Period'); 
    $emitter->emit_cell($kidPeriod->kdPer_kidPeriodId); 
    $emitter->emit_cell(''); 
    $emitter->emit_cell(''); 
     $emitter->emit_cell($kidPeriod->kdPer_points_kcm2_total, $stk); 
    $emitter->emit_cell($kidPeriod->kdPer_points_games, $stg);
    $emitter->emit_cell($kidPeriod->kdPer_points_kcm1_total, $stp); 
   $emitter->emit_cell($note,$stg); 
    $emitter->emit_row_end();
}

} // end class

?>

