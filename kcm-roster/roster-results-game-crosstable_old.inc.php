<?php

// roster-results-game-crosstable.inc.php

// this is a kcm1 report

include_once( 'kcm1/kcm-libAsString.inc.php' );
include_once( 'kcm1/kcm-libKcmState.inc.php' );
include_once( 'kcm1/kcm-libKcmFunctions.inc.php' );
include_once( 'kcm1/kcm-page-ColumnDef.inc.php' );
include_once( 'kcm1/kcm-page-Engine.inc.php' );
include_once( 'kcm1/kcm-page-Styles.inc.php' );
include_once( 'kcm1/kcm-page-DOM.inc.php' );
include_once( 'kcm1/kcm-page-Export.inc.php' );
include_once( 'kcm1/kcm-libNavigate.inc.php' );
include_once( 'kcm1/kcm-roster.inc.php' );
include_once( 'kcm1/kcm-roster_objects.inc.php' );
include_once('../../lib/fpdf/fpdf.php');  //@JPR-2019-11-04 21:55
include_once('../../lib/excel/PHPExcel/PHPExcel.php');
include_once('../../lib/excel/rc_excel/rc_PHPExcel.inc.php');

const kcmGAME_CHESS_INDEX = 1;
const kcmGAME_BLITZ_INDEX = 2;
const kcmGAME_BUGHOUSE_INDEX = 3;

define('kcmMAX_POINT_CATEGORIES',12); //also in KCM roster

class report_crosstable {
//public $ctr_programObject;
public $ctr_exportType;
public $ctr_isExport;
public $ctr_pageCount;
public $ctr_roster;
public $ctr_gameGroup;
public $ctr_gameType;
//public $ctr_page;
//public $ctr_argFormat;


function __construct () {
    //$this->ctr_programObject  = $programId;
}

function ctr_print($appGlobals, $emitter, $programId, $periodId, $gameTypeIndex, $exportCode) {
    $this->ctr_exportType = $exportCode;
    $this->ctr_isExport   = ( ($this->ctr_exportType=='p') or ($this->ctr_exportType=='e'));

    $this->ctr_gameType = $gameTypeIndex;
    $db = $appGlobals->gb_db;
    
    //$db = new rc_database();
    $this->ctr_roster = new kcm_roster($programId);
    $this->ctr_roster->load_roster_headerAndKids();
    $this->ctr_roster->sort_periodFilter(0);
    $this->ctr_roster->sort_start();
    $this->ctr_roster->sort_byPeriodCurrent('c');
    $this->ctr_roster->sort_byFirstName();
    $this->ctr_roster->sort_end();
    

    $section = new kcm_crosstable_section($db, $this->ctr_roster, $periodId, $gameTypeIndex);
    $section->cts_loadThis_section();
    // $geEngine->processPage($kcmState,$this->ctr_roster, $section, $db);

    // added jpr - not in original code here
    ////*************
    ////* Read Data *
    ////*************
    //$db = new rc_database();
    //
    //$this->ctr_roster = new kcm_roster($this->ctr_programObject->prog_programId);
    //$this->ctr_roster->load_roster_headerAndKids();
    //
    $mainTitle = 'Name Labels<br>'.$this->ctr_roster->program->getNameLong($this->ctr_roster);
    
    $kcmState = NULL;
    $columnDef = new kcm_ColumnDef('geSort',11,$kcmState,'kcm-game_entry.php');
    // option enabl  sort   direc freeze
    $columnDef->initColumn('colROWNUM'  , 1, FALSE, TRUE,  TRUE,  TRUE);
    $columnDef->initColumn('colFINAME'  , 2, FALSE, TRUE,  TRUE,  TRUE);  // 1 is always default sort column
    $columnDef->initColumn('colLANAME'  , 3, FALSE, TRUE,  TRUE,  TRUE);
    $columnDef->initColumn('colGRADEGRP', 4, FALSE, FALSE,  TRUE,  TRUE, TRUE); // ??? 2nd false was $curPeriod->gradeGroups->ggEnabled
    $columnDef->initColumn('colGRADE'   , 5, FALSE, TRUE,  TRUE,  TRUE, TRUE);
    $columnDef->initColumn('colROOKIE'  , 6, FALSE, TRUE,  TRUE,  FALSE, TRUE);
    //$columnDef->initColumn('colSUBGRP', 7, TRUE,  $sgActive, TRUE,  TRUE, TRUE);
    $columnDef->initColumn('colWIN'     , 7, FALSE, TRUE,  FALSE,  FALSE);
    $columnDef->initColumn('colLOSS'    , 8, FALSE, TRUE,  FALSE,  FALSE);
    $columnDef->initColumn('colDRAW'    , 9, FALSE, TRUE,  FALSE,  FALSE);
    $columnDef->initColumn('colPERCENT' ,10, FALSE, TRUE,  TRUE,  FALSE);
    $columnDef->initColumn('colGAMELIST',11, FALSE, TRUE,  FALSE, FALSE);
    $columnDef->initFinalize(colFINAME);
    
    //$page->frmStart('get','crstbl','kcm-game_entry.php','kcGuiDataForm');
    //if (! $this->ctr_isExport) {
    //    $page->frmStart('get','crstbl','kcm-game_entry.php','kcGuiDataForm');
    //}
    $pageTitle = 'Enter Games';
    $page = new kcm_pageEngine($columnDef);
    $page->setIsReportPreview();
    $page->setBreakOnNewTable(TRUE);
    $this->ctr_pageCount = 0;
    $this->ctr_pageHeader($page,$columnDef);
    $row = ' kgridEven';
    //$cnt=count($this->ctr_rosterKids);
    $lineCount = 0;
    for ($i = 0; $i<$this->ctr_roster->kidCount; $i++) {
        $curKid = $this->ctr_roster->kidArray[$i];
        ++$lineCount;
        //if ($lineCount > 40) {
        //    $this->ctr_pageHeader($page,$columnDef);
        //    $lineCount = 0;
        //}
        $page->rowStart('d');
            $page->rpt_col_CellOfText(colROWNUM,'geNum',$i+1);
            $page->rpt_col_CellOfText(colFINAME,'geFirstName',$curKid->prg->FirstName);
            $page->rpt_col_CellOfText(colLANAME,'geLastName',$curKid->prg->LastName);
            $page->rpt_col_CellOfText(colGRADEGRP,'geGrade',$curKid->per->getGradeGroupName());
            $page->rpt_col_CellOfText(colGRADE,'geGrade',$curKid->prg->GradeDesc);
            $page->rpt_col_CellOfText(colROOKIE,'geGrade',kcmAsString_Rookie($curKid,$this->ctr_roster));
    //            $page->cellOfText('colSUBGRP',$curKid->KcmClassSubGroup);
            $page->rpt_col_CellOfText(colWIN,'geWon',$curKid->trn->ctp_playerWon);
            $page->rpt_col_CellOfText(colLOSS,'geLost',$curKid->trn->ctp_playerLost);
            $page->rpt_col_CellOfText(colDRAW,'geDraw',$curKid->trn->ctp_playerDraw);
            $page->rpt_col_CellOfText(colPERCENT,'gePercent',$curKid->trn->ctp_playerPercent);
            $r1 = '';
            $r2 = '';
            if ($curKid->trn->ctp_gameCount>=1) {
                $sep1 = '';
                $sep2 = '';
                for ($j = 0; $j<$curKid->trn->ctp_gameCount; ++$j) {
                    $game = $curKid->trn->ctp_gameArray[$j];
                    if ($game->ctg_classDate == $this->ctr_roster->schedule->entryDateSql) {
                        $r1 .= $sep1 . $game->ctg_getFormatted_result($this->ctr_isExport);
                        $sep1 = ' , ';
                    }
                    else {
                        $r2 .= $sep2 . $game->ctg_getFormatted_result($this->ctr_isExport);
                        $sep2 = ' , ';
                    }
                }
            }
            //$s = $curKid->trn->getFormatted_results();
            $page->rpt_col_CellOfText(colGAMELIST,'geGameList1',$r1);
            $page->rpt_col_CellOfText(colGAMELIST,'geGameList2',$r2);
        $page->rowEnd();
    }

    $page->rpt_tableEnd();

    if ($this->ctr_isExport) {
        $file = $this->ctr_roster->program->getExportName($this->ctr_roster).'-Program';
        $page->export->exportClose($this->ctr_exportType,$file);
    //    $page->export->domSetAutoPage(TRUE);
    //    $page->export->domSetBreakOnNewTable(TRUE);
        $page->frmEnd();
        $page->webPageBodyEnd();
    }
    else {
        //??? $page->frmAddHidden($kcmState->Id, $kcmState->ksConvertToString());
        $page->frmEnd();
        $page->ScreenOnlyEnd();
       // $s = outJavaScript($geEngine);
        $page->webPageBodyEnd($s);
    }

}

function ctr_pageHeader($page,$columnDef) {
    ++$this->ctr_pageCount;
    //if ($this->ctr_pageCount > 1) {
    //    if ( !$this->ctr_isExport )
    //        return;
    //    $page->rpt_tableEnd();
    //    $page->rpt_screenPageBreak(TRUE);
    //}
    $page->rpt_tableStart('kgridTable');
    
    //if (! $this->ctr_isExport) {
    //    echo '<tr>';
    //    $page->rowStart('h');
    //    dxxbg('fix below');
    //    // $s = $this->ctr_gameGroup->gameTypeDesc.' Tournament';
    //    $s = ' ??? Tournament';
    //    $page->rpt_cellOfText('geGuiGridHeadGame', $s, 'colspan="10"');
    //    //$page->cellStart('geGuiGridHeadGame', 'colspan="10"');
    //    //$page->textOut($this->ctr_gameGroup->gameTypeDesc.' Tournament');
    //    //$page->cellEnd();
    //    $page->rowEnd();
    //    echo '</tr>';
    //}

    $page->rowSetClasses(array('kgridEven','kgridOdd'));
    //********* Cross Table - Print Header row **********
    $page->rowStart('h');
    $page->rpt_col_Header (colROWNUM,'geNum kgridHead','Num');
    $page->rpt_col_Header (colFINAME,'geFirstName kgridHead','First Name');
    $page->rpt_col_Header (colLANAME,'geLastName kgridHead','Last Name');
    $page->rpt_col_Header (colGRADEGRP,'geGradeGroup kgridHead','Grade<br>Group');
    $page->rpt_col_Header (colGRADE,'geGrade kgridHead','Grade');
    $page->rpt_col_Header (colROOKIE,'geRookie kgridHead','R<br>V');
    //$page->rpt_col_Header (colSUBGRP,'geSubGroup kgridHead','Class<br>Sub-Group');
    $page->rpt_col_Header (colWIN,'geWon kgridHead','Win');
    $page->rpt_col_Header (colLOSS,'geLost kgridHead','Loss');
    $page->rpt_col_Header (colDRAW,'geDraw kgridHead','Draw');
    $page->rpt_col_Header (colPERCENT,'gePercent kgridHead','Percent');
    $dateDesc  = $this->ctr_roster->schedule->entryDateObject->format( "F j" );
    $page->rpt_col_Header (colGAMELIST,'geGameList1 kgridHead','Games this Week<br>'.$dateDesc);
    $page->rpt_col_Header (colGAMELIST,'geGameList2 kgridHead','Games<br>Other Weeks');
    $page->rowEnd();
}

} // end class

//======
//============
//==================
//========================
//= Section
//========================
//==================
//============
//======

Class kcm_crosstable_section {

public $options;
public $allGamesArray;
public $allGamesCount;
private $ctr_roster;
private $isDetails;   // include opponents and games (can only add games if not detailed view)
private $db;
private $periodId;
private $programId;
private $gameTypeIndex;

//%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%????????????????????? is pDetails needed
function __construct ($db, $pRoster, $pPeriodId, $pGameTypeIndex, $pDetails=FALSE) {
    $this->db = $db;
   // kcm_crosstable_game::$kcmState = NULL; // ????????????? $pKcmState;
    $this->roster = $pRoster;
    $this->isDetails = $pDetails;
    $this->periodId = $pPeriodId;
    $this->programId = $pRoster->program->ProgramId;
    $this->gameTypeIndex = $pGameTypeIndex;
    $this->options = new kcm_tournament_options($pGameTypeIndex);
}

function cts_loadThis_section() {
    //--- clear sort arrays
    $sortKidGameId = array();   // to connect game with player
    $sortKidGameType = array();
    $sortKidGameObject = array();
    $sortPairingId = array ();    // to connect games with same @gameId (same game - each player has a record for it)
    $sortPairingObject = array ();
    //--- create player array from roster (which must be pre-sorted for cross-table order)
    for ($i = 0; $i<$this->roster->kidCount; ++$i) {
        $ctp_kid =  $this->roster->kidArray[$i];
        $ctp_kid->trn = new kcm_crosstable_player($ctp_kid);
        $ctp_kid->trn->ctp_status = $ctp_kid->ttt[$this->gameTypeIndex];  //??? make sure it's created before and initialized
        $ctp_kid->trn->ctp_rank = $i + 1;
        $sortKidGameId[] = $ctp_kid->per->KidPeriodId;
        $sortKidGameType[] = 1;   // ctp_kid period object
        $sortKidGameObject[] = $ctp_kid;
    }
    //--- read gameArray for current tournament
    $this->allGamesCount = 0;
    $this->allGamesArray = array();
    $fieldList = "*";
    $sql = array();
    $sql[] = "SELECT ".$fieldList;
    $sql[] = "FROM `gp:games`";
    $sql[] = "WHERE `gpGa:@PeriodId` ='".$this->periodId."'";
    $sql[] = "   AND `gpGa:GameTypeIndex` ='".$this->gameTypeIndex."'";
    $sql[] = "ORDER BY  `gpGa:@KidPeriodId`, `gpGa:ClassDate`";  //~~15/08
    $query = implode( $sql, ' ');
    $result = $this->db->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    while($row=$result->fetch_array()) {
        $game = new kcm_crosstable_game;
        $game->ctg_loadGameRow($row);
        $this->allGamesArray[] = $game;
        ++$this->allGamesCount;
        $sortKidGameId[] = $game->ctg_atKidPeriodId;
        $sortKidGameType[] = 2;  // game obect
        $sortKidGameObject[] = $game;
        if ($game->ctg_atGameId >= 1) {
            $sortPairingId[] = $game->ctg_atGameId;
            $sortPairingObject[] = $game;
        }
    }
    //---- associate all games to the appropriate ctp_kid
    array_multisort ($sortKidGameId, $sortKidGameType, $sortKidGameObject);
    $count = count($sortKidGameId);
    dbxxg('fix line two below AND isempty many lines down',$sortKidGameType[$i],$this->gameTypeIndex);
    for ($i = 0; $i < $count; ++$i) {
         if ( $sortKidGameType[$i] == 1) { //???? $this->ctr_gameType  //??????????? 1 @?@?@?@?@?@?@?@
             $curKid = $sortKidGameObject[$i];
         }
         else {
            $game = $sortKidGameObject[$i];
            $game->ctg_gameKid = $curKid;
            //$curKid->trn->ctp_playerWon += $game->ctg_gamesWon;  // php 5.
            //$curKid->trn->ctp_playerLost += $game->ctg_gamesLost;
            //$curKid->trn->ctp_playerDraw += $game->ctg_gamesDraw;
            $curKid->trn->ctp_playerWon .= $game->ctg_gamesWon;   // php 7.
            $curKid->trn->ctp_playerLost .= $game->ctg_gamesLost;
            $curKid->trn->ctp_playerDraw .= $game->ctg_gamesDraw;
            // $curKid->trn->ctp_playerPercent += '??'; // works in php 5.
            $curKid->trn->ctp_playerPercent .= '??';   // changed for php 7
            $curKid->trn->ctp_gameArray[] = $game;
            ++$curKid->trn->ctp_gameCount;
         }
    }
    //---- generate pairings (from game records - paired together if same @GameId)
    array_multisort ($sortPairingId, $sortPairingObject);
    $count = count($sortPairingId);
    $curPairingId = 0;
    for ($i = 0; $i < $count; ++$i) {
        $game =  $sortPairingObject[$i];
        $curKid = $game->ctg_gameKid;
        if ($game->ctg_atGameId != $curPairingId) {
            $curPairingId = $game->ctg_atGameId;
            $gpArray = array();
            $gpCount = 0;
        }
        $gpArray[] = $game;
        ++$gpCount;
        if ($gpCount >= 2) {
            for ( $j=0; $j < $gpCount-1; ++$j) {  // do not include last game
                $opGame = $gpArray[$j];
                $opGame->ctg_oppGameArray[] = $game;
                ++$opGame->ctg_oppGameCount;
                $game->ctg_oppGameArray[] = $opGame;
                ++$game->ctg_oppGameCount;
            }
        }
    }
    
    for ($i = 0; $i<$this->roster->kidCount; ++$i) {
        $ctp_kid =  $this->roster->kidArray[$i];
        $ctp_kid->trn->ctp_playerWon = empty($ctp_kid->trn->ctp_playerWon) ? 0: $ctp_kid->trn->ctp_playerWon;
        $ctp_kid->trn->ctp_playerLost = empty($ctp_kid->trn->ctp_playerLost) ? 0: $ctp_kid->trn->ctp_playerLost;
        $ctp_kid->trn->ctp_playerDraw = empty($ctp_kid->trn->ctp_playerDraw) ? 0: $ctp_kid->trn->ctp_playerDraw;
        $ctp_kid->trn->ctp_playerPercent = kcm_gamePercent($ctp_kid->trn->ctp_playerWon,$ctp_kid->trn->ctp_playerLost,$ctp_kid->trn->ctp_playerDraw);
    }
}

} // end class

//======
//============
//==================
//========================
//= Game
//========================
//==================
//============
//======

Class kcm_crosstable_game {
public $ctg_gameId;          // gpGa:GameId
public $ctg_atProgramId;     // gpGa:@ProgramId
public $ctg_atPeriodId;      // gpGa:@ProgramId
public $ctg_atKidPeriodId;   // gpGa:@KidPeriodId
public $ctg_atGameId;        // gpGa:@GameId   // links several $GameIds in this table as same game
public $ctg_gameTypeIndex;   // gpGa:GameTypeKey
public $ctg_resultCode;      // gpGa:ResultCode   //~~15/08 changing to use only for bughouse
public $ctg_gamesWon;        // gpGa:GamesWon
public $ctg_gamesLost;       // gpGa:GamesLost
public $ctg_gamesDraw;       // gpGa:GamesDraw
public $ctg_whenCreated;     // gpGa:When Created   // @jpr 18-02
public $ctg_classDate;       // gpGa:ClassDate
public $ctg_modByAtStaffId;  // gpGao:ModBy@StaffId
public $ctg_modWhen;         // gpGa:ModWhen
// computed
public $ctg_gameKid;         // player record
public $ctg_oppGameArray; // array of associated opponent game objects  - does not include self
public $ctg_oppGameCount; // array of associated opponent game objects  - does not include self
//public $bughouseGameResultCode;  // for radio button name/value - needed for bughouse only
//public static $kcmState;
public $ctg_status;  // unused ??????

function __construct () {
    //%%%%%%%??????????? clear should not be needed
    $this->ctg_clear();
}

function  ctg_clear() {
    $this->ctg_gameId = 0;
    $this->ctg_atProgramId = 0;
    $this->ctg_atPeriodId = 0;
    $this->ctg_atKidPeriodId = 0;
    $this->ctg_atGameId = 0;
    $this->ctg_gameTypeIndex = 0;
    $this->ctg_resultCode = ' ';
    $this->ctg_gamesWon = 0;
    $this->ctg_gamesLost = 0;
    $this->ctg_gamesDraw = 0;
    $this->ctg_classDate = '';
    $this->ctg_whenCreated = '00-00-00 00:00:00';     // @jpr 18-02
    $this->ctg_modByAtStaffId = '';
    $this->ctg_modWhen= '';
    $this->ctg_gameKid = NULL;
    $this->ctg_oppGameArray = array();
    $this->ctg_oppGameCount = 0;
}

function ctg_loadGameRow($pRow) {
    $this->ctg_gameId          = $pRow['gpGa:GameId'];
    $this->ctg_atProgramId     = $pRow['gpGa:@ProgramId'];
    $this->ctg_atPeriodId      = $pRow['gpGa:@PeriodId'];
    $this->ctg_atKidPeriodId   = $pRow['gpGa:@KidPeriodId'];
    $this->ctg_atGameId        = $pRow['gpGa:@GameId'];
    $this->ctg_gameTypeIndex   = $pRow['gpGa:GameTypeIndex'];
    $this->ctg_resultCode      = $pRow['gpGa:ResultCode'];
    $this->ctg_gamesWon        = $pRow['gpGa:GamesWon'];
    $this->ctg_gamesLost       = $pRow['gpGa:GamesLost'];
    $this->ctg_gamesDraw       = $pRow['gpGa:GamesDraw'];
    $this->ctg_whenCreated     = $pRow['gpGa:WhenCreated'];  // @jpr 18-02
    $this->ctg_modByAtStaffId  = $pRow['gpGa:ModBy@StaffId'];
    $this->ctg_classDate       = $pRow['gpGa:ClassDate'];
    $this->ctg_modWhen         = $pRow['gpGa:ModWhen'];
    $this->ctg_oppGameArray    = array();
    $this->ctg_oppGameCount    = 0;
}

function ctg_readThisGame($pDb,$pGameId) {
    $sql = array();
    $sql[] = "SELECT *";
    $sql[] = "FROM `gp:games`";
    $sql[] ="WHERE `gpGa:GameId` ='{$pGameId}'";
    $query = implode( $sql, ' ');
    $result = $pDb->rc_query( $query );
    if ($result === FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    if ($row=$result->fetch_array() ) {
        $this->ctg_loadGameRow($row);
    }
    else {
        kcm_db_CriticalError( __FILE__,__LINE__,'Expected Game Record not found');
    }
}

function ctg_saveThisGame($pDb, $pSetAtGameId,$classPointsDate, $players) {  // @jpr@kcm2 - added $players param
   if ($this->ctg_gameId==0) {
        $this->ctg_whenCreated = rc_getNow();  // @jpr@kcm2 change
        $q = new rc_saveToDbQuery( $pDb, 'gp:games', "INSERT INTO" );
        $q->setFieldVal('gpGa:@ProgramId',    $this->ctg_atProgramId );
        $q->setFieldVal('gpGa:@PeriodId',     $this->ctg_atPeriodId );
        $q->setFieldVal('gpGa:@KidPeriodId',  $this->ctg_atKidPeriodId );
        $q->setFieldVal('gpGa:@GameId',       $this->ctg_atGameId );
        $q->setFieldVal('gpGa:GameTypeIndex', $this->ctg_gameTypeIndex );
        $q->setFieldVal('gpGa:WhenCreated', $this->ctg_whenCreated);   // @jpr@kcm2 change
        $this->ctg_classDate = $classPointsDate;
        //$this->ctg_whenCreated = $classPointsDateTime;
    }
    else {
        $q = new rc_saveToDbQuery( $pDb, 'gp:games', "UPDATE" );
        $q->setWhere("`gpGa:GameId` = '{$this->ctg_gameId}'" );
    }
    $this->ctg_modWhen = rc_getNow();
    $q->setFieldVal('gpGa:ResultCode', $this->ctg_resultCode );
    $q->setFieldVal('gpGa:GamesWon',   $this->ctg_gamesWon );
    $q->setFieldVal('gpGa:GamesLost',  $this->ctg_gamesLost );
    $q->setFieldVal('gpGa:GamesDraw',  $this->ctg_gamesDraw );
    $q->setFieldVal('gpGa:OriginCode',  0 );  // @jpr@kcm2
    // @jpr@kcm2 change - START
    $op = '';
    echo '  GC=' . $this->ctg_oppGameCount. ' - ' . count($this->ctg_oppGameArray);
    for ($i=0; $i<count($players); ++$i) {
        if ($players[$i] != $this->ctg_atKidPeriodId) {
            if ($op != '') {
                $op .= ',';
            }
           $op .= $players[$i];
        }
    }
    $q->setFieldVal('gpGa:Opponents',  $op );
    // @jpr@kcm2 change - END
    $this->ctg_modByAtStaffId  = $pDb->rc_MakeSafe( rc_getStaffId() );
    $q->setFieldVal('gpGa:ClassDate', $this->ctg_classDate);
    //$q->setFieldVal('gpGa:WhenCreated', $this->ctg_whenCreated);
    $q->setFieldVal('gpGa:ModBy@StaffId',$this->ctg_modByAtStaffId );
    $q->setFieldVal('gpGa:ModWhen', $this->ctg_modWhen);
    $result = $q->doQuery();
    if ($result == FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
    if ($this->ctg_gameId==0) {
        $this->ctg_gameId = $pDb->insert_id;
        if ($pSetAtGameId) {
            $this->ctg_atGameId = $this->ctg_gameId;
            $x = $this->GameId;
        }
        $q = new rc_saveToDbQuery( $pDb, 'gp:games', "UPDATE" );
        $q->setWhere("`gpGa:GameId` = '{$this->ctg_gameId}'" );
        $q->setFieldVal('gpGa:@GameId',  $this->ctg_atGameId );
    }
    $result = $q->doQuery();
    if ($result == FALSE) {
        kcm_db_CriticalError( __FILE__,__LINE__);
    }
}

function ctg_getFormatted_points() {
    $s = '';
    for ($i=0; $i < $this->ctg_gamesWon; ++$i) {
       $s .= 'W';
    }
    for ($i=0; $i < $this->ctg_gamesLost; ++$i) {
       $s .= 'L';
    }
    for ($i=0; $i < $this->ctg_gamesDraw; ++$i) {
       $s .= 'D';
    }
    if (strlen($s)>1) {
       $s = $s . '-';
    }
    return $s;
}

function ctg_getFormatted_opponents() {
    $s = '';
    $sep = '';
    for ($i = 0; $i < $this->ctg_oppGameCount; ++$i) {
        $opGame = $this->ctg_oppGameArray[$i];
        $opKid = $opGame->ctg_gameKid;
        $s .= $sep;
        $sep = ',';
        $s .= ($opKid->per->sortIndex + 1);
    }
    return $s;
}

function ctg_getFormatted_result($isExport) {
    $s = $this->ctg_getFormatted_points() . $this->ctg_getFormatted_opponents();
    if (! $isExport) {
        $url = 'need-to-fix'; // self::$kcmState->convertToUrl('kcm-game_entry.php',array('Submit','ctlink','GEct',$this->ctg_atGameId));
        $s = '<a href="'.$url.'">'.$s.'</a>';
    }
    return $s;
}

} // end class

//============
//==================
//========================
//= Options
//========================
//==================
//============
//======

Class kcm_tournament_options {
public $key;
public $desc;
public $gamePoints;
function __construct($pGameType) {
    $this->key = $pGameType;
    if ($pGameType==kcmGAME_CHESS_INDEX) {
        $this->desc = 'Chess';
        $this->gamePoints = 10;
    }
    if ($pGameType==kcmGAME_BUGHOUSE_INDEX) {
        $this->desc = 'Bughouse';
        $this->gamePoints = 3;  //~~15/08
    }
    if ($pGameType==kcmGAME_BLITZ_INDEX) {
        $this->desc = 'Blitz';
        $this->gamePoints = 5;
    }
}

} // end class

//======
//============
//==================
//========================
//= Player
//========================
//==================
//============
//======

Class kcm_crosstable_player {
    public $ctp_kid;         //?????????????
    public $ctp_kidProgram;  //?????????????
    public $ctp_kidPeriod;
    public $ctp_status;  // totals and status for current tournament - from game totals - not game table
    // computed  (the results in $status **SHOULD** be the same as the totals from the game records
    // but the game records may no longer exist for the tournament if previous semesters
    //???? maybe need an isArchived flag in status record for each status record, or have some other mechanism to indicate this
    public $ctp_playerWon;
    public $ctp_playerLost;
    public $ctp_playerDraw;
    public $ctp_playerPercent;
    public $ctp_gameArray;
    public $ctp_gameCount;
    public $ctp_rank;   // row on crosstable  (if pre-sorted then same as index plus 1)

function __construct ($pRosterKid) {
    $this->ctp_gameArray = array();
    $this->ctp_gameCount = 0;
    $this->ctp_kid = $pRosterKid;
    $this->ctp_kidPeriod = $this->ctp_kid->per;
    $this->ctp_kidProgram = $this->ctp_kid->prg;
    $this->ctp_playerWon = '';
    $this->ctp_playerLost = '';
    $this->ctp_playerDraw = '';
    $this->ctp_playerPercent = '';
}

//function getFormatted_results() {
//    if ($this->ctp_gameCount==0) {
//        return '';
//    }
//    $s = '';
//    $sep = '';
//    for ($i = 0; $i<$this->ctp_gameCount; ++$i) {
//        $game = $this->ctp_gameArray[$i];
//        $s .= $sep . $game->ctg_getFormatted_result();
//        $sep = ' , ';
//    }
//   return $s;
//}

}

?>