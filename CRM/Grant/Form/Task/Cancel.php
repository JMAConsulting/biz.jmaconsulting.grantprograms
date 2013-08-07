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

/**
 * This class provides the functionality to cancel a group of
 * grant payments.
 */
class CRM_Grant_Form_Task_Cancel extends CRM_Grant_Form_PaymentTask {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();

    //check permission for delete.
    if (!CRM_Core_Permission::checkActionPermission('CiviGrant', CRM_Core_Action::DELETE)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));  
    }
  }

  /**
   * Build the form
   *
   * @access public
   * @return void
   */
  function buildQuickForm() {
    $this->addDefaultButtons(ts('Cancel Grants'), 'done');
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   * @return None
   */
  public function postProcess() {
    if (empty($this->_grantPaymentIds)) {
      return FALSE;
    }    
    // change status of payment(s) to Cancelled And grant status to Eligible
    $query = "UPDATE civicrm_grant cg
LEFT JOIN civicrm_entity_payment cep ON cep.entity_id = cg.id
LEFT JOIN civicrm_payment cp ON cp.id = cep.payment_id
SET cg.status_id = %1,
cp.payment_status_id = %2
WHERE  cp.id IN (" . implode(',', $this->_grantPaymentIds) . ") AND cep.entity_table = 'civicrm_grant'";
    $params = array(
      1 => array(CRM_Core_OptionGroup::getValue('grant_status', 'Eligible', 'name'), 'Integer'),
      2 => array(CRM_Core_OptionGroup::getValue('grant_payment_status', 'Cancelled', 'name'), 'Integer'),
    );
    CRM_Core_DAO::executeQuery($query, $params);

    CRM_Core_Session::setStatus(ts('Cancel Grant Payments(s): %1', array(1 => count($this->_grantPaymentIds))));
    CRM_Core_Session::setStatus(ts('Total Selected Grant Payments(s): %1', array(1 => count($this->_grantPaymentIds))));
  }
}


