<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * This class provides the functionality to update a group of
 * grants. This class provides functionality for the actual
 * update.
 */
class CRM_Grant_Form_Task_Update extends CRM_Grant_Form_Task {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();

    //check permission for update.
    if (!CRM_Core_Permission::checkActionPermission('CiviGrant', CRM_Core_Action::UPDATE)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $grantStatus = CRM_Grant_PseudoConstant::grantStatus();
    CRM_Utils_System::setTitle(ts('Update Grants'));
    $this->addElement('select', 'status_id', ts('Grant Status'), array('' => '') + $grantStatus);
    $this->addElement('radio', 'radio_ts', null, 'Do not update', 'no_update' );  
    $this->addElement('radio', 'radio_ts', null, 'Other Amount', 'amount_granted' );  
    $this->setDefaults( array( 'radio_ts'=> 'no_update' ) );

    $this->addElement('text', 'amount_granted', ts('Amount Granted'));
    $this->addRule('amount_granted', ts('Please enter a valid amount.'), 'money');

    $this->addElement('radio', 'radio_ts', null, 'Standard Allocation', 'amount_total' );

    $this->addDate('decision_date', ts('Grant Decision'), FALSE, array('formatType' => 'custom'));

    // $this->assign('elements', array('status_id', 'amount_granted', 'decision_date'));
    $this->assign('totalSelectedGrants', count($this->_grantIds));

    $this->addDefaultButtons(ts('Update Grants'), 'done');
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $updatedGrants = 0;

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);
    $qfKey = $params['qfKey'];
    $grantIds = implode(', ', $this->_grantIds);
    $query = "SELECT id, assessment FROM civicrm_grant WHERE id IN ({$grantIds})";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $sort[$dao->id] = $dao->assessment;
    }
    foreach ($params as $key => $value) {
      if ($value == '' || $key == 'qfKey') {
        unset($params[$key]);
      }
    }

    if (!empty($params)) {
      foreach ($params as $key => $value) {
        if ( $key != 'radio_ts' ) {
          $values[$key] = $value;
        }
      }
      arsort($sort);
      foreach ($sort as $keys => $vals) {
        if (array_key_exists($keys, array_flip($this->_grantIds))) {
          $sorted[] = $keys;
        }
      }
      foreach ($sorted as $grantId) {
        $ids['grant_id'] = $grantId;
        if ( $params['radio_ts'] == 'amount_total' ) {
          unset($params['amount_granted']);
          $grantParams = array('id'=>$grantId);
          $grant = CRM_Grant_BAO_Grant::retrieve($grantParams, $defaults=array());
          self::allocation($grant);
        }
        CRM_Grant_BAO_Grant::add($values, $ids);
        $updatedGrants++;
      }
    }

    $status = 
      ts('Updated Grant(s): %1', array(1 => $updatedGrants)).', '.
      ts('Total Selected Grant(s): %1', array(1 => count($this->_grantIds)));
    CRM_Core_Session::setStatus($status);
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/search', 'force=1&qfKey=' . $qfKey));
  }

  function allocation($grant) {
    $defaults = $grants = array();
    $programId = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $grant->id, 'grant_program_id');
    $programParams = array('id' => $programId);
    $grantProgram = CRM_Grant_BAO_GrantProgram::retrieve($programParams, $defaults);
    $algoType = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $grantProgram->allocation_algorithm, 'grouping');
    if ($algoType == 'immediate') {
      $grantedAmount = self::quickAllocate($grantProgram, $grant);
    } 
    else if ($algoType == 'batch') {
      $grantedAmount = self::batchAllocate($grantProgram, $grant);
    }

    if ($grantedAmount) {
      $grantUpdate = array(
        'id' => $grant->id,
        'amount_granted' => $grantedAmount,
      );
      $grantIds['grant'] = $grant->id;
      CRM_Grant_BAO_Grant::add($grantUpdate, $grantIds);
    }
  }

  function quickAllocate($grantProgram, $grant) {
    $grantThresholds = CRM_Core_OptionGroup::values( 'grant_thresholds' );
    $grantThresholds = array_flip($grantThresholds);
    $amountGranted = NULL; 
    if( $grantProgram->remainder_amount == '0.00' ) {
      $totalAmount = $grantProgram->total_amount;
    } else {
      $totalAmount = $grantProgram->remainder_amount;
    }
    if (isset($grant->assessment)) {
      if ($grant->assessment > $grantThresholds['Minimum Score For Grant Award']) {
        if ((($grantThresholds['Fixed Percentage Of Grant']/100) * $grant->amount_total) < $totalAmount) {
          $amountGranted = ($grantThresholds['Fixed Percentage Of Grant']/100) * $grant->amount_total;
        }
      } else if ((($grant->assessment/100) * $grant->amount_total) < $totalAmount) {
        $amountGranted = ($grant->assessment/100) * $grant->amount_total;
      }
    }

    //Update grant program
    $grantProgramParams = array();
    $grantProgramParams['remainder_amount'] = $totalAmount - $amountGranted;
    $grantProgramParams['id'] =  $grantProgram->id;
    $ids['grant_program']     =  $grantProgram->id;
    CRM_Grant_BAO_GrantProgram::create( $grantProgramParams, $ids );
    return $amountGranted;
  }

  function batchAllocate($grantProgram, $grant) {
    $grantThresholds = CRM_Core_OptionGroup::values( 'grant_thresholds' );
    $grantThresholds = array_flip($grantThresholds);
    $amountGranted = NULL; 
    if( $grantProgram->remainder_amount == '0.00' ) {
      $totalAmount = $grantProgram->total_amount;
    } else {
      $totalAmount = $grantProgram->remainder_amount;
    }
    if( $grant->amount_total < $totalAmount ) {
      if ( $grant->amount_total >= $grantThresholds['Maximum Grant'] ) {
        $totalAmount = $totalAmount - $grantThresholds['Maximum Grant'];
        $amountGranted = $grantThresholds['Maximum Grant'];
      } else {
        $totalAmount = $totalAmount - $grant->amount_total;
        $amountGranted = $grant->amount_total;
      }
      //Update grant program
      $grantProgramParams = array();
      $grantProgramParams['remainder_amount'] = $totalAmount - $amountGranted;
      $grantProgramParams['id'] =  $grantProgram->id;
      $ids['grant_program']     =  $grantProgram->id;
      CRM_Grant_BAO_GrantProgram::create( $grantProgramParams, $ids );
      return $amountGranted;
    }
  }
}

