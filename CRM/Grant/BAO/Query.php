<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
class CRM_Grant_BAO_Query {
  static function &getFields() {
    $fields = array();
    $fields = CRM_Grant_BAO_Grant::exportableFields();
    return $fields;
  }

  /**
   * build select for CiviGrant
   *
   * @return void
   * @access public
   */
  static function select(&$query) {
    if ($query->_mode & CRM_Contact_BAO_Query::MODE_GRANT) {
      if (CRM_Utils_Array::value('grant_status_id', $query->_returnProperties)) {
        $query->_select['grant_status_id'] = 'grant_status.id as grant_status_id';
        $query->_element['grant_status'] = 1;
        $query->_tables['grant_status'] = $query->_whereTables['grant_status'] = 1;
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
      }

      if (CRM_Utils_Array::value('grant_status', $query->_returnProperties)) {
        $query->_select['grant_status'] = 'grant_status.label as grant_status';
        $query->_element['grant_status'] = 1;
        $query->_tables['grant_status'] = $query->_whereTables['grant_status'] = 1;
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
      }

      if (CRM_Utils_Array::value('grant_program', $query->_returnProperties)) {
        $query->_select['grant_program'] = 'gp.name as program_name';
        $query->_element['grant_program'] = 1;
        $query->_tables['grant_program'] = 1;
      }

      if (CRM_Utils_Array::value('grant_program_id', $query->_returnProperties)) {
        $query->_select['grant_program_id'] = 'gp.id as program_id';
        $query->_element['grant_program_id'] = 1;
        $query->_tables['grant_program_id'] = 1;
      }

      if (CRM_Utils_Array::value('status_weight', $query->_returnProperties)) {
        $query->_select['status_weight'] = 'v.weight as status_weight';
        $query->_element['status_weight'] = 1;
        $query->_tables['status_weight'] = 1;
      }

      if (CRM_Utils_Array::value('grant_type_id', $query->_returnProperties)) {
        $query->_select['grant_type_id'] = 'grant_type.id as grant_type_id';
        $query->_element['grant_type'] = 1;
        $query->_tables['grant_type'] = $query->_whereTables['grant_type'] = 1;
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
      }

      if (CRM_Utils_Array::value('grant_type', $query->_returnProperties)) {
        $query->_select['grant_type'] = 'grant_type.label as grant_type';
        $query->_element['grant_type'] = 1;
        $query->_tables['grant_type'] = $query->_whereTables['grant_type'] = 1;
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
      }

      if (CRM_Utils_Array::value('grant_note', $query->_returnProperties)) {
        $query->_select['grant_note'] = "civicrm_note.note as grant_note";
        $query->_element['grant_note'] = 1;
        $query->_tables['grant_note'] = 1;
      }
      
        if (CRM_Utils_Array::value(COURSE_CONFERENCE_TYPE_COLUMN, $query->_returnProperties)) { 
        $query->_select['course_type']  = COURSE_CONFERENCE_DETAILS.'.'.COURSE_CONFERENCE_TYPE_COLUMN.' as course_type';
        $query->_element['course_type'] = 1;
        $query->_tables['course_type']  = 1;
      }
            
      if (CRM_Utils_Array::value(COURSE_CONFERENCE_NAME_COLUMN, $query->_returnProperties)) {
        $query->_select['course_name']  = COURSE_CONFERENCE_DETAILS.'.'.COURSE_CONFERENCE_NAME_COLUMN.' as course_name';
        $query->_element['course_name'] = 1;
        $query->_tables['course_name']  = 1;
      }

      $query->_select['grant_amount_requested'] = 'civicrm_grant.amount_requested as grant_amount_requested';
      $query->_select['grant_amount_granted'] = 'civicrm_grant.amount_granted as grant_amount_granted';
      $query->_select['grant_amount_total'] = 'civicrm_grant.amount_total as grant_amount_total';
      $query->_select['grant_application_received_date'] = 'civicrm_grant.application_received_date as grant_application_received_date ';
      $query->_select['grant_report_received'] = 'civicrm_grant.grant_report_received as grant_report_received';
      $query->_select['grant_money_transfer_date'] = 'civicrm_grant.money_transfer_date as grant_money_transfer_date';
      $query->_select['grant_payment_created'] = 'civicrm_payment.payment_created_date as grant_payment_created';
      $query->_element['grant_type_id'] = 1;
      $query->_element['grant_status_id'] = 1;
      $query->_tables['civicrm_grant'] = 1;
      $query->_whereTables['civicrm_grant'] = 1;
    }
  }

  /**
   * Given a list of conditions in params generate the required
   * where clause
   *
   * @return void
   * @access public
   */
  static function where(&$query) {
    foreach ($query->_params as $id => $values) {
      if (!is_array($values) || count($values) != 5) {
        continue;
      }

      if (substr($values[0], 0, 6) == 'grant_') {
        self::whereClauseSingle($values, $query);
      }
    }
  }

  static function whereClauseSingle(&$values, &$query) {
    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
    list($name, $op, $value, $grouping, $wildcard) = $values;
    switch ($name) {
      case 'grant_money_transfer_date_low':
      case 'grant_money_transfer_date_high':
        $query->dateQueryBuilder($values, 'civicrm_grant',
          'grant_money_transfer_date', 'money_transfer_date',
          'Money Transfer Date'
        );
        return;

      case 'grant_money_transfer_date_notset':
        $query->_where[$grouping][] = "civicrm_grant.money_transfer_date IS NULL";
        $query->_qill[$grouping][] = ts("Grant Money Transfer Date is NULL");
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
        return;

      case 'grant_application_received_date_low':
      case 'grant_application_received_date_high':
        $query->dateQueryBuilder($values, 'civicrm_grant',
          'grant_application_received_date',
          'application_received_date', 'Application Received Date'
        );
        return;

      case 'grant_application_received_notset':
        $query->_where[$grouping][] = "civicrm_grant.application_received_date IS NULL";
        $query->_qill[$grouping][] = ts("Grant Application Received Date is NULL");
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
        return;

      case 'grant_due_date_low':
      case 'grant_due_date_high':
        $query->dateQueryBuilder($values, 'civicrm_grant',
          'grant_due_date',
          'grant_due_date', 'Grant Due Date'
        );
        return;

      case 'grant_due_date_notset':
        $query->_where[$grouping][] = "civicrm_grant.grant_due_date IS NULL";
        $query->_qill[$grouping][] = ts("Grant Due Date is NULL");
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
        return;

      case 'grant_decision_date_low':
      case 'grant_decision_date_high':
        $query->dateQueryBuilder($values, 'civicrm_grant',
          'grant_decision_date',
          'decision_date', 'Grant Decision Date'
        );
        return;

      case 'grant_decision_date_notset':
        $query->_where[$grouping][] = "civicrm_grant.decision_date IS NULL";
        $query->_qill[$grouping][] = ts("Grant Decision Date is NULL");
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;
        return;

      case 'grant_type_id':

        $value = $strtolower(CRM_Core_DAO::escapeString(trim($value)));

        $query->_where[$grouping][] = "civicrm_grant.grant_type_id $op '{$value}'";

        $grantTypes = CRM_Core_OptionGroup::values('grant_type');
        $value = $grantTypes[$value];
        $query->_qill[$grouping][] = ts('Grant Type %2 %1', array(1 => $value, 2 => $op));
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;

        return;
      case 'grant_program_id':
        
        $value = $strtolower(CRM_Core_DAO::escapeString(trim($value)));
        
        $query->_where[$grouping][] = "civicrm_grant.grant_program_id $op '{$value}'";
        
        $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
        $value = $grantPrograms[$value];
        $query->_qill[$grouping ][] = ts('Grant Type %2 %1', array(1 => $value, 2 => $op));
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;

        return;

      case 'grant_status_id':

        $value = $strtolower(CRM_Core_DAO::escapeString(trim($value)));

        $query->_where[$grouping][] = "civicrm_grant.status_id $op '{$value}'";


        $grantStatus = CRM_Core_OptionGroup::values('grant_status');
        $value = $grantStatus[$value];

        $query->_qill[$grouping][] = ts('Grant Status %2 %1', array(1 => $value, 2 => $op));
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;

        return;

      case 'grant_report_received':

        if ($value == 1) {
          $yesNo = 'Yes';
          $query->_where[$grouping][] = "civicrm_grant.grant_report_received $op $value";
        }
        elseif ($value == 0) {
          $yesNo = 'No';
          $query->_where[$grouping][] = "civicrm_grant.grant_report_received IS NULL";
        }

        $query->_qill[$grouping][] = "Grant Report Received = $yesNo ";
        $query->_tables['civicrm_grant'] = $query->_whereTables['civicrm_grant'] = 1;

        return;

      case 'grant_amount':
      case 'grant_amount_low':
      case 'grant_amount_high':
        $query->numberRangeBuilder($values,
          'civicrm_grant', 'grant_amount', 'amount_granted', 'Amount Granted'
        ); 
      case 'grant_amount_total':
      case 'grant_amount_total_low':
      case 'grant_amount_total_high':
        $query->numberRangeBuilder($values,
          'civicrm_grant', 'grant_amount_total', 'amount_total', 'Amount Allocated' 
        );
      case 'grant_assessment':
      case 'grant_assessment_low':
      case 'grant_assessment_high':
        $query->numberRangeBuilder($values,
          'civicrm_grant', 'grant_assessment', 'assessment', 'Assessment' 
        );        
    }
  }

  static function from($name, $mode, $side) {
    $from = NULL;
    switch ($name) {
      case 'civicrm_grant':
        $from = " $side JOIN civicrm_grant ON civicrm_grant.contact_id = contact_a.id ";
        break;

      case 'grant_status':
        $from .= " $side JOIN civicrm_option_group option_group_grant_status ON (option_group_grant_status.name = 'grant_status')";
        $from .= " $side JOIN civicrm_option_value grant_status ON (civicrm_grant.status_id = grant_status.value AND option_group_grant_status.id = grant_status.option_group_id ) ";
        break;

      case 'grant_type':
        $from .= " $side JOIN civicrm_option_group option_group_grant_type ON (option_group_grant_type.name = 'grant_type')";
        if ($mode & CRM_Contact_BAO_Query::MODE_GRANT) {
          $from .= " INNER JOIN civicrm_option_value grant_type ON (civicrm_grant.grant_type_id = grant_type.value AND option_group_grant_type.id = grant_type.option_group_id ) ";
        }
        else {
          $from .= " $side JOIN civicrm_option_value grant_type ON (civicrm_grant.grant_type_id = grant_type.value AND option_group_grant_type.id = grant_type.option_group_id ) ";
        }
        $from .= "$side JOIN civicrm_entity_payment AS temp1 ON (civicrm_grant.id = temp1.entity_id AND temp1.entity_table = 'civicrm_grant')
$side JOIN (SELECT payment_id AS payment_id, entity_id AS entity_id FROM civicrm_entity_payment ORDER BY payment_id DESC) AS temp2 ON temp1.entity_id = temp2.entity_id
$side JOIN civicrm_payment ON (temp2.payment_id = civicrm_payment.id)"; 

        break;

      case 'grant_note':
        $from .= " $side JOIN civicrm_note ON ( civicrm_note.entity_table = 'civicrm_grant' AND
                                                        civicrm_grant.id = civicrm_note.entity_id )";
        break;
      case 'status_weight':
        $from .= " $side JOIN civicrm_option_value v ON (civicrm_grant.status_id = v.value AND v.option_group_id=21)";
        break;

      case 'course_name':
        $from .= ' '.$side.' JOIN '.COURSE_CONFERENCE_DETAILS.' ON ( civicrm_grant.id = '.COURSE_CONFERENCE_DETAILS.'.entity_id )';
        break;

      case 'grant_program':
        $from .= " $side JOIN civicrm_grant_program gp ON (civicrm_grant.grant_program_id = gp.id)";
        break;
    }
    return $from;
  }

  /**
   * getter for the qill object
   *
   * @return string
   * @access public
   */
  function qill() {
    return (isset($this->_qill)) ? $this->_qill : "";
  }

  static function defaultReturnProperties($mode,
    $includeCustomFields = TRUE
  ) {
    $properties = NULL;
    if ($mode & CRM_Contact_BAO_Query::MODE_GRANT) {
      $properties = array(
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
        'grant_id' => 1,
        'grant_type' => 1,
        'grant_status' => 1,
        'status_weight' => 1,
        'grant_amount_requested' => 1,
        'grant_application_received_date' => 1,
        'grant_payment_created' => 1,
        COURSE_CONFERENCE_TYPE_COLUMN => 1,
        COURSE_CONFERENCE_NAME_COLUMN => 1,
        'grant_report_received' => 1,
        'grant_money_transfer_date' => 1,
        'grant_note' => 1,
        'grant_program' => 1,
        'grant_program_id' => 1,
      );
    }

    return $properties;
  }

  /**
   * add all the elements shared between grant search and advanaced search
   *
   * @access public
   *
   * @return void
   * @static
   */
  static function buildSearchForm(&$form) {

    $grantType = CRM_Core_OptionGroup::values('grant_type');
    $form->add('select', 'grant_type_id', ts('Grant Type'),
      array(
        '' => ts('- any -')) + $grantType
    );

    $grantStatus = CRM_Core_OptionGroup::values('grant_status');
    $form->add('select', 'grant_status_id', ts('Grant Status'),
      array(
        '' => ts('- any -')) + $grantStatus
    );

    $form->addDate('grant_application_received_date_low', ts('App. Received Date - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('grant_application_received_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addElement('checkbox', 'grant_application_received_notset', ts(''), NULL);

    $form->addDate('grant_money_transfer_date_low', ts('Money Sent Date - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('grant_money_transfer_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addElement('checkbox', 'grant_money_transfer_date_notset', ts(''), NULL);

    $form->addDate('grant_due_date_low', ts('Report Due Date - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('grant_due_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addElement('checkbox', 'grant_due_date_notset', ts(''), NULL);

    $form->addDate('grant_decision_date_low', ts('Grant Decision Date - From'), FALSE, array('formatType' => 'searchDate'));
    $form->addDate('grant_decision_date_high', ts('To'), FALSE, array('formatType' => 'searchDate'));

    $form->addElement('checkbox', 'grant_decision_date_notset', ts(''), NULL);

    $form->addYesNo('grant_report_received', ts('Grant report received?'));

    $form->add('text', 'grant_amount_low', ts('Minimum Amount'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('grant_amount_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');

    $form->add('text', 'grant_amount_high', ts('Maximum Amount'), array('size' => 8, 'maxlength' => 8));
    $form->addRule('grant_amount_high', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');

    // add all the custom  searchable fields
    $grant = array('Grant');
    $groupDetails = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, TRUE, $grant);
    if ($groupDetails) {
      $form->assign('grantGroupTree', $groupDetails);
      foreach ($groupDetails as $group) {
        foreach ($group['fields'] as $field) {
          $fieldId = $field['id'];
          $elementName = 'custom_' . $fieldId;
          CRM_Core_BAO_CustomField::addQuickFormElement($form,
            $elementName,
            $fieldId,
            FALSE, FALSE, TRUE
          );
        }
      }
    }

    $form->assign('validGrant', TRUE);
  }

  static function addShowHide(&$showHide) {
    $showHide->addHide('grantForm');
    $showHide->addShow('grantForm_show');
  }

  static function searchAction(&$row, $id) {}

  static function tableNames(&$tables) {}
}

