<?php

// roster-system-data-roster.inc.php
//???????????????????? is it used ????????????????

// data that is shared for each period - even if different tabs
// data is mostly "static" - not changed by KCM (a few exceptions such as ASP info, point values)
// does not include "view" data such as list of games and points

const ROSTER_CHESS    = 0;
const ROSTER_BLITZ    = 1;
const ROSTER_BUGHOUSE = 2;

const SCORES_ORIGIN_KCM1     = 0;  // kcm1 - valid but should be converted on-the-run to another type,
const SCORES_ORIGIN_NOINFO   = 1;  // does not contain additional info - records can be combined into tally record without losing info except for when created
const SCORES_ORIGIN_WITHINFO = 2;  // contains additional info such as opponenents, category, etc (not combinable to tally)
const SCORES_ORIGIN_TALLY    = 3;  // only accessible from tally (kcm2 only)

const ROSTERKEY_KIDID        = 1;
const ROSTERKEY_KIDPROGRAMID = 1;
const ROSTERKEY_KIDPERIODID  = 3;

define('kcmMAX_POINT_CATEGORIES',12); //also in KCM roster

class pPr_program_extended_forRoster extends dbRecord_program {  // roster record
// roster is a program record joined with additional information about periods, kids, etc. in that program

// Could save memory by having an extended version(s) of this class with elements that are used only by just a few scripts

public $rst_cur_period = NULL;  // current period (NULL if none)

public static $rst_emptyGameUnit = NULL;
public static $rst_emptyGameBatch = NULL;
public static $rst_emptyPointUnit = NULL;
public static $rst_emptyPointBatch = NULL;

public $rst_classSchedule;
public $rst_classDateObject = NULL;  // current class date object from schedule (null if none)
public $rst_classDate = '';  // current class date object from schedule (null if none)

public $rst_map_period = array();  // period objects indexed by period ID
public $rst_map_kid = array(); // kid objects indexed by kid ID
public $rst_map_kidProgram = array();  // kid-program objects indexed by kid-program ID

public $rst_filter_kidPeriodId = NULL;

function __construct($appGlobals) {
    $this->rst_loadProgram_version($appGlobals,$appGlobals->gb_url_programId);
    $this->rst_loadProgram_andPeriods($appGlobals,$appGlobals->gb_url_programId);
    
   $this->rst_loadProgram_schedule($appGlobals,$appGlobals->gb_url_programId);  // need to know current default class date ???
    $this->rst_cur_period = $this->rst_map_period[$appGlobals->gb_url_periodId];
    self::$rst_emptyGameUnit = new stdData_game_root;
    self::$rst_emptyGameBatch = new stdData_gameUnit_batch;
    self::$rst_emptyPointUnit = new stdData_pointsUnit_record;
    self::$rst_emptyPointBatch = new stdData_pointsUnit_batch;
}

private function rst_loadProgram_version($appGlobals,$urlProgramId) {
    $query = "SELECT `pPr:KcmVersion`,`pPr:KcmPointCategories` FROM `pr:program` WHERE `pPr:ProgramId` = '{$urlProgramId}'";
    $result = $appGlobals->gb_db->rc_query(  $query );
    if (($result === FALSE) || ($result->num_rows == 0)) {
        $this->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
    }
    $row=$result->fetch_array();
    $version = $row['pPr:KcmVersion'];
    if ($version!=2) {
        //include_once('kcmI2-data-convert-version2.inc.php');  //???????
        //$convert = new kcm2_cvt_convert_to_version2;
        //$convert->cvt_convert_oneProgram($urlProgramId, $this->gb_db);
        $query = "UPDATE `pr:program` SET `pPr:KcmVersion` = '2' WHERE `pPr:ProgramId` = '{$urlProgramId}'";
        $appGlobals->gb_sql->sql_performQuery($query,__LINE__,NULL,'Update Program Record');
    }
}

private function rst_loadProgram_andPeriods($appGlobals,$programId) {
    $this->prog_programId = $programId;
    $fields = array();
    $fields = dbRecord_program::stdRec_addFieldList($fields);
    $fields = dbRecord_period::stdRec_addFieldList($fields);
    $fieldList = "`".implode("`,`",$fields)."`";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
    $sql[] = ", CONCAT_WS(' ',`pSc:NameShort`,`pPr:SchoolNameUniquifier`) AS `SchoolName`";
    $sql[] = "FROM `pr:program`";
    $sql[] = "JOIN  `pr:period` ON  `pPe:@ProgramId` = `pPr:ProgramId`";
    $sql[] = "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`";
    $sql[] = "WHERE `pPr:ProgramId` ='".$programId."'";
    $sql[] = "ORDER BY `pPe:TimeStart`";
    //print '<br><hr><br>'; print implode( $sql, '<br>'); print '<br><hr><br>';
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query( $query );
    if (($result === FALSE) || ($result->num_rows == 0)) {
        $this->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
    }
    $isFirstRecord = TRUE;
    while($row=$result->fetch_array()) {
        if ($isFirstRecord) {
            $this->stdRec_loadRow($row);
            $isFirstRecord = FALSE;
        }
        $period = new stdData_period_exRecord();
        $period->stdRec_loadRow($row);
        $this->rst_map_period[$period->perd_periodId] = $period;
    }
    if (empty($periodId)) {
        $periodId = NULL;
        $timeStart = NULL;
        foreach($this->rst_map_period as $period) {
            if  ( ($timeStart===NULL) or ($period->perd_timeStart < $timeStart) ) {
                $timeStart = $period->perd_timeStart;
                $periodId = $period->perd_periodId;
            }
        }
    }
}

function rst_loadProgram_schedule($appGlobals, $programId) {
    $this->rst_classSchedule = new schedule_oneProgram_eventDates();
    $this->rst_classSchedule->schProg_read($appGlobals->gb_db,$programId, $this);
    $this->rst_classDateObject = $this->rst_classSchedule->schProg_dateDefault;
    $this->rst_classDate = $this->rst_classSchedule->schProg_dateDefault;
}

function rst_load_rosterData($appGlobals, $chain, $readPeriod = TRUE) {
    // ?????? Use of this function should be eliminated and replaced with rst_load_kids( - this function not always needed
    $this->rst_load_kids($appGlobals);
}

function rst_load_kids($appGlobals, $kidPeriodId=NULL) {
    $fields = array();
    $fields = stdData_kid_exRecord::stdRec_addFieldList($fields);
    $fields = stdData_kidProgram_exRecord::stdRec_addFieldList($fields);
    $fields = stdData_kidPeriod_exRecord::stdRec_addFieldList($fields);
    $fieldList = "`".implode("`,`",$fields)."`";
    $fieldList .=", IF(COALESCE(`rKd:NickName`) = '', `rKd:FirstName`, `rKd:NickName`) as First";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
  //  $sql[] = ", GROUP_CONCAT(`rKPe:@PeriodId` SEPARATOR ',') AS `Periods`";
    $sql[] = "FROM `ro:kid_program`";
    $sql[] = "LEFT JOIN `ro:kid_period` ON `rKPe:@KidProgramId` = `rKPr:KidProgramId`";
    $sql[] = "INNER JOIN `ro:kid` ON `rKd:KidId` = `rKPr:@KidId`";
    $sql[] = "WHERE (`rKpr:@ProgramId`  = {$this->prog_programId})";
    if ($kidPeriodId >= 1) {
        $sql[] = "AND (`rKPe:KidPeriodId` = '{$kidPeriodId}')";
    }
    $sql[] = "AND (`rKPe:HiddenStatus` = '0')";
    $sql[] = "AND (`rKPe:InactiveStatus` = '0')";
    $sql[] = "ORDER BY `First`,`rKd:LastName`,`rKd:KidId`";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query( $query );
    if ($result === FALSE) {
        $this->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
    }
    $curKidProgramId = NULL;
    $curKidProgram   = NULL;
    $curKidId = NULL;
    $curKid = NULL;
    while($row=$result->fetch_array()) {
        //$kidProgramId = $row['rKPe:@KidProgramId'];
        $kidId= $row['rKd:KidId'];
        //$programId = $row['rKPr:@ProgramId'];
        $periodId  = $row['rKPe:@PeriodId'];
        $periodRec = $this->rst_map_period[$periodId];
        $kidProgramId = $row['rKPr:KidProgramId'];
        $kidPeriodId = $row['rKPe:KidPeriodId'];
        if ($kidId != $curKidId ) {
            $curKid = new stdData_kid_exRecord ($row);
            $curKid->stdRec_loadRow($row);
            $curKidId = $kidId;
            $this->rst_map_kid[$curKidId] = $curKid;  // harmless if it already exists
        }
        if ($kidProgramId != $curKidProgramId ) {
            $curKidProgram = new stdData_kidProgram_exRecord ($curKid);
            $curKidProgram->stdRec_loadRow($row);
            $curKidProgramId = $kidProgramId;
            $this->rst_map_kidProgram[$curKidProgram->kidPrg_kidProgramId] = $curKidProgram;
        }
        $kidPeriod = new stdData_kidPeriod_exRecord($curKid, $curKidProgram);
        $kidPeriod->stdRec_loadRow($row);
        $periodRec->perd_kidPeriodMap[$kidPeriodId] = $kidPeriod;
       //?????? $periodRec->perd_kidPeriodMap[$kidPeriodId] = $kidPeriod;
        //????? $periodRec->perd_kidMap[$kidPeriodId] = $curKid;
        $curKid->rstKid_kidProgramId = $kidProgramId;
        $curKid->rstKid_kidPeriodIdMap[$kidPeriodId] = $kidPeriodId;
    }
    $this->rst_process_duplicateNameUpdate();
}

private function rst_process_duplicateNameUpdate() {
    $soundHash = array();
    foreach ($this->rst_map_kid as $kidId => $kid) {
        $meta = metaphone($kid->rstKid_firstName);
        if (isset($soundHash[$meta])) {
            $soundHash[$meta][] = $kid;
        }
        else {
            $soundHash[$meta] = array($kid);
        }
    }
    foreach ($soundHash as $kidArray) {
        if (count($kidArray) >= 2) {
           foreach($kidArray as $kid) {
                // these kids sound the same
                $dif = 0;
                $last = count($kidArray)-1;
                for ($i=0; $i<$last; ++$i) {
                    for ($j=$i+1; $j<=$last; ++$j) {
                        // levenshtein() returns the number of characters you have to replace, insert or delete to transform str1 into str2
                        $kidiName = strtolower($kidArray[$i]->rstKid_firstName);
                        $kidjName = strtolower($kidArray[$j]->rstKid_firstName);
                        $dif = max($dif, levenshtein(  $kidiName, $kidjName ) );
                    }
                }
                // could enhance by using different less conspicous symbol if kids in seperate periods
                $sep = ($dif<=2) ? ' --> ' : ' -> ';
                foreach ($kidArray as $kid) {
                    $kid->rstKid_uniqueName = $kid->rstKid_firstName . $sep . $kid->rstKid_lastName;
                }
            }
        }
    }
}

//function rst_currentKid_set($appGlobals, $chain, $kidId, $kidProgramId, $kidPeriodId) {
// before calling this function you must load all the kidSameId
// after using this function get get information (name, grade) for kid in chain without loading all the kids
//}
//
//function rst_currentKid_load($appGlobals, $chain, $kidId, $kidProgramId, $kidPeriodId) {
//}

function rst_get_period($periodId = NULL) {
    if ($periodId==NULL) {
        return $this->rst_cur_period;
    }
    else {
        return isset($this->rst_map_period[$periodId]) ? $this->rst_map_period[$periodId] : NULL;
    }
}

// function            {
//     // if ( is_a($kidId,'dbRecord_kidPeriod') {
//     //     $kidId = $kidId->kidPer_kidId;
//     // }
//     return isset($this->rst_map_kid[$kidId]) ? $this->rst_map_kid[$kidId] : NULL;
// }
//
function rst_get_kidProgram($kidProgramId) {
    return isset($this->rst_map_kidProgram[$kidProgramId]) ? $this->rst_map_kidProgram[$kidProgramId] : NULL;
}

function rst_load_parents($appGlobals,$keyType,  $keyValue) {
    // needed for roster report (all kids), and kid view/edit (one kid)
    // includes kidProgram data when not using kidId (maybe need flag for this)
    $fields = array();
    $fields = dbRecord_parentFamilyCombo::stdRec_addFieldList($fields);
    $fields = stdData_kidProgram_exRecord::stdRec_addFieldList($fields);
    $fieldList = "`".implode("`,`",$fields)."`";
    $sql = array();
    //????????  need kid program so can load all parents for program
    $sql[] = "SELECT ".$fieldList;
    switch  ($keyType) {
        case ROSTERKEY_KIDID:
            $sql[] = "FROM `ro:kid`";
            $sql[] = "JOIN `ro:family` ON `rFa:FamilyId` = `rKd:@FamilyId`";
            $sql[] = "JOIN `ro:parentalunit` ON `rPU:@FamilyId` = `rFa:FamilyId`";
            $sql[] = "WHERE `rKd:KidId` ='{$keyValue}'";
            break;
        case ROSTERKEY_KIDPERIODID:
            $sql[] = "FROM `ro:kid_period`";
            $sql[] = "JOIN `ro:kid` ON `rKd:KidId` = `rKPe:@KidId`";
            $sql[] = "JOIN `ro:family` ON `rFa:FamilyId` = `rKd:@FamilyId`";
            $sql[] = "JOIN `ro:parentalunit` ON `rPU:@FamilyId` = `rFa:FamilyId`";
            $sql[] = "JOIN `ro:kid_program` ON `rKPr:KidProgramId` = `rKPe:@KidProgramId`";
            $sql[] = "WHERE `rKPe:KidPeriodId` ='{$keyValue}'";
            $sql[] = "GROUP BY `rPU:ParentId`";   // each row is a unique parent
            break;
        case ROSTERKEY_KIDPROGRAMID:
            $sql[] = "FROM `ro:kid_program`";
            $sql[] = "JOIN `ro:kid` ON `rKd:KidId` = `rKPr:@KidId`";
            $sql[] = "JOIN `ro:family` ON `rFa:FamilyId` = `rKd:@FamilyId`";
            $sql[] = "JOIN `ro:parentalunit` ON `rPU:@FamilyId` = `rFa:FamilyId`";
            $sql[] = "JOIN `ro:kid_program` ON `rKPr:KidProgramId` = `rKPe:@KidProgramId`";
            $sql[] = "WHERE `rKPr:KidProgramId` ='{$keyValue}'";
            $sql[] = "GROUP BY `rPU:ParentIdt`";   // each row is a unique parent
            break;
    }
    $sql[] = "ORDER BY `rPU:ContactPriority`";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query( $query );
    if ($result === FALSE) {
        $appGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
    }
    while ($row=$result->fetch_array()) {
        $kidId = $row['rKd:KidId'];
        $kidProgramId = $row['rKPr:KidProgramId'];
        $kid = $this->rst_get_kid($kidId);
        if ( $kid->rstKid_parent == NULL) {
            $kid->rstKid_parent = new dbRecord_parentFamilyCombo;
            if ( !isset($this->rst_map_kidProgram[$kidProgramId]) ) {
                $kidProgram = new stdData_kidProgram_exRecord;
                $kidProgram->stdRec_loadRow($row);
                $this->rst_map_kidProgram[$kidProgramId] = $kidProgram;
            }
       }
        $kid->rstKid_parent->stdRec_loadRow($row);
    }
}

function rst_addFilter(&$sql, $fieldName, $value) {
    if ($value!==NULL) {
        $sql[] = "'AND (`{$fieldName}`='{$value}')";
    }
}

} // end class


//======
//============
//==================
//========================
//=   Period Data
//=   For each period-id, this is the same for all pages in browser ????????????????????????????????
//=   i.e. - can store one copy in session for all the pages for current period (not per browser tab/page)
//========================
//==================
//============
//======

class dbRecord_period extends draff_database_record {

const DB_TABLE_NAME  = '';
const DB_TABLE_INDEX = '';
const DB_MOD_WHEN    = '';
const DB_MOD_WHO     = '';
const DB_SELECT_FIELDS  = array();

public $perd_periodId;
public $perd_programId;
public $perd_periodName;
public $perd_timeStart;
public $perd_timeEnd;
public $perd_timeStartDesc;
public $perd_timeEndDesc;
public $perd_descShort;
public $perd_descLong;
public $perd_minGrade;
public $perd_maxGrade;
public $perd_gradeGroups;

function __construct() {
}

static function stdRec_addFieldList($fldList, $flags=0) {
    $fldList[] = 'pPe:PeriodId';
    $fldList[] = 'pPe:@ProgramId';
    $fldList[] = 'pPe:PeriodName';
    $fldList[] = 'pPe:TimeStart';
    $fldList[] = 'pPe:TimeEnd';
    $fldList[] = 'pPe:MinGradeAccepted';
    $fldList[] = 'pPe:MaxGradeAccepted';
    $fldList[] = 'pPe:KcmGradeGroups';
    return $fldList;
}

static function cSS_appendSelect($queryCommand) {
    $queryCommand->draff_sql_selectFields('pPe:PeriodId','pPe:@ProgramId');
    $queryCommand->draff_sql_selectFields('pPe:PeriodName','pPe:TimeStart','pPe:TimeEnd');
    $queryCommand->draff_sql_selectFields('pPe:MinGradeAccepted','pPe:MaxGradeAccepted','pPe:KcmGradeGroups');
}

function stdRec_loadRow($row, $flags=0) {
    $this->perd_programId     = $row['pPr:ProgramId'];
    $this->perd_periodId      = $row['pPe:PeriodId'];
    $this->perd_periodName    = $row['pPe:PeriodName'];
    $this->perd_timeStart     = $row['pPe:TimeStart'];
    $this->perd_timeEnd       = $row['pPe:TimeEnd'];
    $this->perd_descShort     = $this->perd_periodName;
    $this->perd_minGrade      = $row['pPe:MinGradeAccepted'];
    $this->perd_maxGrade      = $row['pPe:MaxGradeAccepted'];
    $this->perd_gradeGroups   = new roster_gradeGroups($row['pPe:KcmGradeGroups'],$this->perd_minGrade,$this->perd_maxGrade);
    $this->perd_timeStartDesc = draff_timeAsString($this->perd_timeStart);
    $this->perd_timeEndDesc   = draff_timeAsString($this->perd_timeEnd);
    $this->perd_descLong      = $this->perd_periodName . ' (' . $this->perd_timeStartDesc . '-' . $this->perd_timeEndDesc . ')';
}

function perd_getPeriodName() {  //??????
    return $this->perd_periodName; // ." Period ".$this->TimeStartDesc. " - ".$this->TimeEndDesc;
}

function perd_getKidPeriodObject($kidPeriodId) {
    return isset($this->perd_kidPeriodMap[$kidPeriodId]) ? $this->perd_kidPeriodMap[$kidPeriodId] :null;
    //  TEST ABOVE BEFORE DELETING BELOW
    //foreach ($this->perd_kidPeriodMap as $kid) {
    //    if ($kid->kidPer_kidPeriodId == $kidPeriodId)
    //        return $kid;
    //}
    //return NULL;
}

function perd_getKidUniqueName($kidPeriodId) {
    return isset($this->perd_kidPeriodMap[$kidPeriodId]) ? $this->perd_kidPeriodMap[$kidPeriodId]->rstKid_uniqueName : 'Withdrawn-Kid';
    //  TEST ABOVE BEFORE DELETING BELOW
    //foreach ($this->perd_kidPeriodMap as $kid) {
    //    if ($kid->kidPer_kidPeriodId == $kidPeriodId)
    //        return $kid->kidPer_kidObject->rstKid_uniqueName;
    //}
    //return 'Withdrawn-Kid';  // if possible keep track of????
}

}  // end class

class stdData_period_exRecord extends dbRecord_period {
// computed
public $perd_kidPeriodMap = array();  // kid-period objects indexed by kid-period ID
public $rst_filter_gameType    = NULL;  // chess, blitz, etc NULL=all game types
public $rst_filter_classDate   = NULL;  // null = all class dates
public $rst_filter_scoreOrigin = NULL;
public $rst_filter_kidPeriodId = NULL;  // null all kids in period
// Computed
//private $ProgramClassMeetDayDowDesc;
//public $ProgramClassDateForPoints;  // also for games ????????????????????????


function __construct() {
}

function perd_load_scores_weekly( $appGlobals, $classDate=NULL, $kidPeriodId=NULL, $gameType=NULL, $periodId=NULL,$mode='u') {
    //$this->perd_load_gameTally($appGlobals, $classDate=NULL, $gameType=NULL, $periodId=NULL, $kidPeriodId=NULL);
    //$this->perd_load_gameBatch($appGlobals, $classDate=NULL, $kidPeriodId=NULL, $gameType=NULL, $periodId=NULL,$mode='u')
    //$this->perd_load_pointBatch($appGlobals, $classDate=NULL, $kidPeriodId=NULL, $gameType=NULL, $periodId=NULL,$mode='u')
}

function perd_load_scores_semester( $appGlobals, $classDate=NULL, $kidPeriodId=NULL, $gameType=NULL, $periodId=NULL,$mode='u') {
    //$this->perd_load_gameTotals($appGlobals, $kidPeriodId=NULL, $gameType=NULL, $periodId=NULL);
}



function perd_load_gameTotals($appGlobals, $kidPeriodId=NULL, $gameType=NULL, $periodId=NULL) {
    if ($periodId<1) {
        $periodId = $this->rst_cur_period->perd_periodId;
    }
    $gameType = intval($gameType);
    $fields = array();
    $fields = stdData_gameTotals_record::stdRec_addFieldList($fields);
    $fieldList = "`".implode("`,`",$fields)."`";
    $sql = array();
    $sql[] = "SELECT " . $fieldList;
    $sql[] = "FROM `gp:gametotals`";
    $sql[] ="WHERE (`gpGT:@PeriodId` = '{$periodId}')";
    // $this->rst_addFilter($sql, 'gpGT:@KidPeriodId', $kidPeriodId);
    // $this->rst_addFilter($sql, 'gpGT:GameTypeIndex', $gameType);
    if ($kidPeriodId >= 1) {
        $sql[] ="  AND (`gpGT:@KidPeriodId` ='{$kidPeriodId}')";
    }
    if ($gameType !==NULL ) {
        $gameType = intval($gameType);
        $sql[] ="   AND (`gpGT:GameTypeIndex` ='{$gameType}')";
    }
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query( $query );
    if ($result === FALSE) {
        $appGlobals->gb_sql->sql_fatalError(  __FILE__,__LINE__);
    }
    while($row=$result->fetch_array()) {
        $kidPeriodId = $row['gpGT:@KidPeriodId'];
        if (!isset($this->perd_kidPeriodMap[$kidPeriodId])) {
            continue;  // total of kid that has withdrawn and not in kid list
        }
        $kidPeriod = $this->perd_kidPeriodMap[$kidPeriodId];
        $totalRec = new stdData_gameTotals_record;
        $totalRec->stdRec_loadRow_gameTotals($row);
        switch ($totalRec->gameTotals_gameType) {
            case ROSTER_CHESS:    $kidPeriod->kidPer_totals_chess = $totalRec; break;
            case ROSTER_BLITZ:    $kidPeriod->kidPer_totals_blitz = $totalRec; break;
            case ROSTER_BUGHOUSE: $kidPeriod->kidPer_totals_bug   = $totalRec; break;
        }
    }
}

function perd_load_gameTally($appGlobals, $classDate=NULL, $gameType=NULL, $periodId=NULL, $kidPeriodId=NULL) {
    $result = $this->perd_execute_gameQuery($appGlobals, $classDate, $gameType, $periodId, $kidPeriodId,  't');
    $curKey = '';
    $curGame = NULL;
    while($row=$result->fetch_array()) {
        $game = new stdData_gameUnit_record;
        $game->stdRec_loadRow($row);
        $newKey = $game->game_classDate . '-' . $game->game_gameType;
        if ($newKey == $curKey) {
            // add results to cur game
            // save curGame
            // delete $game record
            // continue
        }
        $curKey = $newKey;
        $curGame = $game;
        $kidPeriodId = $game->game_kidPeriodId;
        if (!isset($this->perd_kidPeriodMap[$kidPeriodId])) {
            continue;  // total of kid that has withdrawn and not in kid list
        }
        $kidPeriod = $this->perd_kidPeriodMap[$kidPeriodId];
        switch ($game->game_gameType) {
            case ROSTER_CHESS:    $kidPeriod->kidPer_tally_chess  = $game; break;
            case ROSTER_BLITZ:    $kidPeriod->kidPer_tally_blitz  = $game; break;
            case ROSTER_BUGHOUSE: $kidPeriod->kidPer_tally_bug    = $game; break;
        }
    }
}

function perd_load_gameBatch($appGlobals, $classDate=NULL, $kidPeriodId=NULL, $gameType=NULL, $periodId=NULL,$mode='u') {
    $result = $this->perd_execute_gameQuery($appGlobals, $classDate, $gameType, $periodId, $kidPeriodId,  $mode);
    while($row=$result->fetch_array()) {
        $game = new stdData_gameUnit_record;
        $game->stdRec_loadRow($row);
        $kidPeriodId = $game->game_kidPeriodId;
        if (!isset($this->perd_kidPeriodMap[$kidPeriodId])) {
            continue;  // total of kid that has withdrawn and not in kid list
        }
        $kidPeriod = $this->perd_kidPeriodMap[$kidPeriodId];
        switch ($game->game_gameType) {
            case ROSTER_CHESS:
                $gameBatch = $kidPeriod->kidPer_batch_chess;
                if ($gameBatch==NULL) {
                    $gameBatch = new stdData_gameUnit_batch;
                    $kidPeriod->kidPer_batch_chess = $gameBatch;
                }
                break;
            case ROSTER_BLITZ:
               $gameBatch = $kidPeriod->kidPer_batch_blitz;
                if ($gameBatch==NULL) {
                    $gameBatch = new stdData_gameUnit_batch;
                    $kidPeriod->kidPer_batch_blitz = $gameBatch;
                }
                break;
            case ROSTER_BUGHOUSE:
               $gameBatch = $kidPeriod->kidPer_batch_bug;
                if ($gameBatch==NULL) {
                    $gameBatch = new stdData_gameUnit_batch;
                    $kidPeriod->kidPer_batch_bug = $gameBatch;
                }
                break;
        }
        $gameBatch->gameBatch_addGame($game);
    }
}

function perd_load_pointUnits($appGlobals, $classDate=NULL, $periodId=NULL, $kidPeriodId=NULL,  $originCode=1) {
    $result = $this->perd_execute_pointQuery($appGlobals, $classDate, $periodId, $kidPeriodId,  'u');
    while ($row=$result->fetch_array()) {
        $pnt = new stdData_pointsUnit_record;
        $pnt->pnt_loadRow($row);
        $kidPeriodId = $pnt->pnt_kidPeriodId;
        if (!isset($this->perd_kidPeriodMap[$kidPeriodId])) {
            continue;  // total of kid that has withdrawn and not in kid list
        }
        $kidPeriod = $this->perd_kidPeriodMap[$kidPeriodId];
        if ( $kidPeriod->kidPer_batch_points == NULL ) {
            $kidPeriod->kidPer_batch_points = new stdData_pointsUnit_batch;
        }
        $kidPeriod->kidPer_batch_points->pointBatch_addPointUnit($pnt);
    }
}

function rst_load_pointsTally($appGlobals, $classDate=NULL, $periodId=NULL, $kidPeriodId=NULL,  $originCode=1) {
    $result = $this->perd_execute_pointQuery($appGlobals, $classDate, $periodId, $kidPeriodId,  't');
    $curKey = '';
    $curPoints = NULL;
    while ($row=$result->fetch_array()) {
        $pnt = new stdData_pointsUnit_record;
        $pnt->pnt_loadRow($row);
        $newKey = $pnt->pnt_kidPeriodId . '-' . $pnt->pnt_classDate;
        if ($newKey == $curKey) {
            // add results to cur points
            // save curPoints
            // delete $pnt record
            // continue
        }
        $curKey = $newKey;
        $curPoints = $pnt;
        $kidPeriodId = $pnt->pnt_kidPeriodId;
        if (!isset($this->perd_kidPeriodMap[$kidPeriodId])) {
            continue;  // total of kid that has withdrawn and not in kid list
        }
        $kidPeriod = $this->perd_kidPeriodMap[$kidPeriodId];
        $kidPeriod->kidPer_tally_points = $pnt;
    }
}

private function perd_execute_gameQuery($appGlobals, $classDate, $gameType, $periodId, $kidPeriodId,  $originCode) {
    if ($periodId<1) {
        $periodId = $this->rst_cur_period->perd_periodId;
    }
    $fields = array();
    $fields = stdData_gameUnit_record::stdRec_addFieldList($fields);
    $fieldList = "`".implode("`,`",$fields)."`";
    $sql = array();
    $sql[] =  'Select ' . $fieldList;
    $sql[] = "FROM `gp:games`";
    $sql[] = "WHERE (`gpGa:@PeriodId`='{$periodId}')";
    if (!empty($classDate)) {
        $sql[] = "   AND (`gpGa:ClassDate`='{$classDate}')";
    }
    if (!empty($kidPeriodId)) {
        $sql[] = "   AND (`gpGa:@KidPeriodId`='{$kidPeriodId}')";
    }
    if (!empty($gameType)) {
        $sql[] = "   AND (`gpGa:GameTypeIndex`='{$gameType}')";
    }
    if ($originCode=='u') {
        $sql[] = "   AND (IFNULL(`gpGa:Opponents`,'')<>'')";
        //$sql[] = "   AND (`gpGa:OriginCode`='{$originCode}')";
    }
    if ($originCode=='t') {
        $sql[] = "   AND (IFNULL(`gpGa:Opponents`,'')='')";
        //$sql[] = "   AND (`gpGa:OriginCode`='{$originCode}')";
    }
    $sql[] = "ORDER BY `gpGa:@KidPeriodId`, `gpGa:ClassDate`,  `gpGa:GameTypeIndex`,`gpGa:OriginCode` DESC";     //????????????????
    $query = implode( $sql, ' ');
    return $appGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
}

private function perd_execute_pointQuery($appGlobals, $classDate=NULL, $periodId=NULL, $kidPeriodId=NULL,  $originCode) {
    if ($periodId<1) {
        $periodId = $this->rst_cur_period->perd_periodId;
    }
    $fields = array();
    $fields = stdData_pointsUnit_record::stdRec_addFieldList($fields);
    $fieldList = "`".implode("`,`",$fields)."`";
    $sql = array();
    $sql[] =  'Select ' . $fieldList;
    $sql[] = "FROM `gp:points`";
    $sql[] = "JOIN `ro:kid_period` ON `rKPe:KidPeriodId` = `gpPo:@KidPeriodId`";
    $sql[] = "WHERE (`rKPe:@PeriodId`='{$periodId}')";
    if (!empty($classDate)) {
        $sql[] = "   AND (`gpPo:ClassDate`='{$classDate}')";
    }
    if (!empty($kidPeriodId)) {
        $sql[] = "   AND (`gpPo:@ProgramId`='{$kidPeriodId}')";
    }
    //if ($originCode) {
    //    $sql[] = "   AND (`gpPo:OriginCode`='{$originCode}')";
    //}
    $sql[] = "ORDER BY `gpPo:@KidPeriodId`, `gpPo:ClassDate`";     //????????????????
    $query = implode( $sql, ' ');
    return $appGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
}

function perd_force_gameObject ($gameObject) {
    if ($gameObject == NULL) {
        $gameObject = new stdData_gameUnit_record;
    }
    return $gameObject;
}

function perd_force_pointsObject ($pointsObject) {
    if ($pointsObject == NULL) {
        $pointsObject = new stdData_pointsUnit_record;
    }
    return $pointsObject;
}

function perd_get_gameObject($allGames, $gameType) {
    if ($allGames == NULL) {
        return self::$rst_emptyGameUnit;
    }
    $gameObject = $allGames->gameAll_get($gameType);
    return $gameObject==NULL ? self::rst_emptyGameUnit : $gameObject;
}

function perd_get_kidPeriod($kidPeriodId) {
    return isset($this->perd_kidPeriodMap[$kidPeriodId]) ? $this->perd_kidPeriodMap[$kidPeriodId] : NULL;
}

//function perd_get_kid($kidId) {
//    return isset($this->perd_kid[$kidId]) ? $this->perd_kidMap[$kidId] : NULL;
//}

}  // end class

//=========================================================
//==========================================================
//==================================================================

class dbRecord_parentFamilyCombo {
// a parent "record" is joined with the family record and needs each parent's row to be loaded
public $rstParent_name = array();
public $rstParent_home = array();
public $rstParent_cell = array();
public $rstParent_work = array();
public $rstParent_emergency_name;
public $rstParent_emergency_phone;

function __construct() {
}

static function stdRec_addFieldList($fldList, $flags=0) {
    $fldList[] = 'rKd:KidId';
    $fldList[] = 'rFa:FamilyId';
    $fldList[] = 'rFa:EmergencyPhone';
    $fldList[] = 'rFa:EmergencyName';
    $fldList[] = 'rPU:ParentId';
    $fldList[] = 'rPU:Email';
    $fldList[] = 'rPU:FirstName';
    $fldList[] = 'rPU:LastName';
    $fldList[] = 'rPU:HomePhone';
    $fldList[] = 'rPU:CellPhone';
    $fldList[] = 'rPU:WorkPhone';
    $fldList[] = 'rPU:ContactPriority';
    return $fldList;
}

static function cSS_appendSelect($queryCommand) {
    $queryCommand->draff_sql_selectFields('rPU:ParentId','rKd:KidId','rFa:FamilyId');
    $queryCommand->draff_sql_selectFields('rFa:EmergencyPhone','rFa:EmergencyName');
    $queryCommand->draff_sql_selectFields('rPU:Email','rPU:FirstName','rPU:LastName');
    $queryCommand->draff_sql_selectFields('rPU:HomePhone','rPU:CellPhone','rPU:WorkPhone','rPU:ContactPriority');
    $queryCommand->draff_sql_selectFields();
}

function stdRec_loadRow($row, $flags=0) {
    // loads a row for each parent
    $this->rstKid_kidId = $row['rKd:KidId'];
    $this->rstParent_name[] =  $row['rPU:FirstName'] . ' ' . $row['rPU:LastName'];
    $this->rstParent_home[] = $row['rPU:HomePhone'];
    $this->rstParent_cell[] = $row['rPU:CellPhone'];
    $this->rstParent_work[] = $row['rPU:WorkPhone'];
    $this->rstParent_emergency_name =  $row['rFa:EmergencyPhone'];
    $this->rstParent_emergency_phone = $row['rFa:EmergencyName'];
}

}  // end class

class dbRecord_kid  extends draff_database_record {

const DB_TABLE_NAME  = '';
const DB_TABLE_INDEX = '';
const DB_MOD_WHEN    = '';
const DB_MOD_WHO     = '';
const DB_SELECT_FIELDS  = array();

public $rstKid_kidId;
public $rstKid_firstName;           // is nickname and dup indicator if applicable
public $rstKid_lastName;
public $rstKid_nickName;
public $rstKid_uniqueName;    // shortest form of unique name   (computed after all kids are loaded)
public $rstKid_gradeCode;
public $rstKid_gradeDesc;  //-- computed
public $rstKid_notesCoach;  //-- computed
public $rstKid_notesSM;     //-- computed
public $rstKid_photoRelease;  //-- computed
public $rstKid_semesterFirst = NULL;  //-- computed
public $rstKid_semesterLast = NULL;  //-- computed

function __construct() {
}

static function stdRec_addFieldList($fldList, $flags=0) {
    //????????? need flag to get kid parent/pickup/notes info
    $fldList[] = 'rKd:KidId';
    $fldList[] = 'rKd:EarliestYearSemester';
    $fldList[] = 'rKd:LatestYearSemester';
    $fldList[] = 'rKd:FirstName';
    $fldList[] = 'rKd:LastName';
    $fldList[] = 'rKd:NickName';
    $fldList[] = 'rKd:Grade';  // adjusted for GradeSchoolYear
    $fldList[] = 'rKd:GradeSchoolYear';
    $fldList[] = 'rKd:NotesForCoach';
    $fldList[] = 'rKd:NotesForSiteLeader';
    $fldList[] = 'rKd:PhotoReleaseStatus';
    return $fldList;
}

static function stdRec_appendSelect($queryCommand) {
    $queryCommand->draff_sql_selectFields('rKd:KidId');
    $queryCommand->draff_sql_selectFields('rKd:EarliestYearSemester','rKd:LatestYearSemester');
    $queryCommand->draff_sql_selectFields('rKd:FirstName','rKd:LastName','rKd:NickName');
    $queryCommand->draff_sql_selectFields('rKd:Grade','rKd:GradeSchoolYear');
    $queryCommand->draff_sql_selectFields('rKd:NotesForCoach','rKd:NotesForSiteLeader','rKd:PhotoReleaseStatus');
}

function stdRec_loadRow($row, $flags=0) {
    $this->rstKid_kidId = $row['rKd:KidId'];
    $this->rstKid_firstName = $row['First'];
    $this->rstKid_lastName  = $row['rKd:LastName'];
    $this->rstKid_nickName = $row['rKd:NickName'];
    $this->rstKid_uniqueName = $row['First'];
    $this->rstKid_gradeCode = $row['rKPr:Grade'];
    $this->rstKid_gradeDesc = kcmRosterLib_getDesc_grade($this->rstKid_gradeCode);
    $this->rstKid_notesCoach     = $row['rKd:NotesForCoach'];
    $this->rstKid_notesSM        = $row['rKd:NotesForSiteLeader'];
    $this->rstKid_photoRelease   = $row['rKd:PhotoReleaseStatus'];
    $this->rstKid_semesterFirst  = $row['rKd:EarliestYearSemester'];
    $this->rstKid_semesterLast   = $row['rKd:LatestYearSemester'];
}

}  // end class

class stdData_kid_exRecord extends dbRecord_kid {
// only used when loadKid_parents is called
public $rstKid_parent = NULL;  //-- computed
public $rstKid_kidPeriodIdMap = array();  //-- computed
public $rstKid_kidProgramId = NULL;  //-- computed  //????? only used once ??????

function __construct() {
}

}  // end class

Class dbRecord_kidProgram {
public $kidPrg_kidProgramId;
public $kidPrg_programId;
public $kidPrg_kidId;
public $kidPrg_teacher;
public $kidPrg_pickupCode;
public $kidPrg_pickupNotes;
public $kidPrg_grade;
public $kidPrg_pairingLabelId;
public $kidPrg_nameLabelNote;

function __construct($kidDbRecord) {
}

static function stdRec_addFieldList($fldList, $flags=0) {
    $fldList[] = 'rKPr:KidProgramId';
    $fldList[] = 'rKPr:@ProgramId';
    $fldList[] = 'rKPr:@KidId';
    $fldList[] = 'rKPr:TeacherName';
    $fldList[] = 'rKPr:PickupCode';
    $fldList[] = 'rKPr:PickupNotes';
    $fldList[] = 'rKPr:Grade';
    $fldList[] = 'rKPr:KcmPairingLabelId';
    $fldList[] = 'rKPr:KcmNameLabelNote';
    return $fldList;
}

static function stdRec_appendSelect($queryCommand) {
    $queryCommand->draff_sql_selectFields('rKPr:KidProgramId');
    $queryCommand->draff_sql_selectFields('rKPr:@ProgramId','rKPr:@KidId');
    $queryCommand->draff_sql_selectFields('rKPr:TeacherName','rKPr:PickupCode','rKPr:PickupNotes');
    $queryCommand->draff_sql_selectFields('rKPr:Grade', 'rKPr:KcmNameLabelNote');
    $queryCommand->draff_sql_selectFields('rKPr:KcmPairingLabelId');
}

function stdRec_loadRow($row, $flags=0) {
    $this->kidPrg_programId = $row['rKPr:@ProgramId'];
    $this->kidPrg_kidProgramId = $row['rKPr:KidProgramId'];
    $this->kidPrg_kidId = $row['rKPr:@KidId'];
    $this->kidPrg_teacher = $row['rKPr:TeacherName'];
    $this->kidPrg_pickupCode = $row['rKPr:PickupCode'];
    $this->kidPrg_pickupNotes = $row['rKPr:PickupNotes'];
    $this->kidPrg_grade = $row['rKPr:Grade'];
    $this->kidPrg_pairingLabelId = $row['rKPr:KcmPairingLabelId'];
    $this->kidPrg_nameLabelNote  = $row['rKPr:KcmNameLabelNote'];
}

}  // end class


class stdData_kidProgram_exRecord extends dbRecord_kidProgram {
public $kidPer_nameConfict;  // computed later  ????????????????? never computed
// computed
//public $kidPrg_kidPeriodArray;  // this could be a circular reference

function __construct() {
}

}  // end class

Class dbRecord_kidPeriod {
public $kidPer_kidPeriodId;
public $kidPer_kidProgramId;
public $kidPer_periodId;
public $kidPer_kidId;
public $kidPer_parentHelperStatus;
public $kidPer_parentHelperName;
public $kidPer_totalGamePoints;
public $kidPer_totalGeneralPoints;

function __construct($kid, $kidProgram ) {
//    $this->kidPer_kidObject = $kid;
   // $this->kidPer_kidProgramObject = $kidProgram;
}

static function stdRec_addFieldList($fldList, $flags=0) {
    $fldList[] = 'rKPe:KidPeriodId';
    $fldList[] = 'rKPe:@KidId';
    $fldList[] = 'rKPe:@KidProgramId';
    $fldList[] = 'rKPe:@PeriodId';
    $fldList[] = 'rKPe:ParentHelperStatus';
    $fldList[] = 'rKPe:ParentHelperName';
    $fldList[] = 'rkPe:KcmGamePoints';
    $fldList[] = 'rKPe:KcmGeneralPoints';   // kcm2 point totals
    return $fldList;
}

static function stdRec_appendSelect($queryCommand) {
    $queryCommand->draff_sql_selectFields('rKPe:KidPeriodId');
    $queryCommand->draff_sql_selectFields('rKPe:@KidId','rKPe:@KidProgramId','rKPe:@PeriodId');
    $queryCommand->draff_sql_selectFields('rKPe:ParentHelperStatus','rKPe:ParentHelperName','rkPe:KcmGamePoints');
    $queryCommand->draff_sql_selectFields('rKPe:KcmGeneralPoints');
}

function stdRec_loadRow($row, $flags=0) {
    $this->kidPer_periodId = $row['rKPe:@PeriodId'];
    $this->kidPer_kidId = $row['rKPe:@KidId'];
    $this->kidPer_kidPeriodId = $row['rKPe:KidPeriodId'];
    $this->kidPer_kidProgramId = $row['rKPe:@KidProgramId'];
    $this->kidPer_parentHelperStatus = $row['rKPe:ParentHelperStatus'];;
    $this->kidPer_parentHelperName = $row['rKPe:ParentHelperName'];;
    $this->kidPer_totalGamePoints = $row['rkPe:KcmGamePoints'];;
    $this->kidPer_totalGeneralPoints = $row['rKPe:KcmGeneralPoints'];;
}

}  // end class

class stdData_kidPeriod_exRecord extends dbRecord_kidPeriod {
// below game and point fields only used when load functions are called to load the data
// Could save memory by having an extended version of this class with the below
public $kidPer_batch_points    = NULL;   // batches can be for one classDate or the entire semester
public $kidPer_batch_chess     = NULL;
public $kidPer_batch_blitz     = NULL;
public $kidPer_batch_bug       = NULL;
public $kidPer_tally_points    = NULL;
public $kidPer_tally_chess     = NULL;   // tally is for one classDate
public $kidPer_tally_blitz     = NULL;
public $kidPer_tally_bug       = NULL;
public $kidPer_totals_chess    = NULL;   // totals are for the entire semester
public $kidPer_totals_blitz    = NULL;
public $kidPer_totals_bug      = NULL;
public $kidPer_weekScores      = NULL;
public $kidPer_semesterScores  = NULL;
// below objects are only loaded as necessary depending on usage
public $kidPer_scores_weekly   = NULL;   // scores reported on tally sheet (not specific results, only weekly tally) - from games - origin=tally
public $kidPer_scores_semester = NULL;   // semester scores (totals, no details) - from game toitals
public $kidPer_scores_specific = NULL;   // specific games and points (origin<>tally), can be weekly or all

function __construct() {
}

function kidPeriod_getGameBatch($gameType) {
    switch ($gameType) {
        case ROSTER_CHESS:    $batch = $this->kidPer_batch_chess; break;
        case ROSTER_BLITZ:    $batch = $this->kidPer_batch_blitz; break;
        case ROSTER_BUGHOUSE: $batch = $this->kidPer_batch_bug; break;
    }
    if ($batch==NULL) {
        $batch = pPr_program_extended_forRoster::$rst_emptyGameBatch;
    }
    return $batch;
}

}  // end class

class scores_history {

function __construct() {
}

}

class scores_weekly {

function __construct() {
}

} // end class

class scores_semester {

function __construct() {
}

} // end class

class scores_group {
public $scr_chess   = NULL;  // stdData_gameUnit_extended (origin = tally)
public $scr_blitz   = NULL;  // stdData_gameUnit_extended (origin = tally)
public $scr_bug     = NULL;  // stdData_gameUnit_extended (origin = tally)
public $scr_points  = NULL;  // stdData_pointsUnit (origin = tally)

function __construct() {
}

function scr_getGameObject($gameType) {
        switch ($gameType) {
            case ROSTER_CHESS:
                return $this->scr_chess;
                break;
            case ROSTER_BLITZ:
                return $this->scr_blitz;
                break;
            case ROSTER_BUGHOUSE:
                return $this->scr_bug;
                break;
            default:
                return NULL;
                break;
        }
}

} // end class

class scores_xsemester {  // used for one specified week and one semester of current period
//public $sdr_tally   = NULL;  // stdData_gameUnit_extended (origin = tally)
public $scr_totals  = NULL;   // stdData_gameTotals_record
public $scr_batch   = NULL;   // stdData_gameUnit_batch(origin <> tally)
}

class scores_xweekly {  // used for one specified week and one semester of current period
public $scr_tally   = NULL;  // stdData_gameUnit_extended (origin = tally)
public $scr_batch   = NULL;   // stdData_gameUnit_batch(origin <> tally)
//public $scr_totals  = NULL;   // stdData_gameTotals_record
}

class scores_ofDateRange {  // used for one specified week and one semester of current period

// tally scores only used when range is one week  - origin = tally     type = stdData_gameUnit_extended  or stdData_pointsUnit_record
public $scr_tally_chess   = NULL;  // stdData_gameUnit_extended (origin = tally)
public $sdr_tally_blitz   = NULL;  // stdData_gameUnit_extended (origin = tally)
public $sdr_tally_bug     = NULL;  // stdData_gameUnit_extended (origin = tally)
public $sdr_tally_points  = NULL;  // stdData_pointsUnit (origin = tally)

// total scores only used when range is one semester - totals or kidperiod table - type = stdData_gameTotals
// possibly also used for one week range for totals of game batches and tally records
public $sdr_totals_chess   = NULL;   // stdData_gameTotals_record
public $sdr_totals_blitz   = NULL;   // stdData_gameTotals_record
public $sdr_totals_bug     = NULL;   // stdData_gameTotals_record
// public $sdr_totals_points  = NULL;   //???? info in kidPeriod record
//  public $kidPer_totalGamePoints;
//  public $kidPer_totalGeneralPoints;

// game/point scores are not always used/loaded - game/points table - origin<>tally  type = stdData_gameUnit_batch
public $sdr_batch_chess   = NULL;   // stdData_gameUnit_batch(origin <> tally)
public $sdr_batch_blitz   = NULL;   // stdData_gameUnit_batch(origin <> tally)
public $sdr_batch_bug     = NULL;   // stdData_gameUnit_batch(origin <> tally)
public $sdr_batch_points  = NULL;   // stdData_pointsUnit_batch (origin <> tally)


function __construct() {
}

function scores_getGameObject($gameType, $defaultObject = NULL) {
        switch ($gameType) {
            case ROSTER_CHESS:
                return $this->scores_chess;
                break;
            case ROSTER_BLITZ:
                return $this->scores_blitz;
                break;
            case ROSTER_BUGHOUSE:
                return $this->scores_bug;
                break;
            default:
                return $defaultObject;
                break;
        }
}

} // end class

class stdData_game_root {
public $game_wins = 0;
public $game_losses = 0;
public $game_draws = 0;

function __construct() {
}

function getCount () {
    return $this->game_wins + $this->game_draws + $this->game_losses;

}

function getPercent () {
    $wins = ($this->game_wins * 2) + $this->game_draws;
    $count = $this->getCount();
    return $count==0 ? 0 : round(($wins / ( $count * 2)) * 100);
}

//--   function getPoints($pGameType) {
//--       $this->key = $pGameType;
//--       if ($pGameType==ROSTER_CHESS) {
//--           $this->desc = 'Chess';
//--           $this->gamePoints = 10;
//--       }
//--       if ($pGameType==ROSTER_BUGHOUSE) {
//--           $this->desc = 'Bughouse';
//--           $this->gamePoints = 3;  //~~15/08
//--       }
//--       if ($pGameType==ROSTER_BLITZ) {
//--           $this->desc = 'Blitz';
//--           $this->gamePoints = 5;
//--       }
//--   }

} // end class

class stdData_gameTotals_record  extends stdData_game_root {
public $gameTotals_gameTotalId;
public $gameTotals_programId;
public $gameTotals_periodId;
public $gameTotals_kidPeriodId;
public $gameTotals_gameType;

function __construct() {
}

static function stdRec_addFieldList($fldList, $flags=0) {
    $fldList[] = 'gpGT:GameTotalId';
    $fldList[] = 'gpGT:@ProgramId';
    $fldList[] = 'gpGT:@PeriodId';
    $fldList[] = 'gpGT:@KidPeriodId';
    $fldList[] = 'gpGT:GameTypeIndex';
    $fldList[] = 'gpGT:GamesWon';
    $fldList[] = 'gpGT:GamesLost';
    $fldList[] = 'gpGT:GamesDraw';
    return $fldList;
}

static function stdRec_appendSelect($queryCommand) {
    $queryCommand->draff_sql_selectFields('gpGT:GameTotalId');
    $queryCommand->draff_sql_selectFields('gpGT:@ProgramId','gpGT:@PeriodId','gpGT:@KidPeriodId');
    $queryCommand->draff_sql_selectFields('gpGT:GameTypeIndex');
    $queryCommand->draff_sql_selectFields('gpGT:GamesWon','gpGT:GamesLost','gpGT:GamesDraw');
}

function stdRec_loadRow_gameTotals($row) {
   $this->gameTotals_gameTotalId = $row['gpGT:GameTotalId'];
   $this->gameTotals_programId = $row['gpGT:@ProgramId'];
   $this->gameTotals_periodId = $row['gpGT:@PeriodId'];
   $this->gameTotals_kidPeriodId = $row['gpGT:@KidPeriodId'];
   $this->gameTotals_gameType = $row['gpGT:GameTypeIndex'];
   $this->game_wins = $row['gpGT:GamesWon'];
   $this->game_losses = $row['gpGT:GamesLost'];
   $this->game_draws = $row['gpGT:GamesDraw'];
}

function gaTotals_clear($appGlobals, $kidPeriodId, $gameType) {
    $this->gameTotals_gameTotalId = 0;
    $this->gameTotals_programId = $appGlobals->gb_programId;
    $this->gameTotals_periodId = $appGlobals->gb_periodId;
    $this->gameTotals_kidPeriodId = $kidPeriodId;
    $this->gameTotals_gameType = $gameType;
    $this->game_wins = 0;
    $this->game_losses = 0;
    $this->game_draws = 0;
}

function gaTotals_read( $appGlobals, $kidPeriodId, $gameType ) {
    $sql = array();
    $sql[] = "SELECT *";
    $sql[] = "FROM `gp:gametotals`";
    $sql[] ="WHERE `gpGT:@KidPeriodId` ='{$kidPeriodId}'";
    $sql[] ="   AND `gpGT:GameTypeIndex` ='" . intval($gameType) . "'";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query( $query );
    if ($result === FALSE) {
        $appGlobals->gb_sql->sql_fatalError(  __FILE__,__LINE__);
    }
    if ($result->num_rows == 0) {
        $this->gaTotals_clear($appGlobals, $kidPeriodId, $gameType);
    }
    else {
        $row=$result->fetch_array();
        $this->stdRec_loadRow_gameTotals($row);
    }
}

function gaTotals_write($appGlobals) {
   // $appGlobals->gb_sql->sql_transaction_start ($appGlobals);  ?????????????????
    $sql = array();
    //??????????? need to update modwhen modStaffId, etc
    if ($this->gameTotals_gameTotalId==0) {
        $sql[] = "INSERT INTO `gp:gametotals`";
        $sql[] = "SET `gpGT:@ProgramId` = '{$this->gameTotals_programId}' ";
        $sql[] = ", `gpGT:@PeriodId` = '{$this->gameTotals_periodId}' ";
        $sql[] = ", `gpGT:@KidPeriodId` = '{$this->gameTotals_kidPeriodId}' ";
        $sql[] = ", `gpGT:GameTypeIndex` = '{$this->gameTotals_gameType}' ";
        //$sql[] = ", `gpGt:PlayerStatus` = '{$this->gatPlayerStatus}' ";
        $sql[] = ", `gpGt:GamesWon` = '{$this->game_wins}' ";
        $sql[] = ", `gpGt:GamesLost` = '{$this->game_losses}' ";
        $sql[] = ", `gpGt:GamesDraw` = '{$this->game_draws}' ";
    }
     else {
        $sql[] = "UPDATE `gp:gametotals`";
        $sql[] = "SET `gpGt:GamesWon` =  '{$this->game_wins}' ";
        $sql[] = ", `gpGt:GamesLost` =  '{$this->game_losses}' ";
        $sql[] = ", `gpGt:GamesDraw` =  '{$this->game_draws}' ";
        $sql[] = "WHERE `gpGT:GameTotalId` = '{$this->gameTotals_gameTotalId}'";
      // $sql[] = "WHERE (`gpGT:GameTypeIndex` ='{$this->GameTypeIndex}') AND (`gpGT:@KidPeriodId` = '{$this->AtKidPeriodId}')";
    }
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query($query, __FILE__, __LINE__ );
    if ($result === FALSE) {
        $appGlobals->gb_sql->sql_fatalError(  __FILE__,__LINE__, $query);
    }
   // $appGlobals->gb_sql->sql_transaction_end ($appGlobals);
}

} // end class

// class stdData_gameUnit_record extends stdData_game_root {
class stdData_gameUnit_record  extends draff_database_record {

const DB_TABLE_NAME  = '';
const DB_TABLE_INDEX = '';
const DB_MOD_WHEN    = '';
const DB_MOD_WHO     = '';
const DB_SELECT_FIELDS  = array();

// Do not code save in this class
//    (1) use extended gameUnit class for saves
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

static function stdRec_addFieldList($fldList, $flags=0) {
    $fldList[] = 'gpGa:GameId';
    $fldList[] = 'gpGa:@ProgramId';
    $fldList[] = 'gpGa:@PeriodId';
    $fldList[] = 'gpGa:@KidPeriodId';
    $fldList[] = 'gpGa:@GameId';
    $fldList[] = 'gpGa:GameTypeIndex';
    $fldList[] = 'gpGa:GamesWon';
    $fldList[] = 'gpGa:GamesLost';
    $fldList[] = 'gpGa:GamesDraw';
    $fldList[] = 'gpGa:OriginCode';
    $fldList[] = 'gpGa:Opponents';  // calculated
    $fldList[] = 'gpGa:ClassDate';
    $fldList[] = 'gpGa:WhenCreated';
    $fldList[] = 'gpGa:ModBy@StaffId';
    $fldList[] = 'gpGa:ModWhen';
    return $fldList;
}

static function stdRec_appendSelect($queryCommand) {
    $queryCommand->draff_sql_selectFields('gpGa:GameId','gpGa:@GameId');
    $queryCommand->draff_sql_selectFields('gpGa:@ProgramId','gpGa:@PeriodId','gpGa:@KidPeriodId');
    $queryCommand->draff_sql_selectFields('gpGa:GameTypeIndex');
    $queryCommand->draff_sql_selectFields('gpGa:GamesWon','gpGa:GamesLost','gpGa:GamesDraw');
    $queryCommand->draff_sql_selectFields('gpGa:ClassDate');
    $queryCommand->draff_sql_selectFields('gpGa:WhenCreated','gpGa:ModBy@StaffId','gpGa:ModWhen');
}

function stdRec_loadRow($row, $flags=0) {
    $this->game_gameId = $row['gpGa:GameId'];
    $this->game_atGameId = $row['gpGa:@GameId'];
    $this->game_kidPeriodId = $row['gpGa:@KidPeriodId'];
    $this->game_gameType = $row['gpGa:GameTypeIndex'];
    $this->game_classDate = $row['gpGa:ClassDate'];
    $this->game_whenCreated = $row['gpGa:WhenCreated'];
    $this->game_atStaffId = $row['gpGa:ModBy@StaffId'];
    $this->game_modWhen = $row['gpGa:ModWhen'];
    $this->gap_kidPeriodId = $row['gpGa:@KidPeriodId'];
    $this->game_wins       = $row['gpGa:GamesWon'];
    $this->game_losses    = $row['gpGa:GamesLost'];
    $this->game_draws     = $row['gpGa:GamesDraw'];
    $this->gap_gameIds   = $row['gpGa:GameId'];
    $this->game_originCode = $row['gpGa:OriginCode'];
    $this->game_opponents = empty($row['gpGa:Opponents']) ? array() : explode(',',$row['gpGa:Opponents']);
    $this->game_opponents_count = count($this->game_opponents);
}

} // end class

class stdData_gameUnit_extended extends stdData_gameUnit_record{
//--- different for all records of this game
public $game_opponents = array();      // zero if no specified opponents
public $game_opponents_count = 0;      // zero if no specified opponents
public $game_opponents_kidPeriodId = array();   // array of kid Id's

// save needs to update other tables not coded in this class

function __construct() {
}

// static function stdRec_game_getBundleSql($appGlobals, $periodId, $classDate, $kidPeriodId, $gameType, $originCode) {
//     $fields = array();
//     $fields = stdData_gameUnit_record::stdRec_addFieldList($fields);
//     $fieldList = "`".implode("`,`",$fields)."`";
//     $sql = array();
//     $sql[] =  'Select ' . $fieldList;
//     $sql[] = "FROM `gp:games`";
//     $sql[] = "WHERE (`gpGa:@PeriodId`='{$periodId}')";
//     if (!empty($classDate)) {
//         $sql[] = "   AND (`gpGa:ClassDate`='{$classDate}')";
//     }
//     if (!empty($kidPeriodId)) {
//         $sql[] = "   AND (`gpGa:KidPeriodId`='{$kidPeriodId}')";
//     }
//     if (!empty($gameType)) {
//         $sql[] = "   AND (`gpGa:GameTypeIndex`='{$gameType}')";
//     }
//     if (!empty($originCode)) {
//         $sql[] = "   AND (`gpGa:OriginCode`='{$originCode}')";
//     }
//     return $sql;
// }

function gaUnit_init($appGlobals, $originCode, $gameType, $kidPeriodId,  $classDate) {
    // never init a player record - player record is just a player's view of a game record - and is read-only when viewed this way
    $kidPeriod = $appGlobals->rst_cur_period->perd_getKidPeriodObject($kidPeriodId);
    $kid = $kidPeriod->kidPer_kidObject;
    $this->game_gameId = 0;
    $this->game_atGameId = 0;
    $this->game_AtProgramId = $appGlobals->gb_programId;
    $this->game_atPeriodId = $appGlobals->gb_periodId;
    $this->game_gameType = $gameType;
    $this->game_classDate = $classDate;
    $this->game_whenCreated = rc_getNow();
    $this->game_modWhen = rc_getNow();
    $this->game_atStaffId = $appGlobals->gb_db->rc_MakeSafe( rc_getStaffId());
    $this->game_kidPeriodId = $kidPeriodId;
    $this->game_originCode = $originCode;
    $this->gap_opponents = '';
    $this->game_wins = 0;
    $this->game_losses = 0;
    $this->game_draws = 0;
    $this->game_opponents_count = 0;
    $this->game_opponents_kidPeriodId = array();
}

function gaUnit_read($appGlobals, $gameId) {
    $sql = array();
    $sql[] =  'Select *';
    $sql[] = "FROM `gp:games`";
    $sql[] = " WHERE `gpGa:GameId`='{$gameId}'";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
    if ($result->num_rows != 1) {
        $appGlobals->gb_sql->sql_fatalError( __FILE__ , __LINE__ , $query);
    }
    $row=$result->fetch_array();
    parent::stdRec_loadRow($row);
    if ( empty($row['gpGa:Opponents']) ) {
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

function gap_tallyRecord_read($appGlobals, $kidPeriodId, $gameType, $classDate){
    $query = "SELECT * FROM `gp:games` WHERE (`gpGa:@KidPeriodId`='{$kidPeriodId}') AND (`gpGa:ClassDate`='{$classDate}') AND (`gpGa:OriginCode` = '".GAME_ORIGIN_TALLY."') AND (`gpGa:GameTypeIndex` = '{$gameType}')";
    $result = $appGlobals->gb_sql->sql_performQuery($query , __FILE__ , __LINE__ );
    //assert( '$result->num_rows <= 1', 'gap_tallyRecord_read - there is more than one tally record' );
    if ($result->num_rows < 1) {
        $this->gaUnit_init($appGlobals,GAME_ORIGIN_TALLY, $gameType, $kidPeriodId,$classDate);
    }
    else {
        $row=$result->fetch_array();
        parent::stdRec_loadRow($row);
    }
}

function gaUnit_save($appGlobals) {
    // $this->game_originCode = empty($this->game_opponents) ? ORIGIN_NOINFO : ORIGIN_WITHINFO; ??? or ORIGIN_TALLY
    $atGameId = $this->game_atGameId;   // will be zero for new game
    $modWhen = rc_getNow();
    $row = array();
    if ($this->game_gameId>=1) {
        $row['gpGa:GameId'] = $this->game_gameId;   //can be zero if new record
    }
    $row['gpGa:@GameId'] = $this->game_atGameId; //will be zero for first player of new game
    $row['gpGa:@ProgramId'] = $this->game_AtProgramId;
    $row['gpGa:@PeriodId'] = $this->game_atPeriodId;
    $row['gpGa:WhenCreated'] = $this->game_whenCreated;
    $row['gpGa:GameTypeIndex'] = $this->game_gameType;
    $row['gpGa:@KidPeriodId'] = $this->game_opponents_kidPeriodId[$i];
    $row['gpGa:GamesWon'] = $this->game_opponents_wins[$i];
    $row['gpGa:GamesLost'] = $this->game_opponents_losts[$i];
    $row['gpGa:GamesDraw'] =$this->game_opponents_draws[$i];
    $row['gpGa:Opponents'] = is_array(game_opponents_kidPeriodId) ? implode(',',$this->game_opponents_kidPeriodId) : $this->game_opponents_kidPeriodId;
    $row['gpGa:OriginCode'] = GAME_ORIGIN_CLASS;
    $row['gpGa:ClassDate'] = $this->game_classDate;
    $row['gpGa:ModBy@StaffId'] = rc_getStaffId();  //????? make safe
    $row['gpGa:ModWhen'] = $modWhen;
    $gameId = $this->game_opponents_gameId[$i];
    if ($gameId==0) {
        $query = kcmRosterLib_db_insert($appGlobals->gb_db,'gp:games',$row);
        $result = $appGlobals->gb_db->rc_query( $query );
        if ($result === FALSE) {
            $appGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
        }
        if ($atGameId==0) {
            $atGameId = $appGlobals->gb_db->insert_id;
            $row = array();
            $row['gpGa:@GameId'] = $atGameId;
            $query = kcmRosterLib_db_update($appGlobals->gb_db,'gp:games',$row,"WHERE `gpGa:GameId` = '{$atGameId}'");
            $result = $appGlobals->gb_db->rc_query( $query );
            if ($result === FALSE) {
                $appGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
            }
        }
    }
    else {
        $query = kcmRosterLib_db_update($appGlobals->gb_db,'gp:games',$row,"WHERE `gpGa:GameId` = '{$gameId}'");
        $result = $appGlobals->gb_db->rc_query( $query );
        if ($result === FALSE) {
            $appGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
        }
    }
    $this->game_atGameId = $atGameId;
    return $atGameId;
}

private function gaUnit_CurRecordTotals($appGlobals, $sign) {
}

private function gaUnit_OrgRecordTotals($appGlobals, $sign) {
    $orgRec = new stdData_gameUnit_extended;
    $orgRec->gaUnit_read($appGlobals,$this->game_gameId);
    $changed = ($orgRec->game_wins != $this->game_wins)
           or ($orgRec->game_losses != $this->game_losses)
           or ($orgRec->game_draws != $this->game_draws)
           or ($orgRec->game_gameType != $this->game_gameType)
           or ($orgRec->game_classDate != $this->game_classDate);
    if ($changed ) {
        $orgRec->gaUnit_updateKidPeriodTotals($appGlobals,-1);
        $orgRec->gaUnit_updateGameTotals($appGlobals,-1);
    }
    return $changed;
}

private function gaUnit_updateGameTotals($appGlobals, $sign) {
    $gameTot = new stdData_gameTotals_record;
    $gameTot->gaTotals_read($appGlobals, $this->game_kidPeriodId, $this->game_gameType);
    $gameTot->game_wins += $sign *  $this->game_wins;
    $gameTot->game_losses += $sign * $this->game_losses;
    $gameTot->game_draws += $sign * $this->game_draws;
    $gameTot->gaTotals_write($appGlobals);
}

private function gaUnit_updateKidPeriodTotals($appGlobals, $sign) {
    if ($this->game_gameType == ROSTER_CHESS) {
        $winPoints = 10;
    }
    else if ($this->game_gameType == ROSTER_BUGHOUSE) {
        $winPoints = 3;
    }
    else if ($this->game_gameType == ROSTER_BLITZ) {
        $winPoints = 5;
    }
    else {
        // should NEVER get here
        exit('Please notify office of error: gag-Update-Kid-Period-Totals-1');
    }
    $pointChange = ( ( $this->game_wins * $winPoints ) + ($this->game_draws * round ($winPoints / 2) ) ) * $sign;
    $sql = array();
    $sql[] = "UPDATE `ro:kid_period`";
    $sql[] = "SET `rkPe:KcmGamePoints` = `rkPe:KcmGamePoints` + '{$pointChange}' ";
    $sql[] = "WHERE `rKPe:KidPeriodId` ='{$this->game_kidPeriodId}'";
    $query = implode( $sql, ' ');
    $appGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
}

} // end class

class stdData_gameUnit_batch extends stdData_game_root {
// can be multiple players if kidPeriodId is empty
public $gaBatch_gameMap = array();

function gameBatch_addGame($game) {
    if (empty($game)) {
        return;
    }
    if (is_array($game)) {
        $newGame = new stdData_gameUnit_record;
        $newGame->stdRec_loadRow($game);
        $this->gaBatch_gameMap[$game->game_gameId] = $newGame;
        $game = $newGame;
    }
    else if (is_a($game,'stdData_gameUnit_batch')) {
       $this->gaBatch_gameMap = array_merge( $this->gaBatch_gameMap, $game->gaBatch_gameMap);
    }
    else {
       $this->gaBatch_gameMap[$game->game_gameId] = $game;
    }
    $this->game_wins     += $game->game_wins;
    $this->game_losses    += $game->game_losses;
    $this->game_draws    += $game->game_draws;
}

}

class stdData_gameMatch_group {
// match is a secondary "view" of the game table
// it is different than the gameUnit, in that it includes all of the players that played a game
// is is used when creating or editing a game
// it is not used by roster, as roster reads games by kid, not by gameId
// i.e. a game match is not associated with any one player (therefore not associated with the roster object)
//      the game match is associated with the gameId and all the players

public $gamatch_gamePlayerMap = array();  // kid-period ID

function __construct() {
    $this->game_gameType = NULL;  // invalid
    $this->game_atGameId = 0;
}

function gaMatch_clear($appGlobals,$gameType=NULL) {
    //?????????????? is this function necessary
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
    $this->game_AtProgramId = $appGlobals->gb_programId;
    $this->game_atPeriodId = $appGlobals->rst_cur_period->perd_periodId;
    $this->game_classDate = $appGlobals->rst_classDateObject->cSD_classDate;
    $this->game_whenCreated = rc_getNow();
 }
function gaMatch_addPlayerResult($kidPlayerId, $win, $draw, $lost) {
    // only valid from game mode
    //??????????????????? does record already exist - what if player change ?????????
    $match = array_search ( $kidPlayerId , $this->gamatch_gamePlayerMap);
    if ($match === FALSE) {
        $newGame = new stdData_gameUnit_extended;
        $newGame->stdRec_loadRow($row);
        $this->gamatch_gamePlayerMap[$newGame->game_kidPeriodId] = $newGame;
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

function gaMatch_read($appGlobals, $atGameId){
    $this->gamatch_gamePlayerMap = array();
    $fields = array();
    $fields = stdData_gameUnit_extended::stdRec_addFieldList($fields);
    $fieldList = "`" . implode("`,`", $fields) . "`";
    $sql = array();
    $sql[] =  'Select ' . $fieldList;
    $sql[] = "FROM  `gp:games` ";
    $sql[] = "WHERE `gpGa:@GameId`='{$atGameId}'";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query( $query );
    if ($result === FALSE) {
        $appGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
    }
    while ($row=$result->fetch_array()) {
        $newGame = new stdData_gameUnit_extended;
        $newGame->stdRec_loadRow($row);
        $this->gamatch_gamePlayerMap[$newGame->game_kidPeriodId] = $newGame;
    }
}

function gaMatch_save($appGlobals){
    // only valid from game mode
   // $appGlobals->gb_sql->sql_transaction_start ($appGlobals);  //?????????????????????
    $exists = ( ! empty($this->game_atGameId) );
    // need to update opponent array
    if ( $exists ) {
         $orgRec = new stdData_gameMatch_group;
         $orgRec->gaMatch_read($appGlobals,$this->game_atGameId, $this->game_opponents_gameId[0]);
         $orgRec->gag_updateGameTotals($appGlobals,-1);
         $orgRec->gag_updateKidPeriodTotals($appGlobals,-1);
    }
    $atGameId = $this->gaUnit_save($appGlobals);
    $this->gag_updateGameTotals($appGlobals,1);
    $this->gag_updateKidPeriodTotals($appGlobals,1);
  //  $appGlobals->gb_sql->sql_transaction_end ($appGlobals);
    return $atGameId;
    // end transaction processing
}

function gaMatch_delete($appGlobals) {
    // only valid from game mode
   // $appGlobals->gb_sql->sql_transaction_start ($appGlobals);
    $this->gag_updateGameTotals($appGlobals,-1);
    $this->gag_updateKidPeriodTotals($appGlobals,-1);
    $query = "DELETE FROM `gp:games` WHERE `gpGa:@GameId` ='{$this->game_atGameId}'";
    $result = $appGlobals->gb_db->rc_query($query, __FILE__, __LINE__ );
    if ($result == FALSE) {
        $appGlobals->gb_sql->sql_fatalError(  __FILE__,__LINE__);
    }
    //$appGlobals->gb_sql->sql_transaction_end ($appGlobals);
}

function gaMatch_getResultString($appGlobals, $roster, $pSep = ' ,') {
    // only valid from game mode
    $s = '';
    $sSep = '';
    for ($i=0; $i < $this->game_opponents_count; ++$i) {
        //$kidPeriod = $appGlobals->rst_cur_period->perd_getKidPeriodObject($this->game_opponents_kidPeriodId[$i]);
        //$kidName = ($kidPeriod==NULL) ? '***' : $kidPeriod->kidPer_kidObject->rstKid_uniqueName;
        $kidName = $roster->rst_cur_period->perd_getKidUniqueName($this->game_opponents_kidPeriodId[$i]);
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
   //return kcmRosterLib_getDesc_gameType($appGlobals->gb_form->chain->chn_posted_object->sesGame->game_gameType) . ' Game Saved: ' . $s;
}


function gaMatch_getSavedString($appGlobals) {
    // only valid from game mode
    $s = '';
    $sSep = '';
    for ($i=0; $i < $this->game_opponents_count; ++$i) {
        //$kidPeriod = $appGlobals->rst_cur_period->perd_getKidPeriodObject($this->game_opponents_kidPeriodId[$i]);
        //$kidName = $kidPeriod->kidPer_kidObject->rstKid_uniqueName;
        $kidName = $appGlobals->rst_cur_period->perd_getKidUniqueName($this->game_opponents_kidPeriodId[$i]);
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

} // end class

class stdData_gameMatch_batch {
// in some cases will be reading the "game bundle" not as an internal list but as objects in the kidPer_tally
public $gagBu_list;
private $gagBu_Result;
private $gagBu_RecMode;

function gagMatchBundle_read($roster, $appGlobals, $periodId = NULL, $classDate=NULL, $kidPeriodId=NULL, $gameType=NULL, $originCode=NULL) {
    if ( empty($periodId) ) {
        $periodId = $roster->rst_cur_period->perd_periodId;
    }
    $this->gagBu_list = array();
    $sql = $this->stdRec_game_getBundleSql($appGlobals, $periodId, $classDate, $kidPeriodId, $gameType, $originCode);
    $sql[] = "ORDER BY `gpGa:ClassDate`,  `gpGa:GameTypeIndex`, `gpGa:@GameId`,`gpGa:@KidPeriodId`";
    $query = implode( $sql, ' ');
    //echo '<hr>read_gameByGame<hr>' . implode($sql,PHP_EOL) . '<hr>';
    $this->gagBu_Result = $appGlobals->gb_db->rc_query( $query );
    if ($this->gagBu_Result === FALSE) {
        $appGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__, $query);
    }
    $curMatchId = 0;
    $curMatch = NULL;
   while($row=$this->gagBu_Result->fetch_array()) {
        //  $gameRec = new stdData_gameMatch_group;  //??????????????????????
        $gameRec = new stdData_gameUnit_extended;
        $gameRec->stdRec_loadRow($row);
        if ($gameRec->game_atGameId != $curMatchId) {
            $curMatch = new stdData_gameMatch_group;
            $this->gagBu_list[$gameRec->game_atGameId] = $gameRec;
            $curMatchId = $gameRec->game_atGameId;
        }
       $curMatch->gamatch_gamePlayerMap[$gameRec->game_kidPeriodId] = $gameRec;
    }
}

static function stdRec_game_getBundleSql($appGlobals, $periodId, $classDate, $kidPeriodId, $gameType, $originCode) {
    $fields = array();
    $fields = stdData_gameUnit_record::stdRec_addFieldList($fields);
    $fieldList = "`".implode("`,`",$fields)."`";
    $sql = array();
    $sql[] =  'Select ' . $fieldList;
    $sql[] = "FROM `gp:games`";
    $sql[] = "WHERE (`gpGa:@PeriodId`='{$periodId}')";
    if (!empty($classDate)) {
        $sql[] = "   AND (`gpGa:ClassDate`='{$classDate}')";
    }
    if (!empty($kidPeriodId)) {
        $sql[] = "   AND (`gpGa:KidPeriodId`='{$kidPeriodId}')";
    }
    if (!empty($gameType)) {
        $sql[] = "   AND (`gpGa:GameTypeIndex`='{$gameType}')";
    }
    if (!empty($originCode)) {
        $sql[] = "   AND (`gpGa:OriginCode`='{$originCode}')";
    }
    return $sql;
}

} // end class

//======
//============
//==================
//========================
//= Points Record
//========================
//==================
//============
//======

class stdData_pointsUnit_record  extends draff_database_record {

const DB_TABLE_NAME  = '';
const DB_TABLE_INDEX = '';
const DB_MOD_WHEN    = '';
const DB_MOD_WHO     = '';
const DB_SELECT_FIELDS  = array();

public $pnt_pointsId = 0;       // gpPo:pnt_pointsId
public $pnt_kidId = 0;        // gpPo:@KidId   ?????
public $pnt_programId = 0;    // gpPo:@ProgramId
public $pnt_kidProgramId = 0; // gpPo:@KidProgramId
public $pnt_kidPeriodId = 0;  // gpPo:@KidPeriodId
public $pnt_pointValue = 0;     // gpPo:pnt_pointValue
//public $pnt_kcm1PointString;     // rKPe:KcmPerPointValues
public $pnt_category = '';           // gpPo:Category
public $pnt_note = '';           // gpPo:Note
public $pnt_classDate;      // gpPo:ClassDate
public $pnt_whenCreated;    // gpPo:WhenCreated   ???????
public $pnt_modByAtStaffId; // gpPo:ModBy@StaffId
public $pnt_modWhen;        // gpPo:pnt_modWhen
public $pnt_originCode = 0;  // gpPo:OriginCode

static function stdRec_addFieldList($fldList, $flags=0) {
    $fldList[] = 'gpPo:PointsId';
    $fldList[] = 'gpPo:@KidId';
    $fldList[] = 'gpPo:@ProgramId';
    $fldList[] = 'gpPo:@KidProgramId';
    $fldList[] = 'gpPo:@KidPeriodId';
    $fldList[] = 'gpPo:CategoryIndex';
    $fldList[] = 'gpPo:CategoryClue';
    $fldList[] = 'gpPo:PointValue';
    $fldList[] = 'gpPo:Category';
    $fldList[] = 'gpPo:Note';
    $fldList[] = 'gpPo:ClassDate';
    $fldList[] = 'gpPo:OriginCode';
    $fldList[] = 'gpPo:WhenCreated';
    $fldList[] = 'gpPo:ModBy@StaffId';
    $fldList[] = 'gpPo:ModWhen';
    return $fldList;
}

static function stdRec_appendSelect($queryCommand) {
    $queryCommand->draff_sql_selectFields('gpPo:PointsId','gpPo:@KidId','gpPo:@ProgramId');
    $queryCommand->draff_sql_selectFields('gpPo:@KidProgramId','gpPo:@KidPeriodId');
    $queryCommand->draff_sql_selectFields('gpPo:CategoryIndex','gpPo:CategoryClue');
    $queryCommand->draff_sql_selectFields('gpPo:PointValue','gpPo:Category','gpPo:Note','gpPo:ClassDate');
    $queryCommand->draff_sql_selectFields('gpPo:OriginCode');
    $queryCommand->draff_sql_selectFields('gpPo:WhenCreated','gpPo:ModBy@StaffId','gpPo:ModWhen');
}

function pnt_loadRow($row) {
    $this->pnt_pointsId       = $row['gpPo:PointsId'];
    $this->pnt_kidId        = $row['gpPo:@KidId'];
    $this->pnt_programId    = $row['gpPo:@ProgramId'];
    $this->pnt_kidProgramId = $row['gpPo:@KidProgramId'];
    $this->pnt_kidPeriodId  = $row['gpPo:@KidPeriodId'];
    $this->pnt_kcm1CategoryIndex  = $row['gpPo:CategoryIndex'];
    $this->pnt_kcm1CategoryClue   = $row['gpPo:CategoryClue'];
    $this->pnt_pointValue     = $row['gpPo:PointValue'];
    $this->pnt_note           = $row['gpPo:Note'];
    $this->pnt_category       = $row['gpPo:Category'];
    $this->pnt_classDate      = $row['gpPo:ClassDate'];
    $this->pnt_whenCreated    = $row['gpPo:WhenCreated'];
    $this->pnt_modByAtStaffId = $row['gpPo:ModBy@StaffId'];
    $this->pnt_modWhen        = $row['gpPo:ModWhen'];
    $this->pnt_originCode     = $row['gpPo:OriginCode'];
    //$this->pnt_kcm1PointString=$row['rKPe:KcmPerPointValues'];
}

} // end class

class stdData_pointsUnit_batch {
// can be multiple players if kidPeriodId is empty
public $ptBatch_pointMap = array();
public $ptBatch_points = 0;

function pointBatch_addPointUnit($pointUnit) {
    if (is_array($pointUnit)) {
        $newPoints = new stdData_pointsUnit_record;
        $newPoints->stdRec_loadRow($game);
        $this->ptBatch_pointMap[$newPoints->pnt_pointsId] = $newPoints;
        $this->ptBatch_points .= $newPoints->pnt_pointValue;
    }
    else {
        $this->ptBatch_pointMap[$pointUnit->pnt_pointsId] = $pointUnit;
        $this->ptBatch_points .= $pointUnit->pnt_pointValue;
    }
}

}

//======
//============
//==================
//========================
//=   Roster Grade Groups
//========================
//==================
//============
//======

Class roster_gradeGroups {

public $grdgp_enabled;   // set only if there are actual groups (other than one grade per group)
public $grdgp_minGrade;
public $grdgp_maxGrade;

public $grdgp_grade_isGroupEnd ;// array of each grade
public $grdgp_grade_descShort;// array of each grade
public $grdgp_grade_descLong;// array of each grade
public $grdgp_grade_count;// array of each grade

public $grdgp_group_descLong;// array of each grade group
public $grdgp_group_minGrade; // array of each grade group
public $grdgp_group_maxGrade; // array of each grade group
public $grdgp_group_count; // array of each grade group


function __construct($pString, $pMinGrade, $pMaxGrade) {
    $this->grdgp_minGrade = $pMinGrade;
    $this->grdgp_maxGrade = $pMaxGrade;
    $this->grdgp_grade_descShort = array ('0','1','2','3','4','5','6','7','8','9','10','11','12','A');
    $this->grdgp_grade_descLong = array ('0K','1st','2nd','3rd','4th','5th','6th','7th','8th','9th','10th','11th','12th','Adult');
    $this->grdgp_grade_count = count($this->grdgp_grade_descLong);
    $this->grdgp_grade_isGroupEnd  = array(14);
    $this->grdgp_convertFromString($pString, $pMinGrade, $pMaxGrade);
}

function grdgp_convertFromString($pString, $pMinGrade, $pMaxGrade) {  //????? eliminate mingrade and maxgrade params
    if ($pString=='') {
        $this->grdgp_setDefaults();
    }
    else {
        for ($i = 0; $i < $this->grdgp_grade_count; $i++)
            $this->grdgp_grade_isGroupEnd [$i] = FALSE;
        $ar = explode('~',$pString);
        for ($i = 0; $i < count($ar); $i++) {
            $j = $ar[$i];
            if ( ($j >= 0) and ($j < $this->grdgp_grade_count) )
                $this->grdgp_grade_isGroupEnd [$j] = TRUE;
        }
    }
    $this->grdgp_refresh();
}

function grdgp_setDefaults() {
    for ($i = 0; $i <$this->grdgp_grade_count; $i++)
        $this->grdgp_grade_isGroupEnd[$i] = TRUE;
    $this->grdgp_refresh();
    return;
}
function grdgp_add($pLow,$pHigh) {
    $pLow = max ($pLow,0);
    $pHigh = min ($pHigh,13);
    for ($i = $pLow; $i < $pHigh; $i++)
        $this->grdgp_grade_isGroupEnd [$i] = FALSE;
    $this->grdgp_grade_isGroupEnd [$pHigh] = TRUE;
    if ($pLow >= 1)
        $this->grdgp_grade_isGroupEnd [$pLow-1] = TRUE;
}
function grdgp_refresh() {
    $this->grdgp_enabled = FALSE;  // will change if there are groups of more than one grade
    $this->grdgp_group_descShort = array();
    $this->grdgp_group_descLong = array();
    $this->grdgp_group_minGrade = array();
    $this->grdgp_group_maxGrade = array();
    $this->grdgp_group_count = 0;
    $this->grdgp_grade_isGroupEnd[$this->grdgp_maxGrade] = TRUE;
    
   $startGrade = $this->grdgp_minGrade;
   for ($i = $this->grdgp_minGrade; $i<=$this->grdgp_maxGrade; $i++) {
        if ( $this->grdgp_grade_isGroupEnd[$i]) {
            if ($i == $startGrade) {
                $this->grdgp_group_descShort[] = $this->grdgp_grade_descShort[$startGrade];
                $this->grdgp_group_descLong[] = $this->grdgp_grade_descLong[$startGrade];
            }
            else {
                $this->grdgp_group_descShort[] = $this->grdgp_grade_descShort[$startGrade] .'-'. $this->grdgp_grade_descShort[$i];
                $this->grdgp_group_descLong[] = $this->grdgp_grade_descShort[$startGrade] .'-'. $this->grdgp_grade_descLong[$i];
                $this->enabled = TRUE;
            }
            $startGrade = $i+1;
        }
    }
    $this->grdgp_group_count = count($this->grdgp_group_descShort);
}

}

?>