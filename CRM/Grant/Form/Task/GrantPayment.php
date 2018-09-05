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
 * This class generates form components for Payments
 *
 */
class CRM_Grant_Form_Task_GrantPayment extends CRM_Core_Form {

  protected $_id     = null;
  protected $_fields = null;
  function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this );
    $this->_prid = CRM_Utils_Request::retrieve('prid', 'Positive', $this );
    if ( $this->_prid ) {
      $session = CRM_Core_Session::singleton();
      $url = CRM_Utils_System::url('civicrm/grant/payment/search', '_qf_PaymentSearch_display=true&force=1&reset=1');
      $session->pushUserContext( $url );
    }
  }

  /**
   * Get payment fields
   */
  public static function getPaymentFields($print) {
    return array(
      'check_number' => array(
        'is_required' => $print,
        'add_field' => TRUE,
      ),
      'trxn_id' => array(
        'add_field' => TRUE,
        'is_required' => FALSE,
      ),
      'description' => array(
        'htmlType' => 'textarea',
        'name' => 'description',
        'title' => ts('Payment reason'),
        'is_required' => FALSE,
        'attributes' => [],
      ),
      'trxn_date' => array(
        'htmlType' => 'datepicker',
        'name' => 'trxn_date',
        'title' => ts('Payment date to appear on cheques'),
        'is_required' => $print,
        'attributes' => array(
          'date' => 'yyyy-mm-dd',
          'time' => 24,
          'context' => 'create',
          'action' => 'create',
        ),
      ),
      'contribution_batch_id' => [
        'htmlType' => 'select',
        'name' => 'contribution_batch_id',
        'title' => ts('Assign to Batch'),
        'attributes' => ['' => ts('None')] + CRM_Contribute_PseudoConstant::batch(),
        'is_required' => $print,
      ],
    );
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Print Grants'));
    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
        [
          'type'      => 'next',
          'name'      => ts('Delete'),
          'isDefault' => TRUE,
        ],
        [
          'type'      => 'cancel',
          'name'      => ts('Cancel'),
        ],
      ]);
      return;
    }
    self::buildPaymentBlock($this);

    $buttonName = $this->_prid ? 'Reprint Checks and CSV Export' : 'Create Checks and CSV Export';
    $this->addButtons([
      [
        'type'      => 'upload',
        'name'      => ts($buttonName),
        'isDefault' => TRUE,
      ],
      [
        'type'      => 'cancel',
        'name'      => ts('Cancel'),
      ],
    ]);
  }

  public static function buildPaymentBlock($form, $print = TRUE) {
    $paymentFields = self::getPaymentFields($print);
    $form->assign('paymentFields', $paymentFields);
    foreach ($paymentFields as $name => $paymentField) {
      if (!empty($paymentField['add_field'])) {
        $attributes = array(
          'entity' => 'FinancialTrxn',
          'name' => $name,
          'context' => 'create',
          'action' => 'create',
        );
        $form->addField($name, $attributes, $paymentField['is_required']);
      }
      else {
        $form->add($paymentField['htmlType'],
          $name,
          $paymentField['title'],
          $paymentField['attributes'],
          $paymentField['is_required']
        );
      }
    }
  }

  public function postProcess() {
    $values = $this->controller->exportValues($this->_name);
    $approvedGrants = $this->get('approvedGrants');
    $approvedGrantIDs = array_keys($approvedGrants);
    $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $financialItemStatus = CRM_Core_PseudoConstant::accountOptionValues('financial_item_status');
    $checkID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'payment_instrument_id', 'Check');
    $mailParams = $printedRows = $files = $trxnIDs = [];
    $totalAmount = $counter = 0;

    $dao = CRM_Core_DAO::executeQuery(sprintf("
    SELECT ft.id as ft_id, g.id as grant_id, fi.id as fi_id, g.financial_type_id, ft.to_financial_account_id, fi.currency, gp.is_auto_email, ft.total_amount, fi.contact_id, g.grant_program_id
      FROM civicrm_entity_financial_trxn eft
       INNER JOIN civicrm_financial_trxn ft ON ft.id = eft.financial_trxn_id AND eft.entity_table = 'civicrm_grant'
       INNER JOIN civicrm_grant g ON g.id = eft.entity_id
       INNER JOIN civicrm_entity_financial_trxn eft1 ON eft1.financial_trxn_id = ft.id AND eft1.entity_table = 'civicrm_financial_item'
       INNER JOIN civicrm_financial_item fi ON fi.id = eft1.entity_id
       INNER JOIN civicrm_grant_program gp ON gp.id = g.grant_program_id
      WHERE g.id IN (%s) GROUP BY ft.id ", implode(', ', $approvedGrantIDs)));
    while($dao->fetch()) {
      $totalAmount += $dao->total_amount;
      $grantID = $dao->grant_id;
      $financialTrxnParams = [
        'from_financial_account_id' => $dao->to_financial_account_id,
        'to_financial_account_id' => CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($dao->financial_type_id, 'Asset Account is') ?: CRM_Grant_BAO_GrantProgram::getAssetFinancialAccountID(),
        'trxn_date' => CRM_Utils_Array::value('trxn_date', $values, date('YmdHis')),
        'trxn_id' => CRM_Utils_Array::value('trxn_id', $values),
        'total_amount' => $dao->total_amount,
        'currency' => $dao->currency,
        'check_number' => $values['check_number'],
        'payment_instrument_id' => $checkID,
        'status_id' => array_search('Completed', $contributionStatuses),
        'entity_table' => 'civicrm_grant',
        'entity_id' => $grantID,
      ];
      $trxnID = civicrm_api3('FinancialTrxn', 'create', $financialTrxnParams)['id'];
      $trxnIDs[] = $trxnID;

      $description = CRM_Utils_Array::value('description', $values, $grantPrograms[$dao->grant_program_id]);
      $financialParams = ['description' => $description, 'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_FinancialItem', 'status_id', 'Paid'), 'amount' => $dao->total_amount];
      $ids = ['id' => $dao->fi_id];
      $trxnids = ['id' => $trxnID];
      CRM_Financial_BAO_FinancialItem::create($financialParams, $ids, $trxnids);

      civicrm_api3('EntityBatch', 'create', [
        'entity_table' => 'civicrm_financial_trxn',
        'entity_id' => $trxnID,
        'batch_id' => $values['contribution_batch_id'],
      ]);

      if ($dao->is_auto_email) {
        $mailParams = [
          'is_auto_email' => TRUE,
          'amount_total' => $dao->total_amount,
          'grant_type_id' => $approvedGrants[$grantID]['grant_type_id'],
          'grant_program_id' => $approvedGrants[$grantID]['grant_program_id'],
          'contact_id' => $dao->contact_id,
          'tplParams' => ['grant' => ['grant_programs' => $grantPrograms[$dao->grant_program_id]]],
        ];
        CRM_Grant_BAO_GrantProgram::sendMail($dao->contact_id, $mailParams, 'Paid', $grantID, 'Approved for Payment');
      }

      $grantPaymentRecord = [
        'financial_trxn_id' => $trxnID,
        'payment_created_date' => date('Y-m-d'),
        'payment_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Grant_DAO_GrantPayment', 'payment_status_id', 'Printed'),
        'payment_reason' => $description,
      ];
      CRM_Grant_BAO_GrantPayment::add($grantPaymentRecord);


      $printedRows[$grantID] = [
        'contact_id' => $dao->contact_id,
        'financial_type_id' => $dao->financial_type_id,
        'payment_batch_number' => $values['contribution_batch_id'],
        'payment_number' => $values['check_number'],
        'payment_date' => date("Y-m-d", strtotime($values['trxn_date'])),
        'payment_created_date' => $grantPaymentRecord['payment_created_date'],
        // TODO remove CRM_Grant_BAO_GrantProgram::getDisplayName
        'payable_to_name' => CRM_Contact_BAO_Contact::displayName($dao->contact_id),
        'payable_to_address' => CRM_Utils_Array::value('address', CRM_Grant_BAO_GrantProgram::getAddress($dao->contact_id, NULL, TRUE)),
        'amount' => $dao->total_amount,
        'curreny' => $dao->currency,
        'payment_reason' => CRM_Utils_Array::value('description', $values, $grantPrograms[$dao->grant_program_id]),
        'payment_status_id' => $grantPaymentRecord['payment_status_id'],
        'replaces_payment_id' => NULL,
        'payment_details' => sprintf(
          '%s </td><td>%s</td><td>%s</td><td>%s',
          date("Y-m-d", strtotime($values['trxn_date'])),
          $dao->grant_id,
          CRM_Contact_BAO_Contact::displayName($dao->contact_id),
          CRM_Utils_Money::format($dao->total_amount, NULL, NULL, FALSE)
        ),
        'total_in_words' => CRM_Grant_BAO_GrantProgram::convertNumberToWords($dao->total_amount),
      ];

      CRM_Core_DAO::executeQuery(sprintf('UPDATE civicrm_grant SET status_id = %d WHERE id = %d',
        $grantID,
        array_search('Paid', CRM_Core_OptionGroup::values('grant_status'))
      ));
    }

    self::printPayments($this, $trxnIDs, CRM_Core_PseudoConstant::getKey('CRM_Grant_DAO_GrantPayment', 'payment_status_id', 'Printed'));
  }

  public static function printPayments($form, $trxnIDs, $statusID, $printPDF = TRUE) {
    $totalAmount = 0;
    $batchID = NULL;
    $dao = CRM_Core_DAO::executeQuery(sprintf("
    SELECT ft.id as ft_id, g.id as grant_id, fi.id as fi_id, g.financial_type_id, ft.to_financial_account_id, fi.currency, gp.is_auto_email, ft.total_amount, fi.contact_id, g.grant_program_id, fi.description, ft.trxn_date, ft.check_number, eb.batch_id
      FROM civicrm_entity_financial_trxn eft
       INNER JOIN civicrm_financial_trxn ft ON ft.id = eft.financial_trxn_id AND eft.entity_table = 'civicrm_grant'
       INNER JOIN civicrm_grant g ON g.id = eft.entity_id
       INNER JOIN civicrm_entity_financial_trxn eft1 ON eft1.financial_trxn_id = ft.id AND eft1.entity_table = 'civicrm_financial_item'
       INNER JOIN civicrm_financial_item fi ON fi.id = eft1.entity_id
       INNER JOIN civicrm_grant_program gp ON gp.id = g.grant_program_id
       INNER JOIN civicrm_entity_batch eb ON eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn'
      WHERE ft.id IN (%s) GROUP BY ft.id ", implode(', ', $trxnIDs)));
    while($dao->fetch()) {
      $batchID = $dao->batch_id;
      $printedRows[$dao->ft_id] = [
        'contact_id' => $dao->contact_id,
        'financial_type_id' => $dao->financial_type_id,
        'payment_batch_number' => $dao->batch_id,
        'payment_number' => $dao->check_number,
        'payment_date' => date("Y-m-d", strtotime($dao->trxn_date)),
        'payment_created_date' => date('Y-m-d'),
        // TODO remove CRM_Grant_BAO_GrantProgram::getDisplayName
        'payable_to_name' => CRM_Contact_BAO_Contact::displayName($dao->contact_id),
        'payable_to_address' => CRM_Utils_Array::value('address', CRM_Grant_BAO_GrantProgram::getAddress($dao->contact_id, NULL, TRUE)),
        'amount' => $dao->total_amount,
        'curreny' => $dao->currency,
        'payment_reason' => $dao->description,
        'payment_status_id' => $statusID,
        'replaces_payment_id' => NULL,
        'payment_details' => sprintf(
          '%s </td><td>%s</td><td>%s</td><td>%s',
          date("Y-m-d", strtotime($dao->trxn_date)),
          $dao->grant_id,
          CRM_Contact_BAO_Contact::displayName($dao->contact_id),
          CRM_Utils_Money::format($dao->total_amount, NULL, NULL, FALSE)
        ),
        'total_in_words' => CRM_Grant_BAO_GrantProgram::convertNumberToWords($dao->total_amount),
      ];
      $totalAmount += $dao->total_amount;
    }
    $form->assign('grantPayment', $printedRows);

    $maxLimit = CRM_Utils_Array::value('Maximum number of checks per pdf file', CRM_Core_OptionGroup::values('grant_thresholds', TRUE));
    $config = CRM_Core_Config::singleton();
    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    if ($printPDF) {
      $counter = 0;
      foreach (array_chunk($printedRows, $maxLimit, TRUE) as $payments) {
        $downloadNamePDF = implode('_', [
          check_plain('grantPayment'),
          date('Ymdhis'),
          $counter
        ]) . '.pdf';
        $fileName = CRM_Utils_File::makeFileName($downloadNamePDF);
        $files[] = $fileName = $config->customFileUploadDir . CRM_Grant_BAO_GrantPayment::makePDF($fileName, $payments);
        $counter++;
      }
    }

    $downloadNameCSV = implode('_', [
      check_plain('grantPayment'),
      date('Ymdhis')
    ]) . '.csv';
    $fileName = CRM_Utils_File::makeFileName($downloadNameCSV);
    CRM_Grant_BAO_GrantPayment::createCSV($config->customFileUploadDir . $fileName, $printedRows);
    $files[] = $config->customFileUploadDir . $fileName;
    $fileID = civicrm_api3('File', 'create', [
      'name' => basename($fileName),
      'mime_type' => 'text/csv',
      'uri' => $fileName,
    ])['id'];
    $entityFileDAO->entity_table = 'civicrm_contact';
    $entityFileDAO->entity_id = CRM_Core_Session::getLoggedInContactID();
    $entityFileDAO->file_id = $fileID;
    $entityFileDAO->save();

    $form->assign('date', date('Y-m-d'));
    $form->assign('time', date('H:i:s'));
    $form->assign('batch_number', $batchID);
    $form->assign('contact', CRM_Contact_BAO_Contact::displayName(CRM_Core_Session::getLoggedInContactID()));
    $form->assign('total_payments', count($printedRows));
    $form->assign('total_amount' , CRM_Utils_Money::format($totalAmount, NULL, NULL,FALSE));
    $form->assign('domain_name', CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', CRM_Core_Config::domainID(), 'name'));
    $checkFile = CRM_Utils_File::makeFileName(check_plain('CheckRegister') . '.pdf');
    $checkRegister = CRM_Grant_BAO_GrantPayment::makeReport($checkFile, $printedRows);
    $files[] = $config->customFileUploadDir . $checkRegister;

    //make Zip
    $zipFile = check_plain('GrantPayment') . '_' . date('Ymdhis') . '.zip';
    foreach($files as $file) {
      $source[] = $config->customFileUploadDir . $file;
    }

    $uri = $config->customFileUploadDir . $zipFile;
    $zip = CRM_Financial_BAO_ExportFormat::createZip($files, $uri);
    $fileID = civicrm_api3('File', 'create', [
      'name' => basename($fileName),
      'mime_type' => 'application/zip',
      'uri' => $zipFile,
    ])['id'];
    $entityFileDAO->entity_table = 'civicrm_contact';
    $entityFileDAO->entity_id = CRM_Core_Session::getLoggedInContactID();
    $entityFileDAO->file_id = $fileID;
    $entityFileDAO->save();

    $activityID = civicrm_api3('Activity', 'create', [
      'source_contact_id' => CRM_Core_Session::getLoggedInContactID(),
      'activity_type_id' => 'grant_payment',
      'assignee_contact_id' => CRM_Core_Session::getLoggedInContactID(),
      'subject' => "Grant Payment",
      'status_id' => 'Completed',
      'priority_id' => 2,
    ])['id'];
    $params = array(
      'id' => $activityID,
      'attachFile_1' => array (
        'uri' => $uri,
        'type' => 'text/csv',
        'location' => $uri,
        'upload_date' => date('YmdHis'),
      ),
    );
    CRM_Activity_BAO_Activity::create($params);

    // download the zip file
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/zip');
    CRM_Utils_System::setHttpHeader('Content-Disposition', 'attachment; filename=' . CRM_Utils_File::cleanFileName(basename($zipFile)));
    CRM_Utils_System::setHttpHeader('Content-Length', '' . filesize($uri));
    ob_clean();
    flush();
    readfile($uri);
    CRM_Utils_System::civiExit();
  }


  /**
   * Function to process the form
   *
   * @access public
   * @return None
  public function postProcess()
  {
    $details = $allGrants = $grantPayments = $grantAmount = array();
    $grandTotal = 0;
    CRM_Utils_System::flushCache( 'CRM_Grant_DAO_GrantPayment' );
    $values  = $this->controller->exportValues( $this->_name );
    $batchNumber = $values['payment_batch_number'];
    $this->_approvedGrants = $this->get( 'approvedGrants' );
    $grantThresholds = CRM_Core_OptionGroup::values('grant_thresholds', TRUE);
    $maxLimit = $grantThresholds['Maximum number of checks per pdf file'];

    if ( $this->_prid ) {
      $query = "SELECT cp.id as pid, cg.amount_granted as total_amount, cp.currency, cp.payment_reason, " .
        "cp.contact_id as id, cep.entity_id as grant_id FROM civicrm_payment as cp " .
        "LEFT JOIN civicrm_entity_payment as cep ON cep.payment_id = cp.id " .
        "LEFT JOIN civicrm_grant as cg ON cg.id = cep.entity_id WHERE cp.id IN (".$this->_prid.")";
      $countQuery = "SELECT COUNT(cp.id) as ids FROM civicrm_payment as cp " .
        "LEFT JOIN civicrm_entity_payment as cep ON cep.payment_id = cp.id " .
        "LEFT JOIN civicrm_grant as cg ON cg.id = cep.entity_id WHERE cp.id IN (".$this->_prid.")";
    } else {
      $query = "SELECT id as grant_id, amount_granted as total_amount, currency, grant_program_id, " .
        "application_received_date, grant_type_id, contact_id as id FROM civicrm_grant " .
        "WHERE id IN (".implode(', ', array_keys($this->_approvedGrants) ).")";
      $countQuery = "SELECT COUNT(id) as grant_id FROM civicrm_grant WHERE id IN (".implode(', ', array_keys($this->_approvedGrants) ).")";
    }
    $daoCount = CRM_Grant_DAO_Grant::singleValueQuery($countQuery);

    $dao = CRM_Grant_DAO_Grant::executeQuery($query);
    $grantPayment = $payment_details = $amountsTotal = $details = array();
    while ($dao->fetch()) {
      if (isset($amountsTotal[$dao->id])) {
        $amountsTotal[$dao->id] += $dao->total_amount;
      } else {
        $amountsTotal[$dao->id] = $dao->total_amount;
      }
      if (!empty($payment_details[$dao->id])) {
        // List all payments on this cheque (if contact has multiple applications being paid)
        $payment_details[$dao->id] .= '</td></tr><tr><td width="15%" >' .
          date("Y-m-d", strtotime($values['payment_date'])) . '</td><td width="15%" >' .
          $dao->grant_id . '</td><td width="50%" >' . CRM_Grant_BAO_GrantProgram::getDisplayName($dao->id) .
          '</td><td width="20%" >' . CRM_Utils_Money::format($dao->total_amount, null, null, false);
      } else {
        $payment_details[$dao->id] = date("Y-m-d", strtotime($values['payment_date'])) . '</td><td width="15%" >' .
          $dao->grant_id . '</td><td width="50%" >' . CRM_Grant_BAO_GrantProgram::getDisplayName($dao->id) .
          '</td><td width="20%" >' . CRM_Utils_Money::format($dao->total_amount, null, null, false);
      }

      // Aggregate payments per contact id
      if (!empty($details[$dao->id]['total_amount'])) {
        $details[$dao->id]['total_amount'] += $dao->total_amount;
      } else {
        $details[$dao->id]['total_amount'] = $dao->total_amount;
      }
      $details[$dao->id]['currency'] = $dao->currency;

      $contactGrants[$dao->grant_id] = $dao->id;
      if (!array_key_exists($dao->id, $grantAmount)) {
        $grantAmount[$dao->id] = 0;
      }
      $grantAmount[$dao->id] += $dao->total_amount;
      if (!$this->_prid) {
        $grantProgramSql = "SELECT is_auto_email FROM civicrm_grant_program WHERE id  = " . $dao->grant_program_id;
        $mailParams[$dao->grant_id]['is_auto_email'] = CRM_Grant_DAO_GrantProgram::singleValueQuery($grantProgramSql);
        $mailParams[$dao->grant_id]['amount_total'] = $dao->total_amount;
        $mailParams[$dao->grant_id]['grant_type_id'] = $dao->grant_type_id;
        $mailParams[$dao->grant_id]['grant_program_id'] = $dao->grant_program_id;
        $grantContctId[$dao->grant_id] = $dao->id;
        $gProgram = CRM_Grant_BAO_GrantProgram::getGrantPrograms($dao->grant_program_id);
        if (!empty($gProgram)) {
          $details[$dao->id]['grant_program_id'][$gProgram[$dao->grant_program_id]] = $gProgram[$dao->grant_program_id];
        }
      }
      else {
        $details[$dao->id]['payment_reason'][$dao->payment_reason] = $dao->payment_reason;
      }
    }

    $totalAmount = 0;
    $words = new CRM_Grant_Words();
    foreach ($details as $id => $value) {
      $grantPayment[$id]['contact_id'] = $id;
      $grantPayment[$id]['financial_type_id'] = $values['financial_type_id'];
      $grantPayment[$id]['payment_batch_number'] = $values['payment_batch_number'];
      $grantPayment[$id]['payment_number'] = $values['payment_number'];
      $grantPayment[$id]['payment_date'] = date("Y-m-d", strtotime($values['payment_date']));
      $grantPayment[$id]['payment_created_date'] = date('Y-m-d');
      $grantPayment[$id]['payable_to_name'] = CRM_Grant_BAO_GrantProgram::getDisplayName($id);
      $grantPayment[$id]['payable_to_address'] =
        CRM_Utils_Array::value('address', CRM_Grant_BAO_GrantProgram::getAddress($id, null, true));
      $grantPayment[$id]['amount'] = $details[$id]['total_amount'];
      $grantPayment[$id]['currency'] = $details[$id]['currency'];
      $grantPayment[$id]['payment_status_id'] = 1;
      if ($this->_prid) {
        $grantPayment[$id]['payment_reason'] = implode(', ', $details[$id]['payment_reason']);
        $grantPayment[$id]['replaces_payment_id'] = $this->_prid;
        $grantPayment[$id]['payment_status_id'] = CRM_Core_OptionGroup::getValue('grant_payment_status', 'Reprinted', 'name');
      } else {
        $grantPayment[$id]['payment_reason'] = implode(', ', $details[$id]['grant_program_id']);
        $grantPayment[$id]['replaces_payment_id'] = 'NULL';
      }
      $grantPayment[$id]['payment_details'] = $payment_details[$id];
      $values['payment_number']++;
      $totalAmount += $details[$id]['total_amount'];
    }

    foreach ($grantPayment as $grantKey => $grantInfo) {
      $grantValues = $grantInfo;
      if ($this->_prid) {
        require_once 'CRM/Grant/DAO/GrantPayment.php';
        $dao = new CRM_Grant_DAO_GrantPayment();
        $dao->id = $this->_prid;
        $dao->payment_status_id = CRM_Core_OptionGroup::getValue('grant_payment_status', 'Stopped', 'name');
        $dao->save();
      }
      require_once 'CRM/Grant/Words.php';
      $words = new CRM_Grant_Words();
      $amountInWords = ucwords($words->convert_number_to_words($grantInfo['amount']));
      $grantPayment[$grantKey]['total_in_words'] = $grantInfo['total_in_words'] =
      $grantValues['total_in_words'] = $amountInWords;
      $grantPayment[$grantKey]['amount'] = $grantInfo['amount'];
      // Save payment
      $savePayment = $grantPayment[$grantKey];
      $savePayment['payable_to_address'] = str_replace('<br /> ', '', $savePayment['payable_to_address']);
      $result = CRM_Grant_BAO_GrantPayment::add($savePayment, $ids = array());

      $grantPayment[$grantKey]['payment_id'] = $result->payment_number;
      $contactPayments[$grantKey] = $result->id;
      unset($grantPayment[$grantKey]['payment_status_id']);
    }
    $grandTotal += $totalAmount;

    // Split payments into multiple files based on given limit
    $splitPayments = array_chunk($grantPayment, $maxLimit, TRUE);

    $counter = 0;
    foreach ($splitPayments as $payments) {
      $this->assign('grantPayment', $payments);
      $downloadNamePDF = check_plain('grantPayment');
      $downloadNamePDF .= '_' . date('Ymdhis') . '_' . $counter;
      $downloadNamePDF .= '.pdf';
      $fileName = CRM_Utils_File::makeFileName($downloadNamePDF);
      $files[] = $fileName = CRM_Grant_BAO_GrantPayment::makePDF($fileName, $payments);
      $counter++;
    }

    $grantPayments += $grantPayment;

    $downloadNameCSV = check_plain('grantPayment');
    $downloadNameCSV .= '_'.date('Ymdhis');
    $this->assign('grantPayment', $grantPayments);
    $downloadNameCSV .= '.csv';
    $fileName = CRM_Utils_File::makeFileName( $downloadNameCSV );
    $config = CRM_Core_Config::singleton();
    $file_name = $config->customFileUploadDir . $fileName;
    foreach($grantAmount as $id => $value) {
      $grantPayments[$id]['amount'] = $value;
    }
    CRM_Grant_BAO_GrantPayment::createCSV($file_name, $grantPayments);
    $files[] = $fileName;

    $this->assign('date', date('Y-m-d'));
    $this->assign('time', date('H:i:s'));
    $this->assign('account_name',CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $values['financial_type_id'], 'name'));
    $this->assign('batch_number', $values['payment_batch_number']);
    $this->assign('contact',CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $_SESSION[ 'CiviCRM' ][ 'userID' ], 'display_name'));
    $this->assign('total_payments', count($grantPayment));
    $this->assign('total_amount' , CRM_Utils_Money::format($grandTotal, NULL, NULL,FALSE));
    $this->assign('domain_name', CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain', CRM_Core_Config::domainID(), 'name'));
    $checkRegisterFile = check_plain('CheckRegister');
    $checkRegisterFile .= '.pdf';
    $checkFile = CRM_Utils_File::makeFileName( $checkRegisterFile );
    $checkRegister = CRM_Grant_BAO_GrantPayment::makeReport( $checkFile, $grantPayment );
    $files[] = $checkRegister;


    $fileDAO = new CRM_Core_DAO_File();
    $fileDAO->uri           = $fileName;
    $fileDAO->mime_type = 'application/zip';
    $fileDAO->upload_date   = date('Ymdhis');
    $fileDAO->save();
    $grantPaymentFile = $fileDAO->id;

    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    $entityFileDAO->entity_table = 'civicrm_contact';
    $entityFileDAO->entity_id    = $_SESSION[ 'CiviCRM' ][ 'userID' ];
    $entityFileDAO->file_id      = $grantPaymentFile;
    $entityFileDAO->save();

    $fileDAO->uri           = $checkFile;
    $fileDAO->upload_date   = date('Ymdhis');
    $fileDAO->save();
    $grantPaymentCheckFile = $fileDAO->id;

    $entityFileDAO = new CRM_Core_DAO_EntityFile();
    $entityFileDAO->entity_table = 'civicrm_contact';
    $entityFileDAO->entity_id    = $_SESSION[ 'CiviCRM' ][ 'userID' ];
    $entityFileDAO->file_id      = $grantPaymentCheckFile;
    $entityFileDAO->save();

    //make Zip
    $zipFile  =  check_plain('GrantPayment').'_'.date('Ymdhis').'.zip';
    foreach($files as $file) {
      $source[] = $config->customFileUploadDir.$file;
    }
    $zip = CRM_Financial_BAO_ExportFormat::createZip($source, $config->customFileUploadDir.$zipFile);
    rename($config->customFileUploadDir.$zipFile, $config->uploadDir.$zipFile);
    foreach($source as $sourceFile) {
      unlink($sourceFile);
    }

    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $activityType = CRM_Core_PseudoConstant::activityType();
    $params = array(
      'source_contact_id' => $_SESSION['CiviCRM']['userID'],
      'activity_type_id' => array_search('Grant Payment', $activityType),
      'assignee_contact_id' => $_SESSION['CiviCRM']['userID'],
      'subject' => "Grant Payment",
      'activity_date_time' => date('Ymdhis'),
      'status_id' => array_search('Completed', $activityStatus),
      'priority_id' => 2,
      'attachFile_1' => array (
        'uri' => $config->uploadDir.$zipFile,
        'type' => 'text/csv',
        'location' => $config->uploadDir.$zipFile,
        'upload_date' => date('YmdHis'),
      ),
    );
    CRM_Activity_BAO_Activity::create($params);

    require_once 'CRM/Grant/DAO/EntityPayment.php';
    if ( $this->_prid ) {
      foreach( $contactGrants as $grantId => $contact ) {
        $entityDAO = new CRM_Grant_DAO_EntityPayment();
        $entityDAO->entity_table = 'civicrm_grant';
        $entityDAO->entity_id    = $grantId;
        $entityDAO->payment_id   = $contactPayments[$contact];
        $entityDAO->save();
      }
      CRM_Core_Session::setStatus( "Selected payment stopped and reprinted successfully.");
    } else {
      foreach ( $this->_approvedGrants as $grantId => $status ) {
        $grantDAO = new CRM_Grant_DAO_Grant();
        $grantDAO->id        = $grantId;
        $grantDAO->status_id = CRM_Core_OptionGroup::getValue( 'grant_status', 'Paid', 'name' );
        $grantDAO->save();
        $entityDAO = new CRM_Grant_DAO_EntityPayment();
        $entityDAO->entity_table = 'civicrm_grant';
        $entityDAO->entity_id    = $grantId;
        $entityDAO->payment_id   = $contactPayments[$contactGrants[$grantId]];
        $entityDAO->save();
        $status_id = array_search('Paid', CRM_Core_OptionGroup::values('grant_status'));
        $grantType   = CRM_Core_OptionGroup::values( 'grant_type' );
        $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
        $this->assign( 'grant_type', $grantType[$mailParams[$grantId]['grant_type_id']] );
        $this->assign( 'grant_programs', $grantPrograms[$mailParams[$grantId]['grant_program_id']] );
        $this->assign( 'grant_status', 'Paid' );
        $this->assign( 'params', $mailParams[$grantId] );
        $mailParams[$grantId]['status_id'] = $status_id;
        CRM_Grant_BAO_GrantProgram::sendMail($grantContctId[$grantId], $mailParams[$grantId], 'Paid', $grantId, 'Approved for Payment');
      }
      CRM_Core_Session::setStatus( "Created ".count($details)." payments to pay for ".count($this->_approvedGrants)." grants to ".count($details)." applicants." );
    }
    CRM_Utils_System::redirect(CRM_Utils_System::url( 'civicrm/grant/payment/search', 'reset=1&bid='.$batchNumber.'&download='.$zipFile.'&force=1'));
  }
  */
}
