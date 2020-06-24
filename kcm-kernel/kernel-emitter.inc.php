<?php

// kernel-emitter.inc.php

class kcmKernel_emitter extends Draff_Emitter_Engine {
private $appGlobals;
public $krmEmit_banner_title1='';
public $krmEmit_banner_title2='';
private $krmEmit_pageTitle;  // for bookmarks, tabs, etc
private $krmEmit_options;  // for banner

function __construct ($appGlobals, $form, $bodyStyle='',$exportType='h') {
    $this->appGlobals = $appGlobals;
    parent::__construct($form, $bodyStyle, $exportType);
    $this->addOption_htmlHeadLines($appGlobals->gb_cssFile_htmlCode);
}

function set_title($bannerTitle1, $bannerTitle2='',$pageTitle='') {
    $this->krmEmit_bannerTitle1 = $bannerTitle1;
    $this->krmEmit_bannerTitle2 = $bannerTitle2;
   $this->krmEmit_pageTitle = empty($pageTitle) ? $bannerTitle1 : $pageTitle ;  // for bookmarks, tabs, etc
}

function set_theme( $themeClassName ) {
}

function set_menu_standard($appChain, $appGlobals) {
    $argList = array_slice(func_get_args(),2);
    $appGlobals->gb_appMenu_init($appChain, $this, $argList);
}

function set_menu_customize( $appChain, $appGlobals, $itemKey=NULL ) {   // sort-of-required "abstract" function
    // declared abstract in draff_emitter
    $argList = array_slice(func_get_args(),2);
    $argCount =  count($argList);
    if ( $argCount>=1) {
        $this->emit_menu->menu_markCurrentItem($argList[0]);
        for ( $i=1; $i<$argCount; ++$i) {
           $this->emit_menu->menu_markTopLevelItem($argList[$i]);
        }
    }
    if ( !empty($itemKey) ) {  // ???? maybe move to parent
        $this->emit_menu->menu_markCurrentItem($itemKey);
    }
}

function krnEmit_banner_output($kernelGlobals, $title1, $title2='', $options='') {
    $this->krmEmit_options = $options;
    $this->krnEmit_banner_start($kernelGlobals);
    $this->krnEmit_banner_cell_logo('../kcm-kernel/images/'.$kernelGlobals->gb_banner_image_system);
    $this->krnEmit_banner_cell_proxyStatus($kernelGlobals);
    $this->krnEmit_banner_cell_title($kernelGlobals,$title1, $title2);
    $this->krnEmit_banner_cell_logo('../kcm-kernel/images/'.$kernelGlobals->gb_banner_image_kidchess);
    $this->krnEmit_banner_cell_login($kernelGlobals);
    $this->krnEmit_banner_end();
}

private function krnEmit_banner_start($kernelGlobals) {
    $overlay = defined('RC_BACKGROUND_IMAGE') ? " style='background-image: url(" . '"../' . RC_BACKGROUND_IMAGE . '"' . "); background-repeat:repeat;'" : '';
    print PHP_EOL . PHP_EOL .'<div class="zone-ribbon theme-banner" '. $this->krnEmit_banner_getSystemBackgroundStyle() . '>';
    print PHP_EOL . '<table class="kcmKrn-banner-table">';
    print PHP_EOL . '<tr>';
}

private function krnEmit_banner_cell_logo($imageFileName) {
    print PHP_EOL;
    print PHP_EOL . '<td class="kcmKrn-banner-icon"><img src="'.$imageFileName.'"></td>';  // no overlay
}

private function krnEmit_banner_cell_title($kernelGlobals, $title1, $title2) {
    print PHP_EOL . '<td class="kcmKrn-banner-title"'.$this->krnEmit_banner_getSystemBackgroundStyle().'">';
    print PHP_EOL . '  ' . $title1 . '<br>' . $title2;
    print PHP_EOL . '  </td>';
}

private function krnEmit_banner_cell_proxyStatus($kernelGlobals) {
    if ( $kernelGlobals === NULL) {
        return;
    }
    $actualUser = $kernelGlobals->gb_owner;
    $proxyUser = $kernelGlobals->gb_user;
    if ( ($actualUser==NULL) or ($proxyUser === NULL) ) {
        return;
    }
    $different = FALSE;
    $this->krnEmit_banner_proxy_compare($different,$actualUser->krnUser_staffId,$proxyUser->krnUser_staffId);
    $this->krnEmit_banner_proxy_compare($different,$actualUser->krnUser_nowDate,$proxyUser->krnUser_nowDate);
    $this->krnEmit_banner_proxy_compare($different,$actualUser->krnUser_nowDateTime,$proxyUser->krnUser_nowDateTime);
    if ( !$different) {
        return;
    }
    print PHP_EOL . '<td class="kcmKrn-banner-proxy"'.$this->krnEmit_banner_getSystemBackgroundStyle().'>';
    print PHP_EOL . '<div class=kcmKrn-banner-proxy-title>Proxy</div>';
    $pre = '';
    if ( $proxyUser->krnUser_nowDate !=$actualUser->krnUser_nowDate) {
        print PHP_EOL . $pre . draff_dateAsString($proxyUser->krnUser_nowDate,'F j, Y') . ' ('.draff_dateAsString($proxyUser->krnUser_nowDate,'l') . ')';
        $pre = '<br>';
    }
    if ( $proxyUser->krnUser_nowTime !=$actualUser->krnUser_nowTime) {
        print PHP_EOL . $pre . draff_timeAsString($proxyUser->krnUser_nowTime);
        $pre = '<br>';
    }
    if ( $proxyUser->krnUser_loginId!=$actualUser->krnUser_loginId) {
        print $pre.$proxyUser->krnUser_staffLongName;
    }
    print PHP_EOL . '</td>';
}

private function krnEmit_banner_cell_login($kernelGlobals) {
    print PHP_EOL;
    print PHP_EOL . '<td class="kcmKrn-banner-login"'.$this->krnEmit_banner_getSystemBackgroundStyle().'>';
    if ( $kernelGlobals!=NULL) {
        if ( $kernelGlobals->gb_isLoggedIn) {
            print PHP_EOL."Logged in: <span class='font-weight-bold'>{$_SESSION['Admin']['LoginName']}</span>";
            print PHP_EOL.'<br>';
             if ($this->krmEmit_options !='profile') {
                $homeScript = $url=strtok(rc_reconstructURL(),'?');    ;
                print PHP_EOL . kernel_regMakeLinkButton( "Log out",'banner-logout' );
                print PHP_EOL . kernel_regMakeLinkButton( "Edit Profile",'banner-profile' );
            }
       }
        else {
            print PHP_EOL."Need to log in</span>";
        }
    }
    print PHP_EOL . '</td>';
}

private function krnEmit_banner_end() {
    print PHP_EOL . '</tr>';
    print PHP_EOL . '</table>';
    print PHP_EOL . '</div>';
    print PHP_EOL ;
}

private function krnEmit_banner_proxy_compare(&$dif, $value1, $value2) {
    if ( $value1 != $value2)
        $dif = TRUE;
}

private function krnEmit_banner_getSystemBackgroundStyle() {
    return defined('RC_BACKGROUND_IMAGE') ? " style='background-image: url(" . '"../../' . RC_BACKGROUND_IMAGE . '"' . "); background-repeat:repeat;'" : '';
}



function krnEmit_output_htmlHead  ($appData, $appGlobals, $appChain, $appEmitter ) {
    if ( ! $appGlobals->gb_isExport) {
        $this->zone_htmlHead($this->krmEmit_bannerTitle1,$this->krmEmit_bannerTitle2);
    }
}

function krnEmit_output_bodyStart ( $appData, $appGlobals, $appChain, $appForm ) {
    if ( ! $appGlobals->gb_isExport) {
       $this->zone_body_start($appChain, $appForm);
    }
}

function krnEmit_output_ribbons  ( $appData, $appGlobals, $appChain, $appForm ){
    if ( ! $appGlobals->gb_isExport) {
        print PHP_EOL.'<div class="zone-ribbon-group">';
        $this->krnEmit_banner_output($appGlobals, $this->krmEmit_bannerTitle1, $this->krmEmit_bannerTitle2);
        $this->zone_messages($appChain, $appForm);
        $this->zone_menu();
        print PHP_EOL.'</div>';
    }
}


function krnEmit_output_bodyEnd  ( $appData, $appGlobals, $appChain, $appForm ){
    if ( ! $appGlobals->gb_isExport) {
        $this->zone_body_end();
    }
}


// function krnEmit_webPageOutput( $appData,  $appGlobals, $chain, $form ) {
//     // declared abstract in draff_emitter
// 	// This function emits the entire web page for all the kcm-systems
// 	//    so all kcm-systems should have a consistent look
//     $form->drForm_initFields( $appData, $appGlobals, $chain);
//     $form->drForm_initHtml( $appData, $appGlobals, $chain, $this );
//     if ($appGlobals->gb_isExport) {
//         $form->drForm_outputHeader  ( $appData, $appGlobals, $chain, $this );
//         $form->drForm_outputContent ( $appData, $appGlobals, $chain, $this );
//         $form->drForm_outputFooter ( $appData, $appGlobals, $chain, $this );
//         return;
//     }
//     $this->zone_htmlHead($this->krmEmit_bannerTitle1,$this->krmEmit_bannerTitle2);
//     $this->zone_body_start($chain, $form);
//     $this->krnEmit_banner_output($appGlobals, $this->krmEmit_bannerTitle1, $this->krmEmit_bannerTitle2);
//     $this->zone_messages($chain, $form);
//     $this->zone_menu();
// 	$form->drForm_outputHeader  ( $appData, $appGlobals, $chain, $this );
// 	$form->drForm_outputContent ( $appData, $appGlobals, $chain, $this );
// 	$form->drForm_outputFooter ( $appData, $appGlobals, $chain, $this );
//     $this->zone_body_end();
// }

function krnEmit_reportTitleRow($title, $colSpan)  {
    $s1 = '<div class="draff-report-top-left"></div>';
    $s2 = '<div class="draff-report-top-middle">'.$title.'</div>';
    $s3 = '<div class="draff-report-top-right">'.draff_dateTimeAsString(rc_getNow(),'M j, Y' ).'</div>';
    $this->row_start();
    $this->cell_block($s1 . $s2 . $s3 ,'draff-report-top', 'colspan="'.$colSpan.'"');
    $this->row_end();
}

function krnEmit_recordEditTitleRow($title, $colSpan)  {
   // $s1 = '<div class="draff-report-top-left"></div>';
    $s2 = '<div class="draff-report-top-middle">'.$title.'</div>';
 //   $s3 = '<div class="draff-report-top-right">'.draff_dateTimeAsString(rc_getNow(),'M j, Y' ).'</div>';
    $this->row_start();
    $this->cell_block($title ,'draff-edit-top', 'colspan="'.$colSpan.'"');
    $this->row_end();
}

function krnEmit_submitsAtBottom($controlArray)  {
    print '<br><br><br>';
    print '<div style="position:fixed; bottom: 10px; left:10px; padding: 6px 12px; border: 1px; background-color: #8c8;">';
    print $this->content_field($controlArray);
    print '</div>';
    $this->row_end();
}

function krnEmit_button_editSubmit($caption, $value) {
    // submit for editing - so all are consistent - (design choices:Edit button - caption - caption button that looks like link)
    //?????????? if report should not be coded as button ??????????????????????
    //$cellClass = empty($cellClass) ? '' : ' class="'.$class.'"';
    return '<button type="submit" class="kcmKrn-button-editLink" name="submit" value="'.$value.'">'  . $caption . '</button>';
}

}  // end class

?>