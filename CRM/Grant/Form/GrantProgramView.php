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
 * This class generates form components for processing a Grant
 *
 */
class CRM_Grant_Form_GrantProgramView extends CRM_Core_Form {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $values = array();
    $params['id'] = $this->_id;
    CRM_Grant_BAO_GrantProgram::retrieve($params, $values);
    $grantPrograms = CRM_Grant_BAO_GrantProgram::grantPrograms();
    $contributionTypes = CRM_Grant_BAO_GrantProgram::contributionTypes();
    $this->assign('grantType', CRM_Grant_BAO_GrantProgram::getOptionName($values['grant_type_id'] ));
    $this->assign('grantProgramStatus', CRM_Grant_BAO_GrantProgram::getOptionName($values['status_id']));
    $this->assign('contributionType', $contributionTypes[$values['financial_type_id']] );
    $this->assign('grantProgramAlgorithm', CRM_Grant_BAO_GrantProgram::getOptionName( $values['allocation_algorithm']));
    $this->assign('grant_program_id', empty($grantPrograms[$values['grant_program_id']]) ? NULL : $grantPrograms[$values['grant_program_id']]);
    $grantTokens = array('label','name','total_amount','from_email_address',
      'remainder_amount', 'allocation_date', 'is_active', 'is_auto_email');

    foreach ($grantTokens as $token) {
      $this->assign($token, htmlspecialchars(CRM_Utils_Array::value($token, $values)));
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
    $this->addButtons(array(
      array (
        'type'      => 'cancel',
        'name'      => ts('Done'),
        'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
        'isDefault' => TRUE)
      )
    );
  }

  public function allocate() {
    $grantStatus = CRM_Core_OptionGroup::values('grant_status', TRUE);
    $algoName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $_POST['algorithm'], 'grouping', 'name');
    $statuses = [$grantStatus['Eligible']];
    if ($algoName == 'immediate') {
      $statuses = array_merge($statuses, [
        $grantStatus['Awaiting Information'],
        $grantStatus['Submitted']
      ]);
    }

    $result = civicrm_api3('Grant', 'get', [
      'status_id' => [
        'IN' => $statuses,
        'grant_program_id' => $_POST['pid'],
        'assessment' => ['IS NOT NULL' => 1],
      ]
    ])['values'];

    if (!empty($result)) {
      if (trim($_POST['algorithm']) == 'Best To Worst, Fully Funded') {
        $order = CRM_Utils_Array::collect('assessment', $result);
        $sort_order = SORT_DESC;
        array_multisort($order, $sort_order, $result);
      }

      $totalAmount = $_POST['remainder_amount'];

      $contact = array();
      $grantThresholds = CRM_Core_OptionGroup::values('grant_thresholds', TRUE);
      foreach ($result as $key => $value) {
        $value['amount_total'] = CRM_Utils_Rule::cleanMoney($value['amount_total']);
        $userparams['contact_id'] = $value['contact_id'];
        $userparams['grant_program_id'] = $_POST['pid'];
        //FIXME pass grant id instead of NULL
        $amountGranted = CRM_Grant_BAO_GrantProgram::getUserAllocatedAmount($userparams, $value['grant_id']);
        if ($_POST['algorithm'] == 'Best To Worst, Fully Funded') {
          $amountEligible = $grantThresholds['Maximum Grant'] - $amountGranted;
          if ($value['amount_total'] > $amountEligible) {
            if ($amountEligible <= $totalAmount) {
              $grant['granted'][] = $amountEligible;
              $totalAmount = $totalAmount - ($amountEligible - $value['amount_granted']);
              $value['amount_granted'] = $amountEligible;
            }
            else {
              $grant['eligible'][] = $value['amount_granted'];
              continue;
            }
          }
          else {
            if ($value['amount_total'] <= $totalAmount) {
              $grant['granted'][] = $value['amount_total'];
              $totalAmount = $totalAmount - ($value['amount_total'] - $value['amount_granted']);
              $value['amount_granted'] = $value['amount_total'];
            }
            else {
              $grant['eligible'][] = $value['amount_granted'];
              continue;
            }
          }
          $ids['grant_id'] = $value['grant_id'];
        }
        else {
          $requestedAmount = CRM_Utils_Money::format((($value['assessment']/100) * $value['amount_total'] * ($grantThresholds['Funding factor'] / 100)), NULL, NULL, TRUE);
       		// Don't grant more money than originally requested
          if ($requestedAmount > $value['amount_total']) {
          	$requestedAmount = $value['amount_total'];
          }
          $amountEligible = CRM_Utils_Money::format(($grantThresholds['Maximum Grant'] - $amountGranted), NULL, NULL, TRUE);
          if ($requestedAmount > $amountEligible) {
            if ($amountEligible > $totalAmount) {
              $grant['eligible'][] = $amountEligible;
              continue;
            }
            else {
              if ($amountEligible != 0) {
                $totalAmount = $totalAmount - ($amountEligible - $value['amount_granted']);
                $value['amount_granted'] = $grant['granted'][] = $amountEligible;
              }
              else {
                $grant['nonEligible'][] = $requestedAmount;
              }
            }
          }
          else {
            if ($requestedAmount > $totalAmount) {
              $grant['eligible'][] = $requestedAmount;
              continue;
            }
            else {
              $totalAmount = $totalAmount - ($requestedAmount - $value['amount_granted']);
              $value['amount_granted'] = $grant['granted'][] = $requestedAmount;
            }
          }
          $ids['grant_id'] = $key;
        }
        $value['allocation'] = TRUE;
        $value['grant_program_id'] = $_POST['pid'];
        $result = CRM_Grant_BAO_Grant::add($value, $ids);
      }
    }

    $grantProgramParams['remainder_amount'] = $totalAmount;
    $grantProgramParams['id'] =  $_POST['pid'];
    $ids['grant_program'] =  $_POST['pid'];
    CRM_Grant_BAO_GrantProgram::create($grantProgramParams, $ids);
    $eligibleCount = $grantedAmount = $eligibleCount = $eligibleAmount = $nonEligibleCount = $nonEligibleAmount = 0;
    foreach($grant as $type => $amount) {
      if ($type == 'granted') {
        $grantedCount  = count($amount);
        $grantedAmount = array_sum($amount);
      }
      if($type == 'eligible') {
        $eligibleCount  = count($amount);
        $eligibleAmount = array_sum($amount);
      }
      if($type == 'nonEligible') {
        $nonEligibleCount  = count($amount);
        $nonEligibleAmount = array_sum($amount);
      }
    }
    $page = new CRM_Core_Page();
    $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
    $eligibleCountMessage = $remainingAmount = NULL;
     if ($nonEligibleCount) {
       $nonEligibleCountMessage = ts($nonEligibleCount." eligible applications were not allocated since they have already received their annual maximum.");
    }
    if ($eligibleCount) {
      $eligibleCountMessage = ts($eligibleCount." eligible applications were not allocated ".CRM_Utils_Money::format($eligibleAmount,NULL, NULL,FALSE)." in funds they would have received were funds available.");
    }
    if ($totalAmount > 0) {
      $remainingAmount = CRM_Utils_Money::format($totalAmount,NULL, NULL,FALSE)." remains unallocated.";
    }
    $message = ts("Trial Allocation Completed. " . CRM_Utils_Money::format($grantedAmount,NULL, NULL,FALSE) . " allocated to {$grantedCount} eligible applications. " . $eligibleCountMessage . $nonEligibleCountMessage . $remainingAmount);

    $page->assign('message', $message);

    $page->assign('grant_program_name', $grantPrograms[$_POST['pid']]);
    CRM_Core_Session::setStatus($message, '', 'success');
    $params['is_auto_email'] = 1;
    CRM_Grant_BAO_GrantProgram::sendMail($_SESSION['CiviCRM']['userID'], $params, 'allocation');
  }

  public function finalize() {
    $grantedAmount = 0;
    $grantStatus = CRM_Core_OptionGroup::values('grant_status', TRUE);
    $algoId = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantProgram', $_POST['pid'], 'allocation_algorithm');
    $algoName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $algoId, 'grouping');
    if ($algoName == "immediate") {
      $statuses = $grantStatus['Eligible'].', '.$grantStatus['Awaiting Information'].', '.$grantStatus['Submitted'];
    }
    else {
      $statuses = $grantStatus['Eligible'];
    }
    $params = array(
      'status_id' => $statuses,
      'grant_program_id' => $_POST['pid'],
    );
    $result = CRM_Grant_BAO_GrantProgram::getGrants($params);
    if (!empty($result)) {
      foreach ($result as $key => $row) {
        $grantedAmount += $row['amount_granted'];
      }
      $totalAmount = $_POST['amount'];
      if($grantedAmount < $totalAmount) {
        $data['confirm'] = 'confirm';
        $data['amount_granted'] =  $grantedAmount;
        echo json_encode($data);
        exit();
      }
      else {
        $data['total_amount'] =  $totalAmount;
        $data['amount_granted'] =  $grantedAmount;
        echo json_encode($data);
        exit();
      }
    }
  }

  public function processFinalization() {
    $grantStatus = CRM_Core_OptionGroup::values('grant_status', TRUE);
    $grantRej = CRM_Core_OptionGroup::values('reason_grant_ineligible', TRUE);
    $algoId = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantProgram', $_POST['pid'], 'allocation_algorithm');
    $algoName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $algoId, 'grouping');
    $grantThresholds = CRM_Core_OptionGroup::values('grant_thresholds', TRUE);
    $statuses = [$grantStatus['Eligible']];
    if ($algoName == 'immediate') {
      $statuses = array_merge($statuses, [
        $grantStatus['Awaiting Information'],
        $grantStatus['Submitted']
      ]);
    }
    $params = array(
      'status_id' => $statuses,
      'grant_program_id' => $_POST['pid'],
    );
    $result = civicrm_api3('Grant', 'get', [
      'status_id' => [
        'IN' => $statuses,
        'grant_program_id' => $_POST['pid'],
      ]
    ])['values'];
    if (!empty($result)) {
      foreach ($result as $key => $row) {
        $userparams['contact_id'] = $row['contact_id'];
        $userparams['grant_program_id'] = $_POST['pid'];
        $amountGranted = CRM_Grant_BAO_GrantProgram::getUserAllocatedAmount($userparams, $row['grant_id']);
        $requestedAmount = (($row['assessment']/100) * $row['amount_total'] * ($grantThresholds['Funding factor'] / 100));
        // Don't grant more money than originally requested
        if ($requestedAmount > $row['amount_total']) {
        	$requestedAmount = $row['amount_total'];
        }
        $amountEligible = $grantThresholds['Maximum Grant'] - $amountGranted;
        CRM_Core_Smarty::singleton()->assign('previousGrant', civicrm_api3('Grant', 'getsingle', ['id' => $key]));
        if ($requestedAmount > $amountEligible) {
          if ($amountEligible == 0) {
            $ids['grant'] = $key;
            $row['status_id'] = $grantStatus['Ineligible'];
            $row['grant_rejected_reason_id'] = $grantRej['Applicant has received their annual maximum already'];
            $result = CRM_Grant_BAO_Grant::add($row, $ids);
          }
        }

        if ( $row['amount_granted'] > 0 ) {
          $ids['grant'] = $key;
          $row['status_id'] = $grantStatus['Approved for Payment'];

          $result = CRM_Grant_BAO_Grant::add($row, $ids);
        }
      }
      CRM_Core_Session::setStatus(ts('Approved allocations finalized successfully.'), '', 'success');
    }
  }

  public function reject() {
    $grantStatus = CRM_Core_OptionGroup::values( 'grant_status', TRUE);
    $algoId = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantProgram', $_POST['pid'], 'allocation_algorithm');
    $algoName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $algoId, 'grouping');
    if ($algoName == "immediate") {
      $statuses = $grantStatus['Eligible'].', '.$grantStatus['Awaiting Information'].', '.$grantStatus['Submitted'];
    }
    else {
      $statuses = $grantStatus['Eligible'];
    }
    $id = $_POST['pid'];
    $params = array(
      'status_id' => $statuses,
      'grant_program_id' => $_POST['pid'],
    );

    $result = CRM_Grant_BAO_GrantProgram::getGrants($params);
    $remainderAmount = $_POST['remainder_amount'];
    if (!empty($result)) {
      foreach ($result as $key => $value) {
        $remainderAmount += $value['amount_granted'];
        $value['status_id'] = $grantStatus['Ineligible'];
        $value['amount_granted'] = 0.00;
        $ids['grant'] = $key;
        $result = CRM_Grant_BAO_Grant::add($value, $ids);
      }
      $grantProgramParams['remainder_amount'] = $remainderAmount;
      $grantProgramParams['id'] = $_POST['pid'];
      $ids['grant_program'] = $_POST['pid'];
      CRM_Grant_BAO_GrantProgram::create($grantProgramParams, $ids);
      CRM_Core_Session::setStatus(ts('Marked remaining unapproved Grants as Ineligible successfully.'), '', 'success');
    }
  }
}
