/**
 * Grant Programs extension improves grant allocation
 * in CiviGrant 
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

-- Accounting integration
SELECT @option_group_id_arel := max(id) from civicrm_option_group where name = 'account_relationship';

DELETE FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Accounts Payable Account is';

DELETE ci, ceft, ceft1, cft FROM `civicrm_entity_financial_trxn` ceft
LEFT JOIN civicrm_financial_trxn  cft ON cft.id = ceft.financial_trxn_id 
LEFT JOIN civicrm_entity_financial_trxn ceft1 ON cft.id = ceft1.financial_trxn_id  AND ceft1.entity_table = 'civicrm_financial_item'
LEFT JOIN civicrm_financial_item ci ON ci.id = ceft1.entity_id
WHERE ceft.entity_table = 'civicrm_grant';

-- RG-149
DELETE cg, cv FROM civicrm_option_group cg
INNER JOIN civicrm_option_value cv ON cg.id = cv.option_group_id
WHERE cg.name = 'grant_info_too_late';

-- RG-181
DROP TABLE IF EXISTS civicrm_entity_payment;
DROP TABLE IF EXISTS civicrm_payment;

ALTER TABLE civicrm_grant_program DROP FOREIGN KEY FK_civicrm_grant_program_status_id, DROP INDEX FK_civicrm_grant_program_status_id;
ALTER TABLE civicrm_grant_program DROP FOREIGN KEY FK_civicrm_grant_program_grant_type_id, DROP INDEX FK_civicrm_grant_program_grant_type_id;
 
ALTER TABLE civicrm_grant DROP FOREIGN KEY FK_civicrm_grant_grant_program_id, DROP INDEX FK_civicrm_grant_grant_program_id;

ALTER TABLE `civicrm_grant` DROP `grant_program_id`, DROP `grant_rejected_reason_id`, DROP `assessment`;

TRUNCATE TABLE civicrm_grant;

DROP TABLE IF EXISTS civicrm_grant_program;

DELETE FROM civicrm_option_group WHERE name = 'grant_payment_status';

DELETE FROM civicrm_option_group WHERE name = 'grant_program_status';

DELETE FROM civicrm_option_group WHERE name = 'allocation_algorithm';

DELETE FROM civicrm_option_group WHERE name = 'grant_thresholds';

DELETE FROM civicrm_option_group WHERE name = 'reason_grant_ineligible';

DELETE FROM civicrm_option_group WHERE name = 'reason_grant_incomplete';

DELETE FROM civicrm_option_group WHERE name = 'msg_tpl_workflow_grant';

SELECT @grantStatus := id FROM  civicrm_option_group WHERE name = 'grant_status';

UPDATE civicrm_option_value SET label = 'Approved', name = 'Approved', weight = 2 WHERE option_group_id = @grantStatus AND label = 'Eligible';
UPDATE civicrm_option_value SET label = 'Rejected', name = 'Rejected', weight = 3 WHERE option_group_id = @grantStatus AND label = 'Ineligible';
UPDATE civicrm_option_value SET weight = 4 WHERE option_group_id = @grantStatus AND label = 'Paid';
UPDATE civicrm_option_value SET weight = 5 WHERE option_group_id = @grantStatus AND label = 'Awaiting Information';
UPDATE civicrm_option_value SET weight = 6 WHERE option_group_id = @grantStatus AND label = 'Withdrawn';

DELETE FROM civicrm_option_value WHERE label = 'Approved for Payment' AND option_group_id = @grantStatus;

SELECT @grantType := id FROM  civicrm_option_group WHERE name = 'grant_type';
DELETE FROM civicrm_option_value WHERE label = 'NEI Grant' AND option_group_id = @grantType;


SELECT @parentId1 := id FROM civicrm_navigation WHERE name = 'CiviGrant';
SELECT @parentId2 := id FROM civicrm_navigation WHERE name = 'Grants';

DELETE FROM civicrm_navigation WHERE parent_id = @parentId2 AND label = 'Find Grant Payments' AND name = 'Find Grant Payments' AND url = 'civicrm/grant/payment/search&reset=1';
DELETE FROM civicrm_navigation WHERE parent_id = @parentId2 AND label = 'New Grant Program' AND name = 'New Grant Program' AND url = 'civicrm/grant_program?action=add&reset=1';
DELETE FROM civicrm_navigation WHERE parent_id = @parentId1 AND label = 'Grant Programs' AND name = 'Grant Programs' AND url = 'civicrm/grant_program&reset=1';

-- custom data
DELETE FROM civicrm_custom_group WHERE name = 'NEI_Employment_Information';
DELETE FROM civicrm_custom_group WHERE name = 'NEI_General_information';
DELETE FROM civicrm_custom_group WHERE name = 'NEI_Course_conference_details';
DELETE FROM civicrm_custom_group WHERE name = 'NEI_ID';

DELETE FROM civicrm_option_group WHERE name = 'predominant_clinical_area_of_pra_nei';
DELETE FROM civicrm_option_group WHERE name = 'nei_employment_status_nei';
DELETE FROM civicrm_option_group WHERE name = 'if_you_are_not_employed_indicate_nei';
DELETE FROM civicrm_option_group WHERE name = 'province_of_employment_nei';
DELETE FROM civicrm_option_group WHERE name = 'position_nei';
DELETE FROM civicrm_option_group WHERE name = 'employment_setting_nei';
DELETE FROM civicrm_option_group WHERE name = 'how_did_you_hear_about_this_init_nei';
DELETE FROM civicrm_option_group WHERE name = 'course_conference_type_nei';
DELETE FROM civicrm_option_group WHERE name = 'how_will_this_course_enhance_the_nei';
DELETE FROM civicrm_option_group WHERE name = 'type_of_course_provider_nei';

DROP TABLE IF EXISTS civicrm_value_nei_employment_information;
DROP TABLE IF EXISTS civicrm_value_nei_general_information;
DROP TABLE IF EXISTS civicrm_value_nei_course_conference_details;
DROP TABLE IF EXISTS civicrm_value_nei_id;

DELETE FROM civicrm_msg_template WHERE msg_title = 'Trial Allocation of Funds';
DELETE FROM civicrm_msg_template WHERE msg_title = 'Grants Eligible Receipt';
DELETE FROM civicrm_msg_template WHERE msg_title = 'Grants Awaiting Information Receipt';
DELETE FROM civicrm_msg_template WHERE msg_title = 'Grants Ineligible Receipt';
DELETE FROM civicrm_msg_template WHERE msg_title = 'Grants Paid Receipt';
DELETE FROM civicrm_msg_template WHERE msg_title = 'Grants Approved for Payment Receipt';
DELETE FROM civicrm_msg_template WHERE msg_title = 'Grants Submitted Receipt';
DELETE FROM civicrm_msg_template WHERE msg_title = 'Grants Withdrawn Receipt';
DELETE FROM civicrm_msg_template WHERE msg_title = 'Grant Payment Check';
DELETE FROM civicrm_msg_template WHERE msg_title = 'Grant Payment Report';

SELECT @financialType := id FROM civicrm_financial_type WHERE name = 'NEI Grant';

DELETE FROM civicrm_entity_financial_account WHERE entity_table = 'civicrm_financial_type' AND entity_id = @financialType;
DELETE FROM civicrm_financial_account WHERE name = 'NEI Grant';
DELETE FROM civicrm_financial_type WHERE name = 'NEI Grant';

ALTER table civicrm_grant DROP column grant_incomplete_reason_id;

DELETE FROM civicrm_extension WHERE full_name = 'biz.jmaconsulting.grantprograms';
