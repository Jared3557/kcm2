<?php

// gateway-system-globals.inc.php

class kcmAdmin_globals extends kcmKernel_globals {
    public $gb_menu;
    
    function __construct() {
        parent::__construct('KCM Administration', '../kcm-kernel/images/banner-icon-kcm.gif', 'kcmAdmin_emitter');
        $this->gb_owner = new kcmKernel_security_user($this, NULL);
        $this->gb_user = new kcmKernel_security_user($this, $this->gb_owner);
        $this->gb_isLoggedIn = ($this->gb_user->krnUser_staffLongName != '');
        $this->gb_banner_image_system = 'kcm_banner_administrate.gif';
        $this->gb_menu = new Draff_Menu_Engine;
    }
    
    function gb_kernelOverride_getStandardUrlArgList() {
        $args = array();
        return $args;
    }
    
    function gb_ribbonMenu_Initialize($chain, $emitter, $overrides = NULL) {   /* required function - called by kernel emitter*/
        $menu = $emitter->emit_menu;
    }
    
} // end class

?>