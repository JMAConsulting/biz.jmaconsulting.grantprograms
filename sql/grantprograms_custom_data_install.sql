-- Create custom groups
SELECT @EInfoGId := id FROM civicrm_custom_group WHERE name = 'NEI_Employment_Information';
SELECT @GInfoGId := id FROM civicrm_custom_group WHERE name = 'NEI_General_information';
SELECT @CInfoGId := id FROM civicrm_custom_group WHERE name = 'NEI_Course_conference_details';
SELECT @CInfoNIId := id FROM civicrm_custom_group WHERE name = 'NEI_ID';

INSERT IGNORE INTO `civicrm_custom_group` (`id`, `name`, `title`, `extends`, `extends_entity_column_id`, `extends_entity_column_value`, `style`, `collapse_display`, `help_pre`, `help_post`, `weight`, `is_active`, `table_name`, `is_multiple`, `min_multiple`, `max_multiple`, `collapse_adv_display`, `created_id`, `created_date`) VALUES
(@EInfoGId, 'NEI_Employment_Information', 'NEI Employment Information', 'Grant', NULL, NULL, 'Inline', 1, '', '', 7, 1, 'civicrm_value_nei_employment_information', 0, NULL, NULL, 0, NULL, Now()),
(@GInfoGId, 'NEI_General_information', 'NEI General information', 'Grant', NULL, NULL, 'Inline', 1, '', '', 8, 1, 'civicrm_value_nei_general_information', 0, NULL, NULL, 0, NULL, Now()),
(@CInfoGId, 'NEI_Course_conference_details', 'NEI Course/conference details', 'Grant', NULL, NULL, 'Inline', 1, '', '', 9, 1, 'civicrm_value_nei_course_conference_details', 0, NULL, NULL, 0, NULL, Now()),
(@CInfoNIId, 'NEI_ID', 'NEI ID', 'Individual', NULL, NULL, 'Inline', 1, '', '', 8, 1, 'civicrm_value_nei_id', 0, NULL, NULL, 0, NULL, Now());

SELECT @EInfoGId := id FROM civicrm_custom_group WHERE name = 'NEI_Employment_Information';
SELECT @GInfoGId := id FROM civicrm_custom_group WHERE name = 'NEI_General_information';
SELECT @CInfoGId := id FROM civicrm_custom_group WHERE name = 'NEI_Course_conference_details';
SELECT @CInfoNIId := id FROM civicrm_custom_group WHERE name = 'NEI_ID';

-- Create option groups
SELECT @OGId1 := id FROM civicrm_option_group WHERE name = 'predominant_clinical_area_of_pra_nei';
SELECT @OGId2 := id FROM civicrm_option_group WHERE name = 'nei_employment_status_nei';
SELECT @OGId3 := id FROM civicrm_option_group WHERE name = 'if_you_are_not_employed_indicate_nei';
SELECT @OGId4 := id FROM civicrm_option_group WHERE name = 'province_of_employment_nei';
SELECT @OGId5 := id FROM civicrm_option_group WHERE name = 'position_nei';
SELECT @OGId6 := id FROM civicrm_option_group WHERE name = 'employment_setting_nei';
SELECT @OGId7 := id FROM civicrm_option_group WHERE name = 'how_did_you_hear_about_this_init_nei';
SELECT @OGId8 := id FROM civicrm_option_group WHERE name = 'course_conference_type_nei';
SELECT @OGId9 := id FROM civicrm_option_group WHERE name = 'how_will_this_course_enhance_the_nei';
SELECT @OGId10 := id FROM civicrm_option_group WHERE name = 'type_of_course_provider_nei';

INSERT INTO `civicrm_option_group` (`id`, `name`, `title`, `description`, `is_reserved`, `is_active`) VALUES
(@OGId1, 'predominant_clinical_area_of_pra_nei', 'Predominant clinical area of practice', NULL, 1, 1),
(@OGId2, 'nei_employment_status_nei', 'NEI Employment status', NULL, 1, 1),
(@OGId3, 'if_you_are_not_employed_indicate_nei', 'If you are NOT EMPLOYED, indicate how you are actively seeking employment', NULL, 1, 1),
(@OGId4, 'province_of_employment_nei', 'Province of employment', NULL, 1, 1),
(@OGId5, 'position_nei', 'Position', NULL, 1, 1),
(@OGId6, 'employment_setting_nei', 'Employment setting', NULL, 1, 1),
(@OGId7, 'how_did_you_hear_about_this_init_nei', 'How did you hear about this initiative?', NULL, 1, 1),
(@OGId8, 'course_conference_type_nei', 'Course/conference type', NULL, 1, 1),
(@OGId9, 'how_will_this_course_enhance_the_nei', 'How will this course enhance the nursing care you provide in Ontario?', NULL, 1, 1),
(@OGId10, 'type_of_course_provider_nei', 'Type of Course Provider', NULL, 1, 1);

SELECT @OGId1 := id FROM civicrm_option_group WHERE name = 'predominant_clinical_area_of_pra_nei';
SELECT @OGId2 := id FROM civicrm_option_group WHERE name = 'nei_employment_status_nei';
SELECT @OGId3 := id FROM civicrm_option_group WHERE name = 'if_you_are_not_employed_indicate_nei';
SELECT @OGId4 := id FROM civicrm_option_group WHERE name = 'province_of_employment_nei';
SELECT @OGId5 := id FROM civicrm_option_group WHERE name = 'position_nei';
SELECT @OGId6 := id FROM civicrm_option_group WHERE name = 'employment_setting_nei';
SELECT @OGId7 := id FROM civicrm_option_group WHERE name = 'how_did_you_hear_about_this_init_nei';
SELECT @OGId8 := id FROM civicrm_option_group WHERE name = 'course_conference_type_nei';
SELECT @OGId9 := id FROM civicrm_option_group WHERE name = 'how_will_this_course_enhance_the_nei';
SELECT @OGId10 := id FROM civicrm_option_group WHERE name = 'type_of_course_provider_nei';

-- Create custom fields for NEI Employment Information
INSERT INTO `civicrm_custom_field` (`custom_group_id`, `name`, `label`, `data_type`, `html_type`, `default_value`, `is_required`, `is_searchable`, `is_search_range`, `weight`, `help_pre`, `help_post`, `mask`, `attributes`, `javascript`, `is_active`, `is_view`, `options_per_line`, `text_length`, `start_date_years`, `end_date_years`, `date_format`, `time_format`, `note_columns`, `note_rows`, `column_name`, `option_group_id`, `filter`) VALUES
(@EInfoGId, 'NEI_Predominant_clinical_area_of_practice', 'Predominant clinical area of practice', 'String', 'Select', NULL, 0, 0, 0, 40, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'predominant_clinical_area_of_pra', @OGId1, NULL),
(@EInfoGId, 'NEI_Employment_status', 'NEI Employment status', 'String', 'Select', NULL, 0, 0, 0, 56, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'nei_employment_status', @OGId2, NULL),
(@EInfoGId, 'NEI_If_you_are_NOT_EMPLOYED_indicate_how_you_are_', 'If you are NOT EMPLOYED, indicate how you are actively seeking employment', 'String', 'Select', NULL, 0, 0, 0, 65, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'if_you_are_not_employed_indicate', @OGId3, NULL),
(@EInfoGId, 'NEI_Other', 'Other', 'String', 'Text', NULL, 0, 0, 0, 71, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'other', NULL, NULL),
(@EInfoGId, 'NEI_Employer_name', 'Employer name', 'String', 'Text', NULL, 0, 0, 0, 77, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'employer_name', NULL, NULL),
(@EInfoGId, 'NEI_Province_of_employment', 'Province of employment', 'String', 'Select', NULL, 0, 0, 0, 82, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'province_of_employment', @OGId4, NULL),
(@EInfoGId, 'NEI_Position', 'Position', 'String', 'Select', NULL, 0, 0, 0, 88, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'position', @OGId5, NULL),
(@EInfoGId, 'NEI_select_or_other', 'Other position', 'String', 'Text', NULL, 0, 0, 0, 95, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'select_or_other', NULL, NULL),
(@EInfoGId, 'NEI_Employment_setting', 'Employment setting', 'String', 'Select', NULL, 0, 0, 0, 103, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'employment_setting', @OGId6, NULL),
(@EInfoGId, 'NEI_Employment_setting_other', 'Employment setting other', 'String', 'Text', NULL, 0, 0, 0, 110, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'employment_setting_other', NULL, NULL),
(@EInfoGId, 'NEI_Work_phone', 'Work phone', 'String', 'Text', NULL, 0, 0, 0, 117, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'work_phone', NULL, NULL),
(@EInfoGId, 'NEI_Work_phone_extension', 'Work phone extension', 'String', 'Text', NULL, 0, 0, 0, 136, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'work_phone_extension', NULL, NULL);

-- Create custom fields for NEI General information
INSERT INTO `civicrm_custom_field` (`custom_group_id`, `name`, `label`, `data_type`, `html_type`, `default_value`, `is_required`, `is_searchable`, `is_search_range`, `weight`, `help_pre`, `help_post`, `mask`, `attributes`, `javascript`, `is_active`, `is_view`, `options_per_line`, `text_length`, `start_date_years`, `end_date_years`, `date_format`, `time_format`, `note_columns`, `note_rows`, `column_name`, `option_group_id`, `filter`) VALUES
(@GInfoGId, 'NEI_How_did_you_hear_about_this_initiative_', 'How did you hear about this initiative?', 'String', 'Select', NULL, 0, 0, 0, 44, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'how_did_you_hear_about_this_init', @OGId7, NULL),
(@GInfoGId, 'NEI_Other_initiative', 'Other initiative', 'String', 'Text', NULL, 0, 0, 0, 58, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'other_initiative', NULL, NULL);

-- Create custom fields for NEI Course/conference details
INSERT INTO `civicrm_custom_field` (`custom_group_id`, `name`, `label`, `data_type`, `html_type`, `default_value`, `is_required`, `is_searchable`, `is_search_range`, `weight`, `help_pre`, `help_post`, `mask`, `attributes`, `javascript`, `is_active`, `is_view`, `options_per_line`, `text_length`, `start_date_years`, `end_date_years`, `date_format`, `time_format`, `note_columns`, `note_rows`, `column_name`, `option_group_id`, `filter`) VALUES
(@CInfoGId, 'NEI_Course_conference_type', 'Course/conference type', 'String', 'Select', NULL, 0, 0, 0, 46, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'course_conference_type', @OGId8, NULL),
(@CInfoGId, 'NEI_Course_conference_type_other', 'Course/conference type other', 'String', 'Text', NULL, 0, 0, 0, 60, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'course_conference_type_other', NULL, NULL),
(@CInfoGId, 'NEI_Course_conference_code', 'Course/conference code', 'String', 'Text', NULL, 0, 0, 0, 67, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'course_conference_code', NULL, NULL),
(@CInfoGId, 'NEI_Course_conference_name', 'Course/conference name', 'String', 'Text', NULL, 0, 0, 0, 72, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'course_conference_name', NULL, NULL),
(@CInfoGId, 'NEI_Course_conference_provider', 'Course/conference provider', 'String', 'Text', NULL, 0, 0, 0, 79, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'course_conference_provider', NULL, NULL),
(@CInfoGId, 'NEI_How_will_this_course_enhance_the_nursing_care', 'How will this course enhance the nursing care you provide in Ontario?', 'String', 'Select', NULL, 0, 0, 0, 83, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'how_will_this_course_enhance_the', @OGId9, NULL),
(@CInfoGId, 'NEI_Proof_of_completion', 'Proof of completion', 'File', 'File', NULL, 0, 0, 0, 90, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'proof_of_completion', NULL, NULL),
(@CInfoGId, 'NEI_Proof_of_payment', 'Proof of payment', 'File', 'File', NULL, 0, 0, 0, 98, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'proof_of_payment', NULL, NULL),
(@CInfoGId, 'NEI_Type_of_Course_Provider', 'Type of Course Provider', 'String', 'Select', NULL, 0, 0, 0, 128, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'type_of_course_provider', @OGId10, NULL),
(@CInfoGId, 'NEI_Start_Date', 'Start Date', 'Date', 'Select Date', NULL, 0, 0, 0, 118, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, 3, 0, 'yy-mm-dd', NULL, 60, 4, 'start_date', NULL, NULL),
(@CInfoGId, 'NEI_End_Date', 'End Date', 'Date', 'Select Date', NULL, 0, 0, 0, 127, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, 3, 0, 'yy-mm-dd', NULL, 60, 4, 'end_date', NULL, NULL);

-- Create custom fields for NEI ID
INSERT INTO `civicrm_custom_field` (`custom_group_id`, `name`, `label`, `data_type`, `html_type`, `default_value`, `is_required`, `is_searchable`, `is_search_range`, `weight`, `help_pre`, `help_post`, `mask`, `attributes`, `javascript`, `is_active`, `is_view`, `options_per_line`, `text_length`, `start_date_years`, `end_date_years`, `date_format`, `time_format`, `note_columns`, `note_rows`, `column_name`, `option_group_id`, `filter`) VALUES
(@CInfoNIId, 'NEI_College_of_Nurses_of_Ontario_Registration_Number', 'College of Nurses of Ontario Registration Number', 'String', 'Text', NULL, 0, 0, 0, 14, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'college_of_nurses_of_ontario_reg', NULL, NULL),
(@CInfoNIId, 'NEI_Social_Insurance_Number', 'Social Insurance Number', 'String', 'Text', NULL, 0, 0, 0, 17, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 255, NULL, NULL, NULL, NULL, 60, 4, 'social_insurance_number', NULL, NULL);


-- Create option values
INSERT INTO `civicrm_option_value` (`option_group_id`, `label`, `value`, `name`, `grouping`, `filter`, `is_default`, `weight`, `description`, `is_optgroup`, `is_reserved`, `is_active`, `component_id`, `domain_id`, `visibility_id`) VALUES
(@OGId1, 'Administration', '50', 'Administration', NULL, NULL, 0, 1, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Cardiac Care', '37', 'Cardiac_Care', NULL, NULL, 0, 2, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Complex Continuing Care', '51', 'Complex_Continuing_Care', NULL, NULL, 0, 3, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Critical Care', '38', 'Critical_Care', NULL, NULL, 0, 4, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Dialysis', '39', 'Dialysis', NULL, NULL, 0, 5, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Emergency', '35', 'Emergency', NULL, NULL, 0, 6, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Gerentology', '47', 'Gerentology', NULL, NULL, 0, 7, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Home Care/Community Care', '40', 'Home_Care_Community_Care', NULL, NULL, 0, 8, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Med/Surg', '52', 'Med_Surg', NULL, NULL, 0, 9, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Oncology/Cancer Care', '44', 'Oncology_Cancer_Care', NULL, NULL, 0, 10, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Operating Room', '43', 'Operating_Room', NULL, NULL, 0, 11, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Palliative Care', '42', 'Palliative_Care', NULL, NULL, 0, 12, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Rehabilitation', '45', 'Rehabilitation', NULL, NULL, 0, 13, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Other', 'select_or_other', 'Other', NULL, NULL, 0, 14, '6', 0, 0, 1, NULL, NULL, NULL),
(@OGId2, 'Unemployed (Seeking Employment)', '21', 'Unemployed_Seeking_Employment_', NULL, NULL, 0, 1, '11', 0, 0, 1, NULL, NULL, NULL),
(@OGId2, 'Full Time Student', '30', 'Full_Time_Student', NULL, NULL, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId2, 'Agency casual by choice', '33', 'Agency_casual_by_choice', NULL, NULL, 0, 3, '4', 0, 0, 1, NULL, NULL, NULL),
(@OGId2, 'Casual by Employer', '35', 'Casual_by_Employer', NULL, NULL, 0, 4, '14', 0, 0, 1, NULL, NULL, NULL),
(@OGId2, 'Full Time', '32', 'Full_Time', NULL, NULL, 0, 5, '20', 0, 0, 1, NULL, NULL, NULL),
(@OGId2, 'Part Time', '31', 'Part_Time', NULL, NULL, 0, 6, '16', 0, 0, 1, NULL, NULL, NULL),
(@OGId2, 'Foreign Educated/Refresher', '34', 'Foreign_Educated_Refresher', NULL, NULL, 0, 7, '4', 0, 0, 1, NULL, NULL, NULL),
(@OGId3, 'Contacts/Interviews', '1', 'Contacts_Interviews', NULL, NULL, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId3, 'Pursuing Education in the area you wish to be employed', '2', 'Pursuing_Education_in_the_area_', NULL, NULL, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId3, 'Accessed RNAO/RPNAO Counselling Service', '3', 'Accessed_RNAO_RPNAO_Counselling', NULL, NULL, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId3, 'Other Counselling Service', '4', 'Other_Counselling_Service', NULL, NULL, 0, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Alberta', 'AB', 'Alberta', NULL, NULL, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'British Columbia', 'BC', 'British_Columbia', NULL, NULL, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Manitoba', 'MB', 'Manitoba', NULL, NULL, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'New Brunswick', 'NB', 'New_Brunswick', NULL, NULL, 0, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Newfoundland', 'NL', 'Newfoundland', NULL, NULL, 0, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Northwest Territories', 'NT', 'Northwest_Territories', NULL, NULL, 0, 6, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Nova Scotia', 'NS', 'Nova_Scotia', NULL, NULL, 0, 7, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Nunavut', 'NU', 'Nunavut', NULL, NULL, 0, 8, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Ontario', 'ON', 'Ontario', NULL, NULL, 0, 9, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Prince Edward Island', 'PE', 'Prince_Edward_Island', NULL, NULL, 0, 10, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Quebec', 'QC', 'Quebec', NULL, NULL, 0, 11, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Saskatchewan', 'SK', 'Saskatchewan', NULL, NULL, 0, 12, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId4, 'Yukon Territory', 'YT', 'Yukon_Territory', NULL, NULL, 0, 13, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId5, 'Staff Nurse', '1', 'Staff_Nurse', NULL, NULL, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId5, 'Charge Nurse', '2', 'Charge_Nurse', NULL, NULL, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId5, 'Visiting Nurse', '3', 'Visiting_Nurse', NULL, NULL, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId5, 'Educator', '4', 'Educator', NULL, NULL, 0, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId5, 'Administrative Position', '5', 'Administrative_Position', NULL, NULL, 0, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId5, 'Other', 'select_or_other', 'Other', NULL, NULL, 0, 6, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId6, 'Community', '16', 'Community', NULL, NULL, 0, 1, '24', 0, 0, 1, NULL, NULL, NULL),
(@OGId6, 'Hospital', '12', 'Hospital', NULL, NULL, 0, 2, '22', 0, 0, 1, NULL, NULL, NULL),
(@OGId6, 'Public Health', '17', 'Public_Health', NULL, NULL, 0, 3, '24', 0, 0, 1, NULL, NULL, NULL),
(@OGId6, 'Long term care', '13', 'Long_term_care', NULL, NULL, 0, 4, '24', 0, 0, 1, NULL, NULL, NULL),
(@OGId6, 'Other', 'select_or_other', 'Other', NULL, NULL, 0, 5, '0', 0, 0, 1, NULL, NULL, NULL),
(@OGId7, 'Direct Mail', '1', 'Direct_Mail', NULL, NULL, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId7, 'Employer', '2', 'Employer', NULL, NULL, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId7, 'Web Site', '3', 'Web_Site', NULL, NULL, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId7, 'Publication', '4', 'Publication', NULL, NULL, 0, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId7, 'Other', 'select_or_other', 'Other', NULL, NULL, 0, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'Clinical/Specialty', '17', 'Clinical_Specialty', NULL, NULL, 0, 1, '15', 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'RPN Cert.-Diploma', '20', 'RPN_Cert_Diploma', NULL, NULL, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'BScN', '18', 'BScN', NULL, NULL, 0, 3, '10', 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'MScN/PhD/Thesis', '21', 'MScN_PhD_Thesis', NULL, NULL, 0, 4, '5', 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'Conference/Workshop', '26', 'Conference_Workshop', NULL, NULL, 0, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'Other', 'select_or_other', 'Other', NULL, NULL, 0, 6, '0', 0, 0, 1, NULL, NULL, NULL),
(@OGId9, 'Improves my quality of care.', '1', 'Improves_my_quality_of_care_', NULL, NULL, 0, 1, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId9, 'Increases my specialty professional skills.', '2', 'Increases_my_specialty_professi', NULL, NULL, 0, 2, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId9, 'Improves my professional knowledge.', '3', 'Improves_my_professional_knowle', NULL, NULL, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId9, 'Increases my ability to participate in agency policy and decision making.', '4', 'Increases_my_ability_to_partici', NULL, NULL, 0, 4, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId9, 'Enhances my ability to move into another clinical area.', '5', 'Enhances_my_ability_to_move_int', NULL, NULL, 0, 5, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId9, 'Enhances my ability to fill an available nursing position.', '6', 'Enhances_my_ability_to_fill_an_', NULL, NULL, 0, 6, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId10, 'University', '28', 'University', NULL, NULL, 0, 1, '17', 0, 0, 1, NULL, NULL, NULL),
(@OGId10, 'Other Provincially Recognized', '29', 'Other_Provincially_Recognized', NULL, NULL, 0, 2, '9', 0, 0, 1, NULL, NULL, NULL),
(@OGId10, 'Other Non-provincially Recognized', '30', 'Other_Non_provincially_Recogniz', NULL, NULL, 0, 3, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId10, 'Community College', '31', 'Community_College', NULL, NULL, 0, 4, '17', 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'Computer Courses', '25', 'Computer_Courses', NULL, NULL, 0, 7, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'General Interest', '6', 'General_Interest', NULL, NULL, 0, 8, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'MN', '22', 'MN', NULL, NULL, 0, 9, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'Non-Clinical', '24', 'Non_Clinical', NULL, NULL, 0, 10, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'RN Refresher', '19', 'RN_Refresher', NULL, NULL, 0, 11, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId8, 'Specialty Non-clinical', '1', 'Specialty_Non_clinical', NULL, NULL, 0, 12, NULL, 0, 0, 1, NULL, NULL, NULL),
(@OGId10, 'Other Recognized for cycle 3', '32', 'Other_Recognized_for_cycle_3', NULL, NULL, 0, 5, '1', 0, 0, 1, NULL, NULL, NULL),
(@OGId1, 'Educator', '48', 'Educator', NULL, NULL, 0, 15, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId1, 'Long Term Care', '41', 'Long_Term_Care', NULL, NULL, 0, 16, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId1, 'Mental Health', '34', 'Mental_Health', NULL, NULL, 0, 17, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId1, 'Neonatal care', '36', 'Neonatal_care', NULL, NULL, 0, 18, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId1, 'Public Health', '46', 'Public_Health', NULL, NULL, 0, 19, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId2, 'Care Nurse', '29', 'Care_Nurse', NULL, NULL, 0, 8, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId2, 'Irregularly/Casual:As chosen by the Employer', '24', 'Irregularly_Casual_As_chosen_by', NULL, NULL, 0, 9, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId2, 'Irregularly/Casual:As chosen by the Individual', '23', 'Irregularly_Casual_As_chosen_by', NULL, NULL, 0, 10, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId2, 'Permanent Full Time', '1', 'Permanent_Full_Time', NULL, NULL, 0, 11, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId2, 'Permanent Part Time', '2', 'Permanent_Part_Time', NULL, NULL, 0, 12, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId2, 'Temporary Full Time', '27', 'Temporary_Full_Time', NULL, NULL, 0, 13, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId2, 'Temporary Part Time', '28', 'Temporary_Part_Time', NULL, NULL, 0, 14, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId2, 'Unemployed (Not seeking work)', '22', 'Unemployed__Not_seeking_work_', NULL, NULL, 0, 15, NULL, 0, 0, 0, NULL, NULL, NULL),
(@OGId6, 'Other non qualifying', '15', 'Other_non_qualifying', NULL, NULL, 0, 6, NULL, 0, 0, 0, NULL, NULL, NULL);

-- Create table for NEI Employment Information
CREATE TABLE IF NOT EXISTS `civicrm_value_nei_employment_information` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `predominant_clinical_area_of_pra` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nei_employment_status` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `if_you_are_not_employed_indicate` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `other` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `employer_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `province_of_employment` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `position` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `select_or_other` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `employment_setting` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `employment_setting_other` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `work_phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `work_phone_extension` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=58 ;

-- Create table for NEI General information
CREATE TABLE IF NOT EXISTS `civicrm_value_nei_general_information` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `how_did_you_hear_about_this_init` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `other_initiative` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=58 ;

-- Create table for NEI Course/conference details
CREATE TABLE IF NOT EXISTS `civicrm_value_nei_course_conference_details` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `course_conference_type` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `course_conference_type_other` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `course_conference_code` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `course_conference_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `course_conference_provider` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `how_will_this_course_enhance_the` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `proof_of_completion` int(10) unsigned DEFAULT NULL,
  `proof_of_payment` int(10) unsigned DEFAULT NULL,
  `type_of_course_provider` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`),
  KEY `FK_civicrm_value_nei_course_confere_20a7cc6f3131520d` (`proof_of_completion`),
  KEY `FK_civicrm_value_nei_course_confere_4e605fc05d1ab42c` (`proof_of_payment`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=58 ;

-- Create table for NEI ID
CREATE TABLE IF NOT EXISTS `civicrm_value_nei_id` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Default MySQL primary key',
  `entity_id` int(10) unsigned NOT NULL COMMENT 'Table that this extends',
  `college_of_nurses_of_ontario_reg` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `social_insurance_number` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_entity_id` (`entity_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;





