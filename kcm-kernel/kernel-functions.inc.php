<?php

// kernel-functions.inc.php

// functions and classes scriptData to kcm, payroll, gateway, etc

function kernel_array_concat($array, $prefix, $suffix) {
    $newArray = array();
    foreach( $array as $value ) {
        $newArray[] = $prefix . $value . $suffix;
    }  
    return $newArray;    
}

function krnLib_assert($isTrue, $error, $line=NULL) {
    if ( !$isTrue) {
        if ( $line!=NULL) {
            $error = ' Notify Jared - Assert Error - ' . $error . ' - ' . $line;
        }
        print '<br><hr>Programming (assert) Error:<br><br>'.$error.'<br><hr><br>';
        exit;
    }    
}

function krnLib_getSchoolName( $schoolName, $uniquifier=NULL ) {
    if ( is_array($schoolName) ) {
        $uniquifier = $schoolName['pPr:SchoolNameUniquifier'];
        $schoolName = $schoolName['pSc:NameShort'];
    }
    if ( !empty($uniquifier) ) {
        $schoolName.= ' ' . $uniquifier;
    }
    return $schoolName;
}

function kernel_regMakeLinkButton( $label, $value ) {
	// modified from rc_regMakeLinkButton
    $html = "<button name='submit' type='submit' value='{$value}'>{$label}</button>";
//--    	$target = '';
//--    	$suffix = '';
//--    	if ($inNewWindow) {
//--    		$target = " target='_blank'";
//--    		$suffix = "...";  //!!! really want an icon?
//--    	}
//--    	$classes = $buttonClasses ? " class='{$buttonClasses}'" : "";
//--    	$href = str_replace( '&amp;', '&', $href );
//--    	$urlParts = parse_url( $href );
//--    	$formClass = "linkButton";
//--    	$html = '';
//--    	//$html = "<form class='{$formClass}' action='{$urlParts['path']}' method='get'{$target}>";
//--    //	$html .= "<div>";
//--    	if (isset( $urlParts['query'] )) {
//--    		$params = array();
//--    		parse_str( $urlParts['query'], $params );
//--    		foreach( $params as $name => $val ) {
//--    			$html .= "<input type='hidden' name='{$name}' value='{$val}'>";
//--    		}
//--    	}
//--    	$html .= "<button type='submit'{$classes}>{$label}{$suffix}</button>";
//--    //	$html .= "</div>";
//--    	//$html .= "</form>";
    return $html;
}

function kernel_processBannerSubmits( $appGlobals, $chain ) {
	if ($chain->chn_submit[0]=='banner-logout') {
		// need to logout  -- is this the best way???
	    unset( $_SESSION['Admin'] );
	    unset( $_SESSION['Post']['Admin'] );
		$url = rc_reconstructURL();
        rc_redirectToURL($url); 
	}
	if ($chain->chn_submit[0]=='banner-profile') {
        $emitter = new kcmKernel_emitter($appGlobals, NULL);
        $emitter->zone_htmlHead($emitter->krmEmit_bannerTitle1);
        $emitter->zone_body_start($chain, NULL);
        $emitter->krnEmit_banner_output($appGlobals, 'Edit Profile', '', 'profile');
        $emitter->zone_start('zone-content-scrollable theme-panel');
        rc_showAdminProfilePage('../../');
        $emitter->zone_end();
        $emitter->zone_body_end();
		// need to logout  -- is this the best way???
		//$url = rc_reconstructURL();
        //rc_redirectToURL($url); 
	}
}

// function krnLib_getFieldList($fieldArray) {
//     $sql = '';
//     foreach ($fieldArray as &$field) {
//         if ( strpos($field,'`')===FALSE )
//             $field= '`'.$field.'`';
//     }
// }
    
function krnLib_getAuthorizationDateRange($dateOverride = NULL) {
    //????? future enhancement - in calling sql check date to only authourize first week or two of previous/next semester's event
    //?????      so multiple semesters for an event only happens on the first/last week of viewing that event 
    //?????      otherwise can access other semesters of event using roster access table    
    $dateToday = empty($dateOverride) ? date_create( "today" ) : date_create( $dateOverride );
    while (date_format( $dateToday, 'N' ) != 1) {  //1=Monday
        date_modify( $dateToday, '-1 day' );
    }    
    $todaySql = date_format($dateToday,'Y-m-d');
    $monthDay  = substr($todaySql,5,5);;
    $year = substr($todaySql,0,4);
    if ($monthDay < '02-05') {
       $dateStart = date_create( ($year-1) . '-11-15' );
    }
    else {
        $beforeDays = '-14';
 	    $dateStart = clone $dateToday;
        date_modify( $dateStart, $beforeDays . ' day' );
    }
    if ($monthDay > '11-15') {
       $dateEnd = date_create(  ($year+1) . '-02-05' );
    }
    else {
        $afterDays  ='+21'; ;  
    	$dateEnd   = clone $dateToday;
        date_modify( $dateEnd, $afterDays . ' day' );
    }
  	$pStartDate = date_format( $dateStart, 'Y-m-d' );
  	$pEndDate = date_format( $dateEnd, 'Y-m-d' );
    return array ('start'=>$pStartDate,'end'=>$pEndDate);
}

function kcm_fetch_selectMap_staff($appGlobals, $isCombo = FALSE ) {
    $staffSelect = $isCombo ? array(0=>'None (Select a Staff member)') : array();
    $query = new draff_database_query;
    $query->rsmDbq_selectStart();
    $query->rsmDbq_selectAddColumns('dbRecord_staff');
    $query->rsmDbq_add( "FROM `st:staff`");
    $query->rsmDbq_add( "WHERE `sSt:HiddenStatus`='0'");
    $query->rsmDbq_add( "ORDER BY `sSt:FirstName`, `sSt:LastName`");
    $result = $appGlobals->gb_pdo->rsmDbe_executeQuery($query);
    $dj = new draff_database_joiner;
    foreach ($result as $row) {
        $staffId = $row['sSt:StaffId'];
        $staffSelect[$staffId] = $row['sSt:FirstName'] . ' ' . $row['sSt:LastName'];
    }
    return $staffSelect;
 }

?>