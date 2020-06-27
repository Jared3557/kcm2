<?php

//--- draff-menu.inc.php ---

class Draff_Menu_item {
public $menuItem_key;
public $menuItem_caption;
public $menuItem_url;
public $menuItem_type;
public $menuItem_option;  //1=favorite 2=always (do not show on "show items", already visible)
public $menuItem_toggled;   // 1=visible menu 2=toggled menu

function __constuct() {
}

} // end class

class Draff_Menu_Engine {
public $drMenu_list = array();
public $drMenu_currentPageKey = NULL;
public $drMenu_containsVisibleItems = FALSE;
public $drMenu_containsToggledItems = FALSE;
private $drMenu_currentToggled = FALSE;
private $drMenu_curSript;

function __construct() {
$this->drMenu_curSript = '$' . basename($_SERVER["SCRIPT_FILENAME"]);
}


function drMenu_addLevel_top_start() {
$this->drMenu_currentToggled = FALSE;
}

function drMenu_addLevel_top_end() {
$this->drMenu_currentToggled = NULL;
}

function drMenu_addLevel_toggled_start() {
$this->drMenu_currentToggled = TRUE;
}

function drMenu_addLevel_toggled_end() {
$this->drMenu_currentToggled = NULL;
}

function drMenu_setItemState($newItem) {
$newItem->menuItem_toggled = $this->drMenu_currentToggled;
if ($this->drMenu_currentToggled) {
$this->drMenu_containsToggledItems = TRUE;
}
else {
$this->drMenu_containsVisibleItems = TRUE;
}
}

function drMenu_addGroup_start($triggerId,$caption) {
$triggerId .= '$start';
$newItem = new Draff_Menu_item;
$this->drMenu_list[$triggerId] = $newItem;
$newItem->menuItem_caption = $caption;
$newItem->menuItem_key = $triggerId;
$newItem->menuItem_url = '';
$newItem->menuItem_type = 1;
$newItem->menuItem_option = 0;
$this->drMenu_setItemState($newItem);
}

function drMenu_addGroup_end($triggerId) {
$triggerId .= '$end';
$newItem = new Draff_Menu_item;
$this->drMenu_list[$triggerId] = $newItem;
$newItem->menuItem_caption = '';
$newItem->menuItem_url = '';
$newItem->menuItem_type = 3;
$newItem->menuItem_key = $triggerId;
$newItem->menuItem_option = 0;
$this->drMenu_setItemState($newItem);
}

function drMenu_addTitleExtension ($triggerId, $caption) {
$newItem = new Draff_Menu_item;
$this->drMenu_list[$triggerId] = $newItem;
$newItem->menuItem_key = $triggerId;
$newItem->menuItem_caption = $caption;
$newItem->menuItem_url = '';
$newItem->menuItem_type = 5;
$newItem->menuItem_option = 2;
$this->drMenu_setItemState($newItem);
}

function drMenu_addItem($chain, $triggerId, $caption, $url, $optionalArguments=NULL) {
// if ( ($inheritedParams===TRUE) ) { //  or ($params==='??')
//     $url = $chain->chn_url_getString($url, TRUE, $params);
// }
// else if ( is_array($params) ) {
//      $url = $chain->chn_url_getString($url, FALSE, $params);
// }
$url = $chain->chn_url_getString($url, FALSE, $optionalArguments);
$newItem = new Draff_Menu_item;
$this->drMenu_list[$triggerId] = $newItem;
$newItem->menuItem_key = $triggerId;
$newItem->menuItem_caption = $caption;
$newItem->menuItem_url = $url;
$newItem->menuItem_type = 2;
$newItem->menuItem_option = 1;
$this->drMenu_setItemState($newItem);
$urlBase = '$' . basename($url);
if ( strpos( $urlBase, $this->drMenu_curSript) !== FALSE) {   // theoretically could be better done
$current = TRUE;
if ( is_array($optionalArguments) ) {
$drfMode = $optionalArguments['drfMode'] ?? NULL;
$drfForm = $optionalArguments['drfForm'] ?? NULL;
$getMode = $_GET['drfMode'] ?? NULL;
$getForm = $_GET['drfForm'] ?? NULL;
if ( ($drfMode !== $getMode) or ($drfForm !== $getForm) ) {
$current = FALSE;
}
}
if ($current) {
$this->drMenu_currentPageKey = ($this->drMenu_currentPageKey == NULL) ? $triggerId : '';
}
// if same script has multiple menu options script must specify current page
}
}

function drMenu_getItem($menuItemKey) {
$menuItem = isset ($this->drMenu_list[$menuItemKey]) ? $this->drMenu_list[$menuItemKey] : NULL;
return $menuItem;
}

function drMenu_markTopLevelItem($keyMask, $status=1) {
if ( substr($keyMask,-1)=='*') {
$keyMask = substr($keyMask,1,-1);
$len = strlen($keyMask);
$count = count($this->menu_itemId);
foreach ($this->drMenu_list as $key->$item) {
if ( substr($item->menuItem_key,0,$len)==$keyMask) {
$item->menuItem_option = $status;
}
}
}
else {
$item = $this->drMenu_getItem($keyMask);
if ( !empty($item)) {
$item->menuItem_option = $status;
}
}
}

function drMenu_markCurrentItem($itemKey) {
$item = $this->drMenu_getItem($itemKey);
$this->drMenu_currentPageKey = empty($item) ? NULL : $itemKey;
}

function drMenu_emit_menu($emitter) {
if ($this->drMenu_containsToggledItems) {
$this->drMenu_emit_toggled($emitter);
}
$emitter->zone_start('zone-ribbon theme-menu');
foreach ( $this->drMenu_list as $menuKey => $menuItem ) {
if ( ( !$menuItem->menuItem_toggled) or ($menuKey == $this->drMenu_currentPageKey) ) {
if ($menuItem->menuItem_type==2) {
$caption = $menuItem->menuItem_caption;
$class = ($menuKey === $this->drMenu_currentPageKey) ? ' draff-menu-item-curent' : '';
$emitter->drOutputIfNotReport( '<div class="draff-menu-item'.$class.'"><a class="draff-menu-item'.$class.'" href="' . $menuItem->menuItem_url . '">'.$caption.'</a></div>');
}
else if ($menuItem->menuItem_type==5) {
$emitter->emit_export->expf_htmlLine_display( '<div class="draff-menu-banner-extension">'. $menuItem->menuItem_caption  . '</div>'  );
}
}
}
if ( $this->drMenu_containsToggledItems) {
$emitter->div_toggled_buttonEmit('draff-toggled-menu','draff-menu-item-more','Show<br>More','Show<br>Less');
}
$emitter->zone_end();
}

function drMenu_emit_toggled($emitter) {  // generate code for the menu that is usually hidden unless the 'more' menu item is clicked
$emitter->div_toggled_start('draff-toggled-menu');
foreach($this->drMenu_list as $menuKey=>$menuItem) {
if ( ! $menuItem->menuItem_toggled) {
continue;
}
if ( $menuItem->menuItem_option ==2) {
continue;
}
$caption = $menuItem->menuItem_caption;
switch ($menuItem->menuItem_type) {
case 1:
$emitter->emit_export->expf_htmlLine_display( '<div class="draff-menu-line-block">');
    $emitter->emit_export->expf_htmlLine_display( '<div class="draff-menu-line-title">'.$caption.'</div>');
    //   print PHP_EOL . '<legend class="draff-menu-legend">'.$this->emit_menu->menu_itemCaption[$i].'</legend>';
    break;
    case 2:
    $class = ($menuItem->menuItem_key === $this->emit_menu->drMenu_currentPageKey) ? ' draff-menu-item-curent' : '';
    $emitter->emit_export->expf_htmlLine_display( '<div class="draff-menu-item'.$class.'"><a class="draff-menu-item'.$class.'" href="' . $menuItem->menuItem_url . '">'.$caption.'</a></div>');
    break;
    case 3:
    $emitter->emit_export->expf_htmlLine_display( '</div>');
break;

case 4:
$class = ($i === $this->drMenu_currentPageKey) ? ' draff-menu-item-curent' : '';
$emitter->emit_export->expf_htmlLine_display( '<div class="draff-menu-item'.$class.'"><a class="draff-menu-item'.$class.'" target="_blank" href="' . $this->menu_itemUrl[$i] . '">'.$caption.'</a></div>');
break;
}
}
//print PHP_EOL.'</div>';
$emitter->div_toggled_end();
}

function drMenu_customize( $itemKey=NULL ) {   // sort-of-required "abstract" function
// declared abstract in draff_emitter
$argList = array_slice(func_get_args(),2);
$argCount =  count($argList);
if ( $argCount>=1) {
$this->emit_menu->drMenu_markCurrentItem($argList[0]);
for ( $i=1; $i<$argCount; ++$i) {
$this->emit_menu->drMenu_markTopLevelItem($argList[$i]);
}
}
if ( !empty($itemKey) ) {  // ???? maybe move to parent
$this->emit_menu->drMenu_markCurrentItem($itemKey);
}
}


} // end class

?>