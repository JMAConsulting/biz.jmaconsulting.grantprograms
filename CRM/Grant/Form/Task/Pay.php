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

require_once 'CRM/Grant/Form/Task.php';

/**
 * This class provides the functionality to delete a group of
 * participations. This class provides functionality for the actual
 * deletion.
 */
class CRM_Grant_Form_Task_Pay extends CRM_Grant_Form_Task {
  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific participation?
   *
   * @var boolean
   */
  protected $_single = false;

  function preProcess() {
    parent::preProcess();
    if (!CRM_Core_Permission::check('create payments in CiviGrant')) {
      CRM_Core_Error::fatal(ts( 'You do not have permission to access this page'));
    }
  }

  function buildQuickForm() {
    $message = [];
    $paidGrantCount = civicrm_api3('Grant', 'getcount', [
      'status_id' => 'Paid',
      'id' => ['IN' => $this->_grantIds]
    ]);
    $this->_approvedGrants = (array) civicrm_api3('Grant', 'get', [
      'status_id' => 'Approved for Payment',
      'id' => ['IN' => $this->_grantIds]
    ])['values'];

    $notApproved = count($this->_grantIds) - $paidGrantCount - count($this->_approvedGrants);

    $currencyCount = count(array_flip(CRM_Utils_Array::collect('currency', $this->_approvedGrants)));
    if (count($this->_approvedGrants)) {
      if ($paidGrantCount) {
        $message[] = sprintf("<br> %d of the %d selected grants have already been paid.", $paidGrantCount, count($this->_grantIds));
      }
      if ($notApproved) {
        $message[] = sprintf("%d of the %d selected grants are not eligible.", $notApproved, count($this->_grantIds));
      }
      if ($currencyCount > 1) {
        $message[] = sprintf("%d of the %d grants have different currency of same user.", $currencyCount, count($this->_grantIds));
      }
      $message[] = 'Would you like to proceed to paying the ' . count($this->_approvedGrants) . ' eligible or approved for payment but unpaid grants?';
      CRM_Core_Session::setStatus(ts(implode('<br>', $message)), 'Payment Details', 'no-popup');

      $this->addButtons( array(
        array (
          'type' => 'next',
          'name' => ts('Continue >>'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => true,
        ),
        array (
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
        )
      );
    }
    else {
      CRM_Core_Session::setStatus(ts('Please select at least one grant that has been approved for payment or eligible and not been paid.'), NULL, 'no-popup');
      $this->addButtons(array(
        array (
          'type' => 'cancel',
          'name' => ts('Cancel') ),
        )
      );
    }
  }

  public function postProcess() {
    $this->set('approvedGrants', $this->_approvedGrants);
    $this->controller->resetPage('GrantPayment');
  }
}
