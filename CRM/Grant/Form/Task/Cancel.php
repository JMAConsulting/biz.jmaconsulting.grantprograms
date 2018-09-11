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
    $smarty = CRM_Core_Smarty::singleton();
    $vars = $smarty->get_template_vars();
    CRM_Core_Session::setStatus(ts('Are you sure you want to cancel the selected Grant Payments? This cancel operation cannot be undone and will delete all transactions associated with these grant payments. Number of selected grant payments: '. $vars['totalSelectedGrants']), NULL, 'no-popup');
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

    $sql = sprintf("UPDATE civicrm_payment p
    INNER JOIN civicrm_entity_financial_trxn eft ON eft.financial_trxn_id = p.financial_trxn_id AND eft.entity_table = 'civicrm_grant'
    INNER JOIN civicrm_grant g ON g.id = eft.entity_id
    SET g.status_id = %s, p.payment_status_id = %s
    WHERE p.id IN (%s) ",
      CRM_Core_PseudoConstant::getKey('CRM_Grant_DAO_Grant', 'status_id', 'Eligible'),
      CRM_Core_OptionGroup::getValue('grant_payment_status', 'Cancelled', 'name'),
      implode(',', $this->_grantPaymentIds)
    );
    CRM_Core_DAO::executeQuery($sql);

    CRM_Core_Session::setStatus(ts('Cancel Grant Payments(s): %1', array(1 => count($this->_grantPaymentIds))));
    CRM_Core_Session::setStatus(ts('Total Selected Grant Payments(s): %1', array(1 => count($this->_grantPaymentIds))));
  }
}
