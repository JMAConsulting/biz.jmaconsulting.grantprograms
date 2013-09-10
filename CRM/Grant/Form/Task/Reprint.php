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

/**
 * This class provides the functionality to delete a group of
 * participations. This class provides functionality for the actual
 * deletion.
 */
class CRM_Grant_Form_Task_Reprint extends CRM_Grant_Form_PaymentTask
{
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
  function preProcess( ) 
  {
    parent::preProcess( );
        
    if ( !CRM_Core_Permission::checkActionPermission( 'CiviGrant', CRM_Core_Action::PAY ) ) {
      CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) );
    }
    
  }
  function setDefaultValues( ) 
  {
    $defaults = array();
    $paymentNumbers = CRM_Grant_BAO_GrantPayment::getMaxPayementBatchNumber( );
    $defaults['payment_date'] = strftime("%m/%d/%Y", strtotime( date('Y/m/d') ));
    $defaults['payment_number'] = $paymentNumbers['payment_number'] + 1;
    $defaults['payment_batch_number'] = $paymentNumbers['payment_batch_number'] + 1;

    return $defaults;
  }
  /**
   * Build the form
   *
   * @access public
   * @return void
   */
  function buildQuickForm( ) 
  { 
    require_once 'CRM/Core/OptionGroup.php';
    $validStatus = array( 'Printed', 'Reprinted');
    $paymentStatus = CRM_Core_OptionGroup::values( 'grant_payment_status' );
    foreach( $paymentStatus as $statusKey => $status ) {
      if(in_array( $status , $validStatus) ) {
        unset( $paymentStatus[$statusKey] );
      }
    }
    $selectedPayments = count($this->_grantPaymentIds);
    foreach ( $this->_grantPaymentIds as $key => $paymentId ) {
      $paymentDAO =& new CRM_Grant_DAO_GrantPayment();
      $paymentDAO->id = $paymentId; 
      $paymentDAO->find(true);
      if( array_key_exists( $paymentDAO->payment_status_id, $paymentStatus ) ) {
        unset($this->_grantPaymentIds[$key]);
      }
    }
    $reprinted = count($this->_grantPaymentIds);
    $stopped = $selectedPayments - $reprinted;
    if ( count($this->_grantPaymentIds ) ) {
    	$this->assign( 'payments', 1 );
    CRM_Core_Session::setStatus(ts( $stopped.' of the '.$selectedPayments.' selected grant payments have already been stopped. '.count($this->_grantPaymentIds).' of the '.count($this->_grantPaymentIds).' selected grant payments are printed or reprinted.'), NULL, 'no-popup');
      $this->applyFilter('__ALL__','trim');
      $attributes = CRM_Core_DAO::getAttribute( 'CRM_Grant_DAO_GrantProgram' );
    
      $this->_contributionTypes = CRM_Grant_BAO_GrantProgram::contributionTypes();
      $this->add('select', 'financial_type_id',  ts( 'From account' ),
                 array( '' => ts( '- select -' ) ) + $this->_contributionTypes , true);

      $this->add( 'text', 'payment_batch_number', ts( 'Payment Batch number' ),
                  $attributes['label'], true );

      $this->add( 'text', 'payment_number', ts( 'Starting cheque number' ),
                  $attributes['label'], true );
        
      $this->addDate( 'payment_date', ts('Payment date to appear on cheques'), false, array( 'formatType' => 'custom') );
        
      $this->addButtons(array( 
                              array ( 'type'      => 'upload',
                                      'name'      => ts('Reprint Checks'), 
                                      'isDefault' => true   ),
                              array ( 'type'      => 'next',
                                      'name'      => ts('Export to CSV'),), 
                              array ( 'type'      => 'cancel', 
                                      'name'      => ts('Cancel') ), 
                               ) 
                        );
    } else {
      CRM_Core_Session::setStatus(ts('Please select at least one grant payment that has been printed.'), NULL, 'no-popup');
      $this->addButtons(array( 
                              array ( 'type'      => 'cancel', 
                                      'name'      => ts('Cancel') ), 
                               ) 
                        );
    }
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   * @return None
   */
  public function postProcess( ) 
  { 
    $values  = $this->controller->exportValues( $this->_name );
    $makePdf = true;
    foreach ( $_POST as $buttonKey => $buttonValue ) {
      if ( $buttonKey == '_qf_Reprint_next' ) {
        $makePdf = false;
      }
    }
    $totalAmount = 0;
    foreach ( $this->_grantPaymentIds as $paymentId ) {
        
      $paymentDAO =& new CRM_Grant_DAO_GrantPayment(); 
      $paymentDAO->id = $paymentId; 
      $paymentDAO->payment_status_id    = CRM_Core_OptionGroup::getValue( 'grant_payment_status', 'Stopped', 'name' );
      $paymentDAO->save();
      $paymentDAO =& new CRM_Grant_DAO_GrantPayment();
      $paymentDAO->id = $paymentId; 
      $paymentDAO->find(true);
      
      $payment['payment_batch_number'] = $values['payment_batch_number'];
      $payment['financial_type_id'] = $values['financial_type_id']; 
      $payment['payment_number']       = $values['payment_number'];
      $payment['contact_id']           = $paymentDAO->contact_id; 
      $payment['payment_created_date'] = date('m/d/Y');
      $payment['payment_date']         = date("Y-m-d", strtotime($values['payment_date']));
      $payment['payable_to_name']      = $paymentDAO->payable_to_name;
      $payment['payable_to_address']   = $paymentDAO->payable_to_address; 
      $payment['amount']               = $paymentDAO->amount;
      $payment['currency']             = $paymentDAO->currency; 
      $payment['payment_reason']       = $paymentDAO->payment_reason;
      $payment['payment_status_id']    = CRM_Core_OptionGroup::getValue( 'grant_payment_status', 'Reprinted', 'name' );
      $payment['replaces_payment_id']  = $paymentId;
      
      $result = CRM_Grant_BAO_GrantPayment::add( &$payment, $ids = array() );
      
      $newPaymentId = $result->id;
        
      $entityDAO =& new CRM_Grant_DAO_EntityPayment();
      $entityDAO->payment_id = $paymentId;
      $entityDAO->find();
      
      while( $entityDAO->fetch() ) {
        $newEntityDAO =& new CRM_Grant_DAO_EntityPayment();
        //$newEntityDAO->find( true );
        $newEntityDAO->payment_id   = $newPaymentId;
        $newEntityDAO->entity_table = 'civicrm_grant';
        $newEntityDAO->entity_id    = $entityDAO->entity_id;
        $newEntityDAO->save();
        $grantDAO =& new CRM_Grant_DAO_Grant();
        $grantDAO->id = $entityDAO->entity_id;
        $grantDAO->find(true);
        
        if ( !empty( $payment_details[$newEntityDAO->payment_id] ) ) {
          $payment_details[$newEntityDAO->payment_id] .= '</td></tr><tr><td width="15%" >'.date("Y-m-d", strtotime($values['payment_date'])).'</td><td width="15%" >'.$entityDAO->entity_id.'</td><td width="50%" >'.CRM_Grant_BAO_GrantProgram::getDisplayName( $result->contact_id ).'</td><td width="20%" >'.CRM_Utils_Money::format( $grantDAO->amount_granted,null, null,false );
        } else {
          $payment_details[$newEntityDAO->payment_id] = date("Y-m-d", strtotime($values['payment_date'])).'</td><td width="15%" >'.$entityDAO->entity_id.'</td><td width="50%" >'.CRM_Grant_BAO_GrantProgram::getDisplayName( $result->contact_id ).'</td><td width="20%" >'.CRM_Utils_Money::format( $grantDAO->amount_granted,null, null,false );
        }
      }
      
      $grantPayment[$newEntityDAO->payment_id]['contact_id']           = $result->contact_id;
      $grantPayment[$newEntityDAO->payment_id]['financial_type_id'] = $values['financial_type_id'];
      $grantPayment[$newEntityDAO->payment_id]['payment_batch_number'] = $values['payment_batch_number'];
      $grantPayment[$newEntityDAO->payment_id]['payment_number'      ] = $values['payment_number'];
      $grantPayment[$newEntityDAO->payment_id]['payment_date'        ] = date("Y-m-d", strtotime( $values['payment_date']));
      $grantPayment[$newEntityDAO->payment_id]['payment_created_date'] = date('Y-m-d');
      $grantPayment[$newEntityDAO->payment_id]['payable_to_name'     ] = CRM_Grant_BAO_GrantProgram::getDisplayName( $result->contact_id );
      $grantPayment[$newEntityDAO->payment_id]['payable_to_address'  ] = CRM_Utils_Array::value( 'address', CRM_Grant_BAO_GrantProgram::getAddress( $result->contact_id ) );
      $grantPayment[$newEntityDAO->payment_id]['amount'              ] = $result->amount;
      $grantPayment[$newEntityDAO->payment_id]['currency'            ] = $result->currency;
      $grantPayment[$newEntityDAO->payment_id]['payment_status_id'   ] = 3;
      $grantPayment[$newEntityDAO->payment_id]['payment_reason'     ]  = $result->payment_reason;
      $grantPayment[$newEntityDAO->payment_id]['replaces_payment_id']  = $result->replaces_payment_id;
      
      foreach ( $grantPayment as $grantKey => $values ) {
      	$row = array();
      	$grantValues = $values;
      	require_once 'CRM/Grant/Words.php';
      	$words = new CRM_Grant_Words();
      	$amountInWords = ucwords($words->convert_number_to_words($values['amount']));
      	$grantPayment[$grantKey]['total_in_words'] = $values['total_in_words'] = $grantValues['total_in_words'] = $amountInWords;
      	$grantPayment[$grantKey]['amount'] = $values['amount'];
      }
      
      if ( $makePdf ) {
        $grantPayment[$newEntityDAO->payment_id]['payment_details'] = $payment_details[$newEntityDAO->payment_id];
        $grantPayment[$newEntityDAO->payment_id]['payment_id']      = $values['payment_number'];
      } else {
        unset($grantPayment[$newEntityDAO->payment_id]['payment_status_id']);
      }
      $values['payment_number']++;
      $totalAmount += $result->amount;
    }
  
    require_once 'CRM/Grant/Form/Task/GrantPayment.php';
    
    $downloadName  = check_plain('grantPayment');
    $downloadName .= '_'.date('Ymdhis');
    $this->assign( 'date', date('Y-m-d'));
    $this->assign( 'time', date('H:i:s'));
    $this->assign( 'account_name',CRM_Core_DAO::getFieldValue( 'CRM_Financial_DAO_FinancialType', $values['financial_type_id'], 'name' ) );
    $this->assign( 'batch_number', $values['payment_batch_number']);
    $this->assign( 'contact',CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', $_SESSION[ 'CiviCRM' ][ 'userID' ], 'display_name' ) );
    $this->assign( 'grantPayment', $grantPayment );
    $this->assign( 'total_payments', count($grantPayment) );
    $this->assign( 'total_amount' , CRM_Utils_Money::format( $totalAmount, null, null,false ) );
    $this->assign( 'domain_name', CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_Domain', CRM_Core_Config::domainID( ) , 'name' ) );
                   
    if( !$makePdf ) {
      $downloadName .= '.csv';
      $fileName = CRM_Utils_File::makeFileName( $downloadName );
      $config = CRM_Core_Config::singleton();
      $file_name = $config->customFileUploadDir . $fileName;
      CRM_Grant_BAO_GrantPayment::createCSV( $file_name, $grantPayment ); 
    } else {
      $downloadName .= '.pdf';
      $fileName = CRM_Utils_File::makeFileName( $downloadName );
      $fileName = CRM_Grant_BAO_GrantPayment::makePDF( $fileName, $grantPayment );
    }
    $checkRegisterFile = check_plain('CheckRegister');
    $checkRegisterFile .= '_'.date('Ymdhis');
    $checkRegisterFile .= '.pdf';
    $checkFile = CRM_Utils_File::makeFileName( $checkRegisterFile );
    $checkRegister = CRM_Grant_BAO_GrantPayment::makeReport($checkFile, $grantPayment );
   
    $fileDAO =& new CRM_Core_DAO_File();
    $fileDAO->uri           = $fileName;
    if ( $makePdf ) {
      $fileDAO->mime_type = 'application/pdf';
    } else {
      $fileDAO->mime_type = 'text/x-csv';
    }
    $fileDAO->upload_date   = date('Ymdhis'); 
    $fileDAO->save();
    $grantPaymentFile = $fileDAO->id;
    
    $entityFileDAO =& new CRM_Core_DAO_EntityFile();
    $entityFileDAO->entity_table = 'civicrm_contact';
    $entityFileDAO->entity_id    = $_SESSION[ 'CiviCRM' ][ 'userID' ];
    $entityFileDAO->file_id      = $grantPaymentFile;
    $entityFileDAO->save();
    
    $fileDAO =& new CRM_Core_DAO_File();
    $fileDAO->uri           = $checkFile;
    $fileDAO->mime_type     = 'application/pdf';
    $fileDAO->upload_date   = date('Ymdhis'); 
    $fileDAO->save();
    $grantPaymentCheckFile = $fileDAO->id;
    
    $entityFileDAO =& new CRM_Core_DAO_EntityFile();
    $entityFileDAO->entity_table = 'civicrm_contact';
    $entityFileDAO->entity_id    = $_SESSION[ 'CiviCRM' ][ 'userID' ];
    $entityFileDAO->file_id      = $grantPaymentCheckFile;
    $entityFileDAO->save(); 

    $params = array( 
                    'source_contact_id'    => $_SESSION[ 'CiviCRM' ][ 'userID' ],
                    'activity_type_id'     => key(CRM_Core_OptionGroup::values( 'activity_type', false, false, false, 'AND v.label = "Grant Payment"' , 'value' )),
                    'assignee_contact_id'  => $_SESSION[ 'CiviCRM' ][ 'userID' ],
                    'subject'              => "Grant Payment",
                    'activity_date_time'   => date('Ymdhis'),
                    'status_id'            => CRM_Core_OptionGroup::getValue( 'activity_status','Completed','name' ),
                    'priority_id'          => 2,
                    'details'              => "<a href=".CRM_Utils_System::url( 'civicrm/file', 'reset=1&id='.$grantPaymentFile.'&eid='.$_SESSION[ 'CiviCRM' ][ 'userID' ].'').">".$downloadName."</a></br><a href=".CRM_Utils_System::url( 'civicrm/file', 'reset=1&id='.$grantPaymentCheckFile.'&eid='.$_SESSION[ 'CiviCRM' ][ 'userID' ].'').">".$checkRegisterFile."</a>",
                     );
    CRM_Activity_BAO_Activity::create( $params );
    CRM_Core_Session::setStatus( "Selected payment stopped and reprinted successfully.");
    CRM_Utils_System::redirect(CRM_Utils_System::url( 'civicrm/grant/payment/search', 'reset=1&bid='.$values['payment_batch_number'].'&download='.$fileName.'&force=1'));
  }
}


