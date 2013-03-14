<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.1                                                |
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
    /**
     * Payment Batch Nnumber
     *
     * @var int unsigned
     */
    public $payment_batch_number;
    /**
     * Payment Number
     *
     * @var int unsigned
     */
    public $payment_number;
    /**
     * Contribution Type ID
     *
     * @var int unsigned
     */
    public $contribution_type_id;
    /**
     * Contact ID
     *
     * @var int unsigned
     */
    public $contact_id;
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
    /**
     * Payable To Name.
     *
     * @var string
     */
    public $payable_to_name;
    /**
     * Payable To Address.
     *
     * @var string
     */
    public $payable_to_address;
    /**
     * Requested grant amount, in default currency.
     *
     * @var float
     */
    public $amount;
    /**
     * 3 character string, value from config setting or input via user.
     *
     * @var string
     */
    public $currency;
    /**
     * Payment Reason.
     *
     * @var string
     */
    public $payment_reason;
    /**
     * Payment Status ID
     *
     * @var int unsigned
     */
    public $payment_status_id;
    /**
     * Replaces Payment ID
     *
     * @var int unsigned
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
                'payment_batch_number' => array(
                    'name' => 'payment_batch_number',
                    'type' => CRM_Utils_Type::T_INT,
                    'title' => ts('Payment Batch Nnumber') ,
                    'required' => true,
                    'import' => true,
                    'where' => 'civicrm_payment.payment_batch_number',
                    'headerPattern' => '',
                    'dataPattern' => '',
                    'export' => true,
                ) ,
                'payment_number' => array(
                    'name' => 'payment_number',
                    'type' => CRM_Utils_Type::T_INT,
                    'title' => ts('Payment Number') ,
                    'required' => true,
                    'import' => true,
                    'where' => 'civicrm_payment.payment_number',
                    'headerPattern' => '',
                    'dataPattern' => '',
                    'export' => true,
                ) ,
                'contribution_type_id' => array(
                    'name' => 'contribution_type_id',
                    'type' => CRM_Utils_Type::T_INT,
                    'title' => ts('Contribution Type ID') ,
                    'required' => true,
                    'import' => true,
                    'where' => 'civicrm_payment.contribution_type_id',
                    'headerPattern' => '',
                    'dataPattern' => '',
                    'export' => true,
                ) ,
                'contact_id' => array(
                    'name' => 'contact_id',
                    'type' => CRM_Utils_Type::T_INT,
                    'title' => ts('Contact ID') ,
                    'required' => true,
                    'import' => true,
                    'where' => 'civicrm_payment.contact_id',
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
                'payment_date' => array(
                    'name' => 'payment_date',
                    'type' => CRM_Utils_Type::T_DATE,
                    'title' => ts('Payment Date') ,
                    'import' => true,
                    'where' => 'civicrm_payment.payment_date',
                    'headerPattern' => '',
                    'dataPattern' => '',
                    'export' => true,
                ) ,
                'payable_to_name' => array(
                    'name' => 'payable_to_name',
                    'type' => CRM_Utils_Type::T_STRING,
                    'title' => ts('Payable To Name') ,
                    'maxlength' => 255,
                    'size' => CRM_Utils_Type::HUGE,
                    'import' => true,
                    'where' => 'civicrm_payment.payable_to_name',
                    'headerPattern' => '',
                    'dataPattern' => '',
                    'export' => true,
                ) ,
                'payable_to_address' => array(
                    'name' => 'payable_to_address',
                    'type' => CRM_Utils_Type::T_STRING,
                    'title' => ts('Payable To Address') ,
                    'maxlength' => 255,
                    'size' => CRM_Utils_Type::HUGE,
                    'import' => true,
                    'where' => 'civicrm_payment.payable_to_address',
                    'headerPattern' => '',
                    'dataPattern' => '',
                    'export' => true,
                ) ,
                'amount' => array(
                    'name' => 'amount',
                    'type' => CRM_Utils_Type::T_MONEY,
                    'title' => ts('Amount') ,
                    'required' => true,
                    'import' => true,
                    'where' => 'civicrm_payment.amount',
                    'headerPattern' => '',
                    'dataPattern' => '/^\d+(\.\d{2})?$/',
                    'export' => true,
                ) ,
                'currency' => array(
                    'name' => 'currency',
                    'type' => CRM_Utils_Type::T_STRING,
                    'title' => ts('Currency') ,
                    'maxlength' => 3,
                    'size' => CRM_Utils_Type::FOUR,
                    'import' => true,
                    'where' => 'civicrm_payment.currency',
                    'headerPattern' => '/cur(rency)?/i',
                    'dataPattern' => '/^[A-Z]{3}$/i',
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
                    'title' => ts('Payment Status ID') ,
                    'required' => true,
                    'import' => true,
                    'where' => 'civicrm_payment.payment_status_id',
                    'headerPattern' => '',
                    'dataPattern' => '',
                    'export' => true,
                ) ,
                'replaces_payment_id' => array(
                    'name' => 'replaces_payment_id',
                    'type' => CRM_Utils_Type::T_INT,
                    'title' => ts('Replaces Payment ID') ,
                    'required' => true,
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
     */
    function &import($prefix = false)
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
     */
    function &export($prefix = false)
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
