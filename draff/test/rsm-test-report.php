<?php

// wip-test-report.php

include_once( '../../../rc_defines.inc.php' );
include_once( '../../../rc_admin.inc.php' );
include_once( '../../../rc_database.inc.php' );
//include_once( '../../../rc_rsm-message-errors.inc.php' );

include_once( '../../rsm/rsm-emitter.inc.php' );
// include_once( '../rsm/rsm-form.inc.php' );
include_once( '../../rsm/rsm-functions.inc.php' );

include_once( '../rsm-emitter-dom-engine.inc.php' );
include_once( '../rsm-emitter-dom-export.inc.php' );


//$test = 'h';
//$test = 'e';
//$test = 'h';
$test = 'a'; // all in test mode
$test = 'h';

rc_session_initialize();


//rsm_functions_init();


//rpt_test_token();
rpt_test_cssEngine();

//assert('3>4','test of assert');

//    if ( $test == 'h') {
//        $report->export_as_html();
//        $emitter->zone_body_end();
//        //$emitter->zone_core_container_end();
//    }
//
//    if ( $test == 'e') {
//        $report->export_as_excel($test == 'E');
//    }
//
//    if ( $test == 'p'){
//        $report->export_as_pdf($test == 'P');
//    }
//    if ( $test == 'a'){
//        $emitter->zone_htmlHead();
//        $emitter->zone_body_start('rsm-zone-body-standard');
//        print '<br><hr>HTML<hr>';
//        $report->export_as_html();
//        $emitter->zone_body_end();
//        print '<br><hr>PDF DEBUG<hr>';
//        $report->export_as_pdf();
//        print '<br><br><hr>EXCEL DEBUG<hr>';
//        $report->export_as_excel(); // must be last due to exit at end
//    }

$emitter      = new rsm_emitter_engine(NULL,'p');
$report       = $emitter;

//$report = new rsm_emitDom_engine($emitter);
$report->head_add_cssStyle ( 'table.rpt-table', 'margin: 20pt 0pt 0pt 0pt;border-collapse:collapse;border-spacing:0;empty-cells:show;');
$report->head_add_cssStyle (    'td', 'border:1px solid black; vertical-align:top;');
$report->head_add_cssStyle ( 'div.rpt-segDate', 'border:1px solid red; background-color: yellow; margin: 2pt 4pt 2pt 4pt;');
$report->head_add_cssStyle ( 'div.rpt-segTime', 'border:1px solid red; background-color: aqua; margin: 2pt 4pt 2pt 4pt;');
$report->head_add_cssStyle ( 'div.rpt-segWhen', 'border:1px solid red; background-color: gray; padding: 4pt;margin: 2pt 4pt 2pt 4pt;');
$report->head_add_cssStyle ( 'td.rpt-name', 'border:1px solid black; background-color: #ddffff;');
$report->head_add_cssStyle ( 'td.rpt-phone', 'border:1px solid black; background-color: #ddddff;');
$report->head_add_cssStyle ( 'td.rpt-addr', 'border:1px solid black; background-color: #ffddff;');
$report->head_add_cssStyle ( 'td.rpt-date', 'border:1px solid black; background-color: #ffffdd;');
$report->head_add_cssStyle ( 'td.rpt-time', 'border:1px solid black; background-color: #ffccdd;');
$report->head_add_cssStyle ( 'td.rpt-when', 'border:1px solid black; background-color: #ffaadd;');
//$report->head_add_cssStyle ( 'td.rpt-head', 'border:1px solid black; padding: 3pt; font-size:140%; font-weight:bold; background-color: #dddddd;');
$report->head_add_cssStyle ( 'td.rpt-footer', 'border:1px solid black; padding: 3pt; background-color: #888888;');
$report->head_add_cssStyle (  'tr.rpt-odd', 'border-top:3px solid black;');
$report->head_add_cssStyle ( 'tr.rpt-even', 'border-bottom:3px solid black;');
$report->head_add_cssStyle (  'td.rpt-odd', 'border-top:3px solid black;');
$report->head_add_cssStyle ( 'td.rpt-even', 'border-bottom:3px solid black;');
$report->head_add_cssStyle (  'thead', 'border:4px solid red; vertical-align:top;background-color:#cccccc;');

if ( $test == 'h') {
    $report->zone_htmlHead();
    $report->zone_body_start('rsm-zone-body-standard');
}

//$emitter->htmlHead_addLine("<link rel='stylesheet' type='text/css' media='all' href='../rsm/rsm-sty-leSheet.c-s-s'>");

    //$emitter->zone_core_container_start('sy-genre-default', $chain);
    //$emitter->zone_core_scrollArea_start('sy-genre-default');
  //  print '<h2>WIP - test of Report Object</h2><br><hr style="height:2px;width:100%;background-color:black;"><br>';
  
  //  $report->rpt_page_header_start('xxx');
  //  //$report->table_start('rpt-table',2);
  //  $report->column_define('@name','rpt-name');
  //  $report->column_define('@address','rpt-addr');
  //  $report->table_row_all_Cells('class','Test',rc_getNow());
  // //  $report->table_end();
  // $report->rpt_page_header_end();
  
    $report->table_start('rpt-table',6);
    //$report->column_define('@name','rpt-name');
    //$report->column_define('@phone','rpt-phone',RPT_TYPE_PHONE);
    //$report->column_define('@address','rpt-addr');
    //$report->column_define('@date','rpt-date',RPT_TYPE_DATE);
    //$report->column_define('@time','rpt-time',RPT_TYPE_TIME);
    //$report->column_define('@when','rpt-when',RPT_TYPE_WHEN);
    
    $report->table_head_start('rpt-head');
    
    $report->row_start('');
    $report->cell_block('Left','','colspan="2"');
    $report->cell_block(NULL);
    $report->cell_block('Middle','','colspan="2" rowspan="2"');
    $report->cell_block(NULL);
    $report->cell_block('Right','','colspan="2"');
    $report->cell_block(NULL);
    $report->row_end();
    
    $report->row_start('');
    $report->cell_block('Middle-2','','colspan="2"');
    $report->cell_block(NULL);
    $report->cell_block(NULL);
    $report->cell_block(NULL);
    $report->cell_block('Right-2','','colspan="2"');
    $report->cell_block(NULL);
    $report->row_end();
    
    $report->row_start('');
    $report->cell_block('Info','','colspan="2"');
    $report->cell_block(NULL);
    $report->cell_block(NULL);
    $report->cell_block('When','','colspan="2"');
    $report->cell_block(NULL);
    $report->cell_block(NULL);
    $report->row_end();

    //$parcel1 = $report->rpt_create_spannedCell_parcel('Info','',3,1);
    //$parcel2 = $report->rpt_create_spannedCell_parcel('When','',3,1);
    //$report->table_row_all_Cells('',$parcel1,NULL, NULL, $parcel2,NULL, NULL);
    $report->row_start('');
    $report->cell_block('Names');
    $report->cell_block('Phone');
    $report->cell_block('Address');
    $report->cell_block('Date');
    $report->cell_block('time');
    $report->cell_block('when');
    $report->row_end();
    $report->table_head_end();
    
    $report->table_body_start('');
    
    $report->row_start('');
    $report->cell_block('aaa');
    $report->cell_block('111-222-3333');
    $report->cell_block('5 aaa');
    $report->cell_block('2011-01-01','');
    $report->cell_block('05:05:01'  ,'');
    $report->cell_block('2011-01-01 01:01:01');
    $report->row_end();
    
    $report->row_start('');
    $report->cell_block('bbb');
    $report->cell_block('222-222-3333');
    $report->cell_block('2 aaa');
    $report->cell_block('2011-02-02','');
    $report->cell_block('04:04:01'  ,'');
    $report->cell_block('2022-02-02 02:02:02');
    $report->row_end();
    
//    $report->table_row_all_Cells('','ddd1','444-222-3333','2 aaa','2011-03-03','03:03:01','2022-02-02 02:02:02');
//    $report->table_row_all_Cells('','ddd2','444-222-3333','2 aaa',array('2011-03-03','',array('#id'=>'@date'))
//        ,array('03:03:01','',array('#id'=>'@time')),'2022-02-02 02:02:02');

    $report->row_start('');
    $report->cell_block('ccc');
    $report->cell_block('333-222-3333');
    $report->cell_block('2 aaa');
    $report->cell_start();
        $report->div_start('rpt-segWhen');
        $report->div_block('2011-04-04','rpt-segDate');
        $report->div_block('02:02:02','rpt-segTime');
        $report->div_end('');
    $report->cell_end();
    $report->cell_block('02:02:02');
    $report->cell_block('2022-02-02 02:02:02');
    $report->row_end();

    $report->row_start('');
    $report->cell_block('eee');
    $report->cell_block('555-222-3333');
    $report->cell_block('2 aaa');
    $report->cell_block('2011-05-05','');
    $report->cell_block('01:01:01'  ,'');
    $report->cell_block('2022-02-02 02:02:02');
    $report->row_end();

//    $report->table_body_end();

//    $report->table_row_all_Cells('','aaa','111-222-3333','5 aaa','2011-01-01','01:01:01','2011-01-01 01:01:01');
//    $report->table_row_all_Cells('','eee','111-222-3333 ext 12','1 eee','2015-02-02','14:02:02','2012-02-02 14:02:02');
//    $report->table_row_all_Cells('','bbb','111-222-3333','4 bbb','2012-02-02','14:02:02','2012-02-02 14:02:02');
//    $report->table_row_all_Cells('','ddd','111-222-3333',array('2',RPT_NEW_LINE,'bbb'),'2014-02-02','14:02:02','2012-02-02 14:02:02');
//    $specsDate = array ( 'id'=>'@date','type'=>RPT_TYPE_DATE, 'format'=>'D, M j, Y');
//    $specsTime = array ( 'id'=>'@time','type'=>RPT_TYPE_TIME);
//    $parcelDateDiv = $report->rpt_create_div_parcel('rpt-segDate','2013-02-02',$specsDate);
//    $parcelTimeDiv = $report->rpt_create_div_parcel('rpt-segTime','14:02:02',$specsTime);
//    $parcelWhenDiv = $report->rpt_create_div_parcel('rpt-segWhen',array($parcelDateDiv,$parcelTimeDiv)); // root
//    // $segWhenCell = $report->rpt_create_cell_root('rpt-segWhen',$parcelWhenDiv,'',1,1); // root
//    $report->table_row_all_Cells('','ccc','111-222-3333','3 ccc',$parcelWhenDiv,'14:02:02','2012-02-02 14:02:02');
    
    $report->table_body_end();
   // $report->table_foot_start('rpt-footer');
   // $report->table_row_all_Cells('','-','-','-');
   // $report->table_foot_end();
    $report->table_end();
    
     //   $report->export_as_html();  //????????????
        $emitter->zone_body_end();    //????????????
 //   $report->table_sort('@table1', array('@date','#asc'));
    $report->row_alternate_cssClasses('@table1', array('rpt-odd','rpt-even'));
    
//    $report->table_start('@table3','rpt-table');
//    $report->column_define('@name','rpt-name');
//    $report->column_define('@address','rpt-addr');
//    $report->column_define('@phone','rpt-phone');
//    $report->column_define('@phone-2','rpt-phone');
//    $report->table_head_start('rpt-head');
//    $report->table_row_all_Cells('','name','address','phone','phone-2');
//   // $report->table_row_all_Cells('','bbb','1 bbb','2222');
//    $report->table_head_end();
//    $report->table_body_start('');
//    $mergeCell = $report->rsm_cell_enhanced_create('title','',2,2);
//    $report->table_row_all_Cells('',$report->rsm_report_cell_enhanced('title','',4,1),NULL,NULL,NULL);
//    $report->table_row_all_Cells('','xxxx','1 aaa','1111', '5555');
//    $report->table_row_all_Cells('','yyyy',$mergeCell,NULL,'6666');
//    $report->table_row_all_Cells('','zzzz',NULL,NULL,'77777');
//    $report->table_row_all_Cells('','zzzz','3 ccc','3333','77777');
//    $report->table_body_end();
//    $report->table_end();
//
//
//
//
//
//    $report->table_start('@table2','rpt-table');
//    $report->column_define('@name','rpt-name');
//    $report->column_define('@address','rpt-addr');
//    $report->column_define('@phone','rpt-phone');
//    $report->column_define('@phone-2','rpt-phone');
//    $report->table_head_start('rpt-head');
//    $report->table_row_all_Cells('','name','address','phone','phone-2');
//   // $report->table_row_all_Cells('','bbb',array('1'RPT_NEW_LINE,'bbb'),'2222');
//    $report->table_head_end();
//    $report->table_body_start('');
//    $segFirst = new rsm_report_cell_segment_span($first,'rpt-first');
//    $segLast  = new rsm_report_cell_segment_span($last,'rpt-last');
//    $segNote  = new rsm_report_cell_segment_span(array($line1,$line2),'rpt-last');
//    $segName  = new rsm_report_cell_segment_span(array($segFirst,$segLast,$segNote),'rpt-name');
//    ?????? does a cell segment need to be created ????????
//    $mergeCell = new rsm_report_cell_enhanced('title','',1,2);
//    $report->table_row_all_Cells('','xxxx','1 aaa','1111', '5555');
//    $report->table_row_all_Cells('','yyyy',array('2',RPT_NEW_LINE,'bbb'),'2222','6666');
//    $report->table_row_all_Cells('','zzzz','3 ccc','3333','77777');
//    $report->table_body_end();
//    $report->table_end();


//   exit;  // why is this needed ?????????????
?>

