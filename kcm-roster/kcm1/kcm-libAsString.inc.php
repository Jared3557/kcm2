<?php
//
// kcm-libAsString.inc.php
//
// convert kcm fields to strings
//

function kcmAsString_Time12Hour($timeFromDatabase ) {
    $time = date_create_from_format( 'H:i:s', $timeFromDatabase );
    if ($time === FALSE) 
        return $timeFromDatabase;
    $formattedTime = date_format( $time, 'g:i a' );
    if ($formattedTime === FALSE) 
        return $timeFromDatabase;
    return $formattedTime;
} 
function kcmAsString_DateTime($pWhen, $pFormat) {
    $dt = strtotime( $pWhen );
    return date( $pFormat, $dt );
}    

function kcmAsString_Grade($pGradeCode,$pGradeYear=NULL,$pProgramYear=NULL) {  
    if ($pGradeCode===NULL)
        return "";
    if ($pGradeCode == RC_GRADE_MIN_INFINITY)
        return "Unknown";  
    if ($pGradeCode < RC_GRADE_K)
        return "Pre-K";
    if ($pGradeCode > 12)
        return "Adult"; 
    if ($pGradeCode == RC_GRADE_K)
        return "0K";  
    if ($pGradeYear!=NULL and $pProgramYear!=NULL) 
       $pGradeCode = $pGradeCode - ($pGradeYear - $pProgramYear);  
    switch ($pGradeCode){
        case 0: return "0K";
        case 1: return "1st";
        case 2: return "2nd";
        case 3: return "3rd";
        case 4: return "4th";
        case 5: return "5th";
        case 6: return "6th";
        case 7: return "7th";
        case 8: return "8th";
        case 9: return "9th";
        case 10: return "10th";
        case 11: return "11th";
        case 12: return "12th";
        case -1: return "0-PK";
    }    
    return "Other";  // should never get here
}

function kcmAsString_Semester($pIncludeSemester,$pSchoolYear, $pSemesterPart) {  
    if ($pSemesterPart==0 and $pSchoolYear==0)
        return "Current Semester";
    if ($pIncludeSemester)
        $s = ' Semester';
    else
        $s = '';    
    switch ($pSemesterPart){
        case 10: return "Summer ".$pSchoolYear.$s;
        case 20: return "Fall ".$pSchoolYear.$s;
        case 30: return "Winter ".$pSchoolYear.$s;
        case 40: return "Spring "." ".($pSchoolYear+1).$s;
        default: return "Other (".$pSemesterPart.")".$s; // Should seldom get here
    }    
}

function kcmAsString_Rookie($kid,$pRoster=NULL) {  
    //deb($kid->prg->FirstName,$kid->prg->LastName,$kid->prg->EarliestYearSemester,$kid->prg->LatestYearSemester);
    if ($pRoster == NULL) {
        if ($kid->prg->EarliestYearSemester==$kid->prg->LatestYearSemester)
            return 'N';
        else
            return 'V';
    }       
    else {
        $s1 = $kid->prg->EarliestYearSemester;
        $kidYear = substr($s1,0,4);
        $kidCode = substr($s1,4,2);
        $prog = $pRoster->program;
        if ($prog->SchoolYear > $kidYear)
            return 'V';
        else if ($prog->SemesterCode > $kidCode)
            return 'V';
        else    
            return 'N';    
    }
//    else if ($pYears===TRUE) {
//        $s1 = $kid->prg->EarliestYearSemester;
//        $s2 = $kid->prg->LatestYearSemester;
//        $s2Year = substr($s2,0,4);
//        $s2Code = substr($s2,4,2);
//        if ($s1Year==$s2Year)
//            return 'V';
//        else 
//            return 'V'.($s2Year-$s1Year+1);
//    }            
}

function kcmAsString_PickupShort($pPickupCode) {  
   switch ($pPickupCode){
        case 0: return '';
        case 1: return 'A';  // asp
        case 2: return 'P';  // parent
        case 3: return 'W';  // walker
        case 90: return 'O';  // other
        case 91: return 'V';  // varies
    }   
    return '';
}
function kcmAsString_PickupLong($pPickupCode) {  
   switch ($pPickupCode){
        case 0: return '';
        case 1: return 'ASP';  // asp
        case 2: return 'Parent';  // parent
        case 90: return 'Other';  // other
        case 91: return 'Varies';  // varies
    }   
    return '';
}

?>