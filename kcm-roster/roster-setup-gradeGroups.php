<?php

//-----  roster-setup-gradeGroups.php -----

ob_start();  // output buffering (needed for redirects, header changes)

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


//=     End of main program        ==
//=   Below are funcs and classes  ==
//===================================

class appForm_setupGradeGroups_main extends kcmKernel_Draff_Form {

//function step_init_submit_accept( $appData, $appGlobals, $appChain ) {
//    $appData->apd_initialize($appGlobals);
//}
//function drForm_validate( $appData, $appGlobals, $appChain ) {
//    $curPeriod = $appGlobals->gbx_roster->rst_cur_period;
//    $gradeGroups = $curPeriod->perd_gradeGroups;
//    if ($this->step_init_submit_fieldId=='setDefaults') {
//        $gradeGroups->grdgp_setDefaults();
//        $this->apd_saveGradeGroups($appGlobals, $appData);
//        return;
//    }
//    if ( ($this->step_init_submit_fieldId=='save') ) {
//        if ($appData->apd_list_gradeGroup_value=='~') {
//            $appChain->chn_theme-message-error-error_setFormError('You must select a group');
//            return;
//        }
//        $keys = explode('~',$appData->apd_list_gradeGroup_value);
//        $gradeGroups->grdgp_add($keys[0],$keys[1]);
//        $this->apd_saveGradeGroups($appGlobals, $appData);
//        return;
//    }
//    $appChain->chn_step_executeNext(1);
//}

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );

    $appChain->chn_form_savePostedData();
    $appChain->chn_ValidateAndRedirectIfError();
    $appChain->chn_launch_continueChain(1);

}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-panel' );
    $appEmitter->emit_options->set_title('Setup Grade Groups');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appEmitter, $appData->apd_roster_program );
    $appGlobals->gb_menu->drMenu_customize();
 }

function drForm_initFields( $appData, $appGlobals, $appChain ) {
    $this->drForm_addField( new Draff_Combo(  '@groups' , $appData->apd_list_gradeGroup_value, $appData->apd_list_gradeGroup_array))
    $this->drForm_addField( new Draff_Button(  '@setDefaults' , 'Set Defaults' ) );
    $this->drForm_addField( new Draff_Button(  '@save' , 'Save' ) );
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
     $appEmitter->zone_start('draff-zone-content-default');
    $curPeriod = $appData->apd_roster_program->rst_cur_period;
    $gradeGroups = $curPeriod->perd_gradeGroups;
   //--- appEmitter entire page

    $appEmitter->table_start('kguiData');
    $appEmitter->row_start();
        $appEmitter->cell_start('eggTop','colspan="99"');
        $appEmitter->emit_nrLine('Edit Grade Groups');
        $appEmitter->cell_end();
    $appEmitter->row_end();
    $appEmitter->row_start();
        $appEmitter->cell_block('Current Groups','eggLeftTitle1');
        $appEmitter->cell_block('Create a Group','eggRightData');
    $appEmitter->row_end();
        $appEmitter->cell_block('If you do not want to see grade group columns, set each group as a single grade or set defaults (below)','eggLeftTitle2');
        $appEmitter->cell_block('current groups (on left) will be adjusted to not conflict with new group you select below','eggRightTitle2');
    $appEmitter->row_end();
    $appEmitter->row_start();
        $appEmitter->cell_start('eggLeftData');
         //$page->unListStart('eggLeftData','','');
                 $appEmitter->emit_nrLine('');
           for ($i = 0; $i<$gradeGroups->grdgp_group_count; $i++) {
                $appEmitter->emit_nrLine('<br>'.$gradeGroups->grdgp_group_descLong[$i]);
          //      $page->unListItem($rangeDescArray[$i]);
            //for ($i = 0; $i<$curPeriod->GG_GroupCount; $i++) {
            //    $page->unListItem($curPeriod->GG_GroupLngDesc[$i]);
            }
                 $appEmitter->emit_nrLine('');
       // $page->unListEnd();
        $appEmitter->emit_nrLine('<br>');
        // would look better with bullets as in kcm
        $appEmitter->content_field ('@setDefaults');
        $appEmitter->cell_end();
        $appEmitter->cell_start('eggRightData');
        $appEmitter->content_field ('@groups');
          // $page->inputListBox('eggRightData',8,'GrGr',$list);
        $appEmitter->content_field ('@save');
        //    $page->inputButton('EggButtonSave','Save', 'Submit','save');
        //$page->textOut('or');
        //$page->inputButton('EggButtonBack','Go Back', 'Submit','back');
        $appEmitter->cell_end();
        $appEmitter->row_end();
    $appEmitter->table_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

}  // end widget class

class application_data extends draff_appData {
public $apd_roster_program;
public $apd_list_gradeGroup_value;
public $apd_list_gradeGroup_array;


function apd_formData_get( $appGlobals, $appChain ) {
}

function apd_formData_validate( $appGlobals, $appChain ) {
}

function apd_initialize($appGlobals) {
    $this->apd_list_gradeGroup_value = -1;
    $this->apd_list_gradeGroup_array = array();
    $this->apd_initGradeGroupList($appGlobals);
}

function apd_initGradeGroupList($appGlobals) {
    $this->apd_list_gradeGroup_array = array();
    $curPeriod = $this->apd_roster_program->rst_cur_period;
    $gradeGroups = $curPeriod->perd_gradeGroups;
    $this->apd_list_gradeGroup_array['~']='(Select a group to create)';
    for ( $min=$gradeGroups->grdgp_minGrade; $min<=$gradeGroups->grdgp_maxGrade; $min++) {
        for ($max=$min; $max<=$gradeGroups->grdgp_maxGrade; $max++) {
           $key = $min . '~' . $max;
           if ($min==$max) {
               $this->apd_list_gradeGroup_array[$key] = $gradeGroups->grdgp_grade_descLong[$min] . ' Grade';
            }
            else {
                if ($min==0)
                    $this->apd_list_gradeGroup_array[$key] = $gradeGroups->grdgp_grade_descLong[$min] . ' to Grade ' . $gradeGroups->grdgp_gradeDescLong[$max];
                else
                    $this->apd_list_gradeGroup_array[$key] = 'Grades ' . $gradeGroups->grdgp_grade_descShort[$min] . ' to ' . $gradeGroups->grdgp_grade_descShort[$max];
            }
        }
    }
}

function apd_saveGradeGroups($appGlobals, $appData) {
    $curPeriod = $appData->apd_roster_program->rst_cur_period;
    $gradeGroups = $curPeriod->perd_gradeGroups;
    $groupsArray = array();
    for ($i = $gradeGroups->grdgp_minGrade; $i<=$gradeGroups->grdgp_maxGrade; ++$i) {
        if ($gradeGroups->grdgp_grade_isGroupEnd[$i]) {
            $groupsArray[] = $i;
        }
    }
    $groupsString = implode('~', $groupsArray);
    $query = "UPDATE `pr:period` SET `pPe:KcmGradeGroups`='"  .$groupsString. "' WHERE `pPe:PeriodId` = '".$curPeriod->perd_periodId. "'";
    $result = $appGlobals->gb_db->rc_query( $query );
    if ($result === FALSE) {
       kcm_db_CriticalError( __FILE__,__LINE__);
    }
}

} // end class

//@@@@@@@@@@@@@@@@
//@ Initialize
//@@@@@@@@@@@@@@@@

rc_session_initialize();

$appChain = new Draff_Chain(  'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmRoster_globals());
$appChain->chn_register_appData( $appData = new application_data());
$appGlobals->gb_forceLogin ();

$appData->apd_roster_program = new pPr_program_extended_forRoster($appGlobals);
$appData->apd_roster_program->rst_load_rosterData($appGlobals, $appChain);

$appChain->chn_form_register(1,'appForm_setupGradeGroups_main');
$appChain->chn_form_launch(); // proceed to current step

exit;

//===================================
?>
