<?php

// gateway-staffList.php

ob_start ();  // output buffering (needed for redirects, content changes)

include_once ( '../../rc_defines.inc.php' );
include_once ( '../../rc_admin.inc.php' );
include_once ( '../../rc_database.inc.php' );
include_once ( '../../rc_messages.inc.php' );

include_once ( '../draff/draff-chain.inc.php' );
include_once ( '../draff/draff-database.inc.php' );
include_once ( '../draff/draff-emitter.inc.php' );
include_once ( '../draff/draff-form.inc.php' );
include_once ( '../draff/draff-functions.inc.php' );
include_once ( '../draff/draff-menu.inc.php' );
include_once ( '../draff/draff-page.inc.php' );

include_once ( '../kcm-kernel/kernel-functions.inc.php' );
include_once ( '../kcm-kernel/kernel-objects.inc.php' );
include_once ( '../kcm-kernel/kernel-globals.inc.php' );
include_once ( '../kcm-kernel/kernel-schedule.inc.php' );

include_once ( 'gateway-system-globals.inc.php' );

const PAGE_STAFF_SELECT = 1;
const PAGE_STAFF_VIEW = 2;
const PAGE_STAFF_REPORT = 3;

class appForm_staff_select extends kcmKernel_Draff_Form {
    private $current_staffSelectMap;
    
    function drForm_process_submit ( $appData , $appGlobals , $appChain ) {  // abstract function
        kernel_processBannerSubmits ( $appGlobals , $appChain );
        if ( isset( $appChain->chn_submit[ 1 ] ) and is_numeric ( $appChain->chn_submit[ 1 ] ) ) {
            $appChain->chn_status->ses_set ( '#recordType' , 'staffId' );
            $appChain->chn_status->ses_set ( '#recordId' , $appChain->chn_submit[ 1 ] );  //??????
            $appChain->chn_status->ses_set ( '#staffId' , $appChain->chn_submit[ 1 ] );
            $appChain->chn_form_launch ( PAGE_STAFF_VIEW , '' );
            return;
        }
        $appData->apd_common_submit ( $appChain );
    }
    
    function drForm_initData ( $appData , $appGlobals , $appChain ) {
        if ( $appChain->chn_url_rsmMode == 'today' ) {
            $appData->apd_init_selectMap_myStaff ( $appGlobals );
            $this->current_staffSelectMap = $appData->apd_selectMap_myStaff;
        } else {
            $appData->apd_init_selectMap_allStaff ( $appGlobals );
            $this->current_staffSelectMap = $appData->apd_selectMap_allStaff;
        }
    }
    
    function drForm_initHtml ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appEmitter->emit_options->set_theme ( 'theme-select' );
        $appEmitter->emit_options->set_title ( 'Staff List' );
        $appGlobals->gb_ribbonMenu_Initialize ( $appChain , $appGlobals );
        $appGlobals->gb_menu->drMenu_customize ();
        
    }
    
    function drForm_initFields ( $appData , $appGlobals , $appChain ) {
        $appData->apd_common_header_define ( $this );
//    $appData->apd_select_getData( $appGlobals, $appChain );
        foreach ( $this->current_staffSelectMap as $staffId => $staffName ) {
            $this->drForm_addField ( new Draff_Button( '@staffId_' . $staffId , $staffName , array ( 'class' => 'draff-button-select' ) ) );
        }
    }
    
    function drForm_process_output ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appGlobals->gb_output_form ( $appData , $appChain , $appEmitter , $this );
    }
    
    function drForm_outputHeader ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appData->apd_common_header_output ( $appEmitter );
    }
    
    function drForm_outputContent ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appEmitter->zone_start ( 'zone-content-scrollable theme-select' );
        foreach ( $this->current_staffSelectMap as $staffId => $staffName ) {
            $appEmitter->content_field ( '@staffId_' . $staffId );
        }
        $appEmitter->zone_end ();
    }
    
    function drForm_outputFooter ( $appData , $appGlobals , $appChain , $appEmitter ) {
    }
    
} // end class

class appForm_staff_view extends kcmKernel_Draff_Form {
    
    function drForm_process_submit ( $appData , $appGlobals , $appChain ) {
        kernel_processBannerSubmits ( $appGlobals , $appChain );
        if ( $appChain->chn_submit[ 0 ] == 'back' ) {
            $appChain->chn_form_launch ( PAGE_STAFF_SELECT );
            return;
        }
        $appData->apd_common_submit ( $appChain );
    }
    
    function drForm_initData ( $appData , $appGlobals , $appChain ) {
    }
    
    function drForm_initHtml ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appEmitter->emit_options->set_theme ( 'theme-panel' );
        $appEmitter->emit_options->set_title ( 'Staff List' );
        $appGlobals->gb_ribbonMenu_Initialize ( $appChain , $appGlobals );
        $appGlobals->gb_menu->drMenu_customize ();
        $appEmitter->emit_options->addOption_styleTag ( 'table.loc-edit-table' , 'border:1pt;width:30em;min-width:30em;max-width:60em; padding: 1em 0.4em 1em 0.4em;' );
        $appEmitter->emit_options->addOption_styleTag ( 'td.loc-name' , 'width:12em;border:1pt; padding: 1em 0.4em 1em 0.4em;' );
        $appEmitter->emit_options->addOption_styleTag ( 'td.loc-colDesc' , 'width:4em;border:1pt; padding: 1em 0.4em 1em 0.4em;' );
        $appEmitter->emit_options->addOption_styleTag ( 'td.loc-phone' , 'width:8em;border:1pt; padding: 1em 0.4em 1em 0.4em;' );
        $appEmitter->emit_options->addOption_styleTag ( 'span.loc-short' , 'margin-left:1.2em;' );
        $appEmitter->emit_options->addOption_styleTag ( 'span.loc-email2' , 'font-size:1.0em;;' );
        
    }
    
    function drForm_initFields ( $appData , $appGlobals , $appChain ) {
        $appData->apd_common_header_define ( $this );
        $appData->apd_getRecord_staff ( $appGlobals , $appChain );
        $this->drForm_addField ( new Draff_Button( 'back' , "Back" ) );
    }
    
    function drForm_process_output ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appGlobals->gb_output_form ( $appData , $appChain , $appEmitter , $this );
    }
    
    function drForm_outputHeader ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appData->apd_common_header_output ( $appEmitter );
    }
    
    function drForm_outputContent ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $staff = $appData->apd_staff_rec;
        
        $appEmitter->zone_start ( 'zone-content-scrollable theme-panel' );  // keep constant even though in theme-panel
        $appEmitter->table_start ( 'draff-edit loc-edit-table' , 1 );
        
        $appEmitter->table_head_start ();
        $appEmitter->row_start ();
        $appEmitter->cell_block ( $staff->sSt_name , 'loc-name' , 'colspan="2"' );
        $appEmitter->row_end ();
        $appEmitter->table_head_end ();
        $appEmitter->table_body_start ( 'rpt-body' );
        
        $appEmitter->row_start ( 'rpt-grid-row' );
        $appEmitter->cell_block ( 'Home Phone' , 'loc-colDesc' );
        $appEmitter->cell_block ( $appEmitter->toString_phone ( $staff->sSt_homePhone ) , 'loc-phone' );
        $appEmitter->row_end ();
        
        $appEmitter->row_start ( 'rpt-grid-row' );
        $appEmitter->cell_block ( 'Cell Phone' , 'loc-colDesc' );
        $appEmitter->cell_block ( $appEmitter->toString_phone ( $staff->sSt_cellPhone ) , 'loc-phone' );
        $appEmitter->row_end ();
        
        $appEmitter->row_start ( 'rpt-grid-row' );
        $appEmitter->cell_block ( 'Work Phone' , 'loc-colDesc' );
        $appEmitter->cell_block ( $appEmitter->toString_phone ( $staff->sSt_workPhone ) , 'loc-phone' );
        $appEmitter->row_end ();
        
        $appEmitter->row_start ( 'rpt-grid-row' );
        $email = $staff->sSt_email;
        $short = $staff->sSt_shortName;
        $appEmitter->cell_block ( 'email' , 'loc-colDesc' );
        if ( ! empty( $email ) ) {
            $emailLink = '<a href="mailto:' . $email . '" class="loc-link-as-button"><span class="email1">email: </><span class="email2">' . $short . '</></a> ';
        } else {
            $emailLink = '';
        }
        $appEmitter->cell_block ( $emailLink , 'loc-email' );
        $appEmitter->row_end ();
        $appEmitter->table_body_end ();
        
        $appEmitter->table_foot_start ();
        $appEmitter->row_start ( 'rpt-grid-row' );
        $appEmitter->cell_block ( '@back' , '' , 'colspan="2"' );
        $appEmitter->row_end ();
        $appEmitter->table_foot_end ();
        
        $appEmitter->table_end ();
        
        $appEmitter->zone_end ();
    }
    
    function drForm_outputFooter ( $appData , $appGlobals , $appChain , $appEmitter ) {
    }
    
} // end class

class appForm_staff_report extends kcmKernel_Draff_Form {
    public $sr_reportStaffList;
    
    function drForm_process_submit ( $appData , $appGlobals , $appChain ) {
        kernel_processBannerSubmits ( $appGlobals , $appChain );
        $appData->apd_common_submit ( $appChain );
    }
    
    function drForm_process_output ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appGlobals->gb_output_form ( $appData , $appChain , $appEmitter , $this );
    }
    
    function drForm_initData ( $appData , $appGlobals , $appChain ) {
        $appData->apd_report_staffList = new appReport_staff();
    }
    
    function drForm_initHtml ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appEmitter->emit_options->set_theme ( 'theme-report' );
        $appEmitter->emit_options->set_title ( 'Staff List' );
        $appGlobals->gb_ribbonMenu_Initialize ( $appChain , $appGlobals );
        $appGlobals->gb_menu->drMenu_customize ();
        $appData->apd_report_staffList->stdRpt_initStyles ( $appEmitter );
    }
    
    function drForm_initFields ( $appData , $appGlobals , $appChain ) {
        $appData->apd_common_header_define ( $this );
    }
    
    function drForm_outputHeader ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appData->apd_common_header_output ( $appEmitter );
    }
    
    function drForm_outputContent ( $appData , $appGlobals , $appChain , $appEmitter ) {
        $appEmitter->zone_start ( 'zone-content-scrollable theme-report' );
        $appData->apd_report_staffList->stdRpt_output ( $appEmitter , $appGlobals );
        $appEmitter->zone_end ();
    }
    
    function drForm_outputFooter ( $appData , $appGlobals , $appChain , $appEmitter ) {
    }
    
} // end class

class appReport_staff {
    
    function stdRpt_initStyles ( $appEmitter ) {
        $appEmitter->emit_options->addOption_styleTag ( 'table.co-table' , 'width:50rem' );
        $appEmitter->emit_options->addOption_styleTag ( '.co-name' , 'width:9rem;' );
        $appEmitter->emit_options->addOption_styleTag ( '.co-phone' , 'width:3rem' );
        $appEmitter->emit_options->addOption_styleTag ( 'span.loc-short' , 'margin-left:1.2em;' );
        $appEmitter->emit_options->addOption_styleTag ( 'span.loc-email1' , 'font-size:0.5em;' );
        $appEmitter->emit_options->addOption_styleTag ( 'span.loc-email2' , 'font-size:1.0em;;' );
        $appEmitter->emit_options->addOption_styleTag ( 'a.loc-link-as-button' , 'background-color: #cfc;display: inline-block;border: 1px solid #8b8;text-align: center;padding: 0.1em 0.5em 0.1em 0.5em;	border-radius: 0.6em; margin: 0.1em 0.1em 0.1em 0.1em;' );
    }
    
    function stdRpt_output ( $appEmitter , $appGlobals ) {
        
        $result = $this->rpt_getData ( $appGlobals );
        
        $tableLayout = new rsmp_emitter_table_layout( 'sc' , array ( 25 , 12 , 12 , 12 , 20 ) );
        $appEmitter->table_start ( 'draff-report' , $tableLayout );
        
        $appEmitter->table_head_start ();
        krnEmit_reportTitleRow ( $appEmitter , 'Staff List Report' , 5 );
        $appEmitter->row_start ( 'rpt-grid-row' );
        $appEmitter->cell_block ( 'Name' , 'co-name' );
        $appEmitter->cell_block ( 'Home Phone' , 'co-phone' );
        $appEmitter->cell_block ( 'Cell Phone' , 'co-phone' );
        $appEmitter->cell_block ( 'Work Phone' , 'co-phone' );
        $appEmitter->cell_block ( 'Email' , 'loc-email2' );
        $appEmitter->row_end ();
        $appEmitter->table_head_end ();
        
        $appEmitter->table_body_start ( '' );
        $row = array ();
        $row[ 'sSt:ShortName' ] = 'Kidchess Office';
        $row[ 'sSt:FirstName' ] = 'Kidchess Office';
        $row[ 'sSt:LastName' ] = '';
        $row[ 'sSt:HomePhone' ] = '';
        $row[ 'sSt:WorkPhone' ] = '770-575-5802 ';
        $row[ 'sSt:CellPhone' ] = '';
        $row[ 'sSt:Email' ] = 'admin@kidchess.com';
        $row[ 'sSt:HiddenStatus' ] = '0';
        $row[ '' ] = '';
        $this->rpt_outRow_detail ( $appEmitter , $row , TRUE );
        foreach ( $result as $row ) {
            $this->rpt_outRow_detail ( $appEmitter , $row );
        }
        $appEmitter->table_body_end ();
        
        $appEmitter->table_end ();
        
    }
    
    function rpt_getData ( $appGlobals ) {
        $fldList = array ();
        $fldList[] = 'sSt:FirstName';
        $fldList[] = 'sSt:LastName';
        $fldList[] = 'sSt:ShortName';
        $fldList[] = 'sSt:Email';
        $fldList[] = 'sSt:HomePhone';
        $fldList[] = 'sSt:WorkPhone';
        $fldList[] = 'sSt:CellPhone';
        $fldList[] = 'sSt:HiddenStatus';
        $fields = "`" . implode ( $fldList , "`, `" ) . "`";
        $sql[] = "Select $fields";
        $sql[] = "FROM `st:staff`";
        $sql[] = "WHERE `sSt:HiddenStatus` = '0'";
        $sql[] = "ORDER BY `sSt:FirstName`,`sSt:LastName`";
        $query = implode ( $sql , ' ' );
        $result = $appGlobals->gb_pdo->rsmDbe_execute ( $query );
        return $result;
    }
    
    function rpt_outRow_detail ( $appEmitter , $row , $isOffice = FALSE ) {
        $short = $row[ 'sSt:ShortName' ];
        $first = $row[ 'sSt:FirstName' ];
        $last = $row[ 'sSt:LastName' ];
        $email = $row[ 'sSt:Email' ];
        $name = $first . ' ' . $last;
        if ( $short != $first ) {
            $name = $name . '<br><span class="loc-short">(' . $short . ')</span>';
        }
        $phone1 = $appEmitter->toString_phone ( $row[ 'sSt:HomePhone' ] );
        $phone2 = $appEmitter->toString_phone ( $row[ 'sSt:WorkPhone' ] );
        $phone3 = $appEmitter->toString_phone ( $row[ 'sSt:CellPhone' ] );
        if ( $isOffice ) {
            $name = '<h2>' . $name . '<h2>';
            $phone2 = '<h2>' . $phone2 . '<h2>';
        }
        $appEmitter->row_start ( 'rpt-grid-row' );
        $appEmitter->cell_block ( $name , 'co-name' );
        $appEmitter->cell_block ( $phone1 );
        $appEmitter->cell_block ( $phone2 );
        $appEmitter->cell_block ( $phone3 );
        if ( ! empty( $email ) ) {
            $emailLink = '<a href="mailto:' . $email . '" class="loc-link-as-button"><span class="email2">' . $short . '</></a> ';
        } else {
            $emailLink = '';
        }
        $appEmitter->cell_block ( $emailLink );
        $appEmitter->row_end ();
    }
    
} // end class

class application_data extends draff_appData {
    public $apd_selectMap_allStaff;   // used by select
    public $apd_selectMap_myStaff;    // used by select
    public $apd_report_staffList;  // used by report
    public $apd_staff_id;  // set by select, used by view
    public $apd_staff_rec; // used by view
    
    function apd_init_selectMap_allStaff ( $appGlobals ) {
        $this->apd_selectMap_allStaff = $this->apd_init_selectMap_officeStaff () + kcm_fetch_selectMap_staff ( $appGlobals );
    }
    
    function apd_init_selectMap_myStaff ( $appGlobals ) {
        $this->apd_selectMap_myStaff = $this->apd_init_selectMap_officeStaff () + gwy_fetch_selectMap_myStaff ( $appGlobals );
    }
    
    function apd_init_selectMap_officeStaff () {
        return array ( 0 => 'Kidchess Office' );
    }
    
    function apd_getRecord_staff ( $appGlobals , $appChain ) {
        $this->apd_staff_id = $appChain->chn_status->ses_get ( '#staffId' , 0 );
        if ( $this->apd_staff_id == 0 ) {
            $this->apd_staff_rec = $this->apd_getRecord_office ();
        } else {
            $this->apd_staff_rec = $appGlobals->gb_pdo->rsmDbe_readRecord ( 'dbRecord_staff' , $this->apd_staff_id );
        }
    }
    
    private function apd_getRecord_office () {
        $row[ 'sSt:StaffId' ] = '0';
        $row[ 'sSt:ShortName' ] = 'Kidchess Office';
        $row[ 'sSt:FirstName' ] = 'Kidchess Office';
        $row[ 'sSt:LastName' ] = '';
        $row[ 'sSt:HomePhone' ] = '';
        $row[ 'sSt:WorkPhone' ] = '770-575-5802 ';
        $row[ 'sSt:CellPhone' ] = '';
        $row[ 'sSt:Email' ] = 'admin@kidchess.com';
        $row[ 'sSt:HiddenStatus' ] = 0;
        $newStaffItem = new dbRecord_staff;
        $newStaffItem->rsmDbr_loadRow ( $row );
        return $newStaffItem;
        
    }
    
    function apd_common_header_define ( $form ) {
        $form->drForm_addField ( new Draff_Button( '@report' , 'Staff List<br>Report' ) );
        $form->drForm_addField ( new Draff_Button( '@selectAll' , 'All<br>Staff' ) );
        $form->drForm_addField ( new Draff_Button( '@selectToday' , "Staff I'm<br> With Today" ) );
    }
    
    function apd_common_header_output ( $appEmitter ) {
        $appEmitter->zone_start ( 'zone-content-header theme-select' );
        $appEmitter->content_field ( '@selectToday' );
        $appEmitter->content_field ( '@selectAll' );
        $appEmitter->content_field ( '@report' );
        $appEmitter->zone_end ();
    }
    
    function apd_common_submit ( $appChain ) {
        if ( $appChain->chn_submit[ 0 ] == '@report' ) {
            $appChain->chn_status->ses_set ( '#filter_select' , 'all' );
            $appChain->chn_form_launch ( PAGE_STAFF_REPORT );
            return;
        }
        if ( $appChain->chn_submit[ 0 ] == '@selectAll' ) {
            $appChain->chn_status->ses_set ( '#filter_select' , 'all' );
            $appChain->chn_form_launch ( PAGE_STAFF_SELECT , 'all' );
            return;
        }
        if ( $appChain->chn_submit[ 0 ] == '@selectToday' ) {
            $appChain->chn_status->ses_set ( '#filter_select' , 'today' );
            $appChain->chn_form_launch ( PAGE_STAFF_SELECT , 'today' );
            return;
        }
    }
    
}  // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize ();

$appChain = new Draff_Chain( 'kcmKernel_emitter' );
$appChain->chn_register_appGlobals ( $appGlobals = new kcmGateway_globals() );
$appChain->chn_register_appData ( new application_data() );
$appGlobals->gb_forceLogin ();

$appChain->chn_form_register ( PAGE_STAFF_SELECT , 'appForm_staff_select' );
$appChain->chn_form_register ( PAGE_STAFF_VIEW , 'appForm_staff_view' );
$appChain->chn_form_register ( PAGE_STAFF_REPORT , 'appForm_staff_report' );
$appChain->chn_form_launch (); // proceed to current step

exit;

?>