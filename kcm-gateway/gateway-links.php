<?php

// gateway-links.php

ob_start();  // output buffering (needed for redirects, content changes)

include_once( '../../rc_defines.inc.php' );
include_once( '../../rc_admin.inc.php' );
include_once( '../../rc_database.inc.php' );
include_once( '../../rc_messages.inc.php' );

include_once( '../draff/draff-chain.inc.php' );
include_once( '../draff/draff-database.inc.php');
include_once( '../draff/draff-emitter.inc.php' );
include_once( '../draff/draff-form.inc.php' );
include_once( '../draff/draff-functions.inc.php' );
include_once( '../draff/draff-menu.inc.php' );
include_once( '../draff/draff-page.inc.php' );

include_once( '../kcm-kernel/kernel-functions.inc.php');
include_once( '../kcm-kernel/kernel-objects.inc.php');
include_once( '../kcm-kernel/kernel-globals.inc.php');

include_once( 'gateway-system-globals.inc.php');

const WID_EVENTS   = 1;
const WID_SCHEDULE = 2;
const WID_ADMIN    = 3;
const WID_CONTACTS = 4;
const WID_SCHOOLS  = 5;

Class appForm_usefulLinks_view extends kcmKernel_Draff_Form {

function drForm_process_submit ( $appData, $appGlobals, $appChain ) {
    kernel_processBannerSubmits( $appGlobals, $appChain, $submit );
}

function drForm_initData( $appData, $appGlobals, $appChain ) {
}

function drForm_initHtml( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->emit_options->set_theme( 'theme-panel' );
    $appEmitter->emit_options->set_title('Useful Links');
    $appGlobals->gb_ribbonMenu_Initialize($appChain, $appGlobals);
    $appGlobals->gb_menu->drMenu_customize();
}

function drForm_initFields( $appData, $appGlobals, $appChain ) {
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('draff-zone-header-default');
    $appEmitter->emit_nrLine('Useful Links');
    $appEmitter->zone_end();
}

function drForm_outputContent ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appEmitter->zone_start('zone-content-scrollable theme-boxes');

    $this->outGroupStart($appEmitter, 'Traffic');
    $this->outLink( $appEmitter, 'http://www.localconditions.com/weather-marietta-georgia/30006/traffic.php','Marietta');
    $this->outLink( $appEmitter, 'http://www.11alive.com/traffic/?fr=y','11 Alive');
    $this->outLink( $appEmitter, 'http://www.wsbtv.com/traffic','WSB');
    $this->outLink( $appEmitter, 'http://www.511ga.org/#u_con_ctl&msg_ctl&l_inc_ctl&vsp_ctl&cam_ctl&g_inf_ctl&spec_ctl&wacc_ctl&wtrf_ctl&whaz_ctl&wshl_ctl&zoom=5&lat=4028879.10862&lon=-9409880.87898','Georgia 511');
    $this->outLink( $appEmitter, 'http://www.ajc.com/traffic/','AJC');
    $this->outLink( $appEmitter, 'http://www.fox5atlanta.com/traffic','Fox 5');
    $this->outLink( $appEmitter, 'https://www.sigalert.com/Map.asp?lat=33.70161&lon=-84.35387&z=2','Sig Alert');
    $this->outLink( $appEmitter, 'http://www.localconditions.com/weather-atlanta-georgia/30301/traffic.php','Local Conditions');
    $this->outLink( $appEmitter, 'http://www.cbs46.com/category/209305/atlanta-traffic-info','46 News');
    $this->outLink( $appEmitter, 'https://www.google.com/maps/place/Atlanta,+GA/@33.7676338,-84.5606888,11z/data=!3m1!4b1!4m5!3m4!1s0x88f5045d6993098d:0x66fede2f990b630b!8m2!3d33.7489954!4d-84.3879824!5m1!1e1','Google');
    $this->outGroupEnd($appEmitter);

    $this->outGroupStart($appEmitter, 'Weather');
    $this->outLink( $appEmitter, 'http://www.localconditions.com/weather-marietta-georgia/30006/forecast.php','Local Conditions');
    $this->outLink( $appEmitter, 'https://www.wunderground.com/forecast/us/ga/marietta?cm_ven=localwx_10day','Weather Underground');
    $this->outLink( $appEmitter, 'http://www.ajc.com/weather/','AJC');
    $this->outLink( $appEmitter, 'https://weather.com/weather/today/l/USGA0631:1:US','weather.com');
    $this->outLink( $appEmitter, 'https://weather.com/weather/tenday/l/USGA0631:1:US','The Weather Channel');
    $this->outLink( $appEmitter, 'https://www.msn.com/en-us/weather','MSN');
    $this->outGroupEnd($appEmitter);

    $this->outGroupStart($appEmitter, 'Weather Radar');
    $this->outLink( $appEmitter, 'http://www.localconditions.com/weather-marietta-georgia/30006/radar/index.php','Local Conditions');
    $this->outLink( $appEmitter, 'http://www.localconditions.com/weather-marietta-georgia/30006/radar/index.php','Weather Underground');
    $this->outLink( $appEmitter, 'https://weather.com/weather/radar/interactive/l/USGA0028:1:US','The Weather Channel');
    $this->outGroupEnd($appEmitter);

    $this->outGroupStart($appEmitter, 'KidChess');
    $this->outLink( $appEmitter, 'http://kidchess.com/','kidchess.com');
    $this->outLink( $appEmitter, 'http://kidchess.com/our-programs/schedule/','Program Schedule and Maps');
    $this->outGroupEnd($appEmitter);

    $this->outGroupStart($appEmitter, 'Chess (Mostly News)');
    $this->outLink( $appEmitter, 'https://en.chessbase.com/','Chess Base');
    $this->outLink( $appEmitter, 'https://www.themaven.net/chessdailynews','Chess Daily News');
    $this->outLink( $appEmitter, 'http://www.chessdom.com/category/chess/','DOM Chess');
    $this->outLink( $appEmitter, 'http://theweekinchess.com/','The Week in Chess');
    $this->outLink( $appEmitter, 'https://www.chess.com/home','Chess.com');
    $this->outGroupEnd($appEmitter);

    $this->outGroupStart($appEmitter, 'Games and Puzzles');
    $this->outLink( $appEmitter, 'http://www.chessgames.com/perl/chesscollection?cid=1025415','22 most instructive games ever played');
    $this->outLink( $appEmitter, 'http://www.chessgames.com/perl/chesscollection?cid=1000119','The most instructive games of chess ever played');
    $this->outLink( $appEmitter, 'http://www.chessgames.com/perl/chesscollection?cid=1029327','Instructive Chess Games');
    $this->outLink( $appEmitter, 'https://gameknot.com/best-annotated-games.pl','Best Annotated Chess Games');
    $this->outLink( $appEmitter, 'http://www.chess-steps.com/puzzles.php','Chess-Steps puzzle corner');
     $this->outGroupEnd($appEmitter);

    $this->outGroupStart($appEmitter, 'Local News');
    $this->outLink( $appEmitter, 'http://www.ajc.com/news/','AJC');
    $this->outLink( $appEmitter, 'http://www.fox5atlanta.com/news','Fox 5');
    $this->outLink( $appEmitter, 'http://www.wsbtv.com/live-stream','WSB 2');
    $this->outLink( $appEmitter, 'http://www.11alive.com/','11 Alive');
    $this->outGroupEnd($appEmitter);

    $appEmitter->zone_end();
}

function drForm_outputFooter ( $appData, $appGlobals, $appChain, $appEmitter ) {
}

function outGroupStart($appEmitter, $title) {
    $appEmitter->emit_nrLine('<div>');
    $appEmitter->emit_nrLine('<span class="theme-boxes-title">');
    $appEmitter->emit_nrLine($title);
    $appEmitter->emit_nrLine('<br>');
    $appEmitter->emit_nrLine('</span>');
}

function outLink($appEmitter, $link,$desc) {
    $appEmitter->content_link($link,$desc,'loc-link');
    print '<br>';
}

function outGroupEnd($appEmitter) {
     $appEmitter->emit_nrLine('</div>');
}

} // end class

class application_data extends draff_appData {

function __construct() {
}

}

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@     Main Program                   @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
rc_session_initialize();

//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//@         Process Page               @
//@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

$appChain = new Draff_Chain(  'kcmKernel_emitter' );
$appChain->chn_register_appGlobals( $appGlobals = new kcmGateway_globals());
$appChain->chn_register_appData( new application_data());
$appGlobals->gb_forceLogin ();

$appChain->chn_form_register(1,'appForm_usefulLinks_view');
$appChain->chn_form_launch(); // proceed to current step

exit;


?>