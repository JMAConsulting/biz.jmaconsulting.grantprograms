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
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_Grant_DAO_GrantPayment extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_payment';
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  static $_fields = null;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  static $_links = null;
  /**
   * static instance to hold the values that can
   * be imported
   *
   * @var array
   * @static
   */
  static $_import = null;
  /**
   * static instance to hold the values that can
   * be exported
   *
   * @var array
   * @static
   */
  static $_export = null;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  static $_log = true;
  /**
   * Id
   *
   * @var int unsigned
   */
  public $id;
  public $financial_trxn_id;
  /**
   * Payment Created Date.
   *
   * @var date
   */
  public $payment_created_date;
  /**
   * Payment Date.
   *
   * @var date
   */
  public $payment_date;
  public $payment_reason;
  /**
   * Payment Status ID
   *
   * @var int unsigned
   */
  public $payment_status_id;
  /**
   * Replaces Payment Id.
   *
   * @var string
   */
  public $replaces_payment_id;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_payment
   */
  function __construct()
  {
    $this->__table = 'civicrm_payment';
    parent::__construct();
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  static function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Id') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_payment.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'financial_trxn_id' => array(
          'name' => 'financial_trxn_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Financial Trxn ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_payment.financial_trxn_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'payment_created_date' => array(
          'name' => 'payment_created_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Payment Created Date') ,
          'import' => true,
          'where' => 'civicrm_payment.payment_created_date',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'payment_reason' => array(
          'name' => 'payment_reason',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Payment Reason') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_payment.payment_reason',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'payment_status_id' => array(
          'name' => 'payment_status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Payment Status') ,
          'pseudoconstant' => [
            'optionGroupName' => 'grant_payment_status',
          ]
        ) ,
        'replaces_payment_id' => array(
          'name' => 'replaces_payment_id',
          'type' => CRM_Utils_Type::T_STRING,
          'maxlength' => 8,
          'size' => CRM_Utils_Type::EIGHT,
          'import' => true,
          'where' => 'civicrm_payment.replaces_payment_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
      );
    }
    return self::$_fields;
  }
  /**
   * returns the names of this table
   *
   * @access public
   * @static
   * @return string
   */
  static function getTableName()
  {
    return self::$_tableName;
  }
  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  function getLog()
  {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   * @static
   */
  static function &import($prefix = false)
  {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['payment'] = & $fields[$name];
          } else {
            self::$_import[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_import;
  }
  /**
   * returns the list of fields that can be exported
   *
   * @access public
   * return array
   * @static
   */
  static function &export($prefix = false)
  {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['payment'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
