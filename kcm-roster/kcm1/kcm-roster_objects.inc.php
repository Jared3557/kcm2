<?php

// kcm-roster_objects.inc.php

define('kcmPERIODFORMAT_TALLY',1);
define('kcmPERIODFORMAT_SHORT',2);
define('kcmPERIODFORMAT_LONG',3);
define('kcmPERIODFORMAT_ONE',4); 

//======
//============
//==================
//========================
//=   School
//========================
//==================
//============
//======

Class kcm_roster_school {
public $SchoolId;         
public $NameFull;         
public $NameShort;        
public $SchoolSystem;     
public $Address;          
public $City;             
public $State;            
public $Zip;              
public $SchoolPhone;      
public $NotesContacts;    
public $NotesEquipment;   
public $NotesOther;       

function __construct() {
}

function  db_setFieldArray_school (&$fields) {
    $fields[] = 'pSc:SchoolId';
    $fields[] = 'pSc:NameFull';
    $fields[] = 'pSc:NameShort';
    $fields[] = 'pSc:SchoolSystem';
    $fields[] = 'pSc:Address';
    $fields[] = 'pSc:City';
    $fields[] = 'pSc:State';
    $fields[] = 'pSc:Zip';
    $fields[] = 'pSc:SchoolPhone';
    $fields[] = 'pSc:NotesContacts';
    $fields[] = 'pSc:NotesEquipment';
    $fields[] = 'pSc:NotesOffice';
    $fields[] = 'pSc:NotesOther';
}

function db_loadRow_school ($row) {
    $this->SchoolId         =$row['pSc:SchoolId'];
    $this->NameFull         =$row['pSc:NameFull'];
    $this->NameShort        =$row['pSc:NameShort'];
    $this->SchoolSystem     =$row['pSc:SchoolSystem'];
    $this->Address          =$row['pSc:Address'];
    $this->City             =$row['pSc:City'];
    $this->State            =$row['pSc:State'];
    $this->Zip              =$row['pSc:Zip'];
    $this->SchoolPhone      =$row['pSc:SchoolPhone'];
    $this->NotesEquipment   =$row['pSc:NotesEquipment'];
    $this->NotesOther    =$row['pSc:NotesOther'];
    $this->NotesContacts    =$row['pSc:NotesContacts'];
}

}


//======
//============
//==================
//========================
//=   Program
//========================
//==================
//============
//======

Class kcm_roster_program {
public $ProgramId;           
public $AtSchoolId;          
public $SemesterCode;        
public $ProgramName;         
public $ProgramType;         
public $SchoolYear;          
public $DayOfWeek;           
public $DateClassFirst;      
public $DateClassLast;    
public $NotesRoomInfo;       
public $NotesForRegistration;
public $NotesForCoach;       
public $NotesForSiteLeader;  
public $NotesUponArriving;   
public $NotesBeforeLeaving;  
public $NotesASPInstructions;
public $NotesParentPickup;   
public $SchoolNameUniquifier;
public $kcm2Version;   //@@@ @jpr_kcm2_change

// Computed        
private $ClassMeetDayDowDesc;
public $ClassDateForPoints;
// point categories variable is child of roster, not of program, even though it's in the program table

function __construct() {
$this->KcmPointCategories = new kcm_pointCategories();
kcm_roster_kidProgram::$kpProgram = $this;
}


function  db_setFieldArray_program(&$fields) {
    $fields[] = 'pPr:ProgramId';
    $fields[] = 'pPr:@SchoolId';
    $fields[] = 'pPr:SemesterCode';
    $fields[] = 'pPr:ProgramName';
    $fields[] = 'pPr:ProgramType';
    $fields[] = 'pPr:SchoolYear';
    $fields[] = 'pPr:DayOfWeek';
    $fields[] = 'pPr:SchoolNameUniquifier';
    $fields[] = 'pPr:DateClassFirst';
    $fields[] = 'pPr:DateClassLast';
    $fields[] = 'pPr:KcmPointCategories';
    $fields[] = 'pPr:NotesRoomInfo';
    $fields[] = 'pPr:NotesForRegistration';
    $fields[] = 'pPr:NotesForCoach';
    $fields[] = 'pPr:NotesForSiteLeader';
    $fields[] = 'pPr:NotesUponArriving'; 
    $fields[] = 'pPr:NotesBeforeLeaving'; 
    $fields[] = 'pPr:NotesASPInstructions'; 
    $fields[] = 'pPr:NotesParentPickup';
    $fields[] = 'pPr:KcmVersion';  //@@@jpr_kcm2_change
}

function db_loadRow_program($row) {
    $this->ProgramId=$row['pPr:ProgramId'];
    $this->AtSchoolId=$row['pPr:@SchoolId'];
    $this->ProgramName=$row['pPr:ProgramName'];
    $this->ProgramType=$row['pPr:ProgramType'];
    $this->SchoolYear=$row['pPr:SchoolYear'];
    $this->SemesterCode=$row['pPr:SemesterCode'];
    $this->DayOfWeek=$row['pPr:DayOfWeek'];
    $this->DateClassFirst=$row['pPr:DateClassFirst'];
    $this->DateClassLast=$row['pPr:DateClassLast'];
    $this->NotesRoomInfo=$row['pPr:NotesRoomInfo'];
    $this->NotesForRegistration=$row['pPr:NotesForRegistration'];
    $this->NotesForSiteLeader=$row['pPr:NotesForSiteLeader'];
    $this->NotesForCoach=$row['pPr:NotesForCoach'];
    $this->NotesUponArriving =$row['pPr:NotesUponArriving'];
    $this->NotesBeforeLeaving=$row['pPr:NotesBeforeLeaving'];
    $this->NotesASPInstructions=$row['pPr:NotesASPInstructions'];
    $this->NotesParentPickup=$row['pPr:NotesParentPickup'];
    $this->ClassMeetDayDowDesc=rc_getDayOfWeekNameFromNumber($this->DayOfWeek);
    $this->SchoolNameUniquifier = $row['pPr:SchoolNameUniquifier'];
    $this->kcm2Version = $row['pPr:KcmVersion'];   //@@@ jpr_kcm2_change
}

function getNameLong($pRoster) {
    return $this->ProgramName;
    if ($this->ProgramType==1) 
        return $pRoster->school->NameShort.' - '.$this->ClassMeetDayDowDesc;
    else {
        $school = $pRoster->school->NameShort;
        $dt = new DateTime($this->DateClassFirst); 
        $st = $dt->format('F j'); 
        $dt = new DateTime($this->DateClassLast); 
        $en = $dt->format('F j'); 
        if ($st==$en)
            $da = " ($st)";
        else    
            $da = " ($st - $en)";
        if ($this->ProgramType == 2) {
           return 'Camp at ' . $school . $da;
        }   
        else if ($this->ProgramType == 3) {
           return 'Tournament at ' . $school . $da;
        }   
        else {
           return 'Special Event at ' . $school . $da;
        }   
    }
        
}
function getExportName($pRoster) {
    if ($this->ProgramType==1) 
        return $pRoster->school->NameShort.'-'.$this->ClassMeetDayDowDesc;
    else {
        $school = $pRoster->school->NameShort;
        $dt = new DateTime($this->DateClassFirst); 
        $st = $dt->format('M-j'); 
        $dt = new DateTime($this->DateClassLast); 
        $en = $dt->format('M-j'); 
        if ($st==$en)
            $da = "-$st";
        else    
            $da = "-$st-$en";
        if ($this->ProgramType == 2) {
            return'Camp-' . $school . $da;
        }   
        else if ($this->ProgramType == 3) {
            return 'Tmt--' . $school . $da;
        }   
        else {
            return 'Evt-' . $school[$i] . $da;
        }   
    }
}

}

//======
//============
//==================
//========================
//=   Schedule
//========================
//==================
//============
//======

Class kcm_roster_schedule {
// note - record may not exist for current class date - in this case some info comes from the program defaults
public $isHistorical; // and also future semester (maybe should be renamed to isNotCurrentSemester)           
public $dowCode;           
public $dowDesc;           
//--- Today
public $todayDateDesc;  // for reports/pages that print the current time          
public $todayDateSql;           
public $todayDateObject;  // public so can reformat the date         
private $todayTimeSql;           
//--- Meet date/time
public $meetDateSql;  // next (or current) time class will meet         
public $meetDateDesc;           
private $meetDateObject;           
private $meetTimeStartSql;           
private $meetTimeEndSql;           
//--- entry date/time (for game/point entry) - default value is previous time class met
//---     will eq♫al meetDate during duration of a class, or before the first day class meets
public $entryDateSql; 
public $entryDateDesc;           
public $entryDateObject;       // public so can reformat the date             
private $entryTimeStartSql;           
private $entryTimeEndSql;           
public $entryDateDefaultSql; 

function __construct($db,$roster) { 
    $program = $roster->program;
    //--- Set today info
    $this->todayDateObject = new datetime(); 
    $this->todayDateSql  = $this->todayDateObject->format( "Y-m-d" );
    $this->todayTimeSql  = $this->todayDateObject->format( "H:i:s" );
    $this->todayDateDesc  = $this->todayDateObject->format( "M j(D), g:i a" );
    //--- Set DOW
    $this->dowCode = $program->DayOfWeek;
    $this->dowDesc=rc_getDayOfWeekNameFromNumber($this->dowCode);
    //-- need to determine proper date for program
    $this->setTodayDefaults($db,$roster);
//deb( '$isHistorical      ',     $this->isHistorical      );
//deb( '$dowCode           ',     $this->dowCode           );
//deb( '$dowDesc           ',     $this->dowDesc           );
//deb( '$todayDateDesc     ',     $this->todayDateDesc     );
//deb( '$todayDateSql      ',     $this->todayDateSql      );
//deb( '$todayTimeSql      ',     $this->todayTimeSql      );
//deb( '$meetDateSql       ',     $this->meetDateSql       );
//deb( '$meetDateDesc      ',     $this->meetDateDesc      );
//deb( '$meetTimeStartSql  ',     $this->meetTimeStartSql  );   
//deb( '$meetTimeEndSql    ',     $this->meetTimeEndSql    ); 
//deb( '$entryDateSql      ',     $this->entryDateSql      );
//deb( '$entryDateDesc     ',     $this->entryDateDesc     );
//deb( '$entryTimeStartSql ',     $this->entryTimeStartSql );    
//deb( '$entryTimeEndSql   ',     $this->entryTimeEndSql   );  
}    
function  setTodayDefaults($db,$roster) {
    $this->time_setFromPeriods($roster,$meetTimeStartSql,$meetTimeEndSql);  //????????????
    $this->setDates($db,$roster);
    $this->entryDateDefaultSql = $this->entryDateSql;
}
function  db_setFieldArray_schedule(&$fields) {
    $fields[] = 'cSD:ClassDate';
    $fields[] = 'cSD:StartTime';
    $fields[] = 'cSD:EndTime';
    $fields[] = 'cSD:HolidayFlag';
    $fields[] = 'cSD:Notes';
    $fields[] = 'cSD:Published?';
}
function db_loadRow_schedule($row) {
    $this->ClassDate   = $row['cSD:ClassDate'];       
    $this->StartTime   = $row['cSD:StartTime'];       
    $this->EndTime     = $row['cSD:EndTime'];     
    $this->HolidayFlag = $row['cSD:HolidayFlag'];         
    $this->Notes       = $row['cSD:Notes'];   
    $this->Published   = $row['cSD:Published?'];       
}

private function time_adjust(&$timeSql, $minutes) {
    if ( ! empty($timeSql) ) {
        $currentDate = strtotime($timeSql);
        $futureDate = $currentDate+($minutes * 60);
        $timeSql = date("H:i:s", $futureDate);
    }    
}

private function time_setFromPeriods($roster, &$timeStartSql, &$timeEndSql) {
    if ($roster->periodCount == 0) {
        $timeStartSql = '00:00:00';
        $timeEndSql = '00:00:00';
    }
    else {   
        $start = $roster->periodArray[0]->TimeStart;
        $end = $roster->periodArray[$roster->periodCount-1]->TimeEnd;
        if ( empty($start) or empty($end) ) {
            $timeStartSql = '00:00:00';
            $timeEndSql = '00:00:00';
            return;
        }    
        $timeStartSql = $start;
        $timeEndSql = $end;
        $this->time_adjust($timeStartSql,-29);
        $this->time_adjust($timeEndSql,29);
    }    
}
private function time_setFromSchedule($db, $program, $dateSql, &$timeStartSql, &$timeEndSql) {
    $fieldArray = array();
    $this->db_setFieldArray_schedule($fieldArray);
    $fieldList = "`" . implode("`,`", $fieldArray) . "`";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
    $sql[] = "FROM `ca:scheduledate`";
    $sql[] = "WHERE (`cSD:@ProgramId` ='{$program->ProgramId}') AND (`cSD:ClassDate`= '{$dateSql}')";
    $query = implode( $sql, ' '); 
    //echo  '<hr>' , $query, '<hr>';
    $result = $db->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    $row=$result->fetch_array();
    if ($row === NULL) {
        return;  // ok if no record for date
    }
    $start = $row['cSD:StartTime'];
    $end = $row['cSD:EndTime'];
    if ( empty($start) or empty($end) ) {
        return;
    }
    $timeStartSql = $start;
    $timeEndSql = $end;
    $this->time_adjust($timeStartSql,-29);
    $this->time_adjust($timeEndSql,29);
}

private function newLimitedDateObject($pProgram, $pDateSql=NULL) {
    if ($pDateSql==NULL)
        $dateObject = new datetime(); 
    else    
        $dateObject = new datetime($pDateSql); 
    $dateSql = $dateObject->format( "Y-m-d" );
    if ($dateSql < $pProgram->DateClassFirst) {
        return new datetime($pProgram->DateClassFirst);
    }    
    else if ($dateSql > $pProgram->DateClassLast) {
        return new datetime($pProgram->DateClassLast);
    }    
    return $dateObject;
}
private function dateDesc($pDateInfo) {
    if (!is_array($pDateInfo) and get_class($pDateInfo)=='DateTime') 
        $pDateInfo = getdate($pDateInfo->getTimestamp());
    if ( is_array($pDateInfo) ) 
        return $pDateInfo["month"]." ".$pDateInfo["mday"].", ".$pDateInfo["year"]." (".$pDateInfo["weekday"].")";
    return '';    
}

private function setDates($db, $roster) {
    // meet date is NEXT date the class meets (or now)
    $program = $roster->program;
    if ($this->todayDateSql > $program->DateClassLast) {
        $this->isHistorical = TRUE;
        $this->setHistoricalDates($roster);
        //$this->time_setFromSchedule($db, $program, $dateSql,$this->meetTimeStartSql,$this->meetTimeEndSql);
        return;
    }
    if ( $this->todayDateSql < $program->DateClassFirst) { // or ($program->DateClassFirst == $program->DateClassLast) ) {
        $this->setFutureDates($roster);
        $this->setMeetDate($db, $roster);  //@JPR-2018-08-10 23:30 
        $this->setEntryDate($db, $roster);  //@JPR-2018-08-10 23:30 
        return;
    }
    $this->isHistorical = FALSE;
    // All events which are (1) one day events (2) before first day and (3) after last day of events have been processed by above
    $this->setMeetDate($db, $roster);
    $this->setEntryDate($db, $roster);
}
private function setHistoricalDates($roster) {
    // today is after program end
    $program = $roster->program;
    $semDesc=kcmAsString_Semester(FALSE,$program->SchoolYear,$program->SemesterCode);
    $this->meetDateObject = $this->newLimitedDateObject($program); 
    $this->meetDateDesc = $semDesc. " (Historical Data)";  
    $this->meetDateSql  = $this->meetDateObject->format( "Y-m-d" ); 
    $this->time_setFromPeriods($roster,$this->meetTimeStartSql,$this->meetTimeEndSql);
    $this->entryDateObject = clone $this->meetDateObject; 
    $this->entryDateDesc = $this->dateDesc($this->entryDateObject);
}
private function setFutureDates($roster) {
    // today is before program start
    $program = $roster->program;
    $semDesc=kcmAsString_Semester(FALSE,$program->SchoolYear,$program->SemesterCode);
    $this->meetDateObject = $this->newLimitedDateObject($program); 
    $this->meetDateDesc = $semDesc. " (Future Semester)";  
    $this->meetDateSql  = $this->meetDateObject->format( "Y-m-d" ); 
    $this->time_setFromPeriods($roster,$this->meetTimeStartSql,$this->meetTimeEndSql);
    $this->entryDateObject = clone $this->meetDateObject; 
    $this->entryDateDesc = $this->dateDesc($this->entryDateObject);
}
//private function setStartDates($db, $roster) {
//    // today is before program start
//    $program = $roster->program;
//    $this->meetDateObject = $this->newLimitedDateObject($program); 
//    $this->meetDateDesc = $meetDateInfo["month"]." ".$meetDateInfo["mday"].", ".$meetDateInfo["year"]." (".$meetDateInfo["weekday"].")";
//    $this->meetDateSql  = $this->meetDateObject->format( "Y-m-d" );
//    $this->time_setFromPeriods($roster,$this->meetTimeStartSql,$this->meetTimeEndSql);
//    $this->time_setFromSchedule($db, $program, $this->meetDateSql, $this->meetTimeStartSql,$this->meetTimeEndSql);
//    $this->entryDateObject = clone $this->meetDateObject; 
//}

private function setMeetDate($db, $roster) {
    // today is during duration of program
    $program = $roster->program;
    $this->meetDateObject = $this->newLimitedDateObject($program); 
    $meetDateInfo = getdate($this->meetDateObject->getTimestamp());
    $this->meetDateSql  = $this->meetDateObject->format( "Y-m-d" );
    $this->time_setFromPeriods($roster, $this->meetTimeStartSql,$this->meetTimeEndSql);
    if ($meetDateInfo["wday"] == $this->dowCode) {
        // override "default" period time if schedule record exists for today
        $this->time_setFromSchedule($db, $program, $this->meetDateSql,$this->meetTimeStartSql,$this->meetTimeEndSql);
        if ($this->meetTimeEndSql < $this->todayTimeSql ) {  
            date_modify($this->meetDateObject,'+1 day');
            $meetDateInfo = getdate($this->meetDateObject->getTimestamp());
        }
    }    
    if ($program->ProgramType == 1) {  // ??? assuming all other than classes are consecutive days  
        while ($meetDateInfo["wday"] != $this->dowCode ) {
            date_modify($this->meetDateObject,'+1 day');
            $meetDateInfo = getdate($this->meetDateObject->getTimestamp());
        }        
    }    
    $this->time_setFromSchedule($db, $program, $this->meetDateSql,$this->meetTimeStartSql,$this->meetTimeEndSql);
    $this->meetDateDesc = $this->dateDesc($meetDateInfo);
    $this->meetDateSql  = $this->meetDateObject->format( "Y-m-d" );
}
private function setEntryDate($db, $roster) {
    // today is during duration of program
    // meet date is Previous date the class met (or now)
    $program = $roster->program;
    $this->entryDateObject = $this->newLimitedDateObject($program); 
    $entryDateInfo = getdate($this->entryDateObject->getTimestamp());
    $this->entryDateSql  = $this->entryDateObject->format( "Y-m-d" );
    $this->time_setFromPeriods($roster,$this->entryTimeStartSql,$this->entryTimeEndSql);
    if ($entryDateInfo["wday"] == $this->dowCode) {
        // override "default" period time if schedule record exists for today
        $this->time_setFromSchedule($db, $program, $this->entryDateSql,$this->entryTimeStartSql,$this->entryTimeEndSql);
        if ($this->entryTimeStartSql > $this->todayTimeSql ) {  // should be class end time
            date_modify($this->entryDateObject,'-1 day');
            $entryDateInfo = getdate($this->entryDateObject->getTimestamp());
        }
    }    
    while ($entryDateInfo["wday"] != $this->dowCode) {
        date_modify($this->entryDateObject,'-1 day');
        $entryDateInfo = getdate($this->entryDateObject->getTimestamp());
    }        
    $this->entryDateDesc = $this->dateDesc($entryDateInfo);
    $this->entryDateSql  = $this->entryDateObject->format( "Y-m-d" );
}
function overrideEntryDate($db, $pRoster, $pDateSql) {   // to override default entry date - date must be valid
    // meet date is Previous date the class met (or now)
    $program = $pRoster->program;
    $this->entryDateObject = $this->newLimitedDateObject($program, $pDateSql);
    $this->time_setFromPeriods($pRoster,$this->entryTimeStartSql,$this->entryTimeEndSql);
    $this->time_setFromSchedule($db, $program, $pDateSql,$this->entryTimeStartSql,$this->entryTimeEndSql);
    $this->entryDateDesc = $this->dateDesc($this->entryDateObject);
    $this->entryDateSql  = $this->entryDateObject->format( "Y-m-d" );
}

}

//======
//============
//==================
//========================
//=   Grade Groups
//========================
//==================
//============
//======

Class kcm_roster_gradeGroups {

public $ggEnabled;   // set only if there are actual groups (other than one grade per group)
public $ggIsGroupEnd ;
public $ggDescShort;

public $ggDescLong;
public $ggMinGrade;
public $ggMaxGrade;


function __construct() { 
    $this->ggIsGroupEnd  = array(14);
    $this->ggDescShort = array(14);
    $this->ggDescLong = array(14);
}    

function ggConvertFromString($pString, $pMinGrade, $pMaxGrade) {
    $this->ggMinGrade = max($pMinGrade,0);
    $this->ggMaxGrade = min($pMaxGrade,13);
    if ($pString=='') {
        $this->ggSetDefaults();
        $this->enabled = FALSE;
    }
    else {
        for ($i = 0; $i <= 13; $i++) 
            $this->ggIsGroupEnd [$i] = FALSE;
        $ar = explode('~',$pString);
        for ($i = 0; $i < count($ar); $i++) {
            $j = $ar[$i];
            if ( ($j >= 0) and ($j <= 13) )
                $this->ggIsGroupEnd [$j] = TRUE;
        }
    }    
    $this->ggRefresh();
}
function ggSetDefaults() {
    for ($i = 0; $i <= 13; $i++) 
        $this->ggIsGroupEnd[$i] = TRUE;
    $this->ggRefresh();    
    return;    
}
function ggAdd($pLow,$pHigh) {
    $pLow = max ($pLow,0);
    $pHigh = min ($pHigh,13);
    for ($i = $pLow; $i < $pHigh; $i++) 
        $this->ggIsGroupEnd [$i] = FALSE;
    $this->ggIsGroupEnd [$pHigh] = TRUE;
    if ($pLow >= 1)
        $this->ggIsGroupEnd [$pLow-1] = TRUE;
}
function ggRefresh() {
    $this->ggEnabled = FALSE;
    $this->ggIsGroupEnd [$this->ggMaxGrade] = TRUE;
    if ($this->ggMinGrade >= 1)
        $this->ggIsGroupEnd [$this->ggMinGrade - 1] = TRUE;
    $grpLow = array(13);
    $grpHigh = array(13);
    for ($i = 0; $i <= 13; $i++) {
        $grpLow[$i] = 0;
        $grpHigh[$i] = 13;
    }
    $curLow = 0;
    for ($i = 0; $i <= 13; $i++) {
        if ($this->ggIsGroupEnd [$i]) {
            for ($j = $curLow; $j <= $i; ++$j) {
                $grpLow[$j] = $curLow;
                $grpHigh[$j] = $i;
            }
            $curLow = $i+1;
        }
    }
    $s1 = array ('0','1','2','3','4','5','6','7','8','9','10','11','12','A');
    $s2 = array ('0K','1st','2nd','3rd','4th','5th','6th','7th','8th','9th','10th','11th','12th','Adult');
    for ($i = 0; $i<=13; $i++) {
       $gLow = $grpLow[$i];
       $gHigh = $grpHigh[$i];
       if ($gLow == $gHigh) {
            $srt = $s1[$gLow];
            $lng = $s2[$gHigh];
       }
       else {
            $srt = $s1[$gLow] .'-'. $s1[$gHigh];
            $lng = $s2[$gLow] .'-'. $s2[$gHigh];
       }
        $this->ggDescShort [$i] = $srt;
        $this->ggDescLong [$i] = $lng;
    }
    $this->enabled = FALSE;
    for ($i = $this->ggMinGrade; $i <= $this->ggMaxGrade; $i++) {
        if ( ! $this->ggIsGroupEnd [$i] )
            $this->enabled = TRUE;
        if ($grpLow[$i] != $grpHigh[$i] )        
             $this->ggEnabled = TRUE;
    }

}   

}

//======
//============
//==================
//========================
// Point Category
//========================
//==================
//============
//======

Class kcm_pointCategory {
public $ShortDesc; 
public $LongDesc;  
public $IsActive;  
public $IsBestResult;  
public $IsAllPeriods;  
public $OnReport;  
public $CatIndex;     
public $InUseCount;  // number of times in use by the points table   

function ptcClear($pIndex) {
    $this->ShortDesc = ''; 
    $this->LongDesc = '';  
    $this->IsActive = '';  
    $this->OnReport = '';  
    $this->CatIndex = $pIndex;     
    $this->IsBestResult = '';  
    $this->IsAllPeriods = '';  
    $this->InUseCount = 0;  
}
function getHeading() {
    return str_replace('-', '<br>', $this->ShortDesc);
}
function ptcGetDesc() {
    $i = 0;
    if ($this->IsBestResult == 'c')
        $i += 1;
    if ($this->IsAllPeriods == 'c')
        $i += 2;
    if ($i==0)    
        return $this->LongDesc;
    else if ($i==1)    
        return $this->LongDesc . ' (Best Result)';
    else if ($i==2)    
        return $this->LongDesc . ' (All Periods)';
    else if ($i==3)    
        return $this->LongDesc . ' (Best Result & All Periods)';
    else
        return $this->LongDesc . ' (??)';
}
}

//======
//============
//==================
//========================
// Point Categories
//========================
//==================
//============
//======

Class kcm_pointCategories {
public $ptcActiveItems;
public $ptcActiveCount;    
public $ptcAllItems;   
public $ptcAllCount;    

function __construct() {
    $this->ptcAllItems = array(kcmMAX_POINT_CATEGORIES);
    for ($i = 0; $i < kcmMAX_POINT_CATEGORIES; $i++) 
        $this->ptcAllItems[$i] = new kcm_pointCategory();
    $this->ptcAllCount = 0;    
    //$this->setDefaults();
}
function ptcSetActiveFilter($pIncludeActive, $pIncludeShow) {
    $this->ptcActiveCount = 0;
    $this->ptcActiveItems = array();
    for ($i = 0; $i < $this->ptcAllCount; $i++) {
        $cat = $this->ptcAllItems[$i];
        $ac = $pIncludeActive ? 'c' : '&';
        $as = $pIncludeShow ? 'c' : '&';
        if ( ($cat->IsActive===$ac) or ($cat->OnReport===$as) ) {
            $this->ptcActiveItems[] = $cat;
            ++$this->ptcActiveCount;
        }    
    }       
}

function ptcAddCategory($pActive, $pRO, $pBest, $pAll, $pShort, $pLong) {
    //$curCategory = new kcm_pointCategory();
    //$this->allItems[$this->allCount] = $curCategory;
    $curCategory = $this->ptcAllItems[$this->ptcAllCount];
    $curCategory->CatIndex = $this->ptcAllCount;
    ++$this->ptcAllCount;
    $curCategory->ShortDesc = $pShort;
    $curCategory->LongDesc  = $pLong;
    $curCategory->IsActive = $pActive;
    if ($pActive)
        $curCategory->IsActive = 'c';
    else    
        $curCategory->IsActive = 'u';
    $curCategory->OnReport = $curCategory->IsActive;
    if ($pBest)
        $curCategory->IsBestResult = 'c';
    else    
        $curCategory->IsBestResult = 'u';
    if ($pAll)
        $curCategory->IsAllPeriods = 'c';
    else    
        $curCategory->IsAllPeriods = 'u';
}
function ptcSetDefaults() {
        $this->ptcAllCount = 0;
        $this->ptcAddCategory( TRUE,  TRUE,  FALSE, FALSE, 'Gen-Pnt','General Points');
        $this->ptcAddCategory( TRUE,  TRUE,  FALSE, FALSE, 'Eval-uat'  ,'Evaluator');
        $this->ptcAddCategory( FALSE, FALSE, FALSE, FALSE, 'Puzz-zles','Solving Puzzles');
        $this->ptcAddCategory( FALSE, FALSE, FALSE, FALSE, 'Coch-Game','Games with Coaches');
        $this->ptcAddCategory( FALSE, FALSE, FALSE, FALSE, 'Othr-Pts'  ,'Special Points');
        $this->ptcAddCategory( TRUE,  TRUE,  FALSE, FALSE, 'Sprt-ship'  ,'Sportsmanship');
        $this->ptcAddCategory( FALSE, FALSE, FALSE, TRUE,  'Daly-Puzl'  ,'Daily Puzzle');
        $this->ptcAddCategory( TRUE,  FALSE, TRUE,  FALSE, 'Mate'   ,'Mate Obstacle Course');
        $this->ptcAddCategory( FALSE, FALSE, TRUE,  TRUE,  'Puz-Les' ,'Puzzle Solving Lessons');
        $this->ptcAddCategory( TRUE,  FALSE, TRUE,  TRUE,  'ABCs'   ,"ABCs");
        $this->ptcAddCategory( TRUE,  FALSE, TRUE,  TRUE,  'IYF-Mate'   ,'IYF Checkmates');
        $this->ptcAddCategory( FALSE, FALSE, TRUE,  TRUE,  'Tac-tics' ,'Tactics');
}
function ptcConvertFromString($pString) {  
    $this->ptcAllCount = 0;
    if ($pString == '') {
        $this->ptcSetDefaults();
        return;
    }
    $a = explode('~',$pString);
    $idx=-1;
    $cnt = Count($a);
    for ($i = 0; $i< $cnt - 2; $i = $i + 2) {
        $key = $a[$i];
        $val = $a[$i + 1];
        switch ($key) {
        case 'I':
            if ($val >= 100)
                $idx = $val - 100;
            else    
                $idx = $val;
            $this->ptcAllCount = max($this->ptcAllCount + 1,$idx + 1); 
            $curCategory = $this->ptcAllItems[$idx];
            $curCategory->ptcClear($val);
            //$curCategory->CatIndex = $val;
            break;
        case 'A': 
            $curCategory->IsActive = $val;
            break;
        case 'S':
            $curCategory->ShortDesc = $val;
            break;
        case 'L':
            $curCategory->LongDesc = $val;
            break;
        case 'R':
            $curCategory->OnReport = $val;
            break;
        case 'B':
            $curCategory->IsBestResult = $val;
            break;
        case 'P':
            $curCategory->IsAllPeriods = $val;
            break;
        }
    }    
    $this->ptcActiveItems = array();
    for ($i = 0; $i < $this->ptcAllCount; ++$i) {
        $this->ptcActiveItems[] = $this->ptcAllItems[$i];
    }
    $this->ptcActiveCount = $this->ptcAllCount;
}
function ptcWriteData($pDb,$pProgramId) {
        if ($this->ptcAllCount != 12) {
            exit('Point Category Write Data must be re-written to handle a change');
        }
        $s = $this->ptcConvertToString();
        $query = "UPDATE `pr:program` SET "
             . "`pPr:KcmPointCategories`='".$s
             . "' WHERE `pPr:ProgramId` = '".$pProgramId. "'";
        $result = $pDb->rc_query( $query );
        if ($result === FALSE) {
            kcm_db_CriticalError( __FILE__,__LINE__);
        }
}
function ptcConvertToString() {  
    $a = array();
    for ($i = 0; $i < $this->ptcAllCount; $i++) { 
        $curCategory = $this->ptcAllItems[$i];
        $a[] = 'I';  $a[] = $curCategory->CatIndex;  // was $i
        $a[] = 'A';  $a[] = $curCategory->IsActive;
        $a[] = 'S';  $a[] = $curCategory->ShortDesc;
        $a[] = 'L';  $a[] = $curCategory->LongDesc;
        $a[] = 'R';  $a[] = $curCategory->OnReport;
        $a[] = 'B';  $a[] = $curCategory->IsBestResult;
        $a[] = 'P';  $a[] = $curCategory->IsAllPeriods;
    }
    $s = implode('~',$a);
    return $s;
}


}

//======
//============
//==================
//========================
//=   Period
//========================
//==================
//============
//======

Class kcm_roster_period {
public $PeriodId;            
public $AtProgramId;         
public $PeriodSequenceBits;  
public $PeriodName;          
public $TimeStart;           
public $TimeEnd;             
public $EnrollmentLimit;     
public $MinGradeAccepted;    
public $MaxGradeAccepted;    
public $NotesParentHelper;   
// computed
public $TimeStartDesc;     
public $TimeEndDesc;       
public $kidThisPeriodCount; 
public $gradeGroups;
public $kidSubGroupsActive;

function __construct() {
    $this->kidThisPeriodCount = 0; 
    $this->gradeGroups = new kcm_roster_gradeGroups;
    $this->kidSubGroupsActive = false;
}

static function  db_setFieldArray_period(&$fields, $loadModeBits) {
    $fields[] = 'pPe:PeriodId';
    $fields[] = 'pPe:@ProgramId';
    $fields[] = 'pPe:PeriodSequenceBits';
    $fields[] = 'pPe:PeriodName';
    $fields[] = 'pPe:TimeStart';
    $fields[] = 'pPe:TimeEnd';
    $fields[] = 'pPe:EnrollmentLimit';
    $fields[] = 'pPe:MinGradeAccepted';
    $fields[] = 'pPe:MaxGradeAccepted';
    $fields[] = 'pPe:NotesParentHelper';
    $fields[] = 'pPe:KcmGradeGroups';
}

function db_loadRow_period($pRow) {
    $this->PeriodId           = $pRow['pPe:PeriodId'];
    $this->AtProgramId        = $pRow['pPe:@ProgramId'];
    $this->PeriodSequenceBits = $pRow['pPe:PeriodSequenceBits'];
    $this->PeriodName         = $pRow['pPe:PeriodName'];
    $this->TimeStart = $pRow['pPe:TimeStart'];
    $this->TimeEnd = $pRow['pPe:TimeEnd'];
    $this->TimeStartDesc = kcmAsString_Time12Hour($this->TimeStart);
    $this->TimeEndDesc = kcmAsString_Time12Hour($this->TimeEnd);
    $this->EnrollmentLimit = $pRow['pPe:EnrollmentLimit'];
    $this->MinGradeAccepted = $pRow['pPe:MinGradeAccepted'];
    $this->MaxGradeAccepted = $pRow['pPe:MaxGradeAccepted'];
    $this->NotesParentHelper = $pRow['pPe:NotesParentHelper'];
    $this->gradeGroups->ggConvertFromString($pRow['pPe:KcmGradeGroups'],$this->MinGradeAccepted,$this->MaxGradeAccepted);
}
function getNameLong() {
    return $this->PeriodName." Period ".$this->TimeStartDesc. " - ".$this->TimeEndDesc;
}    

}

//======
//============
//==================
//========================
//=   Family
//========================
//==================
//============
//======

Class kcm_roster_family {
public $FamilyId;      
public $EmergencyPhone;
public $EmergencyName; 
public $NotesOther;    
public $Parent1;       
public $Parent2;       

function __construct() {
$this->parent1 = NULL;
$this->parent2 = NULL;
}

static function  db_setFieldArray_family (&$fields) {
    $fields[] = 'rFa:FamilyId';
    $fields[] = 'rFa:EmergencyPhone';
    $fields[] = 'rFa:EmergencyName';
    $fields[] = 'rFa:NotesOther';
}
function db_loadRow_family ($pRow) {
    $this->FamilyId = $pRow['rFa:FamilyId'];
    $this->EmergencyPhone = $pRow['rFa:EmergencyPhone'];
    $this->EmergencyName = $pRow['rFa:EmergencyName'];
    $this->NotesOther = $pRow['rFa:NotesOther'];
}

}

//======
//============
//==================
//========================
//=   Parent
//========================
//==================
//============
//======

Class kcm_roster_parent {
public $ParentId;   
public $ContactPriority;
public $AtFamilyId;  
public $Email;       // rPU:Email  or  rPU:EmailNonLogin   //--jpr new
public $FirstName;   
public $LastName;    
public $HomePhone;   
public $WorkPhone;   
public $CellPhone;   

static function  db_setFieldArray_parent (&$fields) {
    $fields[] = 'rPU:ParentId';
    $fields[] = 'rPU:ContactPriority';
    $fields[] = 'rPU:@FamilyId';
    $fields[] = 'rPU:FirstName';
    $fields[] = 'rPU:LastName';
    $fields[] = 'rPU:HomePhone';
    $fields[] = 'rPU:CellPhone';
    $fields[] = 'rPU:WorkPhone';
    $fields[] = 'rPU:Email';
    $fields[] = 'rPU:EmailNonLogin';
}
function db_loadRow_parent ($pRow) {
    $this->ParentId = $pRow['rPU:ParentId'];
    $this->ContactPriority = $pRow['rPU:ContactPriority'];
    $this->AtFamilyId = $pRow['rPU:@FamilyId'];
    $this->FirstName = $pRow['rPU:FirstName'];
    $this->LastName = $pRow['rPU:LastName'];
    $this->HomePhone = $pRow['rPU:HomePhone'];
    $this->CellPhone = $pRow['rPU:CellPhone'];
    $this->WorkPhone = $pRow['rPU:WorkPhone'];
    $this->Email = $pRow['rPU:Email'];
    if ( ($this->Email == NULL) or ($this->Email == '')) {
        $this->Email = $pRow['rPU:EmailNonLogin'];
    }    
    if ($this->Email == NULL)
        $this->Email = '';
}

}

//======
//============
//==================
//========================
//=   Kid (Information about the kid that is the same for all periods)
//========================
//==================
//============
//======

Class kcm_roster_kidProgram { 
public $KidId;               
public $AtFamilyId;          
public $EarliestYearSemester;
public $LatestYearSemester;  
public $FirstName;           // is nickname and dup indicator if applicable
public $LastName;            
public $GradeCode;           
public $NotesForCoach;       
public $NotesForSiteLeader;  
public $KidProgramId;        
public $Teacher;             
public $TeamName;            
public $PickupCode;          
public $PhotoReleaseStatus;          
public $PickupNotes;         
public $Notes;               
public $kcm2NameLabelNote;   // @jpr@kcm2 rKPr:KcmNameLabelNote
public $KcmPrgPointValues;
public $KpUserName;
public $KpPassword;
public $KpRating;
public $KpAttempted;
public $KpSolved;
//--- objects
public $family;        // object
public $parent1;       // object
public $parent2;       // object
//--- computed 
public $GradeDesc;
public $PickupDesc;
public $kidPeriodArray;
public $IsRookie;    // based on earliest date any program was signed up for
public $Lunch;       // true if lunch feature - based on kid-period feature flag
public $PeriodComboAllBits;  // all the periods the kid is attending
public $PeriodComboSortCode; // a sequence number of the period combination for sorts: 1 < 12 < 2 < 23 < 3
public $PeriodComboEarliest; // the first period the kid is attending
public $NameConflict;        // (0..9) computed after all the kids are read, indicates another kid with similar sounding name
public $PairingLabelNumber;       //@JPR-2018-08-11 15:28 
static public $kpProgram;

function __construct() {
    $this->family = NULL;
    $this->parent1 = NULL;
    $this->parent2 = NULL;
    $this->PeriodComboAllBits = 0;
    $this->PeriodComboSortCode = 0;
    $this->PeriodComboEarliest = 0;
    $this->NameConflict = 0;
    $this->PairingLabelNumber = 0;  //@JPR-2018-08-11 15:27 
    $this->kidPeriodArray = array();
    $this->KcmPrgPointValues = array_fill ( 0 , kcmMAX_POINT_CATEGORIES-1,  0 );
}

static function  db_setFieldArray_kidProgram (&$fields) {
    $fields[] = 'rKd:KidId';
    $fields[] = 'rKd:@FamilyId';
    $fields[] = 'rKd:EarliestYearSemester';
    $fields[] = 'rKd:LatestYearSemester';
    $fields[] = 'rKd:FirstName';
    $fields[] = 'rKd:LastName';
    $fields[] = 'rKd:NickName';
    $fields[] = 'rKd:Grade';  // adjusted for GradeSchoolYear 
    $fields[] = 'rKd:GradeSchoolYear';
    $fields[] = 'rKd:NotesForCoach';
    $fields[] = 'rKd:NotesForSiteLeader';
    $fields[] = 'rKd:PhotoReleaseStatus';
    $fields[] = 'rKPr:KidProgramId';
    $fields[] = 'rKPr:@KidId';
    $fields[] = 'rKPr:Grade';
    $fields[] = 'rKPr:TeamName';
    $fields[] = 'rKPr:KcmPrgPointValues';
    $fields[] = 'rKPr:TeacherName';
    $fields[] = 'rKPr:PickupCode';
    $fields[] = 'rKPr:PickupNotes';
    $fields[] = 'rKPr:KcmPairingLabelId';    //@JPR-2018-08-11 15:29 
    $fields[] = 'rKPr:Notes';
    $fields[] = 'rKPr:KcmNameLabelNote'; // @jpr@kcm2
    $fields[] = 'rKd:KpUserName';
    $fields[] = 'rKd:KpPassword';
}

function db_loadRow_kidProgram ($pRow) {
    //--- from kid record
    $this->KidId = $pRow['rKd:KidId'];
    $this->FirstName = $pRow['rKd:NickName'];
    $this->LastName  = $pRow['rKd:LastName'];
    $this->GradeSchoolYear = $pRow['rKd:GradeSchoolYear'];
    if ($this->FirstName==NULL)
        $this->FirstName = $pRow['rKd:FirstName'];
//~~15/09    $this->GradeCode = $pRow['rKd:Grade'];
             $this->GradeCode = $pRow['rKPr:Grade']; //~~15/09
//~~15/08  $this->GradeCode = rc_getGradeNowFromGradeForAnotherYear( $this->GradeCode, $this->GradeSchoolYear ); 
    //$this->GradeCode = $pRow["rKPr:Grade"];
    //%%%%%%%%%%%%?????
    //$this->GradeCode = rc_getGradeForYearFromGradeForAnotherYear( $this->GradeCode, $this->GradeSchoolYear, self::$kpProgram->SchoolYear);
    $this->GradeDesc = kcmAsString_Grade($this->GradeCode);
    $this->AtFamilyId = $pRow['rKd:@FamilyId'];
    $this->NotesForCoach= $pRow['rKd:NotesForCoach'];
    $this->NotesForSiteLeader= $pRow['rKd:NotesForSiteLeader'];
    $this->EarliestYearSemester = $pRow['rKd:EarliestYearSemester'];
    $this->LatestYearSemester = $pRow['rKd:LatestYearSemester'];
    $this->IsRookie = ($this->EarliestYearSemester == $this->LatestYearSemester);  //????
    $this->PhotoReleaseStatus = $pRow['rKd:PhotoReleaseStatus'];
    //--- from Kid Program record
    $this->KidProgramId = $pRow['rKPr:KidProgramId'];
    $programGrade = $pRow['rKPr:Grade'];
    $this->TeamName= $pRow['rKPr:TeamName'];
    $this->KcmPrgPointValues = kcm_convertStringToPointValues($pRow['rKPr:KcmPrgPointValues']);
    $this->Teacher= $pRow['rKPr:TeacherName'];
    $this->PickupCode= $pRow['rKPr:PickupCode'];
    $this->PickupDesc = kcmAsString_PickupShort ($this->PickupCode);  
    $this->PickupNotes = $pRow['rKPr:PickupNotes'];  
    $this->Notes = $pRow['rKPr:Notes'];  
    $this->KpUserName = $pRow['rKd:KpUserName'];
    $this->KpPassword = $pRow['rKd:KpPassword']; 
    $this->PairingLabelNumber = $pRow['rKPr:KcmPairingLabelId'];  //@JPR-2018-08-11 15:36 
    $this->kcm2NameLabelNote = $pRow['rKPr:KcmNameLabelNote']; // @jpr@kcm2
}

}

//======
//============
//==================
//========================
//=   Kid Period
//========================
//==================
//============
//======

Class kcm_roster_kidPeriod { 
// Table = ro:kid
public $kidProgram; 
public $kidPeriod; 
public $KidPeriodId;       
public $AtKidProgramId;    
// Table = ro:kid_period
public $AtPeriodId;          
public $ParentHelperStatus;  
public $ParentHelperName;  
public $KcmClassSubGroup;    
public $KcmPerPointValues; 
public $KcmGamePoints;  
public $kcm2GeneralPoints;  //@@ @jpr_kcm2_change
public $totalCatPoints; // Computed - sum of kid-period and kid-program point arrays
public $totalAllPoints; // Computed - cat points and game points
public $sortIndex;  // Computed (kidArray index after it's sorted)
public $InvoiceNo;  // used by roster.inc for waitlist info
public $InvoiceDate;  // used by roster.inc for waitlist info

//--- computed after reading all the kids
public $PeriodBitsWithFeatures;  // pPe:PeriodSequenceBits  1=1st  period; 2=2nd   period; 4=3rd  period; 8=4th period 4096=Feature (Also used for sorting)
public $PeriodBitsSinglePeriod;   // the single period - NO feature flags (from period table)
public $PeriodName;
public $period;   // computed - period object

function __construct() {
    $this->Parent  = null;
    $this->KidPoints  = NULL;
    $this->period  = NULL;
    $this->totalPointsAll   = 0;
    $this->totalPointsClass = 0;
    $this->totalPointsGames = 0;
    $this->totalCatPoints = 0;
    $this->KcmGamePoints = 0;
    $this->totalAllPoints = 0;
    $this->totalCatPoints = 0;
    $this->resultTourns = array(3);
    $this->kcm2GeneralPoints = 0 ;  //@jpr_kcm2_change
    $this->KcmPerPointValues = array(kcmMAX_POINT_CATEGORIES);
    for ( $i = 0; $i < 3; ++$i) {
        $this->resultTourns[$i] = new kcm_roster_gameTotals($i);
    }    
}

function __destruct() {
    if ($this->Parent!=NULL) 
       unset($parent);
}

static function  db_setFieldArray_kidPeriod (&$fields) {
    $fields[] = 'rKPe:KidPeriodId';
    $fields[] = 'rKPe:@KidProgramId';
    $fields[] = 'rKPe:@PeriodId';
    $fields[] = 'rKPe:ParentHelperStatus';
    $fields[] = 'rKPe:ParentHelperName';
    $fields[] = 'rKPe:KcmClassSubGroup';
    $fields[] = 'rKPe:KcmPerPointValues';
    $fields[] = 'rKPe:KcmGamePoints';
    $fields[] = 'rKPe:KcmGeneralPoints';  //@jpr_kcm2_change
    //%%%%%%%%%%%%%%%%%%%%%%??????????????????????????
    //$fields[] = 'pPe:PeriodSequenceBits';
}

function db_loadRow_kidPeriod ($pRow, $pKidProgram) {
    $this->kidProgram = $pKidProgram;
    $this->KidPeriodId = $pRow['rKPe:KidPeriodId'];
    $this->AtKidProgramId = $pRow['rKPe:@KidProgramId'];
    $this->AtPeriodId = $pRow['rKPe:@PeriodId'];
    $this->ParentHelperStatus= $pRow['rKPe:ParentHelperStatus'];
    $this->ParentHelperName = $pRow['rKPe:ParentHelperName'];
    //$this->KcmClassSubGroup = $pRow['rKPe:KcmClassSubGroup'];  //@jp2 18-03
    $this->KcmClassSubGroup = $pKidProgram->kcm2NameLabelNote;   //@jp2 18-03
    $this->KcmGamePoints = $pRow['rKPe:KcmGamePoints'];
    $this->KcmPerPointValues = kcm_convertStringToPointValues($pRow['rKPe:KcmPerPointValues']);
    $this->totalCatPoints = array_sum($this->KcmPerPointValues);
    $this->totalAllPoints = $this->totalCatPoints + $this->KcmGamePoints;
    $this->kcm2GeneralPoints = $pRow['rKPe:KcmGeneralPoints'];  //@@@ jpr_kcm2_change
    //%%%%%%%%%%%%%%%%%%%%%%??????????????????????????
    //    $this->PeriodBitsWithFeatures = $pRow['pPe:PeriodSequenceBits'];
    //%%%%%%%%%%%%%%%%%%%%%%??????????????????????????
    //if ($programGrade!=$this->GradeCode) {
        // if (pPr:SchoolYear = rc_getDefaultSchoolYear())
        //$s ='Grade Conflict '.$this->FirstName.'  '.$this->LastName.' pg='.$programGrade.' kg='.$this->GradeCode;     
        //exit($s);
    //}
    $this->PeriodBitsSinglePeriod = ($this->PeriodBitsWithFeatures & 4095);
    $this->PeriodComboAllBits = $this->PeriodBitsSinglePeriod; // will be combined later
    $this->PeriodComboEarliest = $this->PeriodBitsSinglePeriod; // will be combined later
    $this->NameConfict = 0;
    $this->Lunch = FALSE;
}
function getNameLong() {
    return $this->FirstName . ' ' . $this->LastName;
}
function getNameShort() {
    if ($this->NameConfict==0)
        return $this->FirstName;
    else   
        return $this->FirstName . ' ' . $this->LastName;
}
function getGradeGroupName() {
    //%%%%%%%%%%%%%%%%%%%%%%??????????????????????????
    $gr  = $this->kidProgram->GradeCode;
    if ($gr>=0 and $gr<=13) {
        $gg = $this->period->gradeGroups;
        return $gg->ggDescShort[$gr];
    }
    else
        return '';
    return;
    if ($gc>=0 and $gc<=6) {
       $gc = max($gc, $this->period->MinGradeAccepted);
       $gc = min($gc, $this->period->MaxGradeAccepted);
        return $this->period->GG_GradeSrtDesc[$gc];
    }    
    else {
        return '';   
    }   
}
function getGradeGroupCode() {
   if ($this->kidProgram->GradeCode>=0 and $this->kidProgram->GradeCode<=6)
       return $this->period->gradeGroups->ggDescShort[$this->kidProgram->GradeCode];
   else
       return 99;   
}
function getKidClassPoints() {
    if ($this->KidPoints==NULL)     
        return '';
    else    
        return $this->KidPoints->totalClassPoints;
}

}

//======
//============
//==================
//========================
//=   Kid Game Totals
//========================
//==================
//============
//======

class kcm_roster_gameTotals { // only load functions (no updates)
public $GameTotalId;  // 0 = none
public $AtProgramId;
public $AtPeriodId;
public $AtKidPeriodId;
public $GameTypeIndex;
public $totWon;
public $totLost;
public $totDraw;
public $Percent;
public $PlayerStatus;

function __construct($gameTypeIndex) {
    $this->clear($gameTypeIndex);
}

function clear($pGameTypeIndex) {
    $this->GameTotalId = 0;
    $this->AtProgramId = 0;
    $this->AtPeriodId = 0;
    $this->AtKidPeriodId = 0;
    $this->GameTypeIndex = $pGameTypeIndex;
    $this->totWon = 0;
    $this->totLost = 0;
    $this->totDraw = 0;
    $this->Percent = '';
    $this->PlayerStatus = '';
}

function  db_setFieldArray_gameTotals(&$fields) {
}

function db_readRow_gameTotals ($row) {
    $this->GameTotalId = $row['gpGT:GameTotalId'];
    $this->AtProgramId  = $row['gpGT:@ProgramId'];
    $this->AtPeriodId  = $row['gpGT:@PeriodId'];
    $this->AtKidPeriodId  = $row['gpGT:@KidPeriodId'];
    $this->GameTypeIndex  = $row['gpGT:GameTypeIndex'];
    $this->PlayerStatus = $row['gpGT:PlayerStatus'];
    $this->totWon = $row['gpGT:GamesWon'];
    $this->totLost = $row['gpGT:GamesLost'];
    $this->totDraw = $row['gpGT:GamesDraw'];
    $this->Percent = kcm_gamePercent($this->totWon , $this->totLost , $this->totDraw);
}

}

Class kcm_roster_kid {
// This is really a kid-period record, but also contains a link to the kid-program and kid records
public $prg;  // kid-program object (info - also kid, parent, family, etc)
public $per;  // kid-period object
public $trn;  // tournament object
public $ttt;  // tournament totals object
static $roster;

function __construct($pKidProgram) {
    $this->prg = $pKidProgram;
    $this->per = new kcm_roster_kidPeriod;
    $pKidProgram->kidPeriodArray[] = $this->per;
    
    $this->trn = NULL;
    $this->ttt = array(3);;
    for ( $i = 0; $i < 3; ++$i) {
        $this->ttt[$i] = new kcm_roster_gameTotals($i);
    }    
}

function getPeriodDesc($pPeriodFormat) { 
    $periodActive = array ();
    $periodDesc = array ();
    $curPeriodIndex = -1;
    if ($this->per->PeriodBitsSinglePeriod == 1) {
        $curPeriodIndex = 0;  // 1st period
    }    
    else if ($this->per->PeriodBitsSinglePeriod == 2) {
        $curPeriodIndex = 1;  // 2nd period
    }    
    else if ($this->per->PeriodBitsSinglePeriod == 4) {
        $curPeriodIndex = 2;  // 3rd period
    }    
    else {   
        $curPeriodIndex = 3;  // 4th or over
    }   
    $progType = kcm_roster_kid::$roster->program->ProgramType;    
    if ($progType==1) { 
          $periodDesc[] = '1st';
          $periodDesc[] = '2nd';
          $periodDesc[] = '3rd';
          $periodDesc[] = '?>3';
          $periodWord  = ' Period';
          $da = '';
    }
    else if ($progType==2) { 
        if ( ($pPeriodFormat == kcmPERIODFORMAT_TALLY) 
             or ($pPeriodFormat == kcmPERIODFORMAT_SHORT)) {
            $periodDesc[] = 'M';
            $periodDesc[] = 'A';
            $periodDesc[] = '?>2';
            $periodDesc[] = '?>3';
            $da = 'Full';
        }
        else {
            $periodDesc[] = 'Morning';
            $periodDesc[] = 'Afternoon';
            $periodDesc[] = '?>2';
            $periodDesc[] = '?>3';
            $da = 'Full Day';
        }    
        $periodWord  = '';
    }
    else {
          //%%%%%%%%%%%%%%%%%%%%%%??????????????????? should get from $this->roster->period->PeriodName 
          $periodDesc[] = '1st';
          $periodDesc[] = '2nd';
          $periodDesc[] = '3rd';
          $periodDesc[] = '?>3';
          $da = '';
          $periodWord  = '';
    }
    if ($pPeriodFormat == kcmPERIODFORMAT_ONE) {
        return $periodDesc[$curPeriodIndex] . $periodWord;
    }
    $periodActive[] = (($this->prg->PeriodComboAllBits & 1) == 1);
    $periodActive[] = (($this->prg->PeriodComboAllBits & 2) == 2);
    $periodActive[] = (($this->prg->PeriodComboAllBits & 4) == 4);
    $activeCount = 0;
    for ($i = 0; $i < count($periodActive); ++$i) {
        if ( $periodActive[$i]) {
            ++$activeCount;
        }
    }    
    if ($pPeriodFormat == kcmPERIODFORMAT_TALLY) {
        $s = $periodDesc[$curPeriodIndex];
        if (count($this->prg->kidPeriodArray)>1) {
            $s = $s . '+';
        }
        return $s;    
    }
    $desc = '';
    $sep = '';
    for ($i = 0; $i < count($periodActive); ++$i) {
        if ($periodActive[$i]) {
            $desc .=  $sep . $periodDesc[$i]; 
            $sep = '-';
        }   
    }     
    if ( ($pPeriodFormat == kcmPERIODFORMAT_LONG) and ($periodWord!='') ) {
        $desc = $desc . $periodWord;
        if ($activeCount > 1) {
            $desc .= 's';
        }
    }
    if ($desc =='Morning-Afternoon')
        $desc ='Full Day'; 
    return $desc;
}

}


?>