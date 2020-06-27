<?php

// gateway.php

ob_start();  // output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );

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

CONST DRFFORM_MY_SCHEDULE     = 1;
CONST DRFFORM_PANEL_EVENT     = 21;
CONST DRFFORM_PANEL_SMREPORT  = 22;

//=============
//==============================
//=============================================
Class appForm_mySchedule extends kcmKernel_Draff_Form {

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    if ($appChain->chn_submit[0] == 'week') {
        $appChain->chn_status->ses_set('#schedAction', $appChain->chn_submit[1]);
        $appChain->chn_form_launch(1);
    //gateway-schedule.php?date='.$appData->apd_scheduleFilter->fiDateStart.'&week=prev';
    }
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_schedule_argDate      = $appChain->chn_status->ses_get('#schedDate', 0);
    //$appData->apd_schedule_argDate = '2019-11-04';
    $appData->apd_schedule_argAction    = $appChain->chn_status->ses_get('#schedAction', 0);
    //$appData->apd_schedule_argAction    = 'next';
    //$appData->apd_init_always( $appGlobals, $appChain );
    $appData->apd_scheduleEngine = new kcm_schedule_engine;
    $appData->apd_scheduleFilter = new kcm_schedule_filter('k');
    $appData->apd_scheduleFilter->setWeekThis($appData->apd_schedule_argDate);
    $appData->apd_scheduleFilter->fiPrintGroupTitle = FALSE;
    $appData->apd_scheduleFilter->fiPrintReportTitle = FALSE;
    if ( $appData->apd_schedule_argAction == 'next')
        $appData->apd_scheduleFilter->setWeekNext();
    else if ( $appData->apd_schedule_argAction == 'prev')
        $appData->apd_scheduleFilter->setWeekPrev();
    $appData->apd_scheduleFilter->fiReportCode = SF_REPORT_SCHED_BYCOACH;
    $appData->apd_scheduleFilter->fiStaffId = rc_getStaffId();
    $appData->apd_scheduleFilter->setComputedFields();
    $oldVersionDataBase = rc_getGlobalDatabaseObject();
    $appData->apd_scheduleEngine->getData($oldVersionDataBase, $appData->apd_scheduleFilter);
    $appData->apd_staffName = $appData->getStaffName($appGlobals);
        $appChain->chn_status->ses_set('#schedDate', $appData->apd_scheduleFilter->fiDateStart);
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appData->apd_view_getData( $appGlobals );
    $appEmitter->emit_options->set_theme( 'theme-legacy' );
    $appEmitter->emit_options->set_title('Weekly Schedule');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appGlobals);
    $appGlobals->gb_menu->drMenu_customize( 'ls-sd'  );
    $appEmitter->emit_options->addOption_styleFile('css/krLib/krLib-schedule-output.css','all','../../');
    $dateCss = 'background-color:white;font-size:1.1em;max-width:6em; min-width:6em;';
    $appEmitter->emit_options->addOption_styleTag('table.scTable','background-color:white;font-size:0.7em;width:95%;max-width:1900px;min-width:400px;');
    $appEmitter->emit_options->addOption_styleTag('td.sch_date',$dateCss);
    $appEmitter->emit_options->addOption_styleTag('td.sch_date_scIsSameFirst',$dateCss);
    $appEmitter->emit_options->addOption_styleTag('div.xdraff-zone-header','font-size:0.5em;background-color:#c2f6ef');
}


function drForm_initFields( $appData, $appGlobals, $appChain ) {
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    print PHP_EOL.'<div class="zone-content-header theme-select">';   //??????????????
    print $appData->apd_staffName . '<br>Schedule for ' .dateSql_toShort($appData->apd_scheduleFilter->fiDateStart).' to '.dateSql_toShort($appData->apd_scheduleFilter->fiDateEnd) ;
          //print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<br>';
    $urlNext = $appChain->chn_url_getString(array('date'=>$appData->apd_scheduleFilter->fiDateStart,'week'=>'next'));
    //print  PHP_EOL.'<a href="'.$urlNext.'">Next Week</a>';
    print  PHP_EOL.PHP_EOL.PHP_EOL.'<button type="submit" name="submit" value="week_next">Next Week</button>';
    print  PHP_EOL.PHP_EOL.PHP_EOL.'<button type="submit" name="submit" value="week_prev">Previous Week</button>';
    //print  PHP_EOL.'<a class="sch_button" href="gateway-schedule.php?date='.$appData->apd_scheduleFilter->fiDateStart.'&week=next">Next Week</button>';
    print PHP_EOL.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    //$urlPrev = $appChain->chn_url_getString(array('date'=>$appData->apd_scheduleFilter->fiDateStart,'week'=>'prev'));
   // print  PHP_EOL.PHP_EOL.'<a class="sch_button" href="'.$urlPrev.'">Previous Week</a>';
    //print  '<a class="sch_button" href="gateway-schedule.php?date='.$appData->apd_scheduleFilter->fiDateStart.'&week=prev">Previous Week</a>';
    print PHP_EOL . '</div>';
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $printSchedule = new rc2_printSchedule('k');
    print PHP_EOL.'<div class="zone-content-scrollable theme-legacy">';
    $page = new kcm_pageEngine();
    $printSchedule->printReport($page, $appData->apd_scheduleFilter, $appData->apd_scheduleEngine);
    //$appEmitter->zone_start('draff-zone-content-report');
    //$appEmitter->zone_end();
    print PHP_EOL . '</div>';
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

//=============
//==============================
//=============================================

class appForm_panel_event extends kcmKernel_Draff_Form {

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    $appData->apd_common_processSubmit( $appGlobals, $appChain );
    //$appData->apd_getData_myProgram( $appGlobals, $appChain )
    if ( $appChain->chn_submit[0] == '@kcm2') {
        $periodId = $appData->apd_get_minPeriod($appGlobals, $appChain->chn_submit[1]);
        // wrong url
        $appChain->chn_url_redirect('../kcm-roster/roster-results-games.php',  FALSE, array('PrId'=>$appChain->chn_submit[1],'PeId'=>$periodId,'drfMode'=>'3'));
        return;
    }
    if ( $appChain->chn_submit[0] == '@kcm1') {
        $periodId = $appData->apd_get_minPeriod($appGlobals, $appChain->chn_submit[1]);
        $appChain->chn_url_redirect('../../kcm-periodHome.php',  FALSE, array('PrId'=>$appChain->chn_submit[1],'PeId'=>$periodId));
         return;
    }
    if ( $appChain->chn_submit[0] == '@smReport') {
        $appChain->chn_data_posted_set('#smReportId',$appChain->chn_submit[1]);
        $appChain->chn_launch_continueChain(DRFFORM_PANEL_SMREPORT);
        return;
    }
    if ( $appChain->chn_submit[0] == '@myEvents') {
        $this->step_setShared('#selectMode','all');
        $appChain->chn_launch_continueChain(DRFFORM_MY_EVENTS);
        return;
    }
    if ( $appChain->chn_submit[0] == '@allEvents') {
        $this->step_setShared('#selectMode','all');
        $appChain->chn_launch_continueChain(DRFFORM_SELECT_PROGRAM);
        return;
    }
    if ( $appChain->chn_submit[0] == '@allSemesters') {
        $this->step_setShared('#selectMode','today');
        $appChain->chn_launch_continueChain(DRFFORM_SELECT_SEMESTER);
        return;
    }
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_init_always( $appGlobals, $appChain );
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-panel' );
    $appEmitter->emit_options->set_title('My Events');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appGlobals);
    $appGlobals->gb_menu->drMenu_customize();
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    //$appData->apd_getData_myEvent( $appGlobals, $appChain );
    $appData->apd_current_programId = $appChain->chn_posted->ses_get('#myEventId');
  //  $appData->apd_myEvents_list = new xxmyEvents_batch($appGlobals);
    $appData->apd_getData_myProgram( $appGlobals, $appChain );
    $myEvent = $appData->apd_myProgram_program;
    $appData->apd_common_initControls ( $appGlobals, $appChain, $this );
    $this->drForm_define_button( '@kcm2_'.$myEvent->prog_programId , 'KCM-2' );
    $this->drForm_define_button( '@kcm1_'.$myEvent->prog_programId , 'KCM-1' );

    foreach ($myEvent->schProg_map_assignmentDate as $key => $calDate) {
        // $key = $myEvent->cale_smReportRecordId[$i];
        $desc = draff_dateAsString($calDate->cSD_classDate, 'F j' );
        $butKey = '@smReport_' . $key;
        $this->drForm_define_button( $butKey, $desc );
    }
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-header theme-select');
    $appData->apd_common_header_output ( $appGlobals, $appChain, $appEmitter, $this );
    $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-select');
    $appEmitter->table_start('draff-report',4);

    $appEmitter->table_head_start();

    $appEmitter->table_body_start('');

    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('Event');
    $appEmitter->cell_block($appData->apd_myProgram_program->prog_programName);
    $appEmitter->row_end();

    $myEvent = $appData->apd_myProgram_program;
    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('KCM');
    $appEmitter->cell_block(array('@kcm2_'.$myEvent->prog_programId,'@kcm1_'.$myEvent->prog_programId));
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('Site Manager<br>Reports');
    $appEmitter->cell_start();

    foreach ($myEvent->schProg_map_assignmentDate as $key => $calDate) {
        $butKey = '@smReport_' . $key;
        // $key = $myEvent->cale_smReportRecordId[$i];
        $appEmitter->content_block( $butKey);
    }
    $appEmitter->cell_end();
    //$appEmitter->cell_block($key3);
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-grid-row');
    $appEmitter->cell_block('Select another semester');
    $appEmitter->cell_block('');
    $appEmitter->row_end();

    $appEmitter->table_body_end();
    $appEmitter->table_end();
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

class appForm_panel_smReport extends kcmKernel_Draff_Form {

public $sem_semesters;

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
    $appData->apd_common_processSubmit( $appGlobals, $appChain, $submit );
    // test for cancel
    $appChain->chn_form_savePostedData();
    $appData->apd_getData_smReport( $appGlobals, $appChain );
    if ( $appChain->chn_submit[0] == '@submitCom') {
        $appData->apd_smReport_save( $appGlobals, $appChain );
        $appChain->chn_launch_restartAfterRecordSave(2);
        return;
    }
    if ( $appChain->chn_submit[0] == '@submitInc') {
        $appData->apd_smReport_save( $appGlobals, $appChain );
        $appChain->chn_launch_restartAfterRecordSave(2);
        return;
   }

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_init_always( $appGlobals, $appChain );
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-panel' );
    $appEmitter->emit_options->set_title('My Events - All Semesters');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appGlobals);
    $appGlobals->gb_menu->drMenu_customize();
    $appData->apd_smReport_editReport->stdRpt_initStyles($appEmitter);
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_getData_smReport( $appGlobals, $appChain );
    $appData->apd_smReport_editReport->stdRpt_initFormFields($appData,$this);
    $appData->apd_common_initControls ( $appGlobals, $appChain, $this );
    //foreach ($this->sem_semesters as $key => $desc) {
        // $name = $Draff_Emitter_Html::getString_sizedMemo($staff->st_name,10);
     //   $name = $staff->st_name;
     //   $id = '@staffId_' .  $staff->st_staffId;
    //    $this->drForm_define_button( $key , $desc );
    //}
    //application_data::common_standardFooter_define($form);
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-header theme-select');
    $appData->apd_common_header_output ( $appGlobals, $appChain, $appEmitter, $this );
    $appEmitter->zone_end();
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
}

} // end class

class appForm_events_viewDetails extends kcmKernel_Draff_Form {

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
    $appData->apd_init_always( $appGlobals, $appChain );
}

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-panel' );
    $appEmitter->emit_options->set_title('My Events');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appGlobals);
    $appGlobals->gb_menu->drMenu_customize();
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

} // end class

class report_smReportEdit {

function __construct() {
   // $this->com_eventSecurity =     new kcm2_sec_events;
}

function stdRpt_initStyles($appEmitter) {
    $appEmitter->emit_options->addOption_styleTag('table.co-table','width:50rem');
    $appEmitter->emit_options->addOption_styleTag('table.smrTable','border-collapse:collapse; border-spacing:0; empty-cells:show; border:2px; table-layout:fixed; margin-top: 8pt;');
    $appEmitter->emit_options->addOption_styleTag('td','border:1px solid #666666; padding: 0.4em; font-size: 1em; font-weight:normal; vertical-align:top;');
    $appEmitter->emit_options->addOption_styleTag('td.com_headColor','background-color: #ddddff;');
    $appEmitter->emit_options->addOption_styleTag('div.smrHeadDiv','font-size:1.4em; font-weight:bold;');
    $appEmitter->emit_options->addOption_styleTag('.smrTimeInput','width: 60pt;');
    $appEmitter->emit_options->addOption_styleTag('td.sSt_name','width: 13em;');
    $appEmitter->emit_options->addOption_styleTag('td.staff_time','width: 5em;');
    $appEmitter->emit_options->addOption_styleTag('td.staff_equip','width: 4em;');
    $appEmitter->emit_options->addOption_styleTag('td.staff_badge','width: 4em;');
    $appEmitter->emit_options->addOption_styleTag('td.staff_schNote',' width: 20em; min-width: 14em; max-width: 35em;');
    $appEmitter->emit_options->addOption_styleTag('td.ev_schNote','');
    $appEmitter->emit_options->addOption_styleTag('textarea.ev_schNote','background-color: #eeeeee; width: 95%;');
    $appEmitter->emit_options->addOption_styleTag('td.notes_edit','width: 30em;');
    $appEmitter->emit_options->addOption_styleTag('textarea.notes_edit','width: 100%; height: 15em; font: 0.9em;');
    $appEmitter->emit_options->addOption_styleTag('button','font-size: 1.2em;');
    $appEmitter->emit_options->addOption_styleTag('button.submitCom','');
    $appEmitter->emit_options->addOption_styleTag('button.submitInc',' margin-left: 5em;');
    $appEmitter->emit_options->addOption_styleTag('button.submitOth','margin-left: 5em;');
    $appEmitter->emit_options->addOption_styleTag('td.error','color: red; font-weight:bold;');
    $appEmitter->emit_options->addOption_styleTag('.error input','background-color: #ffcccc; color: black;');
    $appEmitter->emit_options->addOption_styleTag('.error textarea','background-color: #ffcccc; color: black;');
    $appEmitter->emit_options->addOption_styleTag('td.absent','color: blue; font-weight:bold;');
    $appEmitter->emit_options->addOption_styleTag('.absent input','background-color: #ccccff; color: blue;');
}

function stdRpt_initFormFields($appData, $form) {
    $dateAssign = $appData->apd_rec_eventDate;
    $form->drForm_addField( new Draff_TextArea  ( 'smrHeadNotes',  $dateAssign->cSD_notes));
    $form->drForm_addField( new Draff_TextArea  ( 'smrHeadAct',  $dateAssign->cSD_notesActivities));
    $form->drForm_addField( new Draff_TextArea  ( 'smrHeadInc',  $dateAssign->cSD_notesIncidents));
    foreach($dateAssign->cSD_staffMap as $key => $calStaff) {
        $staffId = $calStaff->cSS_staffId;
        $form->drForm_addField( new Draff_Time  ( 'smrStaffArrived_' . $staffId, $calStaff->cSS_timeArrived));
        $form->drForm_addField( new Draff_Time  ( 'smrStaffLeft_' . $staffId,  $calStaff->cSS_timeLeft));
        $this->drForm_addField( new Draff_Combo( 'smrStaffAdjust_' . $staffId, $calStaff->cSS_timeAdjustment, $this->build_TimeAdjustList()) );
        $form->drForm_addField( new Draff_Checkbox('smrStaffBadge_' . $staffId, $calStaff->cSS_hadBadge, '', 0 ));
        $form->drForm_addField( new Draff_Checkbox('smrStaffEquip_' . $staffId, $calStaff->cSS_hadEquipment, '', 0));
        $form->drForm_addField( new Draff_TextArea  ( 'cSS_Notes' . $staffId, $calStaff->cSS_Notes));
    }
    $form->drForm_define_button( '@submitCom' , 'Submit Completed Report' );
    $form->drForm_define_button( '@submitInc' , 'Submit (incomplete, errors ok)' );
    $form->drForm_define_button( '@submitOth' , 'Exit' );
    $form->drForm_define_button( '@submitRes' , 'Reset' );
    //$this->drForm_addField( new Draff_Combo($fieldId, $value, $list) );
    //$form->drForm_define_button($fieldId, $caption)
    //$form->drForm_addField( new Draff_Checkbox($fieldId, $caption, $value, $checkedValue,$uncheckedValue)
}

function stdRpt_output($appEmitter,$appGlobals, $appData) {
    $dateAssign = $appData->apd_rec_eventDate;
    $tableLayout = new rsmp_emitter_table_layout('smrTable', array(15,16,16,13,10,40));
    $appEmitter->table_start('smrTable',$tableLayout);
    $this->smr_print_header($appEmitter, $dateAssign);
    $this->event_printDetails($appEmitter, $dateAssign);
    $this->smr_print_staffHeading($appEmitter, $dateAssign) ;
    foreach($dateAssign->cSD_staffMap as $key => $staff) {
        $this->smr_print_staffRow($appEmitter, $staff);
    }
    $this->smr_print_notes($appEmitter, $dateAssign);
    $appEmitter->row_start();
    $appEmitter->cell_block( array('@submitCom' , '@submitInc' ,  '@submitOth' ,'@submitRes'),'','colspan="6"');
    $appEmitter->row_end();
    $appEmitter->table_end();
}

function smr_print_header($appEmitter, $dateAssign) {
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

function event_printDetails($appEmitter, $dateAssign) {
    $appEmitter->table_body_start();
    $appEmitter->row_start();
    $appEmitter->cell_block('Scheduled Time','sSt_name');
    $s = draff_timeAsString($dateAssign->cSD_startTime,'g:ia') . ' - ' . draff_timeAsString($dateAssign->cSD_endTime,'g:ia');
    $this->time_display($appEmitter,$dateAssign->cSD_startTime);
    $this->time_display($appEmitter,$dateAssign->cSD_endTime);
    $s = '<textArea class="ev_schNote" readonly>'.$dateAssign->cSD_notes.'</textarea>';
    $appEmitter->cell_block('@smrHeadNotes','ev_schNote','colspan="4"');
    $appEmitter->row_end();
    $appEmitter->table_body_end();
}

function smr_print_staffHeading($appEmitter, $dateAssign) {
    $appEmitter->table_head_start();
    $appEmitter->row_start();
    $appEmitter->cell_block('Staff Information','com_headColor','colspan="7"');
    $appEmitter->row_end();
    $appEmitter->row_start();
    $appEmitter->cell_block('Name','sSt_name');
    $appEmitter->cell_block('Time Arrived','staff_time');
    $appEmitter->cell_block('Time Departured','staff_time');
    $appEmitter->cell_block('Time Adjustment<br>(h:mm)','staff_time');
    $appEmitter->cell_block('Has<br>Badge','staff_badge');
    $appEmitter->cell_block('Has<br>Equipment','staff_equip');
    $appEmitter->cell_block('Schedule Note','staff_schNote');
    $appEmitter->row_end();
    $appEmitter->table_head_end();
}

function smr_print_staffRow($appEmitter, $staff) {  // , $fields,$timeAdjustList, $dateAssign
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

function smr_print_notes($appEmitter, $dateAssign) {
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

function printSubmit($appEmitter, $dateAssign) {
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
//public $apd_joinMap_semesters;
//public $apd_joinMap_programs;
//
//// current status from chain status
//public $apd_current_year;
//public $apd_current_semester;
//public $apd_current_programType;
//public $apd_current_programId;
//
//public $apd_myEvents_list;
//public $apd_myProgram_program;
//public $apd_smReport_programId;
//public $apd_smReport_editReport;
//public $apd_rec_eventDate;
//
//public $programSemesters;  // programs included in com_fList_program_array
//public $submitProgramId = 0;
//public $submitOverrideProgramId = 0;


// form gateway-old
public $apd_schedule_argDate;
public $apd_schedule_argAction;
public $apd_scheduleEngine;
public $apd_scheduleFilter;
public $apd_staffName;

function __construct() {
//    $mode = draff_urlArg_getOptional('rsMode', NULL);
//    $ar = explode('-',$mode);
//    if ( count($ar)==2 ) {
//        $this->apd_current_year = $ar[0];
//        $this->apd_current_semester = $ar[1];
//    }
}

function apd_init_always( $appGlobals, $appChain ) {
    //$appData->apd_schedule_argDate = '2019-11-04';
    //$appData->apd_schedule_argAction = 'next';
}

function apd_getData_smReport( $appGlobals, $appChain ) {
    $this->apd_smReport_programId = $appChain->chn_data_posted_get('#smReportId');
    //$this->apd_current_programId = $appChain->chn_data_posted_get('#myEventId');
    $this->apd_smReport_editReport = new report_smReportEdit;
    $this->apd_rec_eventDate = new dbRecord_calDate;
    $this->apd_rec_eventDate->stdRec_readRecord($appGlobals, $this->apd_smReport_programId);
    $appChain->chn_posted_read( '@smrHeadNotes',$this->apd_rec_eventDate->cSD_notes);
    $appChain->chn_posted_read( '@smrHeadAct',  $this->apd_rec_eventDate->cSD_notesActivities);
    $appChain->chn_posted_read( '@smrHeadInc',$this->apd_rec_eventDate->cSD_notesIncidents);
    foreach($this->apd_rec_eventDate->cSD_staffMap as $key => $staff) {
        $staffId = $staff->cSS_staffId;
        $appChain->chn_posted_read('@smrStaffArrived_' . $staffId, $staff->cSS_timeArrived);
        $appChain->chn_posted_read('@smrStaffLeft_' . $staffId, $staff->cSS_timeLeft);
        $appChain->chn_posted_read('@smrStaffAdjust_' . $staffId, $staff->cSS_timeAdjustment);
        $appChain->chn_posted_read('@smrStaffBadge_' . $staffId,$staff->cSS_hadBadge);
        $appChain->chn_posted_read('@smrStaffEquip_' . $staffId,$staff->cSS_hadEquipment);
        $appChain->chn_posted_read('@cSS_Notes' . $staffId, $staff->cSS_Notes);
    }
}

function apd_smReport_save( $appGlobals, $appChain ) {
    // possibly should be a transaction. but not at all critical if some records are not updated
    $q = new rc_saveToDbQuery( $appGlobals->gb_db, 'ca:scheduledate', "UPDATE" );
    $q->setFieldVal( 'cSD:ScheduleDateId'    , $this->apd_rec_eventDate->cSD_scheduleDateId );
    $q->setFieldVal( 'cSD:NotesIncidents'    , $this->apd_rec_eventDate->cSD_notes );
    $q->setFieldVal( 'cSD:NotesActivities'   , $this->apd_rec_eventDate->cSD_notesIncidents );
    $q->setFieldVal( 'cSD:SMSubmissionStatus', $this->apd_rec_eventDate->cSD_notesActivities );
    $q->setModByAndWhen( 'cSD:' );
    $q->buildWhere( 'cSD:ScheduleDateId', $this->apd_rec_eventDate->cSD_scheduleDateId );
    $q->setCopyToHistoryBeforeAfter();
    $result = $q->doQuery();
    if ($result === FALSE) {
        $dbFailed = TRUE;
    }
    foreach($this->apd_rec_eventDate->cSD_staffMap as $key => $calStaff) {
        $q = new rc_saveToDbQuery( $appGlobals->gb_db, 'ca:scheduledate_staff', "UPDATE" );
        $q->setFieldVal( 'cSS:ScheduleDateStaffId', $calStaff->cSS_calStaffId );
        $q->setFieldVal( 'cSS:TimeArrived', $calStaff->cSS_timeArrived );
        $q->setFieldVal( 'cSS:TimeLeft', $calStaff->cSS_timeLeft );
        $q->setFieldVal( 'cSS:TimeAdjustment', $calStaff->cSS_timeAdjustment );
        $q->setFieldVal( 'cSS:HadEquipment', $calStaff->cSS_hadEquipment );
        $q->setFieldVal( 'cSS:HadBadge', $calStaff->cSS_hadBadge );
        $q->setFieldVal( 'cSS:Notes', $calStaff->cSS_Notes );
        $q->setModByAndWhen( 'cSS:' );
        $q->buildWhere( 'cSS:ScheduleDateStaffId', $calStaff->cSS_calStaffId );
        $q->setCopyToHistoryBeforeAfter();
        $result = $q->doQuery();
        if ($result === FALSE) {
            $dbFailed = TRUE;
        }
    }
    //$this->status->ses_set('#recordWhenSaved',draff_getMicroTime());
    $appChain->chn_message_set('SM Report data changed');  // should add date

}

function apd_get_minPeriod($appGlobals, $programId) {
    $sql = array();
    $sql[] = "SELECT `pPe:PeriodId`,`pPe:PeriodSequenceBits`";
    $sql[] = "FROM `pr:program`";
    $sql[] = "JOIN `pr:period` ON `pPe:@ProgramId` = `pPr:ProgramId`";
    $sql[] = "WHERE `pPr:ProgramId` ='{$programId}'";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query( $query );
    if ( $result === FALSE) {
        $appGlobals->gb_sql->sql_errorTerminate( $query);
    }
    $minVal = 99999;
    $minId = 0;
    while ($row=$result->fetch_array()) {
        $val = $row['pPe:PeriodSequenceBits'];
        if ($val < $minVal) {
            $minVal = $val;
            $minId = $row['pPe:PeriodId'];
        }
    }
    return $minId;
}

function com_htmlOut_startOfPage($appChain, $appData, $appEmitter, $form, $appGlobals, $subTitle) {
    //$appEmitter->gwyEmit_kernelOverride_webPage_init( $appGlobals, $appChain,'??','ls*');  // includes adding of css files
    $appEmitter->emit_options->addOption_styleTag('table.loc-programs', 'margin: 10pt 10pt 10pt 10pt; background-color:white;');
    $appEmitter->emit_options->addOption_styleTag('td.loc-programs-sel', 'font:14pt; font-weight:bold; padding: 4pt 12pt 4pt 12pt; background-color:white;min-width:210pt;');
    $appEmitter->emit_options->addOption_styleTag('td.loc-programs', 'font:14pt; font-weight:bold; padding: 4pt 12pt 4pt 12pt; background-color:white;');
}

static function common_standardFilters_define($form) {
    $form->drForm_addField( new Draff_Checkbox( '@filterClass', 'Classes' , 1,'1','0') );
    $form->drForm_addField( new Draff_Checkbox( '@filterTourn', 'Tournaments' , 1,'1','0') );
    $form->drForm_addField( new Draff_Checkbox( '@filterCamp' ,'Camps', 1,'1','0') );
    $form->drForm_addField( new Draff_Checkbox( '@filterOther' ,'Other', 1,'1','0') );
}

static function common_standardFooter_define($form) {
    $form->drForm_define_button ('@back','Back');
    $form->drForm_define_button ('@myEvents','My<br>Events');
    $form->drForm_define_button ('@allEvents','All<br>Events');
    $form->drForm_define_button ('@allSemesters',"All<br>Semesters");
}


// from gateway-old
function apd_view_getData( $appGlobals ) {
    $this->apd_schedule_argDate = draff_urlArg_getOptional('date','');  // url argument - not posted value (due to use of kcm1 code)
    $this->apd_schedule_argAction = draff_urlArg_getOptional('week','');  // url argument - not posted value (due to use of kcm1 code)
}

function getStaffName($appGlobals) {
    $query = "SELECT `sSt:ShortName`,`sSt:FirstName`,`sSt:LastName` FROM `st:staff` WHERE `sSt:StaffId` = '".rc_getStaffId()."'";
    $result = $appGlobals->gb_pdo->rsmDbe_execute( $query );
    //$result = $appGlobals->gb_db->rc_query( $query );
    //if ( $result === FALSE) {
    //    $appGlobals->gb_sql->sql_errorTerminate( $query);
    //}
    if ( $row=$result->fetch()) {
        return $row['sSt:FirstName'] . ' ' . $row['sSt:LastName'];
    }
    else
        return '??';
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

$appChain = new Draff_Chain( 'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmGateway_globals());
$appChain->chn_register_appData( new application_data());
$appGlobals->gb_forceLogin ();

$appChain->chn_form_register(DRFFORM_MY_SCHEDULE,'appForm_mySchedule');
$appChain->chn_form_register(DRFFORM_PANEL_SMREPORT,'appForm_panel_smReport');
$appChain->chn_form_register(DRFFORM_PANEL_EVENT,'appForm_panel_event');


//$mode = draff_urlArg_getOptional('mode', NULL);
//if ($mode !== NULL) {
//    $appChain->chn_form_setCurrent($mode);
//}


$appChain->chn_form_launch(); // proceed to current step

exit;

?>