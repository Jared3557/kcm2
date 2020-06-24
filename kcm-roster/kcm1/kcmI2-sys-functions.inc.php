<?php

//--- kcmI2-sys-functions.inc.php ---

// This sys unit is an independent stand-alone unit not requiring any other include code
// ... and not referencing anything that is KCM or Raccoon specific


class sys_db {
    
static function sydGenSqlInsert($db,$table, $fields) {
    $s1 = '';
    $s2 = '';
    $punc = '';
    foreach( $fields as $field => $value) {
        $s1 .= $punc . "`" . $field . "`";
        $s2 .= $punc . sys_db::sydGenSqlGetValueString($db,$value);
        $punc = ',';
    }
    return "INSERT INTO `{$table}` ({$s1}) VALUES ({$s2})";
}

static function sydGenSqlUpdate($db, $table, $fields, $where) {
    $s1 = '';
    $punc = '';
    foreach( $fields as $field => $value) {
        $s1 .= "{$punc}`{$field}`=". sys_db::sydGenSqlGetValueString($db, $value); 
        $punc = ',';
    }
    return "UPDATE `{$table}` SET {$s1} {$where}";
}

static function sydGenSqlGetValueString($db, $val) {
	if (gettype( $val ) == "boolean") {
		return (int)$val;
				// the boolean FALSE gets rendered as an empty string
				// starting with MySQL 5.7, this is an error instead of acting like 0
	}
	if (is_null( $val )) {
		return 'NULL';
	}
	else {
		return "'" . $db->real_escape_string( $val ) . "'";
	}
}

}    // end class

class sys_when {
    
static function sywTimeAsString($sqlTime, $format = 'g:ia' ) {
    $time = date_create_from_format( 'H:i:s', $sqlTime );
    if ($time === FALSE) {
        return $sqlTime;
    }    
    $formattedTime = date_format( $time, $format );
    if ($formattedTime === FALSE) {
        return $sqlTime;
    }    
    return $formattedTime;
} 

static function sywDateAsString( $sqlDate, $format='D, M j, Y' ) {
	if (rc_isZeroDate( $sqlDate )) {
		return "";
	}
	$date = date_create_from_format( 'Y-m-d', $sqlDate );
	if ($date === FALSE) {
		return ""; 
	}
	$text = date_format( $date, $format );
	if ($text === FALSE) {
		return ""; 
	}
	return $text;
}

static function sywDateTimeAsString( $sqlDateTime, $format='D, M j, Y, g:ia' ) {
	if (rc_isZeroDate( $sqlDateTime )) {
		return "";
	}
 	$dateTime = date_create_from_format( 'Y-m-d H:i:s', $sqlDateTime );
	if ($dateTime === FALSE) {
		return ""; 
	}
	$text = date_format( $dateTime, $format );
	if ($text === FALSE) {
		return ""; 
	}
	return $text;
}

static function sywDateDif($date1, $date2) {
$datetime1 = date_create($date1);
$datetime2 = date_create($date2);
$interval = ($date1 > $date2) ?  date_diff($datetime1, $datetime2)->format('-%a') : date_diff($datetime2, $datetime1)->format('%a');
return $interval;
}

static function sywDateIncrement($date, $modByDays) {
$modBy = ($modByDays>=0) ? ('+'.$modByDays) : ($modByDays);
$dat = new DateTime($date);
$dat->modify($modBy . ' day');
return $dat->format('Y-m-d');    
}

static function sywTimeIncrement($time, $modByMinutes) {
$modBy = ($modByMinutes>=0) ? ('+'.$modByMinutes) : ($modByMinutes);
$dat = new DateTime($time);
$dat->modify($modBy . ' minutes');
return $dat->format('H:i:s');    
}

} // end class


function debOut($s) {
    $ss = htmlspecialchars( $s, ENT_QUOTES ); 
    //print $ss;
    //print '<span style="display:inline;">'.$ss.'</span>';
    print '<span style="diaplay:inline-block; border:1px solid gray; padding:2pt;background-color:#ffffcc;">'.$ss.'</span>';
}

function debgvar($a) {
  // echo '<br><br>$$$$$'; var_dump($a); echo '<br><br>'; 
   // print '#'.$a.'<br>';
    if (is_null($a)) {
       print 'NULL';
       return;
    }
    if (is_bool($a)) {
        if ($a===TRUE) 
           debOut( 'TRUE');
        else if ($a===FALSE) 
           debOut( 'FALSE');
        return;   
        }   
    if (is_array($a)) {
         debout(var_export($a,true));
        return;   
    }
    if (is_object($a)) {
        print '{[Object]';
        debout(var_export($a,true));
       // var_dump($a);
        return;   
    }
    if (strpos($a,'`') >= 1) { //SQL query string
        print '<span style="overflow-wrap:break-word;border:1px solid gray; background-color:#ffffcc;">'.$a.'</span>';
        return;
    }
    print debOut($a);
}

function dbg() {
    print '<div>';
    $numargs = func_num_args();
    $arg_list = func_get_args();
     // echo '<br><br>'; var_dump($arg_list); echo '<br><br>'; 
    print '<br>@@_____';
    for ($i = 0; $i < $numargs; $i++) {
        debgvar($arg_list[$i]);
        if ($i<$numargs-1) {
            print '______';
        }    
    }
    print '_____@@<br>';
    print '</div>';
}

function dbglog() {
    //ob_clean ();
    $logKey = 'dbgLog';
    if ( ! isset($_SESSION[$logKey]) ) {
        $_SESSION[$logKey] = array();
    }
    $logArray = & $_SESSION[$logKey];
    $numargs = func_num_args();
    if ($numargs!=0) {
        $arg_list = func_get_args();
        $logArray[] = $arg_list;
        return;
    }    
    // no args - end of logging - time to print
    print '<hr>@@@@@@@@@@@@ Start of Debug Logging @@@@@@@@@@@@<hr>';
    $logCount = count($logArray);
    for ($i=0; $i<$logCount; ++$i) {
        $entryArray = $logArray[$i];
        $entryCount = count($entryArray);
        print '<br>@@_____';
        for ($j = 0; $j < $entryCount; $j++) {
            debgvar($entryArray[$j]);
            if ($j<$entryCount-1) {
                print '______';
            }    
        }    
    }
    print '_____@@<br>';
    print '<hr>@@@@@@@@@@@@ End of Debug Logging @@@@@@@@@@@@<hr>';
    unset($_SESSION[$logKey]);
    //exit('<br>exit - end of log');
}

function kcmSys_assert($shouldBeTrue,$errorMsg, $isCritical = FALSE) {
    if ( $shouldBeTrue === FALSE) {
        print '<hr>'.$errorMsg.'<hr>';
        if ($isCritical) {
            exit('Critical Error: '. $errorMsg);
        }
    }
}


?>