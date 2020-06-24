<?php
// 
// rc_job-common-functions.inc.php
// 
// define constants and utility functions used by 
// both payroll and scheduling
//
// dependencies:
//	rc_defines.inc.php
//	rc_database.inc.php
//


	// Task record origin codes
const RC_JOB_ORIGIN_SCHEDULE	= 10;  // schedule item
const RC_JOB_ORIGIN_SM			= 20;  // schedule item overridden by site manager
const RC_JOB_ORIGIN_STAFF		= 30;  // staff new record or overrides
const RC_JOB_ORIGIN_TRAVEL		= 40;  // travel record
const RC_JOB_ORIGIN_SALARY		= 50;  // salary record
const RC_JOB_ORIGIN_PM			= 60;  // payroll manager overrides

	// Attendance codes
const RC_JOB_ATTEND_NA			= 0;
const RC_JOB_ATTEND_PRESENT		= 1;
const RC_JOB_ATTEND_PIF			= 2;
const RC_JOB_ATTEND_SICK			 = 16;
const RC_JOB_ATTEND_VACATION		 = 17;
const RC_JOB_ATTEND_ABSENT_EXCUSED	 = 18;
const RC_JOB_ATTEND_ABSENT_UNEXCUSED = 19;

	// Schedule Status codes
const RC_JOB_SCHEDSTATUS_UNPUBLISHED	= 0;  // seen by scheduling, but not by staff or payroll
const RC_JOB_SCHEDSTATUS_PUBLISHED		= 1;  // visible to all
const RC_JOB_SCHEDSTATUS_NOT_EVENT		= 2;  // not part of scheduling: payroll only.

	// PayPeriod Types
const RC_PAYPERIOD_NORMAL  = 1;
const RC_PAYPERIOD_SPECIAL = 2;

	// PayPeriod length
const RC_PAYPERIOD_DAYS		= 14;  // days in a normal pay period

	// earliest payroll date:
const RC_PAYPERIOD_EARLIEST_DATE = "2019-04-06";
			// job records before this are part of scheduling, but not part of payroll

	// latest payroll date:
const RC_JOB_MAX_ADVANCE_DAYS = 366;  // all dates must be no later than today plus this constant

	// keys in job:systemstatus records
const RC_JOB_STATUS_KEY_OPEN_PERIOD_STAFF	= "OpenPeriod_Staff";
const RC_JOB_STATUS_KEY_OPEN_PERIOD_PAYMASTER	= "OpenPeriod_PayMaster";



	// Keys for default values in the following constant arrays
const RC_JOB_ROLE_DEFAULT = "default";
const RC_JOB_PROG_DEFAULT = "default";

	// RC_JOB_ADJUSTMENT_BEFORE[<programtype>][<roletype>]
	//   <programtype> is one of the RC_PROG_TYPE... constants defoned in rc_defines.inc.php
	//   <roletype> is one of the RC_ROLE_... constants defined in rc_defines.inc.php
	// Gives minutes before scheduled event time that staff in
	//   the given programtype and roletype are expected to work.
	// If RC_JOB_ADJUSTMENT_BEFORE[<programtype>] not set,
	//     use RC_JOB_PROG_DEFAULT as key.
	// If RC_JOB_ADJUSTMENT_BEFORE[<programtype>][<roletype>] not set,
	//     use RC_JOB_ROLE_DEFAULT as 2nd key.
	// (Use array_key_exists( <key>, <array> ) to determine existence, because 
	//  isset() does not work on constants.)
	// Numbers should be >= 0
	// ***Make sure there is an entry for RC_JOB_PROG_DEFAULT in RC_JOB_ADJUSTMENT_BEFORE.
	// ***Make sure there is an entry for RC_JOB_ROLE_DEFAULT
	//									in each RC_JOB_ADJUSTMENT_BEFORE[<programtype>].
const RC_JOB_ADJUSTMENT_BEFORE = array( 
	RC_PROG_TYPE_CLASS => array(
						RC_ROLE_SITEMANAGER => 25,
						RC_JOB_ROLE_DEFAULT => 20 ),
	RC_PROG_TYPE_CAMP => array(
						RC_ROLE_SITEMANAGER => 30,
						RC_JOB_ROLE_DEFAULT => 30 ),
	RC_PROG_TYPE_TOURNAMENT => array(
						RC_ROLE_SITEMANAGER => 25,
						RC_JOB_ROLE_DEFAULT => 20 ),
	RC_JOB_PROG_DEFAULT => array(
						RC_ROLE_SITEMANAGER => 15,
						RC_JOB_ROLE_DEFAULT => 15 ),
		);
	// Similarly for RC_JOB_ADJUSTMENT_AFTER[<programtype>][<roletype>]
const RC_JOB_ADJUSTMENT_AFTER = array( 
	RC_PROG_TYPE_CLASS => array(
						RC_ROLE_SITEMANAGER => 25,
						RC_JOB_ROLE_DEFAULT => 15 ),
	RC_PROG_TYPE_CAMP => array(
						RC_ROLE_SITEMANAGER => 15,
						RC_JOB_ROLE_DEFAULT => 15 ),
	RC_PROG_TYPE_TOURNAMENT => array(
						RC_ROLE_SITEMANAGER => 15,
						RC_JOB_ROLE_DEFAULT => 15 ),
	RC_JOB_PROG_DEFAULT => array(
						RC_ROLE_SITEMANAGER => 15,
						RC_JOB_ROLE_DEFAULT => 15 ),
		);

	// default prep time minutes for site managers in class type programs
	// indexed by number of periods
CONST RC_SM_CLASS_PREP_MINUTES = array( 1 => 30, 2 => 40, 3 => 50, 4 => 60 );


// rc_getStaffStartFromEventStart
// Args in:
//	$eventStart -	start time of event
//	$programType -  program type of event
//	$roleType - 	role type of staff
// returns:
//	calculated start time for a $roleType staff member, or null
function rc_getStaffStartFromEventStart( $eventStart, $programType, $roleType ) {
	if ( ! $eventStart) {
		return null;
	}
	$pType = array_key_exists( $programType, RC_JOB_ADJUSTMENT_BEFORE ) ?
											$programType : RC_JOB_PROG_DEFAULT;
	$rType = array_key_exists( $roleType, RC_JOB_ADJUSTMENT_BEFORE[$pType] ) ?
											$roleType : RC_JOB_ROLE_DEFAULT;
	$timeBefore = RC_JOB_ADJUSTMENT_BEFORE[$pType][$rType];

	$dateObj = new DateTime( $eventStart );
	$dateObj->modify( "-{$timeBefore} minutes" );
	return $dateObj->format( 'H:i:s' );
}		


// rc_getStaffEndFromEventEnd
// Args in:
//	$eventEnd -	end time of event
//	$programType -  program type of event
//	$roleType - 	role type of staff
// returns:
//	calculated end time for a $roleType staff member, or null
function rc_getStaffEndFromEventEnd( $eventEnd, $programType, $roleType ) {
	if ( ! $eventEnd) {
		return null;
	}
	$pType = array_key_exists( $programType, RC_JOB_ADJUSTMENT_AFTER ) ?
											$programType : RC_JOB_PROG_DEFAULT;
	$rType = array_key_exists( $roleType, RC_JOB_ADJUSTMENT_AFTER[$pType] ) ?
											$roleType : RC_JOB_ROLE_DEFAULT;
	$timeAfter = RC_JOB_ADJUSTMENT_AFTER[$pType][$rType];

	$dateObj = new DateTime( $eventEnd );
	$dateObj->modify( "+{$timeAfter} minutes" );
	return $dateObj->format( 'H:i:s' );
}		


// rc_getPrepTimeFromProgramId
// Args in:
//	$programId -	id of program
//	$roleType - 	role type of staff
// Returns:
//	minutes of prep time
//		returns 0 if db errors or no program id or time not specified in constant
function rc_jobGetPrepTimeFromProgramId( $programId, $roleType ) {
	if ($roleType != RC_ROLE_SITEMANAGER) {  // no prep time except for site managers
		return 0;
	}
	if ($programId && is_numeric( $programId )) {  // real program id
			// count non-feature periods in program
		$db = rc_getGlobalDatabaseObject();
		$notHidden = RC_HIDDEN_SHOW;
		$featureBit = RC_PERIOD_SEQ_FEATURE;
		$query = <<<DELIM
SELECT COUNT(*) FROM  `pr:period`
WHERE (`pPe:@ProgramId` = '{$programId}')
 AND (`pPe:PeriodSequenceBits` < {$featureBit})
 AND (`pPe:HiddenStatus` = {$notHidden})
DELIM;
		$periodCount = $db->rc_QueryValue( $query );
		if ($periodCount === FALSE) { // database error
			return 0;
		}
		if ( ! array_key_exists( $periodCount, RC_SM_CLASS_PREP_MINUTES)) {
			return 0;
		}
		return RC_SM_CLASS_PREP_MINUTES[$periodCount];
	}
	return 0;  // default
}



// rc_jobGetPayPeriodIdFromDate
// Args in:
//	$jobDate :	as a yyyy-mm-dd string
// Returns:
//	id of pay period, or an error message string
//		use is_string( <result> ) to test for failure
// Creates pay period(s) if necessary
// Only finds/creates NORMAL pay periods
function rc_jobGetPayPeriodIdFromDate( $jobDate ) {
	if ($jobDate < RC_PAYPERIOD_EARLIEST_DATE) {
		return "Error: {$jobDate} is before start of payroll system";
	}
	$db = rc_getGlobalDatabaseObject();
	$payPeriodNormal = RC_PAYPERIOD_NORMAL;
		// fetch normal pay period that includes $jobDate
	$query = <<<DELIM
SELECT 
 `jPer:PayPeriodId` as `payperiodid`
FROM  `job:payperiod`
WHERE ('{$jobDate}' BETWEEN `jPer:DateStart` AND `jPer:DateEnd`)
 AND (`jPer:PayPeriodType` = '{$payPeriodNormal}')
DELIM;
	$result = $db->rc_query( $query );
	if ($result === FALSE) {
		return "Database fetch error";
	}
	if ($result->num_rows > 0) {  // pay period record exists
		$row = $result->fetch_assoc();
		return intval( $row['payperiodid'] );  // ensure id is not returned as a string
	}
	$jobDateObj = new DateTime( $jobDate );
	$maxFutureDays = RC_JOB_MAX_ADVANCE_DAYS;
	$dateObj = new DateTime();  // now
	$dateObj->modify( "+{$maxFutureDays} days" );  // latest date allowed
	if ($jobDateObj > $dateObj) {
		return "Error: {$jobDate} too far in the future";
	}
		// find last normal pay period record
	$query = <<<DELIM
SELECT 
 `jPer:DateEnd` as `enddate`
FROM  `job:payperiod`
WHERE (`jPer:PayPeriodType` = '{$payPeriodNormal}')
ORDER BY `jPer:DateEnd` DESC
LIMIT 1
DELIM;
	$result = $db->rc_query( $query );
	if ($result === FALSE) {
		return "Database fetch last record error";
	}
	if ($result->num_rows == 0) {
		return "Error: No database records";
	}
	$row = $result->fetch_assoc();
	$periodEndDate = $row['enddate'];
	if ($jobDate <= $periodEndDate) {
		return "Pay period missing: {$jobDate} is before last pay period end date";
	}
	$dateObj = new DateTime( $periodEndDate );  // last day of last pay period
	while (TRUE) {
		$dateObj->modify( "+1 day" );  // first day of next pay period
		$payPeriodId = rc_jobMakeNormalPayPeriod( $dateObj->format( 'Y-m-d' ) );
		if ($payPeriodId === FALSE) {
			return "Error creating new database record";
		}
		$dateObj->modify( "+" . (RC_PAYPERIOD_DAYS - 1) . " days" );  // last day of pay period
		if ($dateObj >= $jobDateObj) {  // $jobDate is in this pay period
			return intval( $payPeriodId );  // ensure id is not returned as a string
		}
	}
}

// rc_jobMakeNormalPayPeriod
// Args in:
//	$startDate :	first day of pay period, as a yyyy-mm-dd string
// Returns:
//	id of new pay period, or FALSE if error
// Creates a NORMAL pay period starting at $startDate
function rc_jobMakeNormalPayPeriod( $startDate ) {
	$dateObj = new DateTime( $startDate );
	$dateObj->modify( "+" . (RC_PAYPERIOD_DAYS - 1) . " days" );  // last day of pay period
	$endDate = $dateObj->format( 'Y-m-d' );
	$db = rc_getGlobalDatabaseObject();
	$q = new rc_saveToDbQuery( $db, 'job:payperiod', "INSERT INTO" );
	$q->setFieldVal( 'jPer:PayPeriodType', RC_PAYPERIOD_NORMAL );
	$q->setFieldVal( 'jPer:PeriodName', rc_jobMakePayPeriodNameFromDates( $startDate, $endDate ) );
	$q->setFieldVal( 'jPer:DateStart', $startDate );
	$q->setFieldVal( 'jPer:DateEnd', $endDate );
	$q->setFieldVal( 'jPer:WhenClosed', NULL );
	$q->setFieldVal( 'jPer:StatusStep', 0 );
	$q->setFieldVal( 'jPer:StatusReports', 0 );
	$q->setFieldVal( 'jPer:HiddenStatus', RC_HIDDEN_SHOW );
	$q->setModByAndWhen( 'jPer:' );
	$q->buildWhere( 'jPer:PayPeriodId', NULL );
	$q->setCopyToHistoryBeforeAfter();
	$result = $q->doQuery();
	if ($result === FALSE) {
		return FALSE;  // database error
	}
	return $q->getInsertId();  // id of record just inserted
}


// rc_jobMakePayPeriodNameFromDates
// Args in:
//	$startDate :		first day of pay period, as a yyyy-mm-dd string
//	$endDate :			last day of pay period, as a yyyy-mm-dd string
//	$payPeriodType :	(optional) RC_PAYPERIOD_NORMAL(default) or RC_PAYPERIOD_SPECIAL
// Returns:
//	name for pay period:  DoW Month Day[, Year] - DoW Month Day, Year[ (Special)]
function rc_jobMakePayPeriodNameFromDates( $startDate, $endDate, $payPeriodType=RC_PAYPERIOD_NORMAL ) {
	$startDateObj = new DateTime( $startDate );
	$endDateObj = new DateTime( $endDate );
	$startDateStr = $startDateObj->format( 'D F j, Y' );
	$endDateStr = $endDateObj->format( 'D F j, Y' );
	if (substr( $startDateStr, -4 ) == substr( $endDateStr, -4 )) {  // same year
		$startDateStr = substr( $startDateStr, 0, -6 );  // remove ", yyyy" from 1st date
	}
	$specialStr = ($payPeriodType == RC_PAYPERIOD_SPECIAL) ? " (Special)" : "";
	return "{$startDateStr} - {$endDateStr}{$specialStr}";
}


	// rc_jobGetMinStaffEditableDate
	// Returns earliest date that schedule is editable by staff
	// Returns FALSE if error
function rc_jobGetMinStaffEditableDate() {
	$db = rc_getGlobalDatabaseObject();
	$key = RC_JOB_STATUS_KEY_OPEN_PERIOD_STAFF;
	$query = <<<DELIM
SELECT `jPer:DateStart`
FROM `job:systemstatus`
LEFT JOIN `job:payperiod` ON `jPer:PayPeriodId` = `jSys:StatusValue`
WHERE `jSys:StatusKey` = '{$key}'
DELIM;
	$startDate = $db->rc_QueryValue( $query );  // start of pay period currently open to staff
	if (($startDate === FALSE) || is_null( $startDate )) { // database error
		return FALSE;
	}
	return max( $startDate, RC_PAYPERIOD_EARLIEST_DATE );
}



/*
---------------------

*/


















?>