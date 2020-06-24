<?php

// pay-system-emitter.inc.php


//?????? this class is deprecated
// need to use kcmKernel_emitter and move menu, banner info, etc to globals


class kcmPay_emitter extends kcmKernel_emitter {
private $emit_staffIds;

function __construct($appGlobals,$form, $bodyStyle = '') {
    parent::__construct($appGlobals,$form, $bodyStyle);
}

function payEmit_init( $appGlobals, $chain  , $currentKey=NULL) {
    $this->emit_kernelOverride_addCssFiles();
    $this->payEmit_init_menu( $appGlobals, $chain  , $currentKey);
}

function payEmit_output_start( $appGlobals ,$chain,$form, $titleLong, $titleShort) {
    $this->zone_htmlHead($titleShort);
    // if ( $appGlobals->gb_synchronize_message!='') {
    //     print $appGlobals->gb_synchronize_message;
    // }
    $this->zone_body_start($chain, $form);
    $this->krnEmit_banner_output( $appGlobals , $titleLong);
    $this->zone_menu();
    $this->zone_messages($chain, $form);
}

function payEmit_output_end() {
    $this->zone_body_end();
}

function emit_kernelOverride_addCssFiles() {
    $this->addOption_styleFile( 'kcm/kcm-payroll/pay-css.css', 'all','../../');   // this may override some rsm styles such as background color on main panels
}

function payEmit_init_menu( $appGlobals, $chain  , $currentKey=NULL) {

    $period = $appGlobals->gb_period_current;;
    if ( $period == NULL) {
        $this->emit_menu->menu_addLevel_top_start();
        $this->emit_menu->menu_addGroup_start('ls','Lists');
        if ( $appGlobals->gb_proxyIsPayMaster) {
            $this->emit_menu->menu_addItem($chain,'pmu-setPeriod','Set-Up<br>Pay Periods','pay-setup-payPeriods.php',NULL);
        }
        $this->emit_menu->menu_addGroup_end('ls');
        $this->emit_menu->menu_addLevel_top_end();
        if ( $appGlobals->gb_proxyIsPayMaster) {
            $this->emit_menu->menu_markTopLevelItem('pmu-setPeriod');
        }
        return;
    }

    $this->emit_menu->menu_addLevel_top_start();
    $this->emit_menu->menu_addGroup_start('ls','Lists');
    $this->emit_menu->menu_addItem($chain,'pmu-home','Payroll<br>Home','pay-home.php',NULL);
 //   $this->emit_menu->menu_addItem('exit','Exit<br>(to Gateway)','gateway-home.php');
     if ( $appGlobals->gb_proxyIsPayMaster) {
        $this->emit_menu->menu_addItem($chain,'pmu-rptLedger','Earning Details<br>Report','pay-report-ledger.php',NULL);
        $this->emit_menu->menu_addItem($chain,'pmu-rptCheck','Gross Pay<br>Report','pay-report-checkRegister.php',NULL);
        $this->emit_menu->menu_addItem($chain,'pmu-setStaff',  'Employee<br>Setup','pay-setup-employee.php',NULL);
        $this->emit_menu->menu_addItem($chain,'pmu-setPeriod','Pay Period<br>Setup','pay-setup-payPeriods.php',NULL);
        $this->emit_menu->menu_addItem($chain,'pmu-setProxy','Set<br>Proxy','pay-setup-proxy.php?disable=yes',NULL);
        $this->emit_menu->menu_markTopLevelItem('pmu-setProxy');
    }
    else {
          $this->emit_menu->menu_addItem($chain,'pmu-rptLedger','View Previous<br>Pay Periods','pay-report-ledger.php',NULL);
        $this->emit_menu->menu_addItem($chain,'pmu-rptLedger','View Previous<br>Pay Periods','pay-report-ledger.php',NULL);
        if ( ($appGlobals->gb_loginIsPayMaster) and ( ! $appGlobals->gb_proxyIsPayMaster) ) {
            $this->emit_menu->menu_addItem($chain,'pmu-setProxy','Disable<br>Proxy','pay-setup-proxy.php?disable=yes',NULL);
        }
  }
    $this->emit_menu->menu_addGroup_end('ls');
    $this->emit_menu->menu_addLevel_top_end();

    $this->emit_menu->menu_addLevel_toggled_start();
    $this->emit_menu->menu_markTopLevelItem('pmu-home');
    $this->emit_menu->menu_markTopLevelItem('exit');
    $this->emit_menu->menu_markTopLevelItem('pmu-setStaff');
    $this->emit_menu->menu_markTopLevelItem('pmu-setPeriod');
    $this->emit_menu->menu_markTopLevelItem('pmu-rptLedger');
    $this->emit_menu->menu_markTopLevelItem('pmu-rptCheck');
    $this->emit_menu->menu_markTopLevelItem('pmu-setProxy');
    $this->emit_menu->menu_addLevel_toggled_end();
    if ( $currentKey!==NULL) {
        $this->emit_menu->menu_markCurrentItem($currentKey);
    }
}

} // end class

?>