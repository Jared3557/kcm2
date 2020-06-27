<?php

//--- roster-results-points.php ---

ob_start();  // output buffering (needed for redirects, draff-appHeader changes)

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

include_once( 'roster-system-functions.inc.php' );
include_once( 'roster-system-globals.inc.php' );
include_once( 'roster-system-data-roster.inc.php');

include_once( 'roster-system-data-points.inc.php' );
//include_once( 'roster-results-points-edit.inc.php' );

define ('FORM_POINTS_PLAYERS',1);
define ('FORM_POINTS_ENTER',2);
define ('FORM_POINTS_EDIT',3);
define ('FORM_POINTS_HISTORY',4);

//@@@@@@@@@@@@@@@@@@@@
//@  Step 1
//@@@@@@@@@@@@@@@@@@@@

class appForm_points_player  extends kcmKernel_Draff_Form {

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//}

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain );

    //$appChain->chn_form_savePostedData();
    //$appChain->chn_ValidateAndRedirectIfError();
    if ($appChain->chn_submit[0] == '@edit') {
        $this->status->set('#pointId',$appChain->chn_submit[1]);
        $appChain->chn_launch_newChain(FORM_POINTS_EDIT);
        // $appChain->chn_dispatch ( FORM_POINTS_EDIT, DRAFF_TYPE_CHAIN_NONE, DRAFF_TYPE_DISPATCH_REDIRECT , ['#mode'=>'e'] );
        return;
    }

    if (  isset($appChain->chn_submit[1]) and is_numeric($appChain->chn_submit[1]) ) {
        $this->status->set('#kidPeriodId',$appChain->chn_submit[1]);
        $appChain->chn_launch_newChain(2);
    }
    $appChain->chn_launch_continueChain(1);

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Enter Points');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize( '$results' );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Enter Points');
     $appEmitter->emit_nrLine('');
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
   // $appGlobals->gb_form->define_button(array('gaMode','draw') , 'Game was draw',   array('propType'=>'inputTag','class'=>'titleButton') );
     kcmRosterLib_kidList_buttons_define($this, $appGlobals, $appData->apd_roster_program,  'points');
     //game_kidButtons_define($appGlobals, 'gaWin');
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    print PHP_EOL.'<div class="draff-appHeader">';

    print 'Select player to get the points';
    print PHP_EOL . '</div>';
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-content-default');
    kcmRosterLib_kidList_buttons_emit($appGlobals, $appData->apd_roster_program, $appEmitter, 'points');
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}

//@@@@@@@@@@@@@@@@@@@@
//@  Step 2
//@@@@@@@@@@@@@@@@@@@@

class appForm_points_enter extends kcmKernel_Draff_Form {

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );

    if ($appChain->chn_submit[0] == 'cancel') {
        $appChain->chn_launch_cancelChain(1);
        return;
    }

    $appChain->chn_form_savePostedData();
    if ($appChain->chn_submit[0] == '@points') {
        $appData->apd_formData_get( $appGlobals, $appChain );
        $appData->apd_points->pnt_pointValue = $this->step_init_submit_suffix;
        $pointId = $appData->apd_points->pnt_classRecord_write($appGlobals);
        $kidName = $appData->apd_roster_program->rst_cur_period->perd_getKidPeriodObject($appData->apd_kidPeriodId)->kidPer_kidObject->rstKid_uniqueName;
        $class='';
        $value = '@edit_' . $pointId;
        $button = '--WIP-for-Button-Link--';
        //$url = $appChain->chn_url_build_chained_url(NULL, TRUE, $appGlobals->gb_kernelOverride_getStandardUrlArgList());
        //$button =  '<form  action="'.$url.'" method="post"><button type="submit" '. $class . ' name="submit" value="'.$value.'">Edit</button></form>';
        $appChain->chn_message_set("Saved {$appData->apd_points->pnt_pointValue} points for {$kidName} {$button}");
        $appChain->chn_launch_restartAfterRecordSave(1);
    }

    $appChain->chn_ValidateAndRedirectIfError();

    $appChain->chn_launch_continueChain(1);

}

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $appData->apd_kidPeriodId =  $this->step_getShared('#kidPeriodId',NULL);
//    krnLib_assert($appData->apd_kidPeriodId<>0,__FILE__,__LINE__);
//    $appData->apd_points = new stdData_pointsUnit_record;
//    $appData->apd_points->pnt_init($appGlobals, $appData->apd_roster_program ,GAME_ORIGIN_CLASS, $this->apd_kidPeriodId, $appData->apd_roster_program->rst_classDateObject->cSD_classDate);
//    $appData->step_updateIfPosted('@noteText', $appData->apd_points->pnt_note);
//    $appData->step_updateIfPosted('@pointCombo', $appData->apd_points->pnt_pointValue);
//    $appData->step_updateIfPosted('@catCombo', $appData->apd_points->pnt_category);
//    $appData->apd_points->pnt_category = trim($appData->apd_points->pnt_category);  // important - key for empty is ' ', not ''
//
//    $appData->apd_combo_categories    = kcmRosterLib_getCombo_pointCategories($appGlobals, $appData->apd_roster_program,$this->apd_points->pnt_category);
//    $appData->apd_combo_points = kcmRosterLib_getCombo_pointValues($appGlobals);
//    if ($appData->apd_points->pnt_category=='') {
//        $appData->apd_points->pnt_category = ' ';
//    }
//}

//function drForm_validate( $appData, $appGlobals, $appChain ) {
//
//    return;   //?????????????????????
//
//    $ses = $formData;
//    if ($this->apd_points->pnt_pointValue == 0) {
//        $appChain->chn_theme-message-error-error_setFormError('You must select number of points');
//        return;
//    }
//    if ($ses->categoryIndex == '@none') {
//        $this->apd_points->pnt_category = '';
//    }
//    else if ($ses->categoryIndex<900) {
//        $this->apd_points->pnt_category = $appData->apd_roster_program->rst_program->prog_pointCategories[$ses->categoryIndex];
//    }
//    $this->apd_points->originCode = GAME_ORIGIN_CLASS;
//    $pointId = $this->apd_points->pnt_classRecord_write($appGlobals);
//    $editUrl =  $this->appChain->chn_url_build_chained_url(NULL, TRUE, array('chRec'=>$pointId,'chStep'=>11) );
//    $s = '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . $editUrl . '">Edit</a>';
//    $savedPoints = new stdData_pointsUnit_record;
//    $savedPoints->pnt_classRecord_read($appGlobals, $pointId);
//    $this->chn_theme-message-error-error_setStatus( $savedPoints->pnt_getResultString($appGlobals, $savedPoints->pnt_pointValue) . $s);
//    $this->appChain->chn_stream_destroy();
//}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-header-default');
    $kidPeriod = $appData->apd_roster_program->rst_cur_period->perd_getKidPeriodObject($appData->apd_kidPeriodId);
    $kidId = $kidPeriod->kidPer_kidId;
    $kid = $appData->apd_roster_program->rst_get_kid ($kidId);
    $appEmitter->emit_nrLine('Points for ' . $kid->rstKid_uniqueName);
    $appEmitter->zone_end();

    $appEmitter->zone_start('draff-zone-filters-default');
    $appEmitter->emit_nrLine('<div class="sy-content-footer">');
    $appEmitter->emit_nrLine($this->drForm_gen_field('@cancel'));
    $appEmitter->emit_nrLine(PHP_EOL . '</div>');
    $appEmitter->zone_end();
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Enter Points');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize ( '$results' );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Enter Points');
     // $appEmitter->zone_messages($appChain, $form);
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->apd_formData_get( $appGlobals, $appChain );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel', array('class'=>'titleButton') ) );

    for ($i=1; $i<=10; ++$i) {
        $fieldId = 'points' . '_' . $i;
        $this->drForm_addField( new Draff_Button( '@' . $fieldId , $i , array('class'=>'draff-button-select')) );
    }
    $intagProps = array('propType'=>'inputTag','class'=>'draff-radio-norm');
    $this->drForm_addField( new Draff_Combo( '@catCombo' , $appData->apd_points->pnt_category, $appData->apd_combo_categories) );
    $this->drForm_addField( new Draff_Combo('@pointCombo', $appData->apd_points->pnt_pointValue, $appData->apd_combo_points) );
    $this->drForm_addField( new Draff_Text('@noteText', $appData->apd_points->pnt_note));

    $this->drForm_addField( new Draff_Button( '@submit' , 'Submit') );
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-content-default');

    //$dataPanel = new draff_emitter_panel(2,$form);
    //$dataPanel->rsmPanel_start('Quick Point Entry');
    $s = '';
    for ($i=1; $i<=10; ++$i) {
        $fieldId = '@points' . '_' . $i;
        $s .= $this->drForm_gen_field($fieldId );
        if ($i==5) {
            $s .= '<br>';
        }
    }
    //$dataPanel->rsmPanel_sectionHeader($s);
    //$dataPanel->rsmPanel_end();

    $appEmitter->table_start('draff-edit',2);

    $appEmitter->table_head_start('draff-edit-head');
    $appEmitter->row_oneCell('Quick Point Entry');
    $appEmitter->table_head_end();

    $appEmitter->table_body_start('rpt-panel-body');
    $appEmitter->row_oneCell( $s);
    $appEmitter->table_body_end();

    $appEmitter->table_head_start('draff-edit-head');
    $appEmitter->row_oneCell('Advanced Point Entry');
    $appEmitter->table_head_end();

    $appEmitter->table_body_start('rpt-panel-body');

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Points: ','draff-edit-fieldDesc');
    $appEmitter->cell_block( '@pointCombo','draff-edit-fieldData');
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Category: ','draff-edit-fieldDesc');
    $appEmitter->cell_block('@catCombo','draff-edit-fieldData');
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block( 'Note: ','draff-edit-fieldDesc');
    $appEmitter->cell_block('@noteText','draff-edit-fieldData');
    $appEmitter->row_end();

    $appEmitter->table_body_end();

    $appEmitter->table_foot_start();
    $appEmitter->row_oneCell(array('@submit','@cancel'));
    $appEmitter->table_foot_end();

    $appEmitter->table_end();

    $appEmitter->zone_end('sy-genre-default');
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}  // end class

class appForm_points_edit extends kcmKernel_Draff_Form {
public $edit_kidPeriodId;
public $edit_points;
public $edit_catCombo;
public $edit_catKey;
public $edit_catValue;
public $edit_pointsCombo;

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
    if ($submit == '@cancel') {
       $appChain->chn_launch_cancelChain(1,'');
       return;
    }
    $appChain->chn_form_savePostedData();
    if ($submit == '@delete') {
        $this->edit_points->pnt_record_delete($appGlobals);
        $appChain->chn_message_set('Deleted Point Record'); //???? more specific theme-message-error-error ??????
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_restartAfterRecordSave(1);
        return;
    }
    if ($submit == '@submit') {
        $appChain->chn_ValidateAndRedirectIfError();
        $pointId = $this->edit_points->pnt_classRecord_write($appGlobals);
        $this->edit_points->pnt_record_write($appGlobals);
        $appChain->chn_message_set( $this->edit_points->pnt_getResultString($appGlobals, $this->edit_points->pnt_pointValue) . $s);
        $appChain->chn_curStream_Clear();
        $appChain->chn_launch_restartAfterRecordSave(1);
    }
    $appChain->chn_launch_continueChain(1);
}

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $pointId =  $this->step_getShared('#pointId',NULL);
//    krnLib_assert($pointId<>0,__FILE__,__LINE__);
//    $this->edit_points = new stdData_pointsUnit_record;
//    $this->edit_points->pnt_record_read($appGlobals, $pointId);
//    $this->step_updateIfPosted('@noteText', $this->edit_points->pnt_note);
//    $this->step_updateIfPosted('@pointCombo', $this->edit_points->pnt_pointValue);
//    $this->step_updateIfPosted('@catCombo', $this->edit_points->pnt_category);
//    $this->edit_points->pnt_category = trim($this->edit_points->pnt_category);  // important - key for empty is ' ', not ''
//
//    $this->edit_catCombo    = kcmRosterLib_getCombo_pointCategories($appGlobals, $appData->apd_roster_program,$this->edit_points->pnt_category);
//    $this->edit_pointsCombo = kcmRosterLib_getCombo_pointValues($appGlobals);
//    if ($this->edit_points->pnt_category=='') {
//        $this->edit_points->pnt_category = ' ';
//    }
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-select' );
    $appEmitter->emit_options->set_title('Enter Points');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize( '$results' );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Edit Points');
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {

    $this->drForm_addField( new Draff_Combo( '@catCombo' , $this->edit_points->pnt_category, $this->edit_catCombo) );
    $this->drForm_addField( new Draff_Combo('@pointCombo', $this->edit_points->pnt_pointValue, $this->edit_pointsCombo) );
    $form->_addField( new Draff_Text('@noteText',  $this->edit_points->pnt_note));


    $this->drForm_addField( new Draff_Button( '@submit' , 'Submit') );
    $this->drForm_addField( new Draff_Button( '@delete' , 'Delete') );
    $this->drForm_addField( new Draff_Button( '@cancel' , 'Cancel') );
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-select');
    $name = $appData->apd_roster_program->rst_cur_period->perd_getKidPeriodObject($this->edit_points->pnt_kidPeriodId)->kidPer_kidObject->rstKid_uniqueName;
    $date = draff_dateAsString( $this->edit_points->pnt_classDate, 'M j');

    $appEmitter->table_start('draff-edit',2);

    $appEmitter->table_head_start('draff-edit-head');
    $appEmitter->row_oneCell( "{$name} on {$date} - Edit Points");
    $appEmitter->table_head_end();


    $appEmitter->table_body_start('rpt-panel-body');

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Points: '   , 'draff-edit-fieldDesc' );
    $appEmitter->cell_block('@pointCombo', 'draff-edit-fieldData' );
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block('Category: ', 'draff-edit-fieldDesc' );
    $appEmitter->cell_block('@catCombo' , 'draff-edit-fieldData' );
    $appEmitter->row_end();

    $appEmitter->row_start('rpt-panel-row');
    $appEmitter->cell_block( 'Note: '   , 'draff-edit-fieldDesc' );
    $appEmitter->cell_block( '@noteText', 'draff-edit-fieldData' );
    $appEmitter->row_end();

    $appEmitter->table_body_end();

    $appEmitter->table_foot_start();
    $appEmitter->row_oneCell(array('@submit','@cancel','@delete'));
    $appEmitter->table_foot_end();

    $appEmitter->table_end();
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}  // end class

class appForm_points_history extends kcmKernel_Draff_Form {  // specify winner

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );

    $appChain->chn_form_savePostedData();
    $appChain->chn_ValidateAndRedirectIfError();
    if (  is_numeric($this->step_init_submit_suffix) ) {
        //$form->appChain->chn_arg_recordId = $form->appChain->chn_submit_index;
        //$form->appChain->chn_url_registerArgument(URL_SUBMIT,'chRec', $form->appChain->chn_arg_recordId);
        $this->step_setShared('#pointId',$this->step_init_submit_suffix);
        $appChain->chn_launch_continueChain(FORM_POINTS_EDIT);
        return;
    }
    $appChain->chn_launch_continueChain();

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-report' );
    $appEmitter->emit_options->set_title('Enter Points');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program);
    $appGlobals->gb_menu->drMenu_customize( '$results' );
    kcmRosterLib_setBannerSubTitle($appEmitter,$appGlobals, $appData->apd_roster_program,'Points History');
 
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $appData->com_initialize($appGlobals);
    $appData->com_read_pointsBundle($appGlobals);
    $this->drForm_addField( new Draff_Combo( '@kidCombo' ,  $appData->kidFilter, $appData->kidChoices ) );
    $this->drForm_addField( new Draff_Combo( '@dateCombo' , $appData->dateFilter, $appData->dateChoices ) );
    $this->drForm_addField( new Draff_Button( '@update','Update') );
    foreach ($appData->com_pointsBundle->pnt_pointUnit_map as $points) {
        $kidUniqueName = $appData->apd_roster_program->rst_cur_period->perd_getKidUniqueName($points->pnt_kidPeriodId);
        $fieldId = '@pointId_'.$points->pnt_pointsId;
        $this->drForm_define_linkButton($fieldId,$kidUniqueName);
    }
}

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $appData->com_initialize($appGlobals);
//    $appData->com_read_pointsBundle($appGlobals);
//}
//
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//}

//function outFilterOptions($appEmitter,$appGlobals) {
//    $appEmitter->kcm_filter_divStart('Point History Filters');
//    $appEmitter->kcm_filter_control('Kids: ',$appGlobals->gb_form->getHtml_combo( 'kidCombo', $this->retainedData->kidFilter ));
//    $appEmitter->kcm_filter_control('Class Date: ', $appGlobals->gb_form->getHtml_combo( 'dateCombo', $this->retainedData->dateFilter ) );
//    $appEmitter->kcm_filter_divEnd($appGlobals->gb_form->getHtml_Button('Update'));
//}

function outReport($appData, $appEmitter, $form, $appGlobals) {
    $appEmitter->table_start('',6);
    $appEmitter->row_start();
    $appEmitter->emit_nrLine('<td class="report-title rpt-hdr" colspan="99">Point History</td>');  //@?@?@?@?
    $appEmitter->row_end();
    $appEmitter->row_end();
    $appEmitter->row_start();
    $appEmitter->cell_block( 'Source','rpt-integer rpt-hdr');
    $appEmitter->cell_block( 'Class Date','rpt-date rpt-hdr');
    $appEmitter->cell_block( 'Kid','rpt-kidName rpt-hdr');
    $appEmitter->cell_block( 'Points','rpt-integer rpt-hdr');
    $appEmitter->cell_block( 'Edit','rpt-editLink rpt-hdr');
    $appEmitter->cell_block( 'Category<br>Notes','rpt-notes rpt-hdr');
    $appEmitter->row_end();
    foreach ($appData->com_pointsBundle->pnt_pointUnit_map as $points) {
        $kidUniqueName = $appData->apd_roster_program->rst_cur_period->perd_getKidUniqueName($points->pnt_kidPeriodId);
        $fieldId = '@pointId_'.$points->pnt_pointsId;
        $appEmitter->row_start();
        $org = kcmRosterLib_getDesc_originCode($points->pnt_originCode);
        $appEmitter->cell_block( $org);
        $appEmitter->cell_block(  draff_dateAsString($points->pnt_classDate, 'M j'),'rpt-date' );
        $appEmitter->cell_block( $kidUniqueName,'rpt-kidName' );
        $appEmitter->cell_block( $points->pnt_pointValue,'rpt-integer' );
        //$appEmitter->cell_start();
        //$form->drForm_field_out($fieldId);
        //$appEmitter->cell_end();
        $appEmitter->cell_block(  $appEmitter->getString_button('Edit','','kpid_'. $points->pnt_pointsId ) , 'rpt-editLink'  );
        $appEmitter->cell_block($points->pnt_category . '<br>' . $points->pnt_note,'rpt-notes' );
        $appEmitter->row_end();
    }
    $appEmitter->table_end();
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-filters-default');
    //$this->outFilterOptions($appEmitter,$appGlobals);
    $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-content-default');
    $this->outReport( $appData, $appEmitter, $this, $appGlobals );
    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}  // end class

class application_data extends draff_appData {
public $apd_roster_program;
public $apd_roster_period;
public $apd_kidPeriodId;
public $apd_pointsId = 0;
public $apd_points;
public $apd_catKey;
public $apd_catValue;
public $apd_combo_points;
public $apd_combo_categories;

function __construct($appGlobals) {
 //   $this->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
 //   $this->apd_roster_program->rst_load_kids($appGlobals);  //???? or later ????
 //   $this->apd_roster_period  = $this->apd_roster_program->rst_get_period();
}


function apd_formData_get( $appGlobals, $appChain ) {
    $this->apd_kidPeriodId = $this->status->get('#kidPeriodId');
    $this->apd_points = new stdData_pointUnit_item;
    $this->apd_points->pnt_init($appGlobals, $this->apd_roster_program, GAME_ORIGIN_CLASS, $this->apd_kidPeriodId, $this->apd_roster_program->rst_classDateObject);  //???? was  ->cSD_classDate
    if ($this->apd_pointsId >= 1) {
        $this->apd_points->pnt_classRecord_read($appGlobals, $this->apd_pointsId);
    }

    $appChain->chn_posted_read('@noteText', $this->apd_points->pnt_note);
    $appChain->chn_posted_read('@pointCombo', $this->apd_points->pnt_pointValue);
    $appChain->chn_posted_read('@catCombo', $this->apd_points->pnt_category);
    $this->apd_points->pnt_category = trim($this->apd_points->pnt_category);  // important - key for empty is ' ', not ''

    $this->apd_combo_categories    = kcmRosterLib_getCombo_pointCategories($appGlobals, $this->apd_roster_program,$this->apd_points->pnt_category);
    $this->apd_combo_points = kcmRosterLib_getCombo_pointValues($appGlobals);
    if ($this->apd_points->pnt_category=='') {
        $this->apd_points->pnt_category = ' ';
    }
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

} // end class

class data_points extends draff_appData {
   public $kidFilter = NULL;
   public $dateFilter = NULL;
   public $kidChoices = array();
   public $dateChoices = array();
   public $kcmEmitter = NULL;
   public $com_pointsBundle = NULL;

function __construct() {
}

function apd_formData_get( $appGlobals, $appChain ) {
}

function apd_formData_validate( $appGlobals, $appChain ) {
}


function com_read_pointsBundle($appGlobals) {
    $this->com_pointsBundle = new kcm2_pntBu_points_bundle();
    $this->com_pointsBundle->pntBu_read_pointsBundle($appGlobals,$this->kidFilter,$this->dateFilter);
}

function com_initialize($appGlobals) {
    $this->kidChoices = kcmRosterLib_getCombo_kidPeriod($appGlobals, $this->apd_roster_program,'@all');
    $this->dateChoices = kcmRosterLib_getCombo_classDates($appGlobals, $this->apd_roster_program,'@all');
}

} // end class

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

rc_session_initialize();

$appChain = new Draff_Chain(  'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmRoster_globals());
$appChain->chn_register_appData( $appData = new application_data());
$appGlobals->gb_forceLogin ();

$appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);

$appChain->chn_form_register(FORM_POINTS_PLAYERS,'appForm_points_player');
$appChain->chn_form_register(FORM_POINTS_ENTER,'appForm_points_enter');
$appChain->chn_form_register(FORM_POINTS_EDIT,'appForm_points_edit');
$appChain->chn_form_launch(); // proceed to current step

exit;

?>