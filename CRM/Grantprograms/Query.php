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

 /**
  * Function to retrieve financial items assigned for a batch
  *
  * @param int $entityID
  * @param array $returnValues
  * @param null $notPresent
  * @param null $params
  * @return Object
  */
 static function getBatchFinancialItems($entityID, $returnValues, $notPresent = NULL, $params = NULL, $getCount = FALSE) {
   if (!$getCount) {
     if (!empty($params['rowCount']) &&
       $params['rowCount'] > 0
     ) {
       $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
     }
   }
   // action is taken depending upon the mode
   $select = 'civicrm_financial_trxn.id ';
   if (!empty( $returnValues)) {
     $select .= " , ".implode(' , ', $returnValues);
   }

   $orderBy = " ORDER BY civicrm_financial_trxn.id";
   if (!empty($params['sort'])) {
     $orderBy = ' ORDER BY ' . CRM_Utils_Type::escape($params['sort'], 'String');
   }

   $from = "civicrm_financial_trxn
 LEFT JOIN civicrm_entity_financial_trxn ON civicrm_entity_financial_trxn.financial_trxn_id = civicrm_financial_trxn.id
 LEFT JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_id = civicrm_financial_trxn.id
 LEFT OUTER JOIN civicrm_contribution ON civicrm_contribution.id = civicrm_entity_financial_trxn.entity_id AND civicrm_entity_financial_trxn.entity_table = 'civicrm_contribution'
 LEFT OUTER JOIN civicrm_grant ON civicrm_grant.id = civicrm_entity_financial_trxn.entity_id AND civicrm_entity_financial_trxn.entity_table = 'civicrm_grant'
 LEFT JOIN civicrm_financial_type ON civicrm_financial_type.id = IFNULL(civicrm_contribution.financial_type_id, civicrm_grant.financial_type_id)
 LEFT JOIN civicrm_contact contact_a ON contact_a.id = IFNULL(civicrm_contribution.contact_id, civicrm_grant.contact_id)
 LEFT JOIN civicrm_contribution_soft ON civicrm_contribution_soft.contribution_id = civicrm_contribution.id
 ";

   $searchFields =
     array(
       'sort_name',
       'financial_type_id',
       'contribution_page_id',
       'contribution_payment_instrument_id',
       'contribution_transaction_id',
       'contribution_source',
       'contribution_currency_type',
       'contribution_pay_later',
       'contribution_recurring',
       'contribution_test',
       'contribution_thankyou_date_is_not_null',
       'contribution_receipt_date_is_not_null',
       'contribution_pcp_made_through_id',
       'contribution_pcp_display_in_roll',
       'contribution_date_relative',
       'contribution_amount_low',
       'contribution_amount_high',
       'contribution_in_honor_of',
       'contact_tags',
       'group',
       'contribution_date_relative',
       'contribution_date_high',
       'contribution_date_low',
       'contribution_check_number',
       'contribution_status_id',
     );
   $values = array();
   foreach ($searchFields as $field) {
     if (isset($params[$field])) {
       $values[$field] = $params[$field];
       if ($field == 'sort_name') {
         $from .= " LEFT JOIN civicrm_contact contact_b ON contact_b.id = civicrm_contribution.contact_id
         LEFT JOIN civicrm_email ON contact_b.id = civicrm_email.contact_id";
       }
       if ($field == 'contribution_in_honor_of') {
         $from .= " LEFT JOIN civicrm_contact contact_b ON contact_b.id = civicrm_contribution.contact_id";
       }
       if ($field == 'contact_tags') {
         $from .= " LEFT JOIN civicrm_entity_tag `civicrm_entity_tag-{$params[$field]}` ON `civicrm_entity_tag-{$params[$field]}`.entity_id = contact_a.id";
       }
       if ($field == 'group') {
         $from .= " LEFT JOIN civicrm_group_contact `civicrm_group_contact-{$params[$field]}` ON contact_a.id = `civicrm_group_contact-{$params[$field]}`.contact_id ";
       }
       if ($field == 'contribution_date_relative') {
         $relativeDate = explode('.', $params[$field]);
         $date = CRM_Utils_Date::relativeToAbsolute($relativeDate[0], $relativeDate[1]);
         $values['contribution_date_low'] = $date['from'];
         $values['contribution_date_high'] = $date['to'];
       }
       $searchParams = CRM_Contact_BAO_Query::convertFormValues($values);
       $query = new CRM_Contact_BAO_Query($searchParams,
         CRM_Contribute_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_CONTRIBUTE,
           FALSE
         ),NULL, FALSE, FALSE,CRM_Contact_BAO_Query::MODE_CONTRIBUTE
       );
       if ($field == 'contribution_date_high' || $field == 'contribution_date_low') {
         $query->dateQueryBuilder($params[$field], 'civicrm_contribution', 'contribution_date', 'receive_date', 'Contribution Date');
       }
     }
   }
   if (!empty($query->_where[0])) {
     $where = implode(' AND ', $query->_where[0]) .
       " AND civicrm_entity_batch.batch_id IS NULL
         AND (civicrm_grant.id IS NOT NULL OR civicrm_contribution.id IS NOT NULL)";
     $searchValue = TRUE;
   }
   else {
     $searchValue = FALSE;
   }

   if (!$searchValue) {
     if (!$notPresent) {
       $where =  " ( civicrm_entity_batch.batch_id = {$entityID}
       AND civicrm_entity_batch.entity_table = 'civicrm_financial_trxn'
       AND (civicrm_grant.id IS NOT NULL OR civicrm_contribution.id IS NOT NULL) )";
     }
     else {
       $where = " ( civicrm_entity_batch.batch_id IS NULL
       AND (civicrm_grant.id IS NOT NULL OR civicrm_contribution.id IS NOT NULL) )";
     }
   }

   $sql = "
 SELECT {$select}
 FROM   {$from}
 WHERE  {$where}
      {$orderBy}
 ";

   if (isset($limit)) {
     $sql .= "{$limit}";
   }

   $result = CRM_Core_DAO::executeQuery($sql);
   return $result;
 }

 public static function buildSearchForm(&$form) {
   $paymentStatus = CRM_Core_OptionGroup::values('grant_payment_status');
   $form->add('select', 'payment_status_id',  ts('Status'),
     array('' => ts('- select -')) + $paymentStatus);

   $form->add('select', 'payment_batch_number',
     ts('Batch'),
       // CRM-19325
       ['' => ts('None')] + CRM_Contribute_PseudoConstant::batch(),
     FALSE, array('class' => 'crm-select2')
   );

   $form->addElement('text', 'payment_number', ts('Payment Number'), array('size' => 10, 'maxlength' => 10));

   CRM_Core_Form_Date::buildDateRange($form, 'payment_created_date', 1, '_low', '_high', ts('From:'), FALSE);

   $form->addElement('text', 'payable_to_name', ts('Payee name'), CRM_Core_DAO::getAttribute('CRM_Grant_DAO_GrantPayment', 'payable_to_name'));

   $form->add('text', 'amount_low', ts('From'), array('size' => 8, 'maxlength' => 8));
   $form->addRule('amount_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');

   $form->add('text', 'amount_high', ts('To'), array('size' => 8, 'maxlength' => 8));
   $form->addRule('amount_high', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');
 }

}
