<?php

// pay-report-ledger.inc.php
// Internally the classes used to produce this report are known as "Ledger"

// Standard variable names
// $taskItem - any job item, same record is used in scheduling and payroll
// $taskGroup - group of job items
// $taskFinal - the final record in the job group (the other records in the group have been overridden)

class ledgerReport_task_description_field {
public $tkdFld_value;
public $tkdFld_mode; // 'f'=final value, 's'=strikeOut value, ' ' = default
public $tkdFld_finalOnly = FALSE; // do not show stikeouts

function __construct() {
}

}


class ledgerReport_task_description_item {
public $tkdItm_originAbbr = '++';
public $tkdItm_originDesc;
public $tkdItm_originCssAbbr;
public $tkdItm_date;
public $tkdItm_eventArrive;
public $tkdItm_eventDepart;
public $tkdItm_eventRole;
public $tkdItm_timeAttendance;
public $tkdItm_timeStart;
public $tkdItm_timeEnd;
public $tkdItm_timeAll;
public $tkdItm_timeRange;
public $tkdItm_timeOverrideMethod;
public $tkdItm_timeOverrideValue;
public $tkdItm_jobRate = '$$$';
public $tkdItm_jobLocation;
public $tkdItm_jobDescription;
public $tkdItm_rateMethod;
public $tkdItm_rateValue;
public $tkdItm_adjustExplain;
public $tkdItm_payHours;
public $tkdItm_payRate;
public $tkdItm_payAmount;
private $tkdItm_mode;
private $tkdFld_origin;

function __construct( $taskItem , $finalRecord = FALSE ) {
   switch ( $taskItem->tskItem_originCode ) {
        case RC_JOB_ORIGIN_SCHEDULE:
            $orgAbbr = 'Sch';
            $orgDesc = 'Schedule';
            $orgCss  = '';
            break;
        case RC_JOB_ORIGIN_SM:
            $orgAbbr = 'SM';
            $orgDesc = 'Site Manager';
            $orgCss  = '';
            break;
        case RC_JOB_ORIGIN_STAFF:
            $orgAbbr = 'Emp';
            $orgDesc = 'Employee';
            $orgCss  = '';
            break;
        case RC_JOB_ORIGIN_TRAVEL:
            $orgAbbr = 'Trv';
            $orgDesc = 'Travel';
            $orgCss  = '';
            break;
        case RC_JOB_ORIGIN_SALARY:
            $orgAbbr = 'Sal';
            $orgDesc = 'Salary';
            $orgCss  = '';
            break;
        case RC_JOB_ORIGIN_PM:
            $orgAbbr = 'PM';
            $orgDesc = 'Payroll Manager';
            $orgCss  = '';
            break;
        default:  // should never get here
            $orgAbbr = '??';
            $orgDesc = '??';
            $orgCss  = '';
            break;
    }
    $this->tkdItm_origin = $orgAbbr;
    switch ( $taskItem->tskItem_event_roleCode ) {
        case RC_ROLE_SITEMANAGER:      $roleDesc = 'Site Manager'; break;
        case RC_ROLE_ASSTMANAGER:  $roleDesc = 'Assistant Site Manager'; break;
        //case SCHED_ROLE_SM_ACT:  $roleDesc = 'Acting Site Manager'; break;
        case RC_ROLE_HEADCOACH:      $roleDesc = 'Head Coach'; break;
        //case SCHED_ROLE_HC_ACT:  $roleDesc = 'Acting Head Coach'; break;
        case RC_ROLE_COACH:   $roleDesc = 'Coach'; break;
        default:                 $roleDesc = 'Other'; break;
    }
    switch ( $taskItem->tskItem_job_atendanceCode ) {
       case RC_JOB_ATTEND_PRESENT:  $attendanceDesc = 'Present'; break;
       case RC_JOB_ATTEND_PIF:      $attendanceDesc = 'Present (PIF)'; break;
       case RC_JOB_ATTEND_ABSENT_UNEXCUSED:   $attendanceDesc = 'Absent'; break;
       case RC_JOB_ATTEND_SICK:     $attendanceDesc = 'Sick'; break;
       case RC_JOB_ATTEND_VACATION: $attendanceDesc = 'Vacation'; break;
       default:                  $attendanceDesc = ''; break;
    }
    $this->tkdItm_mode         =  ($finalRecord) ? 'f' : 's' ;
    $this->tkdItm_date         = $this->tkdItm_createField(draff_dateAsString($taskItem->tskItem_job_date,'D, M j, Y') );
    $this->tkdItm_eventArrive  = $this->tkdItm_createField(draff_timeAsString($taskItem->tskItem_event_timeArrive) );
    $this->tkdItm_eventDepart  = $this->tkdItm_createField(draff_timeAsString($taskItem->tskItem_event_timeDepart) );
    $this->tkdItm_timeStart    = $this->tkdItm_createField(draff_timeAsString($taskItem->tskItem_job_time_start) );
    $this->tkdItm_timeEnd      = $this->tkdItm_createField(draff_timeAsString($taskItem->tskItem_job_time_end) );
    $this->tkdItm_timeRange    = $this->tkdItm_createField($this->tkdItm_getString_timeRange($taskItem) );
    $this->tkdItm_timeAll      = $this->tkdItm_createField($this->tkdItm_getString_timeComplete($taskItem) );
    $this->tkdItm_timeOverrideMethod = $this->tkdItm_createField('@@@' );
    $this->tkdItm_timeOverrideValue = $this->tkdItm_createField('@@@' );
    $this->tkdItm_jobLocation    = $this->tkdItm_createField($taskItem->tskItem_job_location );
    $this->tkdItm_jobDescription = $this->tkdItm_createField($taskItem->tskItem_job_notes );
    $this->tkdItm_rateMethod     = $this->tkdItm_createField('@@@' );
    $this->tkdItm_rateValue      = $this->tkdItm_createField('@@@' );
    $this->tkdItm_adjustExplain  = $this->tkdItm_createField($taskItem->tskItem_override_explanation );
    $this->tkdItm_eventRole      = $this->tkdItm_createField($roleDesc );
    $this->tkdItm_timeAttendance = $this->tkdItm_createField($attendanceDesc );
     $this->tkdItm_originAbbr    = $this->tkdItm_createField($orgAbbr );
    $this->tkdItm_originDesc    = $this->tkdItm_createField($orgDesc );
    $this->tkdItm_originCssAbbr = $this->tkdItm_createField($orgCss );
    $this->tkdItm_jobRate     =  $this->tkdItm_createField($this->tkdItm_getString_jobRate($taskItem) );
    //$this->tkdItm_payHours  =  $this->tkdItm_createField($finalRecord ? draff_minutesAsString($taskItem->tskItem_pay_final_minutes) : '' );
    //$this->tkdItm_payRate   =  $this->tkdItm_createField($finalRecord ? draff_dollarsAsString($taskItem->tskItem_pay_final_rate) : '' );
    //$this->tkdItm_payAmount =  $this->tkdItm_createField($finalRecord ? draff_dollarsAsString($taskItem->tskItem_pay_final_amount) : '' );
    $this->tkdItm_payHours  =  $this->tkdItm_createField(draff_minutesAsString($taskItem->tskItem_pay_final_minutes) , 'f'  );
    $this->tkdItm_payRate   =  $this->tkdItm_createField(draff_dollarsAsString($taskItem->tskItem_pay_final_rate) , 'f'  );
    $this->tkdItm_payAmount =  $this->tkdItm_createField(draff_dollarsAsString($taskItem->tskItem_pay_final_amount) , 'f' );
    $this->tkdItm_changeIfEmpty($this->tkdItm_payHours,'0:00');
}

function tkdItm_createField($value, $isFinalOnly = FALSE) {
    $newField = new ledgerReport_task_description_field;
    $newField->tkdFld_value = $value == NULL ? '' : $value;
    $newField->tkdFld_mode  = $this->tkdItm_mode;
    $newField->tkdFld_finalOnly = $isFinalOnly;
    $newField->tkdFld_origin = $this->tkdItm_origin;
    return $newField;
}

function tkdItm_changeIfEmpty(&$field, $newValue) {
     if (empty($field->tkdFld_value)) {
         $value = $newValue;
     }
}

function tkdItm_getString_timeComplete( $taskItem ) {
    switch($taskItem->tskItem_job_atendanceCode) {
        case RC_JOB_ATTEND_NA:        return 'N/A'; break;
        case RC_JOB_ATTEND_SICK:      return 'Sick'; break;
        case RC_JOB_ATTEND_VACATION:  return 'Vacation'; break;
        case RC_JOB_ATTEND_ABSENT_UNEXCUSED:    return 'Absent'; break;
    }
    $timeRange = $this->tkdItm_getString_timeRange($taskItem);
    if ( $taskItem->tskItem_event_prepTime >= 1 ) {
       $timeRange .= '+Prep:'.$taskItem->tskItem_event_prepTime;
    }
    $pif = ($taskItem->tskItem_job_atendanceCode==RC_JOB_ATTEND_PIF) ? 'PIF ' : '';
    switch ( $taskItem->tskItem_override_timeMethod ) {
        case PAY_TIMEOVERRIDE_FALSE   : return $pif . $timeRange; break;
        case PAY_TIMEOVERRIDE_TRUE  : return ' =' . draff_minutesAsString($taskItem->tskItem_override_timeMinutes); break;
        //case PAY_TIMEADJUST_ADDITIONAL: return $timeRange . '+' . draff_minutesAsString($taskItem->tskItem_override_timeMinutes); break;
        //case PAY_TIMEADJUST_MINUS     : return $timeRange . '-' . draff_minutesAsString($taskItem->tskItem_override_timeMinutes); break;
        default: return '????';
    }
}

function tkdItm_getString_timeRange( $taskItem ) {
    $t1 = $taskItem->tskItem_job_time_start;
    $t2 = $taskItem->tskItem_job_time_end;
    if ( empty($t1) and empty($t2) ) {
        $out =  'n/a';
    }
    else if ( empty($t1) or empty($t2) ) {
        $out =  '???';
    }
    else {
        $time1 = date_create_from_format( 'H:i:s', $t1 );
        $time2 = date_create_from_format( 'H:i:s', $t2 );
        if ( ($t1<'12:00:00') and ($t2<'12:00:00') ) {
           $out = date_format( $time1, 'g:i' ) . '-' .  date_format( $time2, 'g:i a' );
        }
        else if ( ($t1>='12:00:00') and ($t2>='12:00:00')) {
           $out = date_format( $time1, 'g:i' ) . '-' . date_format( $time2, 'g:i a' );
        }
        else {
           $out = date_format( $time1, 'g:ia' ) . '-' .  date_format( $time2, 'g:i a' );
        }
    }
    return $out;
}

function tkdItm_getString_timeAdjustment( $taskItem ) {
    switch( $taskItem->tskItem_override_timeMethod ) {
        case PAY_TIMEOVERRIDE_FALSE   : $desc = 'n/a'; break;
        case PAY_TIMEOVERRIDE_TRUE  : $desc = ' =' . draff_minutesAsString($taskItem->tskItem_override_timeMinutes); break;
        //case PAY_TIMEADJUST_ADDITIONAL: $desc = ' +' . draff_minutesAsString($taskItem->tskItem_override_timeMinutes); break;
        //case PAY_TIMEADJUST_MINUS     : $desc = ' -' . draff_minutesAsString($taskItem->tskItem_override_timeMinutes); break;
        default: $desc .= '??';  break;
    }
    return $desc;
}

function tkdItm_getString_jobRate( $taskItem ) {
    //$rate = draff_dollarsAsString( $taskItem->tskItem_pay_final_rate );
    switch($taskItem->tskItem_job_rateCode) {
        case  PAY_RATEMETHOD_ADMIN: $desc = 'Admin'; break;
        case  PAY_RATEMETHOD_FIELD: $desc = 'Field'; break;
        case  PAY_RATEMETHOD_SALARY: $desc = 'Salary'; break;
        case  PAY_RATEMETHOD_OVERRIDE_RATE: $desc = 'Rate='.draff_dollarsAsString($taskItem->tskItem_override_rateAmount); break;
        case  PAY_RATEMETHOD_OVERRIDE_AMOUNT: $desc = 'Pay='.draff_dollarsAsString($taskItem->tskItem_override_rateAmount); break;
        case  0: $desc = 'n/a'; break;
        default: $desc = '???'; break;
    }
}

} // end class


class ledgerReport_job_description_group {
public $tkdGrp_array = array();
//public $tkdGrp_changes = array();
//public $tkdGrp_curValues;
public $tkdGrp_varArray;
public $tkdGrp_finalItem = NULL;

function __construct( $taskGroup ) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $this->varArray = get_class_vars('ledgerReport_task_description_item');
    
    $taskLast = count($taskGroup->tskGrp_jobItem_array) - 1;
    for ( $i=0; $i<=$taskLast; ++$i ) {
        $taskItem = $taskGroup->tskGrp_jobItem_array[$i];
        $this->tkdGrp_array[] = new ledgerReport_task_description_item($taskItem, ($i==$taskLast) );
    }
    $this->tkdGrp_finalItem = $this->tkdGrp_array [count($this->tkdGrp_array)-1];
}

function jdg_get_displayHtml ( $varName, $includeFinal = TRUE ) {
    $fields = array();
    foreach ($this->tkdGrp_array as $descItem) {
        $fields[] = $descItem->$varName;
    }
    $noDupFields = array();
    $curValue = NULL;
    $curField = NULL;
    foreach ($fields as $field) {
        $value = $field->tkdFld_value;
        if ( $value !== $curValue) {
            $noDupFields[] = $field;
            $curValue = $value;
            $curField = $field;
        }
        else {
            $curField->tkdFld_origin .= ',' . $field->tkdFld_origin;
       }
    }
    //var_dump($noDupFields);
    $noDupCount = count($noDupFields);
    if ($noDupCount>=1) {
        $noDupFields[ $noDupCount-1]->tkdFld_mode = 'f';
    }
    $html = '';
    foreach ($noDupFields as $field) {
        $value = $field->tkdFld_value;
        if ($noDupCount > 1) {
            $value = $value . ' (' . $field->tkdFld_origin . ')';
        }
        $mode =  $field->tkdFld_mode;
        $finalOnly = $field->tkdFld_finalOnly;
        // different color/style for each origin type ?
        if ( $includeFinal and ($mode=='f') ) {
            $html .= '<span class="final">' .$value . '</span><br>';
        }
        else if (!$finalOnly and ($mode=='s') ) {
            $html .= '<span class="strikeOut">' .$value . '</span><br>';
        }
    }
    return $html;
}

    //       $trail .= $pre . $s;
    //        $pre = ' and ';
        // switch ($taskGroup->tskItem_originCode) {
        //     case RC_JOB_ORIGIN_SCHEDULE: $org = '???'; break;
        //     case RC_JOB_ORIGIN_SM:       $org = "(Site Manager override of {$trail})"; break;
        //     case RC_JOB_ORIGIN_STAFF:    $org = "(Employee override of {$trail})"; break;
        //     case RC_JOB_ORIGIN_TRAVEL:   $org = "???"; break;
        //     case RC_JOB_ORIGIN_SALARY:   $org = "???"; break;
        //     case RC_JOB_ORIGIN_PM: $org = "(Payroll Manager override of {$trail})"; break;
        //     default: $org='';
        // }
} // end class

class ledgerReport_lib {

static function ldgLib_common_cssInit( $emitter ) {
    $emitter->emit_options->addOption_styleTag('span.strikeOut','display:inline-block;text-decoration: line-through;line-height:10pt;font-size:1rem;font-weight:normal; margin:0pt 0pt 0pt 10pt;');
    $emitter->emit_options->addOption_styleTag('button.loc-approved','background-color:#ccffcc !important;');
    $emitter->emit_options->addOption_styleTag('button.loc-unapproved','background-color:#ffcccc !important;border:3px double black;');
    $emitter->emit_options->addOption_styleTag('div.loc-approved','background-color:#ccffcc;');
    $emitter->emit_options->addOption_styleTag('div.loc-unapproved','background-color:#ffcccc;');
}

static function ldgLib_get_originDesc( $taskGroup ) {
    $originDesc = '';
    $sep = '';
    $last = count($taskGroup->tskGrp_jobItem_array) - 1;
    for ($i=0; $i<=$last; ++$i) {
        $taskItem = $taskGroup->tskGrp_jobItem_array[$i];
        switch ($taskItem->tskItem_originCode) {
            case RC_JOB_ORIGIN_SCHEDULE: $orgItem = 'Schedule'; break;
            case RC_JOB_ORIGIN_SM:       $orgItem = 'SM'; break;
            case RC_JOB_ORIGIN_STAFF:    $orgItem = 'Staff'; break;
            case RC_JOB_ORIGIN_TRAVEL:   $orgItem = 'Travel'; break;
            case RC_JOB_ORIGIN_SALARY:   $orgItem = 'Salary'; break;
            case RC_JOB_ORIGIN_PM:       $orgItem = 'PM'; break;
            default:                  $orgItem = '??'; break;
        }
        if ($i < $last) {
            $orgItem = '<span class="strikeOut">' . $orgItem . '</span>';
        }
        $originDesc .= $sep . $orgItem ;
        $sep = ', ';
    }
    return $originDesc;
    }
    
} // end class

class reportStandard_earningDetails {

//private $tkdItm_cur_actionButtons;
private $tkdItm_asForm;

//????? used - but can eliminate ???
private $user_destType;
private $option_allStaff;
private $rowCode;
private $isForm;
private $ldgDet_emitter;
private $ldgDet_jobDescGroup;

function ldgRpt_ledgerReport_standardEmit( $appGlobals, $emitter,  $period, $staffId, $form, $asForm=TRUE) { // staff id can be NULL (all staff)
    $this->ldgDet_emitter = $emitter;
    $this->tkdItm_isLedgerMode = TRUE;
    $appGlobals->gb_load_global_employees($staffId);
    $this->tkdItm_emitter = $emitter;
    $this->tkdItm_asForm = $asForm;
    // employee is "payData_ledger_period" object when doing multiple staff
    $this->user_destType = $appGlobals->gb_proxyIsPayMaster ? RC_JOB_ORIGIN_PM : RC_JOB_ORIGIN_STAFF;
    
    $this->isForm = $asForm;
    
    $this->rowCode = 1;
    $emitter->table_start('draff-report-wide',8);  // wide implies difficult to use on small devices  ?????????????????????????????????
    //$emitter->row_oneCell('Pay Period: '.$period->prd_periodName);;
    $isFirstEmployee = TRUE;
    
    if ( $staffId != NULL ) {
        $employeeId = $staffId;
        if ( isset($appGlobals->gb_employeeArray[$employeeId]) ) {
            $employee = $appGlobals->gb_employeeArray[$employeeId];
        }
        else {
            $employee = new dbRecord_payEmployee;
            $employee->emp_read($appGlobals , $employeeId);
            $appGlobals->gb_employeeArray[$employeeId] = $employee;
        }
        $employeeLedger = new payData_ledger_employee( $employeeId );
        $employeeLedger->ldgEpy_read( $appGlobals , $period);
        $this->ldgRpt_row_staffHeader( $appGlobals ,$emitter, $employee,$isFirstEmployee) ;
        foreach ( $employeeLedger->ldgEpy_dailyLedger_array as $dailyLedger ) {
           ++$this->rowCode;
            $emitter->table_body_start('ldg-date');
            foreach ( $dailyLedger->ldgDay_taskGroup_array as $taskGroup ) {
                $this->ldgRpt_row_transaction( $appGlobals , $taskGroup, $emitter );
            }
        }
        $this->ldgRpt_row_staffFooter( $appGlobals ,$employeeLedger,$emitter, $employee ) ;
        $emitter->table_end();
    }
    else {
        $periodLedger = new payData_ledger_period;
        $periodLedger->ldgPer_read( $appGlobals , $period, 0);
        foreach ( $periodLedger->ldgPer_employeeLedger_array as $employeeLedger ) {
            $employee = $appGlobals->gb_employeeArray[$employeeLedger->ldgEpy_employeeId];
            $this->ldgRpt_row_staffHeader( $appGlobals ,$emitter, $employee,$isFirstEmployee) ;
            foreach ( $employeeLedger->ldgEpy_dailyLedger_array as $dailyLedger ) {
               ++$this->rowCode;
                $emitter->table_body_start('ldg-date');
                foreach ( $dailyLedger->ldgDay_taskGroup_array as $taskGroup ) {
                    $this->ldgRpt_row_transaction( $appGlobals , $taskGroup, $emitter );
                }
            }
            $this->ldgRpt_row_staffFooter( $appGlobals ,$employeeLedger,$emitter, $employee ) ;
            $isFirstEmployee = FALSE;
        }
        $emitter->table_end();
    }
    
 }

function ldgRpt_cell( $varName , $cssClass , $includeChanges = FALSE ) {
    if ( is_array($varName) ) {
        $html = '';
        $sep = '';
        foreach ( $varName as $name ) {
            $html .= $sep . $this->ldgDet_jobDescGroup->jdg_get_displayHtml( $name );
            $sep = '<hr class = "multi-sep">';
        }
    }
    else {
        $html = $this->ldgDet_jobDescGroup->jdg_get_displayHtml( $varName );
    }
    //$html = $includeChanges ? $this->$this->ldgDet_jobDescGroup->$varName : $this->ldgDet_jobDescGroup->jdg_get_displayHtml($varName);
    $this->ldgDet_emitter->cell_block( $html , $cssClass );
}


function ldgRpt_row_jobTravelGroup( $taskGroup , $employee , $emitter , $cssClass , $actionButtons ) {
    $this->ldgDet_jobDescGroup = new ledgerReport_job_description_group( $taskGroup );
    $cssClass = $cssClass . ' travel';
    $emitter->row_start($cssClass);
    $this->ldgRpt_cell( 'tkdItm_date' , 'ldg-job' . $cssClass , TRUE );
    $this->ldgRpt_cell( 'tkdItm_timeAll' , $cssClass , TRUE );
    $this->ldgRpt_cell( array( 'tkdItm_jobLocation' , 'tkdItm_jobDescription' , 'tkdItm_adjustExplain' ) , 'ldg-notes' . $cssClass , TRUE );
    $this->ldgRpt_cell( 'tkdItm_jobRate' , $cssClass,TRUE);
    $this->ldgRpt_cell( 'tkdItm_payHours' , 'ldg-payTime' . $cssClass,TRUE);
    $this->ldgRpt_cell( 'tkdItm_payRate', 'ldg-payRate' . $cssClass,TRUE);
    $this->ldgRpt_cell( 'tkdItm_payAmount' , 'ldg-payAmount'.$cssClass , TRUE );
    $this->tkdItm_emitter->cell_block( $actionButtons , 'ldg-action ' . $cssClass );
    $emitter->row_end( $cssClass );
}

function ldgRpt_row_taskGroup( $appGlobals , $taskGroup , $emitter , $cssClass , $actionButtons ) {
    $this->ldgDet_jobDescGroup = new ledgerReport_job_description_group( $taskGroup );
    $emitter->row_start( $cssClass );
    $this->ldgRpt_cell( 'tkdItm_date','ldg-job',TRUE);
    $this->ldgRpt_cell( 'tkdItm_timeAll',$cssClass,TRUE);
    $this->ldgRpt_cell( array( 'tkdItm_jobLocation' , 'tkdItm_jobDescription' , 'tkdItm_adjustExplain' ),'ldg-notes',TRUE);
    $this->ldgRpt_cell( 'tkdItm_jobRate' , $cssClass , TRUE );
    $this->ldgRpt_cell( 'tkdItm_payHours' , 'ldg-payTime' , TRUE );
    $this->ldgRpt_cell( 'tkdItm_payRate' , 'ldg-payRate' , TRUE );
    $this->ldgRpt_cell( 'tkdItm_payAmount' , 'ldg-payAmount' , TRUE );
    $this->tkdItm_emitter->cell_block( $actionButtons , 'ldg-action '); //  group-id: $taskGroup->tskGrp_get_finalItem()->tskItem_group_taskId
    $emitter->row_end();
}

function ldgRpt_row_transaction( $appGlobals , $taskGroup , $emitter ) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $employee = $appGlobals->gb_employeeArray[$taskFinal->tskItem_staffId];
    $borders= '';
    $nextRec = NULL;

    $botRowCount = 0; // one extra row on button (0 if not used)
    $actionButtons = $this->ldgRpt_get_actionButtonCellHtml( $appGlobals , $taskGroup );
    $cssClass = ($this->rowCode % 2 == 0) ? 'ldg-row-even' : 'ldg-row-odd';

    if ( ($taskFinal->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL) ) {
        $this->ldgRpt_row_jobTravelGroup($taskGroup , $employee , $emitter , $cssClass , $actionButtons );
    }
    else {
        $this->ldgRpt_row_taskGroup( $appGlobals , $taskGroup , $emitter , $cssClass , $actionButtons );
    }
    
 }

function ldgRpt_row_staffHeader( $appGlobals ,$emitter, $employee, $isFirstEmployee ) {  // need to change all calls to use employee and not the ledger
    // employee is staff item (not batch)
    // $staffItem = $appGlobals->gb_employeeArray[$curTran->tskItem_staffId];
    $submitValue = "@add_{$employee->emp_staffId}.'_0'";
    $addButton =   $this->tkdItm_asForm ? '<button type="submit" class="accept" name="submit" value="'.$submitValue.'">Add Payroll Item</button>' : '';
    if ( !$isFirstEmployee) {
        $emitter->row_oneCell('','ldg-seperatorRow');
    }
    $emitter->row_start();
    $emitter->cell_block( $employee->emp_name . ' ' . $addButton . ' Pay Period: '
        . $appGlobals->gb_period_current;->prd_periodName , 'empHeader' , 'colspan="8"');
    $emitter->row_end();
    $emitter->row_start();
    $emitter->cell_block( 'Date' , 'ldg-date' );
    $emitter->cell_block( 'Time' , 'ldg-time' );
  //  $emitter->cell_block( 'Source' , 'source' );
    $emitter->cell_block( 'Job Location , Notes/Explanations','job' );
    $emitter->cell_block( 'Job Rate' , 'ldg-rate' );
    $emitter->cell_block( 'Hours' ,'ldg-payHours' );
    $emitter->cell_block( 'Rate' ,'fRate' );
    $emitter->cell_block( 'Pay' ,'fPay' );
    $emitter->cell_block( 'Source , Actions' , 'ldg-action' );
    $emitter->row_end();
}

function ldgRpt_row_staffFooter( $appGlobals ,$employeeLedger,$emitter, $employee = NULL ) {  // need to change all calls to use employee and not the ledger
     if ( $employee == NULL) {
        $employeeId = ($employeeId === NULL) ? $employeeLedger->ldgEpy_employeeId : $employeeId;
        $employee = $appGlobals->gb_employeeArray[$employeeId];
    }
    $emitter->row_start();
    $key = 'Abbreviations: "(Evt)" = Event &nbsp; &nbsp; "(Sch)" = From Schedule &nbsp; &nbsp; "(SM)" = Site Manager &nbsp; &nbsp; "(Emp)" = Employee &nbsp; &nbsp; "(PM)" = Payroll Manager';;
    $emitter->cell_block( $key , '' , 'colspan="8"' );
    $emitter->row_end();
}

function ldgRpt_get_actionButtonCellHtml( $appGlobals , $taskGroup ) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $approvalCode = $taskFinal->tskItem_payStatus;
    
    $originCode = $taskFinal->tskItem_originCode;
    $originHtml = ledgerReport_lib::ldgLib_get_originDesc( $taskGroup );
    $buttonHtml = $this->ldgRpt_get_actionButton ( $appGlobals , $approvalCode, $taskGroup );
    return $originHtml.'<br>'.$buttonHtml;
}

function ldgRpt_get_actionButton ( $appGlobals , $approvalCode, $taskGroup ) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $actionId = '';
    $approved = ( $taskFinal->tskItem_payStatus==PAY_PAYSTATUS_APPROVED );
    $cssClass = $approved ? 'normal loc-approved' : 'normal loc-unapproved';
    if ( $appGlobals->gb_proxyIsPayMaster) {
        if ( ($taskFinal->tskItem_originCode == RC_JOB_ORIGIN_PM)
            or ($taskFinal->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL)
            or ($taskFinal->tskItem_originCode == RC_JOB_ORIGIN_SALARY) ) {
                if ($approved) {
                    $actionId = '@edit';
                    $caption = 'Edit';
                }
                else {
                   $actionId = '@edit';
                   $caption = 'Approve/Edit';
                }
        }
        else {
            if ($approved) {
                $actionId = '@ledgerCreateOverride';
                $caption = 'Override';
            }
            else {
                $actionId = '@overView';
                $caption = 'Approve or Override';
            }
        }
   }
    else {
        if ($taskFinal->tskItem_originCode == RC_JOB_ORIGIN_STAFF) {
            $actionId = '@edit';
            $caption = 'Edit';
       }
        else if ($taskFinal->tskItem_originCode == RC_JOB_ORIGIN_SCHEDULE) {
            $actionId = '@ledgerCreateOverride';
            $caption = 'Override';
        }
        //$needsApproval = '';
    }
   if ( $this->tkdItm_asForm) {
        $val = $actionId
            . '_' . $taskFinal->tskItem_staffId
            . '_' . $taskFinal->tskItem_group_taskId;
            //if ($needsApproval != '') {
            //    $needsApproval = '<br><h6>'.$needsApproval.'</h6>';
            //}
        return '<button type="submit" class="'.$cssClass.'" name="submit" value="'.$val.'"><div class="'.$cssClass.'">'.$caption.'</div></button>';
    }
    else {
        if ( $appGlobals->gb_proxyIsPayMaster) {
            $needsApproval = ($approvalCode > 0) ? '' : 'Needs<br>Approval';
        }
        else {
            $needsApproval = '';
        }
        return $needsApproval;
    }
}

function ldgRpt_ledgerReport_standardInitStyles( $emitter ) {
    ledgerReport_lib::ldgLib_common_cssInit( $emitter );
    $emitter->emit_options->addOption_styleTag( 'span.strikeOut' , ' margin:0pt 0pt 0pt 0pt;' );  // override parent
    $emitter->emit_options->addOption_styleTag( 'hr.multi-sep' , ' margin:0pt 0pt 0pt 0pt;border-top:2px solid #ddddff;' );  // override parent
    $emitter->emit_options->addOption_styleTag( 'td.ldg-action' , 'min-width:100pt;border-left:2px' );
    $emitter->emit_options->addOption_styleTag( 'td.ldg-date' , 'min-width:85pt;line-height:12pt; padding: 4pt 6pt 4pt 6pt;' );
    $emitter->emit_options->addOption_styleTag( 'td.ldg-rate' , 'border-right:2px;' );
    $emitter->emit_options->addOption_styleTag( 'td.ldg-time'  , 'min-width:110pt;border-right:2px;line-height:12pt; padding: 4pt 6pt 4pt 6pt;' );
    $emitter->emit_options->addOption_styleTag( 'td.ldg-payHours' , 'border-left:2px; ' );
    $emitter->emit_options->addOption_styleTag( 'td.ldg-payRate' , 'border-left:2px;' );
    $emitter->emit_options->addOption_styleTag( 'td.ldg-payAmount' , 'border-left:2px;' );
    $emitter->emit_options->addOption_styleTag( 'tr.ldg-seperatorRow' , 'border:none;background-color:white;height:18pt;' );
    $emitter->emit_options->addOption_styleTag( 'td.ldg-seperatorRow' , 'border:none;background-color:white;height:18pt;' );
    $emitter->vaddOption_styleTag( 'td.empHeader' , 'font-size:14pt;font-weight:bold;' );
 
    $emitter->emit_options->addOption_styleTag( 'table.payTable' ,  'background-color:white;' );
    $emitter->emit_options->addOption_styleTag( 'td' , 'line-height:1.5rem;' );
    $emitter->emit_options->addOption_styleTag( 'td.source' ,  'font-size:1rem; width:30pt;vertical-align:top;' );
    $emitter->emit_options->addOption_styleTag( 'td.status' ,  'font-size:8pt;vertical-align:middle;line-height:10pt;text-align:center;padding:0pt;' );
    $emitter->emit_options->addOption_styleTag( 'label.status' ,  'margin:6pt;display:block;border: 1px solid black; font-size:1rem;vertical-align:middle;text-align:center;padding:4pt;' );
    $emitter->emit_options->addOption_styleTag( 'td.job' , 'width:200pt;border-left:2px solid black;' );
    $emitter->emit_options->addOption_styleTag( 'td.travel' , 'background-color:#ddddff; height:15pt;' );
    $emitter->emit_options->addOption_styleTag( 'tr.travel' , 'background-color:#ddddff; height:15pt;' );
    $emitter->emit_options->addOption_styleTag( 'td.lastRow' ,  'font-size:1rem; pt;vertical-align:middle;' );
    $emitter->emit_options->addOption_styleTag( 'button.small' ,  'font-size:1rem;padding:1pt 6pt 1pt 6pt;border-radius: 4pt;height:14pt;min-height:14pt;max-height:14pt;' );
    $emitter->emit_options->addOption_styleTag( 'td.pisource' , 'width:120pt;font-size:18pt;text-align:center;' );
    $emitter->emit_options->addOption_styleTag( 'button.accept  ,  button.correct' , 'font-size:12pt;padding:2pt 2pt 2pt 2pt;border-radius: 2pt;min-height:14pt;margin:4pt;' );
    $emitter->emit_options->addOption_styleTag( 'button.accept' , 'background-color:#ffcccc;' );
    $emitter->emit_options->addOption_styleTag( 'button.normal' , 'background-color:#ccffcc;' );
    $emitter->emit_options->addOption_styleTag( 'td.boLeft' , 'border-left:2px;' );
    $emitter->emit_options->addOption_styleTag( 'td.boRight' , 'border-right:2px;' );
    $emitter->emit_options->addOption_styleTag( 'td.source' , 'border-right:2px;' );
    $emitter->emit_options->addOption_styleTag( 'tr.accept' , 'background-color:#bbffbb' );
    $emitter->emit_options->addOption_styleTag( 'td.pending' , 'background-color:#ffcccc' );
    $emitter->emit_options->addOption_styleTag( 'span.s-sc' , 'border:none; padding:2px;' );
    $emitter->emit_options->addOption_styleTag( 'span.s-sm'    , 'border:none; padding:2px;' );
    $emitter->emit_options->addOption_styleTag( 'span.s-st' , 'border:none; padding:2px;' );
    $emitter->emit_options->addOption_styleTag( 'span.s-pm' , 'border:none; padding:2px;' );
    $emitter->emit_options->addOption_styleTag( 'span.s-ot' , 'border:none; padding:2px;' );
    $emitter->emit_options->addOption_styleTag( 'hr.light' , 'height:0px; width:100%; border-top: 1px solid #eeeeee;margin:0pt 0pt 0pt 0pt; padding:0pt;' );
    $emitter->emit_options->addOption_styleTag( 'span.accepted' , 'display:inline-block;font-weight:bold;padding:3pt;border:1px solid #bbbbbb;margin: 5pt 5pt 5pt 4pt;' );
    $emitter->emit_options->addOption_styleTag( 'span.source' , 'display:inline-block;font-weight:bold;font-size:1rem;padding:0pt 5pt 0pt 5pt;border:1px solid #bbbbbb;margin: 1pt 4pt 1pt 12pt;' );
    $emitter->emit_options->addOption_styleTag( 'span.year' , 'display:inline-block;font-size:80%;text-align:center;padding: 2pt 0pt 2pt 10pt;' );
    $emitter->emit_options->addOption_styleTag( 'span.dif' , 'text-decoration: line-through;background-color:#ffdddd;border:1px solid #aaaa; padding:2px;' );
    $emitter->emit_options->addOption_styleTag( 'tbody.ldg-date' , 'border-top:5px double #aaaaaa;border-bottom:5px double #aaaaaa;' );
    $emitter->emit_options->addOption_styleTag( 'div.line' , 'height: 1px; background-color: #000;' );
    $emitter->emit_options->addOption_styleTag( 'td.line' , 'height: 1px; background-color: #000;' );
    $emitter->emit_options->addOption_styleTag( '.ldg-row-even','background-color: #ffffe4;');
    $emitter->emit_options->addOption_styleTag( '.ldg-row-odd', 'background-color: #fffff8;');
}

} // end class

class reportStandard_ledgerEdit {
private $ldgEdit_employee;
private $ldgEdit_mode_date;
private $ldgEdit_mode_time;
private $ldgEdit_mode_timeAdjust;
private $ldgEdit_mode_jobLocation;
private $ldgEdit_mode_jobDescription;
private $ldgEdit_mode_jobRate;
private $ldgEdit_mode_adjustments;
private $ldgEdit_mode_travel;
public $ldgEdit_display;
const ROLE_EDIT = 1;
const ROLE_READONLY = 2;
const ROLE_NA   = 9;

function ldgEdit_stdReport_output( $appGlobals , $taskGroup, $emitter, $form, $edit ) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $this->ldgEdit_display = new ledgerReport_job_description_group( $taskGroup );
    //$finalLdgItem = $this->ldgEdit_display;
    $this->tkdItm_isEditMode = TRUE;
    $this->ldgEdit_employee = $appGlobals->gb_employeeArray[$taskFinal->tskItem_staffId];
    $this->tkdItm_emitter = $emitter;
    $colspan = 'colspan="' . ( count($taskGroup->tskGrp_jobItem_array) + 1 ) . '"';   //??????????????????????????????????????????????????????

    $emitter->table_start('draff-edit' , count($taskGroup->tskGrp_jobItem_array)+2);
    $this->ldgEdit_out_header( $appGlobals , $taskGroup , $emitter);
    $this->ldgEdit_out_body( $appGlobals , $taskGroup , $emitter);
    $this->ldgEdit_out_footer( $appGlobals , $taskGroup , $emitter, $edit);
    $emitter->table_end();
   
}

function ldgEdit_out_header( $appGlobals , $taskGroup, $emitter ) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $origin = ledgerReport_lib::ldgLib_get_originDesc( $taskGroup );

    $emitter->table_head_start('draff-edit-head',count($taskGroup->tskGrp_jobItem_array)+2 );  //??????????????
    //$typeDesc = ($taskGroup->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL) ? 'Travel Time' : 'Pay Item';
    $employee = $appGlobals->gb_employeeArray[$taskFinal->tskItem_staffId];
    //$emitter->row_oneCell('Edit '.$typeDesc.' for "' . $employee->emp_name.'"');
    $emitter->row_oneCell($employee->emp_name . ' &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; Source: ' . $origin);
    
    //$emitter->row_start();
    //$emitter->cell_block('');
    //if ( $taskGroup->tskGrp_hasHistory() === FALSE ) {
    //    switch ($taskFinal->tskItem_originCode) {
    //        case RC_JOB_ORIGIN_SCHEDULE: $org = '(Pay Set by Schedule)'; break;
    //        case RC_JOB_ORIGIN_SM:       $org = "(Pay added by Site Manager)"; break;
    //        case RC_JOB_ORIGIN_STAFF:    $org = "(Pay added by Employee)"; break;
    //        case RC_JOB_ORIGIN_TRAVEL:   $org = "(Pay is Travel Time)"; break;
    //        case RC_JOB_ORIGIN_SALARY:   $org = "(Pay is Salary)"; break;
    //        case RC_JOB_ORIGIN_PM:       $org = "(Pay added by Payroll Manager))"; break;
    //        default:                  $org='';
    //    }
    //}
    //else {
    //    $trail = '';
    //    $pre = '';
    //    foreach ( $taskGroup->tskGrp_jobItem_array as $taskItem ) {
    //        switch ( $taskItem->tskItem_originCode ) {
    //            case RC_JOB_ORIGIN_SCHEDULE: $s = 'Schedule'; break;
    //            case RC_JOB_ORIGIN_SM:       $s = 'Site Manager'; break;
    //            case RC_JOB_ORIGIN_STAFF:    $s = 'Employee'; break;
    //            case RC_JOB_ORIGIN_TRAVEL:   $s = "???"; break;
    //            case RC_JOB_ORIGIN_SALARY:   $s = "???"; break;
    //            case RC_JOB_ORIGIN_PM:       $s = "???"; break;
    //            default: $s='';
    //        }
    //        $trail .= $pre . $s;
    //        $pre = ' and ';
    //    }
    //    switch ( $taskFinal->tskItem_originCode ) {
    //        case RC_JOB_ORIGIN_SCHEDULE: $org = '???'; break;
    //        case RC_JOB_ORIGIN_SM:       $org = "(Site Manager override of {$trail})"; break;
    //        case RC_JOB_ORIGIN_STAFF:    $org = "(Employee override of {$trail})"; break;
    //        case RC_JOB_ORIGIN_TRAVEL:   $org = "???"; break;
    //        case RC_JOB_ORIGIN_SALARY:   $org = "???"; break;
    //        case RC_JOB_ORIGIN_PM: $org = "(Payroll Manager override of {$trail})"; break;
    //        default: $org='';
    //    }
    //}
    //$emitter->cell_block('Current Pay &nbsp;&nbsp;'.$org, 'editColHead');
    $emitter->table_head_end();
    
}

function ldgEdit_out_body( $appGlobals , $taskGroup, $emitter ) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    
    $emitter->table_body_start('rpt-panel-body');

    if ( $taskFinal->tskItem_originCode == RC_JOB_ORIGIN_SALARY ) {
        $this->ldgEdit_row_sectionTitle( 'When', $emitter);
        $this->ldgEdit_row_date( $appGlobals , $taskGroup, $emitter);  // ro
        $this->ldgEdit_row_sectionTitle( 'Job', $emitter);
        $this->ldgEdit_row_jobLocation( $appGlobals , $taskGroup, $emitter);  // ro
        $this->ldgEdit_row_jobRateStandard( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_sectionTitle( 'Explanations', $emitter);
        $this->ldgEdit_row_adjustStandard( $appGlobals , $taskGroup, $emitter);
    }
    else if ( $taskFinal->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL ) {
        $this->ldgEdit_row_sectionTitle( 'When', $emitter);
        $this->ldgEdit_row_date( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_timeTravel( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_timeAdjust( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_sectionTitle( 'Job', $emitter);
        $this->ldgEdit_row_jobLocation( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_jobDescriptionStandard( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_jobRateStandard( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_sectionTitle( 'Explanations', $emitter);
        $this->ldgEdit_row_adjustStandard( $appGlobals , $taskGroup, $emitter);
    }
    else if ( !empty($taskFinal->tskItem_event_eventId) ) {
        $this->ldgEdit_row_sectionTitle( 'When', $emitter);
        $this->ldgEdit_row_date( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_timeScheduled( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_adjustScheduled( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_sectionTitle( 'Job', $emitter);
        $this->ldgEdit_row_jobLocation( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_jobDescriptionScheduled( $appGlobals , $taskGroup, $emitter);
    }
    else {
        $this->ldgEdit_row_sectionTitle( 'When', $emitter);
        $this->ldgEdit_row_date( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_timeStandard( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_timeAdjust( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_sectionTitle( 'Job', $emitter);
        $this->ldgEdit_row_jobLocation( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_jobDescriptionStandard( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_jobRateStandard( $appGlobals , $taskGroup, $emitter);
        $this->ldgEdit_row_sectionTitle( 'Explanations', $emitter);
        $this->ldgEdit_row_adjustStandard( $appGlobals , $taskGroup, $emitter);
    }
    
    $emitter->table_body_end();
}

function ldgEdit_out_footer( $appGlobals , $taskGroup, $emitter, $edit ) {
    $emitter->table_foot_start();
    if ($edit->edit_isNoOptions) {
        $emitter->row_oneCell('@cancel');
        return;
    }
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $buttons = array();
    if ($edit->edit_ctr_approveCheckbox) {
        $buttons[] =  '@ledgerApproveCheckbox';
        $buttons[] =  ' &nbsp; &nbsp;';
    }
    if ($edit->edit_ctr_saveButton) {
        $buttons[] =  '@ledgerSaveAsIs';
        $buttons[] =  ' &nbsp; &nbsp;';
    }
    if ($edit->edit_ctr_overrideButton) {
        $buttons[] =  '@ledgerCreateOverride';
        $buttons[] =  ' &nbsp; &nbsp;';
    }
    $buttons[] =  '@cancel';
    if ($edit->edit_ctr_deleteButton) {
        $buttons[] = ' &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp;';
        $buttons[] = $this->ldgEdit_getButton ( $appGlobals , $taskGroup, 'Delete','@delete');
    }
    $emitter->row_oneCell($buttons);
    $emitter->table_foot_end();
}

function ldgEdit_getButton ( $appGlobals , $taskGroup , $caption , $actionId ) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $cssClass = 'normal';
    $val = $actionId
        . '_' . $taskFinal->tskItem_staffId
        . '_' . $taskFinal->tskItem_group_taskId;
    return '<button type="submit" class="'.$cssClass.'" name="submit" value="'.$val.'"><div class="'.$cssClass.'">'.$caption.'<br><h6>'.''.'</h6></div></button>';
}

function ldgEdit_row_sectionTitle( $sectionTitle, $emitter ) {
    $emitter->row_oneCell($sectionTitle,'sectionHead');
}

function ldgEdit_row_date( $appGlobals , $taskGroup, $emitter ) {
    $emitter->row_start('rpt-panel-row');
    $emitter->cell_block('Date','draff-edit-fieldDesc');
    $emitter->cell_block('@dateOfJob','draff-edit-fieldData');
    $emitter->row_end();
}

function ldgEdit_row_timeStandard( $appGlobals , $taskGroup, $emitter ) {
    $emitter->row_start( 'rpt-panel-row' );
    $emitter->cell_block('Time Worked','draff-edit-fieldDesc');  // ,'rowspan="2"'
    $fieldArray = array('@timeStatus','&nbsp;&nbsp;&nbsp;','@timeStartHour',':','@timeStartMinute','&nbsp;-&nbsp;','@timeEndHour',':','@timeEndMinute');
    $finalItem = $taskGroup->tskGrp_get_finalItem();
    if ( $finalItem->tskItem_event_prepTime >= 1 ) {
        $fieldArray[] = '&nbsp;&nbsp;&nbsp;Prep Time: '. $finalItem->tskItem_event_prepTime;
    }
    $emitter->cell_block( $fieldArray,'draff-edit-fieldData' );
    $emitter->row_end();
}

function ldgEdit_row_timeScheduled( $appGlobals , $taskGroup, $emitter ) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $emitter->row_start('rpt-panel-row');
    $emitter->cell_block('Time Worked<br><h4>and<br>scheduled time</h4>','draff-edit-fieldDesc');  // ,'rowspan="2"'
    $fieldArray = array('@timeStatus','&nbsp;&nbsp;&nbsp;','@timeStartHour',':','@timeStartMinute','&nbsp;-&nbsp;','@timeEndHour',':','@timeEndMinute');
    $emitter->cell_start( 'draff-edit-fieldData' );
    $this->ldgEdit_twoLineBlock($emitter,'@timeStatus','','tkdItm_timeAttendance');
    $this->ldgEdit_twoLineBlock($emitter,array('@timeStartHour','@timeStartMinute'),'<h6>Scheduled: </h6> '.draff_timeAsString($taskFinal->tskItem_event_timeArrive),'tkdItm_timeStart');
    $this->ldgEdit_twoLineBlock($emitter,array('@timeEndHour','@timeEndMinute'),'<h6>Scheduled: </h6> '.draff_timeAsString($taskFinal->tskItem_event_timeDepart),'tkdItm_timeEnd');
    //$this->ldgEdit_twoLineBlock($emitter,'@timeStartHour',$this->ldgEdit_getHour($taskFinal->tskItem_event_timeArrive));
    //$this->ldgEdit_twoLineBlock($emitter,'@timeStartMinute',$this->ldgEdit_getMinute($taskFinal->tskItem_event_timeArrive));
    //$this->ldgEdit_twoLineBlock($emitter,'@timeEndHour',$this->ldgEdit_getHour($taskFinal->tskItem_event_timeDepart));
    //$this->ldgEdit_twoLineBlock($emitter,'@timeEndMinute',$this->ldgEdit_getMinute($taskFinal->tskItem_event_timeDepart));
    if ( $taskFinal->tskItem_event_prepTime >= 1) {
        $this->ldgEdit_twoLineBlock($emitter,$taskFinal->tskItem_event_prepTime,'Prep Time');
    //    $fieldArray[] = '&nbsp;&nbsp;&nbsp;Prep Time: '. $taskFinal->tskItem_event_prepTime;
        //$fieldArray[] = '@timePrep';
    }
    $emitter->cell_end( 'draff-edit-fieldData' );
    $emitter->row_end();
}

function ldgEdit_row_timeTravel( $appGlobals , $taskGroup, $emitter) {
    $emitter->row_start('rpt-panel-row');
    $emitter->cell_block('Time Traveled','draff-edit-fieldDesc');
    $start = draff_timeAsString($taskGroup->tskItem_job_time_start);
    $end = draff_timeAsString($taskGroup->tskItem_job_time_end);
    $dif = draff_timeMinutesDif($taskGroup->tskItem_job_time_start,$taskGroup->tskItem_job_time_end);
    $desc = $start . ' - ' . $end . ' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Hours: ' . draff_minutesAsString($dif);
    $emitter->cell_block( array('@timeStatus','&nbsp;&nbsp;&nbsp;',$desc),'draff-edit-fieldData' );
    $emitter->row_end();
}

function ldgEdit_row_timeAdjust( $appGlobals , $taskGroup, $emitter) {
    $emitter->row_start('rpt-panel-row');
    $emitter->cell_block('Time Override<br>/Adjustments','draff-edit-fieldDesc');
    //if ( $taskGroup->tskGrp_hasHistory() ) {
    //    $this->tkdItm_out_time_adjustments($taskGroup,'overrideCol');
    //}
    $emitter->cell_block( array('Method','@timeAdjustMethod' ,'&nbsp;&nbsp;&nbsp;Value:','@timeAdjustMinutes'),'draff-edit-fieldData descText'); // '@timeStatus',
    $emitter->row_end();
}

function ldgEdit_row_jobLocation( $appGlobals , $taskGroup, $emitter) {
    $emitter->row_start('rpt-panel-row');
    $emitter->cell_block('Job Location','draff-edit-fieldDesc');
    $emitter->cell_block('@jobLocation','draff-edit-fieldData');
    $emitter->row_end();
}

function ldgEdit_row_jobDescriptionScheduled( $appGlobals , $taskGroup, $emitter) {
    $taskFinal = $taskGroup->tskGrp_get_finalItem();
    $emitter->row_start('rpt-panel-row');
    $emitter->cell_block('Job Description','draff-edit-fieldDesc');
    $rate = draff_dollarsAsString($taskFinal->tskItem_pay_final_rate);
    switch ($taskFinal->tskItem_job_rateCode) {
        case PAY_RATEMETHOD_ADMIN: $rateDesc = 'Admin Rate: '.$rate; break;
        case PAY_RATEMETHOD_FIELD: $rateDesc = 'Field Rate: '.$rate; break;
        case PAY_RATEMETHOD_SALARY: $rateDesc = 'Salary'; break;
        default: $rateDesc = '??';  // should never happen
    }
    $finalLdgItem = $this->ldgEdit_display->tkdGrp_finalItem;
    $emitter->cell_block(array($finalLdgItem->tkdItm_jobDescription->tkdFld_value,' &nbsp; &nbsp; &nbsp;',$rateDesc),'draff-edit-fieldData'); //'@jobDesc'
    $emitter->row_end();
}

function ldgEdit_row_jobDescriptionStandard( $appGlobals , $taskGroup, $emitter) {
    $emitter->row_start('rpt-panel-row');
    $emitter->cell_block('Job Description','draff-edit-fieldDesc');
    $emitter->cell_block('@jobDesc','draff-edit-fieldData');
    $emitter->row_end();
}

function ldgEdit_row_jobRateStandard( $appGlobals , $taskGroup, $emitter) {
    $emitter->row_start('rpt-panel-row');
    $emitter->cell_block('Job Rate','draff-edit-fieldDesc');
    $emitter->cell_block( array('Method','@jobRateMethod','&nbsp;&nbsp;&nbsp;Value:','@jobRateOverride'),'draff-edit-fieldData descText');
    $emitter->row_end();
}

function ldgEdit_row_adjustStandard( $appGlobals , $taskGroup, $emitter) {
    $emitter->row_start('rpt-panel-row');
    $emitter->cell_block('Explain Adjustments','draff-edit-fieldDesc');
    $emitter->cell_block('@overExpain','draff-edit-fieldData');
    $emitter->row_end();
}

function ldgEdit_row_adjustScheduled( $appGlobals , $taskGroup, $emitter) {
    $emitter->row_start('rpt-panel-row');
    $emitter->cell_block('Explain why not<br>scheduled Time','draff-edit-fieldDesc');
    $emitter->cell_block(array($this->ldgEdit_display->jdg_get_displayHtml('tkdItm_adjustExplain'),'@overExpain'),'draff-edit-fieldData');
    $emitter->row_end();
}

function ldgEdit_twoLineBlock( $emitter, $line1, $line2, $varName = '') {
   $changes = $varName == '' ? '' : $this->ldgEdit_display->jdg_get_displayHtml($varName, FALSE);
   $emitter->div_block(array($changes,$line1,'<br><span class="schedDesc">'.$line2.'</span>'),'sched');
}

function ldgEdit_getMinute( $time) {
    return substr($time,2,3);
}

function ldgEdit_getHour( $time) {
    $hour = substr($time,0,2);
    if ( $hour=='12') {
        return '12pm';
    }
    else if ( $hour=='00') {
        return '12am';
    }
    else if ( $hour>='12') {
        return ($hour-12) . 'pm';
    }
    else {
        if ( substr($hour,0,1) == '0') {
            $hour = substr($hour,1,1);
        }
        return $hour . 'am';
    }
}

function ldgEdit_stdReport_init_styles($emitter) {
    ledgerReport_lib::ldgLib_common_cssInit($emitter);
    
    $emitter->emit_options->addOption_styleTag('table.payTable', 'background-color:white;');
    $emitter->emit_options->addOption_styleTag('td.source', 'font-size:1rem;vertical-align:middle;');
    $emitter->emit_options->addOption_styleTag('td.status', 'font-size:1rem;vertical-align:middle;line-height:1.2rem;text-align:center;padding:0pt;');
    $emitter->emit_options->addOption_styleTag('label.status', 'margin:6pt;display:block;border: 1px solid black; font-size:1rem;vertical-align:middle;text-align:center;padding:4pt;');
    $emitter->emit_options->addOption_styleTag('input[type="radio"].status', 'width:16pt; height:16pt;margin:4pt;vertical-align:middle;margin:0;padding:0pt;');
    $emitter->emit_options->addOption_styleTag('input[type="radio"].status:checked + label.status', 'background-color:green');
    $emitter->emit_options->addOption_styleTag('input[type="radio"].status:checked + label.unknown', 'background-color:red');
    $emitter->emit_options->addOption_styleTag('td.lastRow', 'font-size:1rem; pt;vertical-align:middle;background-color:#f8f8ff');
    $emitter->emit_options->addOption_styleTag('button.small', 'font-size:1rem;padding:1pt 6pt 1pt 6pt;border-radius: 4pt;height:14pt;min-height:14pt;max-height:14pt;');
    $emitter->emit_options->addOption_styleTag('td.override',  'background-color:#ffccff;');
    $emitter->emit_options->addOption_styleTag('td.data',  'font-size:60%;color:#777777;');
    $emitter->emit_options->addOption_styleTag('td.job',  'background-color:#e2f8f7;');
    $emitter->emit_options->addOption_styleTag('td.days',  'background-color:#e2f8f7;');
    $emitter->emit_options->addOption_styleTag('td.hours',  'background-color:#e2f8f7;');
    $emitter->emit_options->addOption_styleTag('td.ldg-rate',  'background-color:#e2f8f7;');
    $emitter->emit_options->addOption_styleTag('td.totals',  'background-color:#e2f8f7;');
    $emitter->emit_options->addOption_styleTag('td.title',  'filter: brightness(105%);font-size:105%;');
    $emitter->emit_options->addOption_styleTag('optGroup',  'background-color:#bbbbbb;font-size:16pt;border:5px solid red;margin:8pt 0pt 0pt 0pt;padding:12pt;');
    $emitter->emit_options->addOption_styleTag('option',  'background-color:white;font-size:14pt;border:0px solid black;');
    $emitter->emit_options->addOption_styleTag('span.overrideDesc',  'display:inline-block; font-size:1rem;margin:5pt 0pt 0pt 15pt');
    $emitter->emit_options->addOption_styleTag('td.descText',  'font-size:11pt;');
    $emitter->emit_options->addOption_styleTag('td.editColHead',  'font-size:1.2rem;');
    $emitter->emit_options->addOption_styleTag('td.overrideCol',  'font-size:1.1rem; font-weight:normal;');
    $emitter->emit_options->addOption_styleTag('tr.sectionHead',  'background-color:#cccccc;height:8pt; font-size:8pt;text-align:center;');
    $emitter->emit_options->addOption_styleTag('div.sched'   ,'display:inline-block; border:1px solid #aaaaaa; font-size:18pt; padding:2px;margin: 3pt 6pt 3pt 6pt;line-height:10pt;');
    $emitter->emit_options->addOption_styleTag('span.s-sc','text-decoration: line-through;background-color:#e8e8ff;border:1px solid #aaaa; padding:2px;');
    $emitter->emit_options->addOption_styleTag('span.s-sm' ,'text-decoration: line-through;background-color:#e8ccff;border:1px solid #aaaa; padding:2px;');
    $emitter->emit_options->addOption_styleTag('span.s-st','text-decoration: line-through;background-color:#ffdddd;border:1px solid #aaaa; padding:2px;');
    $emitter->emit_options->addOption_styleTag('span.s-pm' ,'text-decoration: line-through;background-color:#ff0000;border:1px solid #aaaa; padding:2px;');
    $emitter->emit_options->addOption_styleTag('span.s-ot','text-decoration: line-through;background-color:red;border:1px solid #aaaa; padding:2px;');
    $emitter->emit_options->addOption_styleTag('span.schedDesc' ,'display:inline; font-size:1.2rem; margin: 3pt 4pt 3pt 4pt;');
    $emitter->emit_options->addOption_styleTag('td.draff-edit-fieldData'   ,'line-height:10pt;');  // override
}

} // end class


?>