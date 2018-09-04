<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */


class CRM_Grant_BAO_GrantPayment extends CRM_Grant_DAO_GrantPayment {
  const
    STOP = 1,
    REPRINT = 2;
  /**
   * static field for all the grant information that we can potentially export
   * @var array
   * @static
   */
  static $_exportableFields = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Grant_BAO_ManageGrant object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $grantPayment = new CRM_Grant_DAO_GrantPayment();
    $grantPayment->copyValues($params);
    if ($grantPayment->find(TRUE)) {
      CRM_Core_DAO::storeValues($grantPayment, $defaults);
      return $grantPayment;
    }
    return NULL;
  }

  function &exportableFields() {
    if (!self::$_exportableFields) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = array();
      }

      $grantFields = array(
        'id' => array(
          'title' => 'Payment ID',
          'name' => 'id',
          'data_type' => CRM_Utils_Type::T_INT
        ),
        'payment_batch_number' => array(
          'title' => 'Payment Batch Nnumber',
          'name' => 'payment_batch_number',
          'data_type' => CRM_Utils_Type::T_INT
        ),
        'payment_number' => array(
          'title' => 'Check Number',
          'name' => 'payment_number',
          'data_type' => CRM_Utils_Type::T_INT
        ),
        'financial_type_id' => array(
          'title' => 'Financial Type ID',
          'name' => 'financial_type_id',
          'data_type' => CRM_Utils_Type::T_INT
        ),
        'contact_id' => array(
          'title' => 'Contact ID',
          'name' => 'contact_id',
          'data_type' => CRM_Utils_Type::T_INT
        ),
        'payment_created_date' => array(
          'title' => 'Payment Created Date',
          'name' => 'payment_created_date',
          'data_type' => CRM_Utils_Type::T_DATE
        ),
        'payment_date' => array(
          'title' => 'Payment Date',
          'name' => 'payment_date',
          'data_type' => CRM_Utils_Type::T_DATE
        ),
        'payable_to_name' => array(
          'title' => 'Payable To Name',
          'name' => 'payable_to_name',
          'data_type' => CRM_Utils_Type::T_STRING
        ),
        'payable_to_address' => array(
          'title' => 'Payable To Address',
          'name' => 'payable_to_address',
          'data_type' => CRM_Utils_Type::T_STRING
        ),
        'amount' => array(
          'title' => 'Amount',
          'name' => 'amount',
          'data_type' => CRM_Utils_Type::T_MONEY
        ),
        'currency' => array(
          'title' => 'Currency',
          'name' => 'currency',
          'data_type' => CRM_Utils_Type::T_STRING
        ),
        'payment_reason' => array(
          'title' => 'Payment Reason',
          'name' => 'payment_reason',
          'data_type' => CRM_Utils_Type::T_STRING
        ),
        'payment_status_id' => array(
          'title' => 'Payment Status ID',
          'name' => 'payment_status_id',
          'data_type' => CRM_Utils_Type::T_STRING
        ),
        'replaces_payment_id' => array(
          'title' => 'Payment Reason',
          'name' => 'replaces_payment_id',
          'data_type' => CRM_Utils_Type::T_STRING
        )
      );

      $fields = CRM_Grant_DAO_GrantPayment::export();
      self::$_exportableFields = $fields;
    }
    return self::$_exportableFields;
  }

  /**
   * function to add grant
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   * @return object
   */
  static function add(&$params, &$ids = []) {
    if (empty($params)) {
      return;
    }

    if (isset($params['total_amount'])) {
      $params[$field] = CRM_Utils_Rule::cleanMoney($params['total_amount']);
    }
    // convert dates to mysql format
    if (isset($params['payment_created_date'])) {
      $params['payment_created_date'] = CRM_Utils_Date::processDate($params['payment_created_date'], NULL, TRUE);
    }

    $grantPayment = new CRM_Grant_DAO_GrantPayment();
    $grantPayment->id = CRM_Utils_Array::value('id', $ids);

    $grantPayment->copyValues($params);
    return $grantPayment->save();
  }


  static function del($id) {
    CRM_Utils_Hook::pre('delete', 'GrantPayment', $id, CRM_Core_DAO::$_nullArray);

    $grantPayment = new CRM_Grant_DAO_GrantPayment();
    $grantPayment->id = $id;

    $grantPayment->find();

    // delete the recently created Grant
    $grantPaymentRecent = array(
      'id'   => $id,
      'type' => 'GrantPayment'
    );
    CRM_Utils_Recent::del($grantPaymentRecent);

    if ($grantPayment->fetch()) {
      $results = $grantPayment->delete();
      CRM_Utils_Hook::post('delete', 'GrantPayment', $grantPayment->id, $grantPayment);
      return $results;
    }
    return FALSE;
  }

  static function getMaxPayementBatchNumber() {
    $query = "SELECT MAX(payment_number) as payment_number, MAX(payment_batch_number) as payment_batch_number FROM civicrm_payment";
    $dao = CRM_Core_DAO::executeQuery($query);
    while($dao->fetch()) {
      $grantPrograms['payment_number'] = $dao->payment_number;
      $grantPrograms['payment_batch_number'] = $dao->payment_batch_number;
    }
    return $grantPrograms;
  }

  static function getPaymentNumber($id) {
    $query = "SELECT id FROM civicrm_payment WHERE payment_number = {$id}";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  static function getPaymentBatchNumber($id) {
    $query = "SELECT id FROM civicrm_payment WHERE payment_batch_number = {$id}";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  static function makeReport($fileName, $rows) {
    $config = CRM_Core_Config::singleton();
    $pdf_filename = $config->customFileUploadDir . $fileName;
    $query = "SELECT msg_subject subject, msg_text text, msg_html html, pdf_format_id format FROM civicrm_msg_template WHERE msg_title = 'Grant Payment Report'";
    $dao = CRM_Core_DAO::executeQuery($query);
    $dao->fetch();
    if (!$dao->N) {
      if ($params['messageTemplateID']) {
        CRM_Core_Error::fatal(ts('No such message template: id=%1.', array(1 => $params['messageTemplateID'])));
      }
      else {
        CRM_Core_Error::fatal(ts('No such message template: option group %1, option value %2.', array(1 => $params['groupName'], 2 => $params['valueName'])));
      }
    }

    $subject = $dao->subject;
    $text = $dao->text;
    $html = $dao->html;
    $format = $dao->format;
    $dao->free();

    civicrm_smarty_register_string_resource();
    $smarty = CRM_Core_Smarty::singleton();
    foreach(array('text', 'html') as $elem) {
      $$elem = $smarty->fetch("string:{$$elem}");
    }
    $output = file_put_contents($pdf_filename,
      CRM_Utils_PDF_Utils::html2pdf(
        $html,
        $fileName,
        true,
        'Letter'
      )
    );
    return $fileName;
  }

  static function createCSV($filename, $grantPayment) {

    $headers[] = array (
      'Contact Id',
      'Financial Type',
      'Batch Number',
      'Payment Number',
      'Payment Date',
      'Payment Created Date',
      'Payable To Name',
      'Payable To Address',
      'Amount',
      'Currency',
      'Payment Reason',
      'Payment Replaces Id',
    );

    $rows = array_merge($headers, $grantPayment);
    $fp = fopen($filename, "w");
    $line = '';
    $comma = "";
    $contributionTypes = CRM_Grant_BAO_GrantProgram::contributionTypes();
    foreach ($rows as $value) {
      if (isset($value['financial_type_id'])) {
        $value['financial_type_id'] = $contributionTypes[$value['financial_type_id']];
      }
      $line .= '"'.implode('","', $value).'"';
      $line .= "\n";
    }
    fputs($fp, $line);
    fclose($fp);
  }

  static function makePDF($fileName, $rows) {
    $config = CRM_Core_Config::singleton();
    $pdf_filename = $config->customFileUploadDir . $fileName;
    $query = "SELECT msg_subject subject, msg_html html, msg_text text, pdf_format_id format
              FROM civicrm_msg_template
              WHERE msg_title = 'Grant Payment Check' AND is_default = 1;";
    $grantDao = CRM_Core_DAO::executeQuery($query);
    $grantDao->fetch();

    if (!$grantDao->N) {
      CRM_Core_Error::fatal(ts('No such message template.'));
    }
    $subject = $grantDao->subject;
    $html = $grantDao->html;
    $text = $grantDao->text;
    $format = $grantDao->format;
    $grantDao->free();

    civicrm_smarty_register_string_resource();
    $smarty = CRM_Core_Smarty::singleton();
    foreach(array('text', 'html') as $elem) {
      $$elem = $smarty->fetch("string:{$$elem}");
    }

    $output = file_put_contents(
      $pdf_filename,
      CRM_Utils_PDF_Utils::html2pdf(
        $html,
        $fileName,
        TRUE,
        'Letter'
      )
    );
    return $fileName;
  }

  /**
   * Function to get events Summary
   *
   * @static
   *
   * @return array Array of event summary values
   */
  static function getGrantSummary($admin = FALSE) {
    $query = "SELECT
      p.id,
      p.label,
      g.status_id,
      count(g.id) AS status_total,
      sum(g.amount_total) AS amount_requested,
      sum(g.amount_granted) AS amount_granted,
      sum(cp.amount) AS total_paid,
      sum(g.amount_granted)/count(g.id) AS average_amount
      FROM civicrm_grant_program p
      LEFT JOIN civicrm_grant g ON g.grant_program_id = p.id
      LEFT JOIN civicrm_entity_payment ep ON ep.entity_id = g.id AND ep.entity_table = 'civicrm_grant'
      LEFT JOIN civicrm_payment cp ON cp.id = ep.payment_id
      WHERE g.status_id IS NOT NULL
      GROUP BY g.grant_program_id, g.status_id WITH ROLLUP";

    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

    $status = array( );
    $summary = array( );
    $summary['total_grants'] = $programs = NULL;
    $summary['no_of_grants'] = NULL;
    $querys = "SELECT
      v.label as label,
      v.weight as value,
      v.value as info
      FROM civicrm_option_value v, civicrm_option_group g
      WHERE  v.option_group_id = g.id
      AND  g.name = 'grant_status'
      AND  g.is_active = 1
      ORDER BY v.weight";
    $daos = CRM_Core_DAO::executeQuery($querys, CRM_Core_DAO::$_nullArray);
    while ($daos->fetch()) {
      $status[$daos->value] = array(
        'weight' => $daos->value,
        'value' => $daos->info,
        'label' => $daos->label,
        'total' => 0,
      );
    }
    foreach ($status as $id => $name) {
      $stats[$status[$id]['value']] = array(
        'label' => $name['label'],
        'value' => $name['value'],
        'weight' => $name['weight'],
        'total' => 0
      );
    }
    $count = 1;
    while ($dao->fetch()) {
      if ($dao->N == $count) {
        $summary['total_grants']['total_requested'] = $dao->amount_requested ? CRM_Utils_Money::format($dao->amount_requested) : CRM_Utils_Money::format(0);
        $summary['total_grants']['total_granted'] = $dao->amount_granted ? CRM_Utils_Money::format($dao->amount_granted) : CRM_Utils_Money::format(0);
        $summary['total_grants']['total_paid'] = $dao->total_paid ? CRM_Utils_Money::format($dao->total_paid) : CRM_Utils_Money::format(0);
        $summary['total_grants']['total_average'] = $dao->average_amount ? CRM_Utils_Money::format($dao->average_amount) : CRM_Utils_Money::format(0);
        continue;
      }
      if (!empty($dao->status_id)) {
        $programs[$dao->label][$stats[$dao->status_id]['weight']] = array(
          'label' => $stats[$dao->status_id]['label'],
          'total' => $dao->status_total,
          'value' => $stats[$dao->status_id]['value'],
          'amount_requested' => $dao->amount_requested ? CRM_Utils_Money::format($dao->amount_requested) : CRM_Utils_Money::format(0),
          'amount_granted' => $dao->amount_granted ? CRM_Utils_Money::format($dao->amount_granted) : CRM_Utils_Money::format(0),
          'total_paid' => $dao->total_paid ? CRM_Utils_Money::format($dao->total_paid) : CRM_Utils_Money::format(0),
          'average_amount' => $dao->average_amount ? CRM_Utils_Money::format($dao->average_amount) : CRM_Utils_Money::format(0),
          'pid' => $dao->id,
        );
        $programs[$dao->label] = $programs[$dao->label] + array_diff_key($status, $programs[$dao->label]); //add the two arrays
        ksort($programs[$dao->label]);
        $summary['total_grants']['all'] = 'All';
        $summary['no_of_grants'] += $dao->status_total;
      }
      else {
        $programs["<b>Subtotal $dao->label </b>"]['subtotal'] = array(
          'label' => '',
          'total' => $dao->status_total,
          'amount_requested' => $dao->amount_requested ? CRM_Utils_Money::format($dao->amount_requested) : CRM_Utils_Money::format(0),
          'amount_granted' => $dao->amount_granted ? CRM_Utils_Money::format($dao->amount_granted) : CRM_Utils_Money::format(0),
          'total_paid' => $dao->total_paid ? CRM_Utils_Money::format($dao->total_paid) : CRM_Utils_Money::format(0),
          'average_amount' => $dao->average_amount ? CRM_Utils_Money::format($dao->average_amount) : CRM_Utils_Money::format(0),
        );
      }
      $count++;
    }
    $summary['per_status'] = $programs;
    return $summary;
  }

}
