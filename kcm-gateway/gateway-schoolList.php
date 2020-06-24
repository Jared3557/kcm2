<?php

// gateway-schoolList.php

ob_start();  // rpt_output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );

include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-objects.inc.php' );
include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );

include_once( '../kcm-kernel/kernel-emitter.inc.php');
include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'gateway-system-globals.inc.php');

CONST PAGE_SCHOOL_SELECT   = 1;
CONST PAGE_SCHOOL_REPORT   = 2;
CONST PAGE_SCHOOL_VIEW     = 3;

Class appForm_school_select extends Draff_Form {
private $current_schoolSelectMap;

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {  // abstract function
    kernel_processBannerSubmits( $appGlobals, $appChain );
    $appData->apd_common_submit($appChain);
    if (  isset($appChain->chn_submit[1]) and is_numeric($appChain->chn_submit[1]) ) {
        $appChain->chn_status->ses_set('#recordType','schoolId');
        $appChain->chn_status->ses_set('#recordId',$appChain->chn_submit[1]);  //??????
        $appChain->chn_status->ses_set('#schoolId',$appChain->chn_submit[1]);
        $appChain->chn_form_launch(PAGE_SCHOOL_VIEW,'');
        return;
    }
    $appData->apd_common_submit($appChain);
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    if ($appChain->chn_url_rsmMode == 'today') {
        $appData->apd_init_selectMap_mySchools( $appGlobals );
        $this->current_schoolSelectMap = $appData->apd_init_selectMap_mySchools;
    }
    else {
        $appData-> apd_init_selectMap_allSchools( $appGlobals );
        $this->current_schoolSelectMap = $appData->apd_init_selectMap_allSchools;
    }
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-select' );
    $appEmitter->set_title('Staff List');
    $appEmitter->set_menu_standard($appChain, $appGlobals);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_common_header_define( $this );
//    $appData->apd_select_getData( $appGlobals, $appChain );
    foreach ($this->current_schoolSelectMap as $schoolId => $schoolName ) {
        $this->drForm_addField( new Draff_Button(  '@schoolId_' .  $schoolId , $schoolName, array('class'=>'draff-button-select') ) );
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
    $appData->apd_common_header_output( $appData, $appGlobals, $appChain, $appEmitter );
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-select');
    foreach ($this->current_schoolSelectMap as $schoolId => $schoolName ) {
        $appEmitter->content_field('@schoolId_' .  $schoolId);
    }
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

Class appForm_school_report extends Draff_Form {
public $scl_reportSchoolList;

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    $appData->apd_common_submit($appChain);
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $this->scl_reportSchoolList = new appReport_school();
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $this->scl_reportSchoolList->rpt_initStyles($appEmitter);
    $appEmitter->set_theme( 'theme-report' );
    $appEmitter->set_title('School List');
    $appEmitter->set_menu_standard( $appChain, $appGlobals );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_common_header_define( $this );
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
    $appData->apd_common_header_output( $appData, $appGlobals, $appChain, $appEmitter );
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-report');
    $this->scl_reportSchoolList->rpt_output($appEmitter,$appGlobals);
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class


Class appForm_school_view extends Draff_Form {
    
    function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
        kernel_processBannerSubmits( $appGlobals, $appChain );
        if ($appChain->chn_submit[0] == 'back') {
            $appChain->chn_form_launch(PAGE_STAFF_SELECT);
            return;
        }
        $appData->apd_common_submit($appChain);
    }
    
    function drForm_initData( $appData, $appGlobals, $appChain ) {
    }
    
    function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
        $appEmitter->set_theme( 'theme-panel' );
        $appEmitter->set_title('School List');
        $appEmitter->set_menu_standard($appChain, $appGlobals);
        $appEmitter->set_menu_customize( $appChain, $appGlobals  );
        $appEmitter->addOption_styleTag('table.loc-edit-table','border:1pt;width:30em;min-width:30em;max-width:60em; padding: 1em 0.4em 1em 0.4em;');
        $appEmitter->addOption_styleTag('td.loc-name','width:12em;border:1pt; padding: 1em 0.4em 1em 0.4em;');
        $appEmitter->addOption_styleTag('td.loc-colDesc','width:4em;border:1pt; padding: 1em 0.4em 1em 0.4em;');
        $appEmitter->addOption_styleTag('td.loc-phone','width:8em;border:1pt; padding: 1em 0.4em 1em 0.4em;');
        $appEmitter->addOption_styleTag('span.loc-short','margin-left:1.2em;');
        $appEmitter->addOption_styleTag('span.loc-email2','font-size:1.0em;;');
        
    }
    
    function drForm_initFields( $appData, $appGlobals, $appChain ) {
        $appData->apd_common_header_define( $this );
        $appData->apd_getRecord_school( $appGlobals, $appChain );
        $this->drForm_addField( new Draff_Button( 'back',"Back") );
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
        $appData->apd_common_header_output( $appData, $appGlobals, $appChain, $appEmitter );
    }
    
    function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
        $staff = $appData->apd_staff_rec;
        
        $appEmitter->zone_start('zone-content-scrollable theme-panel');  // keep constant even though in theme-panel
        $appEmitter->table_start('draff-edit loc-edit-table',1);
        
        $appEmitter->table_head_start();
        $appEmitter->row_start();
        $appEmitter->cell_block($staff->sSt_name,'loc-name', 'colspan="2"');
        $appEmitter->row_end();
        $appEmitter->table_head_end();
        $appEmitter->table_body_start('rpt-body');
        
        $appEmitter->row_start('rpt-grid-row');
        $appEmitter->cell_block('Home Phone','loc-colDesc');
        $appEmitter->cell_block($appEmitter->toString_phone($staff->sSt_homePhone),'loc-phone');
        $appEmitter->row_end();
        
        $appEmitter->row_start('rpt-grid-row');
        $appEmitter->cell_block('Cell Phone','loc-colDesc');
        $appEmitter->cell_block($appEmitter->toString_phone($staff->sSt_cellPhone),'loc-phone');
        $appEmitter->row_end();
        
        $appEmitter->row_start('rpt-grid-row');
        $appEmitter->cell_block('Work Phone','loc-colDesc');
        $appEmitter->cell_block($appEmitter->toString_phone($staff->sSt_workPhone),'loc-phone');
        $appEmitter->row_end();
        
        $appEmitter->row_start('rpt-grid-row');
        $email = $staff->sSt_email;
        $short = $staff->sSt_shortName;
        $appEmitter->cell_block('email','loc-colDesc');
        if (!empty($email)) {
            $emailLink = '<a href="mailto:'.$email.'" class="loc-link-as-button"><span class="email1">email: </><span class="email2">'.$short.'</></a> ';
        }
        else {
            $emailLink = '';
        }
        $appEmitter->cell_block($emailLink,'loc-email');
        $appEmitter->row_end();
        $appEmitter->table_body_end();
        
        $appEmitter->table_foot_start();
        $appEmitter->row_start('rpt-grid-row');
        $appEmitter->cell_block('@back','','colspan="2"') ;
        $appEmitter->row_end();
        $appEmitter->table_foot_end();
        
        $appEmitter->table_end();
        
        $appEmitter->zone_end();
    }
    
    function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    }
    
} // end class

class appReport_school {

function __construct() {
}

function rpt_initStyles($appEmitter) {
    $appEmitter->addOption_styleTag('span.loc-school','font-size:14pt;font-weight:bold;');
    $appEmitter->addOption_styleTag('span.loc-phone','font-size:14pt;');
}

function rpt_getData($appGlobals) {
    $fldList = array();
    $fldList[] = 'pSc:NameShort';
    $fldList[] = 'pSc:SchoolSystem';
    $fldList[] = 'pSc:Address';
    $fldList[] = 'pSc:City';
    $fldList[] = 'pSc:State';
    $fldList[] = 'pSc:Zip';
    $fldList[] = 'pSc:SchoolPhone';
    $fldList[] = 'pSc:NotesContacts';
    $fldList[] = 'pSc:NotesEquipment';
    $fldList[] = 'pSc:HiddenStatus';
    $fields = "`" . implode($fldList,"`, `") . "`";
    $query = new draff_database_query;
    $query->rsmDbq_set("SELECT ".$fields." FROM `pr:school` WHERE `pSc:HiddenStatus` = '0' ORDER BY `pSc:NameShort`");
    $dbResult = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
//    $sql[] = "Select $fields";
//    $sql[] = "FROM `pr:school`";
//    $sql[] = "WHERE `pSc:HiddenStatus` = '0'";
//    $sql[] = "ORDER BY `pSc:NameShort`";
//    $query = implode( $sql, ' ');
//    $dbResult = $appGlobals->gb_db->rc_query( $query );
//    if ( ($dbResult === FALSE) || ($dbResult->num_rows == 0)) {
//        draff_errorTerminate( $query);
//    }
    return $dbResult;
}

function rpt_output($appEmitter,$appGlobals) {
    $tableLayout = new rsmp_emitter_table_layout('sc', array(25,25,30));
    $appEmitter->table_start('draff-report',$tableLayout);

    $appEmitter->table_head_start();
    $appEmitter->krnEmit_reportTitleRow('School List Report',3);

    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('School Name<br>  School System','co-school');
    $appEmitter->cell_block('Address','co-address');
    $appEmitter->cell_block('Notes','co-notes');
    $appEmitter->row_end();

    $appEmitter->table_head_end();

    $appEmitter->table_body_start('');
    $dbResult = $this->rpt_getData($appGlobals);
    foreach ($dbResult as $row) {
        $this->rpt_outRow_detail($appEmitter,$row);
    }
    $appEmitter->table_body_end();

    $appEmitter->table_end();


}

function rpt_outRow_detail($appEmitter,$row) {
       $phone = '<span class="loc-phone">' . $appEmitter->getString_phone($row['pSc:SchoolPhone']) .  '</span>';
       $address = $row['pSc:Address'];
       $city = $row['pSc:City'];
       $state = $row['pSc:State'];
       $zip =$row['pSc:Zip'];
       $addressAll = $address . '<br>' . $city . ',  ' .  $state . ' ' . $zip;
       $googleAddress = $address . '+' . $city . '+' .  $state . '+' . $zip;
       $googleAddress = str_replace(' ','+',$googleAddress);
       $googleUrl = 'https://www.google.com/maps/place/'.$address . '+' . $city . '+' .  $state . '+' . $zip;
       $address = $address . '<br>' . $city . ',  ' .  $state . ' ' . $zip;


       $schoolName = '<span class="loc-school">' . $row['pSc:NameShort'] . '</span>';
       $googleLink = $appEmitter->getString_link($googleUrl,'Map','') ;

        $appEmitter->row_start('rpt-grid-row');
        $appEmitter->cell_block($schoolName . '<br>' .$googleLink ,'co-school');
        $appEmitter->cell_block($addressAll, 'co-address');
        $appEmitter->cell_block($row['pSc:NotesEquipment'],'co-notes');
        $appEmitter->row_end();
}

} // end class

class application_data extends draff_appData {
public $apd_selectMap_allSchools;
public $apd_selectMap_mySchools;
public $apd_school_id;
public $apd_school_rec;

function __construct() {
}

function apd_init_selectMap_allSchools( $appGlobals ) {
    $this->apd_init_selectMap_allSchools = $this->getMap_allSchools_select($appGlobals);
}

function apd_init_selectMap_mySchools( $appGlobals ) {
    $this->apd_init_selectMap_mySchools = gwy_fetch_selectMap_mySchools($appGlobals);
}

function apd_common_header_define( $form ) {
    $form->drForm_addField( new Draff_Button( '@report','School List<br>Report') );
    $form->drForm_addField( new Draff_Button( '@selectAll','All<br>Schools') );
    $form->drForm_addField( new Draff_Button( '@selectToday',"My<br>Schools") );
}

function apd_common_header_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-header theme-select');
    $appEmitter->content_field('@selectToday');
    $appEmitter->content_field('@selectAll');
    $appEmitter->content_field('@report');
    $appEmitter->zone_end();
}

function apd_common_submit($appChain) {
    if ($appChain->chn_submit[0] == '@report') {
        $appChain->chn_status->ses_set('#filter_select','all');
        $appChain->chn_form_launch(PAGE_SCHOOL_REPORT);
        return;
    }
    if ( $appChain->chn_submit[0] == '@selectAll') {
        $appChain->chn_status->ses_set('#filter_select','all');
        $appChain->chn_form_launch(PAGE_SCHOOL_SELECT,'all');
        return;
    }
    if ( $appChain->chn_submit[0] == '@selectToday') {
        $appChain->chn_status->ses_set('#filter_select','today');
        $appChain->chn_form_launch(PAGE_SCHOOL_SELECT,'today');
        return;
    }
}

function apd_getRecord_school( $appGlobals, $appChain ) {
    $this->apd_school_id = $appChain->chn_status->ses_get('schoolId',0);
    $this->apd_school_rec = $appGlobals->gb_pdo->rsmDbe_readRecord('dbRecord_school',$this->apd_school_id);
}

function getMap_allSchools_select($appGlobals) {
    $query = new draff_database_query;
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_add( "FROM `pr:school`");
    $query->rsmDbq_add( "WHERE `pSc:HiddenStatus` = '0'");
    $query->rsmDbq_add( "ORDER BY `pSc:NameShort`");
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
    $mySchoolsSelect = array();
    foreach ($result as $row) {
        $schoolId = $row['pSc:SchoolId'];
        $mySchoolsSelect[$schoolId] = $row['pSc:NameShort'];
    }
    // to get fancier could list programs at each school - DOW, etc
    return $mySchoolsSelect;
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
rc_session_initialize();

$appGlobals = new kcmGateway_globals();
$appGlobals->gb_forceLogin ();
$appData = new application_data();

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@         Process Page               @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );
$appChain->chn_form_register(PAGE_SCHOOL_SELECT,'appForm_school_select');
$appChain->chn_form_register(PAGE_SCHOOL_REPORT,'appForm_school_report');
$appChain->chn_form_register(PAGE_SCHOOL_VIEW,'appForm_school_view');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>