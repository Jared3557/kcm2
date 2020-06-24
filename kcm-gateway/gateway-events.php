<?php

// gateway-events.php

ob_start();  // output buffering (needed for redirects, content changes)

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
//include_once( '../kcm-kernel/kernel-kcm1-kcm2-common.inc.php' );  //????

include_once( 'gateway-system-globals.inc.php');
include_once( 'gateway-system-kcm1-conversion.inc.php');

include_once( '../../krLib-schedule-data.inc.php' );
include_once( '../../krLib-schedule-engine.inc.php' );
include_once( '../../krLib-schedule-output.inc.php' );
include_once( '../../kcm-page-Engine.inc.php' );
include_once( '../../kcm-libKcmFunctions.inc.php' );
include_once( '../../kcm-libAsString.inc.php' );

CONST DRFFORM_EVENTS_SELECT    = 1;
CONST DRFFORM_SEMESTERS_SELECT = 2;
CONST DRFFORM_PROGRAM_EDIT    = 3;
CONST DRFFORM_SMREPORT_EDIT   = 4;

//=============
//==============================
//=============================================

class appForm_events_select extends Draff_Form {

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    if ($appChain->chn_submit[0]=='progId' ) {
        $appChain->chn_status->ses_set('#editProgramId',$appChain->chn_submit[1]);
        // could save record desc here (name with duplicate checking)
        $appChain->chn_form_launch(DRFFORM_PROGRAM_EDIT);
    }
    switch ($appChain->chn_submit[0]) {
        case 'category':
            $appChain->chn_status->ses_set('#eventCategory',$appChain->chn_submit[1]);
            $appChain->chn_form_launch(DRFFORM_EVENTS_SELECT);
            break;
        case 'sbtSemesters':
            $appChain->chn_form_launch(DRFFORM_SEMESTERS_SELECT);
            break;
    }
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_init_always( $appGlobals, $appChain );
    if ($appChain->chn_url_rsmMode== '2') {
        $appData->apd_events_joinerAllPrograms = $appData->apd_events_fetchJoinerAllEvents( $appGlobals );
        $appData->apd_events_joinerPrograms = $appData->apd_events_joinerAllPrograms;
    }
    else {
        $appData->apd_events_joinerMyPrograms = $appData->apd_events_fetchJoinerMyEvents( $appGlobals, $appChain );
        $appData->apd_events_joinerPrograms = $appData->apd_events_joinerMyPrograms;
    }
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    foreach ($appData->apd_events_joinerPrograms as $key => $program) {
        $id = 'progId_' . $key;
        $desc = $program->prog_programName;
        if (  $appData->apd_events_catOfType[$program->prog_progType] == $appData->apd_events_catToView ) {
            $this->drForm_addField( new Draff_Button(  $id , $desc ) );
        }
    }
    foreach ($appData->apd_events_catDesc as $index => $desc) {
        $catCount = ' (' . $appData->apd_events_catCount[$index] . ')';
        $this->drForm_addField( new Draff_Button(  'category_' . $index, $desc . $catCount ) );
    }
    $this->drForm_addField( new Draff_Button(  'sbtSemesters' , 'Change' ) );
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-select' );
    $appEmitter->set_title('My Events');
    $appEmitter->set_menu_standard($appChain, $appGlobals);
    $menuKey =($appChain->chn_url_rsmMode== '2') ? 'ls-ae' : 'ls-me';
    $appEmitter->set_menu_customize( $appChain, $appGlobals, $menuKey  );
//    $appEmitter->set_title('My Events');
//    $appEmitter->set_menu_standard($appChain, $appGlobals);
//    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
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
   $appEmitter->zone_start('zone-content-header theme-header');
    print PHP_EOL.'<div style="float:left;display:table-cell;margin-left:10px;text-align:center;margin-right:20px;">';
    if ($appChain->chn_url_rsmMode== '2') {
        if ($appData->apd_semesters_year>1) {
            $s = '<h1>'.rc_getSemesterNameFromCode($appData->apd_semesters_semesterCode) . ' - ' . $appData->apd_semesters_year.'</h1>';
            $appEmitter->content_block($s);
            $appEmitter->content_block('@sbtSemesters');
        }
        else {
            $s = '<h1>Current Semester(s)</h1>';
            $appEmitter->content_block($s);
            $appEmitter->content_block('@sbtSemesters');
        }
    }
    print PHP_EOL.'</div>';
    foreach ($appData->apd_events_catDesc as $index => $desc) {
        $appEmitter->content_block( '@category_' . $index );
    }
   $appEmitter->zone_end('zone-content-header theme-select');
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-select');
   //foreach ($appData->apd_events_joinerMyPrograms as $program) {
   //     $key = '@myEventId_' . $program->prog_programId;
   //     $appEmitter->row_start('rpt-grid-row');
   //     $appEmitter->content_block($key);
   //     $appEmitter->row_end();
   // }
    foreach ($appData->apd_events_joinerPrograms as $key => $program) {
        $id = 'progId_' . $key;
        if (  $appData->apd_events_catOfType[$program->prog_progType] == $appData->apd_events_catToView ) {
            $appEmitter->content_field($id);
        }
    }
   $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

class appForm_semesters_select extends Draff_Form {

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    if ($appChain->chn_submit[0]=='cancel' ) {
        $appChain->chn_form_launch(DRFFORM_EVENTS_SELECT);
    }
    if ($appChain->chn_submit[0]=='sbtSemesterKey') {
        $eventCategoryType = $appChain->chn_submit[2]=='10' ? 2 : 1;
        $appChain->chn_status->ses_set('#eventYear',$appChain->chn_submit[1]);
        $appChain->chn_status->ses_set('#eventSemesterCode',$appChain->chn_submit[2]);
        $appChain->chn_status->ses_set('#eventCategory',$eventCategoryType);
        $appChain->chn_form_launch(DRFFORM_EVENTS_SELECT);
    //     $appChain->chn_url_redirect(array('rsMode'=>$appChain->chn_submit[0],'rsForm'=>DRFFORM_EVENTS_SELECT));
    }
    kernel_processBannerSubmits( $appGlobals, $appChain, $appChain->chn_submit );
    //$appChain->chn_setStatus($appChain->chn_submit[0]);
    if ($appChain->chn_submit[0]=='@current') {
        $appChain->chn_submit[0] = '0000-00';
    }
    $appChain->chn_url_redirect(array('rsMode'=>$appChain->chn_submit[0],'rsForm'=>DRFFORM_SEMESTERS_SELECT));
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_init_always( $appGlobals, $appChain );
    $appData->apd_semesters_selectMap = $appData->apd_semesters_FetchSelectMap( $appGlobals );
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-panel' );
    $appEmitter->set_title('My Events - All Semesters');
    $appEmitter->set_menu_standard($appChain, $appGlobals);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
    //$appEmitter->set_title('My Events - All Semesters');
    //$appEmitter->set_menu_standard($appChain, $appGlobals);
    //$appEmitter->set_menu_customize( $appChain, $appGlobals  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $this->drForm_addField( new Draff_Button(  'sbtSemesterKey_0000_00' , 'Current Semester(s)' ) );
//    foreach ($appData->apd_semesters_selectMap as $key => $desc) {
//           $this->drForm_addField( new Draff_Button(  'sbtSemesterKey_'. $key , $desc ) );
//    }
    foreach ($appData->apd_semesters_selectMap as $key => $desc) {
        $this->drForm_addField( new Draff_Button( ['sbtSemesterKey', $key], $desc ) );
    }
    $this->drForm_addField( new Draff_Button( 'cancel' , 'Cancel') );
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

    $appEmitter->table_start('draff-report',4);

    $appEmitter->table_head_start();
    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('School Year');
    $appEmitter->cell_block('Semester');
    $appEmitter->row_end();
    $appEmitter->table_head_end();

    $appEmitter->table_body_start('');

    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('Current');
    $appEmitter->cell_block(array(' &nbsp; &nbsp; &nbsp; &nbsp; ','@sbtSemesterKey_0000_00'));
    $appEmitter->row_end();

    $curYear = 0;
    foreach ($appData->apd_semesters_selectMap as $key => $desc) {
        $newYear = substr($key,0,4);
        if ($newYear != $curYear) {
            if ($curYear==0) {
                $appEmitter->cell_end();
                $appEmitter->row_end();
            }
            $appEmitter->row_start('rpt-grid-row');
            $curYear = $newYear;
            $appEmitter->cell_block($curYear);
            $appEmitter->cell_start();
        }
        $appEmitter->content_field('sbtSemesterKey_'. $key);
    }
    if ($curYear!=0) {
        $appEmitter->cell_end();
        $appEmitter->row_end();
    }
    $appEmitter->table_body_end();
    $appEmitter->table_end();
    $appEmitter->zone_end();
 }

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-footer theme-select');
    $appEmitter->content_block( '@cancel' );
    $appEmitter->zone_end();
}

} // end class

//=============
//==============================
//=============================================

class appForm_program_edit extends Draff_Form {

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    if ($appChain->chn_submit[0]=='cancel' ) {
        $appChain->chn_form_launch(DRFFORM_EVENTS_SELECT);
    }
    kernel_processBannerSubmits( $appGlobals, $appChain );
    $appData->apd_init_always( $appGlobals, $appChain );
    if ( $appChain->chn_submit[0] == 'kcm2') {
        $periodId = $appData->apd_get_minPeriod($appGlobals, $appChain->chn_submit[1]);
        // wrong url
        $appChain->chn_url_redirect('../kcm-roster/roster-results-games.php',  FALSE, array('PrId'=>$appChain->chn_submit[1],'PeId'=>$periodId,'rsmMode'=>'3'));
        return;
    }
    if ( $appChain->chn_submit[0] == 'kcm1') {
        $periodId = $appData->apd_get_minPeriod($appGlobals, $appChain->chn_submit[1]);
        $appChain->chn_url_redirect('../../kcm-periodHome.php',  FALSE, array('PrId'=>$appChain->chn_submit[1],'PeId'=>$periodId));
         return;
    }
    if ( $appChain->chn_submit[0] == 'smReport') {  // calDate Id
        $appChain->chn_status->ses_set('#smCalDateId',$appChain->chn_submit[1]);
        $appChain->chn_form_launch(DRFFORM_SMREPORT_EDIT);
        return;
    }
    if ( $appChain->chn_submit[0] == 'progId') {  // from semesters
        $appChain->chn_status->ses_set('#editProgramId',$appChain->chn_submit[1]);
        $appChain->chn_form_launch(DRFFORM_PROGRAM_EDIT);
        return;
    }
    if ( $appChain->chn_submit[0] == '@allEvents') {
        $this->step_setShared('#selectMode','all');
        $appChain->chn_form_launch(DRFFORM_EVENTS_SELECT);
        return;
    }
    if ( $appChain->chn_submit[0] == '@allSemesters') {
        $this->step_setShared('#selectMode','today');
        $appChain->chn_form_launch(DRFFORM_SEMESTERS_SELECT);
        return;
    }
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_init_always( $appGlobals, $appChain );
    $appData->apd_program_joiner = $appData->app_program_fetchJoiner( $appGlobals, $appChain );
    $appData->apd_program_record = $appData->apd_program_joiner->rsmDbj_getRootRecord();
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-panel' );
    $appEmitter->set_title('My Events');
    $appEmitter->set_menu_standard($appChain, $appGlobals);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $program = $appData->apd_program_record;
    $this->drForm_addField( new Draff_Button(  'kcm2_'.$program->prog_programId , 'KCM-2' ) );
    $this->drForm_addField( new Draff_Button(  'kcm1_'.$program->prog_programId , 'KCM-1' ) );
    $calDateArray = $program->joins['calDate'];
    foreach ($calDateArray as $key => $calDate) {
    //    // $key = $program->cale_smReportRecordId[$i];
        $desc = draff_dateAsString($calDate->cSD_classDate, 'F j' );
        if ($calDate->cSD_isHoliday) {
            $desc .= ' (holiday)';
        }
        $butKey = 'smReport_' . $key;
        $this->drForm_addField( new Draff_Button(  $butKey, $desc ) );
    }
    if ($program->prog_progType == 1) {
        $semesterArray = $program->joins['semesters'];
        foreach ($semesterArray as $key => $prog) {
            $semYear = $prog->prog_schoolYear;
            $semCode = $prog->prog_semester;
            $semDescYear = ($semCode==40) ? ($semYear+1) : $semYear;
            $desc = rc_getSemesterNameFromCode($semCode) . ' - ' . $semDescYear;
            $butKey = 'progId_' . $key;
            $this->drForm_addField( new Draff_Button(  $butKey, $desc ) );
        }
    }
    $this->drForm_addField( new Draff_Button( 'cancel' , 'Cancel') );
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
    $appEmitter->zone_start('zone-content-header theme-header');
    print $appData->apd_program_record->prog_programName;
    $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-select');
    $appEmitter->table_start('draff-report',4);

    $appEmitter->table_head_start();
    $appEmitter->row_start('rpt-grid-row');
    $sem = rc_getSemesterAndYearNameFromYearAndCodeList( $appData->apd_program_record->prog_schoolYear, $appData->apd_program_record->prog_semester );
    $desc = $appData->apd_program_record->prog_programName . ' - ' . $sem;
    $appEmitter->cell_block($desc,'draff-large-title','colspan="2"');
    $appEmitter->row_end();

    $appEmitter->table_head_end();

    $appEmitter->table_body_start('');

    $program = $appData->apd_program_record;
    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('KCM');
    $appEmitter->cell_block(array('@kcm2_'.$program->prog_programId,'@kcm1_'.$program->prog_programId));
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('Site Manager<br>Reports');
    $appEmitter->cell_start();

    $calDateArray = $program->joins['calDate'];
    foreach ($calDateArray as $key => $calDate) {
        $butKey = '@smReport_' . $key;
        // $key = $program->cale_smReportRecordId[$i];
        $appEmitter->content_block( $butKey);
    }
    $appEmitter->cell_end();
    //$appEmitter->cell_block($key3);
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('Select another semester');
    $appEmitter->cell_start();
    if ($program->prog_progType == 1) {
        $semesterArray = $program->joins['semesters'];
        foreach ($semesterArray as $key => $prog) {
            $butKey = '@progId_' . $key;
            $appEmitter->content_block( $butKey);
        }
    }
    $appEmitter->cell_end();
    $appEmitter->row_end();

    $appEmitter->table_body_end();
    $appEmitter->table_end();
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-footer theme-select');
    $appEmitter->content_block( '@cancel' );
    $appEmitter->zone_end();
}

} // end class

class appForm_smReport_edit extends Draff_Form {

public $sem_semesters;

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    $command = $appChain->chn_submit[0];
    if ($command=='cancel' ) {
        $appChain->chn_form_launch(DRFFORM_PROGRAM_EDIT);
    }
    kernel_processBannerSubmits( $appGlobals, $appChain );
    if ( ( $command == 'submitCom') or ($command == 'submitInc') ) {
        $this->drForm_initData( $appData, $appGlobals, $appChain );
        // need to validate ??
        $appData->apd_smReport_save( $appGlobals, $appChain );
        $appChain->chn_form_launch(DRFFORM_PROGRAM_EDIT);
    }

// next line for testing
// $appChain->chn_form_launch(DRFFORM_SMREPORT_EDIT);

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_init_always( $appGlobals, $appChain );
    $appData->apd_smReport_calDateJoiner = $appData->app_smReport_fetchJoinerCalDate( $appGlobals, $appChain );
    $appData->apd_smReport_CalDateRecord = $appData->apd_smReport_calDateJoiner->rsmDbj_getRootRecord();
    $calDate = $appData->apd_smReport_CalDateRecord;
    $appChain->chn_readPostedField( $calDate->cSD_notes          , 'smrHeadNotes');
    $appChain->chn_readPostedField( $calDate->cSD_notesActivities, 'smrHeadAct'  );
    $appChain->chn_readPostedField( $calDate->cSD_notesIncidents , 'smrHeadInc' );
    $staffArray = $calDate->joins['calStaff'];
    foreach($staffArray as $key => $calStaff) {
        $staffId = $calStaff->cSS_staffId;
        $appChain->chn_readPostedField( $calStaff->cSS_timeArrived   , 'smrStaffArrived' , $staffId);
        $appChain->chn_readPostedField( $calStaff->cSS_timeLeft      , 'smrStaffLeft'    , $staffId);
        $appChain->chn_readPostedField( $calStaff->cSS_timeAdjustment, 'smrStaffAdjust'  , $staffId);
        $appChain->chn_readPostedField( $calStaff->cSS_hadBadge      , 'smrStaffBadge'   , $staffId);
        $appChain->chn_readPostedField( $calStaff->cSS_hadEquipment  , 'smrStaffEquip'   , $staffId);
        $appChain->chn_readPostedField( $calStaff->cSS_Notes         , 'cSS_Notes'       , $staffId);  //???????
    }
    $appData->apd_smReport_editReport = new report_smReportEdit;
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-panel' );
    $appEmitter->set_title('My Events - All Semesters');
    $appEmitter->set_menu_standard($appChain, $appGlobals);
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
   //$appEmitter->set_title('My Events - All Semesters');
    //$appEmitter->set_menu_standard($appChain, $appGlobals);
    //$appEmitter->set_menu_customize( $appChain, $appGlobals  );
    $appData->apd_smReport_editReport->stdRpt_initStyles($appEmitter);
    $this->drForm_addField( new Draff_Button( 'cancel' , 'Cancel') );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_smReport_editReport->stdRpt_initFormFields($appData,$this);
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
   // $appEmitter->zone_start('zone-content-header theme-header');
   // $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-select');
    $appData->apd_smReport_editReport->stdRpt_output($appEmitter,$appGlobals, $appData);

    //$appEmitter->table_start('draff-report',4);
    //$appEmitter->table_head_start();
    //$appEmitter->row_start('rpt-grid-row');
    //$appEmitter->cell_block('School Year');
    //$appEmitter->cell_block('Semester');
    //$appEmitter->row_end();
    //$appEmitter->table_head_end();
    //
    //$appEmitter->table_body_start('');
    //$curYear = 0;
    //foreach ($this->sem_semesters as $key => $desc) {
    //    $newYear = substr($key,0,4);
    //    if ($newYear != $curYear) {
    //        if ($curYear==0) {
    //            $appEmitter->row_end();
    //            $appEmitter->cell_end();
    //        }
    //        $appEmitter->row_start('rpt-grid-row');
    //        $curYear = $newYear;
    //        $appEmitter->cell_block($curYear);
    //        $appEmitter->cell_start();
    //    }
    //    $appEmitter->content_field($key);
    //}
    //if ($curYear!=0) {
    //    $appEmitter->cell_end();
    //    $appEmitter->row_end();
    //}
    //$appEmitter->table_body_end();
    //$appEmitter->table_end();
    $appEmitter->zone_end();
 }

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-footer theme-select');
    $appEmitter->content_block( '@cancel' );
    $appEmitter->zone_end();
}

} // end class

class report_smReportEdit {

function __construct() {
   // $this->com_eventSecurity =     new kcm2_sec_events;
}

function stdRpt_initStyles($appEmitter) {
    $appEmitter->addOption_styleTag('table.co-table','width:50rem');
    $appEmitter->addOption_styleTag('table.smrTable','border-collapse:collapse; border-spacing:0; empty-cells:show; border:2px; table-layout:fixed; margin-top: 8pt;');
    $appEmitter->addOption_styleTag('td','border:1px solid #666666; padding: 0.4em; font-size: 1em; font-weight:normal; vertical-align:top;');
    $appEmitter->addOption_styleTag('td.com_headColor','background-color: #ddddff;');
    $appEmitter->addOption_styleTag('div.smrHeadDiv','font-size:1.4em; font-weight:bold;');
    $appEmitter->addOption_styleTag('.smrTimeInput','width: 60pt;');
    $appEmitter->addOption_styleTag('td.sSt_name','width: 13em;');
    $appEmitter->addOption_styleTag('td.staff_time','width: 5em;');
    $appEmitter->addOption_styleTag('td.staff_equip','width: 4em;');
    $appEmitter->addOption_styleTag('td.staff_badge','width: 4em;');
    $appEmitter->addOption_styleTag('td.staff_schNote',' width: 20em; min-width: 14em; max-width: 35em;');
    $appEmitter->addOption_styleTag('td.ev_schNote','');
    $appEmitter->addOption_styleTag('textarea.ev_schNote','background-color: #eeeeee; width: 95%;');
    $appEmitter->addOption_styleTag('td.notes_edit','width: 30em;');
    $appEmitter->addOption_styleTag('textarea.notes_edit','width: 100%; height: 15em; font: 0.9em;');
    $appEmitter->addOption_styleTag('button','font-size: 1.2em;');
    $appEmitter->addOption_styleTag('button.submitCom','');
    $appEmitter->addOption_styleTag('button.submitInc',' margin-left: 5em;');
    $appEmitter->addOption_styleTag('button.submitOth','margin-left: 5em;');
    $appEmitter->addOption_styleTag('td.error','color: red; font-weight:bold;');
    $appEmitter->addOption_styleTag('.error input','background-color: #ffcccc; color: black;');
    $appEmitter->addOption_styleTag('.error textarea','background-color: #ffcccc; color: black;');
    $appEmitter->addOption_styleTag('td.absent','color: blue; font-weight:bold;');
    $appEmitter->addOption_styleTag('.absent input','background-color: #ccccff; color: blue;');
}

function stdRpt_initFormFields($appData, $form) {
    $calDateRec = $appData->apd_smReport_CalDateRecord;
    $form->drForm_addField( new Draff_TextArea( 'smrHeadNotes', $calDateRec->cSD_notes,array('readonly'=>'x','class'=>'red'),DRAFF_FIELD_RO));
    $form->drForm_addField( new Draff_TextArea ( 'smrHeadAct', $calDateRec->cSD_notesActivities));
    $form->drForm_addField( new Draff_TextArea ( 'smrHeadInc', $calDateRec->cSD_notesIncidents ));
    $calStaffArray = $calDateRec->joins['calStaff'];
    foreach($calDateRec->joins['calStaff'] as $key => $calStaff) {
        $staffId = $calStaff->cSS_staffId;
        $form->drForm_addField( new Draff_Time( 'smrStaffArrived_' . $staffId, $calStaff->cSS_timeArrived) );
        $form->drForm_addField( new Draff_Time( 'smrStaffLeft_' . $staffId, $calStaff->cSS_timeLeft) );
        $form->drForm_addField( new Draff_Combo( 'smrStaffAdjust_' . $staffId, $calStaff->cSS_timeAdjustment, $this->build_TimeAdjustList() ) );
        $form->drForm_addField( new Draff_CheckBox('smrStaffBadge_' . $staffId, $calStaff->cSS_hadBadge, '',  '0','1') );
        $form->drForm_addField( new Draff_CheckBox('smrStaffEquip_' . $staffId, $calStaff->cSS_hadEquipment, '',  '0','1'));
        $form->drForm_addField( new Draff_TextArea  ( 'cSS_Notes' . $staffId, $calStaff->cSS_Notes) );
    }
    $form->drForm_addField( new Draff_Button(  'submitCom' , 'Submit Completed Report' ) );
    $form->drForm_addField( new Draff_Button(  'submitInc' , 'Submit (incomplete, errors ok)' ) );
    $form->drForm_addField( new Draff_Button(  'submitOth' , 'Exit' ) );
    $form->drForm_addField( new Draff_Button(  'submitRes' , 'Reset' ) );
    //$this->drForm_addField( new Draff_Combo( ( $fieldId, $value, $list)
    //$this->drForm_addField( new Draff_Button( $fieldId, $caption) );
    //$form->drForm_addField( new Draff_Checkbox($fieldId, $caption, $value, $checkedValue,$uncheckedValue)
}

function stdRpt_output($appEmitter,$appGlobals, $appData) {
    $calDateRec = $appData->apd_smReport_CalDateRecord;
    $tableLayout = new rsmp_emitter_table_layout('smrTable', array(15,16,16,10,8,8,45));
    $appEmitter->table_start('smrTable',$tableLayout);
    $this->smr_print_header($appEmitter, $calDateRec);
    $this->event_printDetails($appEmitter, $calDateRec);
    $this->smr_print_staffHeading($appEmitter, $calDateRec) ;
    foreach($appData->apd_smReport_CalDateRecord->joins['calStaff'] as $key => $staff) {
        $this->smr_print_staffRow($appEmitter, $staff);
    }
    $this->smr_print_notes($appEmitter, $calDateRec);
    $appEmitter->row_start();
    $appEmitter->cell_block( array('@submitCom' , '@submitInc' ,  '@submitOth' ,'@submitRes'),'','colspan="7"');
    $appEmitter->row_end();
    $appEmitter->table_end();
}

function smr_print_header($appEmitter, $calDateRec) {
    $appEmitter->table_head_start();
    $appEmitter->row_oneCell('Class Details','com_headColor');
    $appEmitter->row_start();
    $appEmitter->cell_block('','com_headColor');
    $appEmitter->cell_block('Class Start','staff_time com_headColor');
    $appEmitter->cell_block('Class End','staff_time com_headColor');
    $appEmitter->cell_block('Schedule Notes','com_headColor', 'colspan="4"');
    $appEmitter->row_end();
    $appEmitter->table_head_end();
}

function event_printDetails($appEmitter, $calDateRec) {
    $appEmitter->table_body_start();
    $appEmitter->row_start();
    $appEmitter->cell_block('Scheduled Time','sSt_name');
    $s = draff_timeAsString($calDateRec->cSD_startTime,'g:ia') . ' - ' . draff_timeAsString($calDateRec->cSD_endTime,'g:ia');
    $this->time_display($appEmitter,$calDateRec->cSD_startTime);
    $this->time_display($appEmitter,$calDateRec->cSD_endTime);
    $s = '<textArea class="ev_schNote" readonly>'.$calDateRec->cSD_notes.'</textarea>';
    $appEmitter->cell_block('@smrHeadNotes','ev_schNote','colspan="4"');
    $appEmitter->row_end();
    $appEmitter->table_body_end();
}

function smr_print_staffHeading($appEmitter, $calDateRec) {
    $appEmitter->table_head_start();
    $appEmitter->row_start();
    $appEmitter->cell_block('Staff Information','com_headColor','colspan="7"');
    $appEmitter->row_end();
    $appEmitter->row_start();
    $appEmitter->cell_block('Name','sSt_name');
    $appEmitter->cell_block('Time<br>Arrived','staff_time');
    $appEmitter->cell_block('Time<br>Departured','staff_time');
    $appEmitter->cell_block('Time Adjustment<br>(h:mm)','staff_time');
    $appEmitter->cell_block('Has<br>Badge','staff_badge');
    $appEmitter->cell_block('Has<br>Equipment','staff_equip');
    $appEmitter->cell_block('Schedule Note','staff_schNote');
    $appEmitter->row_end();
    $appEmitter->table_head_end();
}

function smr_print_staffRow($appEmitter, $staff) {  // , $fields,$timeAdjustList, $calDateRec
    $appEmitter->row_start();
    $appEmitter->cell_block($staff->cSS_staffName, 'sSt_name');
    $staffId = $staff->cSS_staffId;
    $appEmitter->cell_block ( '@smrStaffArrived_' . $staffId);
    $appEmitter->cell_block ( '@smrStaffLeft_' . $staffId);
    $appEmitter->cell_block ( '@smrStaffAdjust_' . $staffId);
    $appEmitter->cell_block ('@smrStaffBadge_' . $staffId);
    $appEmitter->cell_block ('@smrStaffEquip_' . $staffId);
    $appEmitter->cell_block ( '@cSS_Notes'. $staffId);
    //$fields->coTimeArrived[$index]->input_cellTime('',$appEmitter);
    //$fields->coTimeLeft[$index]->input_cellTime('',$appEmitter);
    //$appEmitter->cellStart('');
    //$appEmitter->inputListBox('staff_time',1,$coach->coGetFieldId('Adjust'),$timeAdjustList,$coach->coTimeAdjustment);
    //$appEmitter->cellEnd();
    //$fields->coHadEquipment[$index]->input_cellCheckbox($appEmitter, '');
    //$fields->coHadBadge[$index]->input_cellCheckbox($appEmitter, '');
    //$appEmitter->cell_block('staff_schNote',$coach->coNotes);
    $appEmitter->row_end();
}

function smr_print_notes($appEmitter, $calDateRec) {
    $appEmitter->table_head_start();
    $appEmitter->row_oneCell('Notes','notes_edit com_headColor','colspan="99"');
    $appEmitter->table_head_end();
    $appEmitter->row_start();
    $appEmitter->cell_block('Incident Notes<br>(unusual incidents office needs to know about)','notes_edit com_headColor','colspan="4"');
    $appEmitter->cell_block('Activity Notes<br>(normal activities and reminders)',             'notes_edit com_headColor','colspan="3"' );
    $appEmitter->row_end();
    $appEmitter->row_start();
    $appEmitter->cell_block ( '@smrHeadInc', '', 'colspan="4"');
    $appEmitter->cell_block ( '@smrHeadAct', '', 'colspan="3"');
    $appEmitter->row_end();
}

function time_display($appEmitter, $time) {
    $appEmitter->cell_block(draff_timeAsString($time), 'staff_time' );
}

function printSubmit($appEmitter, $calDateRec) {
    print '<br>';
    $appEmitter->inputButton('submitCom','Submit Completed Report', 'submit','submitCom');
    $appEmitter->inputButton('submitInc','Submit (incomplete, errors ok)', 'submit','submitInc');
    $appEmitter->inputButton('submitOth','Exit', 'submit','submitCan');
    $appEmitter->inputButton('submitOth','Reset', 'submit','submitRes');
    print '<br>';
}

function build_TimeAdjustList() {
    $list = array();
    $list[] = 0;
    $list[] = '(none)';
    for ($i=5; $i <= 600; $i = $i + 5) {
        $minutes = $i % 60;
        $hours = ($i - $minutes) / 60;
        if ($minutes <= 5)
            $minutes = '0' . $minutes;
        $desc = $hours . ':' . $minutes;
        $list[$i] = $desc;
    }
    return $list;
}

} // end class

class application_data  extends draff_appData{

// used in events (select) form
public $apd_events_joinerAllPrograms;
public $apd_events_joinerMyPrograms;
public $apd_events_joinerPrograms;  // the current joiner (can by AllPrograms or MyPrograms)
public $apd_events_catToView;
public $apd_events_catDesc;  // array of categories ( classes, camps, etc), 0..integer
public $apd_events_catOfType;  // category of each event type
public $apd_events_catCount;

// used in semesters (select) form
public $apd_semesters_selectMap;
public $apd_semesters_year=0;          // also used by other forms, but set here - defult is current semester
public $apd_semesters_semesterCode=0;  // also used by other forms, but set here - defult is current semester

// used in program (view/edit) form
public $apd_program_programId;
public $apd_program_joiner;
public $apd_program_record;

// used in smReport (view/edit) form
public $apd_smReport_calDateId;
public $apd_smReport_programId;
public $apd_smReport_editReport;
public $apd_smReport_calDateJoiner;
public $apd_smReport_CalDateRecord;  // same as recordCalDate ??

function __construct() {
    $mode = draff_urlArg_getOptional('rsMode', NULL);
    $ar = explode('-',$mode);
    if ( count($ar)==2 ) {
        $this->apd_semesters_year = $ar[0];
        $this->apd_semesters_semesterCode = $ar[1];
    }
}

function apd_init_always( $appGlobals, $appChain ) {
    $this->apd_semesters_year         = $appChain->chn_status->ses_get('#eventYear', 0);
    $this->apd_semesters_semesterCode = $appChain->chn_status->ses_get('#eventSemesterCode', 0);
    $this->apd_events_catToView       = $appChain->chn_status->ses_get('#eventCategory', 0);
    $this->apd_program_programId      = $appChain->chn_status->ses_get('#editProgramId');
    $this->apd_smReport_calDateId     = $appChain->chn_status->ses_get('#smCalDateId');
}

function apd_events_fetchJoinerAllEvents( $appGlobals ) {
    $this->apd_events_catDesc = array('Classes<br>&nbsp;','Tournaments<br>and Camps','Other<br>Events');
    $this->apd_events_catOfType = array ( 2, 0, 1, 1, 2, 2, 2, 2, 2, 2 );
    $this->apd_events_catCount = array ( 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    $query = new draff_database_query;
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_add( "FROM `pr:program`");
    $query->rsmDbq_add( "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
    $query->rsmDbq_add( "WHERE (`pPr:HiddenStatus` ='0')" );  // maybe need to make optional
    if ($this->apd_semesters_year!='0000') {
        $query->rsmDbq_add( "AND (`pPr:SchoolYear` ='{$this->apd_semesters_year}')" );
        $query->rsmDbq_add( " AND (`pPr:SemesterCode` ='{$this->apd_semesters_semesterCode}')" );
    }
    else {
        $dateToday = date_create( "today" );
        $dateStart = clone $dateToday;
        $dateEnd = clone $dateStart;
        date_modify( $dateEnd, '21 day' );
        date_modify( $dateStart, '-21 day' );
        $pStartDate = date_format( $dateStart, 'Y-m-d' );
        $pEndDate = date_format( $dateEnd, 'Y-m-d' );
        $query->rsmDbq_add( "AND (`pPr:DateClassFirst`<='{$pEndDate}') AND (`pPr:DateClassLast` >= '{$pStartDate}')");
    }
    // switch ($this->apd_events_catToView) {
    //     case 1:
    //         $query->rsmDbq_add( "AND (`pPr:ProgramType` = '1')" );
    //         break;
    //     case 2:
    //     case 3:
    //         $query->rsmDbq_add( "AND (`pPr:ProgramType` IN ('2','3') )" );
    //         break;
    //     case 9:
    //         $query->rsmDbq_add( "AND (`pPr:ProgramType`  IN ('4','9') ) " );
    //         break;
    //     default:
    //         break;
    // }
    //  if duplicate school, need to add semester descriptions
    $query->rsmDbq_add( "ORDER BY `pSc:NameShort`");
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);

    $joiner = new draff_database_joiner;
    //$eventJoins = array();
    foreach ($result as $row) {
         $program = $joiner->rsmDbj_addDbRecord_asRoot( 'dbRecord_program', $row , 'pPr:ProgramId' );
         $cat =  $this->apd_events_catOfType[$program->prog_progType];
         ++$this->apd_events_catCount[$cat];
        // $school  = $joiner->rsmDbj_addDbRecord_asShared( 'dbRecord_school_base', $program, 'school', $row,  'pPr:@SchoolId'  );
    }
    return $joiner;
}

function apd_events_fetchJoinerMyEvents ( $appGlobals ) {
    $this->apd_events_catDesc = array('Classes<br>&nbsp;','Tournaments<br>and Camps','Other<br>Events');
    $this->apd_events_catOfType = array ( 2, 0, 1, 1, 2, 2, 2, 2, 2, 2 );
    $this->apd_events_catCount = array ( 0, 0, 0, 0, 0, 0, 0, 0, 0, 0 );
    $query = new draff_database_query;
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_add( "FROM `pr:program`");
    $query->rsmDbq_add( "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
    $query->rsmDbq_add( "WHERE `pPr:ProgramId` IN (" );
    gwy_fetch_subQuery_myAuthorizedPrograms($appGlobals , $query);
    $query->rsmDbq_add( ")" );
    $query->rsmDbq_add( "ORDER BY `pSc:NameShort`");
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);

    $joiner = new draff_database_joiner;
    //$eventJoins = array();
    foreach ($result as $row) {
        $program = $joiner->rsmDbj_addDbRecord_asRoot( 'dbRecord_program', $row , 'pPr:ProgramId' );
        $school  = $joiner->rsmDbj_addDbRecord_asShared( 'dbRecord_school_base', $program, 'school', $row,  'pPr:@SchoolId'  );
         $cat =  $this->apd_events_catOfType[$program->prog_progType];
         ++$this->apd_events_catCount[$cat];
    }
    return $joiner;
}

function apd_semesters_FetchSelectMap( $appGlobals ) {
    $semesters = array();
    $query = new draff_database_query;
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_add( "FROM `pr:program`");
    $query->rsmDbq_add( "WHERE `pPr:HiddenStatus` = '0'" );  //???? add form option to include hidden
    $query->rsmDbq_add( "GROUP BY `pPr:SchoolYear`,`pPr:SemesterCode`");
    $query->rsmDbq_add( "ORDER BY `pPr:SchoolYear` DESC,`pPr:SemesterCode`");
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
    foreach ( $result as $row) {
        $semYear = $row['pPr:SchoolYear'];
        $semCode = $row['pPr:SemesterCode'];
        $semDescYear = ($semCode==40) ? ($semYear+1) : $semYear;
        $semDesc = rc_getSemesterNameFromCode($semCode) . ' - ' . $semDescYear;
        $key = $semYear . '_' . $semCode ;
        $semesters[$key]  = $semDesc;
    }
    return $semesters;
}

function app_program_fetchJoiner( $appGlobals, $appChain ) {
    $query = new draff_database_query;
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_selectAddColumns('dbRecord_calDate');
    $query->rsmDbq_selectAddColumns('dbRecord_calStaff');
    $query->rsmDbq_selectAddColumns('dbRecord_staff');
    $query->rsmDbq_add( "FROM `pr:program`");
    $query->rsmDbq_add( "LEFT JOIN `ca:scheduledate` on `cSD:@ProgramId` = `pPr:ProgramId`");
    $query->rsmDbq_add( "LEFT JOIN `ca:scheduledate_staff` ON (`cSS:@ScheduleDateId` = `cSD:ScheduleDateId`)");
    $query->rsmDbq_add( "LEFT JOIN `st:staff` ON `sSt:StaffId` = `cSS:@StaffId`" );
    $query->rsmDbq_add( "LEFT JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
    $query->rsmDbq_add( "WHERE (`pPr:ProgramId` ='{$this->apd_program_programId}')" );
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
    $joiner = new draff_database_joiner;
    foreach ($result as $row) {
         $program = $joiner->rsmDbj_addDbRecord_asRoot( 'dbRecord_program', $row , 'pPr:ProgramId' );
         $calDate  = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_calDate', $program, 'calDate', $row );
         $calStaff  = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_calStaff', $calDate, 'calStaff', $row );
         $staff  = $joiner->rsmDbj_addDbRecord_asShared( 'dbRecord_staff', $calStaff, 'staff', $row );
    }

    if ($program->prog_progType == 1) {
        $query2 = new draff_database_query;
        $query2->rsmDbq_selectAddColumns('dbRecord_program');
        $query2->rsmDbq_selectAddColumns('dbRecord_school_base');
        $query2->rsmDbq_add( "FROM `pr:program`");
        $query2->rsmDbq_add( "JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
        $query2->rsmDbq_add( "WHERE (`pPr:@SchoolId` ='{$program->prog_schoolId}')" );
        $query2->rsmDbq_add( "   AND (`pPr:ProgramType` ='1')" );
        $query2->rsmDbq_add( "ORDER BY `pPr:SchoolYear` DESC, `pPr:SemesterCode` DESC" );
        $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query2);
        foreach ($result as $row) {
             $semProgram  = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_program', $program, 'semesters', $row );
        }
    }
    return $joiner;
}

function app_smReport_fetchJoinerCalDate( $appGlobals, $appChain ) {
    $query = new draff_database_query;
    $query->rsmDbq_selectAddColumns('dbRecord_program');
    $query->rsmDbq_selectAddColumns('dbRecord_school_base');
    $query->rsmDbq_selectAddColumns('dbRecord_calDate');
    $query->rsmDbq_selectAddColumns('dbRecord_calStaff');
    $query->rsmDbq_selectAddColumns('dbRecord_staff');
    $query->rsmDbq_add( "FROM `ca:scheduledate`");
    $query->rsmDbq_add( "LEFT JOIN `ca:scheduledate_staff` ON `cSS:@ScheduleDateId` = `cSD:ScheduleDateId`");
    $query->rsmDbq_add( "LEFT JOIN `st:staff` ON `sSt:StaffId` = `cSS:@StaffId`" );
    $query->rsmDbq_add( "LEFT JOIN  `pr:program` ON `pPr:ProgramId` = `cSD:@ProgramId`");
    $query->rsmDbq_add( "LEFT JOIN `pr:school` ON `pSc:SchoolId` = `pPr:@SchoolId`");
    $query->rsmDbq_add( "WHERE `cSD:ScheduleDateId` ='{$this->apd_smReport_calDateId}'" );
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
    $joiner = new draff_database_joiner;
    foreach ($result as $row) {
         $calDate   = $joiner->rsmDbj_addDbRecord_asRoot( 'dbRecord_calDate', $row );
         $calStaff  = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_calStaff', $calDate, 'calStaff', $row );
         $program   = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_program', $calDate, 'program', $row );
         $staff     = $joiner->rsmDbj_addDbRecord_asMultipleJoins( 'dbRecord_staff', $calStaff, 'staff', $row );
    }
    return $joiner;
   // // apd_smReport_calDateId
   //  $this->apd_smReport_programId = $appChain->chn_data_posted_get('#smCalDateId');
   //  //$this->apd_program_programId = $appChain->chn_data_posted_get('#myEventId');
   //  $this->apd_smReport_editReport = new report_smReportEdit;
   //  $this->apd_smReport_CalDateRecord = new dbRecord_calDate;
   //  $this->apd_smReport_CalDateRecord->stdRec_readRecord($appGlobals, $this->apd_smReport_programId);
}

function apd_smReport_save( $appGlobals, $appChain ) {
    // possibly should be a transaction. but not at all critical if some records are not updated
    $row = array();
    $row['cSD:ScheduleDateId'] = $this->apd_smReport_CalDateRecord->cSD_scheduleDateId;
    $row['cSD:Notes'] = $this->apd_smReport_CalDateRecord->cPA_courseKey;
    $row['cSD:NotesIncidents'] = $this->apd_smReport_CalDateRecord->cSD_notesIncidents;
    $row['cSD:NotesActivities'] = $this->apd_smReport_CalDateRecord->cSD_notesActivities;
    $row['cSD:SMSubmissionStatus'] = $this->apd_smReport_CalDateRecord->cSD_SMSubmissionStatus;
    $appGlobals->gb_pdo->rsmDbe_writeRecord('dbRecord_calDate', $row);
    foreach ($this->apd_smReport_CalDateRecord->joins['calStaff'] as $calStaffId => $calStaff) {
        $row = array();
        $row['cSS:ScheduleDateStaffId'] = $calStaff->cSS_calStaffId;
        $row['cSS:@ScheduleDateId'] = $calStaff->cSS_calDateId;
        $row['cSS:@StaffId'] = $calStaff->cSS_staffId;
        $row['cSS:RoleType'] = $calStaff->cSS_roleType;
        $row['cSS:TimeArrived'] = $calStaff->cSS_timeArrived;
        $row['cSS:TimeLeft'] = $calStaff->cSS_timeLeft;
        $row['cSS:TimeAdjustment'] = $calStaff->cSS_timeAdjustment;
        $row['cSS:HadEquipment'] = $calStaff->cSS_hadEquipment;
        $row['cSS:HadBadge'] = $calStaff->cSS_hadBadge;
        $row['cSS:Notes'] = $calStaff->cSS_Notes;  //??????
        $appGlobals->gb_pdo->rsmDbe_writeRecord('dbRecord_calStaff', $row);
    }
 //    $appChain->chn_message_set('SM Report data changed');  // should add date

}

function apd_get_minPeriod($appGlobals, $programId) {
    // should move to what is being called so if min period not specified, will compute default
    $sql = array();
    $sql[] = "SELECT `pPe:PeriodId`,`pPe:PeriodSequenceBits`";
    $sql[] = "FROM `pr:program`";
    $sql[] = "JOIN `pr:period` ON `pPe:@ProgramId` = `pPr:ProgramId`";
    $sql[] = "WHERE `pPr:ProgramId` ='{$this->apd_program_programId}'";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_pdo->rsmDbe_execute($query);
    $minVal = 99999;
    $minId = 0;
    foreach ($result as $row) {
       $val = $row['pPe:PeriodSequenceBits'];
        if ($val < $minVal) {
            $minVal = $val;
            $minId = $row['pPe:PeriodId'];
        }
    }
    return $minId;
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
$appChain->chn_form_register(DRFFORM_EVENTS_SELECT     ,'appForm_events_select');
$appChain->chn_form_register(DRFFORM_SEMESTERS_SELECT  ,'appForm_semesters_select');
$appChain->chn_form_register(DRFFORM_SMREPORT_EDIT    ,'appForm_smReport_edit');
$appChain->chn_form_register(DRFFORM_PROGRAM_EDIT     ,'appForm_program_edit');

$appChain->chn_form_launch(); // proceed to current step

exit;

?>