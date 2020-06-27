<?php

//--- draff-database.inc.php ---

const DB_MODE_BASIC    = 1;
const DB_MODE_DETAILS  = 2;
const DB_MODE_NOTES    = 3;
const DB_MODE_ALL      = 4;

interface draff_database_record_inferface {
static function rsmDbr_getColumnNames($options=NULL);
static function rsmDbr_getInfo();

} // end interface

abstract class draff_database_record { //  implements draff_database_record_inferface {

const DB_TABLE_NAME  = '';  // name of database table
const DB_TABLE_INDEX = '';  // name of field for autoinc index
const DB_MOD_WHEN    = '';  // name of field for mod when
const DB_MOD_WHO     = '';  // name of field for mod who
const DB_SELECT_FIELDS  = 'array of fields for select';

const DB_HISTORY_TABLE  = '*';  // default is to use "*" to prefix "history_"  to DB_TABLE_NAME
const DB_HISTORY_BEFORE  = FALSE;
const DB_HISTORY_AFTER   = FALSE;
const DB_MODE            = DB_MODE_ALL;

public $joins = array();

function __construct() {
}

}   // end class

class draff_database_joiner extends ArrayObject {  //   // implements  ArrayAccess interface

public $rsmDbj_sharedRecords = array();  // public for debugging

function rsmDbj_getRootRecord( $index=NULL ) {
    return $this[$index] ?? ($this->count()==1  ? reset($this) : NULL );
}

function rsmDbj_addDbRecord_asRoot( $recordClassName, $row, $indexName=NULL ) {
    // ?????? root implies one thing - but this is an array at the root level
    if ($indexName===NULL) {
        $indexName = $recordClassName::DB_TABLE_INDEX;
    }
    $key = $row[$indexName];
    if ($key===NULL) {
        return NULL;
    }
    if ( ! isset($this[$key]) ) {
        $this[$key] = new $recordClassName($row);
    }
    return $this[$key];
}

function rsmDbj_addDbRecord_asMultipleJoins( $className, $record, $tableKey, $row,  $keyName=NULL ) {
     if ($keyName===NULL) {
        $keyName = $className::DB_TABLE_INDEX;
    }
    if ($record == NULL) {
        return NULL;
    }
    if ($tableKey === NULL) {
        return NULL;
    }
    if ( !isset($record->joins[$tableKey]) ) {
        $record->joins[$tableKey] = array();
    }
    $tableArray = &$record->joins[$tableKey];
    $key = $row[$keyName];
    if ($key===NULL) {
        return NULL;
    }
    if ( !isset($tableArray[$key]) ) {
        $tableArray[$key] = new  $className($row);
    }
    return $tableArray[$key];
 }

function rsmDbj_addDbRecord_asSingleJoin( $className , $record, $row, $keyName = NULL ) {
     if ($keyName===NULL) {
        $keyName = $className::DB_TABLE_INDEX;
    }
    if ($record == NULL) {
        return NULL;
    }
    if ( !isset($record->joins[$tableKey]) ) {
        $record->joins[$tableKey] = array();
    }
    $tableArray = &$record->joins[$tableKey];
    if ($joinType==DRAFF_TYPE_JOIN_ARRAY) {
        if ( isset($dest[$key]) ) {
            return $dest[$key];
        }
        else {
            $newObject = new  $className($row);
            $dest[$key] = $newObject;
            return $newObject;
        }
    }
}

function rsmDbj_addDbRecord_asShared( $className, $record, $tableKey, $row,  $keyName  = NULL ) {
     if ($keyName===NULL) {
        $keyName = $className::DB_TABLE_INDEX;
    }
    if ($record == NULL) {
        return NULL;
    }
    if ( !isset($this->rsmDbj_sharedRecords[$tableKey]) ) {
        $this->rsmDbj_sharedRecords[$tableKey] = array();
    }
    $tableArray = &$this->rsmDbj_sharedRecords[$tableKey];
    $key = $row[$keyName];
    if ($key===NULL) {
        return NULL;
    }
    if ( !isset($tableArray[$key]) ) {
        $tableArray[$key] = new $className($row);
    }
    $record->joins[$tableKey] = $tableArray[$key];
    return $tableArray[$key];
}

function getArray() {
    return $this->getArrayCopy();  // used to debug
}

} // end class

class draff_database_query {

public  $rsmDbq_query_string = '';
public  $rsmDbq_query_bindings = array();

public  $rsmDbq_whereCount = 0;
public  $rsmDbq_whereGroup = 0;
private $rsmDbq_query_seperator = '';
private $rsmDbq_query_records = 0;
private $rsmDbq_select_seperator;

function __construct() {
}

function rsmDbq_set($asIsQuery, $bindings=array() ) {
    $this->rsmDbq_query_string = $asIsQuery;
    $this->rsmDbq_query_bindings = $bindings;
}

// Select (column names)

function rsmDbq_selectStart() {
    if ($this->rsmDbq_query_string!='') {
        $this->rsmDbq_query_string .= ' ';
    }
    $this->rsmDbq_query_string .= 'SELECT ';
    $this->rsmDbq_select_seperator ='';
}

function rsmDbq_selectAddColumns($recordClassName,  $endOption=NULL) {
    if ($this->rsmDbq_query_string=='') {
        $this->rsmDbq_query_string = 'SELECT ';
        $this->rsmDbq_select_seperator = '';
    }
    foreach ($recordClassName::DB_SELECT_FIELDS as $field) {
        if ( is_numeric($field ) ) {
            if ($field===$recordClassName::DB_MODE) {
               break;
            }
            continue;
        }
        $this->rsmDbq_query_string .=  $this->rsmDbq_select_seperator . '`' . $field . '`';
        $this->rsmDbq_select_seperator = ' ,';
    }
}

function rsmDbq_selectAddString($string) {
    $this->rsmDbq_query_string .= $this->rsmDbq_select_seperator . $string;
    $this->rsmDbq_select_seperator =' ,';
}


function rsmDbq_add($string, $bindings=NULL ) {
    if ($this->rsmDbq_query_string != '') {
        $this->rsmDbq_query_string .= ' ';
    }
    $this->rsmDbq_query_string .= $string;
    if (  is_array($bindings) ) {
        $this->$rsmDbq_query_bindings = $this->$rsmDbq_query_bindings + $bindings;
    }
 }

function rsmDbq_addBinding($arrayOrKey, $value=null ) {
    if ($value===NULL) {
        if ( is_array($arrayOrKey)) {
            $this->$rsmDbq_query_bindings = $this->$rsmDbq_query_bindings + $arrayOrKey;
        }
    }
    else {
        $this->$rsmDbq_query_bindings[$arrayOrKey] = $value;
    }
}

function rsmDbq_start() {
    if (!empty($this->rsmDbq_query_string)) {
        $this->rsmDbq_query_string .= ' ';
    }
    $this->rsmDbq_select_seperator = '';
    $this->rsmDbq_query_string .= 'SELECT ';
    $this->rsmDbq_query_seperator = ' ';
}

}

class draff_database_engine extends PDO {
public $rsmDbe_transactionCount = 0;
public $rsmDbe_modWho = NULL;

function __construct($host, $dbName, $userId, $password, $charset = 'utf8_unicode_ci') {
    $charset = 'utf8mb4';  // ???? what is wrong with 'utf8_unicode_ci'
    $dsn = "mysql:host=$host;dbname=$dbName;charset=$charset;";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    try {
         parent::__construct($dsn, $userId, $password, $options);
    } catch (\PDOException $e) {
         throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}

function rsmDbe_executeQuery($query) {  // param not used if bindParam is used
    // print '<br><br>'.$query.'<br><br>';
    $stmt = $this->prepare($query->rsmDbq_query_string);
    $stmt->execute($query->rsmDbq_query_bindings);
    return $stmt;
}

function rsmDbe_bindParam($symbol, $value, $type=NULL) {
}

//function rsmDbe_execute($symbolValueArray=NULL) {  // param not used if bindParam is used
//    // whole statement vs from above????
//}

// function sql_prepare($sqlString) {
    //   sql statement with placehlders :xxx
//     $stmt = $pdo->pdo->prepare($sqlCommand, $params);
//     return $stmt;
// }

function rsmDbe_execute($sqlCommand, $placeholders=NULL) {
    $stmt = $this->prepare($sqlCommand);
    $stmt->execute($placeholders);
    return $stmt;
}

// function sql_execute($stmt, $params) {

//     $stmt = $pdo->pdo->prepare($sqlCommand, $params);
//     return $stmt;
// }

function rsmDbe_query($query) {
    // use if no parameters
    return $this->query($query);
}

public function rsmDbe_startTransaction() {
    ++$this->rsmDbe_transactionCount;
	if ($this->rsmDbe_transactionCount != 1) {
        // prevent nested transactions by allowing just the outermost one
        return;
	}
	$this->transactionStarted = TRUE;
	$acsuccess = $this->autocommit( FALSE );
rc_setDebugData( 'Database-transaction', "Setting autocommit to FALSE." );
	if ($acsuccess === FALSE) {  // failure
rc_setDebugData( 'Database-error', "Setting autocommit to FALSE failed." );
		if (RC_TESTING) {  // if testing, queue error (this might show up on *next* page loaded)
			rc_queueError( "DATABASE-ERROR: Setting autocommit to FALSE failed." );
		}
		rc_LogErrorToFile( "DATABASE-ERROR: Setting autocommit to FALSE failed." );
//!!! what else should be one in case of error?
	}
}

function rsmDbe_readRecord($recordClassName, $indexValue) {
    $tableName = $recordClassName::DB_TABLE_NAME;
    $indexName = $recordClassName::DB_TABLE_INDEX;
    $queryString = "SELECT * FROM `".$tableName."` WHERE `".$indexName."`='".$indexValue."'";  //??? should bind
    $stmt = $this->prepare($queryString);
    $stmt->execute(NULL);
    if ($stmt->rowCount()==0) {
        return NULL;
    }
    if ($stmt->rowCount()>1) {
        print 'ERROR';
        return NULL;
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $rec = new $recordClassName;
    $rec->rsmDbr_loadRow($row);
    return $rec;
}

function rsmDbe_writeRecord($recordClassName, $row) {
    $tableName     = $recordClassName::DB_TABLE_NAME;
    $nameOfIndex   = $recordClassName::DB_TABLE_INDEX;
    $nameOfModWhen = $recordClassName::DB_MOD_WHEN;
    $nameOfModWho  = $recordClassName::DB_MOD_WHO;
    $modWhen       = date( "Y-m-d H:i:s" );
    $modWho        = $this->rsmDbe_modWho;
    $historyTable  = $recordClassName::DB_HISTORY_TABLE;
    $historyBefore = $recordClassName::DB_HISTORY_BEFORE;
    $historyAfter  = $recordClassName::DB_HISTORY_AFTER;
    if ( $historyTable == '*' ) {
        $historyTable =  'history_' . $tableName;
    }
    // save before, after ???
    $indexValue = $row[$nameOfIndex] ?? NULL;
    if ( empty($indexValue) or ($indexValue <= 0) ) {
        $indexValue = 0;
    }
    if ( $historyBefore and ($indexValue >= 1) ) {
        $this->rsmDbe_CopyToHistory( $tableName, $historyTable, "WHERE `$nameOfIndex` = '$indexValue'" );
    }
    if ( !empty($nameOfModWhen)  ) {
        $row[$nameOfModWhen] = $modWhen;
    }
    if ( !empty($nameOfModWho) and (!empty($modWho) ) ) {
        $row[$nameOfModWho] = $modWho;
    }
    $setClause = " SET ";
    $sep = '';
    foreach ($row as $name => $value) {
        $valueText = (is_null( $value ) ) ? "NULL" : "'{$value}'";
        $setClause .= $sep . "`{$name}`={$valueText}";
        $sep = ', ';
    }

    if ($indexValue!=0) {
        if ( isset($row[$nameOfIndex]) ) {
            unset($row[$nameOfIndex]);
        }
        $sql = "UPDATE `{$tableName}`";
        $sql .= $setClause;
        $sql .= " WHERE `$nameOfIndex` = '$indexValue'";
        $stmt = $this->prepare($sql);
        $stmt->execute(NULL);
    }
    else {
        $sql = "INSERT INTO `{$tableName}`";
        $sql .= $setClause;
        $stmt = $this->prepare($sql);
        $stmt->execute(NULL);
        $indexValue = $this->lastInsertId();
    }
    if ( $historyAfter and ($indexValue >= 1) ) {
        $this->rsmDbe_CopyToHistory( $tableName, $historyTable, "WHERE `$nameOfIndex` = '$indexValue'" );
    }
    return $indexValue;
}

function rsmDbe_deleteRecord($recordClassName, $indexValue) {
    $tableName     = $recordClassName::DB_TABLE_NAME;
    $nameOfIndex   = $recordClassName::DB_TABLE_INDEX;
    $nameOfModWhen = $recordClassName::DB_MOD_WHEN;
    $nameOfModWho  = $recordClassName::DB_MOD_WHO;
    $modWhen       = date( "Y-m-d H:i:s" );
    $modWho        = $this->rsmDbe_modWho;
    $historyTable  = $recordClassName::DB_HISTORY_TABLE;
    $historyBefore = $recordClassName::DB_HISTORY_BEFORE;
    $historyAfter  = $recordClassName::DB_HISTORY_AFTER;
    if ( empty($indexValue) or !is_numeric($indexValue) ) {
        return;
    }
    if ( $historyTable == '*' ) {
        $historyTable =  'history_' . $tableName;
    }
    $sql = "DELETE FROM `{$tableName}` WHERE `{$nameOfIndex}`='{$indexValue}'";
    $stmt = $this->prepare($sql);
    $stmt->execute(NULL);
}

public function rsmDbe_CopyToHistory( $tablename, $historyName, $where ) {
    // copies a record from a table into its history table
    // $tablename is name of table (history table is named "history_{$tablename}"
    // $where is experession that selects record to copy
    //		typically "`{$idFieldName}` = '{$idValue}'" but could be more complicated
    // (can copy multiple records, for example, will copy all records if $where is "TRUE")
    // returns TRUE on success, FALSE on failure
	$query = "REPLACE INTO `{$historyName}` SELECT * FROM `{$tablename}`{$where}";
				// using REPLACE INTO so no problem if record is already there
    dbg($query);
	//$result = $this->rc_query( $query );
	//return $result;
}


public function rsmDbe_commit() {
    --$this->rsmDbe_transactionCount;
	if ($this->rsmDbe_transactionCount != 0) {
        // prevent nested transactions by allowing just the outermost one
        return;
	}
	$success = $this->commit();
rc_setDebugData( 'Database-transaction', "Committing." );
	if ($success === FALSE) {  // failure
rc_setDebugData( 'Database-error', "Committing failed." );
		if (RC_TESTING) {  // if testing, queue error (this might show up on *next* page loaded)
			rc_queueError( "DATABASE-ERROR: Committing failed." );
		}
		rc_LogErrorToFile( "DATABASE-ERROR: Committing failed." );
//!!! what else should be one in case of error?
	}

	$acsuccess = $this->autocommit( TRUE );
rc_setDebugData( 'Database-transaction', "Setting autocommit to TRUE." );
	if ($acsuccess === FALSE) {  // failure
rc_setDebugData( 'Database-error', "Setting autocommit to TRUE failed." );
		if (RC_TESTING) {  // if testing, queue error (this might show up on *next* page loaded)
			rc_queueError( "DATABASE-ERROR: Setting autocommit to TRUE failed." );
		}
		rc_LogErrorToFile( "DATABASE-ERROR: Setting autocommit to TRUE failed." );
//!!! what else should be one in case of error?
	}
	$this->transactionStarted = FALSE;
}

public function rsmDbe_rollback() {
	if ($this->rsmDbe_transactionCount == 0) {
        // error - not in transaction
        return;
	}
    $this->rsmDbe_transactionCount = 0;
	$success = $this->rollback();
rc_setDebugData( 'Database-transaction', "Rolling back." );
	if ($success === FALSE) {  // failure
rc_setDebugData( 'Database-error', "Rolling back failed." );
		if (RC_TESTING) {  // if testing, queue error (this might show up on *next* page loaded)
			rc_queueError( "DATABASE-ERROR: Rolling back failed." );
		}
		rc_LogErrorToFile( "DATABASE-ERROR: Rolling back failed." );
//!!! what else should be one in case of error?
	}

	$acsuccess = $this->autocommit( TRUE );
rc_setDebugData( 'Database-transaction', "Setting autocommit to TRUE." );
	if ($acsuccess === FALSE) {  // failure
rc_setDebugData( 'Database-error', "Setting autocommit to TRUE failed." );
		if (RC_TESTING) {  // if testing, queue error (this might show up on *next* page loaded)
			rc_queueError( "DATABASE-ERROR: Setting autocommit to TRUE failed." );
		}
		rc_LogErrorToFile( "DATABASE-ERROR: Setting autocommit to TRUE failed." );
//!!! what else should be one in case of error?
	}
	$this->transactionStarted = FALSE;
}

//   function sql_writeToLog() {
//   }
//
//   function sql_handleException($message, $code) {
//--    PDOException extends RuntimeException {
//--   /* Properties */
//--   public array $errorInfo ;
//--   protected string $code ;
//--   /* Inherited properties */
//--   protected string $message ;
//--   protected int $code ;
//--   protected string $file ;
//--   protected int $line ;
//--   /* Inherited methods */
//--   final public Exception::getMessage ( void ) : string
//--   final public Exception::getPrevious ( void ) : Throwable
//--   final public Exception::getCode ( void ) : mixed
//--   final public Exception::getFile ( void ) : string
//--   final public Exception::getLine ( void ) : int
//--   final public Exception::getTrace ( void ) : array
//--   final public Exception::getTraceAsString ( void ) : string
//--   public Exception::__toString ( void ) : string
//--   final private Exception::__clone ( void ) : void
//--   }
//   }
//


} // end class

?>