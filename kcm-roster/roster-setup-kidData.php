<?php

//--- roster-setup-kidData.php ---

ob_start();  // output buffering (needed for redirects, draff-appHeader changes)

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

include_once( 'roster-system-functions.inc.php' );
include_once( 'roster-system-globals.inc.php' );
include_once( 'roster-system-data-roster.inc.php');

//@@@@@@@@@@@@@@@@@@@@
//@  Step 1
//@@@@@@@@@@@@@@@@@@@@

class appForm_kidSetup_select extends Draff_Form {  // specify winner

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChaint );
    $appData->apd_select_getData( $appGlobals, $appChain );
    if (  $appChain->chn_submit[0]=='@gaWin' ) {
        $appData->apd_select_submit( $appGlobals, $appChain, $v[1] );
        $appChain->chn_launch_newChain(2);
    }
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-select' );
    $appEmitter->set_title('Kid Data');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
   // $appEmitter->set_title('Kid Data');
   // $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
   // $appEmitter->set_menu_customize( $appChain, $appGlobals  );
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_select_getData( $appGlobals, $appChain );
    kcmRosterLib_kidList_buttons_define($this, $appGlobals, $appData->apd_roster_program ,'gaWin' );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-content-default');
    kcmRosterLib_kidList_buttons_emit($appGlobals, $appData->apd_roster_program, $appEmitter, 'gaWin');
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}  // end class

//@@@@@@@@@@@@@@@@@@@@
//@  Step 2
//@@@@@@@@@@@@@@@@@@@@

class appForm_kidSetup_edit extends Draff_Form {

function drForm_processSubmit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );
    if ($appChain->chn_submit[0] == '@cancel') {
        $message = 'Cancelled changes for '  . $this->status->get('#recordDesc');
        $appChain->chn_launch_cancelChain(1,$message);
    }
    $appChain->chn_form_savePostedData();
    $appData->apd_edit_getData( $appGlobals, $appChain );
    if ($appChain->chn_submit[0] == '@save') {
        $succeeded = $appData->apd_edit_submit( $appGlobals, $appChain );
        if ($succeeded) {
            $appChain->chn_launch_restartAfterRecordSave(1);
        }
        else {
            $appChain->chn_launch_continueChain(2);
        }
        return;
    }
    $appChain->chn_form_launch();
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->set_theme( 'theme-select' );
    $appEmitter->set_title('Kid Data');
    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program );
    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
//    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'');
//    $appEmitter->set_title('Kid Data');
//    $appGlobals->gb_appMenu_init($appChain, $appEmitter, $appData->apd_roster_program);
//    $appEmitter->set_menu_customize( $appChain, $appGlobals  );
//    $appData->apd_edit_report->stdRpt_initOutput( $appData, $appGlobals, $appChain , $appEmitter, $this);
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_edit_getData( $appGlobals, $appChain );
    $appData->apd_edit_report->stdRpt_initControls( $appData, $appGlobals, $appChain , $this);
    $this->drForm_addField( new Draff_RadioGroup ('@pickGroup',  'Pickup Code', $appData->apd_edit_kidProgram->kidPrg_pickupCode, 'x' ) );
    $this->drForm_addField( new Draff_RadioItem ('@pickAsp',    $appData->apd_edit_kidProgram->kidPrg_pickupCode, '@pickGroup', 'ASP','1') );
    $this->drForm_addField( new Draff_RadioItem ('@pickParent', $appData->apd_edit_kidProgram->kidPrg_pickupCode, '@pickGroup', 'Parent','2' ) );
    $this->drForm_addField( new Draff_RadioItem ('@pickWalker', $appData->apd_edit_kidProgram->kidPrg_pickupCode, '@pickGroup', 'Walker','3' ) );
    $this->drForm_addField( new Draff_RadioItem ('@pickOther',  $appData->apd_edit_kidProgram->kidPrg_pickupCode, '@pickGroup', 'Other','90' ) );
    $this->drForm_addField( new Draff_RadioItem ('@pickVaries', $appData->apd_edit_kidProgram->kidPrg_pickupCode, '@pickGroup', 'Varies','91' ) );
    $intagProps = array('@propType'=>'inputTag','class'=>'');
    $this->_addField( new Draff_Text('@ad_kid_teacher',$appData->apd_edit_kidProgram->kidPrg_teacher,$intagProps);
    $this->_addField( new Draff_Text('@note',$appData->apd_edit_kidProgram->kidPrg_pickupNotes,$intagProps);
    $this->drForm_addField( new Draff_Button( '@save' , 'Save' ) );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
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
    $appEmitter->zone_start('draff-zone-content-default');
    print PHP_EOL.'<div class="draff-appHeader">';
    print PHP_EOL;

    $appData->apd_edit_report->stdRpt_emit( $appData, $appGlobals, $appChain , $appEmitter, $this);

    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}  // end class

class appData_kidSetup extends draff_appData {
public $apd_roster_program;
public $apd_roster_period;
public $apd_edit_kidProgram;
public $apd_edit_parents;
public $apd_edit_kidPeriodId = NULL;
public $apd_edit_kidProgramId = NULL;
public $apd_edit_report;

function __construct($appGlobals) {
    $this->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
    $this->apd_roster_program->rst_load_kids($appGlobals, $this->apd_edit_kidPeriodId);
    $this->apd_roster_period  = $this->apd_roster_program->rst_get_period();
}

function apd_select_getData( $appGlobals, $appChain ) {
    $this->apd_roster_program->rst_load_kids($appGlobals);
}

function apd_select_submit( $appGlobals, $appChain, $kidPeriodId ) {
    $appChain->chn_data_posted_set('#kidPeriodId', $kidPeriodId);
    return TRUE; // submit succeeded
}


function apd_edit_getData( $appGlobals, $appChain ) {
    $this->apd_edit_kidPeriodId = $appChain->chn_data_posted_get ('#kidPeriodId');
    $kidPeriod = $this->apd_roster_period->perd_get_kidPeriod($this->apd_edit_kidPeriodId);
    $this->apd_kid = $this->apd_roster_program->rst_get_kid($kidPeriod->kidPer_kidId);
    // do not have kid duplicate name info if only one kid is read (not critical here)
    //$this->apd_kid = $this->apd_roster_program->rst_get_kidObject(ROSTERKEY_KIDPERIODID, $this->apd_edit_kidPeriodId);
    $this->apd_roster_program->rst_load_parents($appGlobals, ROSTERKEY_KIDPERIODID, $this->apd_edit_kidPeriodId);
    $this->apd_edit_kidProgramId = $this->apd_kid->rstKid_kidProgramId;
    $this->apd_edit_kidProgram = $this->apd_roster_program->rst_get_kidProgram($this->apd_edit_kidProgramId);
    $this->apd_edit_parents = $this->apd_kid->rstKid_parent;
    $appChain->chn_posted_read('@pickGroup',$this->apd_edit_kidProgram->kidPrg_pickupCode );
    $appChain->chn_posted_read('@note',$this->apd_edit_kidProgram->kidPrg_pickupNotes);
    $appChain->chn_posted_read('@ad_kid_teacher',$this->apd_edit_kidProgram->kidPrg_teacher    );
    $this->status->set('#recordDesc',$this->apd_kid->rstKid_firstName . ' ' . $this->apd_kid->rstKid_lastName); // used for messages
}

function apd_edit_submit( $appGlobals, $appChain ) {
    if ( !$appChain->chn_messages->ses_arrayIsEmpty();
        return FALSE; // submit did not succeed
    }
    $this->apd_edit_saveData( $appGlobals, $appChain );
    return TRUE; // submit succeeded
}

function apd_edit_saveData( $appGlobals, $appChain ) {
    $q = new rc_saveToDbQuery( $appGlobals->gb_db, 'ro:kid_program', "UPDATE" );
    $q->setFieldVal( 'rKPr:TeacherName', $this->apd_edit_kidProgram->kidPrg_teacher );
    $q->setFieldVal( 'rKPr:PickupCode', $this->apd_edit_kidProgram->kidPrg_pickupCode );
    $q->setFieldVal( 'rKPr:PickupNotes', $this->apd_edit_kidProgram->kidPrg_pickupNotes );
    $q->setModByAndWhen( 'rKPr:' );
    $q->buildWhere( 'rKPr:KidProgramId', $this->apd_edit_kidProgramId );
    $q->setCopyToHistoryBeforeAfter();
    $result = $q->doQuery();
    if ($result === FALSE) {
        $dbFailed = TRUE;
    }
    $this->status->set('#recordWhenSaved',draff_getMicroTime());
    $appChain->chn_message_set('Kid data changed for '.$this->apd_kid->rstKid_firstName . ' ' . $this->apd_kid->rstKid_lastName);
}

} // end class

class stdReport_kidSetup_edit {

function stdRpt_initControls( $appData, $appGlobals, $appChain, $form ) {
    $form->drForm_addField( new Draff_RadioGroup ('@pickGroup',  'Pickup Code', $appData->apd_edit_kidProgram->kidPrg_pickupCode, 'x' ) );
    $form->drForm_addField( new Draff_RadioItem ('@pickAsp',    $appData->apd_edit_kidProgram->kidPrg_pickupCode, '@pickGroup', 'ASP','1'))
    $form->drForm_addField( new Draff_RadioItem ('@pickParent', $appData->apd_edit_kidProgram->kidPrg_pickupCode, '@pickGroup', 'Parent','2' ));
    $form->drForm_addField( new Draff_RadioItem ('@pickWalker', $appData->apd_edit_kidProgram->kidPrg_pickupCode, '@pickGroup', 'Walker','3' ));
    $form->drForm_addField( new Draff_RadioItem ('@pickOther',  $appData->apd_edit_kidProgram->kidPrg_pickupCode, '@pickGroup', 'Other','90' ));
    $form->drForm_addField( new Draff_RadioItem ('@pickVaries', $appData->apd_edit_kidProgram->kidPrg_pickupCode, '@pickGroup', 'Varies','91' ));
    $intagProps = array('@propType'=>'inputTag','class'=>'');
    $form->_addField( new Draff_Text('@ad_kid_teacher',$appData->apd_edit_kidProgram->kidPrg_teacher,$intagProps));
    $form->_addField( new Draff_Text('@note', $appData->apd_edit_kidProgram->kidPrg_pickupNotes,$intagProps);
    $this->drForm_addField( new Draff_Button( '@save' , 'Save' ) );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
}

function stdRpt_initOutput( $appData, $appGlobals, $appChain, $appEmitter, $form ) {
}

function stdRpt_emit( $appData, $appGlobals, $appChain, $appEmitter, $form ) {
    $appEmitter->table_start('draff-edit',2);

    $appEmitter->table_head_start('draff-edit-head');
    $appEmitter->row_oneCell($appData->apd_kid->rstKid_firstName . ' ' . $appData->apd_kid->rstKid_lastName);
    $appEmitter->table_head_end();

    $appEmitter->table_body_start('rpt-panel-body');

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Pickup');
    $appEmitter->cell_block(array ('@pickAsp', '@@' , '@pickParent', '@@' , '@pickWalker', '@@' , '@pickOther', '@@' , '@pickVaries'));
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Teacher');
    $appEmitter->cell_block('@ad_kid_teacher');
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Note');
    $appEmitter->cell_block('@note');
    $appEmitter->row_end();

    $count = count($appData->apd_edit_parents->rstParent_name);
    for ($i=0; $i<$count; ++$i) {
        $desc = ($i==0) ? 'Primary Parent Contact' : 'Secondary Parent Contact';
        $appEmitter->table_body_end();
        $appEmitter->table_head_start('draff-edit-head');
        $appEmitter->row_oneCell($desc);
        $appEmitter->table_head_end();
        $appEmitter->table_body_start('rpt-panel-body');

        $appEmitter->row_start('rpt-panel-row');
        $appEmitter->cell_block('Name' , 'draff-edit-fieldDesc' );
        $appEmitter->cell_block($appData->apd_edit_parents->rstParent_name[$i], 'draff-edit-fieldData' );
        $appEmitter->row_end();

       $appEmitter->row_start('rpt-panel-row');
       $appEmitter->cell_block('Home Phone'  , 'draff-edit-fieldDesc' );
       $appEmitter->cell_block($appEmitter->getString_phone($appData->apd_edit_parents->rstParent_home[$i]), 'draff-edit-fieldData' );
       $appEmitter->row_end();

        $appEmitter->row_start('rpt-panel-row');
        $appEmitter->cell_block('Cell Phone' , 'draff-edit-fieldDesc' );
        $appEmitter->cell_block($appEmitter->getString_phone($appData->apd_edit_parents->rstParent_cell[$i]) , 'draff-edit-fieldData' );
        $appEmitter->row_end();

        $appEmitter->row_start('rpt-panel-row');
        $appEmitter->cell_block('Work Phone', 'draff-edit-fieldDesc' );
        $appEmitter->cell_block($appEmitter->getString_phone($appData->apd_edit_parents->rstParent_work[$i]) , 'draff-edit-fieldData' );
        $appEmitter->row_end();

    }
    $appEmitter->table_body_end();
    $appEmitter->table_head_start('draff-edit-head');
    $appEmitter->row_oneCell('Emergency Contact');
    $appEmitter->table_head_end();
    $appEmitter->table_body_start('rpt-panel-body');

        $appEmitter->row_start('rpt-panel-row');
        $appEmitter->cell_block('Emergency Contact' , 'draff-edit-fieldDesc' );
        $appEmitter->cell_block($appData->apd_edit_parents->rstParent_emergency_name , 'draff-edit-fieldData' );
        $appEmitter->row_end();

        $appEmitter->row_start('rpt-panel-row');
        $appEmitter->cell_block('Emergency Phone' , 'draff-edit-fieldDesc' );
        $appEmitter->cell_block($appEmitter->getString_phone($appData->apd_edit_parents->rstParent_emergency_phone), 'draff-edit-fieldData' );
        $appEmitter->row_end();

    $appEmitter->table_body_end();

    $appEmitter->table_foot_start();
    $appEmitter->row_oneCell(array('@save','@cancel'));
    $appEmitter->table_foot_end();

    $appEmitter->table_end();
}

}


//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appGlobals = new kcmRoster_globals();  // extended kcmKernal_globals
$appGlobals->gb_forceLogin ();
$appData = new appData_kidSetup($appGlobals);
$appChain = new Draff_Chain( $appData, $appGlobals, 'kcmKernel_emitter' );

$appChain->chn_form_register(1,'appForm_kidSetup_select');
$appChain->chn_form_register(2,'appForm_kidSetup_edit');
$appChain->chn_form_launch(); // proceed to current form

exit;

?>