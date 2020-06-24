<?php

// roster-system-functions.inc.php

function kcmRosterLib_setBannerSubTitle($emitter,$appGlobals,$roster,$title, $flag='') {
    $date = rc_getNowDate();
    $periodDesc = '';
    // 'cSD_scheduleDateId' => '43922', 'cSD_classDate' => '2019-05-13', 'cSD_startTime' => '14:30:00', 'cSD_endTime' => '16:35:00', 'cSD_isHoliday' => false, 'cSD_notes' => '', ))
    //$time = date( "H:i:s" );
    $classDate = $roster->rst_classDate;
    $classDate = empty($classDate) ? '' : ' - Class Date: ' . draff_dateAsString( $classDate , 'M j, Y' );
    $schoolName = empty($roster->prog_nameUniquifier) ? $roster->prog_programName : ($roster->prog_programName . ' ' . $program->SchoolNameUniquifie);
    $semester = rc_getSemesterAndYearNameFromYearAndCodeList($roster->prog_schoolYear,$roster->prog_semester);
    if ($roster->prog_dateFirst > $date ) {
        $semester .= ' (future event)';
    }
    else if ($roster->prog_dateLast < $date ) {
        $semester .= ' (past event)';
    }
  //  $subtitle = $schoolName . ' - ' . $semester;
    if ($flag=='$bothPeriods') {
        $periodDesc = '(applies to all periods)';
    }
    else if ($flag!='$noPeriod') {
        $period = $roster->rst_cur_period;
        if ($period==NULL) {
            $periodDesc = 'Need to select period';
        }
        else  {
            $periodDesc = $period->perd_descShort;
            // if possible, if class is meeting now and 1st period after 2nd period start time then give warning
        }
        $subtitle = $periodDesc;
    }
    $title = $title . ' - ' . $periodDesc;
    $subtitle = $schoolName;
    $emitter->set_title($title,$subtitle);
}

function kcmRosterLib_getDesc_grade($pGradeCode,$pGradeYear=NULL,$pProgramYear=NULL) {
    if ($pGradeCode===NULL)
        return "";
    if ($pGradeCode == RC_GRADE_MIN_INFINITY)
        return "Unknown";
    if ($pGradeCode < RC_GRADE_K)
        return "Pre-K";
    if ($pGradeCode > 12)
        return "Adult";
    if ($pGradeCode == RC_GRADE_K)
        return "0K";
    if ($pGradeYear!=NULL and $pProgramYear!=NULL)
       $pGradeCode = $pGradeCode - ($pGradeYear - $pProgramYear);
    switch ($pGradeCode){
        case 0: return "0K";
        case 1: return "1st";
        case 2: return "2nd";
        case 3: return "3rd";
        case 4: return "4th";
        case 5: return "5th";
        case 6: return "6th";
        case 7: return "7th";
        case 8: return "8th";
        case 9: return "9th";
        case 10: return "10th";
        case 11: return "11th";
        case 12: return "12th";
        case -1: return "0-PK";
    }
    return "Other";  // should never get here
}

function kcmRosterLib_getDesc_originCode($orgCode) {
    switch ($orgCode) {
        case 1: return 'Class'; break;
        case 2: return 'Tally'; break;
       default: return 'Kcm-1'; break;
    }
}

function kcmRosterLib_getDesc_gamePercent($pWin, $pLost, $pDraw) {  //???? unused ?????
    $games = $pWin + $pLost + $pDraw;
    if ($games == 0)
        return '';
    else
        return  round( ( 100 * ( $pWin + $pDraw * .5) ) / $games );
}

function kcmRosterLib_getDesc_gameType($gameType) {
    switch ($gameType) {
        case 0:
             return 'Chess';
             break;
        case 1:
             return 'Blitz';
             break;
        case 2:
             return 'Bughouse';
             break;
        default:
             return 'Unknown';
             break;
    }
}

function kcmRosterLib_getCombo_kidPeriod($appGlobals, $roster, $option = NULL) {
    $list = array();
    if ($option === '@all') {
        $list['@all'] = '(all Kids)';
    }
     if ($option === '@none') {
        $list['@none'] = '(select a Kid)';
    }
    $rst_cur_period = $roster->rst_get_period();
    foreach ($rst_cur_period->perd_kidPeriodMap as $kidPeriod) {
       $list[$kidPeriod->kidPer_kidPeriodId] = $kidPeriod->kidPer_kidObject->rstKid_uniqueName;
    }
    return $list;
}

function kcmRosterLib_getCombo_pointValues($appGlobals, $option = NULL) {
    $desc = ($option==='@none') ? 0 : '(select Points)';
    $list = array(0=>$desc, 1=>1, 2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,
            10=>10,11=>11,12=>12,13=>13,14=>14,15=>15,
            20=>20,25=>25,30=>30,35=>35,40=>40,45=>45,50=>50,55=>55,60=>60,80=>80,100=>100,
            -1=>-1,-2=>-2,-3=>-3,-4=>-4,-5=>-5 , -6=>'-6' , -10=>'-10' , -20=>'-20' );
     return $list;
}

function kcmRosterLib_getCombo_pointCategories($appGlobals, $roster, $curValue) {
    // different than most lists as saved point category must be added to list of valid values
    // i.e. A valid value from a previous list is valid even if not on current list
   // $program = $roster->rst_program;
     $program = $roster;
   $catChoices = array(' '=>'(None)');
    //if ($curValue=='') {
    //    $catChoices[''] = '(None)';
    //}
    //else {
        if (!in_array($curValue, $program->prog_pointCategories)) {
            $catChoices[$curValue] = $curValue;
        }
    //}
    foreach ($program->prog_pointCategories as $catName) {
        $catName = trim($catName);
        if ($catName!='') {
            $catChoices[$catName] = $catName;
        }
    }
    return $catChoices;
}

function kcmRosterLib_getCombo_gameCount($appGlobals) {
    $list = array();
    for ($i=0; $i<=12; ++$i) {
        $list[$i] = $i;
    }
    return $list;
}

function kcmRosterLib_getCombo_gameTypes($appGlobals, $option = NULL) {
    $list = array();
    if ($option === '@none') {
        $list['@none'] = '(select a Game Type)';
    }
   if ($option === '@all') {
        $list['@all'] = '(all Game Types)';
    }
    $list[ 0] = 'Chess';
    $list[ 1] = 'Blitz';
    $list[ 2] = 'Bughouse';
    return $list;
}

function kcmRosterLib_getCombo_classDates($appGlobals, $roster, $option = NULL) {
    $list = array();
    if ($option === '@all') {
        $list['@all'] = '(all Class Dates)';
    }
     if ($option === '@none') {
        $list['@none'] = '(select a Class Date)';
    }
    foreach ($roster->rst_classSchedule->schProg_items as $schedDateItem) {
        $s = draff_dateAsString($schedDateItem->cSD_classDate, 'M j');
        $list[$schedDateItem->cSD_classDate] = $s;
    }
    return $list;
}

function kcmRosterLib_kidList_checkboxes_define($form, $appGlobals, $roster, $checked=array()) {
    foreach ($roster->rst_cur_period->perd_kidPeriodMap as $kidPeriodId => $kidPeriod) {
        $kid = $roster->rst_get_kid($kidPeriod->kidPer_kidId);
        $name = $draff_emitter_engine::getString_sizedMemo($kid->rstKid_uniqueName,10);
        $fieldId =  '@kidCheck_' . $kidPeriod->kidPer_kidPeriodId;
        $value = array_search($kidPeriod->kidPer_kidPeriodId,$checked)===FALSE ? 0 : 1;
        $form->drForm_addField( new Draff_Combo( $fieldId , $name, $value,'1','0','draff-checkbox-select') );
    }
}

function kcmRosterLib_kidList_checkboxes_getChecked($appGlobals, $roster, $chain) {
    $list = array();
    foreach ($roster->rst_cur_period->perd_kidPeriodMap as $kidPeriodId => $kidPeriod) {
        $fieldId =  '@kidCheck_' . $kidPeriod->kidPer_kidPeriodId;
        if ($chain->chn_data_posted_get($fieldId, NULL) == 1) {
            $list[] = $kidPeriod->kidPer_kidPeriodId;
        }
    }
    return $list;
}

function kcmRosterLib_kidList_checkboxes_emit($emitter, $appGlobals, $roster, $fieldIdPrefix) {
    print PHP_EOL;
    //print PHP_EOL.'<div class="draff-appContent">';  //????????????? should already be done ?????
    $rst_cur_period = $roster->rst_cur_period;
    foreach ($rst_cur_period->perd_kidPeriodMap as $kidPeriodId => $kidPeriod) {
        $kid = $roster->rst_get_kid($kidPeriod->kidPer_kidId);
        $name = emitHtml::emGen_sizedMemo($kid->rstKid_uniqueName,10);
        $fieldId =  '@kidCheck_' . $kidPeriod->kidPer_kidPeriodId;
 //       print PHP_EOL.'<div class="draff-checkbox-select">';
        $emitter->content_field($fieldId);
 //       print PHP_EOL.'</div>';
    }
    //print PHP_EOL . '</div>';
    print PHP_EOL;
}

function kcmRosterLib_kidList_buttons_define($form, $appGlobals, $roster, $fieldIdPrefix, $excludeKid=NULL) {
    $rst_cur_period = $roster->rst_get_period();
    foreach ($rst_cur_period->perd_kidPeriodMap as $kidPeriodId => $kidPeriod) {
        if ($kidPeriod->kidPer_kidId != $excludeKid) {
            //$kid = $roster->rst_get_kidObject(ROSTERKEY_KIDPERIODID,$kidPeriodId);
            $kid = $roster->rst_get_kid($kidPeriod->kidPer_kidId);
            $name = Draff_Emitter_Engine::getString_sizedMemo($kid->rstKid_uniqueName,10);
            //$cl = 'draff-button-select' .kcm2_lib::klGetCssFontSizeSuffix($kidPeriod->kidPer_kidObject->rstKid_uniqueName,'draff-button-select');
            //$appGlobals->gb_form->define_button( array($fieldIdPrefix, $kidPeriod->kidPer_kidPeriodId) , $kidPeriod->kidPer_kidObject->rstKid_uniqueName , array('propType'=>'inputTag','class'=>$cl) );
            $id = $fieldIdPrefix . '_' .  $kidPeriod->kidPer_kidPeriodId;
            $this->drForm_addField( new Draff_Button(  '@' . $id , $name, array('class'=>'draff-button-select') ) );
        }
    }
}

function kcmRosterLib_kidList_buttons_emit($appGlobals, $roster, $emitter, $fieldIdPrefix, $excludeKid=NULL) {
    $rst_cur_period = $roster->rst_cur_period;
    foreach ($rst_cur_period->perd_kidPeriodMap as $kidPeriod) {
        if ($kidPeriod->kidPer_kidPeriodId != $excludeKid) {
            $id = '@' . $fieldIdPrefix . '_' . $kidPeriod->kidPer_kidPeriodId;
            $emitter->content_field($id);
        }
    }
}

function kcmRosterLib_redirect_toMainMenu( $appGlobals, $chain, $overrides = array()) {
    //????@@@?????@@@?????@@@???@@@???@@@???@@@???@@@???@@@
    $url = $chain->chn_url_build_chained_url('roster-home.php', $appGlobals->gb_kernelOverride_getStandardUrlArgList(), $overrides);
//?????? eliminate token, step, etc - Need "fresh" URL
    rc_redirectToURL( $url );
}

//==================================
// Database functions
//==================================

function kcmRosterLib_db_insert($db,$table, $fieldValueArray) {
    $s1 = '';
    $s2 = '';
    $punc = '';
    foreach( $fieldValueArray as $field => $value) {
        $s1 .= $punc . "`" . $field . "`";
        $s2 .= $punc . kcmRosterLib_db_getNormalizedValue($db,$value);
        $punc = ',';
    }
    return "INSERT INTO `{$table}` ({$s1}) VALUES ({$s2})";
}

function kcmRosterLib_db_update($db, $table, $fields, $where) {
    $s1 = '';
    $punc = '';
    foreach( $fields as $field => $value) {
        $s1 .= "{$punc}`{$field}`=". kcmRosterLib_db_getNormalizedValue($db, $value);
        $punc = ',';
    }
    return "UPDATE `{$table}` SET {$s1} {$where}";
}

function kcmRosterLib_db_getNormalizedValue($db, $val) {
	if (gettype( $val ) == "boolean") {
		return (int)$val;
				// the boolean FALSE gets rendered as an empty string
				// starting with MySQL 5.7, this is an error instead of acting like 0
	}
	if (is_null( $val )) {
		return 'NULL';
	}
	else {
		return "'" . $db->real_escape_string( $val ) . "'";
	}
}

function kcmRosterLib_getKidName($appGlobals, $roster, $kidPeriodId) {
        $period = $roster->rst_cur_period;
        if ( empty($period) or empty($kidPeriodId) ) {
            return '';
        }
        $kidPeriod = $period->perd_getKidPeriodObject($kidPeriodId);
        if ( empty($kidPeriod) ) {
             return '';
        }
        return $kidPeriod->kidPer_kidObject->rstKid_uniqueName;
}

?>