<?php

// kernel-schedule.inc.php

//======
//============
//==================
//========================
//=   Event Date
//========================
//==================
//============
//======

class dbRecord_calDate  extends draff_database_record {

const DB_TABLE_NAME  = 'ca:scheduledate';
const DB_TABLE_INDEX = 'cSD:ScheduleDateId';
const DB_MOD_WHEN    = 'cSD:ModWhen';
const DB_MOD_WHO     = 'cSD:ModBy@StaffId';
const DB_HISTORY_TABLE  = '*';  // * = prefix history_ to table name 
const DB_HISTORY_BEFORE  = TRUE;
const DB_HISTORY_AFTER   = TRUE;
const DB_SELECT_FIELDS  = array(
    'cSD:ScheduleDateId'       
    , 'cSD:@ProgramId'
    , 'cSD:ClassDate', 'cSD:StartTime', 'cSD:EndTime'     
    , 'cSD:HolidayFlag', 'cSD:SMSubmissionStatus', 'cSD:Published?'   
    , 'cSD:Notes', 'cSD:NotesIncidents', 'cSD:NotesActivities'  
    );

//--- Defined in database  cSd
public $cSD_scheduleDateId;
public $cSD_programId;
public $cSD_classDate;
public $cSD_startTime;
public $cSD_endTime;
public $cSD_isHoliday;  
public $cSD_notes;  
public $cSD_notesIncidents;  
public $cSD_notesActivities;  
public $cSD_SMSubmissionStatus;  
public $cSD_isPublished;  
//--- Not defined in database
public $cSD_staffMap = array();  // map of staff assopiciated with this event

function __construct($row=NULL) {
    if ( is_array($row)) {
        $this->cSD_loadRow($row);
    }
}
function cSD_loadRow($row, $flags=0) {
    $this->cSD_scheduleDateId     = $row['cSD:ScheduleDateId'];       
    $this->cSD_programId          = $row['cSD:@ProgramId'];       
    $this->cSD_classDate          = $row['cSD:ClassDate'];       
    $this->cSD_startTime          = $row['cSD:StartTime'];    
    $this->cSD_endTime            = $row['cSD:EndTime'];     
    $this->cSD_isHoliday          = ($row['cSD:HolidayFlag'] == 1); 
    $this->cSD_SMSubmissionStatus = $row['cSD:SMSubmissionStatus'];   
    $this->cSD_isPublished        = $row['cSD:Published?'];   
    if (isset($row['cSD:Notes'])) {    
        $this->cSD_notes              = $row['cSD:Notes'];   
        $this->cSD_notesIncidents     = $row['cSD:NotesIncidents'];   
        $this->cSD_notesActivities    = $row['cSD:NotesActivities'];   
    }    
    if ( draff_isBitSet($flags,ROW_ASGN_JOIN_STAFF) ) {    
        if ($this->cSS_asgDate == NULL) { 
            $newStaff = new dbRecord_calStaff;
            $newStaff->cSS_loadRow($row, $flags=0);
            $this->cSD_staffMap[$newStaff->cSS_calStaffId] = $newStaff;
       }
    }    
}

function cSD_readRecord($appGlobals, $scheduleDateId, $flags=0) {  
    $this->cSD_staffMap = array();
    $fldList = array(); 
    $fldList = self::stdRec_addFieldList($fldList,ROW_ASGN_NOTES);
    $fldList = dbRecord_program::stdRec_addFieldList($fldList);
    $fldList = dbRecord_calStaff::cSS_addFieldList($fldList, ROW_ASGN_JOIN_STAFFNAME);
    $fldList = cSt_staff_kcmRecord::cSS_addFieldList($fldList);
    $fields = "`" . implode($fldList,"`, `") . "`";
    $sql = array();
    $sql[] = "SELECT {$fields}";
    $sql[] = "FROM `ca:scheduledate`";
    $sql[] = "JOIN `ca:scheduledate_staff` ON `cSS:@ScheduleDateId` = `cSD:ScheduleDateId`";
    $sql[] = "JOIN `pr:program` ON `pPr:ProgramId` = `cSD:@ProgramId`";
    $sql[] = "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`";
    $sql[] = "JOIN `st:staff` ON `sSt:StaffId` = `cSS:@StaffId`";
    $sql[] = "WHERE `cSD:ScheduleDateId` ='{$scheduleDateId}'";
    $query = implode( $sql, ' ');
    $first = TRUE;
    $result = $appGlobals->gb_pdo->rsmDbe_execute( $query );
    foreach($result as $row) {
        if ($first) {
            $this->stdRec_loadRow($row);
            //$this->evtDate_program = new dbRecord_program;
            //$this->evtDate_program->stdRec_loadRow($row, $flags=0);
            $first = FALSE;
        }
       // $newStaff = new dbRecord_calStaff;
        //$newStaff->stdRec_loadRow($row, $flags=0);
        //$this->cSD_staffMap[$newStaff->cSS_calStaffId] = $newStaff;
    }
    //$this->prog_renameProgramIfOtherSemester();
}

function cSD_addStaff($row) {
    $newStaffItem = new dbRecord_staff;
    $newStaffItem->rsmDbr_loadRow($row);
    $this->cSD_staffMap	[$newStaffItem->sSt_staffId] = $newStaffItem;
}
        
} // end class

//======
//============
//==================
//========================
//=   Event Staff
//========================
//==================
//============
//======

Class dbRecord_calStaff  extends draff_database_record {

const DB_TABLE_NAME  = 'ca:scheduledate_staff';
const DB_TABLE_INDEX = 'cSS:ScheduleDateStaffId';
const DB_MOD_WHEN    = 'cSS:ModWhen';
const DB_MOD_WHO     = 'cSS:ModBy@StaffId';
const DB_HISTORY_TABLE  = '*';  // * = prefix history_ to table name 
const DB_HISTORY_BEFORE  = TRUE;
const DB_HISTORY_AFTER   = TRUE;
const DB_SELECT_FIELDS  = array(
    'cSS:ScheduleDateStaffId', 'cSS:@ScheduleDateId'       
    , 'cSS:@StaffId'
    , 'cSS:RoleType', 'cSS:TimeArrived', 'cSS:TimeLeft'     
    , 'cSS:TimeAdjustment', 'cSS:HadEquipment', 'cSS:HadBadge'   
    , 'cSS:Notes', 'cSS:HiddenStatus', 'cSS:ModBy@StaffId' , 'cSS:ModWhen' 
    );

public $cSS_calStaffId;
public $cSS_calDateId;
public $cSS_staffId;
public $cSS_roleType;
public $cSS_timeArrived;
public $cSS_timeLeft;
public $cSS_timeAdjustment;  
public $cSS_hadEquipment;
public $cSS_hadBadge;  
public $cSS_staffName  = '';  // optional
public $cSS_Notes;  
public $cSS_asgDate    = NULL;  // optional: joined assignment date object (unused not needed or causes circular reference)
public $cSS_asgProgram = NULL;  // optional: joined assignment date object (unused not needed or causes circular reference)

function __construct($row=NULL) {
    if ( is_array($row)) {
        $this->cSS_loadRow($row);
    }
}
//cSS_loadRow
function cSS_loadRow($row, $flags=0) {
    $this->cSS_calStaffId     = $row['cSS:ScheduleDateStaffId'];
    $this->cSS_calDateId      = $row['cSS:@ScheduleDateId'];
    $this->cSS_staffId        = $row['cSS:@StaffId'];
    $this->cSS_roleType       = $row['cSS:RoleType'];
    $this->cSS_timeArrived    = $row['cSS:TimeArrived'];
    $this->cSS_timeLeft       = $row['cSS:TimeLeft'];  
    $this->cSS_timeAdjustment = $row['cSS:TimeAdjustment'];  
    $this->cSS_hadEquipment   = $row['cSS:HadEquipment'];  
    $this->cSS_hadBadge       = $row['cSS:HadBadge'];  
    $this->cSS_Notes          = $row['cSS:Notes']; 
    if ( draff_isBitSet($flags,ROW_ASGN_JOIN_DATE) ) {    
        if ($this->cSS_asgDate == NULL) { 
            $this->cSS_asgDate = new dbRecord_calDate;
            $this->cSS_asgDate->stdRec_loadRow($row, $flags=0);
        }
    }    
    if ( isset($row['sSt:FirstName']) ) {  
        $this->cSS_staffName = $row['sSt:FirstName'] . ' ' . $row['sSt:LastName'];
    }    
}

static function cSS_appendSelect($queryCommand, $flags=0) { 
    $queryCommand->draff_sql_selectFields('cSS:ScheduleDateStaffId','cSS:@ScheduleDateId','cSS:@StaffId','cSS:RoleType');
    $queryCommand->draff_sql_selectFields('cSS:TimeArrived','cSS:TimeLeft','cSS:TimeAdjustment','cSS:HadEquipment');
    $queryCommand->draff_sql_selectFields('cSS:HadBadge','cSS:Notes');
    if (draff_isBitSet($flags,ROW_ASGN_JOIN_STAFFNAME) ) {
        $queryCommand->selectFields('sSt:FirstName','sSt:LastName');
    }
}
  
static function cSS_addFieldList($fldList, $flags=0) { 
// $queryString->selectFields('cSS:ScheduleDateStaffId','cSS:@ScheduleDateId','cSS:@StaffId','cSS:RoleType');
 // $queryString->selectFields('cSS:TimeArrived','cSS:TimeLeft','cSS:TimeAdjustment','cSS:HadEquipment');
  // $queryString->selectFields('cSS:HadBadge','cSS:Notes');
  $fldList[] = 'cSS:ScheduleDateStaffId';
    $fldList[] = 'cSS:@ScheduleDateId';
    $fldList[] = 'cSS:@StaffId';
    $fldList[] = 'cSS:RoleType';
    $fldList[] = 'cSS:TimeArrived';
    $fldList[] = 'cSS:TimeLeft';  
    $fldList[] = 'cSS:TimeAdjustment';  
    $fldList[] = 'cSS:HadEquipment';  
    $fldList[] = 'cSS:HadBadge';  
    $fldList[] = 'cSS:Notes';  
    if (draff_isBitSet($flags,ROW_ASGN_JOIN_STAFFNAME) ) {
     // $queryString->selectFields('sSt:FirstName','sSt:LastName');
        $fldList[] = 'sSt:FirstName';  
        $fldList[] = 'sSt:LastName';  
   }
    return $fldList;
}
  
} // end class


//-- Class cSS_calStaff_kcmBatch {
//-- 
//-- function __construct() {
//-- }
//-- 
//-- } // end class

class dbRecord_programAuthorization  extends draff_database_record {

const DB_TABLE_NAME  = 'ca:programauthorization';
const DB_TABLE_INDEX = 'cPA:ProgramAuthorizationId';
const DB_MOD_WHEN    = 'cPA:ModWhen';
const DB_MOD_WHO     = 'cPA:ModBy@StaffId';
const DB_HISTORY_TABLE  = '*';  // * = prefix history_ to table name 
const DB_HISTORY_BEFORE  = TRUE;
const DB_HISTORY_AFTER   = TRUE;
const DB_SELECT_FIELDS  = array(
    'cPA:ProgramAuthorizationId','cPA:CourseKey'
    , 'cPA:@StaffId', 'cPA:@SchoolId', 'cPA:@ProgramId'
    , 'cPA:ProgramType', 'cPA:ProgramDow', 'cPA:RoleType', 'cPA:DateExpires', 'cPA:ReadOnly'
    , 'cPA:ModBy@StaffId', 'cPA:ModWhen'
);

public $cPA_authorizationId      = 0;
public $cPA_courseKey       = 0;    
public $cPA_staffId         = 0;
public $cPA_schoolId        = 0;
public $cPA_programId       = 0;    // is zero for classes (multiple programs), but not events
public $cPA_programType     = 0;    // is zero for classes (multiple programs), but not events
public $cPA_programDow      = 0;    
public $cPA_roleType        = 0;
public $cPA_dateExpires     = NULL;
public $cPA_modByStaffId    = 0;  
public $cPA_modByWhen       = 0;  
public $cPA_readOnly     = 0;     

function __construct($row=NULL) {
    if (is_array($row)) {
        $this->cPA_loadRow($row);
    }
}

function cPA_loadRow($row, $flags=0) {
    if ($row == NULL) {
        return;  // empty record
    }
    $this->cPA_authorizationId = $row['cPA:ProgramAuthorizationId'];
    $this->cPA_courseKey       = $row['cPA:CourseKey'];
    $this->cPA_staffId         = $row['cPA:@StaffId'];
    $this->cPA_schoolId        = $row['cPA:@SchoolId'];
    $this->cPA_programId       = $row['cPA:@ProgramId'];
    $this->cPA_programType     = $row['cPA:ProgramType'];
    $this->cPA_programDow      = $row['cPA:ProgramDow'];
    $this->cPA_roleType        = $row['cPA:RoleType'];
    $this->cPA_dateExpires     = $row['cPA:DateExpires'];
    $this->cPA_readOnly        = $row['cPA:ReadOnly'];
    $this->cPA_modByStaffId    = $row['cPA:ModBy@StaffId'];
    $this->cPA_modByWhen       = $row['cPA:ModWhen'];
}

} // end class

?>
