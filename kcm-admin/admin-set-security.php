<?php

// racKcmAdmin_security.php

// todo:
//    eliminate old expired data

ob_start();  // output buffering (needed for redirects, draff-appHeader changes)- or use hide ????

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
include_once( '../kcm-kernel/kernel-schedule.inc.php');

include_once( 'admin-system-emitter.inc.php' );
include_once( 'admin-system-globals.inc.php' );

//@@@@@@@@@@@@@@@@@@@@
//@  Step 1
//@@@@@@@@@@@@@@@@@@@@

class appForm_courseAuthorization_grid extends Draff_Form {  // specify winner

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    //$appChain->chn_form_savePostedData();
    $appData->apd_init_always ($appGlobals, $appChain);
    //$appChain->chn_ValidateAndRedirectIfError();
    switch ($appChain->chn_submit[0]) {
        case 'prmViewByStaff':
            $appChain->chn_status->ses_set('#gridSubmitViewMode','staff');
            $appChain->chn_form_launch(1);
            return;
            break;

        case 'prmViewBySchool':
            $appChain->chn_status->ses_set('#gridSubmitViewMode','school');
            $appChain->chn_form_launch(1);
            return;
            break;
        case 'editStaff':
            $appChain->chn_status->ses_set('#editStaffId'    ,$appChain->chn_submit[1]);
            $appChain->chn_clearAllPosted();
            $appChain->chn_form_launch(2);
        case 'editCourse':
            $courseKey = (count($appChain->chn_submit)==2) ? $appChain->chn_submit[1] : $appChain->chn_submit[1] . '-' . $appChain->chn_submit[2];
            $appChain->chn_status->ses_set('#editCourseKey',$courseKey);
           // $appChain->chn_status->ses_set('#gridSubmitSchoolId',$appChain->chn_submit[1]);
            //$appChain->chn_status->ses_set('#gridSubmitDow'    ,$appChain->chn_submit[2]);
            $appChain->chn_clearAllPosted();
            $appChain->chn_form_launch(2);
            return;
            break;
    }
    $appChain->chn_launch_continueChain(1);
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_init_always ($appGlobals, $appChain);
    // $appData->apd_selectMap_roles      = $appData->apd_fetch_selectMap_roles ($appGlobals);
    if ($appData->apd_current_isCourseMode) {
        $appData->apd_joiner_allCourses = $appData->apd_fetch_joiner_allCourses ($appGlobals);
    }
    else {
        $appData->apd_joiner_allStaff = $appData->apd_fetch_joiner_allStaff($appGlobals);
    }
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-report' );
    $appEmitter->set_title('Set Roster Security');
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    // if ($appData->apd_current_viewMode=='school') {
    //     //$appEmitter->cell_block($appEmitter->getString_button( 'View by Staff', '', 'prmViewByStaff'));
    // }
    // else {
    //    $appData->apd_fetch_selectMap_staff($appGlobals);  // done in report
    // }

    //nn $appData->apd_joinMap_auth = new cPA_calAuth_kcmFilteredBatch;
    //nn if ($appData->apd_current_viewMode=='school') {
    //nn     $appData->apd_joinMap_auth->rstBatch_readView_byProgram ($appGlobals);
    //nn }
    //nn else {
    //nn     $appData->apd_joinMap_auth->rstBatch_readView_byStaff ($appGlobals);
    //nn }
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
    $appEmitter->zone_start('zone-content-header theme-select');
    if ($appData->apd_current_viewMode=='school') {
        $appEmitter->cell_block($appEmitter->getString_button( 'View by Staff', '', 'prmViewByStaff'));
    }
    else {
        $appEmitter->cell_block($appEmitter->getString_button( 'View by School/Event', '', 'prmViewBySchool'));
    }
    $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-report');
    $report = new appReport_authorization_grid;
    $report->rep_output( $appData, $appGlobals, $appChain, $appEmitter );
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@
//@  Step 2
//@@@@@@@@@@@@@@@@@@@@

class appForm_courseAuthorization_edit extends Draff_Form {

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    if ($appChain->chn_submit[0] == 'subCancel') {
        $appChain->chn_message_set( 'Cancelled');
        $appChain->chn_form_launch(1);
    }
    if ($appChain->chn_submit[0] != 'subSave') {
        $appChain->chn_form_launch(1);  // this should never happen
    }

     // Validate
    //$first = true;
    $this->drForm_initData( $appData, $appGlobals, $appChain );
    $appData->apd_validate_authorizationJoin ($appGlobals, $appChain);
    $appChain->chn_form_launch(DRAFF_TYPE_RELAUNCH_IF_ERROR);

    // save the information
    $appData->apd_save_authorizationJoin($appGlobals, $appChain);
    $appChain->chn_message_set( 'Authorization Record Saved');
    $appChain->chn_form_launch(1);

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_init_always ($appGlobals, $appChain);
    $appData->apd_load_authorizationJoin ($appGlobals, $appChain);
    foreach ($appData->apd_joiner_authorizedActiveEdit as $authorizeId => $authorize) {
        $recordId = $authorize->cPA_authorizationId;
        $appChain->chn_readPostedField( $authorize->cPA_courseKey,'courseKey',$recordId);
        $appChain->chn_readPostedField( $authorize->cPA_staffId,'staffId',$recordId);
        $appChain->chn_readPostedField( $authorize->cPA_roleType,'roleType',$recordId);
        $appChain->chn_readPostedField( $authorize->cPA_dateExpires,'expDate',$recordId);
        $appChain->chn_readPostedField( $authorize->cPA_readOnly,'readOnly',$recordId);
    }
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-report' );
    $appEmitter->set_title('Set Roster Security');
    $appEmitter->set_menu_standard($appChain, $appGlobals);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
   // $appData->apd_edit_getData( $appGlobals, $appChain );
    if ($appData->apd_current_isCourseMode) {
        foreach ($appData->apd_joiner_oneCourse as $authorizationId => $authorization) {
            $recordId = $authorization->cPA_authorizationId;
            if ( !isset($appData->apd_selectMap_staff[$authorization->cPA_staffId]) ) {
                 $select =  $appData->apd_selectMap_staff;
                 $select[0] = "Staff member '{$authorization->cPA_staffId}' no longer active";
                 $this->drForm_addField( new Draff_Combo( "{$recordId}_staffId" , $authorization->cPA_staffId, $select  ) );
            }
            else {
                $this->drForm_addField( new Draff_Combo( "{$recordId}_staffId" , $authorization->cPA_staffId, $appData->apd_selectMap_staff) );
            }
            $this->drForm_addField( new Draff_Combo( "{$recordId}_roleType" ,  $authorization->cPA_roleType,$appData->apd_selectMap_roles) );
            $this->drForm_addField( new Draff_Date (  "{$recordId}_expDate" , $authorization->cPA_dateExpires));
            $this->drForm_addField( new Draff_Checkbox( "{$recordId}_readOnly", 'View Only',$authorization->cPA_readOnly,1,'0','draff-checkbox-select') );
         }
    }
    else {
        foreach ($appData->apd_joiner_oneStaff as $authorizationId => $authorization) {
            $recordId = $authorization->cPA_authorizationId;
            if ( !isset($appData->apd_selectMap_courses[$authorization->cPA_courseKey]) ) {
                $select =  $appData->apd_selectMap_courses;
                $select[0] = "Course '{$authorization->cPA_courseKey}' no longer active";
                $this->drForm_addField( new Draff_Combo( "{$recordId}_courseKey" , $authorization->cPA_courseKey, $select) );
            }
            else {
                $this->drForm_addField( new Draff_Combo( "{$recordId}_courseKey" , $authorization->cPA_courseKey, $appData->apd_selectMap_courses) );
            }
            $this->drForm_addField( new Draff_Combo( "{$recordId}_roleType" ,  $authorization->cPA_roleType,$appData->apd_selectMap_roles) );
            $this->drForm_addField( new Draff_Date ( "{$recordId}_expDate" ,  $authorization->cPA_dateExpires) );
            $this->drForm_addField( new Draff_Checkbox( "{$recordId}_readOnly", 'View Only',$authorization->cPA_readOnly,'1','0','draff-checkbox-select') );
        }
    }
    $this->drForm_addField( new Draff_Button( 'subSave' , 'Save' ) );
    $this->drForm_addField( new Draff_Button( 'subCancel' , 'Cancel') );
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
    $appEmitter->zone_start('zone-content-scrollable theme-select');
    $report = new appReport_authorizationEdit_panel;
    $report->rep_outputEdit( $appData, $appGlobals, $appChain, $appEmitter );
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}  // end class

class appReport_authorization_grid {

function __construct($row=NULL) {
}

function rep_output( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-report');
    if ($appData->apd_current_viewMode=='school') {
        $this->rep_viewByCourse ($appEmitter,$appGlobals, $appData);
    }
    else {
        $this->rep_viewByStaff ($appEmitter,$appGlobals, $appData);
    }
    $appEmitter->zone_end();
}

function rep_viewByStaff ($appEmitter,$appGlobals, $appData) {
    // for staff listing
        $Column1Title = 'Staff';
        $Column2Title = 'School/Event';
        $addTitle = 'Add School/Event';
    $tableLayout = new rsmp_emitter_table_layout('ase', array(32,18,16,14,14));
    $appEmitter->table_start('draff-report',$tableLayout);
    $appEmitter->table_head_start();
    $appEmitter->krnEmit_reportTitleRow('Event Staff Authorization Report',5);
    $appEmitter->row_start();
    $appEmitter->cell_block($Column1Title,'');
    $appEmitter->cell_block($Column2Title,'');
    $appEmitter->cell_block('Role','');
    $appEmitter->cell_block('Expiration<br>Date','');
    $appEmitter->cell_block('Options');
    $appEmitter->row_end();
    $appEmitter->table_head_end();
    $staffPoint = 0;
    foreach ($appData->apd_joiner_allStaff as $staff) { // $appData->apd_joiner_allStaff->programMap as $staff) {
        $authArray = $staff->joins['auth'] ?? array();
        $authCount = count($authArray);
        $rowCount  = $authCount;
        if ($rowCount==0) {
   //         continue;  //???????????????????????????????????????????????????????????
        }
        $authPoint = 0;
        // $secItem = $secGroup->ksGrp_first;
        //$appEmitter->row_start('loc-subheader');
        $editId = 'editStaff_'.$staff->sSt_staffId; // '_' . $secItem->cPA_authorizationId  . '_' . $secItem->cPA_staffId . '_' .$secItem->cPA_schoolId . '_' . $secItem->cPA_programId. '_' . $secItem->cPA_programDow;
        $button = $appEmitter->getString_button( 'Edit', 'loc-short-button', $editId);
        // $Column1Data = $button . ' &nbsp;' . $staff->sSt_name; //  . ' ' . $button;
        $Column1Data = $appEmitter->krnEmit_button_editSubmit($staff->sSt_name, $editId);
        $Column2Data = 'school';
        $topStyle = '  border-group-top';
        $appEmitter->row_start('loc-subheader');
        if ($rowCount==0) {
            // if ( $staffPoint==$staffCount) {
            //     $topStyle .= ' border-group-bottom';
            // }
            $appEmitter->cell_block($Column1Data,'loc-staffName' . $topStyle . ' border-group-left');
            $appEmitter->cell_block('', 'border-group-right' . $topStyle,'colspan="4"');
            $appEmitter->row_end();
        }
        else {
            if ($rowCount==1) {
                $appEmitter->cell_block($Column1Data,'loc-staffName border-group-left' . $topStyle);
            }
            else {
                $appEmitter->cell_block($Column1Data,'loc-staffName border-group-left' . $topStyle,'rowspan="'.$rowCount.'"');
            }
            $first = TRUE;
            foreach ($authArray as $authorization) {
               ++$authPoint;
                // if ( ($staffPoint==$staffCount) and ($authPoint==$authCount) ) {
                //     $topStyle .= ' border-group-bottom';
                // }
                if (!$first) {
                   $appEmitter->row_start('loc-detailRow');
                   $first = FALSE;
                }
                $school = $authorization->joins['school'];
                $program = $authorization->joins['program'] ?? NULL;
                $progName = ($program==NULL) ? $school->school_nameShort : $program->prog_programName;
                //$progName = $program->prog_programName;
                $this->stdReport_detail_row ($appEmitter, $appGlobals, $appData, $authorization, $progName, $topStyle);
                $appEmitter->row_end();
                $topStyle = '';
            }
        }
    }
    $appEmitter->table_end();
}

function rep_viewByCourse ($appEmitter,$appGlobals, $appData) {
    // for staff listing
    $tableLayout = new rsmp_emitter_table_layout('ase', array(32,18,16,14,14));
    $appEmitter->table_start('draff-report',$tableLayout);
    $appEmitter->table_head_start();
    $appEmitter->krnEmit_reportTitleRow('Event Program Authorization Report',5);
    $appEmitter->row_start();
    $appEmitter->cell_block('Program/Event','');
    $appEmitter->cell_block('Staff','');
    $appEmitter->cell_block('Role','');
    $appEmitter->cell_block('Expiration<br>Date','');
    $appEmitter->cell_block('Options');
    $appEmitter->row_end();
    $appEmitter->table_head_end();
    //nn $progCount = $appData->apd_joinMap_auth->count();
    $progPoint = 0;
    //$authbyProgramBatch = new cPA_calAuth_batchByCourse;
   // $authbyProgramBatch->cAuthPr_load ($appGlobals);
    foreach ($appData->apd_joiner_allCourses as $program) {
        $school = $program->joins['school'];
        $auth = $program->joins['auth'];
        ++$progPoint;
        // $secItem = $secGroup->ksGrp_first;
        print PHP_EOL.PHP_EOL.'<tbody class="table-section">';
        $authCount = count($auth);
        $authPoint = 0;
        //$appEmitter->row_start('loc-subheader');
        $editId = 'editCourse_'.$program->prog_schoolId . '_' . $program->prog_dayOfWeek;
            // '_' . $secItem->cPA_authorizationId  . '_' . $secItem->cPA_staffId . '_' .$secItem->cPA_schoolId . '_' . $secItem->cPA_programId. '_' . $secItem->cPA_programDow;
        $button = $appEmitter->getString_button( 'Edit', 'loc-short-button', $editId);
        $Column1Data = $button . ' &nbsp;' . $program->prog_programName; //  . ' ' . $button;
        $Column1Data = $appEmitter->krnEmit_button_editSubmit($program->prog_programName, $editId);
        $Column2Data = 'school';
        $appEmitter->row_start('loc-subheader');
        $topStyle = '  border-group-top';
        if ( $authCount==0 ) {
            // if ( $progPoint==$progCount) {
            //     $topStyle .= ' border-group-bottom';
            // }
            $appEmitter->cell_block($Column1Data,'loc-staffName border-group-left' . $topStyle);
            $appEmitter->cell_block('',$topStyle . ' border-group-right','colspan="4"');
            $appEmitter->row_end();
        }
        else {
            if ($authCount==1) {
                $appEmitter->cell_block($Column1Data,'loc-staffName border-group-left' . $topStyle);
            }
            else {
                $appEmitter->cell_block($Column1Data,'loc-staffName border-group-left' . $topStyle,'rowspan="'.$authCount.'"');
            }
            $first = TRUE;
            foreach ($auth as $authorization) {
                $staff = $authorization->joins['staff'];
                ++$authPoint;
                // if ( ($progPoint==$progCount) and ($authPoint==$authCount) ) {
                //     $topStyle .= ' border-group-bottom';
                // }
                if (!$first) {
                   $appEmitter->row_start('loc-detailRow');
                   $first = FALSE;
                }
                $staffName = $staff->sSt_name;
                $this->stdReport_detail_row ($appEmitter, $appGlobals, $appData, $authorization, $staffName, $topStyle);
                $appEmitter->row_end();
                $topStyle = '';
            }
        }
        print PHP_EOL.'</tbody>'.PHP_EOL;
    }
    $appEmitter->table_end();
}

function stdReport_detail_row ($appEmitter, $appGlobals, $appData, $authorization, $col1, $topStyle) {
    $roleDesc = $appData->apd_selectMap_roles[$authorization->cPA_roleType];
    $optionDesc = ( $authorization->cPA_readOnly == 1 ) ? 'View-Only' : '';
    $dateExpires = $authorization->cPA_dateExpires === NULL ? '??' : $authorization->cPA_dateExpires ;
    $appEmitter->cell_block($col1 , $topStyle);
    $appEmitter->cell_block($roleDesc , $topStyle); // $secItem->cPA_roleTypeDesc);
    $appEmitter->cell_block($dateExpires , $topStyle); // $secItem->cPA_dateExpires);
    $appEmitter->cell_block($optionDesc , 'border-group-right' . $topStyle);
}

} // end class

class appReport_authorizationEdit_panel {

function __construct($row=NULL) {
}

function rep_edit_program( $appData, $appGlobals, $appChain, $appEmitter ) {
    foreach ($appData->apd_joiner_oneCourse as $authorizationId => $authorization) {
        $recordId = $authorization->cPA_authorizationId;
        $appEmitter->row_start('rpt-grid-row');
        $appEmitter->cell_block( "@{$recordId}_staffId");
        $appEmitter->cell_block( "@{$recordId}_roleType");
        $appEmitter->cell_block( "@{$recordId}_expDate");
        $appEmitter->cell_block( "@{$recordId}_readOnly");
        $appEmitter->row_end();
    }
}

function rep_edit_staff( $appData, $appGlobals, $appChain, $appEmitter ) {
    foreach ($appData->apd_joiner_oneStaff as $authorizationId => $authorization) {
        $recordId = $authorization->cPA_authorizationId;
        $appEmitter->row_start('rpt-grid-row');
        $appEmitter->cell_block( "@{$recordId}_courseKey");
        $appEmitter->cell_block( "@{$recordId}_roleType");
        $appEmitter->cell_block( "@{$recordId}_expDate");
        $appEmitter->cell_block( "@{$recordId}_readOnly");
        $appEmitter->row_end();
    }
}

function rep_outputEdit( $appData, $appGlobals, $appChain, $appEmitter ) {

    $tableLayout = new rsmp_emitter_table_layout('sc', array(41,20,23,40));
    $appEmitter->table_start('draff-report',$tableLayout);
    $appEmitter->table_head_start();
    $appEmitter->row_start('rpt-grid-row');
    $desc = '???134';
   // if ($appData->apd_current_viewMode=='school') {
   //     $desc = $appData->apd_edit_program->prog_programName;
   // }
   // else {
   //     $desc = $appData->apd_edit_staff->sSt_name;
   // }
    $appEmitter->row_oneCell($desc);
    $appEmitter->row_end();
    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('Program','');
    $appEmitter->cell_block('Role','');
    $appEmitter->cell_block('Expiration<br>Date','');
    $appEmitter->cell_block('Options','');
    $appEmitter->row_end();
    $appEmitter->table_head_end();
    $appEmitter->table_body_start();
    if ($appData->apd_current_isCourseMode) {
        $this->rep_edit_program( $appData, $appGlobals, $appChain, $appEmitter );
    }
    else {
        $this->rep_edit_staff( $appData, $appGlobals, $appChain, $appEmitter );
    }
    $appEmitter->table_body_end();
    $appEmitter->table_foot_start();
    $appEmitter->row_oneCell(array('@subSave','@subCancel'));
    $appEmitter->table_foot_end();
    $appEmitter->table_end();
}

} // end class

//======
//============
//==================
//========================
//= App Data
//========================
//==================
//============
//======

class application_data extends draff_appData {

// joiners
public $apd_joiner_allStaff;  // staff -> courses for each staff
public $apd_joiner_allCourses;  // course (class:school-dow or programId)
public $apd_joiner_oneCourse;  // courses for editing
public $apd_joiner_oneStaff;   // staff for editing
public $apd_joiner_authorizedActiveEdit;  // the active joiner of the ones

// used by edit
public $apd_selectMap_courses = NULL;
public $apd_selectMap_staff = NULL;
public $apd_selectMap_roles;

// status used by all
public $apd_current_viewMode;
public $apd_current_staffId;
public $apd_current_courseKey;
public $apd_current_courseProgram = NULL;    // computed from courseId
public $apd_current_courseSchoolId = NULL;   // computed from courseId
public $apd_current_courseDow = NULL;        // computed from courseId

// Maps by all


// public $apd_joinMap_auth;  //??????? is it used

// used by edit
public $apd_edit_program = NULL;
public $apd_edit_staff = NULL;

// private $apd_edit_authCount = NULL;  //??????? is it used

function __construct($pPeriodId=NULL) {
    $this->apd_fetch_selectMap_roles();
    $this->apd_selectMap_roles = $this->apd_fetch_selectMap_roles();
}

function apd_init_always ($appGlobals, $appChain) {
    $this->apd_current_viewMode = $appChain->chn_status->ses_get('#gridSubmitViewMode');
    if ($this->apd_current_viewMode == 'school') {
        $this->apd_current_isCourseMode = TRUE;
        $this->apd_current_isStaffMode  = FALSE;
    }
    else {
        $this->apd_current_isCourseMode = FALSE;
        $this->apd_current_isStaffMode  = TRUE;
    }
    $this->apd_current_staffId = $appChain->chn_status->ses_get('#editStaffId');
    $this->apd_current_courseKey = $appChain->chn_status->ses_get('#editCourseKey');
    if ( empty($this->apd_current_courseKey) ) {
        $this->apd_current_courseProgram = NULL;
        $this->apd_current_courseSchoolId = NULL;
        $this->apd_current_courseDow = NULL;
    }
    else if ( strpos($this->apd_current_courseKey,'-')===FALSE ) {
        $this->apd_current_courseProgram = $this->apd_current_courseKey;
        $this->apd_current_courseSchoolId = NULL;
        $this->apd_current_courseDow = NULL;
    }
    else {
        $ar = explode('-',$this->apd_current_courseKey);
        $this->apd_current_courseProgram = 0;
        $this->apd_current_courseSchoolId = $ar[0];
        $this->apd_current_courseDow = $ar[1];
    }
    $this->apd_selectMap_roles = $this->apd_fetch_selectMap_roles ($appGlobals);
}

function apd_fetch_joiner_allStaff($appGlobals) {
    $this->rstBatch_startDate = draff_dateIncrement( rc_getNowDate(), -180);

    $query = new draff_database_query;
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddColumns('dbRecord_staff');
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_selectAddColumns('dbRecord_programAuthorization');
    $query->rsmDbq_add( "FROM `st:staff`");
    $query->rsmDbq_add( "LEFT JOIN `ca:programauthorization` ON `cPA:@StaffId` = `sSt:StaffId`") ;
    $query->rsmDbq_add( "LEFT JOIN `pr:program` ON (`pPr:ProgramId` = `cPA:@ProgramId`) ");
    $query->rsmDbq_add( "  AND  (  `pPr:DateClassLast` >= '{$this->rstBatch_startDate}' )" );
    $query->rsmDbq_add( "  AND ( `pPr:ProgramType` IN ('2','3') )" );
    $query->rsmDbq_add( "LEFT JOIN `pr:school` ON `pSc:SchoolId` = `cPA:@SchoolId`");

    $query->rsmDbq_add( "UNION");
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddColumns('dbRecord_staff');
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_selectAddColumns('dbRecord_programAuthorization');

    $query->rsmDbq_add( "FROM `st:staff`");
    $query->rsmDbq_add( "LEFT JOIN `ca:programauthorization` ON `cPA:@StaffId` = `sSt:StaffId`" );
    $query->rsmDbq_add( "LEFT JOIN `pr:program` ON ");
    $query->rsmDbq_add( "   (  `pPr:DateClassLast` >= '{$this->rstBatch_startDate}' )" );
    $query->rsmDbq_add( "  AND ( `pPr:ProgramType` = 1 )");
    $query->rsmDbq_add( "  AND (`cPA:@SchoolId` = `pPr:@SchoolId`) AND (`cPA:ProgramDow` = `pPr:DayOfWeek`)");
    $query->rsmDbq_add( "LEFT JOIN `pr:school` ON `pSc:SchoolId` = `cPA:@SchoolId`");

    $query->rsmDbq_add( "GROUP BY  `pPr:@SchoolId`, `pPr:DayOfWeek` ");
    $query->rsmDbq_add(  "ORDER BY `sSt:FirstName` , `sSt:LastName`" );
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);

    $joiner = new draff_database_joiner;
    foreach ($result as $row) {
        $staff   = $joiner->rsmDbj_addDbRecord_asRoot( 'dbRecord_staff', $row, 'sSt:StaffId'  );
        $auth    = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_programAuthorization', $staff, 'auth', $row  );
        $school  = $joiner->rsmDbj_addDbRecord_asShared( 'dbRecord_school_base', $auth, 'school', $row  );
        $prog    = $joiner->rsmDbj_addDbRecord_asShared( 'dbRecord_program', $auth, 'program', $row  );
    }
    return $joiner;
}

function apd_fetch_joiner_allCourses ($appGlobals , $programId=NULL, $schoolId = NULL, $dow=NULL, $emptyCount = 0) {
    $joinMap_courses = array();
    $startDate = draff_dateIncrement( rc_getNowDate(), -180);
    $filters = '';
    if (!empty($schoolId)) {
         $filters .=  "    AND (`pPr:@SchoolId` = '{$schoolId}')";
    }
    if (!empty($programId)) {
        $filters .=   "    AND (`pPr:ProgramId` = '{$programId}')";
    }
    if (!empty($dow)) {
        $filters .=   "    AND (`pPr:DayOfWeek` = '{$dow}')";
    }
    $query = new draff_database_query;
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_programAuthorization');
    $query->rsmDbq_selectAddColumns('dbRecord_staff');
    $query->rsmDbq_selectAddString ( dbRecord_program::SQL_COURSEID);
    //$unionSelect = $queryCmd->draff_sql_getString();

    //$queryCmd = new draff_sql_command;
    //$queryCmd->draff_sql_selectStart();
    //dbRecord_staff::sSt_appendSelect($queryCmd);
    //dbRecord_program::pPr_appendSelect($queryCmd);
    //dbRecord_programAuthorization::cPA_appendSelect($queryCmd);
    //$sql = array();
    //$sql[] = $queryCmd->draff_sql_getString();

    if (empty($dow)) {

       // $sql[] = "SELECT ".$fieldList;
        $query->rsmDbq_add( "FROM `pr:program`");
        $query->rsmDbq_add( "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
        $query->rsmDbq_add( "LEFT JOIN `ca:programauthorization` ON `cPA:@ProgramId` = `pPr:ProgramId`" );
        $query->rsmDbq_add( "LEFT JOIN `st:staff` ON `sSt:StaffId` = `cPA:@StaffId`" );
        $query->rsmDbq_add( "WHERE ( `pPr:DateClassLast` >= '{$startDate}' )");
        $query->rsmDbq_add( "    AND (`pPr:ProgramType` IN ('2','3'))");
        $query->rsmDbq_add( $filters);
        $query->rsmDbq_add( "UNION ");
    }
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_programAuthorization');
    $query->rsmDbq_selectAddColumns('dbRecord_staff');
    $query->rsmDbq_selectAddString ( dbRecord_program::SQL_COURSEID);
   // $sql[] = "SELECT ".$fieldList;
//    $sql[] = ", concat(`pPr:@SchoolId`,'-',`pPr:DayOfWeek`) as programkey";
//    $sql[] = ", GROUP_CONCAT(DISTINCT `pPr:DayOfWeek` ORDER BY `pPr:DayOfWeek` SEPARATOR ',') as dowList";
    $query->rsmDbq_add(  "FROM `pr:program`");
    $query->rsmDbq_add(  "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
    $query->rsmDbq_add(  "LEFT JOIN `ca:programauthorization` ON (`cPA:@SchoolId` = `pPr:@SchoolId`) AND (`cPA:ProgramDow` = `pPr:DayOfWeek`)" );
    $query->rsmDbq_add(  "LEFT JOIN `st:staff` ON `sSt:StaffId` = `cPA:@StaffId`" );
    $query->rsmDbq_add(  "WHERE ( `pPr:DateClassLast` >= '{$startDate}' )" );
    $query->rsmDbq_add(  "    AND (`pPr:ProgramType` = '1')" );
    $query->rsmDbq_add( $filters );
    $query->rsmDbq_add(  "GROUP BY  `pPr:@SchoolId`, `pPr:DayOfWeek` , `cPA:@StaffId`");
    $query->rsmDbq_add(  "ORDER BY `pPr:ProgramType` , `pSc:NameShort`, `pPr:DayOfWeek`" );
   // $query->rsmDbq_add(  "ORDER BY `pPr:ProgramType` , `pPr:DayOfWeek`" );
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);

    $joiner = new draff_database_joiner;
    foreach ($result as $row) {
         // $programId = $row['pPr:ProgramId'];
         // $type      = $row['pPr:ProgramType'];
         // $schoolId  = $row['pPr:@SchoolId'];
         // $dow       = $row['pPr:DayOfWeek'];
         // $courseKey = $type==1 ? ($schoolId . '-' . $dow) : $programId;
         $program = $joiner->rsmDbj_addDbRecord_asRoot( 'dbRecord_program', $row, 'CourseId'  );
         $auth    = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_programAuthorization', $program, 'auth', $row  );
         $school  = $joiner->rsmDbj_addDbRecord_asShared( 'dbRecord_school_base', $program, 'school', $row );
         $staff   = $joiner->rsmDbj_addDbRecord_asShared('dbRecord_staff' , $auth, 'staff', $row );
    }

    foreach($result as $row) {
        $programId = $row['pPr:ProgramId'];
        $type      = $row['pPr:ProgramType'];
        $schoolId  = $row['pPr:@SchoolId'];
        $dow       = $row['pPr:DayOfWeek'];
        $courseKey = $type==1 ? ($schoolId . '-' . $dow) : $programId;
        $authId = $row['cPA:ProgramAuthorizationId'];
        if ($courseKey != $curProgramKey) {
            $curProgram = new dbRecord_program($row);
            $curAuthMap = $this->draff_batchCreateItemBatch( 'authMap', $courseKey );
            $joiner[$courseKey] = $curProgram;
            $curProgramKey = $courseKey;
        }
        if ($authId !=NULL) {
            $auth = new dbRecord_programAuthorization($row);
            $curAuthMap[$authId] = $auth;
            $staff = new dbRecord_staff($row);
            $joiner[$staff->sSt_staffId]  = $staff;       }
    }
    if ($emptyCount>= 1) {
        for ($i=1; $i<=5; ++$i) {
            $curProgram = new dbRecord_program; // empty record
            $joiner['@new-' . $i] = $curProgram;
        }
    }
    return $joiner;
}

function apd_fetch_joiner_oneCourse($appGlobals, $courseKey, $emptyCount) {
    $joinMap_courses = array();
    $startDate = draff_dateIncrement( rc_getNowDate(), -180);
    $query = new draff_database_query;
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_programAuthorization');
    $query->rsmDbq_selectAddColumns('dbRecord_staff');
    //$query->rsmDbq_selectAddString ( dbRecord_program::SQL_COURSEID);
    $query->rsmDbq_add( "FROM `ca:programauthorization`") ;
    $query->rsmDbq_add( "LEFT JOIN  `st:staff` ON `sSt:StaffId`=`cPA:@StaffId`");
    $query->rsmDbq_add( "LEFT JOIN `pr:program` ON (`pPr:ProgramId` = `cPA:@ProgramId`) ");
    //$query->rsmDbq_add( "  AND  (  `pPr:DateClassLast` >= '{$this->rstBatch_startDate}' )" );
    //$query->rsmDbq_add( "  AND ( `pPr:ProgramType` IN ('2','3') )" );
    $query->rsmDbq_add( "LEFT JOIN `pr:school` ON `pSc:SchoolId` = `cPA:@SchoolId`");
    $query->rsmDbq_add("WHERE (`cPA:CourseKey` = '{$courseKey}')" );  //???? should be binded
    $query->rsmDbq_add(  "ORDER BY `sSt:FirstName` , `sSt:LastName`" );
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
    $joiner = new draff_database_joiner;
    foreach ($result as $row) {
         $auth    = $joiner->rsmDbj_addDbRecord_asRoot('dbRecord_programAuthorization' ,  $row, 'cPA:ProgramAuthorizationId' );
         $staff   = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_staff', $auth, 'staff', $row,  'sSt:StaffId'  );
         $school  = $joiner->rsmDbj_addDbRecord_asShared( 'dbRecord_school_base', $auth, 'school',$row,  'cPA:@SchoolId' );
         $prog    = $joiner->rsmDbj_addDbRecord_asShared( 'dbRecord_program', $auth, 'program', $row,  'pPr:ProgramId'  );
    }
    if ($emptyCount>= 1) {
        for ($i=1; $i<=5; ++$i) {
            $curAuth = new dbRecord_programAuthorization; // empty record
            $auth->cPA_authorizationId = -$i;
            $joiner[$curAuth->cPA_authorizationId] = $curAuth;
        }
    }
    return $joiner;
}

function apd_fetch_joiner_oneStaff($appGlobals, $staffId=NULL, $emptyCount=0) {
    $authMapForStaff = array();
    $this->rstBatch_startDate = draff_dateIncrement( rc_getNowDate(), -180);

    $query = new draff_database_query;
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddColumns('dbRecord_staff');
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_selectAddColumns('dbRecord_programAuthorization');
    //????? from auth is clearer
    $query->rsmDbq_add( "FROM `st:staff`");
    //??????????? if class than is program relevant ?????? (school is used)
    $query->rsmDbq_add( "LEFT JOIN `ca:programauthorization` ON `cPA:@StaffId` = `sSt:StaffId`") ;
    $query->rsmDbq_add( "LEFT JOIN `pr:program` ON (`pPr:ProgramId` = `cPA:@ProgramId`) ");
    $query->rsmDbq_add( "  AND  (  `pPr:DateClassLast` >= '{$this->rstBatch_startDate}' )" );
    $query->rsmDbq_add( "  AND ( `pPr:ProgramType` IN ('2','3') )" );
    $query->rsmDbq_add( "LEFT JOIN `pr:school` ON `pSc:SchoolId` = `cPA:@SchoolId`");
    $query->rsmDbq_add("WHERE (`sSt:StaffId` = '{$staffId}')" );  //???? should be binded
    $query->rsmDbq_add(  "ORDER BY `sSt:FirstName` , `sSt:LastName`" );
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);

    $joiner = new draff_database_joiner;
    foreach ($result as $row) {
         $auth    = $joiner->rsmDbj_addDbRecord_asRoot('dbRecord_programAuthorization', $row,'cPA:ProgramAuthorizationId'   );
         $staff   = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_staff', $auth, 'staff', $row,  'sSt:StaffId'  );
         //$staff   = $joiner->rsmDbj_addDbRecord_asRoot('dbRecord_staff', $row, 'sSt:StaffId'  );
         //$auth    = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_programAuthorization', $staff, 'auth', $row,  'cPA:ProgramAuthorizationId' );
         $school  = $joiner->rsmDbj_addDbRecord_asShared( 'dbRecord_school_base', $auth, 'school', $row,  'cPA:@SchoolId'  );
         $prog    = $joiner->rsmDbj_addDbRecord_asShared( 'dbRecord_program', $auth, 'program', $row,  'pPr:ProgramId'  );
    }

    if ( ($staffId!==NULL) and  ($emptyCount!=0) ) {
      for ($i=1; $i<=5; ++$i) {
          //????????????????????????? is structure correct for new joiner
          $auth = new dbRecord_programAuthorization(); // empty record
          $auth->cPA_authorizationId = -$i;
          $joiner['new-' . $i] = $auth;
      }
    }
    return $joiner;
}

function apd_fetch_selectMap_roles() {
    $roles = array();
    $roles [0] = 'Not Specified';
    $roles [1] = 'Site Leader';
    $roles [2] = 'Head Coach';
    $roles [3] = 'Coach';
    $roles [4] = 'Parent Helper';
    $roles [5] = 'Assistant Site Leader';
    $roles [6] = 'Acting SIte Leader';
    $roles [7] = 'Acting Head Coach';
    $roles [99] = 'Other';
    return $roles;
}

function apd_fetch_selectMap_courses ($appGlobals) {
    $courseSelects = array(0=>'( Empty row - do not save this row )');
    $startDate = draff_dateIncrement( rc_getNowDate(), -180);
    $query = new draff_database_query;
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_add( "FROM `pr:program`");
    $query->rsmDbq_add( "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
    $query->rsmDbq_add( "WHERE ( `pPr:DateClassLast` >= '{$startDate}' )");
    $query->rsmDbq_add( "    AND (`pPr:ProgramType` = '1')");   //???????  hidden???
    $query->rsmDbq_add( "GROUP BY  `pPr:@SchoolId`, `pPr:DayOfWeek`");
    $query->rsmDbq_add( "UNION");
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_add( "FROM `pr:program`");
    $query->rsmDbq_add( "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
    $query->rsmDbq_add( "WHERE ( `pPr:DateClassLast` >= '{$startDate}' )");
    $query->rsmDbq_add( "    AND (`pPr:ProgramType` IN ('2','3'))");   //???????  hidden???
    $query->rsmDbq_add( "ORDER BY `pPr:ProgramType` , `pSc:NameShort`, `pPr:DayOfWeek`");
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
    foreach ($result as $row) {
        $programId = $row['pPr:ProgramId'];
        $type      = $row['pPr:ProgramType'];
        $schoolId  = $row['pPr:@SchoolId'];
        $dow       = $row['pPr:DayOfWeek'];
        $courseKey = $type==1 ? ($schoolId . '-' . $dow) : $programId;
        $newProgram = new dbRecord_program($row);
        $courseSelects[$courseKey] = $newProgram->prog_programName;
    }
    //foreach ( $courseBatch as $courseKey => $program) {
    //    $this->apd_selectMap_courses[$courseKey] = $program->prog_programName;
    //}
    return $courseSelects;
}

function apd_fetch_selectMap_staff ($appGlobals, $staffId=NULL, $emptyCount=0) {
    $selectMap_staff = kcm_fetch_selectMap_staff($appGlobals, TRUE );
    return $selectMap_staff;
}

function apd_validate_authorizationJoin($appGlobals, $appChain) {
    $usedStaff = array();
    $usedCourse = array();
    foreach ($this->apd_joiner_authorizedActiveEdit as $authorizeId => $authorize) {
        $usedStaff[]  = $authorize->cPA_staffId;
        $usedCourse[] = $authorize->cPA_courseKey;;
    }
    $usedStaff  = array_count_values($usedStaff);
    $usedCourse = array_count_values($usedCourse);
    foreach ($this->apd_joiner_authorizedActiveEdit as $authorizeId => $authorize) {
        $recordId = $authorize->cPA_authorizationId;
        $courseKey = $authorize->cPA_courseKey;
        if ($this->apd_current_isCourseMode) {
            if ( empty($authorize->cPA_staffId) ) {
                continue;
            }
            if ( empty($authorize->cPA_courseKey)  or ($authorize->cPA_courseKey==0) ) {
                $authorize->cPA_courseKey = $appData->apd_current_courseKey;
            }
            if ($usedStaff[$authorize->cPA_staffId] > 1) {
                $appChain->chn_message_field($recordId,'staffId','Duplicate staff not allowed');
            }
        }
        else {
            if ( empty($authorize->cPA_courseKey)  or ($authorize->cPA_courseKey==0) ) {
                continue;
            }
            if ( empty($authorize->cPA_staffId)  ) {
                $authorize->cPA_staffId = $appData->apd_current_staffId;
            }
            if ($usedCourse[$authorize->cPA_courseKey] > 1) {
                $appChain->chn_message_field($recordId,'courseKey','Duplicate courses not allowed');
            }
        }
        if ( empty($authorize->cPA_roleType) ) {
            $appChain->chn_message_field($recordId,'roleType','Role Key is required');
        }
        if ( strlen($authorize->cPA_dateExpires) != 10  ) {
            $appChain->chn_message_field($recordId,'expDate','Invalid Date');
        }
        if ( $authorize->cPA_dateExpires < $appGlobals->gb_getNow()  ) {
            $appChain->chn_message_field($recordId,'expDate','Invalid Date');
        }
        $ar = explode( '-' , $authorize->cPA_courseKey);
        if ( count($ar)==1 ) {
            $program = $authorize->joins['program'];
            $authorize->cPA_programId = $ar[0];
            $authorize->cPA_schoolId   = $program->prog_schoolId;
            $authorize->cPA_programDow = $program->prog_dayOfWeek;
            $authorize->cPA_programType = $program->prog_progType;
       }
        else {
            $authorize->cPA_programId = NULL;  // must be NULL and not zero for join
            $authorize->cPA_schoolId   = $ar[0];
            $authorize->cPA_programDow = $ar[1];
            $authorize->cPA_programType = 1;
        }
    }
}

function apd_save_authorizationJoin($appGlobals, $appChain) {
    foreach ($this->apd_joiner_authorizedActiveEdit as $authorizeId => $authorize) {
        $recordId = $authorize->cPA_authorizationId;
        if ($authorize->cPA_authorizationId >= 1) {
             if ( ($authorize->cPA_courseKey==0) or ($authorize->cPA_staffId==0) ) {
                 $appGlobals->gb_pdo->rsmDbe_deleteRecord('dbRecord_programAuthorization', $authorize->cPA_authorizationId);
                 continue;
             }
        }
        if ($authorize->cPA_courseKey==0) {
            continue;
        }
        $row = array();
        $row['cPA:ProgramAuthorizationId'] = $authorize->cPA_authorizationId;
        $row['cPA:CourseKey'] = $authorize->cPA_courseKey;
        $row['cPA:@StaffId'] = $authorize->cPA_staffId;
        $row['cPA:@SchoolId'] = $authorize->cPA_schoolId;
        $row['cPA:@ProgramId'] = $authorize->cPA_programId;
        $row['cPA:ProgramType'] = $authorize->cPA_programType;
        $row['cPA:ProgramDow'] = $authorize->cPA_programDow;
        $row['cPA:RoleType'] = $authorize->cPA_roleType;
        $row['cPA:DateExpires'] = $authorize->cPA_dateExpires;
        $row['cPA:ReadOnly'] = $authorize->cPA_readOnly;  //??????
        $appGlobals->gb_pdo->rsmDbe_writeRecord('dbRecord_programAuthorization', $row);
    }
}

function apd_load_authorizationJoin($appGlobals, $appChain) {
    if ($this->apd_current_isCourseMode) {
        $this->apd_joiner_oneCourse = $this->apd_fetch_joiner_oneCourse($appGlobals, $this->apd_current_courseKey, 5);
        $this->apd_joiner_authorizedActiveEdit = $this->apd_joiner_oneCourse;
        $this->apd_selectMap_staff = $this->apd_fetch_selectMap_staff($appGlobals);
    }
    else {
        $this->apd_joiner_oneStaff =  $this->apd_fetch_joiner_oneStaff($appGlobals, $this->apd_current_staffId, 5);
        $this->apd_joiner_authorizedActiveEdit = $this->apd_joiner_oneStaff;
     }
    $this->apd_selectMap_courses  =  $this->apd_fetch_selectMap_courses ($appGlobals);
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();


$appGlobals = new kcmAdmin_globals();
$appGlobals->gb_forceLogin ();
$appData = new application_data;

$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );
$appChain->chn_form_register(1,'appForm_courseAuthorization_grid');
$appChain->chn_form_register(2,'appForm_courseAuthorization_edit');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>