<?php

// kernel-objects.inc.php

// classes used by kcm, payroll, gateway, etc

// kernel-objects.inc.php

// functions and classes scriptData to kcm, payroll, gateway, etc

CONST ROW_PROG_NOTES  = 1;// include note fields
CONST ROW_PROG_POINTS = 2;  // include point fields
CONST ROW_PROG_NOSCHOOL = 4;  // do not compute program name as join with school table is necessary
CONST ROW_PROG_DOW     = 8;  // place dow at end of school name Ford (Monday)

// calander date flags must be unique between date, staff, etc
CONST ROW_ASGN_NOTES = 1;  // include note fields
CONST ROW_ASGN_JOIN_DATE  = 2;  // include assignment date operation - joining date and staff will cause infinite loop
CONST ROW_ASGN_JOIN_STAFF = 4;  // include assignment staff operation - joining date and staff will cause infinite loop
CONST ROW_ASGN_JOIN_STAFFNAME = 8;  // include assignment staff operation - joining date and staff will cause infinite loop

CONST AUTH_OPTIONBIT_VIEWONLY = 1;
CONST AUTH_OPTIONBIT_BETATESTER = 256; // to give access to beta testers in live system

Class dbRecord_school_base extends draff_database_record {

const DB_TABLE_NAME  = 'pr:school';
const DB_TABLE_INDEX = 'pSc:SchoolId';
const DB_MOD_WHEN    = 'pSc:ModWhen';
const DB_MOD_WHO     = 'pSc:ModBy@StaffId';
const DB_SELECT_FIELDS  = array (
     'pSc:SchoolId'
    ,'pSc:SortOrder'
    ,'pSc:NameFull','pSc:NameShort'
    ,'pSc:HiddenStatus'
    );

public $school_id;
public $school_sort;
public $school_nameFull;
public $school_nameShort;
public $school_hidden;

function __construct($row = NULL) {
    if (is_array($row) ) {
        $this->stdRec_loadRow($row);
    }
}

function stdRec_loadRow($row, $flags=0) {
    $this->school_id = $row['pSc:SchoolId'];
    $this->school_sort = $row['pSc:SortOrder'];
    $this->school_nameFull = $row['pSc:NameFull'];
    $this->school_nameShort = $row['pSc:NameShort'];
    $this->school_hidden = $row['pSc:HiddenStatus'];

}

} // end class

Class dbRecord_program extends draff_database_record {

const DB_TABLE_NAME  = 'pr:program';
const DB_TABLE_INDEX = 'pPr:ProgramId';
const DB_MOD_WHEN    = 'pPr:ModWhen';
const DB_MOD_WHO     = 'pPr:ModBy@StaffId';
const DB_SELECT_FIELDS  = array (
    'pPr:ProgramId','pPr:@SchoolId'
    ,'pPr:ProgramName','pPr:SchoolNameUniquifier'
    ,'pPr:ProgramType','pPr:SemesterCode','pPr:SchoolYear','pPr:DayOfWeek'
    ,'pPr:DateClassFirst','pPr:DateClassLast','pPr:DatesConfirmed?'
    ,'pPr:KcmVersion'
    ,'pPr:HiddenStatus'
    ,'pPr:KcmPointCategories','pPr:KcmPointCatList'
    ,'pPr:NotesRoomInfo','pPr:NotesForCoach','pPr:NotesForSiteLeader','pPr:NotesUponArriving'
    ,'pPr:NotesBeforeLeaving','pPr:NotesASPInstructions','pPr:NotesParentPickup'
    //,'pSc:NameShort'
    //,'pSc:SchoolId'
   // ,'pSc:HiddenStatus'
    );
// const DB_JOINS = array('school'=>'');
//  need to add below SQL_COURSEID to select when using CourseID in the SQL (as in GROUP) - not needed if not in SQL - loadRow does not use this field
const SQL_COURSEID = "IF (`pPr:ProgramType`='1',CONCAT(`pPr:@SchoolId`,'-',`pPr:DayOfWeek`),`pPr:ProgramId`) AS `CourseId`";

public $prog_programId;
public $prog_schoolId;
public $prog_semester;
public $prog_progNamePrefix;
public $prog_progType;
public $prog_nameUniquifier;
public $prog_schoolYear;
public $prog_dayOfWeek;
public $prog_dateFirst;
public $prog_dateLast;
public $prog_kcmVersion;  // 2=version 2 1=conversion in progress
// only used by KCM-1 roster points
public $prog_pointCategories;         // only used by KCM-2 and conversion
public $prog_kcm1PointCatCompressed;  // only used by KCM-1 and conversion
public $prog_notesRooomInfo;       //  pPr:NotesRoomInfo
public $prog_notesCoach;           //  pPr:NotesForCoach
public $prog_notesSiteLeader;      //  pPr:NotesForSiteLeader
public $prog_notesUponArriving;    //  pPr:NotesUponArriving
public $prog_notesBeforeLeaving;   //  pPr:NotesBeforeLeaving
public $prog_NotesASPInstructions; //  pPr:NotesASPInstructions
public $prog_NotesParentPickup;    //  pPr:NotesParentPickup
public $prog_authKey;              //  pPr:NotesParentPickup
// computed
public $prog_courseKey;
public $prog_programName = '';  // computed to standardized name

function __construct($row = NULL) {
    if (is_array($row) ) {
        $this->stdRec_loadRow($row);
    }
}

function stdRec_loadRow($row, $flags=0) {
    // automatically handles join with school to get program name
    $this->prog_schoolId        = $row['pPr:@SchoolId'];
    $this->prog_programId       = $row['pPr:ProgramId'];
    $this->prog_progNamePrefix  = $row['pPr:ProgramName'];
    $this->prog_progType        = $row['pPr:ProgramType'];
    $this->prog_schoolYear      = $row['pPr:SchoolYear'];
    $this->prog_semester        = $row['pPr:SemesterCode'];
    $this->prog_dayOfWeek       = $row['pPr:DayOfWeek'];
    $this->prog_dateFirst       = $row['pPr:DateClassFirst'];
    $this->prog_dateLast        = $row['pPr:DateClassLast'];
    $this->SchoolNameUniquifier = $row['pPr:SchoolNameUniquifier'];
    $this->prog_courseKey = ($this->prog_progType == 1) ? ( $this->prog_schoolId . '-' . $this->prog_dayOfWeek ) : $this->prog_programId;
    $this->prog_progNamePrefix        = $this->prog_programName;
    //if (isset($row['pSc:HiddenStatus'])and ($row['pSc:HiddenStatus']==1)  ){
    //}
    if ( isset($row['pSc:NameShort']) ) {  // This must be set to get "standard" name
        $this->prog_programName = $row['pSc:NameShort'];
        if ($this->SchoolNameUniquifier != '') {
            $this->prog_programName .= ' - ' . $this->SchoolNameUniquifier;
        }
        if ($this->prog_progType == 1) {
            if ( ($flags && ROW_PROG_DOW) == ROW_PROG_DOW) {
                $this->prog_programName .= ' (' . rc_getDayOfWeekNameFromNumber($this->prog_dayOfWeek) .')';
            }
        }
        else {
            $dates = draff_dateAsString( $this->prog_dateFirst , 'm/d/Y' );
            if ( $this->prog_dateLast != $this->prog_dateFirst) {
                $dates .= ' to ' . draff_dateAsString( $this->prog_dateLast , 'm/d' );
            }
            switch ($this->prog_progType) {
                case 2: $this->prog_programName = $this->prog_programName;
                        // uniquifier must have camp date
                        break;
                case 3: $this->prog_programName = 'Tournament on ' . $dates . ' at ' . $this->prog_programName;
                        break;
                case 4: $this->prog_programName = 'Special Event at ' . $this->prog_programName;
                        break;
                        //???? add ' . $dates . '
                case 9: $this->prog_programName = 'Special Event ' . $dates . ' at ' . $this->prog_programName;
                        break;
            }
        }
    }
    if ( isset($row['pPr:KcmPointCategories']) ) {
        $this->prog_kcm1PointCatCompressed = $row['pPr:KcmPointCategories'];
        $this->prog_pointCategories = explode(',',$row['pPr:KcmPointCatList']);
    }
    else {
        $this->prog_pointCategories = array();
    }
    if ( isset($row['pPr:NotesRoomInfo']) ) {
        $this->prog_notesRooomInfo       = $row['pPr:NotesRoomInfo'];
        $this->prog_notesCoach           = $row['pPr:NotesForCoach'];
        $this->prog_notesSiteLeader      = $row['pPr:NotesForSiteLeader'];
        $this->prog_notesUponArriving    = $row['pPr:NotesUponArriving'];
        $this->prog_notesBeforeLeaving   = $row['pPr:NotesBeforeLeaving'];
        $this->prog_NotesASPInstructions = $row['pPr:NotesASPInstructions'];
        $this->prog_NotesParentPickup    = $row['pPr:NotesParentPickup'];
    }
    $this->prog_authKey = ($this->prog_progType==1) ? $this->prog_schoolId . '-' . $this->prog_dayOfWeek : $this->prog_programId;
}

static function pPr_appendSelect($queryCommand, $flags=0) {
    $queryCommand->draff_sql_selectFields('pPr:ProgramId','pPr:@SchoolId');
    $queryCommand->draff_sql_selectFields('pPr:SemesterCode','pPr:ProgramName','pPr:ProgramType');
    $queryCommand->draff_sql_selectFields('pPr:SchoolNameUniquifier');
    $queryCommand->draff_sql_selectFields('pPr:SchoolYear','pPr:DayOfWeek','pPr:DateClassFirst','pPr:DateClassLast');
    $queryCommand->draff_sql_selectFields('pPr:KcmVersion');
    $queryCommand->draff_sql_selectFields('pPr:HiddenStatus');
    if ( ($flags && ROW_PROG_POINTS) == ROW_PROG_POINTS) {
        $queryCommand->draff_sql_selectFields('pPr:KcmPointCategories','pPr:KcmPointCatList');
    }
    if ( ($flags && ROW_PROG_NOTES) == ROW_PROG_NOTES) {
        $queryCommand->draff_sql_selectFields('pPr:NotesRoomInfo','pPr:NotesForCoach','pPr:NotesForSiteLeader');
        $queryCommand->draff_sql_selectFields('pPr:NotesUponArriving','pPr:NotesBeforeLeaving','pPr:NotesASPInstructions','pPr:NotesParentPickup');
    }
    if ( ($flags && ROW_PROG_NOSCHOOL) != ROW_PROG_NOSCHOOL) {
       $queryCommand->draff_sql_selectFields('pSc:NameShort','pSc:SchoolId','pSc:HiddenStatus');
    }
}

function stdRec_readRecord($appGlobals, $programId, $flags=0) {
    $fldList = array();
    $fldList = self::stdRec_addFieldList($fldList);
    $fields = "`" . implode($fldList,"`, `") . "`";
    $sql = array();
    $sql[] = "SELECT {$fields}";
    $sql[] = "FROM `pr:program`";
    $sql[] = "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`";
    $sql[] = "WHERE `pPr:ProgramId` ='{$programId}'";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query( $query );
    if ( $result === FALSE) {
        $appGlobals->gb_sql->sql_errorTerminate( $query);
    }
    $row=$result->fetch_array();
    $this->stdRec_loadRow($row);
    $this->prog_renameProgramIfOtherSemester();
}

function prog_isHistorical($appGlobals) {
    $now = rc_getNowDate();
    $dif = draff_dateDif($now, $this->prog_dateLast);
    if ($dif <= 60) {  // games should not be purged from database for at least a few months
        return FALSE;
    }
    $query = "SELECT COUNT(*) as `cnt` FROM `gp:games` WHERE (`gpGa:@ProgramId`='{$this->prog_programId}') LIMIT 1";
    $result = $appGlobals->gb_sql->sql_performQuery( $query , __FILE__ , __LINE__ );
    $row=$result->fetch_array();
    return ( $row['cnt'] < 1);  // historical data if no rows in game table
}

function prog_renameProgramIfOtherSemester() {
    $today = rc_getNowDate();
    $start = $this-> prog_dateFirst;
    $end   = $this-> prog_dateLast;
    if ( ($end<$today) or ($start>$today) ) {
        $name = $this->prog_programName;
        $semYear = $this->prog_schoolYear;
        $semCode = $this-> prog_semester;
        $semName = rc_getSemesterAndYearNameFromYearAndCodeList( $semYear, $semCode );
        $this->prog_programName .= ' (' . $semName . ')';
    }
}

} // end class

class dbRecord_staff extends draff_database_record {

const DB_TABLE_NAME  = 'st:staff';
const DB_TABLE_INDEX = 'sSt:StaffId';
const DB_MOD_WHEN    = 'sSt:ModWhen';
const DB_MOD_WHO     = 'sSt:ModBy@StaffId';
const DB_SELECT_FIELDS  = array (
     'sSt:StaffId', 'sSt:FirstName', 'sSt:LastName', 'sSt:ShortName'
     ,'sSt:Email', 'sSt:HomePhone', 'sSt:WorkPhone', 'sSt:CellPhone'
     ,'sSt:Notes'
     ,'sSt:HiddenStatus','sSt:HiddenStatus','sSt:ModBy@StaffId','sSt:ModWhen'
     );

public $sSt_staffId;
public $sSt_shortName;
public $sSt_firstName;
public $sSt_lastName;
public $sSt_name;  // computed
public $sSt_homePhone ;
public $sSt_workPhone;
public $sSt_cellPhone;
public $sSt_email;
public $sSt_hidden;

function __construct($row=NULL) {
    if (is_array($row)) {
        $this->rsmDbr_loadRow($row);
    }
}

function rsmDbr_loadRow($row, $flags=0) {
    $this->sSt_staffId   = $row['sSt:StaffId'];
    $this->sSt_shortName = $row['sSt:ShortName'];
    $this->sSt_firstName = $row['sSt:FirstName'];
    $this->sSt_lastName  = $row['sSt:LastName'];
    $this->sSt_homePhone = $row['sSt:HomePhone'];
    $this->sSt_workPhone = $row['sSt:WorkPhone'];
    $this->sSt_cellPhone = $row['sSt:CellPhone'];
    $this->sSt_email     = $row['sSt:Email'];
    $this->sSt_name      = $this->sSt_firstName . ' ' . $this->sSt_lastName;
    $this->sSt_hidden      = $row['sSt:HiddenStatus'];
}

}  // end class

Class schedule_oneProgram_eventDates extends dbRecord_program {
public $schProg_map_assignmentDate;
public $schProg_dateDefault;  // default date - based on todays date
public $schProg_isScheduled;  // true if any schedule records (from database, not "defaults"
//public $schProg_includeCoWorkers = NULL;  // needed for SM reporting

function schProg_read($db,$programId, $appGlobals) {
    //????????????????? usually schedule date join is not needed
    //?????????????????  except to get the closest class date
    $this->schProg_isScheduled = FALSE;
    $fields = array();
    $fields = dbRecord_program::stdRec_addFieldList($fields);
    $fields = dbRecord_calDate::cSD_getColumnNames($fields );
    $fieldList = "`" . implode("`,`", $fields) . "`";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
    $sql[] = "FROM `pr:program`";
    $sql[] = "JOIN  `ca:scheduledate` ON `cSD:@ProgramId` =`pPr:ProgramId`";
    $sql[] = "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`";
    $sql[] = "WHERE `pPr:ProgramId` ='{$programId}'";
    $sql[] = "ORDER BY `cSD:ClassDate`";
    $query = implode( $sql, ' ');
    $first = TRUE;
    $result = $appGlobals->gb_pdo->rsmDbe_execute( $query );
    //???@?@?@?@?@?@?@??????????????????????????????????????????????????????? Review below code
    foreach($result as $row) {
        if ($first) {
            parent::stdRec_loadRow($row);
            $first=FALSE;
        }
        $sd = new dbRecord_calDate;
        $sd->cSD_loadRow($row);
        if ( $row['cSD:ScheduleDateId']!==NULL) {
            $this->schProg_map_assignmentDate[$sd->cSD_scheduleDateId] = $sd;
            $this->schProg_isScheduled = TRUE;
        }
    }
    if (!$this->schProg_isScheduled) {
        $query = "SELECT * FROM `pr:program` WHERE `pPr:ProgramId` = '{$programId}'";
        $result = $appGlobals->gb_pdo->rsmDbe_execute( $query );
        $row=$result->fetch();
        $sd = new dbRecord_calDate;
        $sd->cSD_scheduleDateId = 0;
        $sd->cSD_classDate   = $row['pPr:DateClassFirst'];
        $sd->cSD_startTime   = '???';
        $sd->cSD_endTime     = '???';
        $sd->cSD_isHoliday   = FALSE;
        $sd->cSD_notes       = '';
        $this->schProg_map_assignmentDate[$sd->cSD_scheduleDateId] = $sd;  //@JPR-2019-12-03 10:25 added index
        $this->schProg_dateDefault = $sd->cSD_classDate;
    }
    else {
        $this->schProg_dateDefault = $this->schProg_getDefaultDate($appGlobals);
    }
}

function schProg_getScheduleDateObject($id=NULL) {
    if (empty($this->schProg_map_assignmentDate)) {
       return NULL;   //@JPR-2019-08-08 21:44   ???????
    }
    if ( ! empty($id) ) {
        foreach ($this->schProg_map_assignmentDate as $schedDateItem) {
            if ( $id == $schedDateItem->cSD_scheduleDateId) {
                return $schedDateItem;
            }
        }
     ///  return NULL;  // can bypass to get current class schedule date item
    }
    // no id specified
    foreach ($this->schProg_map_assignmentDate as $schedDateItem) {
        if ( $schedDateItem->cSD_classDate  == $this->schProg_dateDefault) {
            return $schedDateItem;
        }
    }
    return $this->schProg_dateDefault;
}

function schProg_getDefaultDate($appGlobals) {
    //?????? should skip over holidays ?????????
    $now = new DateTime();
    $now->modify('+45 minutes');  // 45 minutes before next poriod is next period
    $today = $now->format('Y-m-d H:i:s');
    if (empty($this->schProg_map_assignmentDate)) {
       return NULL;   //@JPR-2019-08-08 21:44   ???????
   }
    foreach ($this->schProg_map_assignmentDate as $schedDateItem) {
        $classDateTime = $schedDateItem->cSD_classDate . ' ' . $schedDateItem->cSD_startTime;
        if ( $today >= $classDateTime) {
            $matchDate = $schedDateItem->cSD_classDate;
        }
    }
    return $matchDate;
}

}  // end class

class report_filters {
public  $rf_report_key = '';   // for saving filters (also keyed by staff ID)
public  $rf_export_pdf   = TRUE;
public  $rf_export_excel = TRUE;
public  $rf_export_code  = 'h';
private $rf_export_status = 0;  // status: 0=none 1=set by script (hidden) 2=set by user
public  $rf_period_list = array();
public  $rf_period_code = '';
private $rf_period_status = 0;
public  $rf_sort_list = array();
public  $rf_sort_code = '';
private $rf_sort_status = 0;
public  $rf_group_list = array();
public  $rf_group_code = '';
private $rf_group_status = 0;
public  $rf_check_list = array();
public  $rf_check_checked = array();
public  $rf_check_unchecked = array();
private $rf_check_status = 0;
public  $rf_flags = array();   // flags for specific options (including hiding other filters, etc)

function __construct($reportKey) {
    $this->rf_report_key = $reportKey;
}

private function rf_form_getStatus($array) {
}

function rf_form_initControls($form) {
    if ($this->rf_export_pdf) {
        $this->drForm_addField( new Draff_Button( '@pdf' , 'Export PDF' ) );
    }
    if ($this->rf_export_excel) {
        $this->drForm_addField( new Draff_Button( '@excel' , 'Export Excel' ) );
    }
 return;
    $this->rf_period_status = $this->rf_form_getStatus($this->rf_period_list,'period');
    $this->rf_sort_status   = $this->rf_form_getStatus($this->rf_sort_list,'sort');
    $this->rf_group_status  = $this->rf_form_getStatus($this->rf_group_list,'group');
    $this->rf_check_status  = $this->rf_form_getStatus($this->rf_check_list,'check');
    if ($this->rf_period_status==2) {
    }
    if ( !empty($this->rf_sort_status==2) ) {
    }
    if ( !empty($this->rf_group_status==2) ) {
    }
    if ( !empty($this->rf_check_status==2) ) {
    }
}

function rf_form_outputHeader($emitter) {
    if ($this->rf_export_pdf) {
        $emitter->content_block('@pdf');
    }
    if ($this->rf_export_excel) {
        $emitter->content_block('@excel');
    }
 return;
}

function rf_form_processExportSubmit( $appReport, $appGlobals, $chain, $submit ) {
    if ($appChain->chn_submit[0] == '@pdf') {
        $appReport->kcr_print('p');
        exit;
    }
    if ($appChain->chn_submit[0] == '@excel') {
        $appReport->kcr_print('e');
        exit;
    }
}

} // end class


?>

