<?php

// roster-system-data-points.inc.php

// these objects are used for editing points
// the point objects associated with the roster are with the roster objects

// does not include lists of points (this list is in "view")

class stdData_pointUnit_item extends stdData_pointsUnit_record {
    
function pnt_init($appGlobals, $roster, $originCode, $kidPeriodId, $classDate) {
    $kidPeriod = $roster->rst_cur_period->perd_getKidPeriodObject($kidPeriodId);
    $kid = $roster->rst_get_kid($kidPeriod->kidPer_kidId);
    $this->pnt_kidId = $kid->rstKid_kidId;       
    $this->pnt_programId =  $roster->prog_programId;
    $this->pnt_kidProgramId = $kidPeriod->kidPer_kidProgramId;
    $this->pnt_classDate = $classDate;  
    $this->pnt_modByAtStaffId = $appGlobals->gb_db->rc_MakeSafe( rc_getStaffId());
    $this->pnt_kidPeriodId = $kidPeriodId;
    $this->pnt_originCode = $originCode;
    $this->pnt_whenCreated = rc_getNow();    //   ???????
    $this->pnt_modWhen = rc_getNow();    //   ???????      
}

function pnt_classRecord_read($appGlobals, $pointsId){
    $this->pnt_record_read($appGlobals, $pointsId);
}

function pnt_classRecord_write($appGlobals) {
   // $this->originCode = GAME_ORIGIN_CLASS;
    return $this->pnt_record_write($appGlobals);
}


function pnt_tallyRecord_read($appGlobals, $kidPeriodId, $classDate) {
    $sql = array();
    $sql[] =  'Select *';
    $sql[] = "FROM `gp:points`";
    $sql[] = " WHERE (`gpPo:@KidPeriodId`='{$kidPeriodId}') AND (`gpPo:ClassDate`='{$classDate}') AND (`gpPo:OriginCode` = '".GAME_ORIGIN_TALLY."')";
    $query = implode( $sql, ' '); 
    $result = $appGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
    assert( $result->num_rows <= 1, 'pnt_tallyRecord_read - there is more than one tally record' );
    if ($result->num_rows < 1) {
        $this->pnt_init($appGlobals,$roster, GAME_ORIGIN_TALLY,$kidPeriodId,$classDate);
    }
    else {
        $row=$result->fetch_array();
        $this->pnt_loadRow($row);
    }    
}

function pnt_tallyRecord_write($appGlobals) {
    $this->originCode = GAME_ORIGIN_TALLY;
    $pointsId = $this->pnt_record_write($appGlobals);
    return $pointsId;
}

function pnt_record_read($appGlobals, $pointId) {
    $sql = array();
    $sql[] =  'Select *';
    $sql[] = "FROM `gp:points`";
    $sql[] = " WHERE `gpPo:PointsId`='{$pointId}'";
    $query = implode( $sql, ' '); 
    $result = $appGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
    if ($result->num_rows == 0) {
        $appGlobals->gb_sql->sql_fatalError( __FILE__,__LINE__ , $query);
    }
    else {
        $row=$result->fetch_array();
        $this->pnt_loadRow($row);
    }    
}

function pnt_record_write($appGlobals) {
    $orgRec = new stdData_pointsUnit_record;
    if ($this->pnt_pointsId != 0) {
        $orgRec->pnt_record_read($appGlobals,$this->pnt_pointsId);
        $orgRec->pnt_category = trim($orgRec->pnt_category); // important - combo box returns ' ', not ''
        $this->pnt_category   = trim($this->pnt_category);  // important - combo box returns ' ', not ''
        $changed = ( ($orgRec->pnt_pointValue != $this->pnt_pointValue) 
               or ($orgRec->pnt_kidPeriodId != $this->pnt_kidPeriodId)
               or ($orgRec->pnt_classDate != $this->pnt_classDate)
               or ($orgRec->pnt_category != $this->pnt_category)
               or ($orgRec->pnt_note != $this->pnt_note) );
       if ( ! $changed ) {
            return $this->pnt_pointsId;  // don't save record if unchanged 
        }   
        $orgRec->pnt_updateKidTotals($appGlobals,-1);
    } 
    else if ( ($this->pnt_pointValue == 0) and ($orgRec->pnt_pointValue == 0) ) {
        // if class record: Zero is invalid value for points - should never happen
        // if tally record: Zero is valid value for points, but do not need to save tally record if zero unless changing value
        return $this->pnt_pointsId; 
    }
    //$appGlobals->gb_sql->sql_transaction_start ($appGlobals);  //?????????????????????????
    $this->pnt_updateKidTotals($appGlobals,1);
    $this->pnt_modByAtStaffId  = $appGlobals->gb_db->rc_MakeSafe( rc_getStaffId() );
    $fields = array();
    // fields appData to update and insert
    $fields['gpPo:OriginCode'   ] = $this->pnt_originCode;
    $fields['gpPo:PointValue'   ] = $this->pnt_pointValue;     
    $fields['gpPo:Note'         ] = $this->pnt_note;          
    $fields['gpPo:Category'     ] = trim($this->pnt_category);  // important - combo box returns ' ', not ''         
    $fields['gpPo:ModBy@StaffId'] = $this->pnt_modByAtStaffId;    
    $fields['gpPo:ModWhen'      ] = rc_getNow();         
    if ($this->pnt_pointsId==0) {
        $fields['gpPo:@ProgramId'   ] = $this->pnt_programId; 
        $fields['gpPo:@KidId'       ] = $this->pnt_kidId; 
        $fields['gpPo:@KidProgramId'] = $this->pnt_kidProgramId; 
        $fields['gpPo:@KidPeriodId' ] = $this->pnt_kidPeriodId; 
        $fields['gpPo:WhenCreated'  ] = $this->pnt_whenCreated;      
        $fields['gpPo:ClassDate'    ] = $this->pnt_classDate;
        $query = kcmRosterLib_db_insert($appGlobals->gb_db,'gp:points',$fields);
    }
    else {    
        $query = kcmRosterLib_db_update($appGlobals->gb_db,'gp:points',$fields,"WHERE `gpPo:PointsId` = '{$this->pnt_pointsId}'");
        $pointsId = $this->pnt_pointsId;
   }     
    $result = $appGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
    if ($this->pnt_pointsId==0) {
        $pointsId = $appGlobals->gb_db->insert_id;
    }    
    //$appGlobals->gb_sql->sql_transaction_end ($appGlobals);
    return $pointsId;
}

function pnt_record_delete($appGlobals) {
   // $appGlobals->gb_sql->sql_transaction_start ($appGlobals);
    $this->pnt_updateKidTotals($appGlobals, -1);
    $query = "DELETE FROM `gp:points` WHERE `gpPo:PointsId`='"
         .$this->pnt_pointsId ."'";
    $result = $appGlobals->gb_sql->sql_performQuery( $query , __FILE__, __LINE__ );
   // $appGlobals->gb_sql->sql_transaction_end ($appGlobals);
    //return $this->pnt_pointsId;
}

private function pnt_updateKidTotals($appGlobals, $sign) {
    $signSymbol = ($sign>=0) ? '+' : '-';
    $sql = array();
    $sql[] = "UPDATE `ro:kid_period`";
    $sql[] = "SET `rKPe:KcmGeneralPoints`=`rKPe:KcmGeneralPoints`{$signSymbol}'{$this->pnt_pointValue}'";
    $sql[] = ", `rKPe:KcmPerPointValues` = `rKPe:KcmPerPointValues`{$signSymbol}'{$this->pnt_pointValue}'"; // KCM1 - evantually eliminate !!!!
    $sql[] = "WHERE `rKPe:KidPeriodId` = '".$this->pnt_kidPeriodId. "'";
    $query = implode( $sql, ' '); 
    $result = $appGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
}

function pnt_getResultString($appGlobals, $points = NULL) {
    $kidPeriod = $appGlobals->rst_cur_period->perd_getKidPeriodObject($this->pnt_kidPeriodId);
    $kidName = $kidPeriod->kidPer_kidObject->rstKid_uniqueName;
    $p = ($points ===NULL) ? $this->pnt_pointValue : $points;
    return 'Points Saved: ' . $kidName . ' (' . $p . ' Points)';
}
    
} // end class

class kcm2_pntBu_points_bundle {
public $pnt_pointUnit_map;      

function pntBu_read_pointsBundle($appGlobals, $periodId, $kidPeriodId, $classDate) {
//    $program = $appGlobals->rst_program;
// $this->pntBu_filter_classDate = '2017-04-24';
    $kcm1PointCats = NULL;
    $curKey = NULL;
    $this->pnt_pointUnit_map = array();
    $sql = array();
    $sql[] = "SELECT `rKPe:@PeriodId`,`gpPo:PointsId`,`gpPo:@KidId`,`gpPo:@ProgramId`,`gpPo:@KidProgramId`,`gpPo:@KidPeriodId`,`gpPo:CategoryIndex`,`gpPo:CategoryClue`";
    $sql[] = ",`gpPo:PointValue`,`gpPo:Category`,`gpPo:Note`,`gpPo:OriginCode`,`gpPo:ClassDate`,`gpPo:WhenCreated`,`gpPo:ModBy@StaffId`,`gpPo:ModWhen`";
    $sql[] = "FROM  `gp:points` "; 
	$sql[] = "LEFT JOIN `ro:kid_period` ON `rKPe:KidPeriodId` = `gpPo:@KidPeriodId`";
	$sql[] = "WHERE (`rKPe:@PeriodId`='{$appGlobals->rst_cur_period->perd_periodId}') AND (`gpPo:OriginCode`<>'".SCORES_ORIGIN_TALLY."')";
    if ($kidPeriodId>=1) {
 	    $sql[] = " AND (`gpPo:@KidPeriodId` = '{$kidPeriodId}')";
    }
    if (!empty($classDate)) {
 	    $sql[] = " AND (`gpPo:ClassDate` = '{$classDate}')";
    }
    $sql[] = "ORDER BY `gpPo:ClassDate` DESC, `gpPo:WhenCreated` DESC"; // `gpPo:@KidPeriodId`, ; // ????? not sure ????? need general rec to be in first group
    $query = implode( $sql, ' '); 
    //echo '<hr>read_pointDetails<hr>' . implode($sql,PHP_EOL) . '<hr>';
    $result = $appGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
    while ($row=$result->fetch_array()) {
        $pnt = new stdData_pointsUnit_record;
        $pnt->pnt_loadRow($row);
        $this->pnt_pointUnit_map[$pnt->pnt_pointsId] = $pnt;
    }    
    $this->pntBu_count = count($this->pnt_pointUnit_map);
}

}  // end class

//======
//============
//==================
//========================
//= Local Session
//========================
//==================
//============
//======

//Class kcm2_points_localSession {
//public $ses_points;
//
//function __construct() {
//    $this->ses_points = new stdData_pointsUnit_record;
//}
//
//function saveValidatedPoints($appGlobals) {
//    $pointId = $this->ses_points->pnt_classRecord_write($appGlobals,'a');
//    $editUrl =  $appGlobals->gb_form->chain->chn_xxurl_build_chained_url('chRec',$pointId,'chStep',11, ); 
//    $s = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . $editUrl . '">Edit</a>';
//    $savedPoints = new stdData_pointsUnit_record;
//    $savedPoints->pnt_classRecord_read($appGlobals, $pointId);
//    $appGlobals->gb_form->chn_theme-message-error-error_setStatus($savedPoints->pnt_getResultString($appGlobals, $this->ses_points->pnt_pointValue) . $s );
//    $appGlobals->gb_form->chain->chn_stream_destroy();
//}
//
//} // end class

?>