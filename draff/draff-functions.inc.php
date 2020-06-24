<?php

//--- draff-functions.inc.php ---


// possible enhancements:
//   add trace option to debug
//   fix debug for recursive references

// This sys unit is an independent stand-alone unit not requiring any other include code
// ... and not referencing anything that is KCM or Raccoon specific

CONST ex = '@exit';  // only temporary - used for debug for easite typing - can delete this line as should not use debug in live code

function dbg() {
    global $deb_inDebug, $deb_circular, $deb_circular2,$deb_level;
    $globals = new deb_globals;
    $deb_circular = array();
    $deb_circular2 = array();
    $deb_level = 0;
    $arg_list = func_get_args();
    foreach ($arg_list as $arg) {
        $globals->deg_list->del_addItem($arg);
    }
    $globals->deg_output();
    print PHP_EOL.'<br>';
    if ($globals->deg_exit!==FALSE) {
        exit;
    }
}

//==================================
// Date Time functions
//==================================
    
    
function draff_timeAsString($sqlTime , $format = '' ) {
    if ( $format == '' ) {
        $format = 'g:i a';
    }
    $time = date_create_from_format( 'H:i:s' , $sqlTime );
    if ( $time === FALSE ) {
        return $sqlTime;
    }    
    $formattedTime = date_format( $time , $format );
    if ( $formattedTime === FALSE ) {
        return $sqlTime;
    }    
    return $formattedTime;
} 

function draff_dateOfWeekCode( $sqlDate ) {
    return date("w", strtotime($sqlDate));
}

function draff_dateAsString( $sqlDate , $format='D M j, Y' ) {
	if ( (is_null( $sqlDate )) or ($sqlDate == "0000-00-00") or ($sqlDate == "") ) {
        // could add additional checks for valid date
		return $sqlDate;
	}
	$date = date_create_from_format( 'Y-m-d' , $sqlDate );
	if ( $date === FALSE) {
		return $sqlDate; 
	}
	$text = date_format( $date , $format );
	if ( $text === FALSE ) {
		return $sqlDate; 
	}
	return $text;
}

function draff_minutesAsString( $minutes ) {
    if ( empty($minutes) ) {
        return '';
    }    
    $min = $minutes % 60;
    $hour = floor($minutes / 60);
    if ( $min < 9 ) {
        $min = '0' . $min;
    }
    return $hour . ':' . $min;
}

function draff_dollarsAsString($dollars) {
    return '$' . number_format($dollars,2,'.','.'); 
}

function draff_dateTimeAsString( $sqlDateTime , $format='D, M j, Y, g:i a' ) {
    if ( $format == '' ) {
        $format = 'D, M j, Y, g:i a';
    }
	if ( rc_isZeroDate($sqlDateTime ) ) {
		return $sqlDateTime;
	}
 	$dateTime = date_create_from_format( 'Y-m-d H:i:s' , $sqlDateTime );
	if ( $dateTime === FALSE ) {
		return $sqlDateTime; 
	}
	$text = date_format( $dateTime, $format );
	if ( $text === FALSE ) {
		return $sqlDateTime; 
	}
	return $text;
}

function draff_dateDif( $date1 , $date2 ) {
    $datetime1 = date_create($date1);
    $datetime2 = date_create($date2);
    $interval = ($date1 > $date2) ?  date_diff($datetime1, $datetime2)->format('-%a') : date_diff($datetime2, $datetime1)->format('%a');
    return $interval;
}

function draff_timeMinutesDif( $time1 , $time2 ) {
    $minutes1 = ( substr($time1,0,2)*60 ) + substr($time1,3,2);  
    $minutes2 = ( substr($time2,0,2)*60 ) + substr($time2,3,2);  
    $dif = abs($minutes1-$minutes2);
    return $dif;
}

function draff_microTimeDif( $time1 , $time2 ) {
    $datetime1 = DateTime::createFromFormat('Y-m-d H:i:s:u', $time1);
    $datetime2 = DateTime::createFromFormat('Y-m-d H:i:s:u', $time2);
    $interval = $datetime1->diff($datetime2);
    $mic = $interval->format('%F');
    $sec = $interval->format('%s');
    $min = $interval->format('%i');
    $hour = $interval->format('%h');
    $rem = $interval->format('%y%m%d');
    if ($rem!='000') {
        return 99999;  // a very long time
    }
    else {
        return (float) $hour*3600 . $sec*60 . '.' . $mic;
    }
}

function draff_dateIncrement( $date , $modByDays) {
    $modBy = ($modByDays >= 0) ? ( '+' . $modByDays ) : $modByDays;
    $dat = new DateTime($date);
    $dat->modify( $modBy . ' day');
    return $dat->format( 'Y-m-d' );    
}

function draff_timeIncrement( $time , $modByMinutes ) {
    $modBy = ( $modByMinutes>=0 ) ? ( '+' . $modByMinutes ) : $modByMinutes;
    $dat = new DateTime($time);
    $dat->modify( $modBy . ' minutes' );
    return $dat->format( 'H:i:s' );    
}
function draff_getMicroTime() {
   $dateObj = DateTime::createFromFormat('0.u00 U', microtime());
   //$dateObj->setTimeZone(new DateTimeZone('America/Denver'));
   return $dateObj->format('Y-m-d H:i:s:u');
}
function draff_concatWithSpaces() {  // ($paramList) - only supported in php 5.6 so not used here
    $paramCount = func_num_args();
    $paramList  = func_get_args();
    $s = '';
    $sep = '';
    for ( $i = 0 ; $i < $paramCount ; ++$i ) {
        if ( ! empty($paramList[$i]) ) {
            $s .= $sep . $paramList[$i];
            $sep = ' ';
        }
    }
    return $s;
}

function draff_urlArg_getOptional($pArgKey, $pDefault=NULL) {
    if ( isset($_GET[$pArgKey])) {
        return $_GET[$pArgKey];
    }
    else {    
        return $pDefault;
    }    
}

function draff_urlArg_getRequired($pArgKey, $message = NULL) {
    if ( isset($_GET[$pArgKey])) {
       $arg = $_GET[$pArgKey];
       // $this->chn_regArg[$pArgKey] = $arg;   ?????????????????
       return $arg;
    }    
    if ( $message === NULL) {
         $message = 'Misformed URL - argument ' . $pArgKey . ' is missing';
    }
    exit($message);            
}

function draff_getElement($array, $index, $default) {  //@JPR-2019-08-10 08:22 
    return isset($array[$index]) ? $array[$index] : $default;
}

function draff_unNullify($val, $default) {  //@JPR-2019-08-10 08:22 
    return ($val===NULL) ? $default : $val;
}

function draff_isBitSet($bits, $bit) {
    return ($bits && $bit) == $bit;
}

//==================================
// Debug functions - deb functions are "private""
//==================================
    

$deb_inDebug = FALSE;
$deb_colors = array('red','#c88','#8c8','#88c','#c88','#8c8','#88c','#c88','#8c8','#88c','#c88','#8c8','#88c','#c88','#8c8','#88c','#c88','#8c8','#88c','#c88','#8c8','#88c','#c88','#8c8','#88c');
$deb_level = 0;
$deb_indent = '';
$deb_circular = array();  // prevent same var from being debugged to prevent circles
$deb_circular2 = array();  // prevent same var from being debugged to prevent circles

//$dbb_css_table   = 'width:100%;padding:0px 0px; margin: 0px 0px; vertical-align:top;border:0px solid black;background-color:'.$deb_colors[$deb_level-1].';border-collapse:collapse;border-spacing:0;empty-cells:show;';
//$dbb_css_tdCol1  = 'background-color:'.$deb_colors[$deb_level-1].';width:10px;font-size:16pt; border:0px solid black;vertical-align:top;padding:4px 2px;text-align:left;';
//$dbb_css_tdCol2  = 'background-color:'.$deb_colors[$deb_level].';font-size:12pt; border:1px solid black;vertical-align:top;';
//$dbb_css_spanVal = 'background-color:#ffffcc; display:inline-block;  border:1px solid black; margin: 2px 2px; padding: 1px 4px';
// need some options 
// (1) horizontal mode to list in as little vertical space as possible (example:for use in loops)
// (2) to use css in rsm style file (when header is printed)
// (3) maybe save to file - and/or session for display later in messages ???

class deb_globals {
public $deg_colors = array('#fbb','#cfc','#ccf','#fcc','#cfc','#ccf','#fcc','#8c8','#88c','#c88','#8c8','#88c','#c88','#8c8','#88c','#c88','#8c8','#88c','#c88','#8c8','#88c','#c88','#8c8','#88c');
//public $deg_level = 0;
public $deg_indent = '';
public $deg_list = '';
public $deg_exit = FALSE;
public $deg_trace = '';

function __construct () {
    global $deb_inDebug, $deb_level;
    $deb_level = 0;
    $this->deg_list = new deb_list(0);
    $deb_inDebug = TRUE;
    $trace = debug_backtrace();
    $caller = $trace[1]; 
    $script   = basename ($caller['file']);
    $line     =  $caller['line'];
    $funcName = isset($trace[2]) ? $trace[2]['function'] : '(main code)';
    $this->deg_trace = $this->deg_valSpan($script) . ' - ' . $this->deg_valSpan($line). ' - ' . $this->deg_valSpan($funcName);

}

function deg_valSpan($value) {
    return '<span style="display:inline-block; background-color:#fdd;margin:2px 3px; padding:1px 4px; border:1px solid gray;font-size:12pt;">'.$value.'</span>';
}

function deg_indent($count) {
    if ($count<0) {
        $this->deg_indent = substr($this->deg_indent,-$count);
    }
    else {
        $this->deg_indent .= substr('          ',0,$count);
    }    
}

function deg_line($line) {
    print PHP_EOL . $this->deg_indent . $line;
}

function deg_getColor($levelAdjust=0) {
    global $deb_level;
    return $this->deg_colors[$deb_level+$levelAdjust];
}

function deg_output() {
    $this->deg_list->del_output($this);
}

}  // end class

class deb_item {
public $dei_name;  // frequently unknown - except for class or 'array' (array name is unknown)
public $dei_value;
public $dei_valueIsList;

function __construct($name,$value,$valueIsList) {
   $this->dei_name = $name;
   $this->dei_value = $value;
   $this->dei_valueIsList = $valueIsList;
}

function dei_out_singleValue($globals, $value) {
    // single value
    //$value = htmlspecialchars( $value, ENT_QUOTES ); 
    $globals->deg_line('<span style="display:inline-block; border:1px solid black; background-color:#ffffcc; margin: 2px 2px; padding: 1px 4px">');
    $globals->deg_indent(3);
    $globals->deg_line($value);
    $globals->deg_indent(-3);
    $globals->deg_line('</span>');
}

function dei_output($globals) {
    global $deb_level;
    $globals->deg_line('<tr>');
    $globals->deg_indent(3);
    $padding =  ($this->dei_valueIsList) ? '8px 8px' : '2px 2px';
    $name = ($deb_level>1) ? $this->dei_name : 'arg';
    //if ($deb_level>1) {
        $globals->deg_line('<td style="background-color:'.$globals->deg_getColor(-1).';width:10px; border:1px;vertical-align:top;padding:'.$padding.';text-align:left;">');
        $globals->deg_line($name);
        $globals->deg_line('</td>');
   // }    
    $globals->deg_line('<td style="background-color:'.$globals->deg_getColor(-1).';width:10px; border:1px solid black;vertical-align:top;padding:'.$padding.';text-align:left;">');
    if ($this->dei_valueIsList) {
        $this->dei_value->del_output($globals);
    }
    else {
       $val ='<span style="display:inline-block; border:1px solid black; background-color:'.$globals->deg_getColor().';margin: 2px 2px; padding: 1px 4px">'.$this->dei_value.'</span>';
       $globals->deg_line($val);
       if (substr($this->dei_value,0,2)=='@e') {
           $globals->deg_exit = $this->dei_value;
       }
       //$globals->deg_line($this->dei_value);
    }
    $globals->deg_line('</td>');
    $globals->deg_indent(-3);
    $globals->deg_line('</tr>');
}

}  // end class

class deb_list {
public $del_count;
public $del_list;  // list of variables - each one is a varItem or a varBatch
public $del_isComplex;  // true if complex structures such as (nested) arrays
public $del_name = '';

function __construct () {
    global $deb_level;
    $this->del_count = 0;
    $this->del_list = array();
    $this->del_isComplex = FALSE;
}

function del_addItem($value, $name='') {
    global $deb_circular, $deb_circular2, $deb_level;
    ++$deb_level;
    if ( is_null($value)) {
        $item = new deb_item($name, '<span style="color:red;">NULL</span>', FALSE);
     }
    else if ( is_bool($value)) {
        $val = $value===FALSE ? 'FALSE' : 'TRUE';
        //$item = new deb_item($name, '<span style="color:blue;">'.$val.'/span>', FALSE);
        $item = new deb_item($name, $val, FALSE);
    }  
    else if ( is_array($value)) {
        $list = new deb_list($deb_level);
        $list->del_isComplex = TRUE;
        $name = ($name=='') ? '(array)' : $name;
        if (empty($value)) {
            $item = new deb_item($name, '(empty array)', FALSE);
        }
        else {
            foreach ( $value as $varName => $varValue) {
                $list->del_addItem($varValue,'['.$varName.']');
            }
            $item = new deb_item($name, $list, TRUE);
        }    
       $list->del_name = 'Array';
       //$this->del_name = 'Array';
    }    
    else if ( is_object($value)) {
        $list = new deb_list($deb_level+1);
        $className = get_class($value);
        $result = array_search ( $value , $deb_circular2, TRUE );
        if ($result !== FALSE) {
             $item = new deb_item($name, '(' . $className . ' Possible Circular Reference Stops Here)', FALSE);
        }
        //else if ( isset($deb_circular2[$className]) and ($deb_level > $deb_circular[$className]) ) {
        //     $item = new deb_item($name, '(' . $className . ' Circular Reference Stops Here)', FALSE);
        //}
        else {
            $deb_circular2[] = $value;  
            $deb_circular[$className] =  $deb_level;      
            $array = get_object_vars($value);
            foreach ( $array as $varName => $varValue) {
                $list->del_addItem($varValue, $varName);
            }
            $item = new deb_item($name, $list, TRUE);
            $list->del_name = 'Object: ' . $className;
        }    
    }    
    else if ( $value==='') {
        $item = new deb_item($name, '""', FALSE);
        //$item = new deb_item($name, '<span style="color:blue;">""</span>', FALSE);
    }
    else {
        //$val ='<span style="display:inline-block;  border:1px solid black; background-color:#ffffcc; margin: 2px 2px; padding: 1px 4px">'.$value.'</span>';
        $item = new deb_item($name, $value, FALSE);
    }
    $this->del_list[] = $item;
    ++$this->del_count;    
    --$deb_level;
}

function del_output($globals) {
    global $deb_level;
    ++$deb_level;
    $globals->deg_line('<table style="width:100%;padding:0px 0px; margin: 0px 0px; font-size:11pt; line-height:14pt;vertical-align:top;border:0px solid black;background-color:'.$globals->deg_getColor(-1).';border-collapse:collapse;border-spacing:0;empty-cells:show;">');
    $globals->deg_line('<tr>');
    if ($deb_level==1) {
        $globals->deg_line('<td style="font-size:14pt; font-weight:bold;padding-left:20px;" colspan="2">');
        $globals->deg_line('Debug called from: '. $globals->deg_trace);
    }
    else    {
        $globals->deg_line('<td colspan="2">');
        $globals->deg_line($this->del_name);
   }
    $globals->deg_line('</td>');
    $globals->deg_line('</tr>');
    $globals->deg_indent(3);
    foreach ($this->del_list as $item) {
        $item->dei_output($globals);

    }
    $globals->deg_indent(-3);
    if ( ($globals->deg_exit!==FALSE) and ($deb_level==1) ) {
         $globals->deg_line('<tr><td style="font-size:14pt;background-color:red; color:yellow;padding-left:20px;" colspan="2">EXIT '.$globals->deg_exit.'</td></tr>');
    }
    $globals->deg_line('</table>');
     --$deb_level;
   
}

}  // end class

function draff_errorTerminate($additional = NULL) {
    // may need html header, etc
    // probably should check that person is logged in
    if ( RC_LIVE) {
        // somewhat cryptify query so its still readable, but not usable as-is
       if ( substr_count($additional,':') * substr_count($additional,'`') >= 1) {
            // better would be (if I knew how to do it) $additional = preg_replace ( look for ` with : before next ` , "`rc_'" , $additional)
            $additional = str_replace(':','_',$additional);  // better if only : inside of `...` was changed
        }    
    }    
    $trace = debug_backtrace();
    $td = '<td style="border: 1px;padding:3pt 5pt 3pt 15pt;">';
    $tdRow = '<td style="border: 1px;padding:8pt 5pt 8pt 25pt; background-color: #ffcccc;" colspan="5";>';
    $tdHead = '<td style="border: 1px;padding:2pt 5pt 2pt 15pt;background-color: #ffcccc;" >';
    print '<table style="width:100%;margin: 20pt 10pt 20pt 20pt; border-collapse:collapse;border-spacing:0;empty-cells:show;border: 1pt solid black;max-width:800px;">';
    print '<tr>' . $tdRow . '<h2>Fatal Error - Please send to Kidchess programming staff or office</h2></td></tr>';
    print '<tr>';
    print $tdHead . 'Trace<br>Depth'. '</td>';
    print $tdHead . 'File'. '</td>';
    print $tdHead . 'Function'. '</td>';
    print $tdHead . 'Line'. '</td>';
    //print $tdHead . 'Class'. '</td>';
    print '</tr>';
    $count = min(count($trace),15);
    for ($i = 1; $i<=$count-1; ++$i) {
        $caller = $trace[$i]; 
        $funcName = $caller['function'];
        if ( strpos($funcName,'_errorTerminate') === FALSE ) {
            print '<tr>';
            print $td.$i.'</td>';
            print $td.basename ($caller['file']).'</td>';
            print $td.$funcName.'</td>';
            print $td. $caller['line'].'</td>';
            // $ar = $caller['args'];
            // print $td.$caller['class'].'</td>';
            print '</tr>';
        }    
    }        
    if ( !empty($additional)) {
        // print '<tr>' . $tdRow . 'More Information'. '</td></tr>';
        print '<tr>' . $tdRow . $additional . '</td></tr>';
    }
    print '</table>';
    exit();
}
 
// function dbxxglog() {
//     // has not been tested/used for a long time
//     //ob_clean ();
//     $logKey = 'dxxbgLog';
//     if ( ! isset($_SESSION[$logKey]) ) {
//         $_SESSION[$logKey] = array();
//     }
//     $logArray = & $_SESSION[$logKey];
//     $numargs = func_num_args();
//     if ( $numargs!=0) {
//         $arg_list = func_get_args();
//         $logArray[] = $arg_list;
//         return;
//     }    
//     // no args - end of logging - time to print
//     print '<hr>@@@@@@@@@@@@ Start of Debug Logging @@@@@@@@@@@@<hr>';
//     $logCount = count($logArray);
//     for ($i=0; $i<$logCount; ++$i) {
//         $entryArray = $logArray[$i];
//         $entryCount = count($entryArray);
//         print '<br>@@_____';
//         for ($j = 0; $j < $entryCount; $j++) {
//             deb_out_variable($entryArray[$j]);
//             if ( $j<$entryCount-1) {
//                 print '______';
//             }    
//         }    
//     }
//     print '_____@@<br>';
//     print '<hr>@@@@@@@@@@@@ End of Debug Logging @@@@@@@@@@@@<hr>';
//     unset($_SESSION[$logKey]);
//     //exit('<br>exit - end of log');
// }

//  function draff_assert_handler($file, $line, $code, $desc = null) {
//      draff_errorTerminate('Assert Error ' . $code, $desc);
//      exit;
//  }
//  
//  function draff_functions_init() {
//      assert_options(ASSERT_CALLBACK, 'draff_assert_handler');  // Set up the callback
//  }

?>