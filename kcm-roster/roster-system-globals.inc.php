<?php

// roster-system-globals.inc.php

const GAME_ORIGIN_OTHER  = '0';  // games without opponents or from KCM1 (may be eliminated once KCM1 is unused)
const GAME_ORIGIN_CLASS  = '1';  // games with opponents
const GAME_ORIGIN_TALLY  = '2';  // from tally - only one result per class day (per session)

    
class kcmRoster_globals  extends kcmKernel_globals {
public $gb_url_programId = NULL;  // need to select class period from gateway before starting roster system
public $gb_url_periodId  = NULL;  // required - but should change to allow default
public $gb_url_scheduleDateId  = NULL;  // optional - ??? need to investigate usage -- ???? need to set to be in standard arg list ?????
    
function __construct() {
    parent::__construct('KCM Roster Management','../kcm-kernel/images/banner-icon-kcm.gif','kcmKernel_emitter') ;
    $this->gb_owner = new kcmRoster_security_user($this, NULL);  
    $this->gb_user  = new kcmRoster_security_user($this, $this->gb_owner);
    $this->gb_banner_image_system = 'kcm_banner_roster.gif'; 
    //$this->gbx_roster = new pPr_program_extended_forRoster($this);
    $this->gb_url_programId = draff_urlArg_getRequired('PrId', 'URL program parameter is required');  //?????????????? get from globals ???????????????????
    $this->gb_url_periodId = draff_urlArg_getRequired('PeId', 'URL period parameter is required');  //????? or need to set default if none
    $this->gb_url_scheduleDateId = draff_urlArg_getOptional('SdId', ''); // not required
}

//function gb_initForKcm1($noPeriodOk=FALSE) {
//    // ??????????????? NEVER CALLED ????????????????
//    //?????????????? $urlScheduleDateId = $this->gb_form->chain->chn_url_getArgument('SdId', ''); // not required
//   // expected URL parameter: ?kcmp=_PrId-1184_PeId-2251
//    //?????????????$kcmArg = $this->gb_form->chain->chn_url_getRequiredArg('kcmp', 'URL kcm1 parameter is required');
//    $k = explode('_',$kcmArg);
//    $urlProgramId = '';
//    $urlPeriodId = '';
//    $urlScheduleDateId = '';
//    foreach ($k as $value) {
//        $key = substr($value,0,4);
//        $value = substr($value,5);
//        if ($key=='PrId') {
//            $urlProgramId = $value;
//        }
//       if ($key=='PeId') {
//            $urlPeriodId = $value;
//        }
//    }
//    if ( ( $urlProgramId == '') or ( ( $urlPeriodId == '') and ($noPeriodOk==FALSE)) ) {
//        exit('Invalid URL parameter for Kcm1');
//    }
//    //???????$this->gb_form->chain->chn_url_registerArgument(URL_ALL,'PrId',$urlProgramId);
//    //???????$this->gb_form->chain->chn_url_registerArgument(URL_ALL,'PeId',$urlPeriodId);
//    //???????$this->gb_form->chain->chn_url_registerArgument(URL_ALL,'SdId',$urlScheduleDateId);
//    $this->rst_loadProgram_andPeriods($this,$urlProgramId); // needed for menu objects ?????
//    $this->rst_cur_period = empty($urlPeriodId) ? '0' : $this->gbx_roster->rst_map_period[$urlPeriodId];
//    $this->gbx_roster->rst_classSchedule = new schedule_oneProgram_eventDates();
//    $this->gbx_roster->rst_classSchedule->schProg_read($this->gb_db,$urlProgramId, $this); 
//    $this->rst_classDateObject = $this->gbx_roster->rst_classSchedule->schProg_getScheduleDateObject($urlScheduleDateId);  //????? move to schedule ?????
//}

function gb_kernelOverride_getStandardUrlArgList() {
    $args = array();
    if ($this->gb_url_programId!=NULL ) {
        $args['PrId'] = $this->gb_url_programId;
    }    
    if ($this->gb_url_periodId!=NULL ) {
        $args['PeId'] = $this->gb_url_periodId;
    } 
    if ($this->gb_url_scheduleDateId!=NULL) {    
        $args['SdId'] = $this->gb_url_scheduleDateId; 
    }  
   return $args;
}

function gb_appMenu_init($chain, $emitter, $overrides = NULL) {   /* required function - called by kernel emitter*/
    $roster = is_array($overrides) ? $overrides[0] : $overrides;  // evantually will always be array
    $menu = $emitter->emit_menu;
    //function rst_emit_menu_mainMenuInit( $appGlobals, $chain, $flag = 0) {
    $stdKcmArgs = $this->gb_kernelOverride_getStandardUrlArgList(); 
    $menu->menu_addLevel_top_start();
    if ($roster!=NULL) {
        $menu->menu_addTitleExtension('resS_status',$this->gb_getTitleExtension_results($roster) );
    }

    $menu->menu_addItem($chain, 'resS_P','Points'  ,'roster-results-points.php');
    $menu->menu_addItem($chain, 'resS_C','Chess'   ,'roster-results-games.php', array( 'rsmForm'=>1,'rsmMode'=>1));
    $menu->menu_addItem($chain, 'resS_S','Blitz'   ,'roster-results-games.php', array( 'rsmForm'=>1,'rsmMode'=>2));
    $menu->menu_addItem($chain, 'resS_B','Bughouse','roster-results-games.php', array( 'rsmForm'=>11,'rsmMode'=>3));
    if (!RC_LIVE) {
        $menu->menu_addItem($chain, 'resS_Debug','Debug','../kcm-kernel/kernel-debug.php');
    }
    $menu->menu_addLevel_top_end();

    $menu->menu_addLevel_toggled_start();
    $menu->menu_addGroup_start('resA','Results Admin');
    $menu->menu_addItem($chain, '_h','Home (KCM)'  ,'roster-home.php');
    $menu->menu_addItem($chain, 'rp_te','Edit<br>Tally'         ,'roster-results-tally.php');
    $menu->menu_addItem($chain, 'rp_cp','Change<br>Period'      ,'roster-results-set-period.php');
    $menu->menu_addItem($chain, 'rp_cd','Change<br>Results Date','roster-results-set-classDate.php');
    //$menu->menu_addItem($chain, 'rp_gh','Game<br>History'       ,'roster-results-game-history.php');
    $menu->menu_addItem($chain, 'rp_ph','Points<br>History'     ,'roster-results-points-history.php');
    $menu->menu_addGroup_end('resA');

    $menu->menu_addGroup_start('rpt','Reports');
    $menu->menu_addItem($chain, 'rpt_ro','Roster'                ,'roster-report-roster.php');
    $menu->menu_addItem($chain, 'rpt_pt','Name<br>Labels'        ,'roster-report-nameLabels.php');
    $menu->menu_addItem($chain, 'rpt_ts','Tally<br>Sheet'        ,'roster-report-tallySheet.php');
    $menu->menu_addItem($chain, 'rpt_rs','Results<br>Sheet'        ,'roster-report-resultsSheet.php');
    $menu->menu_addItem($chain, 'rpt_so','Sign-Out<br>Sheet'        ,'roster-report-signOutSheet.php');
    $menu->menu_addItem($chain, 'rpt_pl','Pairing<br>Labels'        ,'roster-report-PairingLabels.php');
    $menu->menu_addItem($chain, 'rpt_ad','Drop<br>List'        ,'roster-report-adminLists.php',array('rsMode'=>'drop'));
    $menu->menu_addItem($chain, 'rpt_ae','Email<br>List'        ,'roster-report-adminLists.php',array('rsMode'=>'email'));
    $menu->menu_addItem($chain, 'rpt_aw','Wait<br>List'        ,'roster-report-adminLists.php',array('rsMode'=>'wait'));
    $menu->menu_addGroup_end('rpt');

    $menu->menu_addGroup_start('admS','Admin Setup');
    $menu->menu_addItem($chain, 'admS_ki','Kid<br>Info'        ,'roster-setup-kidData.php');
    $menu->menu_addItem($chain, 'admS_pc','Point<br>Categories','roster-setup-pointCategories.php');
    $menu->menu_addItem($chain, 'admS_gg','Grade<br>Groups'    ,'roster-setup-gradeGroups.php');
    $menu->menu_addGroup_end('admS');

    $menu->menu_addLevel_toggled_end();

    $menu->menu_markTopLevelItem('home',2);
    $menu->menu_markTopLevelItem('home_h',2);
    $menu->menu_markTopLevelItem('resS_P',2);
    $menu->menu_markTopLevelItem('resS_C',2);
    $menu->menu_markTopLevelItem('resS_S',2);
    $menu->menu_markTopLevelItem('resS_B',2);
    if (!RC_LIVE) {
        $menu->menu_markTopLevelItem('res_Debug',2);
    }

    $menu->menu_markTopLevelItem('resT_te',2);
}

function gb_getTitleExtension_results ($roster) {
    $period = $roster->rst_cur_period;
    if ( empty($this) or empty($period) ) {
        return '';
    }
    // 'cSD_scheduleDateId' => '43922', 'cSD_classDate' => '2019-05-13', 'cSD_startTime' => '14:30:00', 'cSD_endTime' => '16:35:00', 'cSD_isHoliday' => false, 'cSD_notes' => '', ))
    //$time = date( "H:i:s" );
    
    $title = '';
    $subTitle = '';
    
    $date = rc_getNowDate();
    $classDate = $roster->rst_classDate;
    $classDate = empty($classDate) ? '(none)' : draff_dateAsString( $classDate , 'D, M j, Y' );
    
    $schoolName = empty($this->prog_nameUniquifier) ? $roster->prog_programName : ($roster->prog_programName . ' ' . $this->SchoolNameUniquifie);

    $semester = rc_getSemesterAndYearNameFromYearAndCodeList($roster->prog_schoolYear,$roster->prog_semester);
    if ($roster->prog_dateFirst > $date ) {
        $semester .= ' (future event)';
    }
    else if ($roster->prog_dateLast < $date ) {
        $semester .= ' (past event)';
    }
    
    $subtitle = $schoolName . ' - ' . $semester;
//    if ($flag=='$bothPeriods') {
//        $title .= ' (applies to all periods)';
//    }    
//    else if ($flag!='$noPeriod') {
        if ($period==NULL) {
            $periodDesc = 'Need to select period';
        }
        else  { 
            $periodDesc = $period->perd_descShort;
            // if possible, if class is meeting now and 1st period after 2nd period start time then give warning
        }    
        //$title .= ' - ' . $periodDesc . $classDate;
      //  $title = $classDate;
//    }    
    //return '<div style="font-size: 1.2em">' . $subtitle . '<br>' . $title . '</div>';
    $classDate = $roster->rst_classDate;
    $s1 = 'Results for: '.draff_dateAsString( $classDate , 'D' );
    $s2 = draff_dateAsString( $classDate , 'M j, Y' );
    //$classDate = empty($classDate) ? '(none)' : draff_dateAsString( $classDate , 'D, M j, Y' );
    //$s = '&nbsp; &nbsp; &nbsp;Results for:<br>' . $classDate;
    $s = $s1 . '<br>' . $s2;
    return $s;
} 

}

// end class

class kcmRoster_security_user extends kcmKernel_security_user {

public $sec_isLive; // used to allow certain features (such as communicating with authorize.net)

public $krnUser_rst_isSystemAdmin; // used to allow certain features  and Proxy
public $krnUser_rst_isOfficeWorker; // used to allow certain features - always true if systemAdmin is true
public $krnUser_rst_isLive; // used to allow certain features - always true if systemAdmin is true
// access: 0=none, 1=View-only, 3=Edit
public $krnUser_rst_accessCalendar;   // allows access to proxy (none, view, or edit)
public $krnUser_rst_accessRoster;     // allows access to all schools (none, view, or edit)
public $krnUser_rst_accessCoach;   // Coach level - in Raccoon called roster limited - edit mode changed to none if not Site leader or not during class hours -  allows access to entering points, setup, etc - etc (none, view, or edit) - changed if leader for that school
public $krnUser_rst_accessFinancial;   // Allows access to dangerous  areas of the code 
public $krnUser_rst_accessSystem;   // Allows access to dangerous utilities, etc and Proxy
    
function __construct($appGlobals, $actualUser=NULL) {
    parent::__construct($appGlobals, $actualUser);
    //$this->isProxy = $pCanBeProxy;
    $this->sec_isLive = RC_LIVE;
    $this->krnUser_rst_isOfficeWorker = FALSE;
}

function sec_user_setSecurityLevels($loginId, $notProxy) {    
    $db = rc_getGlobalDatabaseObject();
    $query = "SELECT `st:roledefinition`.* FROM `st:loginidentity` 
        INNER JOIN `st:roledefinition` ON `sLI:@RoleId` = `sRD:RoleId`
        WHERE (`sLI:LoginId` = '{$loginId}')
        AND (`sLI:HiddenStatus` = " . RC_HIDDEN_SHOW .")
        AND (`sRD:HiddenStatus` = " . RC_HIDDEN_SHOW .")";
    $result = $db->rc_query( $query );  //?????????????????????????????????????????
    //if (($result === FALSE) || ($result->num_rows == 0)) {
    //    krnLib_sql_database::sql_fatalError( __FILE__,__LINE__, $query);
    //}
    $row=$result->fetch_array();
    if ( $row['sRD:SystemAccess']==3)  {
        if ($notProxy) {
            $this->krnUser_isSysAdmin = TRUE;
        }    
        $this->krnUser_rst_isOfficeWorker = TRUE;
    }
    if ( ($row['sRD:caScheduleAccess']==3) and ($row['sRD:roRosterAccess']==3) and ($row['sRD:fiFinanceAccess']==3) ) {
        $this->krnUser_rst_isOfficeWorker = TRUE;
    }
    $this->krnUser_rst_accessCalendar = $row['sRD:caScheduleAccess'];
    $this->krnUser_rst_accessRoster= $row['sRD:roRosterAccess'];
    $this->krnUser_rst_accessCoach= $row['sRD:roRosterLimitedAccess'];
    $this->krnUser_rst_accessFinancial= $row['sRD:fiFinanceAccess'];
    $this->krnUser_rst_accessSystem= $row['sRD:SystemAccess'];
    if ($notProxy) {
         $this->krnUser_rst_isOfficeWorker = ($this->krnUser_rst_accessFinancial >= 1);
    }
    else {
         $this->krnUser_rst_accessFinancial= max($this->krnUser_rst_accessFinancial,1);
    }    
    if (!$notProxy) {
        $this->krnUser_isSysAdmin = ( $this->krnUser_rst_accessSystem==3); 
    }    
    else {
         $this->krnUser_isSysAdmin = FALSE;
         $this->krnUser_rst_accessFinancial= max($this->krnUser_rst_accessFinancial,1);
         $this->krnUser_rst_accessSystem= max($this->krnUser_rst_accessSystem,1);
    }    
}
    
}  // end class  

?>