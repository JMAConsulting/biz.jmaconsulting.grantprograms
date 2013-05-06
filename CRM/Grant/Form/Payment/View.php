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
 * This class generates form components for processing a Grant
 * 
 */
class CRM_Grant_Form_Payment_View extends CRM_Core_Form {

  /**  
   * Function to set variables up before form is built  
   *                                                            
   * @return void  
   * @access public  
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $this->assign('context', $context);
    $values = array(); 
    $params['id'] = $this->_id;
    CRM_Grant_BAO_GrantPayment::retrieve( $params, $values);
    $paymentStatus = CRM_Core_OptionGroup::values( 'grant_payment_status' );
    $contributionTypes = CRM_Grant_BAO_GrantProgram::contributionTypes();
    $this->assign('payment_status_id', $paymentStatus[$values['payment_status_id']]);
    $this->assign('contribution_type_id', $contributionTypes[$values['contribution_type_id']]);
 
    $grantTokens = array( 
      'payment_batch_number',
      'payment_number',
      'payment_created_date',
      'payment_date', 
      'payable_to_name', 
      'payable_to_address', 
      'amount', 
      'currency', 
      'payment_reason', 
      'replaces_payment_id' 
    );

    foreach ($grantTokens as $token) {
      $this->assign($token, CRM_Utils_Array::value($token, $values));
    }

    $this->assign('id', $this->_id);
  }
  
  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->addButtons(array(  
        array ( 
          'type' => 'cancel',  
          'name' => ts('Cancel'),  
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',  
          'isDefault' => TRUE   
       )
     ));     
    } 
    elseif (($this->_action & CRM_Core_Action::STOP) || ($this->_action &  CRM_Core_Action::REPRINT) || ($this->_action &  CRM_Core_Action::WITHDRAW)) {
      $this->addButtons(array( 
        array ( 
          'type' => 'submit',  
          'name' => ts('OK'),  
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',  
          'isDefault' => TRUE), 
        array ( 
          'type' => 'cancel',  
          'name' => ts('Cancel'),  
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',  
          'isDefault' => TRUE)
        )
      );
    }
  }

  public function postProcess() {
    CRM_Utils_System::flushCache('CRM_Grant_DAO_GrantPayment');
    if ($this->_action & CRM_Core_Action::STOP) {
      $domainID = CRM_Core_Config::domainID();
      $dao = new CRM_Grant_DAO_GrantPayment();
      $dao->id = $this->_id;
      $dao->domain_id = $domainID;
      $dao->payment_status_id = CRM_Core_OptionGroup::getValue('grant_payment_status', 'Stopped', 'name');
      $dao->save();
      CRM_Core_Session::setStatus(ts('Selected Grant Payment has been stopped successfully.'));
      CRM_Utils_System::redirect( CRM_Utils_System::url('civicrm/grant/payment/search', 'reset=1&force=1'));
    } 
    elseif ($this->_action & CRM_Core_Action::REPRINT) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/payment/reprint', 'reset=1&prid=' . $this->_id . '&qfKey='. $_POST['qfKey']));            
    } 
    elseif ($this->_action & CRM_Core_Action::WITHDRAW) {
      $domainID = CRM_Core_Config::domainID();
      $dao = new CRM_Grant_DAO_GrantPayment();
      $dao->id = $this->_id;
      $dao->domain_id = $domainID;
      $dao->payment_status_id = CRM_Core_OptionGroup::getValue('grant_payment_status', 'Withdrawn', 'name');
      $dao->save();
      CRM_Core_Session::setStatus(ts('Selected Grant Payment has been withdraw successfully.'));
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/payment/search', 'reset=1&force=1'));
    }
  }  
}