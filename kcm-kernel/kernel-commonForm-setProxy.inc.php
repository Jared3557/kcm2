<?php

// kernel-commonForm-setProxy.inc.php

// Not really includable between gateway and payroll as security classes are different
// So use this only from Payroll
// Do not use from gateway (unless they both use same security classes)


class appForm_shared_setProxy_edit extends kcmKernel_Draff_Form {

function __construct($returnUrl) {
 //   $this->sp_common = new common_shared_setProxyEdit;
    $this->sp_returnUrl = $returnUrl;
}

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    $appData->apd_edit_loadData( $appGlobals, $appChain );
    if ( $appChain->chn_submit[0] == 'cancel') {
        $appChain->chn_message_set('Proxy Information Changes Cancelled');
        //$appChain->chn_curStream_Clear();
        $appChain->chn_launch_cancelChain(1,$message);
        //rc_redirectToURL($this->sp_returnUrl);
    }
    if ( $appChain->chn_submit[0] == 'disable') {
        $appData->apd_edit_submit_proxyDisable($appGlobals, $appChain);
        $appChain->chn_curStream_Clear();
        $appChain->chn_url_redirect();
        //rc_redirectToURL($this->sp_returnUrl);
    }
    //if ( $appChain->chn_submit[0] == 'reset') {
    //    $appChain->chn_curStream_Clear();
    //    $appChain->chn_message_set('Reset');
    //    $appChain->chn_launch_continueChain(1);
    //}
    //$appChain->chn_form_savePostedData();
    if ( $appChain->chn_submit[0]=='submit') {
        $appData->apd_edit_submit_proxySave($appGlobals, $appChain);
        $appChain->chn_form_launch(1);
    }
    //$appChain->chn_launch_newChain();
    //$appChain->chn_launch_continueChain();
    $appChain->chn_launch_newChain(1);
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_edit_loadData( $appGlobals, $appChain );
    $appData->apd_proxyReport = new stdReport_proxyEdit;
    $appData->apd_proxyReport->stdRpt_initControls( $appData, $appGlobals, $appChain, $this);
}

function drForm_initHtml($appData, $appGlobals, $appChain, $appEmitter) {
    $appEmitter->emit_options->set_theme( 'theme-panel' );
    $appEmitter->emit_options->set_title('Setup Proxy');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appGlobals);
    $appGlobals->gb_menu->drMenu_customize( );
    $appEmitter->emit_options->addOption_styleTag('span.loc-small', 'display:inline; font-size:12pt; font-weight:normal; padding: 0pt 12pt 4pt 12pt');
    $appData->apd_proxyReport->stdRpt_initOutput( $appData, $appGlobals, $appChain, $appEmitter, $this );
}

function drForm_initFields($appData, $appGlobals, $appChain) {
    $appData->apd_edit_initializeCombos($appData, $appGlobals);
    $this->drForm_addField( new Draff_Combo( 'proxyLoginId', $appData->apd_proxy_loginId  , $appData->apd_edit_combo_users) );
    $this->drForm_addField( new Draff_Combo( 'proxyYear',    $appData->apd_proxy_year     , $appData->apd_edit_combo_years,array('#label'=>'Year')) );
    $this->drForm_addField( new Draff_Combo( 'proxyMonth',   $appData->apd_proxy_month    , $appData->apd_edit_combo_months,array('#label'=>'Month')) );
    $this->drForm_addField( new Draff_Combo( 'proxyDay',     $appData->apd_proxy_day      , $appData->apd_edit_combo_days,array('#label'=>'Day')) );
    $this->drForm_addField( new Draff_Combo( 'proxyHour',    $appData->apd_proxy_hour     , $appData->apd_edit_combo_hours,array('#label'=>'Hour')) );
    $this->drForm_addField( new Draff_Combo( 'proxyMinute',  $appData->apd_proxy_minute   , $appData->apd_edit_combo_minutes,array('#label'=>'Minute')) );
    $this->drForm_addField( new Draff_Combo( 'proxySecond',  $appData->apd_proxy_second   , $appData->apd_edit_combo_seconds,array('#label'=>'Second')) );
    $this->drForm_addField( new Draff_Button( 'submit','Submit') );
    $this->drForm_addField( new Draff_Button( 'cancel','Cancel') );
    $this->drForm_addField( new Draff_Button( 'disable','Disable') );
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

    function drForm_outputHeader($appData, $appGlobals, $appChain, $appEmitter) {
}

function drForm_outputContent($appData, $appGlobals, $appChain, $appEmitter) {
    $appEmitter->zone_start('zone-content-scrollable theme-panel');
    $appData->apd_proxyReport->stdRpt_emit( $appData, $appGlobals, $appChain, $appEmitter, $this );


    $appEmitter->zone_end();
}

function drForm_outputFooter($appData, $appGlobals, $appChain, $appEmitter) {
}

}

class stdReport_proxyEdit {

function stdRpt_initControls( $appData, $appGlobals, $appChain, $form ) {
}

function stdRpt_initOutput( $appData, $appGlobals, $appChain, $appEmitter, $form ) {
}

function stdRpt_emit( $appData, $appGlobals, $appChain, $appEmitter, $form ) {
    $title = 'Set a proxy'
           . '<br>(1) Act as another user'
           . "<br>(2) Act as if it's a different date"
           . "<br>(3) Act as if it's a different time"
           . '<span class="loc-small">'
           . "<br>This is useful to see what another user sees, for testing, etc"
           . '<br>Note: All changes will be saved with non-proxy information'
           . '</span>';

    $appEmitter->table_start('draff-edit',2);
    $appEmitter->table_head_start();
    $appEmitter->row_oneCell( $title);  // ,'sy-dataPanel-header-left'
    $appEmitter->table_body_start();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('User','draff-edit-fieldDesc');
    $appEmitter->cell_block('@proxyLoginId','draff-edit-fieldData');
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Date','draff-edit-fieldDesc');
    $appEmitter->cell_block(array('@proxyMonth','#sep','@proxyDay','#sep','@proxyYear'),'draff-edit-fieldData');
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Time','draff-edit-fieldDesc');
    $appEmitter->cell_block(array('@proxyHour','#sep','@proxyMinute','#sep','@proxySecond'),'draff-edit-fieldData');
    $appEmitter->row_end();

    $appEmitter->row_oneCell(array('@submit','@cancel','@disable'));
    $appEmitter->table_end();
}

} // end class

class appData_commonSetProxy extends draff_appData {
//public $sp_common;

    public $apd_proxyReport;

    public $apd_proxy_loginId = '';
    public $apd_proxy_date    = '';
    public $apd_proxy_time    = '';
    public $apd_proxy_day     = '@none';
    public $apd_proxy_month   = '@none';
    public $apd_proxy_year    = '@none';
    public $apd_proxy_hour    = '@none';
    public $apd_proxy_minute  = '@none';
    public $apd_proxy_second  = '@none';

    public $apd_edit_combo_years;
    public $apd_edit_combo_months;
    public $apd_edit_combo_days;
    public $apd_edit_combo_hours;
    public $apd_edit_combo_minutes;
    public $apd_edit_combo_seconds;
    public $apd_edit_combo_users;

public $sp_returnUrl;  //??????

function __construct() {
}

function apd_edit_loadData($appGlobals, $appChain) {

    $this->apd_proxy_loginId = $appGlobals->gb_session_proxy->ses_get( '#proxyLoginId'  ,$this->apd_proxy_loginId );
    $this->apd_proxy_Date    = $appGlobals->gb_session_proxy->ses_get( '#proxyDate'     ,$this->apd_proxy_date    );
    $this->apd_proxy_Time    = $appGlobals->gb_session_proxy->ses_get( '#proxyTime'     ,$this->apd_proxy_time    );
    $this->apd_proxy_year    = $appGlobals->gb_session_proxy->ses_get( '#proxyYear'     ,$this->apd_proxy_year   );
    $this->apd_proxy_month   = $appGlobals->gb_session_proxy->ses_get( '#proxyMonth'    ,$this->apd_proxy_month  );
    $this->apd_proxy_day     = $appGlobals->gb_session_proxy->ses_get( '#proxyDay'      ,$this->apd_proxy_day    );
    $this->apd_proxy_hour    = $appGlobals->gb_session_proxy->ses_get( '#proxyHour'     ,$this->apd_proxy_hour   );
    $this->apd_proxy_minute  = $appGlobals->gb_session_proxy->ses_get( '#proxyMinute'   ,$this->apd_proxy_minute );
    $this->apd_proxy_second  = $appGlobals->gb_session_proxy->ses_get( '#proxySecond'   ,$this->apd_proxy_second );

    $appChain->chn_readPostedField( $this->apd_proxy_loginId , 'proxyLoginId'    );
    $appChain->chn_readPostedField( $this->apd_proxy_year    , 'proxyYear'      );
    $appChain->chn_readPostedField( $this->apd_proxy_month   , 'proxyMonth'     );
    $appChain->chn_readPostedField( $this->apd_proxy_day     , 'proxyDay'       );
    $appChain->chn_readPostedField( $this->apd_proxy_hour    , 'proxyHour'      );
    $appChain->chn_readPostedField( $this->apd_proxy_minute  , 'proxyMinute'    );
    $appChain->chn_readPostedField( $this->apd_proxy_second  , 'proxySecond'    );

//    $appGlobals->gb_session_proxy->ses_set('#proxyLoginId'  ,$this->apd_proxy_loginId   );
//    $appGlobals->gb_session_proxy->ses_set('#proxyYear'     ,$this->apd_proxy_year   );
//    $appGlobals->gb_session_proxy->ses_set('#proxyMonth'    ,$this->apd_proxy_month  );
//    $appGlobals->gb_session_proxy->ses_set('#proxyDay'      ,$this->apd_proxy_day    );
//    $appGlobals->gb_session_proxy->ses_set('#proxyHour'     ,$this->apd_proxy_hour   );
//    $appGlobals->gb_session_proxy->ses_set('#proxyMinute'   ,$this->apd_proxy_minute );
//    $appGlobals->gb_session_proxy->ses_set('#proxySecond'   ,$this->apd_proxy_second );
}

function apd_edit_submit_proxySave($appGlobals, $appChain) {
    $now = rc_getNow();
    $year   = substr($now, 0,4);
    $month  = substr($now, 5,2);
    $day    = substr($now, 8,2);
    $hour   = substr($now,11,2);
    $minute = substr($now,14,2);
    $second = substr($now,17,2);
    $new_year   = $this->apd_proxy_year   == '@none' ? $year   : $this->apd_proxy_year;
    $new_month  = $this->apd_proxy_month  == '@none' ? $month  : $this->apd_proxy_month ;
    $new_day    = $this->apd_proxy_day    == '@none' ? $day    : $this->apd_proxy_day   ;
    $new_hour   = $this->apd_proxy_hour   == '@none' ? $hour   : $this->apd_proxy_hour  ;
    $new_minute = $this->apd_proxy_minute == '@none' ? $minute : $this->apd_proxy_minute;
    $new_second = $this->apd_proxy_second == '@none' ? $second : $this->apd_proxy_second;
    if ( !checkdate ( $new_month , $new_day , $new_year ) ) {
        $appChain->chn_message_set('@proxyDay','Invalid day');
        //???????????????? $appChain->chn_step_executeNext(1);
        return;
    }
   if ( ($this->apd_proxy_day === '@none') and ($this->apd_proxy_month === '@none') and ($this->apd_proxy_year === '@none') ) {
       $proxyDate = '@none';
    }
    else {
        $proxyDate =$new_year . '-' . $new_month . '-' . $new_day;
    }
    if ( ($this->apd_proxy_hour === '@none') and ($this->apd_proxy_minute === '@none') and ($this->apd_proxy_second === '@none') ) {
        $proxyTime = '@none';
    }
    else {
        $proxyTime = $new_hour . ':' . $new_minute . ':' . $new_second;
    }
    $this->apd_edit_saveElement( $appGlobals, '#proxyLoginId',$this->apd_proxy_loginId);
    $this->apd_edit_saveElement( $appGlobals, '#proxyYear',$this->apd_proxy_year);
    $this->apd_edit_saveElement( $appGlobals, '#proxyMonth',$this->apd_proxy_month);
    $this->apd_edit_saveElement( $appGlobals, '#proxyDay',$this->apd_proxy_day);
    $this->apd_edit_saveElement( $appGlobals, '#proxyHour',$this->apd_proxy_hour);
    $this->apd_edit_saveElement( $appGlobals, '#proxyMinute',$this->apd_proxy_minute);
    $this->apd_edit_saveElement( $appGlobals, '#proxySecond',$this->apd_proxy_second);
    $this->apd_edit_saveElement( $appGlobals, '#proxyDate',$proxyDate);
    $this->apd_edit_saveElement( $appGlobals, '#proxyTime',$proxyTime);
    $appChain->chn_message_set('Proxy Information Saved');
   // $appChain->chn_curStream_Clear();
    //$appChain->chn_launch_newChain(1);
   // $appChain->chn_url_redirect();
        //rc_redirectToURL($this->sp_returnUrl);
}

function apd_edit_submit_proxyDisable($appGlobals, $appChain) {
    $appGlobals->gb_session_proxy->ses_set('#proxyLoginId',NULL);
    $appGlobals->gb_session_proxy->ses_set('#proxyYear',NULL);
    $appGlobals->gb_session_proxy->ses_set('#proxyMonth',NULL);
    $appGlobals->gb_session_proxy->ses_set('#proxyDay',NULL);
    $appGlobals->gb_session_proxy->ses_set('#proxyHour',NULL);
    $appGlobals->gb_session_proxy->ses_set('#proxyMinute',NULL);
    $appGlobals->gb_session_proxy->ses_set('#proxySecond',NULL);
    $appGlobals->gb_session_proxy->ses_set('#proxyDate',NULL);
    $appGlobals->gb_session_proxy->ses_set('#proxyTime',NULL);
    $appChain->chn_curStream_Clear();
    $appChain->chn_message_set('Proxy Disabled');
}

function apd_edit_initializeCombos($appData, $appGlobals) {  // or appGlobals, gateGlobals, etc.
    //------------------------------
    //--- Initialize Static Lists
    //------------------------------
    $this->apd_edit_combo_years = array();
    $now = rc_getNowDate();  // actual date - not from proxy
    $nowYear  = substr($now,0,4);
    $this->apd_edit_combo_years['@none']='(do not override)';
    $month = substr($now,5,2);
   // if ( $month<=1) {
       $this->apd_edit_combo_years[$nowYear-1] = ($nowYear-1);
  //  }
    $this->apd_edit_combo_years[$nowYear] = ($nowYear);
    if ( $month>=12) {
       $this->apd_edit_combo_years[$nowYear+1] = ($nowYear+1);
    }
    $this->apd_edit_combo_months = array('@none'=>'(do not override)','01'=>'January','02'=>'February','03'=>'March','04'=>'April','05'=>'May','06'=>'June','07'=>'July','08'=>'August','09'=>'September','10'=>'October','11'=>'November','12'=>'December');
    $this->apd_edit_combo_days = array('@none'=>'(do not override)'
                 ,'01'=>'01','02'=>'02','03'=>'03','04'=>'04','05'=>'05','06'=>'06','07'=>'07','08'=>'08','09'=>'09','10'=>'10'
                 ,'11'=>'11','12'=>'12','13'=>'13','14'=>'14','15'=>'15','16'=>'16','17'=>'17','18'=>'18','19'=>'19','20'=>'20'
                 ,'21'=>'21','22'=>'22','23'=>'23','24'=>'24','25'=>'25','26'=>'26','27'=>'27','28'=>'28','29'=>'29','30'=>'30'
                 ,'31'=>'31');
    $this->apd_edit_combo_hours = array('@none'=>'(do not override)'
                ,'12'=>'12pm','13'=>'1pm','14'=>'2pm','15'=>'3pm','16'=>'4pm','17'=>'5pm','18'=>'6pm','19'=>'7pm','20'=>'8pm','21'=>'9pm','22'=>'10pm','23'=>'11pm'
                ,'00'=>'12am','01'=>'1am','02'=>'2m','03'=>'3am','04'=>'4am','05'=>'5am','06'=>'6am','07'=>'7am','08'=>'8am','09'=>'9am','10'=>'10am','11'=>'11am');
    $this->apd_edit_combo_minutes = array('@none'=>'(do not override)', '00'=>'00'
                 ,'01'=>'01','02'=>'02','03'=>'03','04'=>'04','05'=>'05','06'=>'06','07'=>'07','08'=>'08','09'=>'09','10'=>'10'
                 ,'11'=>'11','12'=>'12','13'=>'13','14'=>'14','15'=>'15','16'=>'16','17'=>'17','18'=>'18','19'=>'19','20'=>'20'
                 ,'21'=>'21','22'=>'22','23'=>'23','24'=>'24','25'=>'25','26'=>'26','27'=>'27','28'=>'28','29'=>'29','30'=>'30'
                 ,'31'=>'21','32'=>'22','33'=>'23','34'=>'24','35'=>'25','36'=>'26','37'=>'27','38'=>'28','39'=>'29','40'=>'30'
                 ,'41'=>'41','42'=>'42','43'=>'23','44'=>'24','45'=>'25','46'=>'26','47'=>'47','28'=>'48','29'=>'49','30'=>'50'
                 ,'51'=>'21','52'=>'22','53'=>'23','54'=>'24','55'=>'25','56'=>'26','57'=>'27','58'=>'28','59'=>'29');
    $this->apd_edit_combo_seconds = array('@none'=>'(do not override)','00'=>'00'
                 ,'01'=>'01','02'=>'02','03'=>'03','04'=>'04','05'=>'05','06'=>'06','07'=>'07','08'=>'08','09'=>'09','10'=>'10'
                 ,'11'=>'11','12'=>'12','13'=>'13','14'=>'14','15'=>'15','16'=>'16','17'=>'17','18'=>'18','19'=>'19','20'=>'20'
                 ,'21'=>'21','22'=>'22','23'=>'23','24'=>'24','25'=>'25','26'=>'26','27'=>'27','28'=>'28','29'=>'29','30'=>'30'
                 ,'31'=>'21','32'=>'22','33'=>'23','34'=>'24','35'=>'25','36'=>'26','37'=>'27','38'=>'28','39'=>'29','40'=>'30'
                 ,'41'=>'41','42'=>'42','43'=>'23','44'=>'24','45'=>'25','46'=>'26','47'=>'47','28'=>'48','29'=>'49','30'=>'50'
                 ,'51'=>'21','52'=>'22','53'=>'23','54'=>'24','55'=>'25','56'=>'26','57'=>'27','58'=>'28','59'=>'29');
    //------------------------------
    //--- Initialize User List
    //------------------------------
    $this->apd_edit_combo_users = array();
    $fldList = array();
    $fldList[] = 'sLI:LoginId';
    $fldList[] = 'sLI:LoginName';
    $fldList[] = 'sLI:@StaffId';
    $fldList[] = 'sLI:@RoleId';
    $fldList[] = 'sLI:HiddenStatus';
    $fldList[] = 'sRD:RoleId';
    $fldList[] = 'sRD:RoleName';
    $fldList[] = 'sSt:ShortName';
    $fldList[] = 'sSt:HiddenStatus';
    $fields = "`" . implode($fldList,"`, `") . "`";
    $sql[] = "Select $fields";
    $sql[] = "FROM `st:loginidentity`";
    $sql[] = "JOIN `st:roledefinition` ON `sRD:RoleId` = `sLI:@RoleId`";
    $sql[] = "JOIN `st:staff` ON `sSt:StaffId` = `sLI:@StaffId`";
    $sql[] = "WHERE (`sLI:HiddenStatus` = '0') AND (`sSt:HiddenStatus` = '0')";
    $sql[] = "ORDER BY `sSt:ShortName`,'sLI:LoginName'";
    $query = implode( $sql, ' ');
    $this->apd_edit_combo_users['@none'] = '(do not override)';
    $result = $appGlobals->gb_pdo->rsmDbe_execute( $query );
    foreach($result as $row) {
        $logId = $row['sLI:LoginId'];
        $name = $row['sSt:ShortName'];
        $logName = $row['sLI:LoginName'];
        $roleName = $row['sRD:RoleName'];
        $this->apd_edit_combo_users[$logId] = $name . ' as ' . $logName . ' (' . $roleName . ')';
    }
}

function apd_edit_saveElement($appGlobals, $key, $value) {
    if ( (empty($value) ) or ( $value == '@none')  ) {
        $appGlobals->gb_session_proxy->ses_unset($key);
    }
    else {
        $appGlobals->gb_session_proxy->ses_set($key,$value);
    }
}

} // end class

?>