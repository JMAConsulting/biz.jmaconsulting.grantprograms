/**
 * Enhanced Event Registration extension improves how parents register kids
 * in CiviEvent 
 * 
 * Copyright (C) 2012 JMA Consulting
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Support: https://github.com/JMAConsulting/biz.jmaconsulting.grantprograms/issues
 * 
 * Contact: info@jmaconsulting.biz
 *          JMA Consulting
 *          215 Spadina Ave, Ste 400
 *          Toronto, ON  
 *          Canada   M5T 2C7
 */

-- create civicrm_payment table. 
CREATE TABLE IF NOT EXISTS `civicrm_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Id',
  `payment_batch_number` int(10) unsigned NOT NULL COMMENT 'Payment Batch Nnumber',
  `payment_number` int(10) unsigned NOT NULL COMMENT 'Payment Number',
  `financial_type_id` int(10) unsigned NOT NULL COMMENT 'Financial Type ID',
  `contact_id` int(10) unsigned NOT NULL COMMENT 'Contact ID',
  `payment_created_date` date DEFAULT NULL COMMENT 'Payment Created Date.',
  `payment_date` date DEFAULT NULL COMMENT 'Payment Date.',
  `payable_to_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Payable To Name.',
  `payable_to_address` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Payable To Address.',
  `amount` decimal(20,2) NOT NULL COMMENT 'Requested grant amount, in default currency.',
  `currency` varchar(3) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.',
  `payment_reason` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Payment Reason.',
  `payment_status_id` int(10) unsigned DEFAULT NULL COMMENT 'Payment Status ID',
  `replaces_payment_id` varchar(8) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Replaces Payment Id.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;

-- create civicrm_entity_payment
CREATE TABLE IF NOT EXISTS `civicrm_entity_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `payment_id` int(10) unsigned NOT NULL COMMENT 'Type of grant. Implicit FK to civicrm_payment.',
  `entity_table` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Entity Table.',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Entity ID',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_entity_payment_payment_id` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `civicrm_entity_payment`

ALTER TABLE `civicrm_entity_payment`
  ADD CONSTRAINT `FK_civicrm_entity_payment_payment_id` FOREIGN KEY (`payment_id`) REFERENCES `civicrm_payment` (`id`);

-- create civicrm_grant_program
CREATE TABLE IF NOT EXISTS `civicrm_grant_program` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Grant Program ID',
  `label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Label displayed to users',
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Stores a fixed (non-translated) name for the grant program.',
  `grant_type_id` int(10) unsigned NOT NULL COMMENT 'Type of grant. Implicit FK to civicrm_option_value in grant_type option_group.',
  `total_amount` decimal(20,2) NOT NULL COMMENT 'Requested grant program amount, in default currency.',
  `remainder_amount` decimal(20,2) NOT NULL COMMENT 'Requested grant program remainder amount, in default currency.',
  `financial_type_id` int(10) unsigned NOT NULL COMMENT 'Financial Type ID',
  `status_id` int(10) unsigned NOT NULL COMMENT 'Id of Grant status.',
  `applications_start_date` datetime DEFAULT NULL COMMENT 'Application Start Date',
  `applications_end_date` datetime DEFAULT NULL COMMENT 'Application End Date.',
  `allocation_date` date DEFAULT NULL COMMENT 'Allocation date.',
  `is_active` tinyint(4) DEFAULT '1' COMMENT 'Is this grant program active?',
  `is_auto_email` tinyint(4) DEFAULT '1' COMMENT 'Is auto email active?',
  `allocation_algorithm` int(10) unsigned DEFAULT NULL COMMENT 'Allocation Algorithm.',
  `grant_program_id` int(11) DEFAULT NULL COMMENT 'FK reference to this civicrm_grant_program table, used to determine grants given to contact in previous year during assessment.',
  PRIMARY KEY (`id`),
  KEY `FK_civicrm_grant_program_grant_type_id` (`grant_type_id`),
  KEY `FK_civicrm_grant_program_status_id` (`status_id`),
  KEY `FK_civicrm_grant_program_grant_program_id` (`grant_program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `civicrm_grant_program`
ALTER TABLE `civicrm_grant_program`
  ADD CONSTRAINT `FK_civicrm_grant_program_grant_type_id` FOREIGN KEY (`grant_type_id`) REFERENCES `civicrm_option_value` (`id`),
  ADD CONSTRAINT `FK_civicrm_grant_program_status_id` FOREIGN KEY (`status_id`) REFERENCES `civicrm_option_value` (`id`);

-- add columns to civicrm_grant
ALTER TABLE `civicrm_grant` 
  ADD `grant_program_id` INT( 10 ) UNSIGNED NOT NULL COMMENT 'Grant Program ID of grant program record given grant belongs to.' AFTER `contact_id`,
  ADD `grant_rejected_reason_id` INT( 10 ) UNSIGNED NULL DEFAULT NULL COMMENT 'Id of Grant Rejected Reason.' AFTER `status_id` ,
  ADD `assessment` VARCHAR( 655 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `grant_rejected_reason_id`;

--
-- Constraints for table `civicrm_grant`
ALTER TABLE `civicrm_grant`
  ADD CONSTRAINT `FK_civicrm_grant_grant_program_id` FOREIGN KEY (`grant_program_id`) REFERENCES `civicrm_grant_program` (`id`) ON DELETE CASCADE;

-- add option groups and option values

-- Grant Payment Status
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'grant_payment_status';
INSERT IGNORE INTO `civicrm_option_group` (`id`, `name`, `title`, `description`, `is_reserved`, `is_active`) VALUES
(@opGId, 'grant_payment_status', 'Grant Payment Status', NULL, 1, 1);
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'grant_payment_status';

-- option values
SELECT @opv1 := id FROM civicrm_option_value WHERE  name = 'Printed' AND option_group_id = @opGId;
SELECT @opv2 := id FROM civicrm_option_value WHERE  name = 'Reprinted' AND option_group_id = @opGId;
SELECT @opv3 := id FROM civicrm_option_value WHERE  name = 'Stopped' AND option_group_id = @opGId;
SELECT @opv4 := id FROM civicrm_option_value WHERE  name = 'Withdrawn' AND option_group_id = @opGId;

INSERT IGNORE INTO `civicrm_option_value` (`id`, `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) 
 VALUES
(@opv1, @opGId, 'Printed', '1', 'Printed', NULL, 0, 0, 1, 'Payment that has had cheque or other payment created via PDF or csv download. The default status.', 0, 1, 1, NULL, 1, NULL),
(@opv3, @opGId, 'Stopped', '2', 'Stopped', NULL, 0, 0, 1, 'The bank has been told to put a Stop Payment on the cheque or payment. Usually caused by a lost cheque that is being replaced by a newly printed one.', 0, 1, 1, NULL, 1, NULL),
(@opv2, @opGId, 'Reprinted', '3', 'Reprinted', NULL, 0, 1, 1, 'This payment is no longer valid, and a new one has been printed to replace it. For example, a cheque jammed in the printer has been reprinted on cheque with a different number.', 0, 1, 1, NULL, 1, NULL),
(@opv4, @opGId, 'Withdrawn', '4', 'Withdrawn', NULL, 0, 0, 2, 'Payment has been returned. For example, a grant winner gets a different better grant that makes them no longer eligible for this grant.', 0, 1, 1, NULL, NULL, NULL);

-- Grant Program Status
SET @opGId := '';
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'grant_program_status';
INSERT IGNORE INTO `civicrm_option_group` (`id`, `name`, `title`, `description`, `is_reserved`, `is_active`) VALUES
(@opGId, 'grant_program_status', 'Grant Program Status', NULL, 1, 1);
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'grant_program_status';

SET @opv1 := '';
SET @opv2 := '';
SET @opv3 := '';
-- option values
SELECT @opv1 := id FROM civicrm_option_value WHERE  name = 'Accepting Applications' AND option_group_id = @opGId;
SELECT @opv2 := id FROM civicrm_option_value WHERE  name = 'Trial Allocation' AND option_group_id = @opGId;
SELECT @opv3 := id FROM civicrm_option_value WHERE  name = 'Allocation Finalized' AND option_group_id = @opGId;

INSERT IGNORE INTO `civicrm_option_value` (`id`, `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) 
 VALUES
(@opv1, @opGId, 'Accepting Applications', '1', NULL, NULL, 0, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
(@opv2, @opGId, 'Trial Allocation', '2', NULL, NULL, 0, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@opv3, @opGId, 'Allocation Finalized', '3', NULL, NULL, 0, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL);

-- Grant Program Allocation Algorithm
SET @opGId := '';
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'allocation_algorithm';
INSERT IGNORE INTO `civicrm_option_group` (`id`, `name`, `title`, `description`, `is_reserved`, `is_active`) VALUES
(@opGId, 'allocation_algorithm', 'Grant Program Allocation Algorithm', NULL, 1, 1);
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'allocation_algorithm';

-- option values
SET @opv1 := '';
SET @opv2 := '';
SELECT @opv1 := id FROM civicrm_option_value WHERE  name = 'Best To Worst, Fully Funded' AND option_group_id = @opGId;
SELECT @opv2 := id FROM civicrm_option_value WHERE  name = 'Over Threshold, Percentage Of Request Funded' AND option_group_id = @opGId;

INSERT IGNORE INTO `civicrm_option_value` (`id`, `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) 
 VALUES
(@opv2, @opGId, 'Over Threshold, Percentage Of Request Funded', '1', 'Over Threshold, Percentage Of Request Funded', 'immediate', 0, 1, 1, NULL, 0, 0, 1, NULL, 1, NULL),
(@opv1, @opGId, 'Best To Worst, Fully Funded', '2', 'Best To Worst, Fully Funded', 'batch', 0, 0, 1, NULL, 0, 0, 1, NULL, 1, NULL);

-- Grant Thresholds
SET @opGId := '';
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'grant_thresholds';
INSERT IGNORE INTO `civicrm_option_group` (`id`, `name`, `title`, `description`, `is_reserved`, `is_active`) VALUES
(@opGId, 'grant_thresholds', 'Grant Thresholds', NULL, 1, 1);
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'grant_thresholds';

-- option values
SET @opv1 := ''; 
SET @opv2 := '';
SET @opv3 := '';
SET @opv4 := '';
SELECT @opv1 := id FROM civicrm_option_value WHERE  name = 'Funding factor' AND option_group_id = @opGId;
SELECT @opv2 := id FROM civicrm_option_value WHERE  name = 'Fixed Percentage Of Grant' AND option_group_id = @opGId;
SELECT @opv3 := id FROM civicrm_option_value WHERE  name = 'Maximum Grant' AND option_group_id = @opGId;
SELECT @opv4 := id FROM civicrm_option_value WHERE  name = 'Minimum Score For Grant Award' AND option_group_id = @opGId;

INSERT IGNORE INTO `civicrm_option_value` (`id`, `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) 
 VALUES
(@opv1, @opGId, 'Funding factor', '85', NULL, NULL, 0, 0, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
(@opv2, @opGId, 'Fixed Percentage Of Grant', '80', 'Fixed Percentage Of Grant', NULL, 0, 0, 3, NULL, 0, 1, 1, NULL, NULL, NULL),
(@opv3, @opGId, 'Maximum Grant', '1500', 'Maximum Grant', NULL, 0, 0, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
(@opv4, @opGId, 'Minimum Score For Grant Award', '73', 'Minimum Score For Grant Award', NULL, 0, 0, 2, NULL, 0, 1, 1, NULL, NULL, NULL);

-- grant_status
SET @opv1 := '';
SET @opv2 := '';
SET @opv3 := '';
SET @opv4 := '';
SET @opv5 := '';
SET @opv6 := '';
SET @opv7 := '';

SELECT @opGId := id FROM civicrm_option_group WHERE name = 'grant_status';

-- option values
UPDATE `civicrm_option_value` SET label = 'Awaiting Information', weight = 2 WHERE name = 'Awaiting Information';
UPDATE `civicrm_option_value` SET weight = 5 WHERE name = 'Paid';
UPDATE `civicrm_option_value` SET weight = 7 WHERE name = 'Withdrawn';
UPDATE `civicrm_option_value` SET label = 'Ineligible', name = 'Ineligible', weight = 6 WHERE name = 'Rejected';
UPDATE `civicrm_option_value` SET label = 'Eligible', name = 'Eligible', weight = 3 WHERE name = 'Approved' OR name = 'Granted';
UPDATE `civicrm_option_value` SET label = 'Submitted', name = 'Submitted'WHERE name = 'Pending';

SELECT @opv1 := id FROM civicrm_option_value WHERE  name = 'Approved' AND option_group_id = @opGId;
SELECT @opv2 := id FROM civicrm_option_value WHERE  name = 'Rejected' AND option_group_id = @opGId;
SELECT @opv3 := id FROM civicrm_option_value WHERE  name = 'Awaiting Information' AND option_group_id = @opGId;
SELECT @opv4 := id FROM civicrm_option_value WHERE  name = 'Withdrawn' AND option_group_id = @opGId;
SELECT @opv5 := id FROM civicrm_option_value WHERE  name = 'Paid' AND option_group_id = @opGId;
SELECT @opv6 := id FROM civicrm_option_value WHERE  name = 'Eligible' AND option_group_id = @opGId;
SELECT @opv7 := id FROM civicrm_option_value WHERE  name = 'Approved for Payment' AND option_group_id = @opGId;
SELECT @opv8 := id FROM civicrm_option_value WHERE  name = 'Ineligible' AND option_group_id = @opGId;
SELECT @opv9 := id FROM civicrm_option_value WHERE  name = 'Granted' AND option_group_id = @opGId;
SELECT @opv10 := id FROM civicrm_option_value WHERE  name = 'Submitted' AND option_group_id = @opGId;

SELECT @gtype := id FROM civicrm_option_group WHERE name = 'grant_type';

INSERT IGNORE INTO `civicrm_option_value` (`id`, `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) 
 VALUES
(@opv7, @opGId, 'Approved for Payment', '7', 'Approved for Payment', NULL, 0, 0, 4, NULL, 0, 1, 1, NULL, NULL, NULL),
(@opv3, @opGId, 'Awaiting Information', 5, 'Awaiting Information', NULL, 0, 0, 2, NULL, 0, 1, 1, NULL, NULL, NULL),
(@opv8, @opGId, 'Ineligible', 3, 'Ineligible', NULL, 0, 0, 4, NULL, 0, 1, 1, NULL, NULL, NULL),
(@opv5, @opGId, 'Paid', 4, 'Paid', NULL, 0, 0, 5, NULL, 0, 1, 1, NULL, NULL, NULL),
(@opv10, @opGId, 'Submitted', 1, 'Submitted', NULL, 0, 0, 1, NULL, 0, 1, 1, NULL, NULL, NULL),
(@opv7, @opGId, 'Withdrawn', 6, 'Withdrawn', NULL, 0, 0, 7, NULL, 0, 1, 1, NULL, NULL, NULL),
('', @gtype, 'NEI Grant', 6, 'NEI Grant', NULL, 0, 0, 7, NULL, 0, 1, 1, NULL, NULL, NULL);

-- reason_grant_ineligible
SET @opGId := '';
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'reason_grant_ineligible';
INSERT IGNORE INTO `civicrm_option_group` (`id`, `name`, `title`, `description`, `is_reserved`, `is_active`) VALUES
(@opGId, 'reason_grant_ineligible', 'Reason Grant Ineligible', NULL, 1, 1);
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'reason_grant_ineligible';

-- option values
SET @opv1 := ''; 
SET @opv2 := '';
SET @opv3 := '';
SET @opv4 := '';
SELECT @opv1 := id FROM civicrm_option_value WHERE  name = 'Outside dates' AND option_group_id = @opGId;
SELECT @opv2 := id FROM civicrm_option_value WHERE  name = 'Ineligible' AND option_group_id = @opGId;
SELECT @opv3 := id FROM civicrm_option_value WHERE  name = 'Information not received in time' AND option_group_id = @opGId;
SELECT @opv4 := id FROM civicrm_option_value WHERE  name = 'Insufficient funds in program' AND option_group_id = @opGId;

INSERT IGNORE INTO `civicrm_option_value` (`id`, `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) 
 VALUES
(@opv1, @opGId, 'Outside dates', '1', 'Outside dates', NULL, 0, 1, 1, NULL, 0, 0, 1, NULL, 1, NULL),
(@opv2, @opGId, 'Ineligible', '2', 'Ineligible', NULL, 0, 2, 1, NULL, 0, 0, 1, NULL, 1, NULL),
(@opv3, @opGId, 'Information not received in time', '3', 'Information not received in time', NULL, 0, 3, 1, NULL, 0, 0, 1, NULL, 1, NULL),
(@opv4, @opGId, 'Insufficient funds in program', '4', 'Insufficient funds in program', NULL, 0, 4, 1, NULL, 0, 0, 1, NULL, 1, NULL);

-- Reason Grant Incomplete
SET @opGId := '';
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'reason_grant_incomplete';
INSERT IGNORE INTO `civicrm_option_group` (`id`, `name`, `title`, `description`, `is_reserved`, `is_active`) VALUES
(@opGId, 'reason_grant_incomplete', 'Reason Grant Incomplete', NULL, 1, 1);
SELECT @opGId := id FROM civicrm_option_group WHERE name = 'reason_grant_incomplete';

-- option values
SET @opv1 := ''; 
SET @opv2 := '';
SET @opv3 := '';
SET @opv4 := '';
SELECT @opv1 := id FROM civicrm_option_value WHERE  name = 'No Receipts' AND option_group_id = @opGId;
SELECT @opv2 := id FROM civicrm_option_value WHERE  name = 'Inadequate Receipts' AND option_group_id = @opGId;
SELECT @opv3 := id FROM civicrm_option_value WHERE  name = 'No Proof of completion' AND option_group_id = @opGId;
SELECT @opv4 := id FROM civicrm_option_value WHERE  name = 'Inadaquate Proof of completion' AND option_group_id = @opGId;

INSERT IGNORE INTO `civicrm_option_value` (`id`, `option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) 
 VALUES
(@opv1, @opGId, 'No Receipts', '1', 'No Receipts', NULL, 0, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
(@opv2, @opGId, 'Inadequate Receipts', '2', 'Inadequate Receipts', NULL, 0, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@opv3, @opGId, 'No Proof of completion', '3', 'No Proof of completion', NULL, 0, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
(@opv4, @opGId, 'Inadaquate Proof of completion', '4', 'Inadaquate Proof of completion', NULL, 0, 0, 4, NULL, 0, 0, 1, NULL, NULL, NULL);

-- insert navigation links

SELECT @parentId1 := id FROM civicrm_navigation WHERE name = 'CiviGrant';
SELECT @parentId2 := id FROM civicrm_navigation WHERE name = 'Grants';
SELECT @weight := MAX(weight) FROM civicrm_navigation WHERE  parent_id = @parentId2;       
INSERT INTO `civicrm_navigation` (`domain_id`, `label`, `name`, `url`, `permission`, `permission_operator`, `parent_id`, `is_active`, `has_separator`, `weight`) VALUES
(1, 'Find Grant Payments', 'Find Grant Payments', 'civicrm/grant/payment/search&reset=1', 'access CiviGrant', 'AND', @parentId2, 1, 1, @weight = @weight + 1),
(1, 'New Grant Program', 'New Grant Program', 'civicrm/grant_program?action=add&reset=1', 'access CiviCRM,access CiviGrant,edit grants', 'AND', @parentId2, 1, 0, @weight = @weight + 1),
(1, 'Grant Programs', 'Grant Programs', 'civicrm/grant_program&reset=1', 'access CiviGrant,administer CiviCRM', 'AND', @parentId1, 1, NULL, 2);


-- Accounting integration RG-125
SELECT @contactId := contact_id FROM civicrm_domain WHERE  id = 1;

SELECT @option_group_id_arel := max(id) from civicrm_option_group where name = 'account_relationship';

SELECT @weight := max(weight) from civicrm_option_value where option_group_id = @option_group_id_arel; 
SET @weight := @weight + 1;


SELECT @option_value_rel_id_exp  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Expense Account is';
SELECT @option_value_rel_id_as  := value FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Asset Account is';

INSERT INTO
   `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `visibility_id`)
VALUES
    (@option_group_id_arel, 'Accounts Payable Account is', @weight, 'Accounts Payable Account is', NULL, 0, 0, @weight, 'Accounts Payable Account is', 0, 1, 1, 2, NULL);

SELECT @financialAccount := id FROM civicrm_financial_account WHERE  name = 'NEI Grant';
SELECT @depositAccount := id FROM civicrm_financial_account WHERE  name = 'Deposit Bank Account';
SELECT @accountPayable := id FROM civicrm_financial_account WHERE  name = 'Accounts Payable';

INSERT IGNORE INTO civicrm_financial_account (id, name, contact_id, is_header_account, financial_account_type_id, accounting_code, account_type_code, is_active) 
VAlUES (@financialAccount, 'NEI Grant', @contactId, 0, 5, 5555, 'EXP', 1);
SET @financialAccountID := LAST_INSERT_ID();

INSERT INTO civicrm_financial_type (name, is_deductible, is_reserved, is_active)
VALUES ('NEI Grant', 0, 0, 1);
SET @financialTypeID := LAST_INSERT_ID();

INSERT INTO civicrm_entity_financial_account (entity_table, entity_id, account_relationship, financial_account_id) 
-- Expense account
VALUES('civicrm_financial_type', @financialTypeID, @option_value_rel_id_exp, IFNULL(@financialAccount, @financialAccountID)),
-- Asset Account of
('civicrm_financial_type', @financialTypeID, @option_value_rel_id_as, @depositAccount),
-- Account Payable
('civicrm_financial_type', @financialTypeID, @weight, @accountPayable);

