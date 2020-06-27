<?php

// pay-report-ledger.php

ob_start();  // output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );
include_once( '../../rc_job-appData-functions.inc.php' );

include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-database.inc.php');
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );
include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-menu.inc.php' );
include_once( '../draff/draff-page.inc.php' );

include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'pay-system-payData.inc.php' );
include_once( 'pay-system-appEmitter.inc.php' );
include_once( 'pay-system-globals.inc.php' );

include_once( 'pay-report-ledger.inc.php' );

Class appForm_ledgerReport_main extends kcmKernel_Draff_Form {
public $ledger_param_staffId;
public $ledger_periods;
public $ledger_periods_count;
public $ledger_cur_periodId;
public $ledger_cur_period;
public $ledger_periodSelect_buttons;

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {  // bundle
     kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
   if ( $submit == '@cancel') {
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_cancelChain(1,'');
    }
    $appChain->chn_form_savePostedData();
    if ( is_numeric($this->step_init_submit_suffix) ) {
        $this->step_setShared('#periodId',$this->step_init_submit_suffix);
    }
    $appChain->chn_launch_continueChain($appData->apd_first_step);
    return;
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {  // bundle
    $appEmitter->emit_options->set_theme( 'theme-report' );
    $appEmitter->emit_options->set_title('Payroll - ???');
    $appGlobals->gb_ribbonMenu_Initialize( $appChain, $appGlobals );
    $appGlobals->gb_menu->drMenu_customize( );
    $desc = (!$appGlobals->gb_proxyIsPayMaster) ?'View Previous Pay Periods' : 'Ledger Report';
    $appEmitter->emit_options->set_title($desc);
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter);
    $appGlobals->gb_menu->drMenu_customize();
}
function drForm_initFields( $appData, $appGlobals, $appChain ) {  // bundle
    // buttons on the ledger report are printed directly without defining them
    $this->ledger_periodSelect_buttons = array();
    if ( $appGlobals->gb_period_current; == NULL) {
        return;
    }
    if ( $appGlobals->gb_proxyIsPayMaster) {
        $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
        $this->drForm_addField( new Draff_Button( '@prev','Previous Person') );
        $this->drForm_addField( new Draff_Button( '@next','Next Person') );
    }
    else if ( !$appGlobals->gb_proxyIsPayMaster) {
        if ( $this->ledger_periods_count>=1) {
            // periods are in reverse order
            $periodIdArray = array_keys($this->ledger_periods->prdBat_items);
            $curIndex = array_search($this->ledger_cur_periodId,$periodIdArray);

            $prevButtonId = ($curIndex < $this->ledger_periods_count-1) ? ('@prev_'.$periodIdArray[$curIndex+1]) : '@prev_0';
            $prevFlags  =($curIndex<$this->ledger_periods_count-1) ? 0 : 1;
            $this->drForm_addField( new Draff_Button( $prevButtonId,'Previous Period') );
            $this->ledger_periodSelect_buttons[] = $prevButtonId;
            if ( $prevFlags == 1) {
                $this->drForm_disable($prevButtonId);
            }

            $nextButtonId = ($curIndex>=1) ? ('@next_'.$periodIdArray[$curIndex-1]) : '@next_0';
            $nextFlags =($curIndex>=1) ? 0 : 1;
            $this->drForm_addField( new Draff_Button( $nextButtonId,'Next Period') );
            $this->ledger_periodSelect_buttons[] = $nextButtonId;
            if ( $nextFlags == 1) {
                $this->drForm_disable($nextButtonId);
            }

        }
   }
}


function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    if ( !$appGlobals->gb_proxyIsPayMaster) {
        $appEmitter->zone_start('draff-zone-header-default');
        $appEmitter->content_field($this->ledger_periodSelect_buttons);
        $appEmitter->zone_end();
    }

}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $outReport_ledgerReport = new reportStandard_earningDetails;
    $outReport_ledgerReport->ldgRpt_ledgerReport_standardInitStyles($appEmitter);
    $appEmitter->zone_start('draff-zone-content-report');
    if  ( (!$appGlobals->gb_proxyIsPayMaster)  and ($appGlobals->gb_period_current; == NULL) ) {
       print "Cannot enter payroll now";
    }
    else if  ( (!$appGlobals->gb_proxyIsPayMaster)  and (empty($this->ledger_cur_period) ) ) {
        Print '<br><br><h2>There are no previous pay periods to view<h2>';
    }
    else {
        $staffId = $appGlobals->gb_proxyIsPayMaster ? NULL : $appGlobals->gb_user->krnUser_staffId;
        $outReport_ledgerReport->ldgRpt_ledgerReport_standardEmit( $appGlobals, $appEmitter,  $this->ledger_cur_period, $staffId,  $form, FALSE);
    }
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
   // $staffId = $this->step_getShared('#bpStaffId',NULL);
   // $this->ledger_param_staffId = ($staffId===NULL) ? $appData->apd_user_proxy->krnUser_staffId : $staffId;
    //$this->ledger_employeeMode = (!$appGlobals->gb_proxyIsPayMaster) or(!$appGlobals->gb_loginIsPayMaster);
    $this->ledger_cur_period = $appGlobals->gb_period_current;;
    if ( !$appGlobals->gb_proxyIsPayMaster) {
        $this->ledger_periods = new payData_payPeriod_batch;
        $this->ledger_periods->prdBat_readEmployeeOldPeriods( $appGlobals , $this->ledger_param_staffId);
        $this->ledger_periods_count = count($this->ledger_periods->prdBat_items);
        if ( $this->ledger_periods_count==0) {
             $this->ledger_cur_periodId = 0;
        }
        else {
            $periodIdArray = array_keys($this->ledger_periods->prdBat_items);
            $this->ledger_cur_periodId =  $periodIdArray[0];
        }
        $this->ledger_cur_periodId = $this->step_getShared('#periodId',$this->ledger_cur_periodId);
        if ( empty($this->ledger_cur_periodId)) {
            $this->ledger_cur_period = NULL;
        }
        else {
            $this->ledger_cur_period   = $this->ledger_periods->prdBat_items[$this->ledger_cur_periodId];
            $appGlobals->gb_period_override = $this->ledger_cur_period;
        }
    }
}

function drForm_validate( $appData, $appGlobals, $appChain ) {   // bundle
}

} // end class

class application_data extends draff_appData {

//---  user information
public $apd_user_proxy;

//--- steps (indexes are different depending on usage/user)
public $apd_first_step;
public $apd_ledger_step;
public $apd_edit_step;
private $apd_chain;

function __construct( $appGlobals ) {
    $this->apd_user_proxy  = $appGlobals->gb_user;
}

function apd_formData_get( $appGlobals, $appChain ) {
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

} // end class

//=====================================================================

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appGlobals = new kcmPay_globals();
$appGlobals->gb_forceLogin ();
$appData = new application_data($appGlobals);

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@         Process Page               @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

$appChain = new Draff_Chain( 'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmPay_globals());
$appChain->chn_register_appData( new application_data());
$appGlobals->gb_forceLogin ();

$appChain->chn_form_register(1,'appForm_ledgerReport_main');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>