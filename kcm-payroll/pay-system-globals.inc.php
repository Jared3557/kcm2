<?php

// pay-system-globals.inc.php

//const RC_PAYPERIOD_EARLIEST_DATE = '2019-04-06';  // all job records before this date are part of scheduling but not part of payroll
//const RC_PAYPERIOD_EARLIEST_DATE = '2019-01-12';  // all job records before this date are part of scheduling but not part of payroll
//const RC_JOB_MAX_ADVANCE_DAYS = '366';   // all dates, including scheduled events, must be within (today plus this increment) or less

// Pay Period Codes
//const RC_PAYPERIOD_NORMAL  = 1;
//const RC_PAYPERIOD_SPECIAL = 2;

// Pay Period Open Codes
const PAY_PERIODOPEN_STAFF   = 1;
const PAY_PERIODOPEN_PM      = 2;
const PAY_PERIODOPEN_ALL     = 3;
const PAY_PERIODOPEN_SPECIAL = 99;

//// Attendance Codes
//const RC_JOB_ATTEND_NA          = 0;
//const RC_JOB_ATTEND_PRESENT     = 1;
//const RC_JOB_ATTEND_PIF         = 2;   // present - Pay in full per event hours for current role
//const RC_JOB_ATTEND_SICK        = 16;
//const RC_JOB_ATTEND_VACATION    = 17;
//const RC_JOB_ATTEND_ABSENT_UNEXCUSED      = 18;  // abscent - not sick or vacation - Must be the lowest of the unpaid codes
//??????? need two types of absent

//// Task Origin Codes
//const RC_JOB_ORIGIN_SCHEDULE    = 10;  // schedule item - no overrides (except maybe hadEquipment and hadBadge as these don't effect pay calculations)
//const RC_JOB_ORIGIN_SM          = 20;  // schedule item overridden by site manager
//const RC_JOB_ORIGIN_STAFF       = 30;  // staff new record or overrides
//const RC_JOB_ORIGIN_TRAVEL      = 40;  // travel record  (one per transaction - no correction records, used only by payroll master)
//const RC_JOB_ORIGIN_SALARY      = 50;  // salary record  (one per transaction - no correction records, used only by payroll master)
//const RC_JOB_ORIGIN_PM          = 60;  // payroll manager overrides

// time override codes
const PAY_TIMEOVERRIDE_FALSE   = 0;
const PAY_TIMEOVERRIDE_TRUE    = 1;

//// Schedule Status Codes
//const RC_JOB_SCHEDSTATUS_UNPUBLISHED  = 0;   // unpublished indicates part of scheduling, but not payroll - (hidden to payroll)
//const RC_JOB_SCHEDSTATUS_PUBLISHED    = 1;   // will be handled by payroll, and possibly scheduling
//const RC_JOB_SCHEDSTATUS_NOT_EVENT    = 2;   // not part of scheduling, only payroll

// Pay Status Codes
const PAY_PAYSTATUS_UNAPPROVED    = 0;   // will be handled by payroll, and possibly scheduling
const PAY_PAYSTATUS_APPROVED      = 5;    // only published transactions can be approved
const PAY_PAYSTATUS_PREPAYROLL    = 18;   // transaction from before start of payroll (no pay amount, etc)

// Pay rate codes
const PAY_RATEMETHOD_ADMIN           = 1;
const PAY_RATEMETHOD_FIELD           = 2;
const PAY_RATEMETHOD_SALARY          = 3;
const PAY_RATEMETHOD_OVERRIDE_RATE   = 21;
const PAY_RATEMETHOD_OVERRIDE_AMOUNT = 22;

const PAY_REFRESH_SAVE_ALWAYS     = 1;
const PAY_REFRESH_SAVE_CHANGED    = 2;
const PAY_REFRESH_SAVE_NEVER      = 3;

// The EVTADJUST constants are the minutes to convert event time to individual's scheduled time (SM=site manager, CO = other coaches)
CONST EVTADJUST_CLASS_BEFORE_SM   = 25; // minutes to be paid for before class start for site managers
CONST EVTADJUST_CLASS_BEFORE_CO   = 20; // minutes to be paid for before class start for others
CONST EVTADJUST_CLASS_AFTER_SM    = 25; // minutes to be paid for before class start for site managers
CONST EVTADJUST_CLASS_AFTER_CO    = 15; // minutes to be paid for before class start for others
CONST EVTADJUST_CLASS_1PER_SMPREP = 30; // minutes to be paid to SM for one period classes
CONST EVTADJUST_CLASS_2PER_SMPREP = 40; // minutes to be paid to SM for two period classes
CONST EVTADJUST_CLASS_1PERLENGTH  = 80; // if timespan is over this, will be considered a two period class
CONST EVTADJUST_TOURN_BEFORE_SM   = 25; // minutes to be paid for before class start for site managers
CONST EVTADJUST_TOURN_BEFORE_CO   = 20; // minutes to be paid for before class start for others
CONST EVTADJUST_TOURN_AFTER_SM    = 15; // minutes to be paid for before class start for site managers
CONST EVTADJUST_TOURN_AFTER_CO    = 15; // minutes to be paid for before class start for others
CONST EVTADJUST_CAMP_BEFORE_SM    = 30; // minutes to be paid for before class start for site managers
CONST EVTADJUST_CAMP_BEFORE_CO    = 30; // minutes to be paid for before class start for others
CONST EVTADJUST_CAMP_AFTER_SM     = 15; // minutes to be paid for before class start for site managers
CONST EVTADJUST_CAMP_AFTER_CO     = 15; // minutes to be paid for before class start for others
CONST EVTADJUST_OTHER_BEFORE_SM   = 15; // minutes to be paid for before class start for site managers
CONST EVTADJUST_OTHER_BEFORE_CO   = 15; // minutes to be paid for before class start for others
CONST EVTADJUST_OTHER_AFTER_SM    = 15; // minutes to be paid for before class start for site managers
CONST EVTADJUST_OTHER_AFTER_CO    = 15; // minutes to be paid for before class start for others
const EVTADJUST_TRAVEL_MAXTIME    = 60;   // maximum travel minutes allowed
const EVTADJUST_TRAVEL_MAXGAP     = 120;  // if over this many minutes then no travel pay

const PAY_DESC_PM = 'Payroll Manager';  // desc of payroll manager

class kcmPay_globals extends kcmKernel_globals {
    public $gb_period_current  = NULL;
    public $gb_period_override = NULL;  // for reports, etc where can see previous periods, otherwise same as current period
    public $gb_period_current_type = 0;
    public $gb_period_staffId = 0;
    public $gb_period_payMasterId = 0;
    public $gb_employeeArray = array();
    public $gb_proxyIsPayMaster = FALSE;  // proxy state
    public $gb_loginIsPayMaster = FALSE;  // user state
    public $gb_synchronize_message = '';  // for debugging
    public $gb_staffFactors = array();  // for encrypting
    
function __construct() {
    parent::__construct('KCM Payroll','../kcm-kernel/images/banner-icon-kcm.gif','kcmPay_emitter') ;
    $this->gb_user   = new pay_security_user($this, NULL);
    $this->gb_owner  = new pay_security_user($this, $this->gb_user);
    //$this->gb_isLoggedIn = ($this->gb_user->krnUser_loginId != NULL);
   // $this->gb_rgb_addSecurity(new pay_security_engine($pChain, $this->gb_db, $this->gb_sql));
    $this->gb_proxyIsPayMaster = $this->gb_user->krnUser_isPayrollManager;
    $this->gb_loginIsPayMaster = $this->gb_user->krnUser_isPayrollManager;
    $this->gb_period_current = payData_factory::payFactory_get_payPeriod_open($this);  // creates open period if job:payperiod table is empty
    $this->gb_period_override = $this->gb_period_current;
    $this-> gb_synchronize();
    //set_error_handler(array($this,'gb_errorTrap'), E_ALL);
    $this->gbKrn_add_cssFile( 'kcm/kcm-payroll/pay-css.css', 'all','../../');   // this may override some rsm styles such as background color on main panels
    $this->gb_banner_image_system = 'kcm_banner_payroll.gif';
}

function gb_synchronize($employeeId=NULL) {
    if ( class_exists ('payData_synchronizer') ) {
        $syncEngine = new payData_synchronizer($this);
        $syncEngine->sync_synchronize($this, $this->gb_period_current->prd_dateStart,$this->gb_period_current->prd_dateEnd, $employeeId);
    //    $syncEngine->sync_synchronize($this, `2014-01-01`,'2020-06-01');
    }
}

function gb_load_global_employees($employeeId=NULL) {
    if ( count($this->gb_employeeArray) >= 30 or ( ($employeeId!==NULL) and isset($this->gb_employeeArray[$employeeId] ) )  ){
        return;  // already loaded
    }
    $this->gb_employeeArray = payData_employee_batch::epyBat_get_array($this,$employeeId);
 }

function pay_getUserInfo() {
    // This is the real person
    $this->pay_loginId  = $_SESSION['Admin']['LoginId'];
    $this->pay_loginName = $_SESSION['Admin']['LoginName'];
    $this->proxyUser = $this->pay_loginId;
    $this->proxyName = $this->pay_loginName;
    $this->pay_staffId = $_SESSION['Admin']['LoginName'];
    $proxyUser = $chain->chn_data_joint_get('#proxy_user',NULL);
    if ( !empty($proxyUser)) {
        $this->pay_staffId = $proxyUser;
        if ( is_numeric($proxyUser)) {
            $query = "SELECT `sLI:LoginName`,`sLI:LoginId`,`sLI:@StaffId`,`sLI:@RoleId` FROM `st:loginidentity` WHERE `sLI:LoginId` = '{$proxyUser}'";
        }
        else {
            $query = "SELECT `sLI:LoginName`,`sLI:LoginId`,`sLI:@StaffId`,`sLI:@RoleId` FROM `st:loginidentity` WHERE `sLI:LoginName` LIKE '{$proxyUser}%'";
        }
        $db = rc_getGlobalDatabaseObject();
        // should only allow if system admin
        $result = $this->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
        if ( $result->num_rows == 0) {
            print 'Invalid user name in globals';
            //$this->gb_sql->sql_errorTerminate('Invalid User ID');
            return;
        }
        if ( $result->num_rows > 1) {
            $punc = ',';
            $s = 'Invalid user name - several choices ';
            while ($row=$result->fetch_array()) {
                $s .= $punc . $row['sLI:LoginName'];
                $punc = ' ';
            }
            echo 'Invalid user name ' . $s;
            return;
        }
        $row=$result->fetch_array();
        $this->pay_proxy_StaffId = $row['sLI:@StaffId'];
        $this->krnUser_loginId = $row['sLI:LoginId'];
        $this->pay_proxyStaffName = $row['sLI:LoginName'];
        //$appGlobals->gb_form->chain->chn_url_registerArgument(URL_ALL,'user',$this->wwLoginName);
        //$this->sec_user_setSecurityLevels($this->krnUser_loginId, FALSE);   // not original loginId so cannot be SysAdmin
    }
}

function gb_ribbonMenu_Initialize($chain, $emitter) {  //  $appGlobals , $currentKey=NULL) {
    $menu = $emitter->emit_menu;

    $period = $this->gb_period_current;
    if ( $period == NULL) {
        $menu->drMenu_addLevel_top_start();
        $menu->drMenu_addGroup_start('ls','Lists');
        if ( $this->gb_proxyIsPayMaster) {
            $menu->drMenu_addItem($chain,'pmu-setPeriod','Set-Up<br>Pay Periods','pay-setup-payPeriods.php');
        }
        $menu->drMenu_addGroup_end('ls');
        $menu->drMenu_addLevel_top_end();
        if ( $this->gb_proxyIsPayMaster) {
            $menu->drMenu_markTopLevelItem('pmu-setPeriod');
        }
        return;
    }

    $menu->drMenu_addLevel_top_start();
    $menu->drMenu_addGroup_start('ls','Lists');
    $menu->drMenu_addItem($chain, 'pmu-home','Payroll<br>Home','pay-home.php');
 //   $this->emit_menu->drMenu_addItem($chain,'exit','Exit<br>(to Gateway)','gateway-home.php',NULL);
     if ( $this->gb_proxyIsPayMaster) {
        $menu->drMenu_addItem($chain,'pmu-rptLedger','Earning Details<br>Report','pay-report-ledger.php');
        $menu->drMenu_addItem($chain,'pmu-rptCheck','Gross Pay<br>Report','pay-report-checkRegister.php');
        $menu->drMenu_addItem($chain,'pmu-setStaff',  'Employee<br>Setup','pay-setup-employee.php');
        $menu->drMenu_addItem($chain,'pmu-setPeriod','Pay Period<br>Setup','pay-setup-payPeriods.php');
        $menu->drMenu_addItem($chain,'pmu-setProxy','Set<br>Proxy','pay-setup-proxy.php', array('disable'=>'yes') );
        $menu->drMenu_markTopLevelItem('pmu-setProxy');
    }
    else {
        $menu->drMenu_addItem($chain,'pmu-rptLedger','View Previous<br>Pay Periods','pay-report-ledger.php');
        $menu->drMenu_addItem($chain,'pmu-rptLedger','View Previous<br>Pay Periods','pay-report-ledger.php');
        if ( ($this->gb_loginIsPayMaster) and ( ! $this->gb_proxyIsPayMaster) ) {
            $menu->drMenu_addItem($chain,'pmu-setProxy','Disable<br>Proxy','pay-setup-proxy.php?disable=yes');
        }
  }
    $menu->drMenu_addGroup_end('ls');
    $menu->drMenu_addLevel_top_end();

    $menu->drMenu_addLevel_toggled_start();
    $menu->drMenu_markTopLevelItem('pmu-home');
    $menu->drMenu_markTopLevelItem('exit');
    $menu->drMenu_markTopLevelItem('pmu-setStaff');
    $menu->drMenu_markTopLevelItem('pmu-setPeriod');
    $menu->drMenu_markTopLevelItem('pmu-rptLedger');
    $menu->drMenu_markTopLevelItem('pmu-rptCheck');
    $menu->drMenu_markTopLevelItem('pmu-setProxy');
    $menu->drMenu_addLevel_toggled_end();
    //if ( $currentKey!==NULL) {
    //  $menu->drMenu_markCurrentItem($currentKey);
    //}
}

//function gb_errorTrap() {
//    $this->gb_errorCount++;
//    return FALSE;
//}

} // end class

class pay_security_user  extends kcmKernel_security_user {

public $krnUser_isPayrollManager = FALSE; // used to allow certain features
//public $sec_isSystemMaster = FALSE; // used to allow certain features
//public $sec_viewerOffice = FALSE; // used to allow certain features
//public $sec_viewerMaster = FALSE; // used to allow certain features
//public $sec_isLive = FALSE; // is live system - maybe should be in parent object

function __construct($appGlobals, $actualUser=NULL) {
    parent::__construct($appGlobals, $actualUser);
 //????@@@/// Probably needed for proxy   if ( $actualUser!=NULL) {
 //????@@@/// Probably needed for proxy       $this->krnUser_setProxyUser($chain, $db, $sql, $actualUser); // only done if applicable
 //????@@@/// Probably needed for proxy   }
    $row = $this->krnUser_getRights($appGlobals);
    $payAccess = $row['sRD:fiPayrollAccess'];
    $this->krnUser_isPayrollManager = ($payAccess == 3);
    
}

} // end class

?>