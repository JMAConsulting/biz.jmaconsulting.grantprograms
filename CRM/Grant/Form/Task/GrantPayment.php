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

  public static function processPaymentDetails($params, $updateTrxn = TRUE) {
    $trxnID = $params['trxn_id'];
    civicrm_api3('EntityBatch', 'create', [
      'entity_table' => 'civicrm_financial_trxn',
      'entity_id' => $trxnID,
      'batch_id' => $params['batch_id'],
    ]);

    if ($updateTrxn) {
      civicrm_api3('FinancialTrxn', 'create', [
        'id' => $trxnID,
        'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'payment_instrument_id', 'Check'),
        'check_number' => CRM_Utils_Array::value('check_number', $params),
        'trxn_id' => CRM_Utils_Array::value('trxn_id', $params),
        'trxn_date' => CRM_Utils_Array::value('trxn_date', $params, date('YmdHis')),
      ]);
    }

    $grantPaymentRecord = [
      'financial_trxn_id' => $trxnID,
      'payment_created_date' => date('Y-m-d'),
      'payment_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Grant_DAO_GrantPayment', 'payment_status_id', 'Printed'),
      'payment_reason' => CRM_Utils_Array::value('description', $params),
    ];
    CRM_Grant_BAO_GrantPayment::add($grantPaymentRecord);
    return $grantPaymentRecord;
  }

  public function postProcess() {
    $values = $this->controller->exportValues($this->_name);
    $approvedGrants = $this->get('approvedGrants');
    $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $financialItemStatus = CRM_Core_PseudoConstant::accountOptionValues('financial_item_status');
    $checkID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'payment_instrument_id', 'Check');
    $mailParams = $printedRows = $files = $trxnIDs = [];
    $totalAmount = $counter = 0;

    $where = 'ft.to_financial_account_id IS NOT NULL AND ';
    if (!empty($this->_prid)) {
      $where .= " cp.id = " . $this->_prid;
    }
    elseif (!empty($approvedGrants)) {
      $where .= sprintf(" g.id IN (%s) ", implode(', ', array_keys($approvedGrants)));
    }

    $dao = CRM_Core_DAO::executeQuery(sprintf("
    SELECT ft.id as ft_id, g.id as grant_id, fi.id as fi_id, g.financial_type_id, ft.to_financial_account_id, fi.currency, gp.is_auto_email, ft.total_amount, fi.contact_id, g.grant_program_id, g.grant_type_id
      FROM civicrm_entity_financial_trxn eft
       INNER JOIN civicrm_financial_trxn ft ON ft.id = eft.financial_trxn_id AND eft.entity_table = 'civicrm_grant'
       INNER JOIN civicrm_grant g ON g.id = eft.entity_id
       INNER JOIN civicrm_entity_financial_trxn eft1 ON eft1.financial_trxn_id = ft.id AND eft1.entity_table = 'civicrm_financial_item'
       INNER JOIN civicrm_financial_item fi ON fi.id = eft1.entity_id
       INNER JOIN civicrm_grant_program gp ON gp.id = g.grant_program_id
       LEFT JOIN civicrm_payment cp  ON cp.financial_trxn_id = eft.financial_trxn_id
      WHERE %s GROUP BY ft.id ", $where));
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

      if ($dao->is_auto_email) {
        $mailParams = [
          'is_auto_email' => TRUE,
          'amount_total' => $dao->total_amount,
          'grant_type_id' => $dao->grant_type_id,
          'grant_program_id' => $dao->grant_program_id,
          'contact_id' => $dao->contact_id,
          'tplParams' => ['grant' => ['grant_programs' => $grantPrograms[$dao->grant_program_id]]],
        ];
        CRM_Grant_BAO_GrantProgram::sendMail($dao->contact_id, $mailParams, 'Paid', $grantID, 'Approved for Payment');
      }

      $grantPaymentRecord = self::processPaymentDetails([
        'trxn_id' => $trxnID,
        'batch_id' => $values['contribution_batch_id'],
        'description' => $description,
      ], FALSE);

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

    $contactID = CRM_Core_Session::getLoggedInContactID();
    $activityID = civicrm_api3('Activity', 'create', [
      'source_contact_id' => $contactID,
      'activity_type_id' => 'grant_payment',
      'assignee_contact_id' => $contactID,
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
    CRM_Core_Session::setStatus(ts('Please click the attached zip file to download the printed grant payments'));
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/activity', "action=view&reset=1&id=$activityID&cid=$contactID&context=activity&searchContext=activity"));
  }

}
