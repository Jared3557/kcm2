-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 26, 2018 at 03:30 PM
-- Server version: 5.7.22-0ubuntu0.16.04.1
-- PHP Version: 5.6.36-1+ubuntu16.04.1+deb.sury.org+1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kidchess_raccoon`
--



-- ========================================================
--    Task Table
-- ========================================================
-- UPDATE `kidchess_raccoon_jared`.`job:systemstatus` SET `jSys:StatusValue`='' WHERE  `jSys:StatusKey` LIKE 'WhenConverted_%';
-- UPDATE `kidchess_raccoon_jared`.`job:systemstatus` SET `jSys:StatusValue`='' WHERE  `jSys:StatusKey` LIKE 'WhenSynchronized_%';

DROP TABLE IF EXISTS `job:task`;

CREATE TABLE `job:task` (
	`jTsk:JobTaskId` INT(10) UNSIGNED NOT NULL,
    `jTsk:Group@JobTaskId` INT(10) UNSIGNED DEFAULT NULL,
	`jTsk:@StaffId` INT(10) UNSIGNED NOT NULL,
	`jTsk:@PayPeriodId` INT(10) UNSIGNED NOT NULL,
	`jTsk:OriginCode` TINYINT(4) NOT NULL DEFAULT '0',
	`jTsk:ScheduleStatusCode` TINYINT(4) NOT NULL DEFAULT '0',
	`jTsk:PayStatusCode` TINYINT(4) NOT NULL DEFAULT '0',
	`jTsk:@EventId` INT(10) UNSIGNED NULL DEFAULT NULL,
 	`jTsk:Event@ProgramId` INT(10) NULL DEFAULT NULL,
  	`jTsk:EventTimeArrive` TIME NULL DEFAULT NULL,
  	`jTsk:EventTimeDepart` TIME NULL DEFAULT NULL,
	`jTsk:EventRoleCode` TINYINT(4) NOT NULL DEFAULT '0',
	`jTsk:EventPrepTime` INT(11) NOT NULL DEFAULT '0',
 	`jTsk:EventHadEquipment` TINYINT NOT NULL DEFAULT '0',
 	`jTsk:EventHadBadge` TINYINT NOT NULL DEFAULT '0',
	`jTsk:JobDate` DATE NOT NULL,
	`jTsk:JobTimeStart` TIME NULL DEFAULT NULL,
	`jTsk:JobTimeEnd` TIME NULL DEFAULT NULL,
 	`jTsk:JobRateCode` TINYINT(4) NULL DEFAULT '0',
	`jTsk:JobAttendanceCode` TINYINT(4) NOT NULL DEFAULT '1',
 	`jTsk:JobLocation` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
 	`jTsk:JobNotes` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
	`jTsk:OverrideTimeMethod` TINYINT(4) NOT NULL DEFAULT '0',
	`jTsk:OverrideTimeMinutes` INT(6) NOT NULL DEFAULT '0',
	`jTsk:OverrideRateAmount` VARBINARY(20),
	`jTsk:OverrideExplanation` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
	`jTsk:PayMinutes` INT(11) NOT NULL DEFAULT '0',
	`jTsk:PayRate` VARBINARY(20),
	`jTsk:PayAmount` VARBINARY(20),
 	`jTsk:TravelPrev@JobItemId` INT(10) UNSIGNED NULL DEFAULT NULL,
	`jTsk:TravelNext@JobItemId` INT(10) UNSIGNED NULL DEFAULT NULL,
	`jTsk:SyncErrorBitFlags` TINYINT(4) NOT NULL,
	`jTsk:Sync@EmployeeModWhen` DATETIME DEFAULT NULL,
	`jTsk:Sync@EventModWhen` DATETIME DEFAULT NULL,
	`jTsk:Sync@TaskModWhen` DATETIME DEFAULT NULL,
	`jTsk:Sync@CalSchedStaffModWhen` DATETIME DEFAULT NULL,
    `jTsk:Sync@CalSchedDateModWhen` DATETIME DEFAULT NULL,
	`jTsk:Sync@CalSchedDateId` INT(10) UNSIGNED NOT NULL,
	`jTsk:HiddenStatus` TINYINT(4) NOT NULL DEFAULT '0',
	`jTsk:ModBy@StaffId` INT(10) UNSIGNED NOT NULL,
	`jTsk:ModWhen` DATETIME NOT NULL
--	PRIMARY KEY (`jTsk:JobTaskId`),
--	INDEX `jTsk:@StaffId` (`jTsk:@StaffId`),
--	INDEX `jTsk:@PayPeriodId` (`jTsk:@PayPeriodId`)
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB;

DROP TABLE IF EXISTS `history_job:task`;
CREATE TABLE `history_job:task` LIKE `job:task`;

ALTER TABLE `job:task`
  MODIFY `jTsk:JobTaskId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`jTsk:JobTaskId`),
  ADD INDEX `staff` (`jTsk:@StaffId`),
  ADD INDEX `taskGroup` (`jTsk:Group@JobTaskId`),
  ADD INDEX `payperiod` (`jTsk:@PayPeriodId`),
  ADD INDEX `synchronization` (`jTsk:Sync@CalSchedStaffModWhen`, `jTsk:Sync@CalSchedDateModWhen`,`jTsk:Sync@EmployeeModWhen`,`jTsk:Sync@EventModWhen`,`jTsk:Sync@TaskModWhen`, `jTsk:@StaffId`, `jTsk:@EventId`),
  ADD INDEX `staffprogram` (`jTsk:@StaffId`, `jTsk:Event@ProgramId`), 
  ADD INDEX `scheduleId` (`jTsk:@EventId`),
  ADD INDEX `jobdate` (`jTsk:JobDate`)
  ;

ALTER TABLE `history_job:task`
  ADD PRIMARY KEY (`jTsk:JobTaskId`,`jTsk:ModWhen`,`jTsk:ModBy@StaffId`) USING BTREE;

  
  
-- ========================================================
--    Event Table
-- ========================================================

DROP TABLE IF EXISTS `job:event`;

CREATE TABLE `job:event` (
	`jEvt:EventId` INT(10) UNSIGNED NOT NULL,
	`jEvt:@ProgramId` INT(10) UNSIGNED NULL DEFAULT NULL,
	`jEvt:EventDate` DATE NOT NULL,
	`jEvt:StartTime` TIME NULL DEFAULT NULL,
	`jEvt:EndTime` TIME NULL DEFAULT NULL,
	`jEvt:HolidayFlag` TINYINT(4) NOT NULL DEFAULT '0',
	`jEvt:Published?` TINYINT(4) NOT NULL,
	`jEvt:SMSubmissionStatus` TINYINT(4) NOT NULL DEFAULT '0',
 	`jEvt:Location` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
	`jEvt:Notes` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
	`jEvt:NotesIncidents` TEXT NULL COLLATE 'utf8_unicode_ci',
	`jEvt:NotesActivities` TEXT NULL COLLATE 'utf8_unicode_ci',
	`jEvt:SyncErrorBitFlags` TINYINT(4) NOT NULL,
	`jEvt:Sync@CalSchedDateId` INT(10) UNSIGNED NOT NULL,
	`jEvt:Sync@CalSchedDateModWhen`         DATETIME DEFAULT NULL,
	`jEvt:HiddenStatus` TINYINT(4) NOT NULL DEFAULT '0',
	`jEvt:ModBy@StaffId` INT(10) UNSIGNED NOT NULL,
	`jEvt:ModWhen` DATETIME NOT NULL
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB
;

DROP TABLE IF EXISTS `history_job:event`;
CREATE TABLE `history_job:event` LIKE `job:event`;

ALTER TABLE `job:event`
    ADD PRIMARY KEY (`jEvt:EventId`),
    MODIFY `jEvt:EventId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
	ADD INDEX `ProgramDate` (`jEvt:@ProgramId`, `jEvt:EventDate`),
	ADD INDEX `jEvt:@ProgramId` (`jEvt:@ProgramId`),
	ADD INDEX `jEvt:EventDate` (`jEvt:EventDate`),
	ADD INDEX `jEvt:HiddenStatus` (`jEvt:HiddenStatus`),
	ADD INDEX `jEvt:Sync@CalSchedDateId` (`jEvt:Sync@CalSchedDateId`),
	ADD INDEX `Synchronization` (`jEvt:ModWhen`, `jEvt:EventId`, `jEvt:EventDate`)
;

ALTER TABLE `history_job:event`
	ADD PRIMARY KEY (`jEvt:EventId`, `jEvt:ModWhen`, `jEvt:ModBy@StaffId`) USING BTREE
;

-- ========================================================
--    Payroll Period Table
-- ========================================================

DROP TABLE IF EXISTS `job:payperiod`;
CREATE TABLE `job:payperiod` (
	`jPer:PayPeriodId` INT(10) UNSIGNED NOT NULL,
	`jPer:PayPeriodType` TINYINT(4) NOT NULL DEFAULT '0',
	`jPer:PeriodName` VARCHAR(60) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
	`jPer:DateStart` DATE NOT NULL,
	`jPer:DateEnd` DATE NOT NULL,
	`jPer:WhenClosed` DATETIME NULL DEFAULT NULL,
	`jPer:StatusStep` TINYINT(4) NOT NULL DEFAULT '0',
	`jPer:StatusReports` TINYINT(4) NOT NULL DEFAULT '0',
	`jPer:HiddenStatus` TINYINT(4) NOT NULL DEFAULT '0',
	`jPer:ModBy@StaffId` INT(10) UNSIGNED NOT NULL,
	`jPer:ModWhen` DATETIME NOT NULL
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB
;

DROP TABLE IF EXISTS `history_job:payperiod`;
CREATE TABLE `history_job:payperiod` LIKE `job:payperiod`;

ALTER TABLE `job:payperiod`
  ADD PRIMARY KEY (`jPer:PayPeriodId`),
  MODIFY `jPer:PayPeriodId` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  ADD UNIQUE INDEX `jPer:DateStart` (`jPer:DateStart`, `jPer:PayPeriodType`) USING BTREE
;

ALTER TABLE `history_job:payperiod`
	ADD PRIMARY KEY (`jPer:PayPeriodId`, `jPer:ModWhen`, `jPer:ModBy@StaffId`) USING BTREE
;


  
-- ========================================================
--    Payroll employee table
-- ========================================================

DROP TABLE IF EXISTS `job:employee`;
CREATE TABLE `job:employee` (
	`jEmp:@StaffId` INT(10) UNSIGNED NOT NULL,
	`jEmp:PayRateAdmin` VARBINARY(20),
	`jEmp:PayRateField` VARBINARY(20),
	`jEmp:PayRateSalary` VARBINARY(20),
	`jEmp:HiddenStatus` TINYINT(4) NOT NULL DEFAULT '0',
	`jEmp:ModBy@StaffId` INT(10) UNSIGNED NOT NULL,
	`jEmp:ModWhen` DATETIME NOT NULL
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB
;


DROP TABLE IF EXISTS `history_job:employee`;
CREATE TABLE `history_job:employee` LIKE `job:employee`;

ALTER TABLE `job:employee`
  ADD UNIQUE (`jEmp:@StaffId`) USING BTREE
;

ALTER TABLE `history_job:employee`
	ADD PRIMARY KEY (`jEmp:@StaffId`, `jEmp:ModWhen`, `jEmp:ModBy@StaffId`) USING BTREE
;



-- ========================================================
--    Payroll status
-- ========================================================

DROP TABLE IF EXISTS `job:systemstatus`;
CREATE TABLE `job:systemstatus` (
	`jSys:StatusKey` VARCHAR(100) NOT NULL COLLATE 'utf8_unicode_ci',
	`jSys:StatusValue` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
	PRIMARY KEY (`jSys:StatusKey`)
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB
;
  
-- ========================================================
--    Add Indexes to make synchronization more efficent
-- ========================================================

-- ALTER TABLE `ca:scheduledate`
-- 	ADD INDEX `Synchronization` (`cSd:ModWhen`, `cSd:EventId`, `cSd:ClassDate`);
-- ALTER TABLE `ca:scheduledate_staff`
-- 	ADD INDEX `Synchronization` (`cSS:ModWhen`, `cSS:@ScheduleDateId`, `cSS:@StaffId`);
-- 


