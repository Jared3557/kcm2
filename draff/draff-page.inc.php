<?php

//--- draff-page.inc.php ---

class Draff_Page {
    
    function drPage_process($appData, $appGlobals, $appChain, $appEmitter, $form) {
 
    // maybe create emitter here - of proper export type
       
       $this->drPage_init ($appData, $appGlobals, $appChain, $appEmitter, $form);
 
       if (!$appGlobals->gb_isExport) {
           $appEmitter->zone_htmlHead();
           $appEmitter->zone_body_start($appChain, $form);
           $this->drPage_ribbons ($appData, $appGlobals, $appChain, $appEmitter, $form);
       }
       
        $this->drPage_content ($appData, $appGlobals, $appChain, $appEmitter, $form);

        if (!$appGlobals->gb_isExport) {
            $appEmitter->zone_body_end();
        }
        
    }
    
    function drPage_init ($appData, $appGlobals, $appChain, $appEmitter, $form) {
        // relevant for all exports
        $form->drForm_form_addErrors($appChain);  // move errors from session to current form ???? is here the best place
        $form->drForm_initData($appChain->chn_app_data, $appChain->chn_app_globals, $appChain);
        $form->drForm_initFields($appChain->chn_app_data, $appChain->chn_app_globals, $appChain);
        $form->drForm_initHtml($appChain->chn_app_data, $appChain->chn_app_globals, $appChain, $appChain->chn_app_emitter);
    }
    
    function drPage_ribbons ($appData, $appGlobals, $appChain, $appEmitter, $form) {
        // only relevant for export to html - not export to pdf or excel
        if (!$appGlobals->gb_isExport) {
            print PHP_EOL . '<div class="zone-ribbon-group">';  // in div so css can hide when printing
            $appEmitter->zone_htmlHead();
            $appEmitter->zone_body_start($appChain, $form);
            print PHP_EOL . '<div class="zone-ribbon-group">';  // in div so css can hide when printing
            $appGlobals->gb_banner->krnEmit_banner_output($appGlobals, $appEmitter);
            $appEmitter->zone_messages($appChain, $form);
            $appGlobals->gb_menu->drMenu_emit_menu($appEmitter);
            print PHP_EOL . '</div>';
        }
    }
    
    function drPage_content ($appData, $appGlobals, $appChain, $appEmitter, $form) {
        // relevant for all exports
        $form->drForm_outputHeader($appData, $appGlobals, $appChain, $appEmitter);
        $form->drForm_outputContent($appData, $appGlobals, $appChain, $appEmitter);
        $form->drForm_outputFooter($appData, $appGlobals, $appChain, $appEmitter);
    }
    
}

?>
