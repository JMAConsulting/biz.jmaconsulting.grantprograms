<?php

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
    if (!$this->_action) {
      $this->_action = $_REQUEST['action'];
    }
    $this->assign('context', $context);
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/grant/payment/search', '_qf_PaymentSearch_display=true&force=1&reset=1'));
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action == CRM_Core_Action::VIEW) {
      $returnProperties = array_merge(
        CRM_Grant_BAO_PaymentSearch::defaultReturnProperties(),
        [
          'id' => 1,
          'contact_id' => 1,
          'payment_created_date' => 1,
          'payment_date' => 1,
          'currency' => 1,
          'payment_reason' => 1,
          'replaces_payment_id' => 1,
        ]
      );
      $params = ['id' => $this->_id];
      $p = new CRM_Grant_BAO_PaymentSearch(
        CRM_Grant_BAO_PaymentSearch::convertFormValues($params),
        $returnProperties,
        NULL,
        FALSE,
        FALSE,
        CRM_Grant_BAO_PaymentSearch::MODE_GRANT_PAYMENT
      );
      $values = $p->searchQuery()->fetchAll()[0];
      $values['payment_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Grant_DAO_GrantPayment', 'payment_status_id', $values['payment_status_id']);
      $values['payable_to_address'] = CRM_Grant_BAO_GrantProgram::getAddress($values['contact_id'], NULL, TRUE);

      foreach (array_keys($returnProperties) as $token) {
        $this->assign($token, CRM_Utils_Array::value($token, $values));
      }

      $this->addButtons(array(
        array (
          'type' => 'cancel',
          'name' => ts('Cancel'),
          'isDefault' => TRUE)
        )
      );
    }
    else {
      $this->assign('action1', $this->_action);
      if ($this->_action == CRM_Grant_BAO_GrantPayment::STOP) {
        CRM_Utils_System::setTitle(ts('Stop Grants Payment'));
        CRM_Core_Session::setStatus(ts('Selected Grant Payment has been stopped successfully.'), '', 'no-popup');
      }
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
        ),
      ));
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
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/payment/reprint', 'reset=1&prid=' . $this->_id));
    }
    elseif ($this->_action & CRM_Core_Action::WITHDRAW) {
      $query = "SELECT cp.id as pid, cg.amount_granted as total_amount, cp.currency, cp.payment_reason, cp.contact_id as id, cep.entity_id as grant_id, cg.grant_program_id, cg.grant_type_id FROM civicrm_payment as cp LEFT JOIN civicrm_entity_payment as cep ON cep.payment_id = cp.id LEFT JOIN civicrm_grant as cg ON cg.id = cep.entity_id WHERE cp.id IN (".$this->_id.")";
      $grantDao = CRM_Grant_DAO_Grant::executeQuery($query);
      while ($grantDao->fetch()) {
        $ids['grant_id'] = $grantDao->grant_id;
        $grantParams['contact_id'] = $grantDao->id;
        $grantParams['grant_program_id'] = $grantDao->grant_program_id;
        $grantParams['grant_type_id'] = $grantDao->grant_type_id;
        $grantParams['id'] = $grantDao->grant_id;
        $grantParams['status_id'] = CRM_Core_OptionGroup::getValue('grant_status', 'Withdrawn', 'name');
        $grantParams['amount_total'] = $grantDao->total_amount;
        CRM_Grant_BAO_Grant::create($grantParams, $ids);
      }
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
