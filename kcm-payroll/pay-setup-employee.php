<?php

// pay-setup-employee.php

ob_start();  // output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );
include_once( '../../rc_job-appData-functions.inc.php' );

include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-objects.inc.php' );
include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );
include_once( '../draff/draff-appEmitter-dom-engine.inc.php' );

include_once( '../kcm-kernel/kernel-emitter.inc.php');
include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'pay-system-payData.inc.php' );
include_once( 'pay-system-appEmitter.inc.php' );
include_once( 'pay-system-globals.inc.php' );

Class appForm_employeeSetup_select extends Draff_Form {

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//}

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits($appChain,$submit);
    $appChain->chn_form_savePostedData();
    $staffId = $this->step_init_submit_suffix;
    if ( is_numeric($staffId) ) {
        $staff = new dbRecord_payEmployee;
        $staff->$this->( $appGlobals ,$staffId);
        $this->step_setShared('#staffId',$staffId);
        $this->step_setShared('#staffName',$staff->emp_name);
        $appChain->chn_launch_newChain(2);
    }
    $appChain->chn_launch_continueChain(1);
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    // no controls on form (except non-form submit buttons)
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_title('Employee Setup');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter);
    $appEmitter->set_menu_customize( $appChain, $appGlobals );

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
    $outReport_staffRatesList = new stdReport_staffRatesList();
    $outReport_staffRatesList->stdReport_init_styles($appEmitter);
    $appEmitter->zone_start('draff-zone-content-report');
    $outReport_staffRatesList->stdReport_output($appEmitter, $appGlobals, $form);

    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

Class appForm_employeeSetup_edit extends Draff_Form {
public $edit_staffId;
public $edit_employeeId;

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $this->edit_staffId = $this->step_getShared('#staffId', NULL);
//    $this->edit_employeeId = new dbRecord_payEmployee;
//    $this->edit_employeeId->$this->($appGlobals , $this->edit_staffId);
//    $this->step_updateIfPosted('@rateField',  $this->edit_employeeId->emp_rateField );
//    $this->step_updateIfPosted('@rateAdmin',  $this->edit_employeeId->emp_rateAdmin );
//    $this->step_updateIfPosted('@rateSalary', $this->edit_employeeId->emp_rateSalary);
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//    if ( $this->edit_employeeId->emp_rateSalary !=0 ) {
//        if ( ($this->edit_employeeId->emp_rateField != 0)or ($this->edit_employeeId->emp_rateAdmin != 0) ) {
//             $appChain->chn_message_set('@rateSalary','Cannot have a salary and other rates');
//        }
//    }
//}

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
    if ( $submit == '@cancel') {
        $appData->edit_staffId = $this->step_getShared('#staffId', 0);
        $staffItem = new dbRecord_payEmployee;
        $staffItem->$this->( $appGlobals ,$appData->edit_staffId);
        $name = '"' . $staffItem->emp_name . '"';
        $appChain->chn_message_set('Cancelled edit of staff rates for  '. $name);
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_cancelChain(1,'');
    }
    $appChain->chn_form_savePostedData();
    $appChain->chn_ValidateAndRedirectIfError();
    if ( $submit == '@save') {
        // validate ????
        $this->edit_employeeId->emp_saveRecord( $appGlobals );
        //$this->edit_staffRateGroup->staffRateGroup_save( $appGlobals );
        //$name = '"' . $appData->edit_staffRateGroup->staffRateGroup_staffName . '"';
        $appChain->chn_message_set('Saved edits to '.$this->edit_employeeId->emp_name);
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_continueChain(1);
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
    $appEmitter->set_title('');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter);
    $appEmitter->set_menu_customize( $appChain, $appGlobals );

    $appEmitter = new kcmPay_emitter($appGlobals, $form);
    $appEmitter->payEmit_init( $appGlobals, $appChain  ,'pmu-setStaff');
    $appEmitter->payEmit_init_menu( $appGlobals, $appChain  );

    $outReport_staffRatesEdit = new stdReport_staffRatesEdit();
    $outReport_staffRatesEdit->stdReport_init_styles($appEmitter);

    $appEmitter->payEmit_output_start( $appGlobals ,$appChain,$form,'Employee Setup', 'Employee Setup');
   // $appEmitter->zone_menu_toggled();

    $appEmitter->zone_body_start($appChain, $form);

    $appEmitter->zone_start('draff-zone-content-report');

    $outReport_staffRatesEdit->stdReport_output($appEmitter, $appGlobals, $form, $this->edit_employeeId);

    $appEmitter->zone_end();
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $form->drForm_addField( new Draff_Number  ('@rateField',    $this->edit_employeeId->emp_rateField );
    $form->drForm_addField( new Draff_Number  ('@rateAdmin',    $this->edit_employeeId->emp_rateAdmin );
    $form->drForm_addField( new Draff_Number  ('@rateSalary',   $this->edit_employeeId->emp_rateSalary );
    $this->drForm_addField( new Draff_Button( '@save','Save') );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
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
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

class apdData_employeeSetup extends draff_appData {

function __construct() {
}

function apd_formData_get( $appGlobals, $appChain ) {
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

} // end class

class stdReport_staffRatesList {
private $rateRpt_employees;

function stdReport_init_styles($appEmitter) {
   $appEmitter->addOption_styleTag('td.hd-rates','border:1pt; text-align:center; padding: 10pt 4pt 10pt 4pt;');
   $appEmitter->addOption_styleTag('.r-name','width:160pt;border:1pt; padding: 10pt 4pt 10pt 4pt; vertical-align:middle;');
   $appEmitter->addOption_styleTag('.r-rate','width:80pt;border:1pt; padding: 10pt 12pt 10pt 4pt; vertical-align:middle;text-align: right;');
   $appEmitter->addOption_styleTag('.r-edit','width:60pt;border:1pt; padding: 2pt 4pt 2pt 4pt; vertical-align:middle;');
   $appEmitter->addOption_styleTag('span.r-short','margin-left:12pt;');
}

function sReport_getData( $appGlobals ) {
    $this->rateRpt_employees = new payData_employee_batch;
    $this->rateRpt_employees->epyBat_read_summary( $appGlobals );
}

function stdReport_output($appEmitter, $appGlobals, $form) {

    $result = $this->sReport_getData($appGlobals);

    $appEmitter->table_start('draff-report',5);

    $appEmitter->table_head_start(,5);
    $appEmitter->row_oneCell('Employee Listing',);
    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('Name','r-name');
    $appEmitter->cell_block('Field<br>Pay Rate',);
    $appEmitter->cell_block('Admin<br>Pay Rate',);
    $appEmitter->cell_block('Salary<br>Pay Rate',);
    $appEmitter->cell_block('Edit','r-edit');
    $appEmitter->row_end();
    $appEmitter->table_head_end();

    $appEmitter->table_body_start('');

    foreach($this->rateRpt_employees->epyBat_empoyeeArray as $employee) {
        $editButton = $form->drForm_gen_button ('@edit_'.$employee->emp_staffId, 'Edit','');
        $appEmitter->row_start('rpt-grid-row');
        $appEmitter->cell_block($employee->emp_name,'r-name');
        $appEmitter->cell_block($employee->emp_rateField,'r-rate');
        $appEmitter->cell_block($employee->emp_rateAdmin,'r-rate');
        $appEmitter->cell_block($employee->emp_rateSalary,'r-rate');
        $appEmitter->cell_block($editButton,'r-edit');
        $appEmitter->row_end();
    }

    $appEmitter->table_body_end();

    $appEmitter->table_end();

}

} // end class

class stdReport_staffRatesEdit {

function stdReport_init_styles($appEmitter) {
    $appEmitter->addOption_styleTag('table.payTable', 'background-color:white;');
    $appEmitter->addOption_styleTag('td.source', 'font-size:10pt;vertical-align:middle;');
    $appEmitter->addOption_styleTag('td.status', 'font-size:10pt;vertical-align:middle;');
    $appEmitter->addOption_styleTag('td.lastRow', 'font-size:10pt; pt;vertical-align:middle;background-color:#eeffee');
    $appEmitter->addOption_styleTag('button.small', 'font-size:10pt;padding:1pt 6pt 1pt 6pt;border-radius: 4pt;min-height: 0pt;min-width: 0pt;');

}

function sReport_getData($appGlobals) {
}

function stdReport_output($appEmitter, $appGlobals, $form, $staff) {
    $appEmitter->table_start('draff-edit',2);

    $appEmitter->table_head_start('draff-edit-head');
    $appEmitter->row_oneCell($staff->emp_name,'rpt-panel');
    $appEmitter->table_head_end();

    $appEmitter->table_body_start('rpt-panel-body');

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Field Pay Rate', 'draff-edit-fieldDesc' );
    $appEmitter->cell_block('@rateField'    , 'draff-edit-fieldData' );
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Admin Pay Rate', 'draff-edit-fieldDesc' );
    $appEmitter->cell_block('@rateAdmin'    , 'draff-edit-fieldData' );
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Salary Pay Rate' , 'draff-edit-fieldDesc' );
    $appEmitter->cell_block('@rateSalary'     , 'draff-edit-fieldData' );
    $appEmitter->row_end();

    $appEmitter->table_body_end();

    $appEmitter->table_foot_start();
    $appEmitter->row_oneCell(array('@save','@cancel'),'');
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
$appData = new apdData_employeeSetup($appGlobals);


//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@         Process Page               @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );
$appChain->chn_form_register(1,'appForm_employeeSetup_select');
$appChain->chn_form_register(2,'appForm_employeeSetup_edit');
$appData->com_staffId = draff_urlArg_getOptional('stid', 0);
//$step = ($appData->com_staffId==0) ? 1 : 2;
$appChain->chn_form_launch(); // proceed to current step

exit;

?>