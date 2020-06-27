<?php

// pay-report-checkRegister.inc.php

class stdReport_checkRegister {

function chkReg_stdReport_output($emitter, $appGlobals, $period) {
    $checkBatch = new pay_check_batch;
    $checkBatch->chkBat_readTransactions( $appGlobals , $period);

    $emitter->table_start('table.payTable',5);
    $colspan = 'colspan="5"';

    $emitter->table_head_start();

    $emitter->row_start('head');
    $emitter->cell_block('Gross Pay Report','',$colspan);
    $emitter->row_end();

    $emitter->row_start('head');
    $emitter->cell_block('Name','name');
    $emitter->cell_block('Rate Desc','pay');
    //$emitter->cell_block('Count','pay');
    $emitter->cell_block('Rate','pay');
    $emitter->cell_block('Time','pay');
    $emitter->cell_block('Amount','pay');
    $emitter->row_end();

    $emitter->table_head_end();

    $emitter->table_body_start('');
    $rowClass = 'cr-first';
    foreach($checkBatch->chkBat_checkArray as $check) {
        $check->finalCheck_finalize();
        $checkDesc = $check->chkFin_checkDesc;  // usually employee name - or 'totals'
        foreach ($check->chkFin_lines as $line) {
            $emitter->row_start($rowClass);
            $emitter->cell_block($checkDesc,'cr-name');
            if ( $line->payLine_type=='t') {
                $emitter->cell_block($line->payLine_desc,'','colspan="3"');
            }
            else {
                $emitter->cell_block($line->payLine_desc,'');
                $emitter->cell_block(draff_dollarsAsString($line->payLine_rate),'');
                //$emitter->cell_block(draff_minutesAsString($line->payLine_minutes),'');
                 $emitter->cell_block(number_format(($line->payLine_minutes/60),3,'.',',') ,'');
           }
            //$emitter->cell_block($line->payLine_tranCount,'');
            $emitter->cell_block(draff_dollarsAsString($line->payLine_amount),'');
            $emitter->row_end();
            $checkDesc = '';
            $rowClass = 'c';
       }
        $rowClass = 'cr-first';
    }
    $emitter->table_body_end();

    $emitter->table_foot_start();
    $emitter->table_foot_end();

    $emitter->table_end();
}

function chkReg_stdReport_init_styles($emitter) {
    $emitter->emit_options->addOption_styleTag('table.payTable', 'background-color:white;font-size:16pt;');
    $emitter->emit_options->addOption_styleTag('tr.detail', 'background-color:white');
    $emitter->emit_options->addOption_styleTag('tr.cr-first','border-top:8px double #aaaaaa;');
 //   $emitter->emit_options->addOption_styleTag('tr.cr-normal','border-top:1px solid #aaaaaa;');
    $emitter->emit_options->addOption_styleTag('td.cr-name','border-top: none;border-bottom:none;');
    $emitter->emit_options->addOption_styleTag('tr.head', 'background-color:#ccffcc');
    $emitter->emit_options->addOption_styleTag('td.name', '');
    $emitter->emit_options->addOption_styleTag('td.rate', 'text-align: right;');
    $emitter->emit_options->addOption_styleTag('td.time', 'text-align: right;');
    $emitter->emit_options->addOption_styleTag('td.pay', 'text-align: right;');
    $emitter->emit_options->addOption_styleTag('td.totalDesc', 'background-color:#ccffcc;');
    $emitter->emit_options->addOption_styleTag('td.totalPay', 'background-color:#ccffcc;text-align: right;');
}

function drForm_process_output ( $appData, $appGlobals, $appChain, $appEmitter ) {
    $appGlobals->gb_output_form ( $appData, $appChain, $appEmitter, $this );
}

function drForm_outputHeader ( $scriptData, $appGlobals, $chain, $emitter ) {
}

function drForm_outputContent ( $scriptData, $appGlobals, $chain, $emitter ) {
}

function drForm_outputFooter ( $scriptData, $appGlobals, $chain, $emitter ) {
}

} // end class

class pay_check_line {  // used for each applicable pay method and totals
public $payLine_rate    = 0;
public $payLine_minutes = 0;
public $payLine_amount  = 0;
public $payLine_desc    = '';
public $payLine_tranCount = 0;
public $payLine_sortVal = 0;
public $payLine_type    = ' ';  // t= total

function __construct($sortVal, $desc, $type= '') {
    $this->payLine_desc    = $desc;
    $this->payLine_sortVal = $sortVal;
    $this->payLine_type = $type;
}

function addRow($row) {
    $staffId = $row['jTsk:@StaffId'];
    $this->payLine_minutes    += $row['jTsk:PayMinutes'];
    $this->payLine_amount     += payData_dollarAmountDecrypt( $staffId , $row['jTsk:PayAmount'] );
    $this->payLine_rate        = payData_dollarAmountDecrypt( $staffId , $row['jTsk:PayRate'] );
    //???? assert it stays the same
    ++$this->payLine_tranCount;
}

}

class pay_final_check {  // used for each employee and also grand totals
public $chkFin_lines   = array();
public $chkFin_checkDesc;  // who or totals
public $chkFin_totalLine;

function __construct($desc) {
    $this->chkFin_checkDesc = $desc;
    $this->chkFin_totalLine = new pay_check_line(99,'Total Check','t');
    $this->chkFin_lines[99] = $this->chkFin_totalLine;
}

function finalCheck_addRow($row) {
    $amount     = $row['jTsk:PayAmount'];
    $minutes    = $row['jTsk:PayMinutes'];
    $rate       = $row['jTsk:PayRate'];
    $payMethod  = $row['jTsk:JobRateCode'];
    $sourceType = $row['jTsk:OriginCode'];
    if ( isset($this->chkFin_lines[$payMethod]) ) {
        $line = $this->chkFin_lines[$payMethod];
    }
    else {
        switch ($payMethod) {
            case 1: // admin rate
                $line = new pay_check_line(1,'Admin Rate');
                break;
            case 2: // field rate
                $line = new pay_check_line(2,'Field Rate');
                break;
            case 3: // salary rate
                $line = new pay_check_line(3,'Salary');
                break;
            case 21: // rate override
                $line = new pay_check_line(4,'Other Rates');
                break;
            case 22: // pay override
                $line = new pay_check_line(5,'Other Pay');
                break;
            default: // Unknown
                $line = new pay_check_line(6,'Unknown Method');
                break;
        }
        $this->chkFin_lines[$payMethod] = $line;
    }
    $line->addRow($row);
    $this->chkFin_totalLine->addRow($row);
}

function finalCheck_finalize() {
    ksort($this->chkFin_lines);
}

} // end class

class pay_check_batch {

public $chkBat_checkArray = array();
public $chkBat_grandTotal;

function __construct() {
    $this->chkBat_grandTotal   = new pay_final_check('Grand Totals');
}

function chkBat_readTransactions( $appGlobals , $period) {
    $result = $this->chkBat_readQuery( $appGlobals , $period);
    $prevTranId = NULL;
    while ($row=$result->fetch_array()) {
        $curTranId = $row['jTsk:JobTaskId'];
        if ( $curTranId == $prevTranId) {
            continue;
        }
        $prevTranId = $curTranId;
        $employeeId = $row['jTsk:@StaffId'];
        if ( isset($this->chkBat_checkArray[$employeeId]) ) {
            $employeeCheck = $this->chkBat_checkArray[$employeeId];
        }
        else {
            $employee = new dbRecord_payEmployee;
            $employee->emp_processRow($appGlobals ,$row);
            $employeeCheck = new pay_final_check($employee->emp_name);
            $this->chkBat_checkArray[$employeeId] =$employeeCheck;
        }
        $employeeCheck->finalCheck_addRow($row);
        $this->chkBat_grandTotal->finalCheck_addRow($row);
    }
    $this->chkBat_checkArray['totals'] = $this->chkBat_grandTotal;
}

function chkBat_readQuery( $appGlobals , $period) {
    $sql = array();
    $sql[] = "SELECT *";
    $sql[] = "FROM `job:task`";
    $sql[] = "JOIN `job:employee` ON `jEmp:@StaffId` = `jTsk:@StaffId`";
    $sql[] = "JOIN `st:staff`     ON (`sSt:StaffId`   = `jTsk:@StaffId`)";
    $sql[] = "WHERE (`jTsk:@PayPeriodId`= '{$period->prd_payPeriodId}')";
    $sql[] = "ORDER BY `sSt:FirstName`,`sSt:LastName`, `jTsk:JobTaskId`, `jTsk:OriginCode` DESC";
    $query = implode( $sql, ' ');
    $result = $appGlobals->gb_sql->sql_performQuery( $query ,__FILE__ , __LINE__);
    return $result;
}

} // end class

?>