<?php

// pay-home.php

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

include_once( '../kcm-kernel/kernel-emitter.inc.php');
include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'pay-system-payData.inc.php' );
include_once( 'pay-system-appEmitter.inc.php' );
include_once( 'pay-system-globals.inc.php' );
include_once( 'pay-system-synchronizer.inc.php');

include_once( 'pay-report-ledger.inc.php' );
include_once( 'pay-report-checkRegister.inc.php');

//=====================================================================

Class appForm_payHome_selectEmployee extends Draff_Form {
public $who_staffStatus;
//public $who_payrollSummary;
public $who_employeeBatch;
public $pmHome_ledgerReport;
public $pmHome_checkReport;


function drForm_processSubmit ( $appData , $appGlobals,  $appChain, $submit ) { // who
    kernel_processBannerSubmits( $appGlobals, $appChain );
    $appChain->chn_form_savePostedData();
    if ( substr($submit,0,6) == '@step_' ) {
       $period = $appGlobals->gb_period_current;;
        switch ( $appChain->chn_submit[0] ) {
            case '@step_1_next':
                $period->prd_statusStep = 2;
                $period->prd_saveRecord( $appGlobals );
                break;
           case '@step_2_view':
                $period->prd_statusStep = 2;  // should be done at end of report - not here
                $period->prd_statusReports = 1;  // should be done at end of report - not here
                $period->prd_saveRecord( $appGlobals );
                $appChain->chn_launch_restartAfterRecordSave(4);
              // $appChain->chn_redirectToKcmUrl('pay-report-ledger.php');
                break;
           case '@step_2_back':
                $period->prd_statusStep = 1;
                $period->prd_saveRecord( $appGlobals );
                break;
           case '@step_2_next':
                $period->prd_statusStep = 3;  // should be done at end of report - not here
                $period->prd_saveRecord( $appGlobals );
                break;
           case '@step_3_view':
                $period->prd_statusStep = 4;  // should be done at end of report - not here
                $period->prd_statusReports = 2;  // should be done at end of report - not here
                $period->prd_saveRecord( $appGlobals );
                $appChain->chn_launch_restartAfterRecordSave($appData->apd_ledger_step);
                //$appChain->chn_redirectToKcmUrl('pay-report-checkRegister.php');
                break;
           case '@step_3_back':
                $period->prd_statusStep = 1;
                $period->prd_statusReports = 0;
                $period->prd_saveRecord( $appGlobals );
                break;
           case '@step_3_next':
                $period->prd_statusStep = 4;  // should be done at end of report - not here
                $period->prd_statusReports = 2;  // should be done at end of report - not here
                $period->prd_saveRecord( $appGlobals );
                break;
            case '@step_4_back':
                $period->prd_statusStep = 1;
                $period->prd_statusReports = 0;
                $period->prd_saveRecord( $appGlobals );
                break;
           case '@step_4_close':
                $period->prd_statusStep = 5;
                $period->prd_saveRecord( $appGlobals );
                break;
            case '@step_5_back':
                $period->prd_statusStep = 1;
                $period->prd_statusReports = 0;
                $period->prd_saveRecord( $appGlobals );
                break;
           case '@step_5_cancel':
                $period->prd_statusStep = 4;
                $period->prd_saveRecord( $appGlobals );
                break;
           case '@step_5_close':
                $this->who_closePayPeriod( $appGlobals );
                break;
            case '@step_9_appr':
                $periodId = $period->prd_payPeriodId;
                //??????????????????????????????????  VERY WRONG since period ID can be zero
                $query = "UPDATE `job:task` SET `jTsk:PayStatusCode` = '1' WHERE `jTsk:@PayPeriodId` = '{$appGlobals->gb_period_current;->prd_payPeriodId}'";
                $result = $appGlobals->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
                break;
           case '@step_9_staffClose':
                $prevEnd = $appGlobals->gb_period_current;->prd_dateEnd;
                $nextStart = draff_dateIncrement( $prevEnd, 1);
                $nextPeriodId = payData_factory::payFactory_get_payPeriodId_ofDate( $appGlobals , $nextStart);
                payData_status_set( $appGlobals , 'OpenPeriod_Staff', $nextPeriodId);
                break;
           case '@step_9_staffOpen':
                payData_status_set( $appGlobals , 'OpenPeriod_Staff', $appGlobals->gb_period_current;->prd_payPeriodId);
                break;
            //case '@step_9_unapproveAll':
            //    $periodId = $appGlobals->gb_period_current;->prd_payPeriodId;
            //    ??????????????????????????????????  VERY WRONG since period ID can be zero
            //    $query = "update `job:task` SET `jTsk:PayStatusCode` = '0' WHERE `jTsk:@PayPeriodId` = '{$appGlobals->gb_period_current;->prd_payPeriodId}'";
            //    $result = $appGlobals->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
            //    break;
       }
    }
    else if ( is_numeric($this->step_init_submit_suffix ) ) {
        $this->step_setShared('#gblEmployeeId',$this->step_init_submit_suffix);
        $appChain->chn_launch_continueChain($appData->apd_ledger_step);
    }
    $appChain->chn_launch_continueChain( $appData->apd_first_step );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData,  $appGlobals, $appChain, $appEmitter ) { // who
    $appEmitter->set_theme( 'theme-report' );
    $appEmitter->set_title('Payroll - ???');
    $appEmitter->set_menu_standard( $appChain, $appGlobals );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    $appEmitter->set_title('Home');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter);
    $appEmitter->set_menu_customize( $appChain, $appGlobals );
    $period = $appGlobals->gb_period_current;;
    $employeeBatch = new payData_employee_batch;
    $employeeBatch->epyBat_read_summary( $appGlobals );
    $payCycleStep = max(1,$period->prd_statusStep);
    if ( ($employeeBatch->epyBat_tot_open >= 1) and ($payCycleStep!=1) ) {
        $payCycleStep = 1;
        $period->prd_statusStep = 1;
        $period->prd_saveRecord( $appGlobals );
    }
    $appEmitter = new kcmPay_emitter($appGlobals, $this);
    $appEmitter->payEmit_init( $appGlobals, $appChain  ,'pmu-home');
    switch ( $payCycleStep ) {
        case 1:
            $this->who_step_chooseWho_initCss ($appEmitter, $appGlobals);
            break;
        case 2:
            $this->who_step_payLedger_initCss ($appEmitter, $appGlobals);
            break;
        case 3:
            $this->who_step_checkRegister_initCss ($appEmitter, $appGlobals);
            break;
        case 4:
            $this->who_step_closePeriod_initCss ($appEmitter, $appGlobals);
            break;
        case 5:
            $this->who_step_closeConfirm_initCss ($appEmitter, $appGlobals);
            break;
         case 99:
            //$this->who_step_initPayPeriod_initCss ($appEmitter, $appGlobals);
            break;
   }
    $this->who_footer_initCss($appEmitter);
    $appEmitter->payEmit_output_start( $appGlobals ,$appChain,$form,'Payroll Home', 'Payroll Home');
    //$appEmitter->zone_menu_toggled();

    //$appEmitter->addOption_styleTag('table.who-footer-table', 'margin:18pt 0pt 6pt 0pt;border:0px none blue;');
    //$appEmitter->addOption_styleTag('td.who-td', 'border:0px none blue;');
    //$appEmitter->addOption_styleTag('td.whoCur-td', 'border:0px none blue; background-color:#ddffdd');
    //$appEmitter->addOption_styleTag('div.who-status-block', 'display:inline-block;  margin:5pt;  border: 1px solid black;');
    //$appEmitter->addOption_styleTag('div.who-status-title', 'display:inline-block; text-align:center; font-size:16pt; width:100%; font-weight:bold;background-color:#ddffdd;border-bottom:3px double #999999');
    //$appEmitter->addOption_styleTag('div.who-status-desc', 'display:inline-block; font-size:14pt;padding:1pt 4pt 1pt 4pt;');
    $appEmitter->addOption_styleTag('button.but-open',  'background-color:#ffcccc;');
    $appEmitter->addOption_styleTag('button.but-close', 'background-color:#ccffcc;');

    switch ( $payCycleStep ) {
        case 1:
            $this->who_step_chooseWho_output ($appEmitter, $appGlobals, $employeeBatch);
            break;
        case 2:
            $this->who_step_payLedger_output ($appEmitter, $appGlobals, $form);
            break;
        case 3:
            $this->who_step_checkRegister_output ($appEmitter, $appGlobals);
            break;
        case 4:
            $this->who_step_closePeriod_output ($appEmitter, $appGlobals);
            break;
        case 5:
            $this->who_step_closeConfirm_output ($appEmitter, $appGlobals);
            break;
//         case 99:
//            $this->who_step_initPayPeriod ($appEmitter, $appGlobals);
//             break;
       default:
               exit('programming error - invalid step');
    }

    $appEmitter->payEmit_output_end();

}

function drForm_initFields( $appData, $appGlobals, $appChain, $form ) { // who
    // buttons are printed directly without defining them
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

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//     //$this->who_employeeBatch = new payData_employee_batch;
//    //$this->who_employeeBatch->epyBat_read_summary( $appGlobals );
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//}
//
function who_step_chooseWho_initCss( $appEmitter , $appGlobals ) {
}

function who_step_chooseWho_output( $appEmitter , $appGlobals , $employeeBatch ) {
    $appEmitter->zone_start('draff-zone-content-report');
    foreach ($employeeBatch->epyBat_empoyeeArray as $employee ) {
        $name = $employee->emp_name;
        $class = $employee->emp_tot_open >= 1 ? 'but-open' : 'but-close';
        $totAll = $employee->emp_tot_open + $employee->emp_tot_accepted;
        $totOpen = $employee->emp_tot_open;
        $totAccepted = $employee->emp_tot_accepted;
        if ( ($totOpen==0) and ($totAccepted==0) ) {
            $status = '(none)';
        }
        else if ( $totOpen==0 ) {
            $status = '<span class="'.$class.'">' . $totAccepted  . ' Approved</span>';
        }
        else {
            $status = '<span class="'.$class.'">' . $totOpen   . ' of ' . $totAll . ' Need Approval</span>';
        }
        $but = '<button type="submit" class="'.$class.'" name="submit" value="@staff_'.$employee->emp_staffId.'">'.$name.'<br>'.$status . '</button>';
        $appEmitter->emit_nrLine($but);
    }

    $appEmitter->zone_end();

    $this->who_footer_emit( 1 , $appEmitter , $appGlobals , $employeeBatch , 1 );
}

function who_step_payLedger_initCss ( $appEmitter , $appGlobals ) {
    $this->pmHome_ledgerReport = new reportStandard_earningDetails;
    $this->pmHome_ledgerReport->ldgRpt_ledgerReport_standardInitStyles( $appEmitter );
}

function who_step_payLedger_output ( $appEmitter, $appGlobals, $form ) {
    $appEmitter->zone_start('draff-zone-content-report');
    if ( $appGlobals->gb_period_current; != NULL ) {
       //??????????????????? really should have a ledger summary (don't need all the fields and items)
        $ledgerPeriod = new payData_ledger_period;
        $ledgerPeriod->ldgPer_read( $appGlobals , $appGlobals->gb_period_current;, NULL);
    }
    if ( $appGlobals->gb_period_current; == NULL ) {
        print "Cannot enter payroll now";
    }
    else {
        $this->pmHome_ledgerReport->ldgRpt_ledgerReport_standardEmit( $appGlobals , $appEmitter, $appGlobals->gb_period_current;,  NULL,     $form );
    }
    $appEmitter->zone_end();
    $this->who_footer_emit(2, $appEmitter,$appGlobals,NULL);
}

function who_step_checkRegister_initCss ( $appEmitter, $appGlobals ) {
    $this->pmHome_checkReport = new stdReport_checkRegister;
    $this->pmHome_checkReport->chkReg_stdReport_init_styles( $appEmitter );
}

function who_step_checkRegister_output ( $appEmitter, $appGlobals ) {
    $appEmitter->zone_start('draff-zone-content-report');
    $this->pmHome_checkReport->chkReg_stdReport_output($appEmitter, $appGlobals,$appGlobals->gb_period_current;);
    $appEmitter->zone_end();
    $this->who_footer_emit( 3 , $appEmitter , $appGlobals , NULL );
}

//function who_step_initPayPeriod ($appEmitter, $appGlobals ) {
//    $appEmitter->zone_start('draff-zone-content-report');
//    print "<br><br><h1> You must create a new pay period and open it<br><br> You should only see this the first time you run payroll</h1>";
//    $appEmitter->zone_end();
//}

function who_step_closePeriod_initCss ( $appEmitter , $appGlobals ) {
}

function who_step_closePeriod_output ( $appEmitter, $appGlobals ) {
    $appEmitter->zone_start('draff-zone-content-report');
    // print "<br><br><br><br>Close pay period and start next pay period<br>This step is not reversable";
    print '<br><br><button type="submit" class="staff" name="submit" value="@step_4_close"><h2>Close pay period and start next pay period<br>This step is not reversable</h2></button>' ;
    print '<br><br><button type="submit" class="staff" name="submit" value="@step_4_back"><h2>Back to Step 1</h2></button>';
    $appEmitter->zone_end();
    $this->who_footer_emit( 4, $appEmitter, $appGlobals , NULL );
}

function who_step_closeConfirm_initCss ( $appEmitter , $appGlobals ) {
}

function who_step_closeConfirm_output ( $appEmitter , $appGlobals ) {
    $appEmitter->zone_start('draff-zone-content-report');
    print "<br><br>";
    print '<br><button type="submit" class="staff" name="submit" value="@step_5_close"><h2>Close this pay period</h2><br><h1>Proceed only if you are sure</h1><br><h2>This step is not reversable</h2></button>' ;
    print '<br><button type="submit" class="staff" name="submit" value="@step_5_back"><h2>Back to Step 1</h2></button>';
    print '<br><button type="submit" class="staff" name="submit" value="@step_5_cancel"><h2>Cancel</h2></button>';
    $appEmitter->zone_end();
    $this->who_footer_emit(4, $appEmitter,$appGlobals,NULL);
}

function who_footer_emit( $curStep , $appEmitter , $appGlobals , $employeeBatch=NULL ) {
    //??????? eliminate use of employee batch if possible - instead use enabled option for button
    $period = $appGlobals->gb_period_current;;
    $appEmitter->zone_start('draff-zone-filters-default');

    $appEmitter->table_start( 'step-table' );
    $appEmitter->row_start( '' );

    $reportStatus = $appGlobals->gb_period_current;->prd_statusReports;
    $allClosed = empty($employeeBatch) ? FALSE : ($employeeBatch->epyBat_tot_open==0);  // ($employeeBatch != NULL) and
    $buttons1 = (!$allClosed) ? '' : ('<button type="submit" class="staff" name="submit" value="@step_1_next"'. $this->who_footer_enabledButon($allClosed) .'>Next</h6></button>');
    $buttons2 =  '<button type="submit" class="staff" name="submit" value="@step_2_back">Back</h6></button>'
              . '<button type="submit" class="staff" name="submit" value="@step_2_next">Approve<br>Payroll Ledger</h6></button>';
// . '<button type="submit" class="staff" name="submit" value="@step_2_view">Review</h6></button>'
    $buttons3 = '<button type="submit" class="staff" name="submit" value="@step_3_back">Back</h6></button>'
              . '<button type="submit" class="staff" name="submit" value="@step_3_next">Approve<br>Gross Pay Report</h6></button>';
    $buttons4 = '';
    $buttons9 = '';
    if ( ($appGlobals->gb_period_current;_type == PAY_PERIODOPEN_ALL)  and ($appGlobals->gb_period_payMasterId == $appGlobals->gb_period_staffId) ) {
        $buttons9  =  '<button type="submit" class="staff" name="submit" value="@step_9_staffClose">Close Current Period to Staff</h6></button>';  // ???? or undo
    }
    else if ( ($appGlobals->gb_period_current;_type == PAY_PERIODOPEN_PM) and ($appGlobals->gb_period_payMasterId != $appGlobals->gb_period_staffId) ) {
        $buttons9  =  '<button type="submit" class="staff" name="submit" value="@step_9_staffOpen">Re-Open Period to Staff</h6></button>';  // ???? or undo
    }
    if ( ! RC_LIVE ) {
        $buttons9 .= '<br><button type="submit" class="staff" name="submit" value="@step_9_appr">Approve All<br>(Testing Only)</button>';
        // $buttons9 .=  '<br><button type="submit" class="staff" name="submit" value="@step_9_unapproveAll">Un-Approve All</h6></button>'
    }
    $this->who_footer_emitStepCell( 1, 'Step 1 of 4' , 'Payroll Entry', 'Approve, Add,<br>Edit transactions', $buttons1, $appEmitter, $curStep, 1);
    $this->who_footer_emitStepCell( 2, 'Step 2 of 4' , 'Approve Payroll Ledger', 'Payroll Ledger must<br>be approved', $buttons2, $appEmitter, $curStep);
    $this->who_footer_emitStepCell( 3, 'Step 3 of 4' , 'Approve Gross Pay Report', 'Gross Pay Report must<br>be approved', $buttons3, $appEmitter, $curStep);
    $this->who_footer_emitStepCell( 4, 'Step 4 of 4' , 'Close Pay Period', 'No changes allowed<br>after this step', $buttons4, $appEmitter, $curStep, 1);
    $this->who_footer_emitStepCell( 9, 'Special Options' , '', '', $buttons9, $appEmitter, 9);

   //  $appEmitter->cell_start('who-td');
   //  print ' Opened=' . $this->who_employeeBatch->epyBat_tot_open;
   //  print ' <br>Accepted=' . $this->who_employeeBatch->epyBat_tot_accepted;
   //  print ' <br>Staff=' . count($this->who_employeeBatch->epyBat_empoyeeArray);
   //  $appEmitter->cell_end('');

    $appEmitter->row_end('');
    $appEmitter->table_end('');

    $appEmitter->zone_end();
}

function who_footer_enabledButon( $enabled ) {
    return $enabled ? '' : ' disabled';
}

function who_footer_emitStepCell( $thisStep , $title1 , $title2 , $desc , $buttons , $appEmitter , $curStep , $options=0 ) {
    $classSuf = (($curStep == $thisStep) or ($curStep==9))? '-cur' : '';
    $appEmitter->cell_start('step-td');
        $appEmitter->div_start('step-block'.$classSuf);
            $appEmitter->div_start('step-title'.$classSuf);
                $appEmitter->content_text($title1.'<br>'.$title2);
            $appEmitter->div_end();
            $appEmitter->div_start('step-desc'.$classSuf);
                if ( $desc !='' ) {
                    if ( ($curStep != $thisStep) or ($options==1) ) {
                      $appEmitter->content_text($desc . '<br>');
                    }
                }
                if ( $curStep == $thisStep ) {
                    print $buttons;
                }
            $appEmitter->div_end();
        $appEmitter->div_end();
    $appEmitter->cell_end();
}

function who_footer_initCss( $appEmitter ) {
    $appEmitter->addOption_styleTag('table.step-table', 'margin:18pt 0pt 6pt 0pt;border:0px none blue;font-size:12pt;');
    $appEmitter->addOption_styleTag('td.step-td', 'border:0px none blue;font-size:12pt;');

    $appEmitter->addOption_styleTag('div.step-block', 'display:inline-block;  padding: 0pt; margin:5pt;  border: 1px solid black;font-size:12pt;');
    $appEmitter->addOption_styleTag('div.step-title', 'background-color:#dddddd; color:#aaaaaa; display:inline-block; text-align:center; font-size:12pt; width:100%; font-weight:bold;border-bottom:3px double #999999;');
    $appEmitter->addOption_styleTag('div.step-desc',  'background-color:#cccccc; color:#aaaaaa; display:inline-block; font-size:14pt;width:100%;');

    $appEmitter->addOption_styleTag('div.step-block-cur', 'display:inline-block;  margin:5pt;  border: 3px double black;font-size:12pt;');
    $appEmitter->addOption_styleTag('div.step-title-cur', 'background-color:#ccffcc;display:inline-block; text-align:center; width:100%;font-size:16pt; font-weight:bold;border-bottom:3px double #999999;font-size:12pt;');
    $appEmitter->addOption_styleTag('div.step-desc-cur',  'background-color:#eeffee;display:inline-block; text-align:center;font-size:14pt;width:100%;font-size:12pt;');

    $appEmitter->addOption_styleTag('button.but-open',  'background-color:#ffcccc;font-size:12pt;');
    $appEmitter->addOption_styleTag('button.but-close', 'background-color:#ccffcc;font-size:12pt;');
}

function who_closePayPeriod( $appGlobals ) {
    $period = $appGlobals->gb_period_current;;
    $period->prd_whenClosed = rc_getNow();
    $period->prd_saveRecord( $appGlobals );  // period is now closed
    if ( $period->prd_periodType == RC_PAYPERIOD_SPECIAL ) {
        $staffPeriodId = payData_status_get( $appGlobals ,'OpenPeriod_Staff' , '');
        payData_status_set( $appGlobals ,'OpenPeriod_PayMaster',$staffPeriodId);  // back to "normal" pay period
        return;
    }
    else {
        $curEnd = $period->prd_dateEnd;
        $nextPeriodStart = draff_dateIncrement($curEnd,1);
        $nextPeriodEnd   = draff_dateIncrement($curEnd,14);
   }
    // Create next and future period (but don't open period yet until closing present one)
    $curStart = $period->prd_dateStart;
    $curEnd = $period->prd_dateEnd;
    // maybe assert days are 14 days apart
    $futurePeriodStart = draff_dateIncrement($nextPeriodEnd,1);
    $futurePeriodEnd  = draff_dateIncrement($nextPeriodEnd,14);
    $nextPeriod   = $this->who_periodGetMatch( $appGlobals , $nextPeriodStart, $nextPeriodEnd);  // can be NULL if does not exist yet
    if ( $nextPeriod == NULL ) {
        $nextPeriod   = payFactory_get_payPeriod_new( $appGlobals ,$futurePeriodStart);
    }
    payData_status_set( $appGlobals ,'OpenPeriod_PayMaster',$nextPeriod->prd_payPeriodId);
    payData_status_set( $appGlobals ,'OpenPeriod_Staff',$nextPeriod->prd_payPeriodId);
    payData_status_set( $appGlobals , 'job_synchronizeWhen_salaried', '' );
         //????? is there a better way of forcing refreshing salaried employees for new pay period
 }

function who_periodGetMatch( $appGlobals , $startDate, $endDate ) {
    $searchStart = draff_dateIncrement($startDate,-14);
    $payBatch = new payData_payPeriod_batch;
    $payBatch -> prdBat_readBatch( $appGlobals , $startDate);
    $matchArray = array();
    foreach ( $payBatch->prdBat_items as $period ) {
        if ( ($period->prd_dateStart >= $startDate) and ($period->prd_dateEnd <= $endDate) ) {
            $matchArray[] = $period;
        }
    }
    $matchCount = count($matchArray);
    if ( $matchCount == 0 ) {
        $newPeriod = new dbRecord_payPeriod();
        $newPeriod->prd_periodType = RC_PAYPERIOD_NORMAL;
        $newPeriod->prd_dateStart = $startDate;
        $newPeriod->prd_dateEnd  = $endDate;
        $newPeriod->prd_setName(NULL);
        $newPeriod->prd_saveRecord( $appGlobals ); // add new record - will set record id
        return $newPeriod;
    }
    else if ( $matchCount == 1 ) {
        $period = $matchArray[0];
        if ( ($period->prd_dateStart==$startDate) and ($period->prd_dateEnd==$endDate) ) {
            return $period;
       }
       else {
           // have conflict - so not creating new period as another period is already present but not the standard two week range
           return NULL;
       }
    }
}

} // end class

//=====================================================================

Class appForm_payHome_editLedger extends Draff_Form {
public $ledger_param_staffId;
//public $ledger_staffObject;
public $ledger_payrollEmployee;
public $ledger_employeeId;
public $ledger_taskGroupId;

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $appGlobals->gb_synchronize_message = '';
//    $this->ledger_employeeId = $this->step_getShared('#gblEmployeeId',NULL);
//    $employeeId = $this->ledger_employeeId;
//    if ( $employeeId == NULL ) {
//        $employeeId = $appGlobals->gb_user->krnUser_staffId;
//        $this->step_setShared('#gblEmployeeId',$employeeId);
//    }
//    //?????????? is this the best way of selecting the staff - can be coming from chooseWho or not
//    $this->ledger_param_staffId = $employeeId; // ($employeeId===NULL) ? $appData->apd_user_proxy->krnUser_staffId : $employeeId;
//    //$this->ledger_staffObject = new dbRecord_payEmployee;
//    // $this->ledger_staffObject->emp_read($appGlobals , $this->ledger_param_staffId);
//    if ( $appGlobals->gb_period_current; != NULL ) {
//        $this->ledger_payrollEmployee = new payData_ledger_employee($this->ledger_param_staffId);
//        $this->ledger_payrollEmployee->ldgEpy_read( $appGlobals ,  $appGlobals->gb_period_current;);
//    }
//
//}
//
//function drForm_validate( $appGlobals, $appChain , $appData ) {   // bundle
//}
//
function drForm_processSubmit( $appData , $appGlobals,  $appChain, $submit ) {  // bundle
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
    if ( $appChain->chn_submit[0] == '@cancel' ) {
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_cancelChain(1,'');
    }
    $appChain->chn_form_savePostedData();
    $sub = explode('_',$submit);
    $this->ledger_taskGroupId = count($sub)>=2 ? $sub[2] : 0;
    $taskGroupId = $this->ledger_taskGroupId;
 //   krnLib_assert(count($sub)==7,'PayItem select - improper parameters');
    $action       = $sub[0];
    $employeeId   = $this->ledger_employeeId;
    if ( $appData->apd_user_isMaster ) {
        if ( $submit == '@next' ) {
            $employeeBatch = new payData_employee_batch;
            $employeeBatch->epyBat_read_summary( $appGlobals );
            $employeeKeys = array_keys($employeeBatch->epyBat_empoyeeArray);
            $employeeIndex = array_search($employeeId,$employeeKeys);
            if ( ($employeeIndex===FALSE) or ($employeeIndex+1>=count($employeeKeys)) ){
                 // message ????
                $this->step_setShared('#gblEmployeeId',0);  //????????????
                $appChain->chn_curStream_Clear();
                $appChain->chn_launch_continueChain(1);
                return;
            }
            $employeeId = $employeeKeys[$employeeIndex+1];
            $this->step_setShared('#gblEmployeeId',$employeeId);  //????????????
            $appChain->chn_launch_continueChain($appData->apd_ledger_step);
            return;
        }
        if ( $appChain->chn_submit[0] == '@prev' ) {
            $employeeBatch = new payData_employee_batch;
            $employeeBatch->epyBat_read_summary( $appGlobals );
            $employeeKeys = array_keys($employeeBatch->epyBat_empoyeeArray);
            $employeeIndex = array_search($employeeId,$employeeKeys);
            if ( ($employeeIndex===FALSE) or ($employeeIndex<1) ){
                // message ????
                $appChain->chn_curStream_Clear();
                $appChain->chn_launch_continueChain(1);
                return;
            }
            $employeeId = $employeeKeys[$employeeIndex-1];
            $this->step_setShared('#gblEmployeeId',$employeeId);
            $appChain->chn_launch_continueChain($appData->apd_ledger_step);
            return;
        }
        if ( $action=='@ledgerSaveApproved' ) {
            $appGlobals->gb_period_current;->prd_clearReportsFlag( $appGlobals );
            $taskGroup = payData_factory::payFactory_get_taskGroup_fromDb( $appGlobals , $taskGroupId);
            $taskFinal = $taskGroup->tskGrp_get_finalItem();
            $taskFinal->tskItem_payStatus = PAY_PAYSTATUS_APPROVED;
            $taskGroup->tskGrp_save_finalRecord( $appGlobals );
            $appChain->chn_launch_restartAfterRecordSave($appData->apd_ledger_step);
            return;
        }
        if ( $action=='@undoApprove' ) {
            $appGlobals->gb_period_current;->prd_clearReportsFlag( $appGlobals );
            $taskGroup = payData_factory::payFactory_get_taskGroup_fromDb( $appGlobals , $taskGroupId);
            $taskFinal = $taskGroup->tskGrp_get_finalItem();
            $taskFinal->tskItem_payStatus = PAY_PAYSTATUS_UNAPPROVED;
            $taskGroup->tskGrp_save_finalRecord( $appGlobals );
            $appChain->chn_launch_restartAfterRecordSave($appData->apd_ledger_step);
            return;
        }
    }
    if ( $action=='@add' ) {
        $this->step_setShared('#ipAction' , '@add');
        $this->step_setShared('#taskGroupId',0);
        //$this->step_setShared('#gblEmployeeId',$employeeId);  //??????????????
        //$this->step_setShared('#gblEmployeeId',$employeeId);
        //$this->step_setShared('#payRecId',0);
        //$this->step_setShared('#ipSchedId',0);
        //$this->step_setShared('#ipSourceFrom',$sourceFrom);
        //$this->step_setShared('#ipSourceDest',$sourceDest);
        $appChain->chn_launch_continueChain($appData->apd_edit_step);
        return;
    }
    if ( ($action=='@overView') or ($action=='@ledgerCreateOverride') or ($action=='@edit') ) {
        $this->step_setShared('#ipAction',$action);
        $this->step_setShared('#taskGroupId',$taskGroupId);
        //$this->step_setShared('#gblEmployeeId',$employeeId);
        //$this->step_setShared('#gblEmployeeId',$employeeId);

        //$this->step_setShared('#payRecId',$payRecId);
        //$this->step_setShared('#ipSchedId',$schedDateId);
        //$this->step_setShared('#ipSourceFrom',$sourceFrom);
        //$this->step_setShared('#ipSourceDest',$sourceDest);
        $appChain->chn_launch_continueChain($appData->apd_edit_step);
        return;
    }
    // should never get here
    $appChain->chn_launch_continueChain($appData->apd_first_step);
    return;
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml($appData, $appGlobals, $appChain, $appEmitter ) {  // bundle
    $appEmitter->set_theme( 'theme-report' );
    $appEmitter->set_title('Payroll - ???');
    $appEmitter->set_menu_standard( $appChain, $appGlobals );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    $appEmitter->set_title('Home');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter);
   $appEmitter->set_menu_customize( $appChain, $appGlobals );
    $appEmitter = new kcmPay_emitter($appGlobals, $form);

    $pmHome_payLedger = new reportStandard_earningDetails;
    $pmHome_payLedger->ldgRpt_ledgerReport_standardInitStyles($appEmitter);

    $appEmitter->payEmit_init( $appGlobals, $appChain  ,'pmu-home');
    $appEmitter->payEmit_output_start( $appGlobals ,$appChain,$form,'Payroll Home', 'Payroll Home');

    //$appEmitter->zone_menu_toggled();

    $appEmitter->zone_start('draff-zone-content-report');
    if ( $appGlobals->gb_period_current; == NULL ) {
        print "Cannot enter payroll now";
    }
    else {
        $pmHome_payLedger->ldgRpt_ledgerReport_standardEmit( $appGlobals, $appEmitter, $appGlobals->gb_period_current;, $this->ledger_param_staffId, $form);
    }


    $appEmitter->zone_end();
    if ( $appData->apd_user_isMaster ) {
        $appEmitter->zone_start('draff-zone-filters-default');
        $appEmitter->content_block (array('@cancel' , '@next' , '@prev'));
        $appEmitter->zone_end();
    }

    $appEmitter->payEmit_output_end();

}

function drForm_initFields( $appData, $appGlobals, $appChain ) {  // bundle
    // buttons on the ledger report are printed directly without defining them
    if ( $appGlobals->gb_period_current; == NULL ) {
        return;
    }
    if ( $appData->apd_user_isMaster ) {
        $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
        $this->drForm_addField( new Draff_Button( '@next' , 'Next Person') );
        $this->drForm_addField( new Draff_Button( '@prev' , 'Previous Person') );
    }
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

//=====================================================================

Class appForm_payHome_editPayItem extends Draff_Form {

public $edit_param_action;
public $edit_param_employeeId;
public $edit_param_taskGroupId;

public $edit_taskGroup;  // group that is being edited
public $edit_taskFinal;  // final item in group
public $edit_employee;

public $edit_isEvent;
public $edit_isViewOnly;
public $edit_isPM;
public $edit_isOwner     = FALSE;  // same person that also originated the record
public $edit_isNoOptions = FALSE;   // cannot override, delete, save, etc  - viewonly should always also be true

public $edit_ctr_approveCheckbox  = FALSE;
public $edit_ctr_saveButton       = TRUE;
public $edit_ctr_overrideButton   = FALSE;
public $edit_ctr_deleteButton     = FALSE;

//public $edit_isOverridable = FALSE;

// Input values which must be verified before saving to item (item and display format are different)
public $edit_in_timeStart_hour;
public $edit_in_timeStart_minute;
public $edit_in_timeStart_status;
public $edit_in_timeEnd_hour;
public $edit_in_timeEnd_minute;
public $edit_in_timeEnd_status;
public $edit_in_timeAdjust_value;
public $edit_in_timePrep_value;
public $edit_in_timeAdjust_status;
public $edit_in_jobRate_override;
public $edit_in_jobRate_status;

public $edit_combo_time_hour;
public $edit_combo_time_minute;
public $edit_combo_time_status;
public $edit_combo_time_method;
public $edit_combo_job_rate;

public $edit_error_array;

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $appGlobals->gb_synchronize_message = '';
//
//    //--- get "parameters" for this step passed by previous step
//    $this->edit_param_action        = $this->step_getShared('#ipAction',  NULL);
//    $this->edit_param_taskGroupId   = $this->step_getShared('#taskGroupId', NULL);  // 0 = new taskGroup
//    $this->edit_param_employeeId    = $this->step_getShared('#gblEmployeeId', NULL);  // necessary for new item
//    //$this->edit_param_sourceDest  = $this->step_getShared('#ipSourceDest', NULL);  // is always 1, 2, 3, or 4
//    //$this->edit_param_itemId      = $this->step_getShared('#payRecId',  NULL);  // can be zero
//    //$this->edit_param_schedDateId = $this->step_getShared('#ipSchedId', NULL);
//    //$this->edit_param_sourceFrom  = $this->step_getShared('#ipSourceFrom', NULL);  // is always 1, 2, 3, or 4
//
//    // get job data
//    $appGlobals->gb_load_global_employees($this->edit_param_employeeId);
//    $newOriginCode  = $appGlobals->gb_proxyIsPayMaster ? RC_JOB_ORIGIN_PM : RC_JOB_ORIGIN_STAFF;
//    switch ($this->edit_param_action ) {
//        case '@edit':
//            $this->edit_taskGroup = payData_factory::payFactory_get_taskGroup_fromDb( $appGlobals , $this->edit_param_taskGroupId);
//            $this->edit_taskFinal = $this->edit_taskGroup->tskGrp_get_finalItem();
//            //$this->edit_jobOriginal = payData_factory::payFactory_create_jobItem_clone($this->edit_taskGroup);
//            break;
//        case '@add':
//            $this->edit_taskGroup = payData_factory::payFactory_get_taskGroup_new_blank( $appGlobals , $this->edit_param_employeeId,$newOriginCode, $appGlobals->gb_period_current;->prd_payPeriodId);
//            $this->edit_taskFinal = $this->edit_taskGroup->tskGrp_get_finalItem();
//            //$this->edit_jobOriginal = NULL;
//            break;
//        case '@overView':
//            $this->edit_taskGroup = payData_factory::payFactory_get_taskGroup_fromDb( $appGlobals , $this->edit_param_taskGroupId);
//            $this->edit_taskFinal = $this->edit_taskGroup->tskGrp_get_finalItem();
//            break;
//        case '@ledgerCreateOverride':
//            $this->edit_taskGroup = payData_factory::payFactory_get_taskGroup_withClonedOverride( $appGlobals , $this->edit_param_taskGroupId, $newOriginCode);
//            $this->edit_taskFinal = $this->edit_taskGroup->tskGrp_get_finalItem();
//            break;
//        default:
//            assert(FALSE,'unknown action for edit pay ledger record');
//            break;
//    }
//
//    $this->edit_in_timeStart_status = '';
//    $this->edit_in_timeEnd_status = '';
//    $this->edit_in_timeAdjust_status = '';
//    $this->edit_in_timeStart_hour = '00';
//    $this->edit_in_timeStart_minute = '00';
//    $this->edit_in_timeStart_hour   = substr($this->edit_taskFinal->tskItem_job_time_start,0,2);
//    $this->edit_in_timeStart_minute = substr($this->edit_taskFinal->tskItem_job_time_start,3,2);
//    $this->edit_in_timeEnd_hour = '00';
//    $this->edit_in_timeEnd_minute = '00';
//    $this->edit_in_timeEnd_hour     = substr($this->edit_taskFinal->tskItem_job_time_end,0,2);
//    $this->edit_in_timeEnd_minute   = substr($this->edit_taskFinal->tskItem_job_time_end,3,2);
//
//    $this->edit_in_jobRate_status = '';
//    $this->edit_employee = $appGlobals->gb_employeeArray[$this->edit_taskFinal->tskItem_staffId];
//    $this->edit_taskGroup->tskItem_job_time_start = $this->edit_in_timeStart_hour . ':' . $this->edit_in_timeStart_minute . ':00';
//    $this->edit_taskGroup->tskItem_job_time_end   = $this->edit_in_timeEnd_hour   . ':' . $this->edit_in_timeEnd_minute   . ':00';
//    //--- update fields for this step
//    $taskFinal = $this->edit_taskGroup->tskGrp_get_finalItem();
//
//    $this->step_updateIfPosted('@jobDesc',           $taskFinal->tskItem_job_notes          );
//    $this->step_updateIfPosted('@dateOfJob',         $taskFinal->tskItem_job_date       );
//    $this->step_updateIfPosted('@timeStartHour',     $this->edit_in_timeStart_hour        );
//    $this->step_updateIfPosted('@timeStartMinute',   $this->edit_in_timeStart_minute       );
//    $this->step_updateIfPosted('@timeEndHour',       $this->edit_in_timeEnd_hour        );
//    $this->step_updateIfPosted('@timeEndMinute',     $this->edit_in_timeEnd_minute       );
//    $this->step_updateIfPosted('@timeAdjustMethod',  $taskFinal->tskItem_override_timeMethod      );
//    $this->step_updateIfPosted('@timeAdjustMinutes', $taskFinal->tskItem_override_timeMinutes  );
//    $this->step_updateIfPosted('@timeStatus',        $taskFinal->tskItem_job_atendanceCode );
//    $this->step_updateIfPosted('@jobLocation',       $taskFinal->tskItem_job_location      );
//    $this->step_updateIfPosted('@jobDesc',           $taskFinal->tskItem_job_notes      );
//    $this->step_updateIfPosted('@ledgerApproveCheckbox', $taskFinal->tskItem_payStatus , PAY_PAYSTATUS_UNAPPROVED);
//    if ( $taskFinal->tskItem_override_rateAmount == 0 ) {
//        $taskFinal->tskItem_override_rateAmount = '';
//    }
//    $this->step_updateIfPosted('@jobRateMethod',     $taskFinal->tskItem_job_rateCode      );
//    $this->step_updateIfPosted('@jobRateOverride',     $taskFinal->tskItem_override_rateAmount );
//    $this->step_updateIfPosted('@overExpain',        $taskFinal->tskItem_override_explanation      );
//    // if ( $this->edit_changed ) {
//    //    $taskFinal->tskItem_payStatus = PAY_PAYSTATUS_UNAPPROVED;
//    // }
//
//    $this->edit_in_timeStart_status = '';
//    $this->edit_in_timeEnd_status = '';
//    $this->edit_in_timeAdjust_status = '';
//    $this->edit_in_jobRate_status = '';
//
//    $this->edit_isEvent = !empty($taskFinal->tskItem_event_eventId);
//    $this->edit_isViewOnly = ($this->edit_param_action=='@overView');
//    $this->edit_isPM = $appGlobals->gb_proxyIsPayMaster;
//    $this->edit_isOwner = FALSE;
//    $this->edit_ctr_deleteButton = FALSE;
//    $this->edit_ctr_approveCheckbox = $this->edit_isPM;  // only PM can approve
//    if ( $taskFinal->tskItem_isPeriod_closed ) {
//        $this->edit_isViewOnly = TRUE;
//        $this->edit_isNoOptions = TRUE;
//    }
//    else {
//        if ($this->edit_isPM) {
//            $this->edit_isOwner = ($taskFinal->tskItem_originCode == RC_JOB_ORIGIN_PM);
//            $this->edit_ctr_overrideButton = $this->edit_isViewOnly;
//        }
//        else {
//            if ( ($taskFinal->tskItem_originCode ==RC_JOB_ORIGIN_TRAVEL) or ($taskFinal->tskItem_originCode ==RC_JOB_ORIGIN_SALARY) ) {
//                $this->edit_isViewOnly = TRUE;
//                $this->edit_isNoOptions = TRUE;
//            }
//           $this->edit_isOwner = ( ($taskFinal->tskItem_originCode == RC_JOB_ORIGIN_STAFF) and ($taskFinal->tskItem_staffId==$appGlobals->gb_user->krnUser_staffId) );
//        }
//        $this->edit_ctr_deleteButton = ($this->edit_isOwner and (!$this->edit_isViewOnly) and ($taskFinal->tskItem_taskId>=1) );
//    }
//    $this->edit_validate_fields( $appGlobals ,$this->edit_taskGroup);
//    //    $this->step_updateIfPosted('@jobRateOverride',  $taskFinal->pay_rateAmountData       );
//    //$jobRateCode = $this->step_getPosted('@jobCombo_array', NULL);
//    $this->edit_init_combos( $appGlobals ,$this->edit_taskGroup);
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {  // edit
//    // no errors when $this->edit_error_array is empty - see note for edit_validate_fields function
//    foreach( $this->edit_error_array as $fieldKey => $message ) {
//        $appChain->chn_message_set($fieldKey,$message);
//    }
//}

function drForm_processSubmit ( $appData , $appGlobals,  $appChain ) {  // edit
    kernel_processBannerSubmits( $appGlobals, $appChain );
    $sub = explode('_',$submit);
    $submit = $sub[0];
    if ( $appChain->chn_submit[0] == '@cancel' ) {
        $appChain->chn_message_set('Cancelled');
        $appChain->chn_launch_cancelChain(1,'');
    }
    $appChain->chn_form_savePostedData();
    $appChain->chn_ValidateAndRedirectIfError();
    $taskFinal = $this->edit_taskFinal;
    //$this->drForm_validate( $appData, $appGlobals, $appChain ) ;
    if ( $appChain->chn_submit[0] == '@delete' ) {
        $appGlobals->gb_period_current;->prd_clearReportsFlag( $appGlobals );
        $message = 'Deleted Record:'. $this->edit_employee->emp_name . ' &nbsp;&nbsp;&nbsp; ' . draff_dateAsString($taskFinal->tskItem_job_date,'D, M j, Y') . ' &nbsp;&nbsp;&nbsp; ' . $taskFinal->tskItem_job_location;
        $appChain->chn_message_set($message);
        $this->edit_taskGroup->tskGrp_delete_finalItem( $appGlobals );
        $this->step_clearPosted(); // keep appChain - but clear posted for this step
        $appChain->chn_launch_continueChain($appData->apd_ledger_step);
    }
    if ( $appChain->chn_submit[0] == '@ledgerCreateOverride' ) {
        $this->step_setShared('#ipAction' , '@ledgerCreateOverride');
        $appChain->chn_launch_continueChain($appData->apd_edit_step);
    }
    if ( ($appChain->chn_submit[0] == '@ledgerSaveAsIs') or ($submit == '@ledgerSaveApproved')  or ($submit == '@ledgerSaveUnapproved') ) {
        $taskFinal = $this->edit_taskGroup->tskGrp_get_finalItem();
        if ($appChain->chn_submit[0] == '@ledgerSaveApproved') {
            $taskFinal->tskItem_payStatus = PAY_PAYSTATUS_APPROVED;
        }
        if ($appChain->chn_submit[0] == '@ledgerSaveUnapproved') {
            $taskFinal->tskItem_payStatus = PAY_PAYSTATUS_UNAPPROVED;
        }
        $appGlobals->gb_period_current;->prd_clearReportsFlag( $appGlobals );
        $message = 'Saved Record:'. $this->edit_employee->emp_name . ' &nbsp;&nbsp;&nbsp; ' . draff_dateAsString($taskFinal->tskItem_job_date,'D, M j, Y') . ' &nbsp;&nbsp;&nbsp; ' . $taskFinal->tskItem_job_location;
        $appChain->chn_message_set($message);
        $this->edit_taskGroup->tskGrp_save_finalRecord( $appGlobals );
        $this->step_clearPosted(); // keep appChain - but clear posted for this step
        $appChain->chn_launch_restartAfterRecordSave($appData->apd_ledger_step);
    }
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml($appData, $appGlobals, $appChain, $appEmitter ) {  // edit
    $appEmitter->set_theme( 'theme-report' );
    $appEmitter->set_title('Payroll - ???');
    $appEmitter->set_menu_standard( $appChain, $appGlobals );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    $appEmitter->set_title('Home');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter);
    $appEmitter->set_menu_customize( $appChain, $appGlobals );
    $outReport_ledgerEdit = new reportStandard_ledgerEdit;

    $appEmitter = new kcmPay_emitter($appGlobals, $form);
    $outReport_ledgerEdit->ldgEdit_stdReport_init_styles($appEmitter);
    $appEmitter->payEmit_init( $appGlobals, $appChain  ,'pmu-home');
    $appEmitter->payEmit_output_start( $appGlobals ,$appChain,$form,'Payroll Home', 'Payroll Home');

    //$appEmitter->zone_menu_toggled();


    $appEmitter->payEmit_output_end();

}

function drForm_initFields( $appData, $appGlobals, $appChain ) {   // edit

    $payItem = $this->edit_taskGroup->tskGrp_get_finalItem();
    if ( empty($payItem->tskItem_job_time_start) ) {
        $this->edit_in_timeStart_hour   = 'na';
        $this->edit_in_timeStart_minute = 'na';
    }
    else {
        $this->edit_in_timeStart_hour   = substr($payItem->tskItem_job_time_start,0,2);
        $this->edit_in_timeStart_minute = substr($payItem->tskItem_job_time_start,3,2);
    }
    if ( empty($payItem->tskItem_job_time_end) ) {
         $this->edit_in_timeEnd_hour     = 'na';
         $this->edit_in_timeEnd_minute    = 'na';
    }
    else {
        $this->edit_in_timeEnd_hour     = substr($payItem->tskItem_job_time_end,0,2);
        $this->edit_in_timeEnd_minute   = substr($payItem->tskItem_job_time_end,3,2);
    }
    //---- When ----------------------------------------------
    $this->drForm_addField( new Draff_Date  ('@dateOfJob',     $payItem->tskItem_job_date,array('size'=>20));
    $this->drForm_addField( new Draff_Combo('@timeStartHour',      $this->edit_in_timeStart_hour,$this->edit_combo_time_hour ) );
    $this->drForm_addField( new Draff_Combo('@timeStartMinute',    $this->edit_in_timeStart_minute, $this->edit_combo_time_minute ) );
    $this->drForm_addField( new Draff_Combo('@timeEndHour',        $this->edit_in_timeEnd_hour, $this->edit_combo_time_hour ) );
    $this->drForm_addField( new Draff_Combo('@timeEndMinute',      $this->edit_in_timeEnd_minute, $this->edit_combo_time_minute ) );
    $this->drForm_addField( new Draff_Text  ('@timePrep',     $payItem->tskItem_event_prepTime, array('size'=>'6') ));
    $this->drForm_addField( new Draff_Combo('@timeAdjustMethod',   $payItem->tskItem_override_timeMethod, $this->edit_combo_time_method ) );
    $this->drForm_addField( new Draff_Text  ('@timeAdjustMinutes',     $this->edit_in_timeAdjust_value, array('size'=>'6') ));
    $this->drForm_addField( new Draff_Combo('@timeStatus',         $payItem->tskItem_job_atendanceCode, $this->edit_combo_time_status ) );

    //---- Job ----------------------------------------------
    $this->drForm_addField( new Draff_Text  ('@jobLocation',   $payItem->tskItem_job_location,array('size'=>50));
    $this->drForm_addField( new Draff_Text  ('@jobDesc',   $payItem->tskItem_job_notes,  array('size'=>50));
    $this->drForm_addField( new Draff_Combo('@jobRateMethod',  $payItem->tskItem_job_rateCode, $this->edit_combo_job_rate) );
    $this->drForm_addField( new Draff_Text  ('@jobRateOverride',   $payItem->tskItem_override_rateAmount,array('size'=>12));

    //---- Overrides ----------------------------------------------
    $this->drForm_addField( new Draff_Text  ('@overExpain',  $payItem->tskItem_override_explanation,array('size'=>50)) );
    if ($this->edit_isViewOnly) {
        $this->drForm_disable();
    }
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
    if ($this->edit_isPM) {
        $this->drForm_addField( new Draff_Checkbox ('@ledgerApproveCheckbox', $payItem->tskItem_payStatus, 'Approved' , PAY_PAYSTATUS_APPROVED,PAY_PAYSTATUS_UNAPPROVED ) );
    }
    $this->drForm_addField( new Draff_Button( '@ledgerSaveAsIs' , 'Save') );
    //$this->drForm_addField( new Draff_Button( @ledgerSaveApproved','Save (Approved)') );
    //$this->drForm_addField( new Draff_Button( '@ledgerSaveUnapproved','Save (Not Approved)') );
    // if ( $this->$edit_isOverridable) {
    $this->drForm_addField( new Draff_Button( '@ledgerCreateOverride','Override' ));
    if ( $this->edit_isEvent ) {
        $this->drForm_disable(array('@jobLocation','@dateOfJob' , '@whenDateThru'));
    }
}

function edit_init_combos( $appGlobals , $taskGroup ) {
    $finalItem = $taskGroup->tskGrp_get_finalItem();
    $employee = $appGlobals->gb_employeeArray[$finalItem->tskItem_staffId];
    $this->edit_combo_time_hour = array(
        ''=>'n/a',  // NULL
        '08'=>' 8am',
        '09'=>' 9am',
        '10'=>'10am',
        '11'=>'11am',
        '12'=>'12pm',
        '13'=>' 1pm',
        '14'=>' 2pm',
        '15'=>' 3pm',
        '16'=>' 4pm',
        '17'=>' 5pm',
        '18'=>' 6pm',
        '19'=>' 7pm',
        '20'=>' 8pm',
        '21'=>' 9pm',
        '22'=>'10pm',
        '23'=>'11pm',
        '00'=>'12am',
        '01'=>' 1am',
        '02'=>' 2am',
        '03'=>' 3am',
        '04'=>' 4am',
        '05'=>' 5am',
        '06'=>' 6am',
        '07'=>' 7am'
     );

    $this->edit_combo_time_minute = array(
        ''=>'n/a',  // NULL
        '00'=>':00',
        '05'=>':05',
        '10'=>':10',
        '15'=>':15',
        '20'=>':20',
        '25'=>':25',
        '30'=>':30',
        '35'=>':35',
        '40'=>':40',
        '45'=>':45',
        '50'=>':50',
        '55'=>':55'
    );

    $this->edit_combo_time_status = array();
    if ( $finalItem->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL ) {
        $this->edit_combo_time_status[RC_JOB_ATTEND_PRESENT] = 'Applicable';
        $this->edit_combo_time_status[RC_JOB_ATTEND_NA] = 'N/A';
    }
    else {
        $this->edit_combo_time_status[RC_JOB_ATTEND_PRESENT] = 'Present';
        if ( $this->edit_isEvent and $this->edit_isPM ) {
            $this->edit_combo_time_status[RC_JOB_ATTEND_PIF] = 'Present (PIF)';
        }
        $this->edit_combo_time_status[RC_JOB_ATTEND_SICK] = 'Sick';
        $this->edit_combo_time_status[RC_JOB_ATTEND_VACATION] = 'Vacation';
        $this->edit_combo_time_status[RC_JOB_ATTEND_ABSENT_UNEXCUSED] = 'Absent';
        $this->edit_combo_time_status[RC_JOB_ATTEND_NA] = 'N/A';
    }

    if ( $finalItem->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL ) {
        $this->edit_combo_time_method = array (
          '0'=>'(Default)',
          '1'=>'Time Override',
        );
    }
    else {
        $this->edit_combo_time_method = array (
          PAY_TIMEOVERRIDE_FALSE => '(N/A)',
          PAY_TIMEOVERRIDE_TRUE =>'Time Override',
          //PAY_TIMEADJUST_ADDITIONAL =>'Additional Time',
          //PAY_TIMEADJUST_MINUS=>'Minus Time',
        );
    }

    $this->edit_combo_job_rate = array();
    if ( $finalItem->tskItem_originCode == RC_JOB_ORIGIN_SALARY ) {
        $this->edit_combo_job_rate[3] = 'Salary Rate ('.$employee->emp_rateSalary.')';
    }
    else {
        if ( !empty($employee->emp_rateAdmin) ) {
            $this->edit_combo_job_rate[1] = 'Admin Rate ('.$employee->emp_rateAdmin.')';
        }
        if ( !empty($employee->emp_rateField) ) {
            $this->edit_combo_job_rate[2] = 'Field Rate ('.$employee->emp_rateField.')';
        }
        if ( !$finalItem->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL ) {
           if ( !empty($employee->emp_rateSalary) ) {
                $this->edit_combo_job_rate[3] = 'Salary Rate ('.$employee->emp_rateSalary.')';
            }
        }
        $this->edit_combo_job_rate['21'] ='Override is pay rate';
    }
    $this->edit_combo_job_rate['22'] ='Override is total pay';
}

function edit_addTransaction($appData, $appGlobals, $employeeId ) {
    $sourceType = $appData->apd_user_isMaster ? RC_JOB_ORIGIN_PM : RC_JOB_ORIGIN_STAFF;
    //????????????????%%%%%%%%%%%%%???????????????????????????? below not defined
    $taskGroup = payData_task_item::psItem_create_new_emptyTransaction( $appGlobals , $employeeId, $sourceType);
    return $taskGroup;
}

private function edit_add_error($fieldKey, $message ) {
    if ( !isset($this->edit_error_array[$fieldKey]) ) {
        // use only first message for each field
        $this->edit_error_array[$fieldKey] = $message;
    }
}

function edit_validate_fields( $appGlobals , $taskGroup ) {
    // Not Standard Validation logic: this function is called from step_init_submit_accept and not from edit_processValidate
    // ...  The validation is done this way, as several input fields need validation before combining into a single field, etc
    // .... The results of this validation is saved in an error array to be used later by edit_processValidate
    // if ( $taskGroup->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL ) { //???????? need to skip some checking as many fields are read only or not used

//  //   //       $this->pay_chain = $appChain;
    $finalItem = $taskGroup->tskGrp_get_finalItem();
    if ( $finalItem->tskItem_originCode == RC_JOB_ORIGIN_TRAVEL ) {
        return;
        //??????????????????????? need seperate validation for travel
    }
    $this->edit_validate_when( $appGlobals ,$finalItem);
    $this->com_mustExplain = FALSE;
    $this->edit_validate_job($finalItem);
    $this->edit_validate_explanation($finalItem);
}

function edit_validate_when( $appGlobals ,$taskGroup ) {
    $payItem = $taskGroup;  //???????????????
    $this->edit_error_array = array();

   // validate date
    $date = $payItem->tskItem_job_date;
    if ( checkdate ( substr($date,5,2), substr($date,8,2),substr($date,0,4) ) === FALSE ) {
        $this->edit_add_error('@dateOfJob' , 'Date must be valid');
    }
    if ( $date <  RC_PAYPERIOD_EARLIEST_DATE ) {
        $this->edit_add_error('@dateOfJob' , 'Date must not be before '.RC_PAYPERIOD_EARLIEST_DATE);
    }
    $payEndDate = $appGlobals->gb_period_current;->prd_dateEnd;
    if ( $date > $payEndDate ) {
        $this->edit_add_error('@dateOfJob' , 'Date must not be after the end of the pay period ('.$payEndDate.')');
    }

    // Validate Time Start
    if ( ($this->edit_in_timeStart_hour=='') or ($this->edit_in_timeStart_minute=='') ) {
        if ( ($this->edit_in_timeStart_hour=='') and ($this->edit_in_timeStart_minute=='') ) {
            $this->edit_in_timeStart_status = 'e';  // empty value (both n/a)
        }
        else {
            $this->edit_in_timeStart_status = 'x';  // invalid  (hours and minuutes must be both n/a or neither n/a)
            $this->edit_add_error('@timeStartHour', 'Time Worked: Both times must be "n/a", or both not "n/a"' );
            $this->edit_add_error('@timeStartMinute', 'Time Worked: Both times must be "n/a", or both not "n/a"' );
        }
        $payItem->tskItem_job_time_start = '';
    }
    else {
        $this->edit_in_timeStart_status = 'v';  // valid value (not n/a)
        $payItem->tskItem_job_time_start = $this->edit_in_timeStart_hour . ':' . $this->edit_in_timeStart_minute . ':00';
    }

    // Validate Time End
    if ( ($this->edit_in_timeEnd_hour=='') or ($this->edit_in_timeStart_minute=='') ) {
        if ( ($this->edit_in_timeEnd_hour=='') and ($this->edit_in_timeEnd_minute=='') ) {
            $this->edit_in_timeEnd_status = 'e';  // empty value (both n/a)
        }
        else {
            $this->edit_in_timeEnd_status = 'x';  // invalid  (hours and minuutes must be both n/a or neither n/a)
            $this->edit_add_error('@timeEndHour', 'Time Worked: Both times must be "n/a", or both not "n/a"' );
            $this->edit_add_error('@timeEndMinute', 'Time Worked: Both times must be "n/a", or both not "n/a"' );
        }
        $payItem->tskItem_job_time_end = '';
    }
    else {
        $this->edit_in_timeEnd_status = 'v';  // valid value (not n/a)
        $payItem->tskItem_job_time_end = $this->edit_in_timeEnd_hour . ':' . $this->edit_in_timeEnd_minute . ':00';
    }
    // Validate Time Adjustment / Override
    $minutes = $this->edit_convert_toMinutes($payItem->tskItem_override_timeMinutes);
    if ( $minutes===NULL ) {
        $this->edit_in_timeAdjust_status = 'x';  // error
    }
    else if ( $minutes==0 ) {
        $this->edit_in_timeAdjust_status = 'e';  // empty
        $payItem->tskItem_override_timeMinutes = 0;
    }
    else {
        $payItem->tskItem_override_timeMinutes = $minutes;
        $this->edit_in_timeAdjust_status = 'v';  // valid value
    }
    $this->edit_in_timeAdjust_value = draff_minutesAsString($payItem->tskItem_override_timeMinutes);
    $this->edit_in_timePrep_value    = draff_minutesAsString($payItem->tskItem_event_prepTime);

    // Validate relationships between the time fields (certain combinations are invalid)
    if ( (!empty($payItem->tskItem_job_time_start)) and (!$payItem->tskItem_job_time_end) ) {
        if ( $payItem->tskItem_job_time_start >= $payItem->tskItem_job_time_end ) {
            $this->edit_add_error('@timeEndHour' , 'Time Worked: The end time must be after the start time');
            $timeStatus = 'x';
        }
    }
    if ( $payItem->tskItem_override_timeMethod==0 ) {
        if ( !empty($payItem->tskItem_override_timeMinutes) ) {
            $this->edit_add_error('@timeAdjustMinutes' , 'Time Adjustment:  Can only be used with a time adjustment method');
        }
    }
    else {
        if ( empty($payItem->tskItem_override_timeMinutes) ) {
            $this->edit_add_error('@timeAdjustMinutes' , 'Time Adjustment:  Required when there is a time adjustment method');
        }
        if ( $payItem->tskItem_override_timeMethod==1 ) {
            //???? if  ($timeStatus!='e' ) {
            //????     $this->edit_add_error('@timeAdjustMethod' , 'To override time all the elements of time worked must be "n/a"');
            //???? }
            if ( !empty($payItem->tskItem_job_time_start) ) {
                $this->edit_add_error('@timeStartHour',   'Must be "n/a" when time overide is used' );
                $this->edit_add_error('@timeStartMinute', 'Must be "n/a" when time overide is used' );
            }
            if ( !empty($payItem->tskItem_job_time_end) ) {
                $this->edit_add_error('@timeEndHour',   'Must be "n/a" when time overide is used' );
                $this->edit_add_error('@timeEndMinute', 'Must be "n/a" when time overide is used' );
            }
        }
        else {
            if ( empty($payItem->tskItem_job_time_start) ) {
                $this->edit_add_error('@timeStartHour',   'Required when time adjustment of additional or minus time is used' );
                $this->edit_add_error('@timeStartMinute', 'Required when time adjustment of additional or minus time is used' );
            }
            if ( empty($payItem->tskItem_job_time_end) ) {
                $this->edit_add_error('@timeEndHour',   'Required when time adjustment of additional or minus time is used' );
                $this->edit_add_error('@timeEndMinute', 'Required when time adjustment of additional or minus time is used' );
            }
        }
    }
}

private function edit_convert_toMinutes($string ) {
    $input = trim($string);
    if ( empty($input) ) {
        return 0;
    }
    $items = explode(':',$input);
    $cnt = count($items);
    if ( $cnt == 0 ) {
        return 0;
    }
    else if ( $cnt == 1 ) {
        $minutes = $items[0];
        if ( is_numeric($minutes) ) {
            return $minutes;
        }
        else {
            $this->edit_add_error('@timeAdjustMinutes' , 'Time Adjustment: Must be a number or time (##:##)' );
        }
        return NULL;
    }
    else if ( $cnt !=2 ){
        $this->edit_add_error('@timeAdjustMinutes' , '2Time Adjustment: Must be a number or time (##:##)' );
        return NULL;
    }
    else {
        $hours = $items[0];
        $minutes = $items[1];
        if ( is_numeric($hours)  and is_numeric($minutes) ) {
            if ( $minutes>=60 ) {
                $this->edit_add_error('@timeAdjustMinutes' , 'Time Adjustment: Minutes must be less than 60' );
                return NULL;
            }
            return ($hours * 60) + $minutes;
        }
        else  {
           $this->edit_add_error('@timeAdjustMinutes' , 'Time Adjustment: Hours and Minutes must be numbers (##:##)' );
        }
    }
}

//private function com_validate_date( ) {
//}

private function edit_validate_job($taskItem ) {
    if ( empty($taskItem->tskItem_job_location) ) {
        $this->edit_add_error('@jobLocation' , 'Job Location: Is required');
    }
    if ($taskItem->tskItem_originCode == RC_JOB_ORIGIN_SALARY) {
        return;
    }
    if ( empty($taskItem->tskItem_job_notes) and (empty($taskItem->tskItem_event_eventId)) ) {
        $this->edit_add_error('@jobDesc' , 'Job description: Is required for non-scheduled events');
    }
    $this->edit_validate_jobRate($taskItem);
    if ( $taskItem->tskItem_job_rateCode<=2 ) {
        if ( $taskItem->tskItem_override_rateAmount!==0 ) {
            $this->edit_add_error('@jobRateOverride' , 'Job Rate: Cannot be used for this method'.$taskItem->tskItem_override_rateAmount);
        }
    }
    else  {
   }
}

private function edit_validate_jobRate($item ) {
    $input = trim($item->tskItem_override_rateAmount);
    if ( empty($input) ) {
        $item->tskItem_override_rateAmount = 0;
        return;
    }
    if ( !is_numeric($input) ) {
        $this->edit_add_error('@jobRateOverride' , ' Job Rate must be a number' );
        return;
    }
    if ( $input<=0 ) {
        $this->edit_add_error('@jobRateOverride' , 'Job Rate must be positive');
        return;
    }
    $item->tskItem_override_rateAmount = $input;
}

private function edit_validate_explanation($item ) {
    // $this->com_mustExplain  @overExpain
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
    $appEmitter->zone_start('draff-zone-content-report');
    $outReport_ledgerEdit->ldgEdit_stdReport_output( $appGlobals ,$this->edit_taskGroup, $appEmitter, $form, $this );
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

//=====================================================================

class appData_payHome extends draff_appData {

//---  user information
public $apd_user_proxy;
public $apd_user_isMaster;  // is payroll master

//--- steps (indexes are different depending on usage/user)
public $apd_first_step;
public $apd_ledger_step;
public $apd_edit_step;

function __construct( $appGlobals ) {
    $this->apd_user_proxy  = $appGlobals->gb_user;
    $this->apd_user_isMaster = $this->apd_user_proxy->krnUser_isPayrollManager;
    $this->user_user   = !$this->apd_user_isMaster;
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
$appData = new appData_payHome($appGlobals);

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@         Process Page               @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );
if ( ($appGlobals->gb_proxyIsPayMaster) ) {
    $appData->apd_first_step = 1;
    $appData->apd_ledger_step = 2;
    $appData->apd_edit_step = 3;
    $appChain->chn_form_register($appData->apd_first_step,'appForm_payHome_selectEmployee');
}
else {
    $appData->apd_first_step = 1;
    $appData->apd_ledger_step = 1;
    $appData->apd_edit_step = 2;
}
$appChain->chn_form_register($appData->apd_ledger_step,'appForm_payHome_editLedger');
$appChain->chn_form_register($appData->apd_edit_step,'appForm_payHome_editPayItem');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>