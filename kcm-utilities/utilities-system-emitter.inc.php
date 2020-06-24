<?php

// utilities-system-emitter.inc.php

class kcmUtilities_emitter extends Draff_Emitter_Engine {

function __construct($utlGlobals, $form, $bodyStyle='') {
    parent::__construct($utlGlobals, $form, $bodyStyle);
}

function emit_kernelOverride_addCssFiles() {  // sort-of-required "abstract" function
}

// function emit_kernelOverride_webPage_outputStart($emitter, $appGlobals, $subtitle='', $isKcm1=FALSE) {  // sort-of-required "abstract" function
//     $bodyStyle = ''; // $isKcm1 ? 'draff-zone-body-legacy' : 'draff-zone-body-standard';
//     $emitter->zone_body#_start($bodyStyle);
//     if ( $subtitle!='') {
//         $subtitle = '<br>'.$subtitle;
//     }
//     $emitter->krnEmit_banner_output($appGlobals, $subtitle);
//     $emitter->zone_menu();
// }

function emit_kernelOverride_webPage_outputEnd($appGlobals,$emitter,$form) {  // sort-of-required "abstract" function
    $emitter->zone_body_end();
}

} // end class


?>