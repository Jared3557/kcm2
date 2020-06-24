<?php

// admin-system-emitter.inc.php

// kcmGateway_emitter can only be created only after com_htmlOut_startOfPage is called (due to getting of rsm $emitter)

class kcmAdmin_emitter extends kcmKernel_emitter {

function __construct($appGlobals, $form, $bodyStyle='') {
    parent::__construct( $appGlobals, $form, $bodyStyle);
}

function emit_kernelOverride_addCssFiles() {  // sort-of-required "abstract" function
}
   

} // end class


?>