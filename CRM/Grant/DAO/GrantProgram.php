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
class CRM_Grant_DAO_GrantProgram extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_grant_program';
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
   * Grant Program ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Label displayed to users
   *
   * @var string
   */
  public $label;
  /**
   * Stores a fixed (non-translated) name for the grant program.
   *
   * @var string
   */
  public $name;
  /**
   * Type of grant. Implicit FK to civicrm_option_value in grant_type option_group.
   *
   * @var int unsigned
   */
  public $grant_type_id;
  /**
   * Requested grant program amount, in default currency.
   *
   * @var float
   */
  public $total_amount;
  /**
   * Requested grant program remainder amount, in default currency.
   *
   * @var float
   */
  public $remainder_amount;
  /**
   * Financial Type ID
   *
   * @var int unsigned
   */
  public $financial_type_id;
  /**
   * Id of Grant status.
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * Application Start Date
   *
   * @var datetime
   */
  public $applications_start_date;
  /**
   * Application End Date.
   *
   * @var datetime
   */
  public $applications_end_date;
  /**
   * Allocation date.
   *
   * @var date
   */
  public $allocation_date;
  /**
   * Is this grant program active?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Is auto email active?
   *
   * @var boolean
   */
  public $is_auto_email;
  /**
   * Allocation Algorithm.
   *
   * @var int unsigned
   */
  public $allocation_algorithm;
  /**
   * Type of grant. Implicit FK to civicrm_payment.
   *
   * @var int unsigned
   */
  public $payment_id;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_grant_program
   */
  function __construct()
  {
    $this->__table = 'civicrm_grant_program';
    parent::__construct();
  }
  /**
   * return foreign links
   *
   * @access public
   * @return array
   */
  function links()
  {
    if (!(self::$_links)) {
      self::$_links = array(
        'grant_type_id' => 'civicrm_option_value:id',
        'status_id' => 'civicrm_option_value:id',
        'payment_id' => 'civicrm_payment:id',
      );
    }
    return self::$_links;
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
        'grant_program_id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Grant Program ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_grant_program.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'label' => array(
          'name' => 'label',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Label') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'name' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'grant_type_id' => array(
          'name' => 'grant_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Grant Type Id') ,
          'required' => true,
          'export' => false,
          'where' => 'civicrm_grant_program.grant_type_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'FKClassName' => 'CRM_Core_DAO_OptionValue',
        ) ,
        'total_amount' => array(
          'name' => 'total_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Total Amount') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_grant_program.total_amount',
          'headerPattern' => '',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
          'export' => true,
        ) ,
        'remainder_amount' => array(
          'name' => 'remainder_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Remainder Amount') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_grant_program.remainder_amount',
          'headerPattern' => '',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
          'export' => true,
        ) ,
        'financial_type_id' => array(
          'name' => 'financial_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Financial Type ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_grant_program.financial_type_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'grant_status_id' => array(
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Grant Program Status Id') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_grant_program.status_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
          'FKClassName' => 'CRM_Core_DAO_OptionValue',
        ) ,
        'applications_start_date' => array(
          'name' => 'applications_start_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Application Start Date.') ,
        ) ,
        'applications_end_date' => array(
          'name' => 'applications_end_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Application End Date') ,
        ) ,
        'allocation_date' => array(
          'name' => 'allocation_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Allocation date') ,
          'import' => true,
          'where' => 'civicrm_grant_program.allocation_date',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ) ,
        'is_auto_email' => array(
          'name' => 'is_auto_email',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ) ,
        'allocation_algorithm' => array(
          'name' => 'allocation_algorithm',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Allocation Algorithm') ,
        ) ,
        'payment_id' => array(
          'name' => 'payment_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Payment Id') ,
          'required' => true,
          'export' => false,
          'where' => 'civicrm_grant_program.payment_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'FKClassName' => 'CRM_Grant_DAO_GrantPayment',
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
            self::$_import['grant_program'] = & $fields[$name];
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
            self::$_export['grant_program'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
