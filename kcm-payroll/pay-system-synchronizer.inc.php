<?php

// pay-system-synchronizer.inc.php

const PAY_SYNCERR_PROGRAMDATE = 1;

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  payData_synchronizer
//@@@@@@@@@@
//@@@@@

class payData_synchronizer {
   
private $param_dateStart=NULL;
private $param_dateEnd=NULL;
private $param_employeeId=0;

private $sync_jobTsk_read_result;
private $sync_jobTsk_read_row;
private $sync_jobTsk_read_event;

//--- Synchronization groups 
private $sync_group_isTimer   = array();
private $sync_group_isCounter = array();
private $sync_group_frequency = array();
private $sync_group_lastWhen  = array();  // actual datetime done
private $sync_group_lastDone  = array();  // seconds ago
private $sync_group_desc      = array();
private $sync_group_active    = array();
private $sync_group_count     = array();

function __construct( $appGlobals ) {
    $this->sync_group_create( $appGlobals ,'JobEmeAdd',  60, 1, 1,  'Job Employees Added');
    $this->sync_group_create( $appGlobals ,'CalEvtGrp',  60, 1, 0,  'Calender Events Group');
    $this->sync_group_create( $appGlobals ,'CalEvtAdd',  60, 0, 1,  'Calender Events Added');
    $this->sync_group_create( $appGlobals ,'CalEvtUpd',  60, 0, 1,  'Calender Events Updated');
    $this->sync_group_create( $appGlobals ,'CalTskAdd',  60, 1, 1,  'Calender Tasks Added');
    $this->sync_group_create( $appGlobals ,'CalTskUpd',  60, 1, 1,  'Calender Tasks Updated');
    $this->sync_group_create( $appGlobals ,'CalTskDel',  60, 1, 1,  'Calender Tasks Deleted');
    $this->sync_group_create( $appGlobals ,'JobEvtVer',  60, 1, 1,  'Job Events Verified');
    $this->sync_group_create( $appGlobals ,'JobEvtErr',  60, 0, 1,  'Job Events Errors');
    $this->sync_group_create( $appGlobals ,'JobTskGrp',  60, 1, 0,  'Job Tasks Updated');
    $this->sync_group_create( $appGlobals ,'JobTskSch',  60, 0, 1,  'Job Tasks Schedules Updated');
    $this->sync_group_create( $appGlobals ,'JobTskOvr',  60, 0, 1,  'Job Tasks Overrides Updated');
    $this->sync_group_create( $appGlobals ,'Salaried',   60, 1, 1,  'Salary Transactions');
    $query = "SELECT `jSys:StatusKey`,`jSys:StatusValue` FROM `job:systemstatus` WHERE `jSys:StatusKey` LIKE 'job_synchronizeWhen_%'";
    $result = $appGlobals->gb_sql->sql_performQuery($query);
    while ($row=$result->fetch_array()) {
        $groupKey = substr($row['jSys:StatusKey'],20);
        $groupVal = $row['jSys:StatusValue'];
        if ( isset($this->sync_group_frequency[$groupKey])) {
            $whenSynchronized = $groupVal;
            $this->sync_group_lastWhen[$groupKey] = $whenSynchronized;
            if ( empty($whenSynchronized) ) {
                $this->sync_group_lastDone[$groupKey] = 9999;  // a long time ago
                $this->sync_group_active[$groupKey] = TRUE;
            }
            else {
                $whenNow = new DateTime( rc_getNow() );
                $whenSynced = new DateTime( $whenSynchronized );
                $diff = $whenNow->getTimestamp() - $whenSynced->getTimestamp(); 
                $this->sync_group_lastDone[$groupKey] = $this->sync_group_frequency[$groupKey] - $diff;
                $this->sync_group_active[$groupKey] = ($this->sync_group_lastDone[$groupKey] <= 0 );
            }  
        }            
    }
}

function sync_group_create( $appGlobals , $key , $frequency, $isTimer, $isCounter ,  $desc ) {
    //$this->sync_group_key[$key]     = $key;
    $this->sync_group_isTimer[$key]   = $isTimer;
    $this->sync_group_isCounter[$key] = $isCounter;
    $this->sync_group_frequency[$key] = $frequency;
    $this->sync_group_lastDone[$key]  = 9999;  // seconds ago
    $this->sync_group_desc[$key]      = $desc;
    $this->sync_group_count[$key]     = 0;
    $this->sync_group_active[$key]    = TRUE;
}

function sync_group_update( $appGlobals , $groupKey) {
    if ( empty( $this->param_employeeId) ) {
        payData_status_set( $appGlobals , 'job_synchronizeWhen_' . $groupKey , rc_getNow() );
    }    
}

private function sync_group_increment( $key ) {
    if ( !isset($this->sync_group_count[$key])) {
        //?????????????????????????????????????????
    }
	++$this->sync_group_count[$key];
}    
    

function sync_group_isActive($key) {
    $b =  isset($this->sync_group_active[$key]) ? $this->sync_group_active[$key] : FALSE;
    return ($b);
}

private function sync_group_saveStatusItem( $s , $color = '#ddffdd') {
    return '<span style="display:inline-block; font-size:8pt; margin: 0pt 1pt 0pt 1pt; padding:2pt 2pt 2pt 2pt;  font-weight:normal; border-left: 1px solid gray; background-color:'.$color.'">'
       . $s . '</span>';
}

private function sync_group_saveStatusToDisplay( $appGlobals ) {
    if ( RC_LIVE) {
        return;
    }
    $message = '';
    foreach ( $this->sync_group_desc as $key => $desc ) {
        //if ( $this->sync_group_active[$key]) {
        $active = $this->sync_group_active[$key];
        $count = $this->sync_group_count[$key];
        $freq = $this->sync_group_frequency[$key];
        $since = $this->sync_group_lastDone[$key];
        $count = $this->sync_group_count[$key];
        $isTimer = $this->sync_group_isTimer[$key];
        $isCounter = $this->sync_group_isCounter[$key];
        
        $s = '';
        if ( !$active) {
            $color = '#999999';
            if ( $isTimer) {
                $s .= $desc . $this->sync_group_saveStatusItem(' Skipped','#999999');
                $s .= $this->sync_group_saveStatusItem( ' Do in '. $since,'#999999');
            }    
        }
        else {
            $color = '#ddffdd';
            if ( $isCounter) {
                $color = ($count>=1) ? '#ddffdd' : '#cccccc';
                $s .= $desc . $this->sync_group_saveStatusItem(' Count = ' . $count,  $color);
            }
        }    
        if ( $s != '') {
            $spanGroup = '<span style="display:inline-block; font-size:8pt; font-weight:bold; padding:2pt 5pt 2pt 5pt; margin: 1pt 5pt 1pt 5pt; border: 1px solid gray; background-color:'.$color.'">';
            $message .= $spanGroup . $s . '</span>';   
        }    
    }
    $appGlobals->gb_synchronize_message .= '<div style="line-height:8pt;font-size:8pt; padding: 1pt 2pt 1pt 2pt;background-color:#ffeeee;">'
                  . 'Not visible in Live System ' . $message . '</div>';
}    
    
function sync_synchronize( $appGlobals , $eventDateStart , $eventDateEnd , $employeeId=0 ) {
    $this->param_dateStart  = $eventDateStart;
    $this->param_dateEnd    = $eventDateEnd;
    $this->param_employeeId = $employeeId;
    if ( $this->sync_group_isActive('JobEmeAdd')) {
       $this->sync_employee_table  ( $appGlobals );
    }    
    if ( $this->sync_group_isActive('CalEvtGrp')) {
       $this->sync_calEvt_process ( $appGlobals );
    }    
    if ( $this->sync_group_isActive('CalTskAdd')) {
        $this->sync_calTsk_process_add ( $appGlobals );
    }    
    if ( $this->sync_group_isActive('CalTskUpd')) {
        $this->sync_calTsk_process_update ( $appGlobals );
    }    
    if ( $this->sync_group_isActive('CalTskDel')) {
        $this->sync_calTsk_process_delete ( $appGlobals );
    }    
    if ( $this->sync_group_isActive('JobEvtVer')) {
        $this->sync_jobEvt_process  ( $appGlobals ); 
    }    
    if ( $this->sync_group_isActive('JobTskGrp')) {
        $this->sync_jobTsk_process  ( $appGlobals );  
    }    
    if ( $this->sync_group_isActive('Salaried')) {
        $this->sync_salaried_employees ( $appGlobals );  
    }    
    
    $this->sync_group_saveStatusToDisplay( $appGlobals );
    
}

//======================================
//=   Synchronize Salaries
function sync_salaried_employees( $appGlobals) {
    $payPeriod = $appGlobals->gb_period_current;;
    $sql = array();
    // COUNT(CASE WHEN rsp_ind = 0 then 1 ELSE NULL END) 
    $sql[] = "SELECT `jEmp:@StaffId`, `jEmp:PayRateSalary`, COUNT(CASE WHEN `jTsk:OriginCode` = '". RC_JOB_ORIGIN_SALARY ."' then 1 ELSE NULL END) AS `SalaryCount`";
    $sql[] = "FROM `job:employee`";
    $sql[] = "LEFT JOIN `job:task` ON (`jTsk:@StaffId` = `jEmp:@StaffId`) AND (`jTsk:OriginCode`='".RC_JOB_ORIGIN_SALARY."') ";
    $sql[] = "    AND (`jTsk:OriginCode` = '". RC_JOB_ORIGIN_SALARY ."')";
    $sql[] = "    AND (`jTsk:@PayPeriodId` = '{$payPeriod->prd_payPeriodId}')";
    $sql[] = "WHERE `jEmp:HiddenStatus`= '0'"; // can't do this due to encryption AND (`jEmp:PayRateSalary` >= '.01')";
    $sql[] = "GROUP BY `jEmp:@StaffId`";
    $query = implode( $sql, ' '); 
    // ???????????????????   NEED TO DETECT SALARY RECORDS FOR NON-SALARIED EMPLOYEES ?????????????????????
    // ???????????????????   ALSO MAKE SURE NO DUPLICATE SALARY RECORDS FOR ONE EMPLOYEE ?????????????????????
    $result = $appGlobals->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
    while ($row=$result->fetch_array()) {
        $salaryCount = $row['SalaryCount'];
        $salary = payData_dollarAmountDecrypt( $row['jEmp:@StaffId'] , $row['jEmp:PayRateSalary'] );
        if ( empty($salaryCount) and ($salary>=0.01) ) {
            $this->sync_salaried_add( $appGlobals, $row);
        }
    }
    $this->sync_group_update( $appGlobals , 'Salaried' ); 
}

function sync_salaried_add( $appGlobals, $row) {
    $payPeriod = $appGlobals->gb_period_current;;
    $taskGroup = new payData_task_group;
    $taskFinal = $taskGroup->tskGrp_createNewFinalItem();
    $taskFinal->tskItem_staffId                     = $row['jEmp:@StaffId'];  
    $taskFinal->tskItem_originCode                  = RC_JOB_ORIGIN_SALARY;  
    $taskFinal->tskItem_setPeriod_byCurrentPeriod( $appGlobals);
    $taskFinal->tskItem_scheduleStatus              = RC_JOB_SCHEDSTATUS_NOT_EVENT;
    $taskFinal->tskItem_payStatus                   = PAY_PAYSTATUS_APPROVED;
    $taskFinal->tskItem_job_date                    = $payPeriod->prd_dateStart;  
    $taskFinal->tskItem_job_time_start              = NULL; 
    $taskFinal->tskItem_job_time_end                = NULL;   
    $taskFinal->tskItem_job_rateCode                = PAY_RATEMETHOD_SALARY;
    $taskFinal->tskItem_job_location                = 'Salary';
    $taskFinal->tskItem_job_notes                   = '';
    $taskFinal->tskItem_override_timeMinutes        = 0;
    $taskFinal->tskItem_override_timeMethod         = 0;
    $taskFinal->tskItem_sync_errorBitFlags          = 0;
    $taskFinal->tskItem_sync_eventModWhen           = rc_getNow();   //???? 
    $taskFinal->tskItem_sync_scheduleDateId         = 0;         
    $taskFinal->tskItem_sync_scheduleDateModWhen    = rc_getNow();  //????
    $taskFinal->tskItem_sync_scheduleStaffModWhen   = rc_getNow();  //????
    $taskFinal->tskItem_sync_taskModWhen            = rc_getNow();  //????
    $taskFinal->tskItem_rec_hiddenStatus            = 0;
    $employee = payData_factory::payFactory_get_employeeItem( $appGlobals , $taskFinal->tskItem_staffId);
    $taskGroup->tskGrp_save_syncRecord( $appGlobals ); 
}

//======================================
//=   Synchronize Employee Table

function sync_employee_table( $appGlobals) {
    // Need to Detect:
    //     When the raccoon staff table has a new staff member
    //     In future may need to detect changes to staff table hiddenStatus 
    // Processing:
    //     Add the new raccoon staff member to the payroll employee table
    // Need to change Raccoon Options:
    //     1. None - have payroll synchronize every-so-often to check for added records in staff table 
    //     2. Have raccoon signal payroll when a change to the staff table (via include function?)
    //     3. Have raccoon automatically add the employee to payroll (via include function)
    //     4. Add pay rate to the raccoon employee update (visible only to PR manager)
    //     5. Combine staff and pay employee table (this may make synchronization less efficient, unless if added sync flag column for payroll in staff table)
    
    $sql = array();
    $sql[] = "SELECT `sSt:StaffId`,`sSt:HiddenStatus`,`jEmp:@StaffId`";
    $sql[] = "FROM `st:staff`";
    $sql[] = "LEFT JOIN `job:employee` ON `jEmp:@StaffId`=`sSt:StaffId`";
    //$sql[] = "WHERE ( (`jEmp:@StaffId` IS NULL) AND (`sSt:HiddenStatus`='0') ) 
    //    OR ( (`jEmp:@StaffId` IS NOT NULL) AND (`sSt:HiddenStatus`<>`jEmp:HiddenStatus`) )";
    $sql[] = "WHERE (`jEmp:@StaffId` IS NULL)  
        OR ( (`jEmp:@StaffId` IS NOT NULL) AND (`sSt:HiddenStatus`<>`jEmp:HiddenStatus`) )";
    $query = implode( $sql, ' '); 
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    
    $convert_employee_added = 0;
    while ( $row=$result->fetch_array() ) {
        $this->sync_employee_record( $appGlobals , $row ); 
    }
    
    //$this->syncLib_lastWhen_update( $appGlobals , $frequencyKey );
    if ( (!RC_LIVE) AND ($this->sync_group_count['JobEmeAdd'] > 70) ) {
       // $this->syncLib_msg_init( 'EmeFak' , 'Initializing Fake Employee Data', '*'  );
        $this->sync_employee_test_createFakeRates( $appGlobals ); 
    }
    $this->sync_group_update( $appGlobals , 'JobEmeAdd' ); 
}

function sync_employee_record( $appGlobals , $row ) {
    // very simple import - just add new raccoon staff to payroll employee table
    $this->sync_group_increment( 'JobEmeAdd' );
    $staffId = $row['sSt:StaffId'];
    $empId = $row['jEmp:@StaffId'];
    $hiddenStatus = $row['sSt:HiddenStatus'];
    $query2 = "INSERT INTO `job:employee` (`jEmp:@StaffId`,`jEmp:HiddenStatus`) VALUES ('{$staffId}','{$hiddenStatus}')";
    if (empty($empId)) {
        $query2 = "INSERT INTO `job:employee` (`jEmp:@StaffId`,`jEmp:HiddenStatus`) VALUES ('{$staffId}','{$hiddenStatus}')";
    }
    else {    
        $query2 = "UPDATE `job:employee` SET `jEmp:HiddenStatus` = '{$hiddenStatus}' WHERE `jEmp:@StaffId` = '{$staffId}'";
    }    
    $result2 = $appGlobals->gb_sql->sql_performQuery( $query2 );
    //not sure if also need to detect changes to staff table hidden record 
}

function sync_employee_test_mangleRate($amount) {
        if (RC_LIVE) {
            return $amount; // don't mangle on live system
        }
        if ( $amount>400) {
            $factor = 4;
        }
        else if ( $amount>100) {
             $factor = 3;        
        }
        else if ( $amount>50) {
             $factor = 2.5;        
        }
        else if ( $amount>20) {
             $factor = 2;        
        }
        else if ( $amount>10) {
             $factor = 1.8;        
        }
        else {
             $factor = 1.5;  
        }              
        $low = $amount / $factor;
        $high = $amount * $factor;
        return mt_rand($low, $high );
}

function sync_employee_test_createFakeRates( $appGlobals ) {
    $this->db = $appGlobals->gb_db;
    //$this->rateClass_batch = new payData_rateClass_batch;
    //$this->rateClass_batch->rateClass_getBatch( $appGlobals );
    $this->staff_batch = new payData_employee_batch;
    $this->staff_batch->epyBat_read_all( $appGlobals );
    foreach ($this->staff_batch->epyBat_empoyeeArray as $staffId => $staff) {
        $staff->emp_rateField = $this->sync_employee_test_mangleRate(20);
        $staff->emp_rateAdmin = $this->sync_employee_test_mangleRate(10); 
        $staff->emp_saveRecord( $appGlobals );
    }
}

private function sync_calEvt_process( $appGlobals ) { 
    // cal:scheduledate records are never deleted
    // except when purging old data
    
    $sql = array();
    $sql[] = "SELECT `ca:scheduledate`.*, `job:event`.*";
    $sql[] = ",`pSc:SchoolId`,`pSc:NameShort`,`pPr:ProgramId`,`pPr:@SchoolId`,`pPr:SchoolNameUniquifier`,`pPr:DateClassFirst`,`pPr:DateClassLast`";
    $sql[] = "FROM `ca:scheduledate`";
    $sql[] = "LEFT JOIN `job:event` ON (`jEvt:Sync@CalSchedDateId`= `cSD:ScheduleDateId`)";
    $sql[] = "JOIN `pr:program` ON (`pPr:ProgramId` = `cSD:@ProgramId`)";
    $sql[] = "JOIN `pr:school` ON (`pSc:SchoolId` = `pPr:@SchoolId`)"; 
    
    $prefix = '';
    $modRangeSql = '';
    if ( isset($this->sync_group_lastWhen['CalEvtGrp']) and (!empty($this->sync_group_lastWhen['CalEvtGrp'])) ) {
        $modRangeSql .= " (`cSD:ModWhen` > '{$this->sync_group_lastWhen['CalEvtGrp']}')";
        $prefix = ' AND ';
    } 
    if ( !empty($this->param_dateStart)) {
        $modRangeSql .= $prefix ."(`cSD:ClassDate` >= '{$this->param_dateStart}')"; 
        $prefix = ' AND ';
    }
    if ( !empty($eventDateEnd)) {
        $modRangeSql .= $prefix . "(`cSD:ClassDate` <= '{$eventDateEnd}')"; 
        $prefix = ' AND ';
    }
    if ( $modRangeSql != '') {
        $modRangeSql = '( ' . $modRangeSql . ' ) AND ';  
    }    
    
    $sql[] = "WHERE {$modRangeSql} ( (`jEvt:Sync@CalSchedDateModWhen` IS NULL) OR (`jEvt:Sync@CalSchedDateModWhen`<>`cSD:ModWhen`) )";
    $query = implode( $sql , ' ' ); 
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    
    $currentScheduleId = 0;
    while ( $row = $result->fetch_array() ) {
        $eventId = $row['jEvt:EventId'];
        if ( $eventId === NULL ) {
            $eventItem = new payData_event_item;
            $this->sync_calEvt_record_add( $appGlobals , $eventItem , $row);
            $eventItem->evt_save_record( $appGlobals );
        }
        else {
            $eventItem = new payData_event_item;
            $eventItem->evt_process_row( $row );
            $this->sync_calEvt_record_update( $appGlobals , $eventItem , $row);
        }
    }
    $this->sync_group_update( $appGlobals , 'CalEvtGrp' ); 
}

private function sync_calEvt_record_copy( $appGlobals , $eventItem , $row ) {
    $eventItem->evt_programId           = $row['cSD:@ProgramId'];  
    $eventItem->evt_sync_errorBitFlags  = $this->syncLib_event_getDateRangeErrorCode( $row['cSD:ClassDate'], $row );  
    $eventItem->evt_sync_scheduleDateId = $row['cSD:ScheduleDateId']; 
    $eventItem->evt_eventDate           = $row['cSD:ClassDate'];  
    $eventItem->evt_startTime           = $row['cSD:StartTime'];  
    $eventItem->evt_endTime             = $row['cSD:EndTime'];  
    $eventItem->evt_holidayFlag         = $row['cSD:HolidayFlag'];  
    $eventItem->evt_location            = krnLib_getSchoolName($row);;  
    $eventItem->evt_publishedFlag       = $row['cSD:Published?'];  
    $eventItem->evt_hiddenStatus        = $row['cSD:HiddenStatus'];  
    $eventItem->evt_notesSchedule       = $row['cSD:Notes'];  
    $eventItem->evt_notesIncidents      = $row['cSD:NotesIncidents'];  
    $eventItem->evt_notesActivities     = $row['cSD:NotesActivities'];  
    $eventItem->evt_SMSubmissionStatus  = $row['cSD:SMSubmissionStatus'];  
    $eventItem->evt_modByStaffId        = $row['cSD:ModBy@StaffId'];  
    $eventItem->evt_modWhen             = $row['cSD:ModWhen'];  
    $eventItem->evt_sync_scheduleDateModWhen = $row['cSD:ModWhen'];  
}

private function sync_calEvt_record_add( $appGlobals , $eventItem , $row ) {
    $this->sync_group_increment( 'CalEvtAdd' );
    $eventItem->evt_jobEventId = 0;  
    $this->sync_calEvt_record_copy( $appGlobals , $eventItem , $row );
    $eventItem->evt_save_record( $appGlobals );
}

private function sync_calEvt_record_update( $appGlobals , $eventItem , $row ) {
    $this->sync_group_increment( 'CalEvtUpd' );
    $eventItem->evt_jobEventId = $row['jEvt:EventId'];  
    $this->sync_calEvt_record_copy( $appGlobals , $eventItem , $row );
    $eventItem->evt_save_record( $appGlobals );
}

private function sync_calTsk_process_add( $appGlobals ) { 
    
    // process tasks that are in raccoon calendar staff table but not in payroll task table
    $sql = array();
    $sql[] = "SELECT `jTsk:JobTaskId`,`job:event`.*,`ca:scheduledate_staff`.*";
    $sql[] = ",`pSc:SchoolId`,`pSc:NameShort`,`pPr:ProgramId`,`pPr:@SchoolId`,`pPr:SchoolNameUniquifier`,`pPr:ProgramType`,`pPr:DateClassFirst`,`pPr:DateClassLast`";
    $sql[] = "FROM `job:event`";
    $sql[] = "JOIN `ca:scheduledate_staff` ON (`cSS:@ScheduleDateId` = `jEvt:Sync@CalSchedDateId`)";
    if ( !empty($this->param_employeeId) ) {
         $sql[] = "   AND (`cSS:@StaffId` = `jTsk:@StaffId`)";  // make sync fast if only needed for one employee   //????? this->param_employeeId
    }
    $sql[] = "LEFT JOIN `job:task` ON (`jTsk:@EventId`= `jEvt:EventId`) AND (`jTsk:@StaffId`=`cSS:@StaffId`)";
    $sql[] = "JOIN `ca:scheduledate` ON `cSD:ScheduleDateId` = `cSS:@ScheduleDateId`";
    $sql[] = "JOIN `pr:program` ON (`pPr:ProgramId` = `jEvt:@ProgramId`)";
    $sql[] = "JOIN `pr:school` ON (`pSc:SchoolId` = `pPr:@SchoolId`)"; 
    $sql[] = "WHERE (`jTsk:JobTaskId` IS NULL)"; 
    // if ( !empty($this->sync_group_lastWhen['CalTskAdd']) ) {
    //      $sql[] = "AND (`cSS:ModWhen` > '{$this->sync_group_lastWhen['CalTskAdd']}')"; 
    // }
    if ( !empty($this->param_dateStart) ) {
         $sql[] = "AND (`cSD:ClassDate` >= '{$this->param_dateStart}')"; 
    }
     if ( !empty($this->param_dateEnd) ) {
         $sql[] = "AND (`cSD:ClassDate` <= '{$this->param_dateEnd}')"; 
    }
    $sql[] = "ORDER BY `jEvt:EventId`"; 
    $query = implode( $sql, ' '); 
    
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    
    $currentEventId = 0;
    
    while ( $row=$result->fetch_array() ) {
        $staffId   = $row['cSS:@StaffId'];
        $employee = payData_factory::payFactory_get_employeeItem( $appGlobals , $staffId );
        $eventId = $row['jEvt:EventId'];
        if ( $eventId != $currentEventId ) {
            $currentEventId = $eventId;
            $eventItem = new payData_event_item;
            $eventItem->evt_process_row( $row );
            $published = ($eventItem->evt_publishedFlag==1);
            $holiday = ($eventItem->evt_holidayFlag==1);
        }
        if ( $published and (!$holiday) ) {
            $key = $eventId . '-' . $staffId;
            $taskGroup = new payData_task_group;
            $taskFinal = $taskGroup->tskGrp_get_finalItem();
            $this->sync_calTsk_record_add( $appGlobals , $taskGroup , $row , $eventItem);
            $taskGroup->tskGrp_save_syncRecord( $appGlobals ); 
        }
    }  
    
    $this->sync_group_update( $appGlobals , 'CalTskAdd' ); 
}

private function sync_calTsk_process_update( $appGlobals ) { 
    // find jobs that are on both the calendar and jobitem table that have different syncdates
    // and then copy most of the calander record to the task record
    
    return;
    //??????????????%%%%%%%%%%%%%%%%?????????????????????????
    //??????????????%%%%%%%%%%%%%%%%?????????????????????????
    //??????????????%%%%%%%%%%%%%%%%?????????????????????????
    
    
    //????????????????%%%%%%%%%%%%%???????????????????  PROBLEM: event changes effect all records in group
    $sql = array();
    $sql[] = "SELECT `job:task`.*,`job:event`.*,`ca:scheduledate_staff`.*";  // `job:event`.*,
    $sql[] = ",`pSc:SchoolId`,`pSc:NameShort`,`pPr:ProgramId`,`pPr:@SchoolId`,`pPr:SchoolNameUniquifier`,`pPr:ProgramType`,`pPr:DateClassFirst`,`pPr:DateClassLast`";
    $sql[] = "FROM `job:task`";
    $sql[] = "JOIN `ca:scheduledate_staff` ON (`cSS:@ScheduleDateId` = `jTsk:Sync@CalSchedDateId`) AND (`cSS:@StaffId` = `jTsk:@StaffId`)";
    $sql[] = "JOIN `job:event` ON `jEvt:EventId`= `jTsk:@EventId`";
    $sql[] = "JOIN `pr:program` ON (`pPr:ProgramId` = `jEvt:@ProgramId`)";
    $sql[] = "JOIN `pr:school` ON (`pSc:SchoolId` = `pPr:@SchoolId`)"; 
    $sql[] = "WHERE (`jTsk:OriginCode`='".RC_JOB_ORIGIN_SCHEDULE."')"; 
    if ( !empty($this->param_employeeId)) {
         $sql[] = "   AND (`jTsk:@StaffId` = '{$this->param_employeeId}')";  
    }
    if ( !empty($this->param_dateStart) ) {
         $sql[] = "AND (`jTsk:JobDate` >= '{$this->param_dateStart}')"; 
    }
    if ( !empty($this->param_dateEnd) ) {
         $sql[] = "AND (`jTsk:JobDate` <= '{$this->param_dateEnd}')"; 
    }
    //   if ( !empty($this->sync_group_lastWhen['CalTskUpd']) ) {     // more efficient but makes harder to test
    //        $sql[] = "AND (`cSS:ModWhen` > '{$this->sync_group_lastWhen['CalTskUpd']}')"; 
    //   }
    $sql[] = "AND ( (`cSS:ModWhen`<>`jTsk:Sync@CalSchedStaffModWhen`) OR (`jEvt:ModWhen`<>`jTsk:Sync@EventModWhen`) )";
    $sql[] = "ORDER BY `jEvt:Sync@CalSchedDateId`";  //????? or jEvt:EventId
    $query = implode( $sql, ' '); 
    $result = $appGlobals->gb_sql->sql_performQuery( $query);
    $curEventId = 0;
    while ( $row=$result->fetch_array() ) {
        $newEventId = $row['jTsk:@EventId'];
        if ( $newEventId != $curEventId) {
            $curEventId = $newEventId;
            $eventItem = new payData_event_item;
            $eventItem->evt_process_row( $row );
        }
        $taskGroup = new payData_task_group;
        $taskItem = $taskGroup->tskGrp_createNewFinalItem();
        //??????????????????? task item vs task group
        $this->sync_calTsk_record_update( $appGlobals , $taskItem , $row , $eventItem);
        $appGlobals->gb_load_global_employees($taskItem->tskItem_staffId);
        $taskGroup->tskGrp_save_syncRecord( $appGlobals ); 
    }
    
    $this->sync_group_update( $appGlobals , 'CalTskUpd' ); 
}

private function sync_calTsk_process_delete( $appGlobals ) { 
    
    return;
    //??????????????%%%%%%%%%%%%%%%%?????????????????????????
    //??????????????%%%%%%%%%%%%%%%%?????????????????????????
    //??????????????%%%%%%%%%%%%%%%%?????????????????????????
    
     // find jobs that have been deleted from the calender
    $sql = array();
    $sql[] = "SELECT `cSS:@ScheduleDateId`,`cSS:ScheduleDateStaffId`,`job:task`.*";
    $sql[] = "FROM `job:task` "; 
    $sql[] = "LEFT JOIN `ca:scheduledate_staff` ON (`cSS:@ScheduleDateId` = `jTsk:Sync@CalSchedDateId`)  AND (`cSS:@StaffId` = `jTsk:@StaffId`)";  
    $sql[] = "WHERE (`cSS:ScheduleDateStaffId` IS NULL) ";  
    if ( !empty($this->sync_group_lastWhen['CalTskDel']) ) {
        $sql[] = "AND (`jTsk:ModWhen` > '{$this->sync_group_lastWhen['CalTskDel']}')"; 
    }
    if ( !empty($eventDateStart) ) {
        $sql[] = "AND (`jTsk:JobDate` >= '{$this->param_dateStart}')"; 
    }
    if ( !empty($eventDateEnd) ) {
        $sql[] = "AND (`jTsk:JobDate` <= '{$this->param_dateEnd}')"; 
    }
    $sql[] = "ORDER BY `cSS:@ScheduleDateId`";   
    $query = implode( $sql , ' ' ); 
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    
    if ( !empty($result->nem_rows)) {
        while ( $row=$result->fetch_array() ) {
            $this->sync_calTsk_record_delete( $appGlobals, $row );
        }
    }
    
    $this->sync_group_update( $appGlobals , 'CalTskDel' ); 
}

function sync_calTsk_record_copy( $appGlobals, $taskGroup , $row , $eventItem ) {
    $roleCode    = $row['cSS:RoleType'];
    $programType = $row['pPr:ProgramType'];
    $timeResult  = pay_calcStaffTimeFromEventTime($eventItem->evt_startTime, $eventItem->evt_endTime, $roleCode , $programType);
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $taskFinal->tskItem_staffId                     = $row['cSS:@StaffId'];        
    $taskFinal->tskItem_setPeriod_byDate ( $appGlobals);
    $taskFinal->tskItem_event_eventId               = $eventItem->evt_jobEventId;    
    $taskFinal->tskItem_event_programId             = $row['pPr:ProgramId'];   
    $taskFinal->tskItem_event_roleCode              = $roleCode;
    $taskFinal->tskItem_event_prepTime              = $timeResult['prepTime'];
    $taskFinal->tskItem_event_hadEquipment          = $row['cSS:HadEquipment'];
    $taskFinal->tskItem_event_hadBadge              = $row['cSS:HadBadge'];
    $taskFinal->tskItem_job_date                    = $eventItem->evt_eventDate;  
    $taskFinal->tskItem_event_timeArrive            = $timeResult['start'];
    $taskFinal->tskItem_event_timeDepart            = $timeResult['end'];   
    $taskFinal->tskItem_job_time_start              = $timeResult['start']; 
    $taskFinal->tskItem_job_time_end                = $timeResult['end'];   
    $taskFinal->tskItem_job_rateCode                = PAY_RATEMETHOD_FIELD;
    $taskFinal->tskItem_job_location                = $eventItem->evt_location;
    //$role = $timeResult['role'];
    //$note = $row['cSS:Notes'];
    $taskFinal->tskItem_job_notes                   = $row['cSS:Notes']; // ( ($role!='') and ($note!='') ) ? $role . ', ' . $note : $role . $note;
    $taskFinal->tskItem_override_timeMinutes        = 0;
    $taskFinal->tskItem_override_timeMethod         = PAY_TIMEOVERRIDE_FALSE;
    $taskFinal->tskItem_sync_errorBitFlags          = $this->syncLib_event_getDateRangeErrorCode( $eventItem->evt_eventDate, $row );
    $taskFinal->tskItem_sync_eventModWhen           = $row['jEvt:ModWhen'];   
    $taskFinal->tskItem_sync_scheduleDateId         = $eventItem->evt_sync_scheduleDateId;         
    $taskFinal->tskItem_sync_scheduleDateModWhen    = $eventItem->evt_sync_scheduleDateModWhen; 
    $taskFinal->tskItem_sync_scheduleStaffModWhen   = $row['cSS:ModWhen'];
    $taskFinal->tskItem_sync_taskModWhen            = $row['cSS:ModWhen']; 
    $taskFinal->tskItem_rec_hiddenStatus            = $row['cSS:HiddenStatus'];
    $taskFinal->tskItem_rec_modByStaffId            = $row['cSS:ModBy@StaffId'];
    $taskFinal->tskItem_rec_modWhen                 = $row['cSS:ModWhen'];
    $taskFinal->tskItem_scheduleStatus              = $eventItem->evt_publishedFlag;
    $taskFinal->tskItem_payStatus                   = PAY_PAYSTATUS_APPROVED;
    $taskGroup->tskGrp_save_syncRecord( $appGlobals ); 
}

function sync_calTsk_record_add( $appGlobals, $taskGroup , $row , $eventItem ) {
    $this->sync_group_increment( 'CalTskAdd' );
    // convert cal:scheduledatestaff record to job:task record (source = schedule)
    
    $taskFinal = $taskGroup->tskGrp_createNewFinalItem();
    $taskFinal->tskItem_taskId = 0;
    $taskFinal->tskItem_group_taskId = 0;
    $taskFinal->tskItem_originCode                  = RC_JOB_ORIGIN_SCHEDULE;
    $taskFinal->tskItem_travel_prevJobItemId        = NULL;
    $taskFinal->tskItem_travel_nextJobItemId        = NULL;
    $taskFinal->tskItem_sync_taskModWhen            = NULL;    
    $taskFinal->tskItem_sync_employeeModWhen        = NULL;  //????????????????????????????
    $taskFinal->tskItem_job_atendanceCode           = RC_JOB_ATTEND_PRESENT;
    $taskFinal->tskItem_override_rateAmount         = 0;
    $taskFinal->tskItem_override_explanation        = '';
    $taskFinal->tskItem_scheduleStatus              = $eventItem->evt_publishedFlag;
    $taskFinal->tskItem_payStatus                   = PAY_PAYSTATUS_APPROVED;  
    $taskFinal->tskItem_pay_final_minutes           = 0;
    $taskFinal->tskItem_pay_final_rate              = 0;
    $taskFinal->tskItem_pay_final_amount            = 0;
    $this->sync_calTsk_record_copy( $appGlobals, $taskGroup , $row , $eventItem );
    $taskFinal->tskItem_setPeriod_byDate( $appGlobals );
}

function sync_calTsk_record_update( $appGlobals , $taskItem , $row , $eventItem ) {
     // update cal:scheduledatestaff record to job:task record (source = schedule)
    $this->sync_group_increment( 'CalTskUpd' );
    $taskItem->tskItem_process_row( $appGlobals , $row);
    $this->sync_calTsk_record_copy( $appGlobals, $taskItem , $row , $eventItem );
}

function sync_calTsk_record_delete( $appGlobals, $row ) {
    $this->sync_group_increment( 'CalTskDel' );
    $taskId = $row['jTsk:JobTaskId'];
    $query = "DELETE FROM `job:task` WHERE `jTsk:JobTaskId` = '".$taskId."'";  // if overridden, will be caught later
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
}

function sync_jobEvt_process( $appGlobals  ) {
    // Note: maybe should be done by raccoon scheduling ?????
    // Need to Detect:
    //     Change in program date range
    //     Change in program information
    //     Deleted or hidden program (only hidden is possible, deleted program not possible)
    //     Change in location (school) name (very infrequent, but can happen, especially concerning uniquifier) 
    // Frequency:
    //     Not frequent - mostly programs are changed - but after adding a new school may need to schedule something there immediately
    // Processing:
    //     Copy the new raccoon staff member to the payroll employee table
    // Need to change Raccoon Options:
    //     1. None - have payroll synchronize every-so-often to check for changed records in program table 
    //     2. Have raccoon signal payroll when a change to the program table (via include function?)
    //     3. Have raccoon do the synchronization immediately (via include function?)
    // Detect:
    //     When the program is modified
    // Processing:
    //     for each modified program
    //         (1) refresh each event record for that program record 
    //         (2) refresh event modWhen date so that task records will be updated 
    //         (3) Associated task records wiil be refreshed later in task table sync   
    
    $sql = array();
    $sql[] = "SELECT `job:event`.*";  // `job:event`.*,
    $sql[] = ",`pPr:ProgramId`,`pPr:ProgramType`,`pPr:DateClassFirst`,`pPr:DateClassLast`";
    $sql[] = "FROM `job:event`";
    $sql[] = "LEFT JOIN `pr:program` ON `pPr:ProgramId` = `jEvt:@ProgramId`";    
    $sql[] = "WHERE (`pPr:DateClassFirst` >= '{$this->param_dateStart}') AND (`pPr:DateClassLast` <= '{$this->param_dateEnd}')"; 
    if ( !empty($lastSyncWhen) ) {
       $sql[] = "AND ( (`pPr:ModWhen` >= '{$this->sync_group_lastWhen['JobEvtVer']}')OR (`pSc:ModWhen`  >= '{$this->sync_group_lastWhen['JobEvtVer']}') )";  // 
    }
    $query = implode( $sql, ' '); 
    $result = $appGlobals->gb_sql->sql_performQuery( $query);
    
    while ( $row = $result->fetch_array() ) {
        $this->sync_jobEvt_record( $appGlobals , $row );
    }
    
    $this->sync_group_update( $appGlobals , 'JobEvtVer' ); 
}

function sync_jobEvt_record( $appGlobals , $row ) {
    // need to detect events which are now invalid due to program date changes, etc
    $event = new payData_event_item;
    $event->evt_process_row($row);
    $this->sync_group_increment( 'JobEvtVer' );
    $progDateFirst = $row['pPr:DateClassFirst'];
    $progDateLast = $row['pPr:DateClassLast'];
    $curErrorCode    = $row['jEvt:SyncErrorBitFlags'];
    $newErrorCode    = ( ($event->evt_eventDate < $progDateFirst) or ($event->evt_eventDate > $progDateLast)) ? PAY_SYNCERR_PROGRAMDATE : 0;
    if ( $curErrorCode != $newErrorCode ) {
        $this->sync_group_increment( 'JobEvtErr' );
        $eventId = $row['jEvt:EventId'];;
        $query = "UPDATE `job:event` SET `jEvt:SyncErrorBitFlags` = '{$newErrorCode}' WHERE `jEvt:EventId` = '{$eventId}'";
        $result = $appGlobals->gb_sql->sql_performQuery($query);
    }
}

//======================================
function sync_jobTsk_process( $appGlobals ) {  
    // Note:
    //     The employee table doesn't often get changed
    //     The schedule gets changed, sometimes at the last minute
    //        (someone calls in sick, and task may get changed by scheduling and/or employee can override to say was sick)
    //     There may be override records, but no harm updating the schedule record
    // Detect:
    //     An employee (pay rate) has been modified  
    //     An event has been modified (times, deleted, etc)
    // Processing:
    //    Handle employee changes (need to consider changes to changes to salaried employee status, or zero wages)
    //    Handle event changes
    
    //??????????????????????????%%%%%%%%%%%%%%%%%%?????????????????????????????????????????????????
    return;
    
    $taskGroup = $this->sync_jobTsk_read_getFirst( $appGlobals );
    while ($taskGroup!== FALSE) {
        //???????????????? process group
        if ( $taskGroup->tskItem_originCode == RC_JOB_ORIGIN_SCHEDULE) {
            $schedule = $taskGroup;
            $this->sync_jobTsk_record_schedule( $appGlobals , $schedule );
            // if schedule is only record in group things are a lot simpler (there are no contradicting overrides)
        }
        else {
            $schedule = NULL;
            foreach($taskGroup->tskGrp_jobItem_array as $item) {
                if ( $item->tskItem_originCode == RC_JOB_ORIGIN_SCHEDULE) {
                     $schedule = $item;
                }
            }
            if ( $schedule != NULL) {
                 $this->sync_jobTsk_record_schedule( $appGlobals , $schedule );
            }
            $this->sync_jobTsk_record_override( $appGlobals , $taskGroup, $schedule );
        }
        $taskGroup = $this->sync_jobTsk_read_getNext( $appGlobals );
    }
    
    $this->sync_group_update( $appGlobals , 'JobTskGrp' ); 
    
}

function sync_jobTsk_record_schedule( $appGlobals , $taskItem ) {
    $this->sync_group_increment( 'JobTskSch' );
    // an existing task schedule item needs updating - either the event or employee pay rates have changed
    $event = $this->sync_jobTsk_read_event;
    if ( $event->evt_modWhen > $taskItem->tskItem_sync_eventModWhen) {
        // event has changed
        $taskItem->tskItem_sync_errorBitFlags =  $event->evt_sync_errorBitFlags; 
//        if ( $event->evt_sync_errorBitFlags != $schedule->tskItem_sync_errorBitFlags ) {
//        } 
//        if ( $event->evt_eventDate != $schedule->tskItem_job_date ) {
//        } 
//        if ( $event->evt_startTime != $schedule->tskItem_job_time_start ) {
//        } 
//        if ( $event->evt_endTime != $schedule->tskItem_job_time_end ) {
//        } 
//        if ( $event->evt_publishedFlag != $schedule-> ) {
//        } 
//        if ( $event-> != $schedule-> ) {
//        } 
//        if ( $event-> != $schedule-> ) {
//        } 
    }
    payAssign::start();
    $taskItem->tskItem_sync_taskModWhen = rc_getNow(); 
 //   payAssign::set($schedule->tskItem_job_location , $event->evt_location );
 //   payAssign::set($schedule->tskItem_job_date , $event->evt_eventDate );
 //   payAssign::set($schedule->tskItem_job_time_start , $event->evt_startTime ); 
 //   payAssign::set($schedule->tskItem_job_time_end , $event->evt_endTime ); 
 //   payAssign::set($schedule-> , $event->evt_jobEventId );  
 //   payAssign::set($schedule-> , $event->evt_sync_scheduleDateId ); 
 //   payAssign::set($schedule-> , $event->evt_sync_scheduleDateModWhen );
 //   payAssign::set($schedule-> , $event->evt_programId );
 //   payAssign::set($schedule-> , $event->evt_holidayFlag );  
 //   payAssign::set($schedule-> , $event->evt_sync_errorBitFlags );  
 //   payAssign::set($schedule-> , $event->evt_notesIncidents );  
 //   payAssign::set($schedule-> , $event->evt_notesActivities );  
 //   payAssign::set($schedule-> , $event->evt_SMSubmissionStatus ); 
 //   payAssign::set($schedule-> , $event->evt_publishedFlag );  
 //   payAssign::set($schedule-> , $event->evt_sync_errorBitFlags );
 //   payAssign::set($schedule-> , $event->evt_hiddenStatus );
 //   payAssign::set($schedule->tskItem_payPeriodId ,  );
 //   payAssign::set($schedule->tskItem_override_timeMethod ,  );
 //   payAssign::set($schedule->tskItem_override_timeMinutes ,  );
 //   payAssign::set($schedule-> ,  );
    
 //   $taskItem->tskItem_  refresh_andSave( $appGlobals );
    
}

function sync_jobTsk_record_override(  $appGlobals , $taskGroup, $schedule ) {
    // an existing task override item needs updating - either the associated task-schedule item or employee pay rates have changed
    // the event has changed for an existing task schedule item
    $this->sync_group_increment( 'JobTskOvr' );
    
    if ( ($schedule!==NULL) and ($taskGroup->tskItem_originCode != RC_JOB_ORIGIN_SCHEDULE) ) {
        if ( ($taskGroup->tskItem_sync_taskModWhen===NULL) or ( $taskGroup->tskItem_sync_taskModWhen < $schedule->tskItem_rec_modWhen))  {
            // could be major (or minor) problem if schedule changed after override was done
            // also schedule errors must propogate to job record 
           // payAssign::start();
           // tskItem_sync_errorBitFlags
            if ( $taskGroup->tskItem_job_date != $schedule->tskItem_job_date) {
                // error - schedule date changed after override done
            }
            $schedStart = draff_timeIncrement($schedule->tskItem_job_time_start,-15);  // a little leeway
            $schedEnd   = draff_timeIncrement($schedule->tskItem_job_time_end,15); // a little leeway
            $schedMinutes   = draff_timeMinutesDif($schedule->tskItem_job_time_start, $schedule->tskItem_job_time_end);
            $jobMinutes   = draff_timeMinutesDif($taskGroup->tskItem_job_time_start, $taskGroup->tskItem_job_time_end);
            $timeDif = abs($schedMinutes - $jobMinutes );
            if ( ($taskGroup->tskItem_job_time_start < $schedStart) or ($taskGroup->tskItem_job_time_end < $schedEnd) or ($timeDif > 30) ) {
                // time contradiction
            }
            if ( $taskGroup->tskItem_job_atendanceCode != $schedule->tskItem_job_atendanceCode) {
                // attendance code contradiction
            }
            $taskGroup->tskItem_sync_taskModWhen = rc_getNow(); 
        }    
             
           // payAssign::set();
    }     
    
    
    $employee = $appGlobals->gb_employeeArray[$taskGroup->tskItem_staffId];
    if ( ($taskGroup->tskItem_sync_employeeModWhen===NULL) or ( $taskGroup->tskItem_sync_employeeModWhen < $employee->emp_rec_modWhen))  {
        // refresh pay rates
    }
}

//========================================

private function sync_jobTsk_read_getFirst( $appGlobals ) {
    
    $this->sync_jobTsk_read_event = new payData_event_item;
    $sql = array();
    $sql[] = "SELECT *";
    $sql[] = "FROM `job:task`";
    $sql[] = "JOIN `job:employee`   ON `jEmp:@StaffId` = `jTsk:@StaffId`";
    $sql[] = "JOIN `st:staff`       ON (`sSt:StaffId`   = `jTsk:@StaffId`)";  // only needed for debugging ?
    $sql[] = "JOIN `job:payperiod`  ON (`jPer:PayPeriodId` = `jTsk:@PayPeriodId`)";  // used in process_row
    $sql[] = "LEFT JOIN `job:event` ON (`jEvt:EventId` = `jTsk:@EventId`)  AND ( `jEvt:ModWhen` > `jTsk:Sync@EventModWhen`)";
   
    $sql[] = "WHERE `jTsk:Group@JobTaskId` IN (";  // start of IN [ ..subquery fills in this part .. ]
    //-------------- subquery start ------------------------
    $sql[] = "(SELECT t1.`jTsk:Group@JobTaskId`";
    $sql[] = "FROM `job:task` as t1";
    $sql[] = "LEFT JOIN `job:task` as t2 ON (t1.`jTsk:Group@JobTaskId` = t2.`jTsk:Group@JobTaskId`) ";
    $sql[] = "LEFT JOIN `job:event` ON (`jEvt:EventId` = t2.`jTsk:@EventId`)  AND ( `jEvt:ModWhen` > t2.`jTsk:Sync@EventModWhen`)";
    $sql[] = "LEFT JOIN `job:employee` ON (`jEmp:@StaffId` = t1.`jTsk:@StaffId`)";
    // (`jEvt:Published?`='1') AND 
    $sql[] = "WHERE (t1.`jTsk:@PayPeriodId` = '{$appGlobals->gb_period_current;->prd_payPeriodId}')";
    if ( !empty($this->param_employeeId) ) {
        $sql[] = "   AND ( t1.`jTsk:@StaffId` = '{$this->param_employeeId}' )";
    }
    $sql[] = " AND (";
    $sql[] = "      (`jEmp:ModWhen` > t1.`jTsk:ModWhen`)";
    $sql[] = "   OR ( (t1.`jTsk:OriginCode` = '".RC_JOB_ORIGIN_SCHEDULE."') AND ( t1.`jTsk:Sync@EventModWhen` < `jEvt:ModWhen`) )";
    $sql[] = "   OR (t1.`jTsk:Sync@TaskModWhen` > t2.`jTsk:ModWhen`)";
    $sql[] = ")";
    $sql[] = "GROUP BY `jTsk:Group@JobTaskId` )";
    //-------------- subquery end ------------------------
    $sql[] = ")";  // end of IN [ ..subquery fills in this part .. ]
    $sql[] = "ORDER BY `sSt:FirstName`, `sSt:LastName`, `jTsk:Group@JobTaskId`, `jTsk:OriginCode` DESC";  //  `jTsk:OriginCode` DESC", `jTsk:JobTaskId`
    $query = implode( $sql, ' '); 
    $this->sync_jobTsk_read_result = $appGlobals->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
    $this->sync_jobTsk_read_row=$this->sync_jobTsk_read_result->fetch_array();
    return $this->sync_jobTsk_read_getNext( $appGlobals );
}
    
private function sync_jobTsk_read_addGroupRow( $appGlobals ,$curJobGroup,$row) {
    $payItem = new payData_task_item;
    $payItem->tskItem_process_row( $appGlobals ,$row);
    $curJobGroup->tskGrp_jobItem_array[] = $payItem;
}

private function sync_jobTsk_read_getNext( $appGlobals ) {
    if ( $this->sync_jobTsk_read_row == FALSE) {
        return FALSE;
    }
    // create global employee record (needed for pay calculation)
    $employeeId = $this->sync_jobTsk_read_row['jTsk:@StaffId'];        
    if ( !isset($appGlobals->gb_employeeArray[$employeeId]) ) {
        $employee = new dbRecord_payEmployee;
        $employee->emp_processRow($appGlobals ,$this->sync_jobTsk_read_row);
        $appGlobals->gb_employeeArray[$employeeId] = $employee;
    }    
    // create taskGroup with first item
    $curJobGroupId = $this->sync_jobTsk_read_row['jTsk:Group@JobTaskId'];
    $curJobGroup = new payData_task_group;
    $curJobGroup->tskItem_process_row( $appGlobals , $this->sync_jobTsk_read_row);
    if ( $this->sync_jobTsk_read_row['jEvt:EventId'] != $this->sync_jobTsk_read_event->evt_jobEventId) {
        $this->sync_jobTsk_read_event->evt_process_row($this->sync_jobTsk_read_row);
    }    
    while ( $this->sync_jobTsk_read_row=$this->sync_jobTsk_read_result->fetch_array()) {
        if ( $this->sync_jobTsk_read_row['jTsk:Group@JobTaskId'] != $curJobGroupId) {
            break;  
        }
        $this->sync_jobTsk_read_addGroupRow( $appGlobals ,$curJobGroup,$this->sync_jobTsk_read_row);
    }
    //$changed = $curJobGroup->tskItem_refresh_calculatedFields( $appGlobals );
    //if ( $changed) {
    //    //?????????????%%%%%%%%%%%??????????????????  change to group save
    //    $curJobGroup->tskGrp_save_finalRecord( $appGlobals , TRUE );
    //}
    return $curJobGroup;
    // $this->sync_jobTsk_read_row is starting row of next taskGroup or FALSE 
}

//======================================




private function syncLib_event_getDateRangeErrorCode( $eventDate, $row ) {
    $error = ( ( $eventDate < $row['pPr:DateClassFirst'] ) or ( $eventDate > $row['pPr:DateClassLast'] ) ); 
    return ($error) ? 1 : 0;    
}

} // end synchronizer class

?>

