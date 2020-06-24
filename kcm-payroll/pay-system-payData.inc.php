<?php

// pay-system-payData.inc.php


// Standard variable names
// $taskItem - any job item, same record is used in scheduling and payroll
// $taskGroup - group of job items
// $taskFinal - the final record in the job group (the other records in the group have been overridden)
// $jobOnly  - Final and only item -

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  payData_task_item
//@@@@@@@@@@
//@@@@@

class payData_task_item  extends draff_database_record {

const DB_TABLE_NAME  = '';
const DB_TABLE_INDEX = '';
const DB_MOD_WHEN    = '';
const DB_MOD_WHO     = '';
const DB_SELECT_FIELDS  = array();

public $tskItem_taskId = 0;
public $tskItem_group_taskId = 0;
public $tskItem_staffId      = 0;
public $tskItem_originCode      = 0;
public $tskItem_payPeriodId = NULL;  // need to set pay period either by reading from db or setting based on open period or event date
public $tskItem_scheduleStatus = RC_JOB_SCHEDSTATUS_NOT_EVENT;
public $tskItem_payStatus      = PAY_PAYSTATUS_UNAPPROVED;

public $tskItem_event_eventId   = NULL;
public $tskItem_event_programId = NULL;
public $tskItem_event_timeArrive = NULL;   // scheduled time to arrive (this time will be on the schedule)
public $tskItem_event_timeDepart = NULL;   // scheduled time to depart (this time will be on the schedule)
public $tskItem_event_prepTime = 0;        // scheduled (approved) standard prep time
public $tskItem_event_roleCode  = 0;     // schedule role (SM, HC, etc)
public $tskItem_event_hadEquipment     = 0;
public $tskItem_event_hadBadge         = 0;

public $tskItem_job_date       = NULL;
public $tskItem_job_time_start = NULL;   // actual time arrived
public $tskItem_job_time_end   = NULL;   // actual time departed
public $tskItem_job_rateCode   = 0;
public $tskItem_job_atendanceCode  = RC_JOB_ATTEND_PRESENT;
public $tskItem_job_location   = '';
public $tskItem_job_notes      = '';

public $tskItem_override_timeMethod  = 0;
public $tskItem_override_timeMinutes = 0;
public $tskItem_override_rateAmount  = 0;
public $tskItem_override_explanation = '';

public $tskItem_pay_final_minutes = '0';
public $tskItem_pay_final_rate    = 0;
public $tskItem_pay_final_amount  = 0;
public $tskItem_travel_prevJobItemId = NULL;
public $tskItem_travel_nextJobItemId = NULL;

public $tskItem_sync_errorBitFlags   = 0;
public $tskItem_sync_employeeModWhen = NULL;
public $tskItem_sync_eventModWhen    = NULL;
public $tskItem_sync_taskModWhen     = NULL;
public $tskItem_sync_scheduleDateId  = 0;
public $tskItem_sync_scheduleDateModWhen = NULL;
public $tskItem_sync_scheduleStaffModWhen = NULL;

public $tskItem_rec_hiddenStatus = 0;
public $tskItem_rec_modWhen      = NULL;
public $tskItem_rec_modByStaffId = 0;

// computed when reading
public $tskItem_isPeriod_closed  = FALSE;
public $tskItem_isPeriod_special = FALSE;
private $tskItem_changed = FALSE;

function tskItem_process_row( $appGlobals , $row ) {
    // This is the only place where taskItem records are read
    // After reading any record, if the pay Period is open,
    //   it must be refreshed, to re-compute pay amount
    //   The pay period Id must never be NULL (Scheduling needs to set the correct pay period)
    $this->tskItem_taskId                      = $row['jTsk:JobTaskId'];
    $this->tskItem_group_taskId                = $row['jTsk:Group@JobTaskId'];
    $this->tskItem_staffId                     = $row['jTsk:@StaffId'];
    $this->tskItem_originCode                  = $row['jTsk:OriginCode'];
    $this->tskItem_setPeriod_bySpecifiedPeriod( $appGlobals, $row['jTsk:@PayPeriodId'] );
    $this->tskItem_scheduleStatus              = $row['jTsk:ScheduleStatusCode'];
    $this->tskItem_payStatus                   = $row['jTsk:PayStatusCode'];
   
    $this->tskItem_sync_errorBitFlags          = $row['jTsk:SyncErrorBitFlags'];
    $this->tskItem_sync_employeeModWhen        = $row['jTsk:Sync@EmployeeModWhen'];
    $this->tskItem_sync_eventModWhen           = $row['jTsk:Sync@EventModWhen'];
    $this->tskItem_sync_taskModWhen            = $row['jTsk:Sync@TaskModWhen'];
    $this->tskItem_sync_scheduleStaffModWhen   = $row['jTsk:Sync@CalSchedStaffModWhen'];
    $this->tskItem_sync_scheduleDateModWhen    = $row['jTsk:Sync@CalSchedDateModWhen'];
    $this->tskItem_sync_scheduleDateId         = $row['jTsk:Sync@CalSchedDateId'];
    $this->tskItem_sync_eventModWhen           = $row['jTsk:Sync@EventModWhen'];
    
    $this->tskItem_event_programId             = $row['jTsk:Event@ProgramId'];
    $this->tskItem_event_eventId               = $row['jTsk:@EventId'];
    $this->tskItem_event_timeArrive            = $row['jTsk:EventTimeArrive'];
    $this->tskItem_event_timeDepart            = $row['jTsk:EventTimeDepart'];
    $this->tskItem_event_roleCode              = $row['jTsk:EventRoleCode'];
    $this->tskItem_event_prepTime              = $row['jTsk:EventPrepTime'];
    $this->tskItem_event_hadEquipment          = $row['jTsk:EventHadEquipment'];
    $this->tskItem_event_hadBadge              = $row['jTsk:EventHadBadge'];
    $this->tskItem_job_date                    = $row['jTsk:JobDate'];
    $this->tskItem_job_location                = $row['jTsk:JobLocation'];
    $this->tskItem_job_notes                   = $row['jTsk:JobNotes'];
    $this->tskItem_job_time_start              = $row['jTsk:JobTimeStart'];
    $this->tskItem_job_time_end                = $row['jTsk:JobTimeEnd'];
    $this->tskItem_job_atendanceCode           = $row['jTsk:JobAttendanceCode'];
    $this->tskItem_job_rateCode                = $row['jTsk:JobRateCode'];
    $this->tskItem_override_timeMethod         = $row['jTsk:OverrideTimeMethod'];
    $this->tskItem_override_timeMinutes        = $row['jTsk:OverrideTimeMinutes'];
    $this->tskItem_override_rateAmount         = $row['jTsk:OverrideRateAmount'];
    $this->tskItem_override_explanation        = $row['jTsk:OverrideExplanation'];
    $this->tskItem_travel_prevJobItemId        = $row['jTsk:TravelPrev@JobItemId'];
    $this->tskItem_travel_nextJobItemId        = $row['jTsk:TravelNext@JobItemId'];
    $this->tskItem_pay_final_minutes           = $row['jTsk:PayMinutes'];
    $this->tskItem_pay_final_rate              = payData_dollarAmountDecrypt( $this->tskItem_staffId,$row['jTsk:PayRate']);
    $this->tskItem_pay_final_amount            = payData_dollarAmountDecrypt( $this->tskItem_staffId,$row['jTsk:PayAmount']);
    $this->tskItem_rec_hiddenStatus            = $row['jTsk:HiddenStatus'];
    $this->tskItem_rec_modByStaffId            = $row['jTsk:ModBy@StaffId'];
    $this->tskItem_rec_modWhen                 = $row['jTsk:ModWhen'];
    $this->tskItem_isPeriod_closed = ($this->tskItem_job_date<RC_PAYPERIOD_EARLIEST_DATE) or (!empty($row['jPer:WhenClosed']));
    if ( isset($row['jPer:PayPeriodType']) ) {  //????????????????????????????????
        $this->tskItem_isPeriod_special = ($this->tskItem_job_date>=RC_PAYPERIOD_EARLIEST_DATE) and ($row['jPer:PayPeriodType']==RC_PAYPERIOD_SPECIAL);
    }
    if ( !$this->tskItem_isPeriod_closed )  {
        $changed = $this->tskItem_refresh_calculatedFields ( $appGlobals );
        if ($changed) {
            $this->tskItem_save_record( $appGlobals );  // save if changes
        }
    }
}

function tskItem_save_record( $appGlobals, $makeTransaction=TRUE) {
// To preserve consistency of the job group, all saving to the database is done by the group
//   which then calls the taskItem to save a record
//   i.e. Only the taskGroup object should be calling this function (otherwise be very careful)
    // should  call tskItem_refresh_calculatedFields before calling this function
    $data = array();
    $data['jTsk:JobTaskId']                 = $this->tskItem_taskId;
    $data['jTsk:Group@JobTaskId']           = $this->tskItem_group_taskId;
    $data['jTsk:@StaffId']                  = $this->tskItem_staffId;
    $data['jTsk:OriginCode']                = $this->tskItem_originCode;
    $data['jTsk:TravelPrev@JobItemId']      = $this->tskItem_travel_prevJobItemId;
    $data['jTsk:TravelNext@JobItemId']      = $this->tskItem_travel_nextJobItemId;
    $data['jTsk:@PayPeriodId']              = $this->tskItem_payPeriodId;

    $data['jTsk:SyncErrorBitFlags']         = $this->tskItem_sync_errorBitFlags;
    $data['jTsk:Sync@EmployeeModWhen']      = $this->tskItem_sync_employeeModWhen;
    $data['jTsk:Sync@EventModWhen']         = $this->tskItem_sync_eventModWhen;
    $data['jTsk:Sync@TaskModWhen']          = $this->tskItem_sync_taskModWhen;
    $data['jTsk:Sync@CalSchedStaffModWhen'] = $this->tskItem_sync_scheduleStaffModWhen;
    $data['jTsk:Sync@CalSchedDateModWhen']  = $this->tskItem_sync_scheduleDateModWhen;
    $data['jTsk:Sync@CalSchedDateId']       = $this->tskItem_sync_scheduleDateId;
    
    $data['jTsk:@EventId']                  = $this->tskItem_event_eventId;
    $data['jTsk:Event@ProgramId']           = $this->tskItem_event_programId;
    $data['jTsk:EventTimeArrive']           = $this->tskItem_event_timeArrive;
    $data['jTsk:EventTimeDepart']           = $this->tskItem_event_timeDepart;
    $data['jTsk:EventRoleCode']             = $this->tskItem_event_roleCode;
    $data['jTsk:EventPrepTime']             = $this->tskItem_event_prepTime;
    $data['jTsk:EventHadEquipment']         = $this->tskItem_event_hadEquipment;
    $data['jTsk:EventHadBadge']             = $this->tskItem_event_hadEquipment;
    
    $data['jTsk:JobTimeStart']              = empty($this->tskItem_job_time_start) ? NULL : $this->tskItem_job_time_start;
    $data['jTsk:JobTimeEnd']                = empty($this->tskItem_job_time_start) ? NULL : $this->tskItem_job_time_end;
    $data['jTsk:JobDate']                   = $this->tskItem_job_date;
    $data['jTsk:JobLocation']               = $this->tskItem_job_location;
    $data['jTsk:JobNotes']                  = $this->tskItem_job_notes;
    $data['jTsk:JobAttendanceCode']         = $this->tskItem_job_atendanceCode;
    $data['jTsk:JobRateCode']               = $this->tskItem_job_rateCode;
    $data['jTsk:OverrideTimeMethod']        = $this->tskItem_override_timeMethod;
    $data['jTsk:OverrideTimeMinutes']       = empty($this->tskItem_override_timeMinutes) ? 0 : $this->tskItem_override_timeMinutes;
    $data['jTsk:OverrideRateAmount']        = $this->tskItem_override_rateAmount;
    $data['jTsk:OverrideExplanation']       = $this->tskItem_override_explanation;
    $data['jTsk:ScheduleStatusCode']        = $this->tskItem_scheduleStatus;
    $data['jTsk:PayStatusCode']             = $this->tskItem_payStatus;
    $data['jTsk:PayMinutes']                = $this->tskItem_pay_final_minutes;
    $data['jTsk:PayRate']                   = payData_dollarAmountEncrypt( $this->tskItem_staffId,$this->tskItem_pay_final_rate);
    $data['jTsk:PayAmount']                 = payData_dollarAmountEncrypt( $this->tskItem_staffId,$this->tskItem_pay_final_amount);
    $data['jTsk:HiddenStatus']              = $this->tskItem_rec_hiddenStatus;
    
    if ( ($this->tskItem_taskId) == 0 and $makeTransaction) {
        $appGlobals->gb_sql->rc_startTransaction();
    }
    $recId = $appGlobals->gb_sql->sql_saveRecord( 'job:task', 'jTsk:', 'jTsk:JobTaskId', $this->tskItem_taskId, $data, RGB_HISTORY_SAVE_TRUE);
    if ( $this->tskItem_taskId == 0 ) {
        $this->tskItem_taskId = $recId;
    }
    if ( $this->tskItem_group_taskId == 0 ) {
        $this->tskItem_group_taskId = $recId;
        $query = "UPDATE `job:task` SET `jTsk:Group@JobTaskId` =  '{$recId}' WHERE `jTsk:JobTaskId` = '{$this->tskItem_taskId}'";
        $result = $appGlobals->gb_sql->sql_performQuery($query );
        //????????????????????????????? need rollback if applicable
        $isFinalRecord = TRUE;
        if ( $makeTransaction) {
            $appGlobals->gb_sql->rc_commit();
        }
    }
    return $recId;
}

private function tskItem_refresh_set (&$dest, $source ) {
    if ($dest != $source) {
        $dest = $source;
        $this->tskItem_changed = TRUE;
        // save changed bit
    }
}


function tskItem_refresh_calculatedFields ( $appGlobals ) {
    $this->tskItem_changed = FALSE;
    if ( $this->tskItem_isPeriod_closed) {
        return FALSE; // never change period ID of closed taskItem
    }
    // after reading or creating taskGroup record, or items added, deleted, etc
    //-- compute certain values which are dependent on other values (such as pay overrides, pay rate, pay amount)
    if ( !empty($appGlobals->gb_period_current;->prd_whenClosed ) ) {
        return FALSE;    // do not re-compute records that are not in the current period
    }
    if ( is_null($this->tskItem_job_date) ) {
        return FALSE;  // should never happen - only time that it could properly happen is if a freshly created jobitem gets refreshed
    }
    $employee = $appGlobals->gb_employeeArray[$this->tskItem_staffId];

    // compute default pay - will be recomputed below for special situations
    if ( (!empty($this->tskItem_job_time_start)) and (!empty($this->tskItem_job_time_end)) ) {
        $this->tskItem_refresh_set($this->tskItem_pay_final_minutes , draff_timeMinutesDif($this->tskItem_job_time_start,$this->tskItem_job_time_end) + $this->tskItem_event_prepTime);
    }
    else  {
        $this->tskItem_refresh_set($this->tskItem_pay_final_minutes , 0);
    }
    $this->tskItem_refresh_set($this->tskItem_pay_final_rate , 0);
    $this->tskItem_refresh_set($this->tskItem_pay_final_amount , 0);
    $allowTimeOverride = TRUE;
    $allowRateOverride = TRUE;
    $allowPayOverride = TRUE;
    // Special pay calculations
    if ( $this->tskItem_originCode == RC_JOB_ORIGIN_SALARY ) {
        $this->tskItem_refresh_set($this->tskItem_pay_final_minutes , 0);
        $this->tskItem_refresh_set($this->tskItem_pay_final_rate , $employee->emp_rateSalary);
        $this->tskItem_refresh_set($this->tskItem_pay_final_amount , $employee->emp_rateSalary);
        if ($this->tskItem_job_rateCode==PAY_RATEMETHOD_OVERRIDE_AMOUNT) {
            $this->tskItem_refresh_set($this->tskItem_pay_final_amount , $this->tskItem_override_rateAmount);
        }
        $allowTimeOverride = FALSE;  // time is irrelavant
        $allowRateOverride = FALSE;  // only one fixed pay rate
        $allowPayOverride  = TRUE;   // possibility of partial pay period for first and last period on job
    }
    else if ( $this->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL ) {
        $allowOverrides = TRUE;
        $this->tskItem_refresh_set($this->tskItem_pay_final_minutes, ($this->tskItem_pay_final_minutes > EVTADJUST_TRAVEL_MAXGAP) ? $this->tskItem_pay_final_minutes = 0 : min($this->tskItem_pay_final_minutes,EVTADJUST_TRAVEL_MAXTIME) );
        $allowTimeOverride = TRUE;
        $allowRateOverride = TRUE;
        $allowPayOverride  = TRUE;
    }
    else if ( $this->tskItem_job_atendanceCode >= RC_JOB_ATTEND_ABSENT_UNEXCUSED ) {
        $this->tskItem_refresh_set($this->tskItem_pay_final_minutes , 0 );
        $this->tskItem_refresh_set($this->tskItem_pay_final_rate , 0 );
        $this->tskItem_refresh_set($this->tskItem_pay_final_amount , 0);
        $allowTimeOverride = FALSE;
        $allowRateOverride = FALSE;
        $allowPayOverride  = FALSE;
    }
    else if ( ($this->tskItem_job_atendanceCode == RC_JOB_ATTEND_PIF) and (!empty($this->tskItem_event_eventId)) ) {
       // whern  PIF (Paid in Full) event time range overrides schedule info
        $query = "SELECT `jEvt:StartTime`,`jEvt:EndTime`,`pPr:ProgramType`  FROM `job:event` JOIN `pr:program` ON `pPr:ProgramId`=`jEvt:@ProgramId` WHERE `jEvt:EventId` = '{$this->tskItem_event_eventId}'";
        $result = $appGlobals->gb_sql->sql_performQuery( $query);
        $row=$result->fetch_array();
        $eventItem = new payData_event_item;
        $eventItem->evt_read( $appGlobals , $this->tskItem_event_eventId);
        $timeResult  = pay_calcStaffTimeFromEventTime($row['jEvt:StartTime'], $row['jEvt:EndTime'], $this->tskItem_event_roleCode , $row['pPr:ProgramType']);
        $this->tskItem_refresh_set($this->tskItem_pay_final_minutes ,draff_timeMinutesDif($timeResult['start'],$timeResult['end']) + $timeResult['prepTime'] );
        $allowTimeOverride = FALSE;  //?????????
        $allowRateOverride = TRUE;
        $allowPayOverride  = TRUE;
    }
    if ( $allowTimeOverride )  {
        switch ( $this->tskItem_override_timeMethod ) {
            case PAY_TIMEOVERRIDE_TRUE:   $this->tskItem_refresh_set($this->tskItem_pay_final_minutes , $this->tskItem_override_timeMinutes); break;
            //case PAY_TIMEADJUST_ADDITIONAL: $this->tskItem_pay_final_minutes = $this->tskItem_pay_final_minutes + $this->tskItem_override_timeMinutes; break;
            //case PAY_TIMEADJUST_MINUS:      $this->tskItem_pay_final_minutes = $this->tskItem_pay_final_minutes - $this->tskItem_override_timeMinutes; break;
        }
    }
    if ( $allowRateOverride )  {
        //--- compute payRate and payAmount
        switch ( $this->tskItem_job_rateCode ) {
            case 0: // n/a - should never happen
                $this->tskItem_refresh_set($this->tskItem_pay_final_rate , 0);
                $this->tskItem_refresh_set($this->tskItem_pay_final_amount , 0);
                $allowPayOverride  = FALSE;
                break;
            case PAY_RATEMETHOD_ADMIN: // admin rate
                $this->tskItem_refresh_set($this->tskItem_pay_final_rate , $employee->emp_rateAdmin);
                $this->tskItem_refresh_set($this->tskItem_pay_final_amount , $this->tskItem_pay_final_rate * ($this->tskItem_pay_final_minutes / 60) );
                break;
            case PAY_RATEMETHOD_FIELD: // field rate
                $this->tskItem_refresh_set($this->tskItem_pay_final_rate , $employee->emp_rateField);
                $this->tskItem_refresh_set($this->tskItem_pay_final_amount , $this->tskItem_pay_final_rate * ($this->tskItem_pay_final_minutes / 60) );
               break;
            case PAY_RATEMETHOD_SALARY: // salary rate
                // should never happen ???????????????
                $this->tskItem_refresh_set($this->tskItem_pay_final_rate , $employee->emp_rateSalary );
                $this->tskItem_refresh_set($this->tskItem_pay_final_amount , $this->tskItem_pay_final_rate );
                break;
            case PAY_RATEMETHOD_OVERRIDE_RATE: // rate override
                $this->tskItem_refresh_set($this->tskItem_pay_final_rate, $this->tskItem_override_rateAmount);
                $this->tskItem_refresh_set($this->tskItem_pay_final_amount, $this->tskItem_pay_final_rate * ($this->tskItem_pay_final_minutes / 60));
                break;
            case PAY_RATEMETHOD_OVERRIDE_AMOUNT: // total pay override
                $this->tskItem_refresh_set($this->tskItem_pay_final_rate, 0);
                $this->tskItem_refresh_set($this->tskItem_pay_final_amount, $this->tskItem_override_rateAmount);
                break;
            default : // should never get here
                $this->tskItem_refresh_set($this->tskItem_pay_final_rate , 0);
                $this->tskItem_refresh_set($this->tskItem_pay_final_amount , 0);
                break;
        }
    }
    $this->tskItem_refresh_set($this->tskItem_pay_final_amount, round($this->tskItem_pay_final_amount , 2 , PHP_ROUND_HALF_UP));
    return $this->tskItem_changed;
}

function tskItem_setPeriod_bySpecifiedPeriod( $appGlobals, $periodId ) {
    if ( $this->tskItem_payPeriodId === NULL ) {
        $this->tskItem_payPeriodId = $periodId;
    }
}

function tskItem_setPeriod_byDate( $appGlobals ) {
    if ( $this->tskItem_job_date<RC_PAYPERIOD_EARLIEST_DATE) {
        $this->tskItem_payPeriodId = 0;
        return;
    }
    if ( empty($this->tskItem_payPeriodId) ) {
        $this->tskItem_payPeriodId = payData_factory::payFactory_get_payPeriodId_ofDate( $appGlobals , $this->tskItem_job_date );
    }
}

function tskItem_setPeriod_byCurrentPeriod( $appGlobals) {
    if ( $this->tskItem_payPeriodId === NULL ) {
        $this->tskItem_payPeriodId = $appGlobals->gb_period_current;->prd_payPeriodId;
    }
}

function tskItem_setPeriod_bySameAsGroup( $taskGroup) {
    // set period to be the same as the other jobItems in group
    if ( $this->tskItem_payPeriodId===NULL) {
        // This should usually or always be the case but ok if it's not the case
        $periodId = NULL;
        foreach ($taskGroup->tskGrp_jobItem_array as $jobLoop) {
            if ( $jobLoop->tskItem_payPeriodId!==NULL) { // last item could be final item or about-to-be not final item
                if ( $periodId===NULL ) {
                    $periodId = $jobLoop->tskItem_payPeriodId;
                }
                else if ( $periodId != $jobLoop->tskItem_payPeriodId) {
                    draff_errorTerminate("Inconsistent period Id's in one job group");
                }
            }
        }
        $this->tskItem_payPeriodId =  $periodId ; // same period id as others in group
    }
}

function tskItem_isSame( $orgJobItem ) {
    $newValues = get_object_vars ($this);
    $orgValues = get_object_vars ($orgJobItem);
    $dif = array_diff_assoc($newValues,$orgValues);
    return empty($dif);
    // alternative code
    // foreach ($newValues as $key => $value) {
    //     if ( $value != $orgValues[$key]) {
    //         return TRUE;
    //     }
    // }
    // return FALSE;
}

function tskItem_create_clonedJobItem() {
    return clone $this;
}

} // end job item class

class payData_task_group {
public $tskGrp_jobItem_array = array();  // last element is "final" task item

function __construct() {
    // parent::__construct();
}

function tskGrp_process_rows( $appGlobals , $rows ) {
}

function tskGrp_save_finalRecord ( $appGlobals ) {
    $taskFinal = $this->tskGrp_get_finalItem();
    $taskFinal->tskItem_refresh_calculatedFields ( $appGlobals );
    $taskFinal->tskItem_save_record( $appGlobals);
}

function tskGrp_save_syncRecord ( $appGlobals ) {
    // a sync record is used for syncing with event, and is missing other items in the group
    // should only be used by synchronization
    $taskFinal = $this->tskGrp_get_finalItem();
    $taskFinal->tskItem_refresh_calculatedFields( $appGlobals);
    $taskFinal->tskItem_save_record( $appGlobals);
}

function tskGrp_save_allRecords ( $appGlobals ) {
    foreach ($this->tskGrp_jobItem_array as $taskItem) {
        $taskItem->tskItem_refresh_calculatedFields( $appGlobals);
        $taskItem->tskItem_save_record( $appGlobals );
    }
}

function tskGrp_addItem( $taskItem ) {
    // assert jobitem id is null or same as others in group
    $taskItem->tskItem_setPeriod_bySameAsGroup($this);
    $this->tskGrp_jobItem_array[] = $taskItem;
}

function tskGrp_get_finalItem() {
    $count = count($this->tskGrp_jobItem_array);
    return ($count==0) ? NULL : $this->tskGrp_jobItem_array[$count-1];
}

function tskGrp_createNewFinalItem() {
    $finalItem = new payData_task_item;
    $this->tskGrp_addItem( $finalItem );
    return $finalItem;
}

function tskGrp_hasHistory() {
    return (count($this->tskGrp_jobItem_array) > 1);
}

function tskGrp_delete_taskGroup( $appGlobals ) {
    $taskFinal = $this->tskGrp_get_finalItem();
    $query = "DELETE FROM `job:task` WHERE `jTsk:Group@JobTaskId` = '".$taskFinal->tskItem_group_taskId."'";
    $result = $appGlobals->gb_sql->sql_performQuery( $query);
    $this->tskGrp_jobItem_array = array();
}

function tskGrp_delete_finalItem( $appGlobals) {
    $finalItem = $this->tskGrp_get_finalItem();
    if ( ($finalItem->tskItem_taskId==0) or ($finalItem->tskItem_originCode == RC_JOB_ORIGIN_SCHEDULE) )  {
        return;
    }
    $query = "DELETE FROM `job:task` WHERE `jTsk:JobTaskId` = '".$finalItem->tskItem_taskId."'";
    $result = $appGlobals->gb_sql->sql_performQuery( $query);
    unset($this->tskGrp_jobItem_array[count($this->tskGrp_jobItem_array)-1] );
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  payData_task_reader
//@@@@@@@@@@
//@@@@@

class payData_task_reader {
// Reads Jobgroups - either (1) All jobgroups in period (2) All jobgroups in period for one employee
//     (3) Specified jobgroup
private $tranRead_result;
private $tranRead_row;

function tskReader_getFirst( $appGlobals , $payPeriod , $employeeId , $taskGroupId=NULL ) {
    $appGlobals->gb_employeeArray = array();
    $sql = array();
    $sql[] = "SELECT *";
    $sql[] = "FROM `job:task`";
    $sql[] = "JOIN `job:employee`   ON `jEmp:@StaffId` = `jTsk:@StaffId`";
    $sql[] = "JOIN `st:staff`       ON (`sSt:StaffId`   = `jTsk:@StaffId`)";
    $sql[] = "JOIN `job:payperiod`  ON (`jPer:PayPeriodId` = `jTsk:@PayPeriodId`)";  // used by process_row
    if ( !empty($taskGroupId) ) {
        $sql[] = "WHERE (`jTsk:Group@JobTaskId`= '{$taskGroupId}')";
    }
    else {
        $sql[] = "WHERE (`jTsk:@PayPeriodId`= '{$payPeriod->prd_payPeriodId}')";
        if ( !empty($employeeId)) {
            $sql[] = "  AND (`jTsk:@StaffId`='$employeeId')";
        }
    }
    $sql[] = "ORDER BY `sSt:FirstName`, `sSt:LastName`, `jTsk:Group@JobTaskId`,  `jTsk:OriginCode`";  //  //, `jTsk:OriginCode` DESC", `jTsk:JobTaskId`
    $query = implode( $sql, ' ');
    $this->tranRead_result = $appGlobals->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
    $this->tranRead_row=$this->tranRead_result->fetch_array();
    return $this->tskReader_getNext( $appGlobals );
}

function tskReader_getNext( $appGlobals ) {
    if ( $this->tranRead_row == FALSE ) {
        return FALSE;
    }
    // create global employee record (needed for pay calculation)
    $employeeId = $this->tranRead_row['jTsk:@StaffId'];
    if ( !isset($appGlobals->gb_employeeArray[$employeeId]) ) {
        $employee = new dbRecord_payEmployee;
        $employee->emp_processRow($appGlobals ,$this->tranRead_row);
        $appGlobals->gb_employeeArray[$employeeId] = $employee;
    }
    $groupRows = array($this->tranRead_row);
    $curJobGroupId = $this->tranRead_row['jTsk:Group@JobTaskId'];
    while ( $this->tranRead_row=$this->tranRead_result->fetch_array() ) {
        if ( $this->tranRead_row['jTsk:Group@JobTaskId'] != $curJobGroupId ) {
            break;
        }
        $groupRows[] = $this->tranRead_row;
    }
    $taskGroup = payData_factory::payFactory_get_taskGroup_fromRows( $appGlobals, $groupRows );
    return $taskGroup;
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  payData_event_item
//@@@@@@@@@@
//@@@@@

class payData_event_item  extends draff_database_record {

const DB_TABLE_NAME  = '';
const DB_TABLE_INDEX = '';
const DB_MOD_WHEN    = '';
const DB_MOD_WHO     = '';
const DB_SELECT_FIELDS  = array();

public $evt_jobEventId = 0;
public $evt_programId = NULL;
public $evt_eventDate;
public $evt_startTime;
public $evt_endTime;
public $evt_holidayFlag = 0;
public $evt_notesSchedule = '';
public $evt_notesIncidents = '';
public $evt_notesActivities = '';
public $evt_location = '';
public $evt_SMSubmissionStatus = 0;
public $evt_publishedFlag = 0;
public $evt_sync_errorBitFlags = 0;
public $evt_sync_scheduleDateId = 0;
public $evt_sync_scheduleDateModWhen    = NULL;  // used when synchronizing
public $evt_hiddenStatus = 0;
public $evt_modByStaffId;
public $evt_modWhen;

function __construct() {
}

function evt_read( $appGlobals , $eventId) {
    $sql = array();
    $sql[] = "SELECT *";
    $sql[] = "FROM `job:event`";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    $row=$result->fetch_array();
    $this->evt_process_row( $row );
}

function evt_process_row($row) {
    $this->evt_jobEventId          = $row['jEvt:EventId'];
    $this->evt_sync_errorBitFlags  = $row['jEvt:SyncErrorBitFlags'];
    $this->evt_sync_scheduleDateId      = $row['jEvt:Sync@CalSchedDateId'];
    $this->evt_sync_scheduleDateModWhen = $row['jEvt:Sync@CalSchedDateModWhen'];
    $this->evt_programId           = $row['jEvt:@ProgramId'];
    $this->evt_location            = $row['jEvt:Location'];
    $this->evt_eventDate           = $row['jEvt:EventDate'];
    $this->evt_startTime           = $row['jEvt:StartTime'];
    $this->evt_endTime             = $row['jEvt:EndTime'];
    $this->evt_holidayFlag         = $row['jEvt:HolidayFlag'];
    $this->evt_notesSchedule       = $row['jEvt:Notes'];
    $this->evt_notesIncidents      = $row['jEvt:NotesIncidents'];
    $this->evt_notesActivities     = $row['jEvt:NotesActivities'];
    $this->evt_SMSubmissionStatus  = $row['jEvt:SMSubmissionStatus'];
    $this->evt_publishedFlag       = $row['jEvt:Published?'];
    $this->evt_hiddenStatus        = $row['jEvt:HiddenStatus'];
    $this->evt_modByStaffId        = $row['jEvt:ModBy@StaffId'];
    $this->evt_modWhen             = $row['jEvt:ModWhen'];
}

function evt_save_record( $appGlobals ) {
    $data = array();
    $data['jEvt:EventId']             = $this->evt_jobEventId;
    $data['jEvt:SyncErrorBitFlags']   = $this->evt_sync_errorBitFlags;
    $data['jEvt:Sync@CalSchedDateId'] = $this->evt_sync_scheduleDateId;
    $data['jEvt:Sync@CalSchedDateModWhen'] = $this->evt_sync_scheduleDateModWhen;
    $data['jEvt:@ProgramId']          = $this->evt_programId;
    $data['jEvt:Location']            = $this->evt_location;
    $data['jEvt:EventDate']           = $this->evt_eventDate;
    $data['jEvt:StartTime']           = $this->evt_startTime;
    $data['jEvt:EndTime']             = $this->evt_endTime;
    $data['jEvt:HolidayFlag']         = $this->evt_holidayFlag;
    $data['jEvt:Notes']               = $this->evt_notesSchedule;
    $data['jEvt:NotesIncidents']      = $this->evt_notesIncidents;
    $data['jEvt:NotesActivities']     = $this->evt_notesActivities;
    $data['jEvt:SMSubmissionStatus']  = $this->evt_SMSubmissionStatus;
    $data['jEvt:Published?']          = $this->evt_publishedFlag;
    $data['jEvt:HiddenStatus']        = $this->evt_hiddenStatus;
    $this->evt_jobEventId = $appGlobals->gb_sql->sql_saveRecord( 'job:event', 'jEvt:', 'jEvt:EventId', $this->evt_jobEventId, $data, RGB_HISTORY_SAVE_TRUE);
    return $this->evt_jobEventId;
}

function evt_delete_record( $appGlobals ) {
    $query = "DELETE FROM `job:event` WHERE `jEvt:EventId` = '".$taskItem->evt_jobEventId."'";
    $result = $appGlobals->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
}

}  // end class


//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  payData_ pay_onePayItem
//@@@@@@@@@@
//@@@@@

class payData_ledger_day {
// Used to produce Earning Details Report
// daily within employee
// contains job items for a single employee, single period
public $ldgDay_taskGroup_array = array();
public $ldgDay_date;
public $ldgDay_employeeId;

function __construct($jobDate, $employeeId) {
    $this->ldgDay_date       = $jobDate;
    $this->ldgDay_employeeId = $employeeId;
}

function ldgDay_addTransaction( $appGlobals , $taskGroup) {
    $this->ldgDay_taskGroup_array[] = $taskGroup;
}

function ldgDay_canTravel($taskGroup) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    return ( !empty($taskFinal->tskItem_event_eventId) and ($taskFinal->tskItem_pay_final_amount>=.01) ) ;
}

function ldgDay_finalize( $appGlobals ) {
    
    //--- Seperate jobItems into travel and job arrays - will need to synchronize travel with any changes to jobs
    $jobArray   = array();
    $travelArray = array();
    for ( $i=0 ; $i<count($this->ldgDay_taskGroup_array) ; ++$i ) {
        $taskGroup = $this->ldgDay_taskGroup_array[$i];
        $finalItem = $taskGroup->tskGrp_get_finalItem();
        if ( $finalItem->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL ) {
            $travelArray[] = $taskGroup;
        }
        else {
            $jobArray[] = $taskGroup;
        }
    }
    
    //--- sort non-travel transactions by time
    $timeArray = array();
    foreach ( $jobArray as $taskGroup ) {
        $finalItem = $taskGroup->tskGrp_get_finalItem();
        $timeArray[] = $finalItem->tskItem_job_time_start;
    }
    array_multisort( $timeArray , $jobArray );
    // maybe should validate no overlapping times

    // need to rebuild array with correct travel transactions
    $this->ldgDay_taskGroup_array = array();
    $jobArrayLast = count($jobArray) - 1;
    $travelArrayCount = count($travelArray);
    for ( $i=0 ; $i<=$jobArrayLast ; ++$i ) {
        $prevTran = $jobArray[$i];
        $this->ldgDay_taskGroup_array[] = $prevTran;
        if ( $i==$jobArrayLast ) {
            break;  // cannot have travel taskGroup after last item
        }
        $nextTran = $jobArray[$i+1];
        if ( ! ($this->ldgDay_canTravel($prevTran) and $this->ldgDay_canTravel($nextTran) ) ) {
            continue;
        }
       
        // both $prevTrana and $nextTran are eligible for travel
        // find travel taskGroup
        $matchingTravelJobGroup = NULL;
        for ( $t=0 ; $t<$travelArrayCount ; ++$t ) {
            $existingTravelJobGroup = $travelArray[$t];
            if ( $existingTravelJobGroup===NULL ) {
                continue;
            }
            $jobTravelItem = $existingTravelJobGroup->tskGrp_get_finalItem();
            $jobPrevItem = $prevTran->tskGrp_get_finalItem();
            $jobNextItem = $nextTran->tskGrp_get_finalItem();
            if ( ($jobTravelItem->tskItem_travel_prevJobItemId === $jobPrevItem->tskItem_taskId)  and ($jobTravelItem->tskItem_travel_nextJobItemId===$jobNextItem->tskItem_taskId) )  {
                $matchingTravelJobGroup = $existingTravelJobGroup;
                $travelArray[$t] = NULL; // this taskGroup of travel has been claimed
            }
        }
        // update existing or create new travel taskGroup
        if ( $matchingTravelJobGroup==NULL ) {
            $matchingTravelJobGroup = payData_factory::payFactory_get_taskGroup_new_travel( $appGlobals ,$prevTran, $nextTran);
        }
        $this->ldgDay_travel_updateTransaction( $appGlobals ,$matchingTravelJobGroup, $prevTran, $nextTran);  // also saves
        $this->ldgDay_taskGroup_array[] = $matchingTravelJobGroup;
    }
    for ( $t=0 ; $t<$travelArrayCount ; ++$t ) {
        $taskGroup = $travelArray[$t];
        if ( $taskGroup != NULL ) {
            $taskGroup->tskGrp_delete_taskGroup( $appGlobals );
        }
    }
     // ???? error checking for duplicate times (unaccept these?, travel time record consistency, etc)
    // ???? also error if schedule record no longer exists (staff was removed from schedule)
    // ???? maybe need error flag in each taskGroup to indicate these problems ????
    // also need to process time records - add, delete, validate, etc
}

function ldgDay_travel_updateTransaction( $appGlobals , $taskGroup , $prevTran , $nextTran ) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $prevTran = $prevTran->tskGrp_get_finalItem();
    $nextTran = $nextTran->tskGrp_get_finalItem();
    $orgTaskFinal = $taskFinal->tskItem_create_clonedJobItem();
    //$taskGroup->tskGrp_refreshChanged = FALSE  or ($taskGroup->tskItem_taskId == 0);
    $curItem = $taskFinal;
    $prevItem = $prevTran;
    $nextItem = $nextTran;
    $curItem->tskItem_job_location  = 'Travel between ' . $prevTran->tskItem_job_location  . ' and ' . $nextTran->tskItem_job_location ;
    $timeDif = draff_timeMinutesDif($curItem->tskItem_job_time_start , $curItem->tskItem_job_time_end);
    if ( $timeDif >=  EVTADJUST_TRAVEL_MAXGAP) {
        $curItem->tskItem_job_notes = 'No travel pay - Large time gap between tasks';
    }
    if ( $curItem->tskItem_job_time_start >=  $curItem->tskItem_job_time_end) {  // add a few minutes ??????
        $curItem->tskItem_job_notes = 'No travel pay - Time gap is insignificant';
    }
    $prevRateMethod = $prevItem->tskItem_job_rateCode;
    $nextRateMethod = $nextItem->tskItem_job_rateCode;
    if ( ($prevRateMethod > 2) or ($nextRateMethod>2) ) {
        $curItem->tskItem_job_rateCode = min($prevRateMethod,$nextRateMethod);
        if ( $curItem->tskItem_job_rateCode > 2) {
            $curItem->tskItem_job_rateCode = PAY_RATEMETHOD_FIELD;
        }
    }
    else {
        $curItem->tskItem_job_rateCode = min($prevRateMethod,$nextRateMethod);
    }
    $taskFinal->tskItem_travel_prevJobItemId = $prevTran->tskItem_taskId;
    $taskFinal->tskItem_travel_nextJobItemId = $nextTran->tskItem_taskId;
    $taskFinal->tskItem_refresh_calculatedFields ( $appGlobals );
    if (  ! $taskFinal->tskItem_isSame( $orgTaskFinal ) ) {
        $taskGroup->tskGrp_save_finalRecord( $appGlobals);
    }
}

function ldgDay_travel_deleteTransaction( $appGlobals ,$taskGroup ) {
   $taskGroup->tskGrp_delete_taskGroup( $appGlobals );
}

} // end ledger day class

class payData_ledger_employee  extends draff_database_record {

const DB_TABLE_NAME  = '';
const DB_TABLE_INDEX = '';
const DB_MOD_WHEN    = '';
const DB_MOD_WHO     = '';
const DB_SELECT_FIELDS  = array();

// Used to produce Earning Details Report
// jobitems for employee within a single period
public $ldgEpy_dailyLedger_array = array();  // the ledger for each day
public $ldgEpy_employeeId;

function __construct($employeeId) {
    $this->ldgEpy_employeeId = $employeeId;
}

function ldgEpy_read( $appGlobals , $payPeriod) {
    $taskGroupReader = new payData_task_reader;
    $taskGroup = $taskGroupReader->tskReader_getFirst( $appGlobals , $payPeriod, $this->ldgEpy_employeeId);
    while ( $taskGroup!== FALSE ) {
        $finalItem = $taskGroup->tskGrp_get_finalItem();
        $payItemEmployeeId = $finalItem->tskItem_staffId;
        $this->ldgEpy_addTransaction( $appGlobals , $taskGroup);
        $taskGroup = $taskGroupReader->tskReader_getNext( $appGlobals );
    }
    $this->ldgEpy_finalize( $appGlobals );
}

function ldgEpy_addTransaction( $appGlobals , $taskGroup) {
    if ( $taskGroup==NULL ) {
        dxbg('Need Transaction for onePeriod');
        return;
    }
    $finalItem = $taskGroup->tskGrp_get_finalItem();
    $jobDate = $finalItem->tskItem_job_date;
    if ( isset($this->ldgEpy_dailyLedger_array[$jobDate]) ) {
        $dailyLedger = $this->ldgEpy_dailyLedger_array[$jobDate];
    }
    else {
        $dailyLedger = new payData_ledger_day($jobDate,$finalItem->tskItem_staffId);
        $this->ldgEpy_dailyLedger_array[$jobDate] = $dailyLedger;
    }
    $dailyLedger->ldgDay_addTransaction( $appGlobals , $taskGroup);
}

function ldgEpy_finalize( $appGlobals ) {
    // need to call this after all of the transactions have been added
    $dates = array();
    foreach ( $this->ldgEpy_dailyLedger_array as $dailyLedger ) {
        $dates[] = $dailyLedger->ldgDay_date;
        $dailyLedger->ldgDay_finalize( $appGlobals );
    }
    array_multisort($dates,$this->ldgEpy_dailyLedger_array);
}

} // end ledger employee class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  payData_ledger_period - pay groups for a single person or everyone
//@@@@@@@@@@
//@@@@@

class payData_ledger_period {
// Used to produce Earning Details Report
// contains job items for a single period (can be all employees or a single employee)
public $ldgPer_employeeLedger_array = array();  // the ledger for each employee

function ldgPer_read( $appGlobals , $payPeriod, $staffId=NULL) {
    //$syncEngine = new payData_sync_converter;
    //$syncEngine->sync_synchronize_calendar( $appGlobals , '2019-01-24','2019-02-09', '2018-01-26 00:00:01',  0); //?????????????????????????????????????

    $taskGroupReader = new payData_task_reader;
    $taskGroup = $taskGroupReader->tskReader_getFirst( $appGlobals , $payPeriod, $staffId);
    $curEmployeeId = 0;
    $curEmployeePayroll = NULL;
    while ($taskGroup!== FALSE) {
        $taskFinal = $taskGroup->tskGrp_get_finalItem();
        $payItemEmployeeId = $taskFinal->tskItem_staffId;
        if ( isset($this->ldgPer_employeeLedger_array[$payItemEmployeeId]) ) {
            $curEmployeePayroll = $this->ldgPer_employeeLedger_array[$payItemEmployeeId];
        }
        else {
            $curEmployeePayroll = new payData_ledger_employee( $payItemEmployeeId );
            $this->ldgPer_employeeLedger_array[$payItemEmployeeId] = $curEmployeePayroll;
        }
        $curEmployeePayroll->ldgEpy_addTransaction( $appGlobals , $taskGroup );
        $taskGroup = $taskGroupReader->tskReader_getNext( $appGlobals );
    }
    $this->ldgPer_finalize( $appGlobals );
}

private function ldgPer_finalize( $appGlobals ) {
    // need to call this after all of the transactions have been added
    foreach ( $this->ldgPer_employeeLedger_array as $employeeLedger ) {
        $employeeLedger->ldgEpy_finalize( $appGlobals );
    }
}

} // end ledger period class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  dbRecord_payPeriod
//@@@@@@@@@@
//@@@@@

class dbRecord_payPeriod  extends draff_database_record {

const DB_TABLE_NAME  = '';
const DB_TABLE_INDEX = '';
const DB_MOD_WHEN    = '';
const DB_MOD_WHO     = '';
const DB_SELECT_FIELDS  = array();

public $prd_payPeriodId   = 0;
public $prd_periodType    = 0; // 1=standard - periods must follow each other, 2=special - can be any reasonable date
public $prd_periodName    = 0;
public $prd_dateStart     = '';
public $prd_dateEnd       = '';
public $prd_whenClosed    = NULL;
public $prd_statusStep    = 0;
public $prd_statusReports = 0; //
public $prd_hiddenStatus  = 0;
public $prd_modWhen       = '';
public $prd_modBy_staffId = 0;

function __construct() {
}

static function prd_getFields( &$fields ) {
    $fields[] = 'jPer:PayPeriodId';
    $fields[] = 'jPer:PayPeriodType';
    $fields[] = 'jPer:PeriodName';
    $fields[] = 'jPer:DateStart';
    $fields[] = 'jPer:DateEnd';
    $fields[] = 'jPer:WhenClosed';
    $fields[] = 'jPer:StatusStep';
    $fields[] = 'jPer:StatusReports';
    $fields[] = 'jPer:HiddenStatus';
    $fields[] = 'jPer:ModBy@StaffId';
    $fields[] = 'jPer:ModWhen';
}

function prd_processRow( $appGlobals , $row) {
    $this->prd_payPeriodId       = $row['jPer:PayPeriodId'];
    $this->prd_periodType        = $row['jPer:PayPeriodType'];
    $this->prd_periodName        = $row['jPer:PeriodName'];
    $this->prd_dateStart         = $row['jPer:DateStart'];
    $this->prd_dateEnd           = $row['jPer:DateEnd'];
    $this->prd_whenClosed        = $row['jPer:WhenClosed'];
    $this->prd_statusStep        = $row['jPer:StatusStep'];
    $this->prd_statusReports     = $row['jPer:StatusReports'];
    $this->prd_hiddenStatus      = $row['jPer:HiddenStatus'];
    $this->prd_modBy_staffId     = $row['jPer:ModBy@StaffId'];
    $this->prd_modWhen           = $row['jPer:ModWhen'];
    if ( trim($this->prd_periodName)=='' ) {
        $this->prd_setName( $appGlobals );
    }
}

function prd_readRecord( $appGlobals , $payPeriodId) {
   $row = $appGlobals->gb_sql->sql_readSingleRecord( '*' , 'job:payperiod' , 'jPer:PayPeriodId' , $payPeriodId);
   $this->prd_processRow( $appGlobals , $row );
}

function prd_clearReportsFlag( $appGlobals ) {
    if ( $this->prd_statusReports !=0) {
       $data = array();
       $data['jPer:StatusReports'] = 0;
       $recId = $appGlobals->gb_sql->sql_saveRecord( 'job:payperiod', 'jPer:', 'jPer:PayPeriodId', $this->prd_payPeriodId, $data, RGB_HISTORY_SAVE_TRUE );
    }
}

function prd_saveRecord( $appGlobals , $histMode = RGB_HISTORY_SAVE_TRUE ) {
    $data = array();
    if ( empty($this->prd_whenClosed) ) {
        $this->prd_whenClosed = NULL;
    }
    $data['jPer:PayPeriodType']   = $this->prd_periodType;
    $data['jPer:PeriodName']      = $this->prd_periodName;
    $data['jPer:DateStart']    = $this->prd_dateStart;
    $data['jPer:DateEnd']      = $this->prd_dateEnd;
    $data['jPer:WhenClosed']   = $this->prd_whenClosed;
    $data['jPer:StatusStep']       = $this->prd_statusStep;
    $data['jPer:StatusReports']    = $this->prd_statusReports;
    $data['jPer:HiddenStatus'] = $this->prd_hiddenStatus;
    $recId = $appGlobals->gb_sql->sql_saveRecord( 'job:payperiod' , 'jPer:' , 'jPer:PayPeriodId' , $this->prd_payPeriodId , $data );
    $this->prd_payPeriodId = $recId;
}

function prd_setName( $appGlobals=NULL ) {
    $year1 = substr($this->prd_dateStart,0,4);
    $year2 = substr($this->prd_dateEnd,0,4);
    if ( $year1 == $year2 ) {
        $format = 'D F j';
        $suffix = ", {$year1}";
    }
    else {
       $format =  'D F j, Y';
       $suffix = '';
    }
    $start = draff_dateAsString($this->prd_dateStart, $format);
    $end   = draff_dateAsString($this->prd_dateEnd, $format);
    if ( $this->prd_dateStart!=$this->prd_dateEnd ) {
        $this->prd_periodName =  $start . ' - ' . $end . $suffix;
    }
    else {
         $this->prd_periodName =  $start . $suffix;
    }
    if ( $this->prd_periodType==RC_PAYPERIOD_SPECIAL ) {
       $this->prd_periodName .= ' (Special)';
    }
    if ( $appGlobals !== NULL ) {
        $this->prd_saveRecord( $appGlobals , RGB_HISTORY_SAVE_TRUE);
    }
}

}  // end pay period item class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  payData_payPeriod_batch
//@@@@@@@@@@
//@@@@@

class payData_payPeriod_batch {
// list of pay periods
public $prdBat_count;
public $prdBat_items;

function _construct() {
}

function prdBat_readBatch( $appGlobals , $dateFrom = NULL ) {
    $query = "SELECT * FROM `job:payperiod`";
    if ( $dateFrom !== NULL ) {
        $query .=  " WHERE (`jPer:DateStart` >= '{$dateFrom}') AND (`jPer:PayPeriodType` = '1')";
    }
    $query .=  " ORDER BY `jPer:DateEnd` DESC";
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    $this->prdBat_count = 0;
    $this->prdBat_items = array();
    while ( $row=$result->fetch_array() ) {
        $newPeriod = new dbRecord_payPeriod;
        $newPeriod->prd_processRow( $appGlobals , $row );
        ++$this->prdBat_count;
        $this->prdBat_items[$newPeriod->prd_payPeriodId] = $newPeriod;
    }
}

function prdBat_readEmployeeOldPeriods( $appGlobals , $employeeId ) {
    // need to make specific for employee
    $dateNow = rc_getNow();
    $query = "SELECT * FROM `job:payperiod`";
    $query .=  " WHERE (`jPer:DateStart` < '{$dateNow}') AND (`jPer:PayPeriodType` = '1')";
    $query .=  " ORDER BY `jPer:DateEnd` DESC";
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    $this->prdBat_count = 0;
    $this->prdBat_items = array();
    while ( $row=$result->fetch_array() ) {
        $newPeriod = new dbRecord_payPeriod;
        $newPeriod->prd_processRow( $appGlobals ,$row );
        ++$this->prdBat_count;
        $this->prdBat_items[$newPeriod->prd_payPeriodId] = $newPeriod;
    }
}

} // end pay period batch class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  dbRecord_payEmployee
//@@@@@@@@@@
//@@@@@

class dbRecord_payEmployee  extends draff_database_record {

const DB_TABLE_NAME  = '';
const DB_TABLE_INDEX = '';
const DB_MOD_WHEN    = '';
const DB_MOD_WHO     = '';
const DB_SELECT_FIELDS  = array();

public $emp_staffId;
public $emp_firstName;
public $emp_lastName;
public $emp_shortName;
public $emp_rateAdmin;
public $emp_rateField;
public $emp_rateSalary;
public $emp_name;       // computed
public $emp_tot_open = 0;  // only used for summary
public $emp_tot_accepted = 0; // only used for summary
public $emp_rec_modWhen;
public $emp_rec_modByStaffId;

static function emp_getFields( &$fields ) {
    $fields[] = 'sSt:StaffId';
    $fields[] = 'sSt:FirstName';
    $fields[] = 'sSt:LastName';
    $fields[] = 'sSt:ShortName';
    $fields[] = 'sSt:DirectorId';
    $fields[] = 'sSt:HiddenStatus';
    $fields[] = 'jEmp:@StaffId';
    $fields[] = 'jEmp:PayRateAdmin';
    $fields[] = 'jEmp:PayRateField';
    $fields[] = 'jEmp:PayRateSalary';
    $fields[] = 'jEmp:PayRateSalary';
    $fields[] = 'jEmp:ModBy@StaffId';
    $fields[] = 'jEmp:ModWhen';
}

static function stdRec_appendSelect($queryCommand) {
    $queryCommand->draff_sql_selectFields('sSt:StaffId');
    $queryCommand->draff_sql_selectFields('sSt:FirstName','sSt:LastName','sSt:ShortName','sSt:DirectorId');
    $queryCommand->draff_sql_selectFields('sSt:HiddenStatus');
    $queryCommand->draff_sql_selectFields('jEmp:@StaffId');
    $queryCommand->draff_sql_selectFields('jEmp:PayRateAdmin','jEmp:PayRateField','jEmp:PayRateSalary');
    $queryCommand->draff_sql_selectFields('jEmp:ModBy@StaffId','jEmp:ModWhen');
}

function emp_processRow( $appGlobals , $row)  {
    $this->emp_staffId = $row['sSt:StaffId'];
    $this->emp_name = $row['sSt:FirstName'] . ' ' . $row['sSt:LastName'] . ' ('. $row['sSt:ShortName'] . ')';
    $this->emp_firstName = $row['sSt:FirstName'];
    $this->emp_lastName  = $row['sSt:LastName'];
    $this->emp_shortName = $row['sSt:ShortName'];
    $this->emp_rec_modWhen = $row['jEmp:ModWhen'];
    $this->emp_rec_modByStaffId = $row['jEmp:ModBy@StaffId'];
    $this->emp_name = $this->emp_firstName . ' ' . $this->emp_lastName . ' ('. $this->emp_shortName . ')';
    $this->emp_rateField  = payData_dollarAmountDecrypt( $this->emp_staffId,$row['jEmp:PayRateField']);  // decrypt
    $this->emp_rateAdmin  = payData_dollarAmountDecrypt( $this->emp_staffId,$row['jEmp:PayRateAdmin']);   // decrypt
    $this->emp_rateSalary = payData_dollarAmountDecrypt( $this->emp_staffId,$row['jEmp:PayRateSalary']);  // decrypt
    //if ( $this->emp_rateAdmin==NULL )  { $this->emp_rateAdmin = 0;}
    //if ( $this->emp_rateField==NULL )  { $this->emp_rateField = 0;}
    //if ( $this->emp_rateSalary==NULL ) { $this->emp_rateSalary = 0;}
   
}

function emp_read( $appGlobals , $staffId ) {
    $fields = array();
    $this->emp_getFields($fields);
    $fieldList = "`".implode("`,`",$fields)."`";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
    $sql[] = "FROM `st:staff`";
    $sql[] = "LEFT JOIN `job:employee` ON (`jEmp:@StaffId` = `sSt:StaffId`)";
    $sql[] = "WHERE `sSt:StaffId` = '{$staffId}'";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    $row=$result->fetch_array();
    $this->emp_processRow( $appGlobals , $row );
}

function emp_saveRecord( $appGlobals ) {
    $data = array();
    $data['jEmp:@StaffId']          = $this->emp_staffId;
    $data['jEmp:PayRateField']      = payData_dollarAmountEncrypt( $this->emp_staffId,$this->emp_rateField);
    $data['jEmp:PayRateAdmin']      = payData_dollarAmountEncrypt( $this->emp_staffId,$this->emp_rateAdmin);
    $data['jEmp:PayRateSalary']     = payData_dollarAmountEncrypt( $this->emp_staffId,$this->emp_rateSalary);
    $recId = $appGlobals->gb_sql->sql_saveRecord( 'job:employee' , 'jEmp:' , 'jEmp:@StaffId' , $this->emp_staffId, $data , RGB_HISTORY_SAVE_TRUE);  //,$this->emp_newRecord
}

} // end employee item class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  payData_employee_batch
//@@@@@@@@@@
//@@@@@

class payData_employee_batch {
public $epyBat_empoyeeArray = array();
public $epyBat_tot_open = 0;
public $epyBat_tot_accepted = 0;
public $epyBat_tot_void = 0;

function epyBat_read_summary( $appGlobals , $pPayPeriod = NULL ) {
    $payPeriod = is_null($pPayPeriod) ? $appGlobals->gb_period_current; : $pPayPeriod;
    // this batch consists of all employees who are current or have transactions in the specified period
    $this->epyBat_tot_open = 0;
    $this->epyBat_tot_accepted = 0;
    $this->epyBat_tot_void = 0;
    $now = rc_getNow();
    $recentDate = draff_dateIncrement( $now , -42);  // date to display someone even if hidden
    $sql = array();
    $sql[] = "SELECT *, SUM(`jTsk:PayStatusCode`='".PAY_PAYSTATUS_UNAPPROVED."') AS `totOpen`, SUM(`jTsk:PayStatusCode`='".PAY_PAYSTATUS_APPROVED."') AS `totAccept`";
    //$sql[] = "SELECT , SUM(`jTsk:jTsk:JobRateCode`='3') AS `totSalary`,SUM(`jTsk:jTsk:JobRateCode`<>'3') AS `totNotSalary`";
    $sql[] = "FROM `st:staff`";
    $sql[] = "LEFT JOIN `job:task` ON (`jTsk:@StaffId` = `sSt:StaffId`)";
    //$sql[] = "    AND (`jTsk:JobTaskId` = `jTsk:Final@JobTaskId`)";
    $sql[] = "    AND (`jTsk:@PayPeriodId` = '{$payPeriod->prd_payPeriodId}')";
    $sql[] = "LEFT JOIN `job:employee`   ON `jEmp:@StaffId` = `sSt:StaffId`";
    $sql[] = "WHERE (`sSt:HiddenStatus`= '0') OR (`sSt:ModWhen`>'{$recentDate}'  OR (`jTsk:JobTaskId`>=1) )";
    $sql[] = "GROUP BY `sSt:StaffId`";
    $sql[] = "ORDER BY `sSt:FirstName`, `sSt:LastName`,`jTsk:JobTaskId`,`sSt:HiddenStatus` ";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
    $this->epyBat_empoyeeArray = array();
    while ($row=$result->fetch_array()) {
        $employee = new dbRecord_payEmployee;
        $employee->emp_processRow($appGlobals ,$row);
        $this->epyBat_empoyeeArray[$employee->emp_staffId] = $employee;
        $employee->emp_tot_open = isset($row['totOpen']) ? $row['totOpen'] : 0;
        $employee->emp_tot_accepted = isset($row['totAccept']) ? $row['totAccept'] : 0;
        $this->epyBat_tot_open     += $employee->emp_tot_open;
        $this->epyBat_tot_accepted += $employee->emp_tot_accepted;
   }
}

function epyBat_read_all( $appGlobals ) {
    $sql = array();
    $sql[] = "SELECT *";
    $sql[] = "FROM `job:employee`";
    $sql[] = "LEFT JOIN `st:staff` ON `sSt:StaffId` = `jEmp:@StaffId`";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
    $this->epyBat_empoyeeArray = array();
    while ( $row=$result->fetch_array() ) {
        $employee = new dbRecord_payEmployee;
        $employee->emp_processRow($appGlobals ,$row);
        $this->epyBat_empoyeeArray[$employee->emp_staffId] = $employee;
    }
}

static function epyBat_get_array( $appGlobals , $employeeId=NULL ) {
    $sql = array();
    $sql[] = "SELECT *";
    $sql[] = "FROM `job:employee`";
    $sql[] = "LEFT JOIN `st:staff` ON `sSt:StaffId` = `jEmp:@StaffId`";
    if ( $employeeId !== NULL) {
        $sql[] = "WHERE `jEmp:@StaffId` = '{$employeeId}'";
    }
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    $employeeArray = array();
    while ($row=$result->fetch_array()) {
        $employee = new dbRecord_payEmployee;
        $employee->emp_processRow($appGlobals ,$row);
        $employeeArray[$employee->emp_staffId] = $employee;
    }
    return $employeeArray;
}

} // end employee batch class

class payDif {

static private $isDifferent = FALSE;

static function start () {
    self::$isDifferent = FALSE;
}

static function dif ( $value1 , $value2 ) {
    if ( $value1 != $value2 ) {
        self::$isDifferent = TRUE;
    }
}

static function isDifferent () {
    return self::$isDifferent;
}

} // end assign class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@@@@@
//@@@@@@@@@@
//  payData_factory
//@@@@@@@@@@
//@@@@@

class payData_factory {

static function payFactory_get_taskGroup_new_blank( $appGlobals, $employeeId, $originCode, $periodId = 0 ) {
    // if no period ID, must wait until there's a date (until then tskItem_ payPeriodId is NULL)
    $appGlobals->gb_load_global_employees( $employeeId );
    $taskGroup = new payData_task_group;
    $jobOnly = $taskGroup->tskGrp_createNewFinalItem();
    $jobOnly->tskItem_staffId    = $employeeId;
    $jobOnly->tskItem_originCode = $originCode;
    if ( $periodId != 0 ) {
        $jobOnly->tskItem_setPeriod_bySpecifiedPeriod( $appGlobals, $periodId);
    }
    return $taskGroup;
}

static function payFactory_get_taskGroup_new_travel( $appGlobals,$jobPrevGroup, $jobNextGroup ) {
    // pay period is always current pay period - calling this is invalid for other periods
    $jobPrevFinal = $jobPrevGroup->tskGrp_get_finalItem();
    $jobNextFinal = $jobNextGroup->tskGrp_get_finalItem();
    if ( $jobPrevFinal->tskItem_payPeriodId != $appGlobals->gb_period_current;->prd_payPeriodId ) {
        return NULL;
    }
    if ( $appGlobals->gb_period_current;->prd_periodType != RC_PAYPERIOD_NORMAL ) {
        return NULL;
    }
    $taskGroup = new payData_task_group;
    $taskFinal = $taskGroup->tskGrp_createNewFinalItem();
    $taskFinal->tskItem_jobItemId            = 0;
    $taskFinal->tskItem_originCode           = RC_JOB_ORIGIN_TRAVEL;
    $taskFinal->tskItem_job_date             = $jobPrevFinal->tskItem_job_date;
    $taskFinal->tskItem_job_time_start       = $jobPrevFinal->tskItem_job_time_end;
    $taskFinal->tskItem_job_time_end         = $jobNextFinal->tskItem_job_time_start;
    $taskFinal->tskItem_override_timeMethod  = 0;
    $taskFinal->tskItem_override_timeMinutes = 0;
    $taskFinal->tskItem_job_atendanceCode    = RC_JOB_ATTEND_PRESENT;
    $taskFinal->tskItem_job_location         = 'Travel between ' . $jobPrevFinal->tskItem_job_location  . ' and ' . $jobNextFinal->tskItem_job_location ;
    $taskFinal->tskItem_job_notes            = '';
    $taskFinal->tskItem_job_rateCode         = 0;
    $taskFinal->tskItem_override_rateAmount  = 0;
    $taskFinal->tskItem_override_explanation = '';
    $taskFinal->tskItem_staffId              = $jobPrevFinal->tskItem_staffId;
    $taskFinal->tskItem_scheduleStatus       = RC_JOB_SCHEDSTATUS_NOT_EVENT;
    $taskFinal->tskItem_payStatus            = PAY_PAYSTATUS_UNAPPROVED;
    $taskFinal->tskItem_travel_prevJobItemId = $jobPrevFinal->tskItem_taskId;
    $taskFinal->tskItem_travel_nextJobItemId = $jobNextFinal->tskItem_taskId;
    $taskFinal->tskItem_event_eventId        = NULL;
    $taskFinal->tskItem_setPeriod_bySpecifiedPeriod( $appGlobals, $jobPrevFinal->tskItem_payPeriodId );
    return $taskGroup;
}

static function payFactory_get_taskGroup_fromDb( $appGlobals, $taskGroupId ) {
    $taskGroupReader = new payData_task_reader;
    $taskGroup = $taskGroupReader->tskReader_getFirst( $appGlobals, NULL, NULL, $taskGroupId );
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $appGlobals->gb_load_global_employees( $taskFinal->tskItem_staffId );
    return $taskGroup;
}

static function payFactory_get_taskGroup_fromRows( $appGlobals, $rows ) {
    $JobGroup = new payData_task_group;
    foreach ( $rows as $itemRow ) {
        $taskItem = new payData_task_item;
        $taskItem->tskItem_process_row( $appGlobals , $itemRow );
        $JobGroup->tskGrp_addItem($taskItem);
    }
    $origin = array();
    foreach ($JobGroup->tskGrp_jobItem_array as $item) {
        $origin[] = $item->tskItem_originCode;
    }
    array_multisort ($origin,$JobGroup->tskGrp_jobItem_array);
    return $JobGroup;
}

static function payFactory_get_taskGroup_withClonedOverride( $appGlobals, $taskGroupId, $newOriginCode ) {
    // read from db and then clone FinalItem
    $taskGroup = self::payFactory_get_taskGroup_fromDb( $appGlobals , $taskGroupId);
    $newTaskFinal =  clone $taskGroup->tskGrp_get_finalItem();
     // change taskId and originCode on cloned item
    $newTaskFinal->tskItem_taskId = 0;
    $newTaskFinal->tskItem_originCode = $newOriginCode;
    $taskGroup->tskGrp_addItem($newTaskFinal);
    return $taskGroup;
}

static function payFactory_get_payPeriod_open( $appGlobals ) {
    $periodPM  = self::payFactory_get_payPeriod_openOfKey( $appGlobals, 'OpenPeriod_PayMaster' );
    $periodEmr = self::payFactory_get_payPeriod_openOfKey( $appGlobals, 'OpenPeriod_Staff' );
    $appGlobals->gb_period_payMasterId = $periodPM->prd_payPeriodId;
    $appGlobals->gb_period_staffId     = $periodEmr->prd_payPeriodId;
    $payPeriod = ( $appGlobals->gb_proxyIsPayMaster ) ? $periodPM : $periodEmr;
    if ( $payPeriod->prd_periodType == RC_PAYPERIOD_SPECIAL ) {
        $appGlobals->gb_period_current;_type = PAY_PERIODOPEN_SPECIAL;
    }
    else if ( $appGlobals->gb_period_payMasterId == $appGlobals->gb_period_staffId ) {
        $appGlobals->gb_period_current;_type = PAY_PERIODOPEN_ALL;
    }
    else if ( $payPeriod->prd_payPeriodId == $appGlobals->gb_period_staffId ) {
        $appGlobals->gb_period_current;_type = PAY_PERIODOPEN_STAFF;
    }
    else if ( $payPeriod->prd_payPeriodId == $appGlobals->gb_period_payMasterId ) {
        $appGlobals->gb_period_current;_type = PAY_PERIODOPEN_PM;
    }
    else {
        $appGlobals->gb_period_current;_type = PAY_PERIODOPEN_ALL;  // should never happen ?????????
   }
   return $payPeriod;
}

static private function payFactory_get_payPeriod_openOfKey( $appGlobals, $periodTypeKey ) {
    $payPeriodId  = payData_status_get( $appGlobals, $periodTypeKey, NULL );;
    // Try to get period specified in payStatus (if any)
    if ( $payPeriodId !== NULL ) {
        $query = "SELECT * FROM `job:payperiod` WHERE `jPer:PayPeriodId` = '{$payPeriodId}'";
        $result = $appGlobals->gb_sql->sql_performQuery( $query );
        if ( $result->num_rows == 1) {
            $row = $result->fetch_array();
            $payPeriod = new dbRecord_payPeriod;
            $payPeriod->prd_processRow( $appGlobals, $row );
            return $payPeriod;
        }
    }
    // No pay period specified - try to get first non-closed period (if any)
    $query = "SELECT * FROM `job:payperiod` WHERE (`jPer:WhenClosed` IS NULL) AND (`jPer:PayPeriodType` = '1') ORDER BY `jPer:DateStart` LIMIT 1";
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    if ( $result->num_rows == 1) {
        $row = $result->fetch_array();
        $payPeriod = new dbRecord_payPeriod;
        $payPeriod->prd_processRow( $appGlobals, $row );
        payData_status_set( $appGlobals, $periodTypeKey, $payPeriod->prd_payPeriodId );
        return $payPeriod;
    }
    // There are no open pay periods
    $query = "SELECT * FROM `job:payperiod` WHERE (`jPer:PayPeriodType` = '1') ORDER BY `jPer:DateEnd` LIMIT 1";
    $result = $appGlobals->gb_sql->sql_performQuery($query ,__FILE__ , __LINE__);
    if ( $result->num_rows == 1 ) {
        $row = $result->fetch_array();
        $endDate = $row['jPer:DateEnd'];
        $startDate = draff_dateIncrement( $endDate , 1 );
    }
    else {
        $startDate = RC_PAYPERIOD_EARLIEST_DATE;
    }
    $payPeriod = self::payFactory_get_payPeriod_new( $appGlobals, $startDate );
    return $payPeriod;
}

static function payFactory_get_payPeriodId_ofDate( $appGlobals, $jobDate ) {
    // forces period record(s) to be created if necessary for the specified date
    if ( $jobDate < RC_PAYPERIOD_EARLIEST_DATE) {
        return 0;
    }
    $query = "SELECT * FROM `job:payperiod` WHERE ('{$jobDate}' BETWEEN `jPer:DateStart` AND `jPer:DateEnd`) AND (`jPer:PayPeriodType` = '1')";
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    if ( $result->num_rows == 1) {
        $row = $result->fetch_array();
        return $row['jPer:PayPeriodId'];
    }
    $lastDateAllowed = draff_dateIncrement(rc_getNowDate(),RC_JOB_MAX_ADVANCE_DAYS);
    if ( $jobDate > $lastDateAllowed ) {
        draff_errorTerminate('Invalid Date Specified '.$jobDate);
    }
    $query = "SELECT * FROM `job:payperiod` WHERE `jPer:PayPeriodType` = '1' ORDER BY `jPer:DateEnd` DESC LIMIT 1";
    $result = $appGlobals->gb_sql->sql_performQuery( $query );
    if ( $result->num_rows != 1) {
        draff_errorTerminate( 'No rows found in pay period table' );
    }
    $row = $result->fetch_array();
    $periodEndDate = $row['jPer:DateEnd'];
    if ( $jobDate <= $periodEndDate) {
        draff_errorTerminate( "Pay Period Period Missing - the date {$jobDate} is before the last period end date {$periodEndDate}" );
    }
    do {
        $periodStartDate = draff_dateIncrement( $periodEndDate   ,  1 );
        $periodEndDate   = draff_dateIncrement( $periodStartDate , 13 );
        $newPeriod = payData_factory::payFactory_get_payPeriod_new( $appGlobals , $periodStartDate );
    } while ( $periodEndDate < $jobDate );
    return $newPeriod->prd_payPeriodId;
}

static function payFactory_get_payPeriod_new( $appGlobals , $startDate ) {
    $payPeriod = new dbRecord_payPeriod;
    $payPeriod->prd_payPeriodId   = 0;
    $payPeriod->prd_periodType    = 1;
    $payPeriod->prd_periodName    = 0;
    $payPeriod->prd_dateStart     = $startDate;
    $payPeriod->prd_dateEnd       = draff_dateIncrement( $startDate , 13 );
    $payPeriod->prd_whenClosed    = NULL;
    $payPeriod->prd_setName( $appGlobals );
    $payPeriod->prd_saveRecord( $appGlobals );
    return $payPeriod;
}

static function payFactory_get_employeeItem( $appGlobals , $employeeId , $row=NULL ) {
    // results are cached in globals so each employee is retrieved only once
    if ( isset($appGlobals->gb_employeeArray[$employeeId]) ) {
        return $appGlobals->gb_employeeArray[$employeeId]; // already exists in globals
    }
    else {
        $employee = new dbRecord_payEmployee;
        if ( is_array($row) ) {
             $employee->emp_processRow( $appGlobals ,$row );
       }
        else {
            $employee->emp_read( $appGlobals ,$employeeId );
        }
        $appGlobals->gb_employeeArray[$employeeId] = $employee;
        return $employee;
    }
}

} // end factory class


function pay_calcStaffTimeFromEventTime( $eventStart , $eventEnd , $roleCode , $programType ) {
    // 1=class 2=camp 3=tournament 4=Special Event with registrations 9=Non-Period Non-registration event for schedule
    // this only works if the person is scheduled to be there for the entire event
    //      and for classes only
    //  for non classes this outputs entire time of event (the default)

    $isSm = ($roleCode==RC_ROLE_SITEMANAGER);
    switch ( $programType ) {
        case 1: // class
                $timeBefore = $isSm ? EVTADJUST_CLASS_BEFORE_SM : EVTADJUST_CLASS_BEFORE_CO;
                $timeAfter  = $isSm ? EVTADJUST_CLASS_AFTER_SM : EVTADJUST_CLASS_AFTER_CO;
                $timePrep   = $isSm ? EVTADJUST_CLASS_AFTER_SM : EVTADJUST_CLASS_AFTER_CO;
                $timePrep = 0;
                if ($isSm) {
                    $diff = draff_timeMinutesDif( $eventStart,$eventEnd);
                    $timePrep =  ($diff<=EVTADJUST_CLASS_1PERLENGTH ) ? EVTADJUST_CLASS_1PER_SMPREP : EVTADJUST_CLASS_2PER_SMPREP;
                }
                break;
        case 2: // camp
                $timeBefore = $isSm ? EVTADJUST_CAMP_BEFORE_SM : EVTADJUST_CAMP_BEFORE_CO;
                $timeAfter  = $isSm ? EVTADJUST_CAMP_AFTER_SM : EVTADJUST__CAMPAFTER_CO;
                $timePrep = 0;
                break;
        case 3: // tournament
                $timeBefore = $isSm ? EVTADJUST_TOURN_BEFORE_SM : EVTADJUST_TOURN_BEFORE_CO;
                $timeAfter  = $isSm ? EVTADJUST_TOURN_AFTER_SM : EVTADJUST_TOURN_AFTER_CO;
                $timePrep = 0;
                break;
        default:
                $timeBefore = $isSm ? EVTADJUST_OTHER_BEFORE_SM : EVTADJUST_OTHER_BEFORE_CO;
                $timeAfter  = $isSm ? EVTADJUST_OTHER_AFTER_SM :  EVTADJUST_OTHER_AFTER_CO;
                $timePrep = 0;
                break;
        
    }
    $timeStart = draff_timeIncrement($eventStart,-$timeBefore);
    $timeEnd   = draff_timeIncrement($eventEnd,$timeAfter);
    switch ( $roleCode ) {
        case RC_ROLE_SITEMANAGER: $role = 'Site Manager';  break;
        //case SCHED_ROLE_SM_ACT: $role = 'Acting Site Manager';  break;
        case RC_ROLE_ASSTMANAGER: $role = 'Assistant Site Manager';  break;
        case RC_ROLE_HEADCOACH: $role = 'Head Coach';  break;
        //case SCHED_ROLE_HC_ACT: $role = 'Acting Head Coach';  break;
        case RC_ROLE_COACH: $role = 'Coach';  break;
        default: $role = 'Other';
    }
    $res = array();
    $res['start']      = $timeStart;
    $res['end']        = $timeEnd;
    $res['prepTime']   = $timePrep;
    $res['role'] = $role;
    return $res;
}

function payData_status_set( $appGlobals , $key , $value ) {
    $query = "REPLACE INTO `job:systemstatus` (`jSys:StatusKey`,`jSys:StatusValue`) values('{$key}', '{$value}')";
    $result = $appGlobals->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
}

function payData_status_get( $appGlobals , $key,$default=NULL ) {
   $row = $appGlobals->gb_sql->sql_readSingleRecord( '*', 'job:systemstatus', 'jSys:StatusKey', $key, __FILE__, __LINE__ );
   return ( isset($row['jSys:StatusValue']) ) ? $row['jSys:StatusValue'] : $default;
}

function payData_dollarAmountEncrypt( $staffId , $amount ) {
    // call this before writing a dollar amount to the database
    if (RC_TESTING) {  // un-adjust amount to restore to real value
        $amount = $amount / payData_getDollarAmountFactor( $staffId );
    }
        // convert number to a string and
        // add precision to prevent round-off errors: 8 seems to be enough
    $amountStr = number_format ( $amount, 8 , ".", "" );
    $encryptedAmount = payData_XORCipher( $amountStr );
    return $encryptedAmount;
}

function payData_dollarAmountDecrypt( $staffId , $encryptedDollars ) {
    // call this after reading a dollar amount from the database
    $amount = payData_XORCipher( $encryptedDollars );
    if (empty($amount)) {
        $amount = 0;
    }
    if (RC_TESTING) {  // adjust amount to obscure actual value
        $amount = $amount * payData_getDollarAmountFactor( $staffId );
    }
    $amount = floatval($amount);  // force to floating point,not string
    return number_format ( $amount, 2 , ".", "" );
}

function payData_XORCipher( $str ) {
    // encrypt/decrypt a string using XOR encryption
    // IMPORTANT: If encrypting a number, convert it to a string before
    //            calling this function!
    $key = "OBFUSCATION";
    $keyLen = strlen( $key );
    $strLen = strlen( $str );
    $result = "";
    for ( $i = 0; $i < $strLen; ++$i ) {
        $result .= $str[$i] ^ $key[ $i % $keyLen ];
    }
    return $result;
}

function payData_getDollarAmountFactor( $staffId ) {
    // get the multiplication/devision to use with dollar amounts
    // associated with $staffId
    return (1 + (hexdec( substr( md5( $staffId ), 0, 1 ) ) / 8));
}

?>