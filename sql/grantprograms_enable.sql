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

UPDATE civicrm_option_group SET is_active = 1 WHERE name IN ('grant_payment_status','grant_program_status', 'allocation_algorithm', 'grant_thresholds', 'reason_grant_ineligible', 'reason_grant_incomplete', 'grant_info_too_late', 'msg_tpl_workflow_grant', 'predominant_clinical_area_of_pra_nei', 'nei_employment_status_nei', 'if_you_are_not_employed_indicate_nei', 'province_of_employment_nei', 'position_nei', 'employment_setting_nei', 'how_did_you_hear_about_this_init_nei', 'course_conference_type_nei', 'how_will_this_course_enhance_the_nei', 'type_of_course_provider_nei');

UPDATE civicrm_option_value SET is_active = 1 WHERE option_group_id IN (SELECT id FROM civicrm_option_group WHERE name IN ('grant_payment_status','grant_program_status', 'allocation_algorithm', 'grant_thresholds', 'reason_grant_ineligible', 'reason_grant_incomplete', 'grant_info_too_late', 'msg_tpl_workflow_grant', 'predominant_clinical_area_of_pra_nei', 'nei_employment_status_nei', 'if_you_are_not_employed_indicate_nei', 'province_of_employment_nei', 'position_nei', 'employment_setting_nei', 'how_did_you_hear_about_this_init_nei', 'course_conference_type_nei', 'how_will_this_course_enhance_the_nei', 'type_of_course_provider_nei'));


UPDATE civicrm_custom_group SET is_active = 1 WHERE name IN ('NEI_Employment_Information','NEI_General_information', 'NEI_Course_conference_details', 'NEI_ID');

UPDATE civicrm_custom_field SET is_active = 1 WHERE custom_group_id IN (SELECT id FROM civicrm_custom_group WHERE name IN ('NEI_General_information', 'NEI_Course_conference_details', 'NEI_ID', 'NEI_Employment_Information'));

SELECT @gtype := id FROM civicrm_option_group WHERE name = 'grant_type';
UPDATE civicrm_option_value SET is_active = 1 WHERE name = 'NEI Grant' AND option_group_id = @gtype;

UPDATE civicrm_financial_account SET is_active = 1 WHERE  name = 'NEI Grant';
UPDATE civicrm_financial_type SET is_active = 1 WHERE  name = 'NEI Grant';

