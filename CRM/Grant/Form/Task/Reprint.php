<?php

/**
 * This class provides the functionality to reprint payments.
 */
class CRM_Grant_Form_Task_Reprint extends CRM_Grant_Form_PaymentTask {
  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific participation?
   *
   * @var boolean
   */
  protected $_single = false;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
    if (!CRM_Core_Permission::check('create payments in CiviGrant')) {
      CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) );
    }
    $validIDs = CRM_Utils_Array::collect('id', CRM_Core_DAO::executeQuery(
      sprintf(
        "SELECT DISTINCT id FROM civicrm_payment WHERE id IN (%s) AND payment_status_id IN (%s) ",
        implode(',', $this->_grantPaymentIds),
        implode(',', [
          CRM_Core_PseudoConstant::getKey('CRM_Grant_BAO_GrantPayment', 'payment_status_id', 'Printed'),
          CRM_Core_PseudoConstant::getKey('CRM_Grant_BAO_GrantPayment', 'payment_status_id', 'Reprinted'),
        ])
      )
    )->fetchAll());
    $invalidCounts = count($this->_grantPaymentIds) - count($validIDs);
    foreach ($this->_grantPaymentIds as $key => $id) {
      if (!in_array($id, $validIDs)) {
        unset($this->_grantPaymentIds[$key]);
      }
    }
    $message = '';
    if ($invalidCounts) {
      $message .= $invalidCounts . ' of the selected grant payments have already been stopped or cancelled.';
    }
    CRM_Core_Session::setStatus(ts($message . count($this->_grantPaymentIds) . ' of the selected grant payments found eligible to be reprinted.'));
  }

  function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Reprint Grants'));
    $validCount = (count($this->_grantPaymentIds) > 0);
    $this->assign('recordFound', $validCount);
    if ($validCount) {
      $this->addButtons([
        [
          'type'      => 'upload',
          'name'      => ts('Reprint Checks'),
          'isDefault' => TRUE,
        ],
        [
          'type'      => 'next',
          'name'      => ts('Export to CSV'),
        ],
        [
          'type'      => 'cancel',
          'name'      => ts('Cancel'),
        ],
      ]);
      CRM_Grant_Form_Task_GrantPayment::buildPaymentBlock($this, FALSE);
    }
    else {
      CRM_Core_Session::setStatus(ts('Please select at least one grant payment that has been printed.'));
      $this->addButtons([
        [
          'type'      => 'cancel',
          'name'      => ts('Back'),
        ],
      ]);
    }
  }

  public function postProcess() {
    $values = $this->controller->exportValues($this->_name);
    $makePdf = empty($_POST['_qf_Reprint_next']);
    $totalAmount = 0;
    $stoppedStatusID = CRM_Core_PseudoConstant::getKey('CRM_Grant_BAO_GrantPayment', 'payment_status_id', 'Stopped');
    $reprintedStatusID = CRM_Core_PseudoConstant::getKey('CRM_Grant_BAO_GrantPayment', 'payment_status_id', 'Reprinted');
    $trxnIDs = $printedRows = [];
    foreach ($this->_grantPaymentIds as $paymentId ) {
      $paymentDAO = new CRM_Grant_DAO_GrantPayment();
      $paymentDAO->id = $paymentId;
      $paymentDAO->payment_status_id = $stoppedStatusID;
      $paymentDAO->save();

      $paymentDAO->find(TRUE);
      $paymentDAO->payment_status_id = $reprintedStatusID;
      $trxnIDs[] = $financialTrxnID = $paymentDAO->financial_trxn_id;

      foreach (['check_number', 'trxn_date'] as $attr) {
        if (!empty($values[$attr])) {
          $financialParams[$attr] = $values[$attr];
        }
      }
      if (!empty($financialParams)) {
        $financialParams['id'] = $financialTrxnID;
        civicrm_api3('FinancialTrxn', 'create', $financialParams);
      }

      if (!empty($values['payment_reason'])) {
        $financialItemID = CRM_Core_DAO::singleValueQuery("SELECT entity_id FROM civicrm_entity_financial_trxn WHERE entity_table = 'civicrm_financial_item' AND financial_trxn_id = " . $financialTrxnID . " LIMIT 1");
        $financialParams = ['description' => $values['payment_reason']];
        $ids = ['id' => $financialTrxnID];
        CRM_Financial_BAO_FinancialItem::create($financialParams, $ids);
      }

      // if user decides to allot to different batch during reprint
      if (!empty($values['contribution_batch_id'])) {
        $params = [
          'entity_table' => 'civicrm_financial_trxn',
          'entity_id' => $financialTrxnID,
          'batch_id' => $values['contribution_batch_id'],
        ];
        $count = civicrm_api3('EntityBatch', 'get', $params)['count'];
        if ($count == 0) {
          civicrm_api3('EntityBatch', 'create', $params);
        }
      }

      $grantPaymentRecord = [
        'financial_trxn_id' => $financialTrxnID,
        'payment_created_date' => date('Y-m-d'),
        'payment_status_id' => $reprintedStatusID,
        'payment_reason' => CRM_Utils_Array::value('payment_reason', $values, $paymentDAO->payment_reason),
        'replaces_payment_id' => $paymentId,
      ];
      CRM_Grant_BAO_GrantPayment::add($grantPaymentRecord);
    }

    CRM_Grant_Form_Task_GrantPayment::printPayments($this, $trxnIDs, $reprintedStatusID, $makePdf);
  }

}
