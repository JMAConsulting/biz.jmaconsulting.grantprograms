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

-- Accounting integration
SELECT @option_group_id_arel := max(id) from civicrm_option_group where name = 'account_relationship';

DELETE FROM civicrm_option_value WHERE option_group_id = @option_group_id_arel AND name = 'Accounts Payable';

SELECT @financialType := id FROM civicrm_financial_type WHERE name = 'NEI Grant';

DELETE FROM civicrm_entity_financial_account WHERE entity_table = 'civicrm_financial_type' AND entity_id = @financialType;

DELETE FROM civicrm_financial_account WHERE name = 'NEI Grant';

DELETE FROM civicrm_financial_type WHERE name = 'NEI Grant';

-- RG-149
DELETE cg, cv FROM civicrm_option_group cg
INNER JOIN civicrm_option_value cv ON cg.id = cv.option_group_id
WHERE cg.name = 'grant_info_too_late';