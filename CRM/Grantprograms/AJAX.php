<?php

class CRM_Grantprograms_AJAX {

  public static function getFinancialTransactionsList() {
    $sortMapper = array(
      0 => '',
      1 => '',
      2 => 'sort_name',
      3 => 'amount',
      4 => 'trxn_id',
      5 => 'transaction_date',
      6 => 'receive_date',
      7 => 'payment_method',
      8 => 'status',
      9 => 'name',
    );

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $return = isset($_REQUEST['return']) ? CRM_Utils_Type::escape($_REQUEST['return'], 'Boolean') : FALSE;
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric');
    $entityID = isset($_REQUEST['entityID']) ? CRM_Utils_Type::escape($_REQUEST['entityID'], 'String') : NULL;
    $notPresent = isset($_REQUEST['notPresent']) ? CRM_Utils_Type::escape($_REQUEST['notPresent'], 'String') : NULL;
    $statusID = isset($_REQUEST['statusID']) ? CRM_Utils_Type::escape($_REQUEST['statusID'], 'String') : NULL;
    $search = isset($_REQUEST['search']) ? TRUE : FALSE;

    $params = $_POST;
    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $returnvalues = array(
      'civicrm_financial_trxn.payment_instrument_id as payment_method',
      'civicrm_contribution.contact_id as contact_id',
      'civicrm_contribution.id as contributionID',
      'contact_a.sort_name',
      'civicrm_financial_trxn.total_amount as amount',
      'civicrm_financial_trxn.trxn_id as trxn_id',
      'contact_a.contact_type',
      'contact_a.contact_sub_type',
      'civicrm_financial_trxn.trxn_date as transaction_date',
      'civicrm_contribution.receive_date as receive_date',
      'civicrm_financial_type.name',
      'civicrm_financial_trxn.currency as currency',
      'civicrm_financial_trxn.status_id as status',
      'civicrm_financial_trxn.check_number as check_number',
      'civicrm_financial_trxn.card_type_id',
      'civicrm_financial_trxn.pan_truncation',
    );

    $columnHeader = array(
      'contact_type' => '',
      'sort_name' => ts('Contact Name'),
      'amount' => ts('Amount'),
      'trxn_id' => ts('Trxn ID'),
      'transaction_date' => ts('Transaction Date'),
      'receive_date' => ts('Received'),
      'payment_method' => ts('Payment Method'),
      'status' => ts('Status'),
      'name' => ts('Type'),
    );

    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $params['context'] = $context;
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = CRM_Utils_Array::value('sortBy', $params);
    $params['total'] = 0;

    // get batch list
    if (isset($notPresent)) {
      $financialItem = CRM_Grantprograms_Query::getBatchFinancialItems($entityID, $returnvalues, $notPresent, $params);
      if ($search) {
        $unassignedTransactions = CRM_Grantprograms_Query::getBatchFinancialItems($entityID, $returnvalues, $notPresent, $params, TRUE);
      }
      else {
        $unassignedTransactions = CRM_Grantprograms_Query::getBatchFinancialItems($entityID, $returnvalues, $notPresent, NULL, TRUE);
      }
      while ($unassignedTransactions->fetch()) {
        $unassignedTransactionsCount[] = $unassignedTransactions->id;
      }
      if (!empty($unassignedTransactionsCount)) {
        $params['total'] = count($unassignedTransactionsCount);
      }

    }
    else {
      $financialItem = CRM_Grantprograms_Query::getBatchFinancialItems($entityID, $returnvalues, NULL, $params);
      $assignedTransactions = CRM_Grantprograms_Query::getBatchFinancialItems($entityID, $returnvalues);
      while ($assignedTransactions->fetch()) {
        $assignedTransactionsCount[] = $assignedTransactions->id;
      }
      if (!empty($assignedTransactionsCount)) {
        $params['total'] = count($assignedTransactionsCount);
      }
    }
    $financialitems = array();
    if ($statusID) {
      $batchStatuses = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', array('labelColumn' => 'name', 'condition' => " v.value={$statusID}"));
      $batchStatus = $batchStatuses[$statusID];
    }
    while ($financialItem->fetch()) {
      $row[$financialItem->id] = array();
      foreach ($columnHeader as $columnKey => $columnValue) {
        if ($financialItem->contact_sub_type && $columnKey == 'contact_type') {
          $row[$financialItem->id][$columnKey] = $financialItem->contact_sub_type;
          continue;
        }
        $row[$financialItem->id][$columnKey] = $financialItem->$columnKey;
        if ($columnKey == 'sort_name' && $financialItem->$columnKey && $financialItem->contact_id) {
          $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=" . $financialItem->contact_id);
          $row[$financialItem->id][$columnKey] = '<a href=' . $url . '>' . $financialItem->$columnKey . '</a>';
        }
        elseif ($columnKey == 'payment_method' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Core_PseudoConstant::getLabel('CRM_Batch_BAO_Batch', 'payment_instrument_id', $financialItem->$columnKey);
          if ($row[$financialItem->id][$columnKey] == 'Check') {
            $checkNumber = $financialItem->check_number ? ' (' . $financialItem->check_number . ')' : '';
            $row[$financialItem->id][$columnKey] = $row[$financialItem->id][$columnKey] . $checkNumber;
          }
        }
        elseif ($columnKey == 'amount' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Utils_Money::format($financialItem->$columnKey, $financialItem->currency);
        }
        elseif ($columnKey == 'transaction_date' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Utils_Date::customFormat($financialItem->$columnKey);
        }
        elseif ($columnKey == 'receive_date' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Utils_Date::customFormat($financialItem->$columnKey);
        }
        elseif ($columnKey == 'status' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $financialItem->$columnKey);
        }
      }
      if (isset($batchStatus) && in_array($batchStatus, array('Open', 'Reopened'))) {
        if (isset($notPresent)) {
          $js = "enableActions('x')";
          $row[$financialItem->id]['check'] = "<input type='checkbox' id='mark_x_" . $financialItem->id . "' name='mark_x_" . $financialItem->id . "' value='1' onclick={$js}></input>";
          $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(
            CRM_Financial_Form_BatchTransaction::links(),
            NULL,
            array(
              'id' => $financialItem->id,
              'contid' => $financialItem->contributionID,
              'cid' => $financialItem->contact_id,
            ),
            ts('more'),
            FALSE,
            'financialItem.batch.row',
            'FinancialItem',
            $financialItem->id
          );
        }
        else {
          $js = "enableActions('y')";
          $row[$financialItem->id]['check'] = "<input type='checkbox' id='mark_y_" . $financialItem->id . "' name='mark_y_" . $financialItem->id . "' value='1' onclick={$js}></input>";
          $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(
            CRM_Financial_Page_BatchTransaction::links(),
            NULL,
            array(
              'id' => $financialItem->id,
              'contid' => $financialItem->contributionID,
              'cid' => $financialItem->contact_id,
            ),
            ts('more'),
            FALSE,
            'financialItem.batch.row',
            'FinancialItem',
            $financialItem->id
          );
        }
      }
      else {
        $row[$financialItem->id]['check'] = NULL;
        $tempBAO = new CRM_Financial_Page_BatchTransaction();
        $links = $tempBAO->links();
        unset($links['remove']);
        $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(
          $links,
          NULL,
          array(
            'id' => $financialItem->id,
            'contid' => $financialItem->contributionID,
            'cid' => $financialItem->contact_id,
          ),
          ts('more'),
          FALSE,
          'financialItem.batch.row',
          'FinancialItem',
          $financialItem->id
        );
      }
      if ($financialItem->contact_id) {
        $row[$financialItem->id]['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage(CRM_Utils_Array::value('contact_sub_type', $row[$financialItem->id]) ? $row[$financialItem->id]['contact_sub_type'] : CRM_Utils_Array::value('contact_type', $row[$financialItem->id]), FALSE, $financialItem->contact_id);
      }
      $financialitems = $row;
    }

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = array(
      'check',
      'contact_type',
      'sort_name',
      'amount',
      'trxn_id',
      'transaction_date',
      'receive_date',
      'payment_method',
      'status',
      'name',
      'action',
    );

    if ($return) {
      return CRM_Utils_JSON::encodeDataTableSelector($financialitems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    }
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($financialitems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

}
