<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
          'title' => 'Payment Number',
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
  static function add(&$params, &$ids) {
    
    if (empty($params)) {
      return;
    }
    
    if (isset( $params['total_amount'])) {
      $params[$field] = CRM_Utils_Rule::cleanMoney($params['total_amount']);
    }
    // convert dates to mysql format
    $dates = array( 
      'payment_date',
      'payment_created_date' 
    );
        
    foreach ($dates as $date) {
      if (isset($params[$date])) {
        $params[$date] = CRM_Utils_Date::processDate($params[$date], NULL, TRUE);
      }
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
    $query = "SELECT MAX(payment_number) as payment_number, MAX(payment_batch_number) as payment_batch_number FROM civicrm_payment ";
    $dao = CRM_Core_DAO::executeQuery($query);
    while($dao->fetch()) {
      $grantPrograms['payment_number'] = $dao->payment_number;
      $grantPrograms['payment_batch_number'] = $dao->payment_batch_number;
    }
    return $grantPrograms;
  }
    
  static function getPaymentNumber($id) {
    $query = "SELECT id FROM civicrm_payment WHERE payment_number = {$id} ";
    return CRM_Core_DAO::singleValueQuery($query);
  }
    
  static function getPaymentBatchNumber($id) {
    $query = "SELECT id FROM civicrm_payment WHERE payment_batch_number = {$id} ";
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
      } else {
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
    $line = "";
    $comma = "";
    $contributionTypes = CRM_Grant_BAO_GrantProgram::contributionTypes();
    foreach ($rows as $value) {
      if (isset($value['financial_type_id'])) {
        $value['financial_type_id'] = $contributionTypes[$value['financial_type_id']];
      }
      $line .= implode('; ', $value);
      $line .= "\n";
    }
    fputs($fp, $line);
    fclose($fp);
  }
 
  static function replaceVariables($html, $values) {
    foreach ($values as $key => $value) {
      $html = str_replace($key, $value, $html);
    }
    return $html;
  }
  
  static function makePDF($fileName, $rows) {
    $config = CRM_Core_Config::singleton();
    $pdf_filename = $config->customFileUploadDir . $fileName;
    $query = "SELECT msg_subject subject, msg_html html
                      FROM civicrm_msg_template 
                      WHERE msg_title = 'Grant Payment Check' AND is_default = 1;";
    $grantDao = CRM_Core_DAO::executeQuery($query);
    $grantDao->fetch();
                
    if (!$grantDao->N) {
      if ($params['messageTemplateID']) {
        CRM_Core_Error::fatal(ts('No such message template.'));
      }
    }
    $subject = $grantDao->subject;
    $html = $grantDao->html;

    $final_html = NULL;
    foreach ($rows as $values) {
      $words = new CRM_Grant_Words();
      $amount = $values['amount'];
      $values['check_total'] = ltrim($amount, '$');
      $amount = str_replace (',', '', $amount);
      $values['total_in_words'] = ucwords($words->convert_number_to_words(ltrim($amount, '$')));
      $final_html .= self::replaceVariables($html, $values) . "<br>";
    }
    $output = file_put_contents( 
      $pdf_filename, 
      CRM_Utils_PDF_Utils::html2pdf( 
        $final_html,
        $fileName,
        TRUE,
        'Letter'
      )
    );
    return $fileName;
  }
}