<?php

// gateway-system-kcm1-conversion.inc.php

class kcm2_cvt_convert_to_version2 {
public $kcm1PointCategories;  
public $kcm2PointCategories;  
private $testMode = TRUE;
    
function cvt_convert_allPrograms($appGlobals) {
    $progIdArray = array();
    $query = "SELECT `pPr:ProgramId` FROM `pr:program`";
    $result = $db->rc_query( $query );
    if ( $result === FALSE) {
        $appGlobals->gb_sql->sql_errorTerminate($query);
    }
    while ($row=$result->fetch_array()) {
        $progIdArray[] = $row['pPr:ProgramId'];
    }    
    $progIdCount = count($progIdArray);
    for ( $i=0; $i<$progIdCount; ++$i ) {
        $programId = $progIdArray[$i];
        print '<br>Starting Conversion of Program ' . $programId;
        $this->cvt_convert_oneProgram($progIdArray[$i], $appGlobals);
        print ' - Completed Program ' . $programId ;
    }        
}
    
function cvt_convert_oneProgram($programId, $appGlobals) {
    $kcmVersion = $this->cvt_table_program_convert($appGlobals, $programId); 
    //if ( $kcmVersion >= 2) {
    //    return;
    //}   
    $this->cvt_table_kidPeriod_convert($appGlobals, $programId);  
    $this->cvt_table_points_convert($appGlobals, $programId);      // convert points - includes combining general records into one general record
    $this->cvt_table_game_convert($appGlobals, $programId);       // convert games - includes combining general records into one general record
    $query = "UPDATE `pr:program` SET `pPr:KcmVersion` = '2' WHERE `pPr:ProgramId` = '{$programId}'";
    $this->cvt_performQuery($appGlobals,$query,__LINE__,NULL,'Update Program Record');
}

function cvt_convert_programsOfDateRange($appGlobals,$dateFirst,$dateLast) {  // date range of starting day of program
    $progIdArray = array();
    $query = "SELECT `pPr:ProgramId`,`pPr:DateClassFirst`,`pPr:DateClassLast` FROM `pr:program` WHERE (`pPr:DateClassFirst`>='{$dateFirst}') AND (`pPr:DateClassFirst`<='{$dateLast}')";
   $result = $db->rc_query( $query );
    if ( $result === FALSE) {
        $appGlobals->gb_sql->sql_errorTerminate($query);
    }
    while ($row=$result->fetch_array()) {
        $progIdArray[] = $row['pPr:ProgramId'];
    }    
    $progIdCount = count($progIdArray);
    for ( $i=0; $i<progIdCount; ++$i ) {
        $this->cvt_convert_oneProgram($progIdArray[$i], $appGlobals);
    }        
}

function cvt_table_program_convert($appGlobals, $programId) {
    $query = "SELECT `pPr:KcmVersion`,`pPr:KcmPointCategories`,`pPr:KcmPointCatList` FROM `pr:program` WHERE `pPr:ProgramId` = '{$programId}'";
    $result = $this->cvt_performQuery($db,$query,__LINE__,NULL,'Select Program Record');
    $row=$result->fetch_array();
    $version = $row['pPr:KcmVersion'];
    if ( $version==2) {
        return $version;  // already done
    }    
    //--- convert Point Categories and save for other conversions
    $kcm1Categories = $row['pPr:KcmPointCategories'];
    $kcm2Categories = $row['pPr:KcmPointCatList'];  // should be empty except when re-converting
    $this->kcm1PointCategories = array();
    $a = array();
    if ( ! empty($kcm1Categories)) { 
        $a = explode('~',$kcm1Categories);
        $aMax = count($a)-1;
        for ($i=0; $i<$aMax; $i=$i+2) {
            if ( $a[$i]=='L') {
                $this->kcm1PointCategories[] = $a[$i+1];
            }    
        }
    }    
    if ( count($this->kcm1PointCategories) < 1) {
        $this->kcm1PointCategories = array('General Points','Evaluator','Solving Puzzles' ,'Games with Coaches' ,'Special Points' ,'Sportsmanship' ,'Daily Puzzle' ,'Mate Obstacle Course' ,'Puzzle Solving Lessons' ,"ABCs" ,'IYF Checkmates' ,'Tactics');
    }
    //--- conversion of Kcm1 point categories - done once in program record 
    //----- Kcm1 conversion - end
    $this->kcm2PointCategories  = array('Evaluator', 'Solving Puzzles' ,'Sportsmanship', 'Play a Coach');
    $pointCatString = implode(',',$this->kcm2PointCategories);
    // update program record
    if ( !empty($kcm2Categories)) {
        $query = "UPDATE `pr:program` SET `pPr:KcmPointCatList` = '{$pointCatString}' WHERE `pPr:ProgramId` = '{$programId}'";
        $this->cvt_performQuery($db,$query,__LINE__,NULL,'Update Program Record');
    }    
    return $version; 
}


function cvt_table_kidPeriod_convert($appGlobals, $programId) {
    $sql = array();
    $sql[] = "SELECT `rKPe:KidPeriodId`,`rKPr:KidProgramId`,`rKPe:KcmGeneralPoints`,`rKPe:KcmPerPointValues`,`rKPr:KcmPrgPointValues`,`rKPe:KcmClassSubGroup`,`pPe:PeriodSequenceBits`,`rKPr:KcmNameLabelNote`"; 
    //,`rKPr:KcmNameLabelNote`"; ?????
    $sql[] = "FROM `ro:kid_period`"; 
    $sql[] = "LEFT JOIN `ro:kid_program` ON  `rKPr:KidProgramId` = `rKPe:@KidProgramId`";
    $sql[] = "LEFT JOIN `pr:period` ON `pPe:PeriodId` = `rKPe:@PeriodId`";
	$sql[] = "WHERE `rKPr:@ProgramId`='{$programId}'";
    $sql[] = "ORDER BY `rKPr:KidProgramId`,`pPe:PeriodSequenceBits`";
    $query = implode( $sql, ' '); 
    $result = $this->cvt_performQuery($db,$query,__LINE__,NULL,'Kid Period Convert');
    //if ( $this->testMode) return;
    $curKidPeriodId = NULL;
    $curLabelGroup = '';
    while ($row=$result->fetch_array()) {
        $kidPeriodId = $row['rKPe:KidPeriodId'];
        $kidProgramId = $row['rKPe:KidPeriodId'];
        $perPointList = $row['rKPe:KcmPerPointValues'];
        $prgPointList = $row['rKPr:KcmPrgPointValues'];
        $oldTotal = $row['rKPe:KcmGeneralPoints'];
        $perTotal = $this->cvt_calcCompressedArrayTotal($perPointList);
        $prgTotal = $this->cvt_calcCompressedArrayTotal($prgPointList);
        $newTotal = $perTotal + $prgTotal;
        $sql2 = array();
        $sql2[] = "UPDATE `ro:kid_period`";
        $sql2[] = "SET `rKPe:KcmGeneralPoints` = '".$newTotal."' ";
        $sql2[] = " , `rKPe:KcmPerPointValues` = '".$newTotal."' ";  // eliminate array - this is for kcm1 "compatibility"
        $sql2[] = "WHERE `rKPe:KidPeriodId` = '".$kidPeriodId."'";
        $query2 = implode( $sql2, ' '); 
        $this->cvt_performQuery($db,$query2,__LINE__,NULL,'Kid Period Update');
        $nameLabelNote = $row['rKPr:KcmNameLabelNote'];
        if ( $nameLabelNote==NULL) {
            $nameLabelNote = '';
        }
        if ( $kidPeriodId != $curKidPeriodId) {
            $curKidPeriodId = $kidPeriodId;
            $curNote = $nameLabelNote;
        }
        else {
            if ( $curNote='') {
                $curNote = $nameLabelNote;
            }
            else if ( $curNote != $nameLabelNote) {
                $curNote = $curNote . ' / ' . $nameLabelNote;
            }
        }
        //if ( $curNote != $row['rKPr:KcmNameLabelNote']) {
            $sql2 = array();
            $sql2[] = "UPDATE `ro:kid_program`";
            $sql2[] = "SET `rKPr:KcmNameLabelNote` = '".$curNote."' ";
            $sql2[] = ", `rKPr:KcmPrgPointValues` = '0' ";
            $sql2[] = "WHERE `rKPr:KidProgramId` = '".$kidProgramId."'";
            $query2 = implode( $sql2, ' '); 
            $this->cvt_performQuery($db,$query2,__LINE__,NULL,'Kid Period Label Update (Changed)');
        //}
    }    
}

function cvt_table_points_convert($appGlobals, $programId) {
    $field = array();        
    $field[] = 'gpPo:PointsId';
    $field[] = 'gpPo:@KidPeriodId';
    $field[] = 'gpPo:Note';
    $field[] = 'gpPo:PointValue';
    $field[] = 'gpPo:ClassDate';
    $field[] = 'gpPo:CategoryIndex';
    $field[] = 'gpPo:@ProgramId';
    $field[] = 'gpPo:OriginCode';
    $field[] = 'gpPo:Category';
    $fieldList = "`" . implode("`,`", $field) . "`";
    $sql = array();
    $sql[] = 'SELECT ' . $fieldList; 
    $sql[] = "FROM `gp:points`"; 
    $sql[] = "WHERE `gpPo:@ProgramId`='{$programId}'";  //???? and note = ''
    $sql[] = "ORDER BY `gpPo:@KidPeriodId`,  `gpPo:ClassDate`";
    $query = implode( $sql, ' '); 
    $result = $this->cvt_performQuery($db,$query,__LINE__,NULL,'Points combine');
    $genKey = NULL;
    $genPointsId = 0;
    while ($row=$result->fetch_array()) {
        $curPointsId = $row['gpPo:PointsId'];
        $note = $row['gpPo:Note'];
        if ( empty($note)) {
            $catIndex = $row['gpPo:CategoryIndex'];
            $note = ($catIndex>=1) ? $this->kcm1PointCategories[$catIndex] : '';
        }    
        $sql = array();
        $sql[] = "UPDATE `gp:points`";
        $sql[] = "SET `gpPo:Note` = '{$note}' ";
        $sql[] = ", `gpPo:OriginCode` = '".GAME_ORIGIN_CLASS."'";
        $sql[] = "WHERE `gpPo:PointsId` = '".$curPointsId."'";
        $query2 = implode( $sql, ' '); 
        $this->cvt_performQuery($db,$query2,__LINE__,NULL,'Points convert');
    }    
} // END process_pointRecords

function cvt_table_game_convert($appGlobals, $programId) {
    $fields = array();
    $fields[] = 'gpGa:GameId';
    $fields[] = 'gpGa:@GameId';
    $fields[] = 'gpGa:@KidPeriodId';
    $fields[] = 'gpGa:GameTypeIndex';
    $fields[] = 'gpGa:ClassDate';
    $fields[] = 'gpGa:GamesWon';
    $fields[] = 'gpGa:GamesLost';
    $fields[] = 'gpGa:GamesDraw';
    $fields[] = 'gpGa:GamesDraw';
    $fields[] = 'gpGa:Opponents';
    $fields[] = 'gpGa:OriginCode';
    $fieldList = "`" . implode("`,`", $fields) . "`";
    $sql = array();
    $sql[] = 'SELECT ' . $fieldList; 
    $sql[] = ", GROUP_CONCAT(`gpGa:@KidPeriodId` SEPARATOR ',') AS `PlayerKidPerIds`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GameId` SEPARATOR ',') AS `PlayerGameIds`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GamesWon` SEPARATOR ',') AS `PlayerWon`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GamesLost` SEPARATOR ',') AS `PlayerLost`";
    $sql[] = ", GROUP_CONCAT(`gpGa:GamesDraw` SEPARATOR ',') AS `PlayerDraw`";
    $sql[] = ",COUNT(*) as `gameCount`";
    $sql[] = "FROM  `gp:games` ";
    $sql[] = "WHERE `gpGa:@ProgramId`='{$programId}'";
    $sql[] = 'GROUP BY `gpGa:@GameId`';
    $sql[] = "ORDER BY `gpGa:@KidPeriodId`,`gpGa:GameTypeIndex`, `gpGa:ClassDate`,`gpGa:@GameId`,`gpGa:GameId`";
    $query = implode( $sql, ' '); 
    $result = $this->cvt_performQuery($db,$query,__LINE__,NULL,'Game Convert');
    $genKey = NULL;
    $genGameId = 0;
    
    while ($row=$result->fetch_array()) {
        $gameCount       = $row['gameCount'];
        $originCode      = $row['gpGa:OriginCode'];
        if ( !empty($originCode) ) {
            continue;  // already converted
        }
        if ( $gameCount > 1) {
            //-- real game between several players - not part of general record logic
            $arrayKidPeriodId = explode(',',$row['PlayerKidPerIds']);
            $arrayGameIds = explode(',',$row['PlayerGameIds']);
            //$db->rc_startTransaction();
            for ($i=0; $i<$gameCount; ++$i) {
                $gameId = $arrayGameIds[$i];
                $ops = array();
                for ($j=0; $j<$gameCount; ++$j) {
                    if ( $i != $j) {
                        $ops[] = $arrayKidPeriodId[$j];
                    }
                }
                $opList = implode(',',$ops);
                $sqla = array();
                $sqla[] = "UPDATE `gp:games`";
                $sqla[] = "SET `gpGa:OriginCode` = '".GAME_ORIGIN_CLASS."'";
                $sqla[] = "  , `gpGa:Opponents` =  '{$opList}'";
                $sqla[] = "WHERE `gpGa:GameId` = '{$gameId}'";
                $query1 = implode( $sqla, ' ');
                $this->cvt_performQuery($db,$query1,__LINE__,NULL,'Update Game - not general');
            }
            //$db->rc_commit();
        }     
        else {        
            $gameId = $row['gpGa:GameId'];
            $sqla = array();
            $sqla[] = "UPDATE `gp:games`";
            $sqla[] = "SET `gpGa:OriginCode` = '".GAME_ORIGIN_CLASS."'";
            $sqla[] = "  , `gpGa:Opponents` =  ''";
            $sqla[] = "WHERE `gpGa:GameId` = '{$gameId}'";
            $query3 = implode( $sqla, ' ');
            $this->cvt_performQuery($appGlobals,$query3,__LINE__,NULL,'Update Game - General - First');
        } 
    }    
}

function cvt_calcCompressedArrayTotal($str) {
    if ( empty($str)) {
        return 0;
    }    
    return array_sum(explode('~', $str));
}


function cvt_performQuery($appGlobals,$query,$line,$debugMode=NULL, $desc=NULL) {
    if ( $debugMode!=NULL) {
        if ( $desc!=NULL) {
             echo '<br><span style="display:inline-block;margin-top:14pt; padding: 4pt; border: 1pt;background-color:#cccccc;line-height:12pt;">' . $desc . '</span>';
        }     
        echo '<br><span style="display:inline-block;margin-bottom: 4pt;padding: 4pt; border: 1pt;background-color:white;line-height:12pt;">' . $query . '</span>';
        if ( $debugMode>5) {
            return FALSE;
        }     
    }
    $result = $db->rc_query( $query );
    if ( $result === FALSE) {
        $appGlobals->gb_sql->sql_errorTerminate($query);
    }
    return $result;
}

}  // end class

?>