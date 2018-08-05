<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Grantprograms_Query extends CRM_Contact_BAO_Query_Interface {

 function select(&$query) {
   if (($query->_mode &CRM_Contact_BAO_Query::MODE_GRANT)) {
     foreach(['status_weight', 'grant_program', 'grant_program_id', 'grant_payment_created'] as $attr) {
       $query->_returnProperties[$attr] = 1;
       if ($attr == 'status_weight') {
         $query->_select['status_weight'] = 'v.weight as status_weight';
         $query->_element['status_weight'] = 1;
         $query->_tables['status_weight'] = 1;
       }
       elseif ($attr = 'grant_program') {
         $query->_select['grant_program'] = 'gp.name as program_name';
         $query->_element['grant_program'] = 1;
         $query->_tables['grant_program'] = 1;
       }
       elseif ($attr = 'grant_program_id') {
         $query->_select['grant_program_id'] = 'gp.id as program_id';
         $query->_element['grant_program'] = 1;
         $query->_tables['grant_program'] = 1;
       }
       else {
         $query->_select['grant_payment_created'] = 'civicrm_payment.payment_created_date as grant_payment_created';
         $query->_tables['civicrm_payment'] = 1;
       }

     }
   }
 }

 function where(&$query) {
   foreach ($query->_params as $id => $values) {
     if (!is_array($values) || count($values) != 5) {
       continue;
     }

     if (substr($values[0], 0, 6) == 'grant_') {
       self::whereClauseSingle($values, $query);
     }
   }
 }

 public static function whereClauseSingle(&$values, &$query) {
   $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
   list($name, $op, $value, $grouping, $wildcard) = $values;
   switch ($name) {
     case 'grant_program_id':

       $value = $strtolower(CRM_Core_DAO::escapeString(trim($value)));

       $query->_where[$grouping][] = "civicrm_grant.grant_program_id $op '{$value}'";

       $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
       $value = $grantPrograms[$value];
       $query->_qill[$grouping ][] = ts('Grant Type %2 %1', array(1 => $value, 2 => $op));
       $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
       return;

     case 'grant_assessment':
     case 'grant_assessment_low':
     case 'grant_assessment_high':
      $query->numberRangeBuilder($values,
        'civicrm_grant', 'grant_assessment', 'assessment', 'Assessment'
      );
      return;
   }
 }

  function &getFields() {
    $fields = array();
    return $fields;
  }

function from($name, $mode, $side) {
    if ($name == 'grant_program') {
      return " $side JOIN civicrm_grant_program gp ON (civicrm_grant.grant_program_id = gp.id)";
    }
    elseif ($name == 'status_weight') {
      return " $side JOIN civicrm_option_value v ON (civicrm_grant.status_id = v.value AND v.option_group_id=21)";
    }
    elseif ($name == 'civicrm_payment') {
      return " $side JOIN civicrm_entity_payment ep ON (civicrm_grant.id = ep.entity_id AND ep.entity_table = 'civicrm_grant')" .
          " $side JOIN civicrm_payment civicrm_payment ON (ep.payment_id = civicrm_payment.id) ";
    }
}

public static function getPanesMapper(&$panes) {
 }

}
