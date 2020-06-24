<?php

// pay-setup-payPeriods.php

ob_start();  // output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );
include_once( '../../rc_job-appData-functions.inc.php' );

include_once( '../kcm-kernel/kernel-emitter.inc.php');
include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-objects.inc.php' );
include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );

include_once( 'pay-system-payData.inc.php' );
include_once( 'pay-system-appEmitter.inc.php' );
include_once( 'pay-system-globals.inc.php' );

Class appForm_setupPayPeriods_select extends Draff_Form {
//public $select_periodBatch;

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
////    $this->select_periodBatch = new payData_payPeriod_batch;
////    $this->select_periodBatch->prdBat_readBatch( $appGlobals );
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//}

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    $appChain->chn_form_savePostedData();
    if ( $appChain->chn_submit[0] == 'add' ) {
        $appData->com_job_jobId = 0;
        $this->step_setShared('#periodId',0);
        $appChain->chn_launch_newChain(2);
    }
    if ( isset($appChain->chn_submit[1]) and is_numeric($appChain->chn_submit[1]) ) {
        $appData->edit_record_id = $this->step_init_submit_suffix;
        $this->step_setShared('#periodId',$this->step_init_submit_suffix);
        $appChain->chn_launch_newChain(2);
    }
    $appChain->chn_launch_continueChain(1);
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-report' );
    $appEmitter->set_title('Payroll - ???');
    $appEmitter->set_menu_standard( $appChain, $appGlobals );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    $appEmitter->set_title('Pay Period Setup');
    $appEmitter->set_menu_customize( $appChain, $appGlobals );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    // no controls on form
}

function drForm_outputPage ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->krnEmit_output_htmlHead  ( $appData, $appGlobals, $appChain, $appEmitter );
    $appEmitter->krnEmit_output_bodyStart ( $appData, $appGlobals, $appChain, $this );
    $appEmitter->krnEmit_output_ribbons  ( $appData, $appGlobals, $appChain, $this );
    $this->drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter );
    $this->drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter );
    $this->drForm_outputFooter  ( $appData, $appGlobals, $appChain, $appEmitter );
    $appEmitter->krnEmit_output_bodyEnd  ( $appData, $appGlobals, $appChain, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $outReport_payPeriodList = new report_payPeriodList;
    $outReport_payPeriodList->periodList_stdReport_init_styles($appEmitter);
    $appEmitter->zone_start('draff-zone-content-report');
    $outReport_payPeriodList->periodList_stdReport_output( $appGlobals, $appData, $appChain, $appEmitter, $form );

    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

Class appForm_setupPayPeriods_edit extends Draff_Form {
public $edit_period;
public $edit_record_id;
public $edit_closedDesc;
public $edit_combo_periodTypes;

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $this->step_updateShared('#periodId',  $appData->edit_record_id);
//    $this->edit_record_id = $this->step_getShared('#periodId',NULL);
//    $this->edit_period   = new dbRecord_payPeriod;
//    if ( $this->edit_record_id!=0) {
//        $this->edit_period->prd_readRecord( $appGlobals ,$this->edit_record_id);
//    }
//    else {
//        $this->edit_period->prd_periodType = RC_PAYPERIOD_SPECIAL;
//    }
//    $this->step_updateIfPosted('@dateStart',  $this->edit_period->prd_dateStart   );
//    $this->step_updateIfPosted('@dateEnd',    $this->edit_period->prd_dateEnd     );
//    $this->step_updateIfPosted('@periodName',     $this->edit_period->prd_periodName      );
//    //$this->step_updateIfPosted('@periodType',     $this->edit_period->prd_periodType      );
//    //$this->step_updateIfPosted('@closed',     $this->edit_period->prd_whenClosed  );
//    $this->edit_closedDesc = $appData->com_getPeriodClosedString($this->edit_period);
//    $this->edit_init_combo_lists( $appGlobals );
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//    //$appData->com_job_desc = trim($appData->com_job_desc);
//    //if ( $appData->com_job_desc=='') {
//    //    $appChain->chn_message_set('@jobDesc','Description is required');
//    //    return;
//    //}
//    $haveError = FALSE;
//    $dateStart = $this->edit_period->prd_dateStart;
//    if ( checkdate ( substr($dateStart,5,2), substr($dateStart,8,2),substr($dateStart,0,4) ) === FALSE ) {
//        $appChain->chn_message_set('@dateStart','Start Date must be valid');
//        $haveError = TRUE;
//    }
//    $dateEnd = $this->edit_period->prd_dateEnd;
//    if ( checkdate ( substr($dateEnd,5,2), substr($dateEnd,8,2),substr($dateEnd,0,4) ) === FALSE ) {
//        $appChain->chn_message_set('@dateEnd','Start Date must be valid');
//        $haveError = TRUE;
//    }
//    if ( ($dateStart > $dateEnd) and (!$haveError) ){
//        $appChain->chn_message_set('@dateStart','Start Date must not be after End Data');
//        $appChain->chn_message_set('@dateEnd','End Date must not be before Start Data');
//    }
//}

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
    if ( $appChain->chn_submit[0] == '@cancel') {
        $appChain->chn_message_set('Cancelled');
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_cancelChain(1,'');
    }
    $appChain->chn_form_savePostedData();
    $appChain->chn_ValidateAndRedirectIfError();
    if ( $appChain->chn_submit[0] == '@save') {
        $isNewSpecialPeriod = ($this->edit_period->prd_periodType == RC_PAYPERIOD_SPECIAL) and ( $this->edit_record_id == 0);
        $recNum = $this->edit_period->prd_saveRecord( $appGlobals );
        if ( $isNewSpecialPeriod) {
             payData_status_set( $appGlobals , 'OpenPeriod_PayMaster' ,'$recNum'); // open special period - only way to close it is to close if from payroll home
        }
        $this->edit_period->prd_periodType = RC_PAYPERIOD_SPECIAL;
        $appChain->chn_message_set('updated');
        //payData_status_set( $appGlobals , 'Table_Schedule_WhenModified' ,'') ;
        //payData_status_set( $appGlobals , 'Table_Schedule_WhenSynchronized', '') ;
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_continueChain(1);
    }
    $payPeriodId = $this->edit_period->prd_payPeriodId;
    if ( $appChain->chn_submit[0] == '@openAll') {
        payData_status_set( $appGlobals ,'OpenPeriod_PayMaster',$payPeriodId);
        payData_status_set( $appGlobals ,'OpenPeriod_Staff',$payPeriodId);
        payData_status_set( $appGlobals , 'Table_Schedule_WhenModified' ,'') ;
        payData_status_set( $appGlobals , 'Table_Schedule_WhenSynchronized', '') ;
    }
    if ( $submit == '@openPM') {
        payData_status_set( $appGlobals ,'OpenPeriod_PayMaster',$payPeriodId);
        if ( $this->edit_period == $payPeriodId) {
             payData_status_set( $appGlobals ,'OpenPeriod_Staff',0);
            payData_status_set( $appGlobals , 'Table_Schedule_WhenModified' ,'') ;
            payData_status_set( $appGlobals , 'Table_Schedule_WhenSynchronized', '') ;
        }
    }
    if ( $submit == '@openStaff') {
        payData_status_set( $appGlobals ,'OpenPeriod_Staff',$payPeriodId);
        payData_status_set( $appGlobals , 'Table_Schedule_WhenModified' ,'') ;
        payData_status_set( $appGlobals , 'Table_Schedule_WhenSynchronized', '') ;
    }
    if ( $submit == '@closeStaff') {
        payData_status_set( $appGlobals ,'OpenPeriod_Staff',0);
        payData_status_set( $appGlobals , 'Table_Schedule_WhenModified' ,'') ;
        payData_status_set( $appGlobals , 'Table_Schedule_WhenSynchronized', '') ;
    }
    $appChain->chn_launch_continueChain();
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-report' );
    $appEmitter->set_title('Payroll - ???');
    $appEmitter->set_menu_standard( $appChain, $appGlobals );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    $appEmitter->set_title('Define Pay Periods');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter);
    $appEmitter->set_menu_customize( $appChain, $appGlobals );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $periodAttr = array('#list'=>$this->edit_combo_periodTypes);
    $periodAttr['disabled'] = '';
    $readOnly = NULL;
    if ( $this->edit_period->prd_periodType == RC_PAYPERIOD_NORMAL) {
        $readOnly = array('disabled'=>'');
        $periodAttr['disabled'] = '';
    }
    $disabled = array('disabled'=>'');


    $this->drForm_addField( new Draff_Date  ('@dateStart',  $this->edit_period->prd_dateStart , $readOnly) );
    $this->drForm_addField( new Draff_Date  ('@dateEnd'  ,    $this->edit_period->prd_dateEnd , $readOnly   ) );
    $this->drForm_addField( new Draff_Combo('@periodType'   , $this->edit_period->prd_periodType, $periodAttr ) ); //????
    $this->drForm_addField( new Draff_Text ('@periodName'   ,  $this->edit_period->prd_periodName  );
    $this->drForm_addField( new Draff_Text ('@closed'   ,  $this->edit_closedDesc  , $disabled);
    $this->drForm_addField( new Draff_Button( '@save','Save') );
  //   $this->drForm_addField( new Draff_Button( '@openAll','Open for All') );
  //   $this->drForm_addField( new Draff_Button( '@openPM','Open only to<br>Payroll Manager') );
  //   $this->drForm_addField( new Draff_Button( '@openStaff','Open to<br>Employees') );
 //    $this->drForm_addField( new Draff_Button( '@closeStaff','Close to employees') );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
}

function edit_init_combo_lists( $appGlobals ) {
    $this->edit_combo_periodTypes = array();
    $this->edit_combo_periodTypes[RC_PAYPERIOD_NORMAL] = 'Standard';
    $this->edit_combo_periodTypes[RC_PAYPERIOD_SPECIAL] = 'Special (Bonuses, etc)';
}

function drForm_outputPage ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->krnEmit_output_htmlHead  ( $appData, $appGlobals, $appChain, $appEmitter );
    $appEmitter->krnEmit_output_bodyStart ( $appData, $appGlobals, $appChain, $this );
    $appEmitter->krnEmit_output_ribbons  ( $appData, $appGlobals, $appChain, $this );
    $this->drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter );
    $this->drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter );
    $this->drForm_outputFooter  ( $appData, $appGlobals, $appChain, $appEmitter );
    $appEmitter->krnEmit_output_bodyEnd  ( $appData, $appGlobals, $appChain, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
     $outReport_payPeriodEdit = new report_payPeriodEdit;
    $outReport_payPeriodEdit->periodEdit_stdReport_init_styles($appEmitter);
    $appEmitter->zone_start('draff-zone-content-report');
    $outReport_payPeriodEdit->periodEdit_stdReport_output( $appData, $appGlobals, $appChain, $appEmitter, $form,  $this->edit_period);
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

class appData_setupPayPeriods extends draff_appData {
public $apd_OpenPeriod_Staff;
public $apd_OpenPeriod_PM;
public $apd_OpenPeriod_All;

function __construct( $appGlobals ) {
    $this->init_period_status( $appGlobals );
}

function apd_formData_get( $appGlobals, $appChain ) {
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

function init_period_status( $appGlobals ) {
    $this->apd_OpenPeriod_PM = payData_status_get( $appGlobals ,'OpenPeriod_PayMaster',NULL);
    $this->apd_OpenPeriod_Staff = payData_status_get( $appGlobals ,'OpenPeriod_Staff',NULL);
    $this->apd_OpenPeriod_All = ($this->apd_OpenPeriod_PM == $this->apd_OpenPeriod_Staff) ? $this->apd_OpenPeriod_PM : 0;
    $this->com_OpenPeriod_Desc = ($this->apd_OpenPeriod_PM == $this->apd_OpenPeriod_Staff) ?$this->apd_OpenPeriod_PM : 0;
}

function com_getPeriodClosedString($period) {
    if ( !empty($period->prd_whenClosed)) {
        return $period->prd_whenClosed;
    }
    if ( $period->prd_periodType == RC_PAYPERIOD_SPECIAL) {
        if ( $period->prd_payPeriodId == $this->apd_OpenPeriod_PM) {
            return '(Special Pay Period - Open to Payroll Manager)';
        }
        else {
            return '(Special Pay Period)';  // should never happen
        }
    }
    if ( $period->prd_payPeriodId == $this->apd_OpenPeriod_All) {
        return '(Open to All)';
    }
    if ( $period->prd_payPeriodId == $this->apd_OpenPeriod_Staff) {
        return '(Open to Employees)';
    }
    if ( $period->prd_payPeriodId == $this->apd_OpenPeriod_PM) {
        return '(Open to Payroll Manager)';
    }
    return '';  // not open or closed
}

function com_getPeriodType($status) {
    switch ($status) {
        case RC_PAYPERIOD_NORMAL: return 'Standard';
        case RC_PAYPERIOD_SPECIAL: return 'Special';
        default: return '????';
   }
}

} // end class

class report_payPeriodList {
public $select_periodBatch;
// reports have certain "rules"
// form can be null if a print preview or export
// limited style support
// print, echo not allowed, must use a limited set of appEmitter functions - very table and cell oriented, and minimal div, span, etc support within cells
// generally $appData will contain the data for the report

function periodList_stdReport_init_styles($appEmitter) {
}

function sReport_getData( $appGlobals ) {
    $this->select_periodBatch = new payData_payPeriod_batch;
    $this->select_periodBatch->prdBat_readBatch( $appGlobals );
}

function periodList_stdReport_output( $appGlobals, $appData, $appChain, $appEmitter, $form ) {
    $this->sReport_getData( $appGlobals );

    $appEmitter->table_start('',7);

    $appEmitter->table_head_start();
    $appEmitter->row_oneCell('Pay Period Setup',);
    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('Period Start');
    $appEmitter->cell_block('Period End');
    $appEmitter->cell_block('Period Name');
    $appEmitter->cell_block('Period Type');
    $appEmitter->cell_block('Closed on');
    $appEmitter->cell_block('Edit');
    $appEmitter->row_end();
    $appEmitter->table_head_end();

    $appEmitter->table_body_start();
    foreach( $this->select_periodBatch->prdBat_items as $period) {
        $appEmitter->cell_block(draff_dateAsString($period->prd_dateStart));
        $appEmitter->cell_block(draff_dateAsString($period->prd_dateEnd));
        $appEmitter->cell_block($period->prd_periodName);
        $appEmitter->cell_block($appData->com_getPeriodType($period->prd_periodType));
        $closedDesc = $appData->com_getPeriodClosedString($period);
        $appEmitter->cell_block(draff_dateTimeAsString($closedDesc));
        $appEmitter->cell_block($this->drForm_gen_button ('@edit_'.$period->prd_payPeriodId, 'Edit', ''),'');
        $appEmitter->row_end();
    }
    $appEmitter->table_body_end();
    if ( $appGlobals->gb_period_current;->prd_periodType == RC_PAYPERIOD_NORMAL) {
        $appEmitter->table_foot_start('');
        $appEmitter->row_start('');
        $appEmitter->cell_block($this->drForm_gen_button ('@add', 'Add Special Period (bonuses, etc)', '') . '&nbsp;&nbsp;&nbsp;&nbsp;' ,'','colspan="99"');
        $appEmitter->row_end();
        $appEmitter->table_foot_end();
    }
    $appEmitter->table_end();

}

} // end class

class report_payPeriodEdit {
// reports have certain "rules"
// form can be null if a print preview or export
// limited style support
// print, echo not allowed, must use a limited set of appEmitter functions - very table and cell oriented, and minimal div, span, etc support within cells
// generally $appData will contain the data for the report

function periodEdit_stdReport_init_styles($appEmitter) {
}

function periodEdit_stdReport_output( $appData, $appGlobals, $appChain, $appEmitter, $form, $period ) {

    $readOnly = NULL;
    $appEmitter->table_start('draff-edit');


    $appEmitter->table_head_start('draff-edit-head');
    if ( $period->prd_periodType == RC_PAYPERIOD_NORMAL) {
          $title = 'Edit Standard Pay Period';
    }
    else if ( $period->prd_payPeriodId == 0) {
        $title =  'Add and Open Special PayPeriod';
    }
    else {
        $title =  'Edit Special Pay Period';
    }
    $appEmitter->row_oneCell( $title);  // ,'sy-dataPanel-header-left'
    if ( $period->prd_periodType == RC_PAYPERIOD_SPECIAL) {
        $warning = '<h2>Information about Special Periods (Bonuses, etc):</h2>';
        $warning .= '<ul>';
        $warning .= '<li>The staff can continue entering individual payroll transactions</li>';
        $warning .= '<li>The staff cannot see or enter transactions for a special period</li>';
        $warning .= '<li>The Payroll Master must complete and close the special pay period before processing standard periods</li>';
        $warning .= '<li>Only the Payroll Master can enter transactions for a special period</li>';
        //$warning .= '<li>All transactions must be within the date range of the special period</li>';  //????????????
        $warning .= '</ul>';
        $appEmitter->row_oneCell($warning,'draff-text-left border-section-top');
    }
    $appEmitter->table_head_end();

    $appEmitter->table_body_start('rpt-panel-body');

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Date Period Starts', 'draff-edit-fieldDesc' );
    $appEmitter->cell_block ('@dateStart'       , 'draff-edit-fieldData' );
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Date Period Ends', 'draff-edit-fieldDesc' );
    $appEmitter->cell_block('@dateEnd'        , 'draff-edit-fieldData' );
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Period Type',   'draff-edit-fieldDesc' );
    $appEmitter->cell_block('@periodType', 'draff-edit-fieldData' );
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Period Name',   'draff-edit-fieldDesc' );
    $appEmitter->cell_block('@periodName', 'draff-edit-fieldData' );
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Date Closed', 'draff-edit-fieldDesc' );
    $appEmitter->cell_block ('@closed'   , 'draff-edit-fieldData' );
    $appEmitter->row_end();

    //  if ( empty($period->prd_whenClosed) ) {
    //      $isOpen_PM    = ($period->prd_payPeriodId == $appData->apd_OpenPeriod_PM);
    //      $isOpen_staff = ($period->prd_payPeriodId == $appData->apd_OpenPeriod_Staff);
    //      $buttons = array();
    //      if ( $period->prd_periodType == RC_PAYPERIOD_SPECIAL) {
    //          if ( !$isOpen_PM) {
    //               $buttons[] = '@openPM';
    //          }
    //      }
    //      else {
    //          if ( (!$isOpen_PM) or (!$isOpen_staff) ) {
    //              $buttons[] = '@openAll';
    //          }
    //          if ( !$isOpen_PM) {
    //               $buttons[] = '@openPM';
    //          }
    //          if ( $isOpen_staff ) {
    //              $buttons[] = '@closeStaff';
    //          }
    //          else {
    //              $buttons[] = '@openStaff';
    //          }
    //      }
    //      if ( !empty($buttons) ) {
    //          $appEmitter->row_start('rpt-panel-row');
    //          $appEmitter->cell_block('Change Open Status'  , 'draff-edit-fieldDesc' );
    //          $appEmitter->cell_block ($buttons, 'draff-edit-fieldData' );
    //          $appEmitter->row_end();
    //      }
    //  }

    $appEmitter->table_body_end();

    $appEmitter->table_foot_start();
    $appEmitter->row_oneCell(array('@save','@cancel'));
    $appEmitter->table_foot_end();
    $appEmitter->table_end();
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appGlobals = new kcmPay_globals();
$appGlobals->gb_forceLogin ();
$appData = new appData_setupPayPeriods($appGlobals);


//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@         Process Page               @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );
$appChain->chn_form_register(1,'appForm_setupPayPeriods_select');
$appChain->chn_form_register(2,'appForm_setupPayPeriods_edit');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>