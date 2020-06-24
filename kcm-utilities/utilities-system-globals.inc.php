<?php

// utilities-system-globals.inc.php

class kcmUtilities_globals extends kcmKernel_globals  {  
    
function __construct( $pDb, $pChain ) {
    parent::__construct($pDb) ;
    $this->gb_owner = new kcmKernel_security_user($this, NULL);  
    $this->gb_user  = new kcmKernel_security_user($this, $this->gb_owner);
    $this->gb_isLoggedIn = ($this->gb_user->krnUser_staffLongName != '');
}
    
function gb_kernelOverride_getStandardUrlArgList() {
    $args = array();
    return $args;
}

function gwy_getKcmVersionSymbol($kcmVersion) {
    if ( $kcmVersion==1) {
        return '&hookrightarrow;';
    }    
	return ($kcmVersion == 2) ? '&xrArr; ' : '&rarr; ';
}
    
function gwy_redirectToKcm1ProgramStart( $appGlobals, $chain, $programId ) {
    $url = self::gwy_getKcm1ProgramUrl( $appGlobals, $chain, $programId );
    rc_redirectToURL( $url );
}

function gwy_getKcm1ProgramUrl( $appGlobals, $chain, $programId ) {
    $sql = array();  
    $sql[] = "Select `pPe:PeriodId`,`pPr:KcmVersion`";
    $sql[] = "FROM `pr:period`";
    $sql[] = "JOIN `pr:program` ON `pPr:ProgramId` = `pPe:@ProgramId`";
    $sql[] = "WHERE `pPe:@ProgramId` = '{$programId}'";  
    $sql[] = "   AND `pPe:HiddenStatus` = '0'";  
    $sql[] = "ORDER BY `pPe:PeriodSequenceBits`";  
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_db->rc_query( $query );
    if ( ($result === FALSE) || ($result->num_rows ==0)) {
        draff_errorTerminate( $query);
    }
    if ( $row=$result->fetch_array()) {
        //???? check authorization for this program ??
        $periodId = $row['pPe:PeriodId'];
        $kcmVersion = $row['pPr:KcmVersion'];
    }
    else {
        $message = "Error - This program has no periods.";  //??????????????????????????????
        $argSubmit = 'program';
    }   
    //if ( $override==1) {
    //    $kcmVersion = 1;
    //}
    //else if ( $override==2) {
    //    $kcmVersion = 2;
    //}
    //else if ( $override) {
    //    $kcmVersion = ($kcmVersion==2) ? 1 : 2;
    //}
    if ( $kcmVersion == 2) {
       $url = $chain->chn_url_getString('../kcm-roster/roster-home.php' , FALSE, array( 'PrId'=>$programId) );
    }
    else {
         $url = "../../kcm-periodHome.php?kcmp=_PrId-{$programId}_PeId-{$periodId}";
    }    
    return $url;
}

}

?>