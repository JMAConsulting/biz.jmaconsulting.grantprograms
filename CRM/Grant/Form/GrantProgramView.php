<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
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

require_once 'CRM/Core/Form.php';  
require_once 'CRM/Grant/BAO/Grant.php';
require_once 'CRM/Grant/BAO/GrantProgram.php';

/**
 * This class generates form components for processing a Grant
 * 
 */
class CRM_Grant_Form_GrantProgramView extends CRM_Core_Form
{

    /**  
     * Function to set variables up before form is built  
     *                                                            
     * @return void  
     * @access public  
     */
    public function preProcess( ) 
    {
        $this->_id        = CRM_Utils_Request::retrieve( 'id', 'Positive', $this );
        $values = array( ); 
        $params['id'] = $this->_id;
        CRM_Grant_BAO_GrantProgram::retrieve( $params, $values );
        $contributionTypes = CRM_Grant_BAO_GrantProgram::contributionTypes();
        $this->assign('grantType', CRM_Grant_BAO_GrantProgram::getOptionName( $values['grant_type_id'] ) );
        $this->assign('grantProgramStatus', CRM_Grant_BAO_GrantProgram::getOptionName($values['status_id'] ) );
        $this->assign('contributionType', $contributionTypes[$values['contribution_type_id']] );
        $this->assign('grantProgramAlgorithm', CRM_Grant_BAO_GrantProgram::getOptionName( $values['allocation_algorithm'] ) );
        $grantTokens = array( 'label','name','total_amount',
                              'remainder_amount','allocation_date', 'is_active', 'is_auto_email' );

        foreach ( $grantTokens as $token ) {
            $this->assign( $token, CRM_Utils_Array::value( $token, $values ) );
        }
        $this->assign( 'id', $this->_id );
    }

    /**
     * Function to build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) 
    {
        $this->addButtons(array(  
                                array ( 'type'      => 'cancel',  
                                        'name'      => ts('Done'),  
                                        'spacing'   => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',  
                                        'isDefault' => true   )
                                )
                          );
    }

    public function allocate( ) 
    {   
      require_once 'CRM/Grant/BAO/Grant.php';
      $grantStatus = CRM_Core_OptionGroup::values( 'grant_status' );
      $grantStatus = array_flip($grantStatus);
      $params = array(
                      'status_id' => $grantStatus['Approved'],
                      'grant_program_id' => $_POST['pid'],
                      'amount_granted' => 'NULL',
                      'assessment'     => 'NOT NULL',
                      );
      
      // $grantAlgorithm = CRM_Core_OptionGroup::values( 'allocation_algorithm' );
      // $grantAlgorithm = array_flip($grantAlgorithm);
      // $grantAlgorithmId = $grantAlgorithm[$_POST['algorithm']];
      $result = CRM_Grant_BAO_Grant::getGrants( $params );
      
      if ( !empty($result ) ) {
        if ( $_POST['algorithm'] == 'Best to Worst, Fully Funded' ) {
          foreach ( $result as $key => $row ) {
            $order[$key] = $row['assessment'];
          }
          $sort_order = SORT_DESC;
          array_multisort( $order, $sort_order, $result );
        } 
        
        if( $_POST['remainder_amount'] == '0.00' ) {
          $totalAmount = $_POST['amount'];
        } else {
          $totalAmount = $_POST['remainder_amount'];
        }
        
        $contact = array(); 
        $grantThresholds = CRM_Core_OptionGroup::values( 'grant_thresholds' );
        $grantThresholds = array_flip($grantThresholds);
         
        foreach ( $result as $key => $value ) {
            
          if ( !in_array( $value['contact_id'], $contact ) ) {
            if ( $_POST['algorithm'] == 'Best to Worst, Fully Funded' ) {
              if( $value['amount_total'] > $totalAmount ) {
                $grant['eligible'][] = $value['amount_total'];
                continue;
              } else {
                if ( $value['amount_total'] >= $grantThresholds['Maximum Grant'] ) {
                  $grant['granted'][] = $grantThresholds['Maximum Grant'];
                  $totalAmount = $totalAmount - $grantThresholds['Maximum Grant'];
                  $contact[] = $value['contact_id'];
                  $value['amount_granted'] = $grantThresholds['Maximum Grant'];
                } else {
                  $grant['granted'][] = $value['amount_total'];
                  $totalAmount = $totalAmount - $value['amount_total'];
                  $value['amount_granted'] = $value['amount_total'];
                }
                $ids['grant']            = $value['grant_id'];
              }
            } elseif( $_POST['algorithm'] == 'Over Threshold, Percentage of Request Funded' ) {
              if($value['assessment'] > $grantThresholds['Minimum Score For Grant Award'] ) {
                if( ( $grantThresholds['Fixed Percentage Of Grant']/100 )*$value['amount_total'] > $totalAmount ) {
                  $grant['eligible'][] = ( $grantThresholds['Fixed Percentage Of Grant']/100 )*$value['amount_total'];
                  continue;
                } else {
                  $value['amount_granted'] = ( $grantThresholds['Fixed Percentage Of Grant']/100 )*$value['amount_total'];
                  $grant['granted'][] = $value['amount_granted'];
                  if ($value['amount_granted'] >= $grantThresholds['Maximum Grant']) {
                    $contact[] = $value['contact_id'];
                  }
                  $totalAmount = $totalAmount - $value['amount_granted'];
                }
              } else {
                if (  ( $value['assessment']/100 )*$value['amount_total'] > $totalAmount ) {
                  $grant['eligible'][] = ( $value['assessment']/100 )*$value['amount_total'];
                  continue;
                }
                else {
                  $value['amount_granted'] = ( $value['assessment']/100 )*$value['amount_total'];
                  $grant['granted'][] = $value['amount_granted'];
                  if ($value['amount_granted'] >= $grantThresholds['Maximum Grant']) {
                    $contact[] = $value['contact_id'];
                  }
                  $totalAmount = $totalAmount - $value['amount_granted'];
                }
              }
              $ids['grant']            = $key;
            } 
            $result = CRM_Grant_BAO_Grant::add( &$value, &$ids );
          } else {
            $grant['nonEligible'][] = $value['amount_total'];
          }
        } 
      }
      
      $grantProgramParams['remainder_amount'] = $totalAmount;
      $grantProgramParams['id'] =  $_POST['pid'];
      $ids['grant_program']     =  $_POST['pid'];
      CRM_Grant_BAO_GrantProgram::create( $grantProgramParams, $ids );
      $eligibleCount = $grantedAmount = $eligibleCount = $eligibleAmount = $nonEligibleCount = $nonEligibleAmount = 0;
      foreach( $grant as $type => $amount ) {
        if( $type == 'granted' ) {
          $grantedCount  = count($amount);
          $grantedAmount = array_sum($amount);
        }
        if( $type == 'eligible' ) {
          $eligibleCount  = count($amount);
          $eligibleAmount = array_sum($amount);
        }
        if( $type == 'nonEligible' ) {
          $nonEligibleCount  = count($amount);
          $nonEligibleAmount = array_sum($amount);
        }
      }
      require_once 'CRM/Core/Page.php';
      require_once 'CRM/Grant/BAO/Grant.php';
      $page = new CRM_Core_Page();
      $grantPrograms = CRM_Grant_BAO_Grant::getGrantPrograms();
      $message = "Trial Allocation Completed. $".$grantedAmount.".00 allocated to {$grantedCount} eligible applications. ".$eligibleCount." eligible applications were not allocated $".$eligibleAmount.".00 in funds they would have received were funds available. $".$totalAmount." remains unallocated.";
     

     
      $page->assign( 'message', $message );
      
      $page->assign( 'grant_program_name', $grantPrograms[$_POST['pid']] );
      CRM_Core_Session::setStatus( $message );
      $params['is_auto_email'] = 1;
      CRM_Grant_BAO_Grant::sendMail( $_SESSION[ 'CiviCRM' ][ 'userID' ], $params, 'allocation' );
    }
    
    public function finalize( ) 
    {   
      $grantedAmount = 0;
      require_once 'CRM/Grant/BAO/Grant.php';
      $grantStatus = CRM_Core_OptionGroup::values( 'grant_status' );
      $grantStatus = array_flip($grantStatus);
      $params = array(
                      'status_id' => $grantStatus['Approved'],
                      'grant_program_id' => $_POST['pid'],
                      );
      $result = CRM_Grant_BAO_Grant::getGrants( $params );
      if ( !empty($result ) ) {
        foreach ($result as $key => $row) {
          $grantedAmount += $row['amount_granted'];
        }
        $totalAmount = $_POST['amount'];
        if( $grantedAmount < $totalAmount ) {
          $data['confirm'] = 'confirm';
          $data['amount_granted'] =  $grantedAmount;
          echo json_encode($data);
          exit(); 
        } else {
          $data['total_amount'] =  $totalAmount;
          $data['amount_granted'] =  $grantedAmount;
          echo json_encode($data);
          exit(); 
        }
      }
    }
    
    public function processFinalization( ) {
      require_once 'CRM/Grant/BAO/Grant.php';
      $grantStatus = CRM_Core_OptionGroup::values( 'grant_status' );
      $grantStatus = array_flip($grantStatus);
      $params = array(
                      'status_id' => $grantStatus['Approved'],
                      'grant_program_id' => $_POST['pid'],
                      );
      $result = CRM_Grant_BAO_Grant::getGrants( $params );
      if ( !empty($result ) ) {
        foreach ($result as $key => $row) {
          if ( $row['amount_granted'] > 0 ) {
            $ids['grant'] = $key;
            $row['status_id'] = $grantStatus['Granted'];
                    
            $result = CRM_Grant_BAO_Grant::add( &$row, &$ids );
          } 
        }
        CRM_Core_Session::setStatus( 'Approved allocations finalized successfully.' );
      }
    }
    
    public function reject( ) 
    {
      require_once 'CRM/Grant/BAO/Grant.php';
      $grantStatus = CRM_Core_OptionGroup::values( 'grant_status' );
      $grantStatus = array_flip($grantStatus);
      $id = $_POST['pid'];
      $params = array(
                      'status_id' => $grantStatus['Submitted'].','.$grantStatus['Approved'],
                      'grant_program_id' => $_POST['pid'],
                      );

      $result = CRM_Grant_BAO_Grant::getGrants( $params );
      
      if ( !empty($result ) ) {
        foreach ( $result as $key => $value ) {
          $value['status_id'] = $grantStatus['Rejected'];
          $value['amount_granted'] = 0.00;
          $ids['grant'] = $key;
          $result = CRM_Grant_BAO_Grant::add( &$value, &$ids );
        } 
        CRM_Core_Session::setStatus( 'Submitted and Approved grants rejected successfully.' );
      }
    }
}