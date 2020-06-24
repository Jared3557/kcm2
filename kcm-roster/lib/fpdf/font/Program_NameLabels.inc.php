<?php

include_once( 'lib/roster_ProgramData.inc.php' );
include_once( 'lib/roster_Sorted.inc.php' );
include_once( 'lib/roster_Engine.inc.php' );

$paramOutput = getParam('Out','View');
$paramProgramId = getParam('ProgramId',196);
   
$title = "Name Labels";
$windowTitle = "Name Labels";

if (RC_LOCAL) {
	$windowTitle .= " [LOCAL]";
}
else if (RC_TESTING) {
	$windowTitle .= " [TESTING]";
}

//===========================================================

// Name Labels Report

function openReport () {
    global $report,$paramOutput;
    $report = new rc_reportEngine($paramOutput,rpPAGE_PORTRAIT,'Roster');
    $report->page->define_PageMargins(54,0,54,0);
//    $report->page->define_PageMargins(0,0,0,0);
    $report->globalSettings_Start(); 
    $report->styleGlobal->fontHeight=9;
    $report->Report_AddFont('Comic');
//    $report->Report_AddFont('Comicbd',TRUE);
    $report->styleGlobal->fontName='Comic';
    $report->styleGlobal->border->setAll(rpBORDER_NONE,rpBORDER_NONE,rpBORDER_NONE,rpBORDER_NONE);
    $report->styleGlobal->rowHeight=142;
    $report->styleGlobal->pad->setAll(2,-2,2,2);  
    $report->layout->add_Column(290);
    $report->layout->add_Column(290);
    $report->globalSettings_End(FALSE);
}

function rpt_OnHeading() {
    global $report;
    global $progName, $periodName, $roster;
    $report->styleRow->rowHeight=10;
    $report->row_Start();   // default for borders is now table default
    $report->cellOfText("",2);
    $report->row_End();
    $report->styleRow->rowHeight=10;
    $report->row_Start();   // default for borders is now table default
    $report->cellOfText($progName.' - Name Labels - '.$roster->classDate,2);
    $report->row_End();
}

function rpt_OnFooter() {
    global $report;
    //$report->styleRow->border->bottom = rpBORDER_THICK;
}

if (isset( $_SESSION['report_PointTally']['ProgramId'] ))
   $programId = $_SESSION['report_PointTally']['ProgramId'];
else
   $programId = "196";

$classDate = 'November 33, 2013 (Wednesday)';

rc_showMessages();
$paramProgramId = getParam('ProgramId',196);

$db = new rc_database();
$rosterEngine = new rc_rosterEngine();  // will eventually get from session
$roster = $rosterEngine->open($paramProgramId,$db,FALSE);
$sortedRoster = new rc_rosterSorted;
$sortedRoster->sort_ForTally($roster);

$progName = $roster->program->name;

//--- Start Report
OpenReport();  // can do processing before this step, but nothing will be printed during this step

$prevPeriod = "";
$borderCount = 0;
$prevPeriod = "";
$periodName = "1st";
$altCount = 1;
$gridCount = 0;
$curItem = 0;
$curRow = 0;
$curIndex = 0;
while($curIndex<=count($sortedRoster->kidList)) {
   if ($curRow>=5) {
      $report->pageBreak();
      $curRow = 0;
   }
   $report->row_Start();
   for ($i=1; $i<3; $i++) {
       if ($curIndex<count($sortedRoster->kidList)) {
           $k = $sortedRoster->kidList[$curIndex];
           if ($k->periodSequenceBits==$k->periodCombinedBits)
               $period = $k->periodName." Period";
           else if ($k->periodCombinedBits==3)
               $period = "1st-2nd Period";  // different if camp
           else
               $period = "Multiple Periods";
           $top = 50;
           $left = -80;           
           $report->customCell_Start();
           $nameFirst = markNameConfict($k->nameFirst,$k->nameConflict);
           $report->customCell_Text ($left+160, 120, $top+3, $nameFirst, 44);
           $report->customCell_Text ($left+160, 120, $top+45, $k->nameLast, 34);
           $report->customCell_Text ($left+60,  120, $top+70, $k->gradeDesc." Grade", 22);
           $report->customCell_Text ($left+175, 120, $top+70, $period, 20);
           $report->customCell_Text ($left+80,  120, $top+85, $k->teacher, 14);
//           $report->customCell_Text ($left+160,  $top+110, $k->pickupDesc, 12);
           $report->CustomCell_Image($left+70, $top-175,60,60,"reports/kcm-kernel/images/LogoForLabels.jpg");
           $pickupImage =NULL;
           if ($k->pickupCode==1) { // ASP
               if ($k->periodCombinedBits==1)
                  $pickupImage = "reports/kcm-kernel/images/ASP_1st.jpg";
               else   
                  $pickupImage = "reports/kcm-kernel/images/ASP_2nd.jpg";
           }
           if ($k->pickupCode==2) { // parent
               if ($k->periodCombinedBits==1)
                  $pickupImage = "reports/kcm-kernel/images/CarPool_1st.jpg";
               else   
                  $pickupImage = "reports/kcm-kernel/images/CarPool_2nd.jpg";
           }
           if ($pickupImage!=NULL)
               $report->CustomCell_Image($left+200,$top-65,42,26,$pickupImage);
           $report->customCell_End();
           }
       else
           $report->cellOfText("");       
       $curIndex = $curIndex +1;
   }    
   $report->row_End();
   $curRow = $curRow + 1;
}
$report->report_Close();
?>
