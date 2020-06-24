<?php

// roster-system-data-games.inc.php

class stdDbRecord_game_result {
public $game_gameId;
public $game_kidPeriodId;     
//--- should be the same for all records of this game
public $game_atGameId;        
public $game_AtProgramId;        
public $game_atPeriodId;        
public $game_gameType;
public $game_originCode;  // tally or game for one kid-period  - in kcm-1 can have multiple tally records for same class date, only one in kcm-2
public $game_classDate;
public $game_whenCreated;
public $game_modWhen;
public $game_atStaffId;
//--- different for all records of this game
public $game_my_wins;       
public $game_my_losts;
public $game_my_draws;
public $game_opponents_count = 0;      // zero if no specified opponents
public $game_opponents_gameId = array();  // each game ID in this set of records
public $game_opponents_kidPeriodId = array();   // array of kid Id's  
public $game_opponents_wins = array();       
public $game_opponents_losts = array();
public $game_opponents_draws = array();

function __construct() {
}

static function dbRec_addFieldList($fldList, $flags=0) {  
}
 
function dbRec_loadRow($row, $flags=0) {
    $this->game_gameId = $row['gpGa:GameId'];
    $this->game_atGameId = $row['gpGa:@GameId'];
    $this->game_gameType = $row['gpGa:GameTypeIndex'];
    $this->game_classDate = $row['gpGa:ClassDate'];
    $this->game_whenCreated = $row['gpGa:WhenCreated'];
    $this->game_atStaffId = $row['gpGa:ModBy@StaffId'];
    $this->game_modWhen = $row['gpGa:ModWhen'];
    $this->gap_kidPeriodId = $row['gpGa:@KidPeriodId'];
    $this->game_my_wins       = $row['gpGa:GamesWon'];
    $this->game_my_losts    = $row['gpGa:GamesLost'];
    $this->game_my_draws     = $row['gpGa:GamesDraw'];
    $this->gap_gameIds   = $row['gpGa:GameId'];
    $this->game_originCode = $row['gpGa:OriginCode'];
    if ( !isset($row['PlayerKidPerIds'])) {
        $this->game_opponents_count = 0;
    }
    else {
        $this->game_opponents_kidPeriodId = explode(',',$row['PlayerKidPerIds']);
        $this->game_opponents_wins       = explode(',',$row['PlayerWon']);
        $this->game_opponents_losts    = explode(',',$row['PlayerLost']);
        $this->game_opponents_draws     = explode(',',$row['PlayerDraw']);
        $this->game_opponents_gameId   = explode(',',$row['PlayerGameIds']);
        $this->game_opponents_count   = count($this->game_opponents_kidPeriodId);
    }
}

}

class kcm2_gap_game_of_player extends stdDbRecord_game_result{

function __construct() {
}

function gap_tallyRecord_init($kcmGlobals, $originCode, $gameType, $kidPeriodId,  $classDate) {
    // never init a player record - player record is just a player's view of a game record - and is read-only when viewed this way
    $kidPeriod = $kcmGlobals->rst_curPeriod->perd_getKidPeriodObject($kidPeriodId);
    $kid = $kidPeriod->kidPer_kidObject;
    $this->game_gameId = 0;
    $this->game_atGameId = 0;        
    $this->game_AtProgramId = $kcmGlobals->gb_roster->rst_program->prog_programId;      
    $this->game_atPeriodId = $kcmGlobals->rst_curPeriod->perd_periodId;     
    $this->game_gameType = $gameType;
    $this->game_classDate = $classDate;
    $this->game_whenCreated = rc_getNow();
    $this->game_modWhen = rc_getNow();
    $this->game_atStaffId = $kcmGlobals->gb_db->rc_MakeSafe( rc_getStaffId());
    $this->game_kidPeriodId = $kidPeriodId;     
    $this->game_originCode = $originCode;
    $this->gap_opponents = array();
    $this->game_my_wins = 0;       
    $this->game_my_losts = 0;
    $this->game_my_draws = 0;
    $this->game_opponents_count = 0;
    $this->game_opponents_kidPeriodId = array();     
}

function gap_playerRecord_loadRow($row) {
    parent::dbRec_loadRow($row);
}

function gap_playerRecord_read($kcmGlobals, $gameId) {
    $this->gap_anyRecord_read($kcmGlobals, $gameId);
    assert( '$this->game_originCode==GAME_ORIGIN_CLASS','gap Class Record Read is reading tally record');
}

function gap_tallyRecord_read($kcmGlobals, $kidPeriodId, $gameType, $classDate){
    $query = "SELECT * FROM `gp:games` WHERE (`gpGa:@KidPeriodId`='{$kidPeriodId}') AND (`gpGa:ClassDate`='{$classDate}') AND (`gpGa:OriginCode` = '".GAME_ORIGIN_TALLY."') AND (`gpGa:GameTypeIndex` = '{$gameType}')";
    $result = $kcmGlobals->gb_sql->sql_performQuery($query , __FILE__ , __LINE__ );
    //assert( '$result->num_rows <= 1', 'gap_tallyRecord_read - there is more than one tally record' );
    if ($result->num_rows < 1) {
        $this->gap_tallyRecord_init($kcmGlobals,GAME_ORIGIN_TALLY, $gameType, $kidPeriodId,$classDate);
    }
    else {
        $row=$result->fetch_array();
        $this->gap_playerRecord_loadRow($row);
    }    
}

function gap_tallyRecord_write($kcmGlobals) {
    // game records (records with more than one player) cannnot be written for GAME_ORIGIN_TALLY origin code type
    //$kcmGlobals->gb_sql->sql_transaction_start ($kcmGlobals);  ????? desirable to do here, but cannot nest transactions - need to make some changes
    if ($this->game_gameId != 0) {
        $orgRec = new kcm2_gap_game_of_player;
        $orgRec->gap_anyRecord_read($kcmGlobals,$this->game_gameId);
//        $changed = ($orgRec->game_my_wins != $this->game_my_wins)
//               or ($orgRec->game_my_losts != $this->game_my_losts)
//               or ($orgRec->game_my_draws != $this->game_my_draws)
//               or ($orgRec->game_gameType != $this->game_gameType)
//               or ($orgRec->game_classDate != $this->game_classDate);
        $changed = FALSE;
        $changed = ($orgRec->game_my_wins == $this->game_my_wins) ? $changed : TRUE ; 
        $changed = ($orgRec->game_my_losts == $this->game_my_losts) ? $changed : TRUE ;  
        $changed = ($orgRec->game_my_draws == $this->game_my_draws) ? $changed : TRUE ; 
        $changed = ($orgRec->game_classDate == $this->game_classDate) ? $changed : TRUE ; 
        if ( ! $changed ) {
            return;  // don't save record if unchanged 
        }   
        $orgRec->gap_updateKidPeriodTotals($kcmGlobals,-1);
        $orgRec->gap_updateGameTotals($kcmGlobals,-1);
    } 
    else if ( ($this->game_my_wins==0) and ($this->game_my_losts==0) and ($this->game_my_draws==0)  ) {
        // Zero is valid value, but do not need to save tally record if zero
        return; 
    }
    $this->gap_updateKidPeriodTotals($kcmGlobals,1);
    $this->gap_updateGameTotals($kcmGlobals,1);
    $this->game_modWhen = rc_getNow();
    $this->game_atStaffId = $kcmGlobals->gb_db->rc_MakeSafe( rc_getStaffId() );
    $fields = array();
    if ($this->game_gameId >= 1) {
        $fields['gpGa:GameId'] = $this->game_gameId;
    }  
    $fields['gpGa:@ProgramId'] = $this->game_AtProgramId;
    $fields['gpGa:@PeriodId'] = $this->game_atPeriodId;
    $fields['gpGa:@KidPeriodId'] = $this->game_kidPeriodId;
    $fields['gpGa:@GameId'] = $this->game_atGameId;
    $fields['gpGa:GameTypeIndex'] = $this->game_gameType;
    $fields['gpGa:GamesWon'] = $this->game_my_wins;
    $fields['gpGa:GamesLost'] = $this->game_my_losts;
    $fields['gpGa:GamesDraw'] = $this->game_my_draws;
    $fields['gpGa:ClassDate'] = $this->game_classDate;
    $fields['gpGa:WhenCreated'] = $this->game_whenCreated;
    $fields['gpGa:ModBy@StaffId'] = $this->game_atStaffId;
    $fields['gpGa:ModWhen'] = $this->game_modWhen;
    $fields['gpGa:Opponents'] = '';
    $fields['gpGa:OriginCode'] = GAME_ORIGIN_TALLY;
    if ($this->game_gameId == 0 ) {
         $query = kcmRosterLib_db_insert($kcmGlobals->gb_db,'gp:games',$fields);
    }
    else {
         $query = kcmRosterLib_db_update($kcmGlobals->gb_db,'gp:games',$fields,"WHERE `gpGa:GameId` = '{$this->game_gameId}'");
    }
    $result = $kcmGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
    if ($this->game_gameId <1) {
        $this->game_gameId = $kcmGlobals->gb_db->insert_id;
        $this->game_atGameId = $this->game_gameId;
        $fields = array();
        $fields['gpGa:GameId'] =  $this->game_gameId;
        $fields['gpGa:@GameId'] =  $this->game_atGameId;
        $query = kcmRosterLib_db_update($kcmGlobals->gb_db,'gp:games',$fields,"WHERE `gpGa:GameId` = '{$this->game_gameId }'");
        $result = $kcmGlobals->gb_sql->sql_performQuery($query , __FILE__ , __LINE__ );
    }
    //$kcmGlobals->gb_sql->sql_transaction_end ($kcmGlobals);
    $gameId = $this->game_gameId;
    return $gameId;
}

function gap_anyRecord_read($kcmGlobals, $gameId) {  //not game records (at least not yet)
    $sql = array();
    $sql[] =  'Select *';
    $sql[] = "FROM `gp:games`";
    $sql[] = " WHERE `gpGa:GameId`='{$gameId}'";
    $query = implode( $sql, ' '); 
    $result = $kcmGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
    if ($result->num_rows != 1) {
        $kcmGlobals->gb_sql->sql_fatalError( __FILE__ , __LINE__ , $query);
    }    
    $row=$result->fetch_array();
    $this->gap_playerRecord_loadRow($row);
}

private function gap_updateGameTotals($kcmGlobals, $sign) {
    $gameTot = new kcm2_gat_game_totals;
    $gameTot->gat_read($kcmGlobals, $this->game_kidPeriodId, $this->game_gameType);
    $gameTot->gameTotals_win += $sign *  $this->game_my_wins;
    $gameTot->gameTotals_lost += $sign * $this->game_my_losts;
    $gameTot->gameTotals_total += $sign * $this->game_my_draws;
    $gameTot->gat_write($kcmGlobals);
}

private function gap_updateKidPeriodTotals($kcmGlobals, $sign) {
    if ($this->game_gameType == GAME_TYPE_CHESS) {
        $winPoints = 10; 
    }
    else if ($this->game_gameType == GAME_TYPE_BUGHOUSE) {
        $winPoints = 3; 
    }
    else if ($this->game_gameType == GAME_TYPE_BLITZ) {
        $winPoints = 5; 
    }
    else {
        // should NEVER get here
        exit('Please notify office of error: gag-Update-Kid-Period-Totals-1');
    }
    $pointChange = ( ( $this->game_my_wins * $winPoints ) + ($this->game_my_draws * round ($winPoints / 2) ) ) * $sign;
    $sql = array();
    $sql[] = "UPDATE `ro:kid_period`";
    $sql[] = "SET `rkPe:KcmGamePoints` = `rkPe:KcmGamePoints` + '{$pointChange}' ";
    $sql[] = "WHERE `rKPe:KidPeriodId` ='{$this->game_kidPeriodId}'";
    $query = implode( $sql, ' '); 
    $kcmGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
}

} // end class


class kcm2_gapBu_game_of_player_bundle {
public $gapBu_count;    
public $gapBu_list;    
// filters
public $gapBu_filterKidPeriodId = NULL;  //  set to view just one kid-Period
public $gapBu_filterStaffId = NULL;     //  set to view only those entered by one user
public $gapBu_filterClassDate = NULL;   //  set to view only one class date
public $gapBu_filterGameType = NULL;    //  set to view only one game type
public $gapBu_filterOriginCode = NULL;  // set to view only one origin code

function gag_playerRecord_readBundle($kcmGlobals) {
    $this->gapBu_list = array();
    $fields = array();
    $fields[] = 'gpGa:GameId';
    $fields[] = 'gpGa:@ProgramId';
    $fields[] = 'gpGa:@PeriodId';
    $fields[] = 'gpGa:@GameId';
    $fields[] = 'gpGa:GameTypeIndex';
    $fields[] = 'gpGa:ClassDate';
    $fields[] = 'gpGa:WhenCreated';
    $fields[] = 'gpGa:ModBy@StaffId';
    $fields[] = 'gpGa:ModWhen';
    $fields[] = 'gpGa:@KidPeriodId';
    $fields[] = 'gpGa:GamesWon';
    $fields[] = 'gpGa:GamesLost';
    $fields[] = 'gpGa:GamesDraw';
    $fields[] = 'gpGa:OriginCode';
    $fields[] = 'gpGa:Opponents';
    $fieldList = "`".implode("`,`",$fields)."`";
    $sql = array();
    $sql[] =  'Select ' . $fieldList;
    $sql[] = "FROM `gp:games`"; 
    $kcmGlobals->gb_sql->sql_whereFilterFirst ( $sql, $kcmGlobals->rst_curPeriod->perd_periodId, '`gpGa:@PeriodId`', 1 );
    $kcmGlobals->gb_sql->sql_whereFilterMore ( $sql, $this->gapBu_filterKidPeriodId, '`gpGa:@KidPeriodId`', 1 );
    $kcmGlobals->gb_sql->sql_whereFilterMore  ( $sql, $this->gapBu_filterGameType, '`gpGa:GameTypeIndex`', 0, TRUE );
    $kcmGlobals->gb_sql->sql_whereFilterMore  ( $sql, $this->gapBu_filterClassDate, '`gpGa:ClassDate`' );
    $kcmGlobals->gb_sql->sql_whereFilterMore  ( $sql, $this->gapBu_filterStaffId, '`gpGa:ModBy@StaffId`' );
    $kcmGlobals->gb_sql->sql_whereFilterMore  ( $sql, $this->gapBu_filterOriginCode, '`gpGa:OriginCode`' );
    $sql[] = "ORDER BY `gpGa:@KidPeriodId`,  `gpGa:GameTypeIndex`, `gpGa:ClassDate`";     //????????????????  
    $query = implode( $sql, ' '); 
    $result = $kcmGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
    $curKey = NULL;
    $curGeneralRec = NULL;
    $isFirst = TRUE;
    while($row=$result->fetch_array()) {
        $game = new kcm2_gap_game_of_player;
        $isFirst = FALSE;
        $game->gap_playerRecord_loadRow($row);
        $this->gapBu_list[] = $game;
    }
    $this->gapBu_count = count($this->gapBu_list);

}

} // end class

class kcm2_gag_game_of_game extends stdDbRecord_game_result{

function __construct() {
    $this->game_gameType = NULL;  // invalid 
    $this->game_atGameId = 0;
}

function gag_gameRecord_clearAll($kcmGlobals,$gameType=NULL) {
    // only valid from game mode
    $this->game_atGameId = 0;
    $this->game_opponents_count = 0;
    $this->game_atGameId = 0;
    $this->game_opponents_count = 0;
    $this->game_opponents_kidPeriodId = array();   
    $this->game_opponents_wins = array(); 
    $this->game_opponents_draws = array(); 
    $this->game_opponents_losts = array(); 
    $this->game_gameType = $gameType;
    $this->game_AtProgramId = $kcmGlobals->gb_roster->rst_program->prog_programId;   
    $this->game_atPeriodId = $kcmGlobals->rst_curPeriod->perd_periodId;     
    $this->game_classDate = $kcmGlobals->rst_classDate->asgDate_classDate;
    $this->game_whenCreated = rc_getNow();
 }
function gag_gameRecord_addPlayer($kidPlayerId, $win, $draw, $lost) {
    // only valid from game mode
    $match = array_search ( $kidPlayerId , $this->game_opponents_kidPeriodId);
    if ($match === FALSE) {
        ++$this->game_opponents_count;
        $this->game_opponents_kidPeriodId[] = $kidPlayerId;   
        $this->game_opponents_wins[] = $win;     
        $this->game_opponents_draws[] = $draw;     
        $this->game_opponents_losts[] = $lost;   
        $this->gagArrayGameId[] = 0;   
    } else {
        $this->game_opponents_wins[$match] += $win;     
        $this->game_opponents_draws[$match] += $draw;     
        $this->game_opponents_losts[$match] += $lost;   
    }   
}

function gag_gameRecord_loadRow($row) {
    parent::dbRec_loadRow($row);
}

function gag_gameRecord_read($kcmGlobals, $atGameId, $gameId = NULL){
    $fields = array();
    $fields[] = 'gpGa:GameId';
    $fields[] = 'gpGa:@ProgramId';
    $fields[] = 'gpGa:@PeriodId';
    $fields[] = 'gpGa:@GameId';
    $fields[] = 'gpGa:GameTypeIndex';
    $fields[] = 'gpGa:OriginCode';
    $fields[] = 'gpGa:ClassDate';
    $fields[] = 'gpGa:WhenCreated';
    $fields[] = 'gpGa:ModBy@StaffId';
    $fields[] = 'gpGa:ModWhen';
    $fieldList = "`" . implode("`,`", $fields) . "`";
    $sql = array();
    $sql[] =  'Select ' . $fieldList;
    $sql[] = ", GROUP_CONCAT(`gpGa:@KidPeriodId` SEPARATOR ',') AS `PlayerKidPerIds`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GameId` SEPARATOR ',') AS `PlayerGameIds`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GamesWon` SEPARATOR ',') AS `PlayerWon`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GamesLost` SEPARATOR ',') AS `PlayerLost`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GamesDraw` SEPARATOR ',') AS `PlayerDraw`";
    $sql[] = "FROM  `gp:games` ";
    if ( $atGameId==0 ) {
        $sql[] = "WHERE `gpGa:GameId`='{$gameId}'";
    }
    else {
        $sql[] = "WHERE `gpGa:@GameId`='{$atGameId}'";
    }
    $sql[] = 'GROUP BY `gpGa:@GameId`';
    $query = implode( $sql, ' '); 
    //echo '<hr>read_gameByGame<hr>' . implode($sql,PHP_EOL) . '<hr>'; return;
    $result = $kcmGlobals->gb_db->rc_query( $query );
    if ($result === FALSE) {
        $kcmGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
    }
    $row=$result->fetch_array();
    //???? check for zero records
    $this->gag_gameRecord_loadRow($row);
}

function gag_gameRecord_write($kcmGlobals){
    // only valid from game mode
   // $kcmGlobals->gb_sql->sql_transaction_start ($kcmGlobals);  //?????????????????????
    $exists = ( ! empty($this->game_atGameId) );
    if ( $exists ) {
         $orgRec = new kcm2_gag_game_of_game;
         $orgRec->gag_gameRecord_read($kcmGlobals,$this->game_atGameId, $this->game_opponents_gameId[0]);
         $orgRec->gag_updateGameTotals($kcmGlobals,-1);
         $orgRec->gag_updateKidPeriodTotals($kcmGlobals,-1);
    }
    $atGameId = $this->gag_gameRecord_update($kcmGlobals); 
    $this->gag_updateGameTotals($kcmGlobals,1);
    $this->gag_updateKidPeriodTotals($kcmGlobals,1);
  //  $kcmGlobals->gb_sql->sql_transaction_end ($kcmGlobals);
    return $atGameId;
    // end transaction processing
}

private function gag_gameRecord_update($kcmGlobals) {
    $atGameId = $this->game_atGameId;   // will be zero for new game
    $modWhen = rc_getNow();
    for ($i=0; $i<$this->game_opponents_count; ++$i) {
        $ops = array();
        for ($j=0; $j<$this->game_opponents_count; ++$j) {
            if ($i != $j) {
                $ops[] = $this->game_opponents_kidPeriodId[$j];
            }
        }
        $opList = implode(',',$ops);
        $fields = array();
        $gameId = $this->game_opponents_gameId[$i];
        if ($this->game_atGameId==0 ) {
            $fields['gpGa:@ProgramId'] = $this->game_AtProgramId;
            $fields['gpGa:@PeriodId'] = $this->game_atPeriodId;
            $fields['gpGa:WhenCreated'] = $this->game_whenCreated;
        }   
        $fields['gpGa:@GameId'] = $atGameId; //will be zero for first player of new game
        $fields['gpGa:GameId'] = $gameId;   //will be zero for new game
        $fields['gpGa:GameTypeIndex'] = $this->game_gameType;
        $fields['gpGa:@KidPeriodId'] = $this->game_opponents_kidPeriodId[$i];
        $fields['gpGa:GamesWon'] = $this->game_opponents_wins[$i];
        $fields['gpGa:GamesLost'] = $this->game_opponents_losts[$i];
        $fields['gpGa:GamesDraw'] =$this->game_opponents_draws[$i];
        $fields['gpGa:Opponents'] = $opList;
        $fields['gpGa:OriginCode'] = GAME_ORIGIN_CLASS;
        $fields['gpGa:ClassDate'] = $this->game_classDate;
        $fields['gpGa:ModBy@StaffId'] = rc_getStaffId();  //????? make safe
        $fields['gpGa:ModWhen'] = $modWhen;
        if ($gameId==0) {
            $query = kcmRosterLib_db_insert($kcmGlobals->gb_db,'gp:games',$fields);
            $result = $kcmGlobals->gb_db->rc_query( $query );
            if ($result === FALSE) {
                $kcmGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
            }
            if ($atGameId==0) {
                $atGameId = $kcmGlobals->gb_db->insert_id;
                $fields = array();
                $fields['gpGa:@GameId'] = $atGameId;
                $query = kcmRosterLib_db_update($kcmGlobals->gb_db,'gp:games',$fields,"WHERE `gpGa:GameId` = '{$atGameId}'");
                $result = $kcmGlobals->gb_db->rc_query( $query );
                if ($result === FALSE) {
                    $kcmGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
                }
            }
        }
        else {
            assert('!empty($this->game_atGameId)');
            $query = kcmRosterLib_db_update($kcmGlobals->gb_db,'gp:games',$fields,"WHERE `gpGa:GameId` = '{$gameId}'");
            $result = $kcmGlobals->gb_db->rc_query( $query );
            if ($result === FALSE) {
                $kcmGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
            }
       }
    }   
    $this->game_atGameId = $atGameId;
    return $atGameId;
}

function gag_gameRecord_delete($kcmGlobals) {
    // only valid from game mode
   // $kcmGlobals->gb_sql->sql_transaction_start ($kcmGlobals);
    $this->gag_updateGameTotals($kcmGlobals,-1);
    $this->gag_updateKidPeriodTotals($kcmGlobals,-1);
    $query = "DELETE FROM `gp:games` WHERE `gpGa:@GameId` ='{$this->game_atGameId}'";
    $result = $kcmGlobals->gb_db->rc_query($query, __FILE__, __LINE__ );
    if ($result == FALSE) {
        $kcmGlobals->gb_sql->sql_fatalError(  __FILE__,__LINE__);
    }
    //$kcmGlobals->gb_sql->sql_transaction_end ($kcmGlobals);
}

function gag_getResultString($kcmGlobals, $pSep = ' ,') {
    // only valid from game mode
    $s = '';
    $sSep = '';
    for ($i=0; $i < $this->game_opponents_count; ++$i) {
        //$kidPeriod = $kcmGlobals->rst_curPeriod->perd_getKidPeriodObject($this->game_opponents_kidPeriodId[$i]);
        //$kidName = ($kidPeriod==NULL) ? '***' : $kidPeriod->kidPer_kidObject->rstKid_uniqueName;
        $kidName = $kcmGlobals->gb_roster->rst_curPeriod->perd_getKidUniqueName($this->game_opponents_kidPeriodId[$i]);
       $sep = '';
        $s1 = '';
        $tot = $this->game_opponents_wins[$i] + $this->game_opponents_losts[$i] + $this->game_opponents_draws[$i];
        if ($this->game_opponents_wins[$i] > 0) {
            $s1 = ($this->game_opponents_wins[$i] + $tot == 2)? 'Won' : ' Won ' . $this->game_opponents_wins[$i] ; 
            $sep = ', ';
        }
        if ($this->game_opponents_losts[$i] > 0) {
            $lost = ($this->game_opponents_losts[$i]  + $tot == 2)? 'Lost' : ' Lost ' . $this->game_opponents_losts[$i] ;
             $s1 .= $sep . $lost; 
             $sep = ', ';
       }
        if ($this->game_opponents_draws[$i]  > 0) {
            $draw = ($this->game_opponents_draws[$i] + $tot  == 2)? 'Draw' : ' Draw ' . $this->game_opponents_draws[$i];
             $s1 .= $sep . $draw; ; 
             $sep = ', ';
       }
        $s .= $sSep . $kidName . ' (' . $s1 . ')';
        $sSep = $pSep;
    }
     return $s;
   //return kcmRosterLib_getDesc_gameType($kcmGlobals->gb_form->chain->chn_posted_object->sesGame->game_gameType) . ' Game Saved: ' . $s;
}
 

function gag_getSavedString($kcmGlobals) {
    // only valid from game mode
    $s = '';
    $sSep = '';
    for ($i=0; $i < $this->game_opponents_count; ++$i) {
        //$kidPeriod = $kcmGlobals->rst_curPeriod->perd_getKidPeriodObject($this->game_opponents_kidPeriodId[$i]);
        //$kidName = $kidPeriod->kidPer_kidObject->rstKid_uniqueName;
        $kidName = $kcmGlobals->rst_curPeriod->perd_getKidUniqueName($this->game_opponents_kidPeriodId[$i]);
        $sep = '';
        $s1 = '';
        $tot = $this->game_opponents_wins[$i] + $this->game_opponents_losts[$i] + $this->game_opponents_draws[$i];
        if ($this->game_opponents_wins[$i] > 0) {
            $s1 = ($this->game_opponents_wins[$i] + $tot == 2)? 'Won' : ' Won ' . $this->game_opponents_wins[$i] ; 
            $sep = ', ';
        }
        if ($this->game_opponents_losts[$i] > 0) {
            $lost = ($this->game_opponents_losts[$i]  + $tot == 2)? 'Lost' : ' Lost ' . $this->game_opponents_losts[$i] ;
             $s1 .= $sep . $lost; 
             $sep = ', ';
       }
        if ($this->game_opponents_draws[$i]  > 0) {
            $draw = ($this->game_opponents_draws[$i] + $tot  == 2)? 'Draw' : ' Draw ' . $this->game_opponents_draws[$i];
             $s1 .= $sep . $draw; ; 
             $sep = ', ';
       }
        $s .= $sSep . $kidName . ' (' . $s1 . ')';
        $sSep = ', ';
    }
    return kcmRosterLib_getDesc_gameType($this->game_gameType) . ' Game Saved: ' . $s;
}
 
private function gag_updateKidPeriodTotals($kcmGlobals, $sign) {
       if ($this->game_gameType == GAME_TYPE_CHESS) {
            $winPoints = 10; 
        }
        else if ($this->game_gameType == GAME_TYPE_BUGHOUSE) {
            $winPoints = 3; 
        }
        else if ($this->game_gameType == GAME_TYPE_BLITZ) {
            $winPoints = 5; 
        }
        else {
            // should NEVER get here
            exit('Please notify office of error: gag-Update-Kid-Period-Totals-2');
        }
    for ($i=0; $i<$this->game_opponents_count; ++$i) {
        $pointChange = ( ( $this->game_opponents_wins[$i] * $winPoints ) + ($this->game_opponents_draws[$i] * round ($winPoints / 2) ) ) * $sign;
        $sql = array();
        $sql[] = "UPDATE `ro:kid_period`";
        $sql[] = "SET `rkPe:KcmGamePoints` = `rkPe:KcmGamePoints` + '{$pointChange}' ";
        $sql[] = "WHERE `rKPe:KidPeriodId` ='{$this->game_opponents_kidPeriodId[$i]}'";
        $query = implode( $sql, ' '); 
        $result = $kcmGlobals->gb_db->rc_query($query, __FILE__, __LINE__ );
        if ($result === FALSE) {
            $kcmGlobals->gb_sql->sql_fatalError(  __FILE__,__LINE__);
        }
    }   
}

private function gag_updateGameTotals($kcmGlobals, $sign) {
    // point totals
     // game totals    
    $gameTot = new kcm2_gat_game_totals;
    for ($i=0; $i<$this->game_opponents_count; ++$i) {
       $gameTot->gat_read($kcmGlobals, $this->game_opponents_kidPeriodId[$i], $this->game_gameType);
       $gameTot->gameTotals_win += $sign *  $this->game_opponents_wins[$i];
       $gameTot->gameTotals_lost += $sign * $this->game_opponents_losts[$i];
       $gameTot->gameTotals_total += $sign * $this->game_opponents_draws[$i];
       $gameTot->gat_write($kcmGlobals);
   }
}

} // end class


class kcm2_gagBu_game_of_game_bundle {
// in some cases will be reading the "game bundle" not as an internal list but as objects in the kidPer_tally
public $gagBu_count;    
public $gagBu_list;    
private $gagBu_Result;
private $gagBu_RecMode;

// filters
public $gagBu_filterKidPeriodId = NULL;   // set to view just one kid
public $gagBu_filterStaffId = NULL;     // set to view only those entered by one user
public $gagBu_filterClassDate = NULL;   // set to view only one class date
public $gagBu_filterGameType = NULL;    // set to view one game type
public $gagBu_filterOriginCode = NULL;  // set to view only one origin code
//????? sorts ??????
 
function gagBu_gameRecord_readBundle($kcmGlobals) {
    $fields = array();
    $fields[] = 'gpGa:GameId';
    $fields[] = 'gpGa:@ProgramId';
    $fields[] = 'gpGa:@PeriodId';
    $fields[] = 'gpGa:@GameId';
    $fields[] = 'gpGa:GameTypeIndex';
    $fields[] = 'gpGa:ClassDate';
    $fields[] = 'gpGa:WhenCreated';
    $fields[] = 'gpGa:ModBy@StaffId';
    $fields[] = 'gpGa:ModWhen';
    $fields[] = 'gpGa:@KidPeriodId';
    $fields[] = 'gpGa:GamesWon';
    $fields[] = 'gpGa:GamesLost';
    $fields[] = 'gpGa:GamesDraw';
    $fields[] = 'gpGa:OriginCode';
    $fieldList = "`" . implode("`,`", $fields) . "`";
    $sql[] =  'Select ' . $fieldList;
    $sql[] = ", GROUP_CONCAT(`gpGa:@KidPeriodId` SEPARATOR ',') AS `PlayerKidPerIds`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GameId` SEPARATOR ',') AS `PlayerGameIds`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GamesWon` SEPARATOR ',') AS `PlayerWon`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GamesLost` SEPARATOR ',') AS `PlayerLost`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GamesDraw` SEPARATOR ',') AS `PlayerDraw`";
    $sql[] = "FROM  `gp:games` ";
    $kcmGlobals->gb_sql->sql_whereFilterFirst ( $sql, $this->gagBu_filterKidPeriodId, 'gpGa:@KidPeriodId', 1 );
    $kcmGlobals->gb_sql->sql_whereFilterMore ( $sql, $kcmGlobals->gb_roster->rst_curPeriod->perd_periodId, 'gpGa:@PeriodId', 1 );
    if (($this->gagBu_filterGameType>=0) and ($this->gagBu_filterGameType<=2)) {
        $kcmGlobals->gb_sql->sql_whereFilterMore  ( $sql, $this->gagBu_filterGameType, 'gpGa:GameTypeIndex' );
    }    
    $kcmGlobals->gb_sql->sql_whereFilterMore  ( $sql, $this->gagBu_filterClassDate, 'gpGa:ClassDate' );
    $kcmGlobals->gb_sql->sql_whereFilterMore  ( $sql, $this->gagBu_filterStaffId, 'gpGa:ModBy@StaffId' );
    $kcmGlobals->gb_sql->sql_whereFilterMore  ( $sql, $this->gagBu_filterOriginCode, 'gpGa:OriginCode' );
    $sql[] = 'GROUP BY `gpGa:ClassDate`,`gpGa:@GameId`';   // seperate each class date
    $sql[] = 'ORDER BY `gpGa:ModWhen` DESC';
    $query = implode( $sql, ' '); 
    //echo '<hr>read_gameByGame<hr>' . implode($sql,PHP_EOL) . '<hr>'; 
    $this->gagBu_Result = $kcmGlobals->gb_db->rc_query( $query );
    if ($this->gagBu_Result === FALSE) {
        $kcmGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
    }
    // connot convert records, look for general records, etc due to grouping
    while($row=$this->gagBu_Result->fetch_array()) {
        $gameRec = new kcm2_gag_game_of_game;
        $gameRec->gag_gameRecord_loadRow($row);
        $this->gagBu_list[] = $gameRec;
    }
    $this->gagBu_count = count($this->gagBu_list);
}

} // end class

class kcm2_gat_game_totals {
public $gameTotals_gameTotalId;
public $gameTotals_programId;
public $gameTotals_periodId;
public $gameTotals_kidPeriodId;
public $gameTotals_gameType;
public $gameTotals_win;
public $gameTotals_lost;
public $gameTotals_total;

function __construct() {
}

function gat_clear($kcmGlobals, $kidPeriodId, $gameType) {
    $this->gameTotals_gameTotalId = 0;
    $this->gameTotals_programId = $kcmGlobals->gb_roster->rst_program->prog_programId;
    $this->gameTotals_periodId = $kcmGlobals->rst_curPeriod->perd_periodId;
    $this->gameTotals_kidPeriodId = $kidPeriodId;
    $this->gameTotals_gameType = $gameType;
    $this->gameTotals_win = 0;
    $this->gameTotals_lost = 0;
    $this->gameTotals_total = 0;
}

function gat_loadRow($row) {
   $this->gameTotals_gameTotalId = $row['gpGT:GameTotalId'];
   $this->gameTotals_programId = $row['gpGT:@ProgramId'];
   $this->gameTotals_periodId = $row['gpGT:@PeriodId'];
   $this->gameTotals_kidPeriodId = $row['gpGT:@KidPeriodId'];
   $this->gameTotals_gameType = $row['gpGT:GameTypeIndex'];
   $this->gameTotals_win = $row['gpGT:GamesWon'];
   $this->gameTotals_lost = $row['gpGT:GamesLost'];
   $this->gameTotals_total = $row['gpGT:GamesDraw'];
}

function gat_read( $kcmGlobals, $kidPeriodId, $gameType ) {
    $sql = array();
    $sql[] = "SELECT *";
    $sql[] = "FROM `gp:gametotals`";
    $sql[] ="WHERE `gpGT:@KidPeriodId` ='{$kidPeriodId}'";
    $sql[] ="   AND `gpGT:GameTypeIndex` ='" . intval($gameType) . "'";
    $query = implode( $sql, ' '); 
    $result = $kcmGlobals->gb_db->rc_query( $query );
    if ($result === FALSE) {
        $kcmGlobals->gb_sql->sql_fatalError(  __FILE__,__LINE__);
    }
    if ($result->num_rows == 0) {
        $this->gat_clear($kcmGlobals, $kidPeriodId, $gameType);
    }
    else {
        $row=$result->fetch_array();
        $this->gat_loadRow($row);
    }
}

function gat_write($kcmGlobals) {
   // $kcmGlobals->gb_sql->sql_transaction_start ($kcmGlobals);  ?????????????????
    $sql = array();
    //??????????? need to update modwhen modStaffId, etc
    if ($this->gameTotals_gameTotalId==0) {
        $sql[] = "INSERT INTO `gp:gametotals`";
        $sql[] = "SET `gpGT:@ProgramId` = '{$this->gameTotals_programId}' ";
        $sql[] = ", `gpGT:@PeriodId` = '{$this->gameTotals_periodId}' ";
        $sql[] = ", `gpGT:@KidPeriodId` = '{$this->gameTotals_kidPeriodId}' ";
        $sql[] = ", `gpGT:GameTypeIndex` = '{$this->gameTotals_gameType}' ";
        //$sql[] = ", `gpGt:PlayerStatus` = '{$this->gatPlayerStatus}' ";
        $sql[] = ", `gpGt:GamesWon` = '{$this->gameTotals_win}' ";
        $sql[] = ", `gpGt:GamesLost` = '{$this->gameTotals_lost}' ";
        $sql[] = ", `gpGt:GamesDraw` = '{$this->gameTotals_total}' ";
    }
     else {
        $sql[] = "UPDATE `gp:gametotals`";
        $sql[] = "SET `gpGt:GamesWon` =  '{$this->gameTotals_win}' ";
        $sql[] = ", `gpGt:GamesLost` =  '{$this->gameTotals_lost}' ";
        $sql[] = ", `gpGt:GamesDraw` =  '{$this->gameTotals_total}' ";
        $sql[] = "WHERE `gpGT:GameTotalId` = '{$this->gameTotals_gameTotalId}'";
      // $sql[] = "WHERE (`gpGT:GameTypeIndex` ='{$this->GameTypeIndex}') AND (`gpGT:@KidPeriodId` = '{$this->AtKidPeriodId}')";
    }    
    $query = implode( $sql, ' '); 
    $result = $kcmGlobals->gb_db->rc_query($query, __FILE__, __LINE__ );
    if ($result === FALSE) {
        $kcmGlobals->gb_sql->sql_fatalError(  __FILE__,__LINE__, $query);
    }
   // $kcmGlobals->gb_sql->sql_transaction_end ($kcmGlobals);
}

} // end class

?>
