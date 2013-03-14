<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.0                                                |
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
require_once 'CRM/Utils/Rule.php';
require_once 'CRM/Grant/BAO/GrantProgram.php';
require_once 'CRM/Grant/BAO/Grant.php';
require_once 'CRM/Grant/DAO/Grant.php';
require_once "CRM/Core/DAO/EntityFile.php";
require_once 'CRM/Core/BAO/File.php';
require_once 'CRM/Grant/BAO/GrantPayment.php';
require_once 'CRM/Activity/BAO/Activity.php';
require_once 'CRM/Grant/Form/Task.php';
require_once 'CRM/Core/OptionGroup.php';
require_once 'CRM/Utils/Money.php';
/**
 * This class generates form components for Payments
 * 
 */    
class CRM_Grant_Form_Task_GrantPayment extends CRM_Grant_Form_Task
{
    
  protected $_id     = null;
  protected $_fields = null;
  function preProcess( ) {
    parent::preProcess( ); 
    $this->_action     = CRM_Utils_Request::retrieve('action', 'String', $this );
    $this->_prid = CRM_Utils_Request::retrieve('prid', 'Positive', $this );
    if ( $this->_prid ) {
      $session = CRM_Core_Session::singleton();
      $url = CRM_Utils_System::url('civicrm/grant/payment/search', '_qf_PaymentSearch_display=true&qfKey='.CRM_Utils_Request::retrieve('prid', 'Positive', $this ));
      $session->pushUserContext( $url );
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
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm( $check = false ) 
  {
    parent::buildQuickForm( );
    if ( $this->_action & CRM_Core_Action::DELETE ) {
            
      $this->addButtons( array(
                               array ( 'type'      => 'next',
                                       'name'      => ts('Delete'),
                                       'isDefault' => true   ),
                                     
                               array ( 'type'      => 'cancel',
                                       'name'      => ts('Cancel') ),
                               )
                         );
      return;
    }

    $this->applyFilter('__ALL__','trim');
    $attributes = CRM_Core_DAO::getAttribute( 'CRM_Grant_DAO_GrantProgram' );
        
    $this->_contributionTypes = CRM_Grant_BAO_GrantProgram::contributionTypes();
    $this->add('select', 'contribution_type_id',  ts( 'From account' ),
               array( '' => ts( '- select -' ) ) + $this->_contributionTypes , true);

    $this->add( 'text', 'payment_batch_number', ts( 'Payment Batch number' ),
                $attributes['label'], true );

    $this->add( 'text', 'payment_number', ts( 'Starting cheque number' ),
                $attributes['label'], true );
        
    $this->addDate( 'payment_date', ts('Payment date to appear on cheques'), false, array( 'formatType' => 'custom') );
    $buttonName = "Print Checks";
    if ( $this->_prid ) {
      $buttonName = "Reprint Checks";
    }
    $this->addButtons(array( 
                            array ( 'type'      => 'upload',
                                    'name'      => ts($buttonName), 
                                    'isDefault' => true   ),
                            array ( 'type'      => 'next',
                                    'name'      => ts('Export to CSV'),), 
                            array ( 'type'      => 'cancel', 
                                    'name'      => ts('Cancel') ), 
                             ) 
                      );
    $this->addFormRule( array( 'CRM_Grant_Form_Task_GrantPayment', 'formRule' ), $this );
        
  }

  public function formRule( $params, $files, $self ) 
  {
    $errors = array( ); 
    $date  = date('m/d/Y', mktime(0, 0, 0, date("m")-6  , date("d")+1, date("Y")) );
    if( strtotime($params['payment_date']  < strtotime($date) ) )
      $errors['payment_date'] = ts( 'Payments may not be back-dated more than 6 months.' );
         
    if ( ! CRM_Utils_Rule::integer($params['payment_number'] ) )
      $errors['payment_number'] = ts( "'{$params['payment_number']}' is not integer value." );

    if ( ! CRM_Utils_Rule::integer($params['payment_batch_number'] ) )
      $errors['payment_batch_number'] = ts( "'{$params['payment_batch_number']}' is not integer value." );

    if ( $params['payment_number'] < 1 )
      $errors['payment_number'] = ts( "Please enter valid payment number." );

    if ( $params['payment_batch_number'] < 1 )
      $errors['payment_batch_number'] = ts( "Please enter valid payment batch number." );

    if ( CRM_Utils_Rule::integer( $params['payment_number'] ) )
      if ( CRM_Grant_BAO_GrantPayment::getPaymentNumber( $params['payment_number'] ) ) 
        $errors['payment_number'] = ts( "Payment number already exists." );

    if ( CRM_Utils_Rule::integer( $params['payment_batch_number'] ) )
      if (  CRM_Grant_BAO_GrantPayment::getPaymentBatchNumber( $params['payment_batch_number'] ) ) 
        $errors['payment_batch_number'] = ts( "Payment batch number already exists." );

    return empty($errors) ? true : $errors;
  }
  /**
   * Function to process the form
   *
   * @access public
   * @return None
   */
  public function postProcess() 
  {
    $details = $allGrants =array();
    CRM_Utils_System::flushCache( 'CRM_Grant_DAO_GrantPayment' );
    $values  = $this->controller->exportValues( $this->_name );
    $makePdf = true;
    foreach ( $_POST as $buttonKey => $buttonValue ) {
      if ( $buttonKey == '_qf_GrantPayment_next' ) {
        $makePdf = false;
      }
    }
    $batchNumaber = $values['payment_batch_number'];
    $this->_approvedGrants = $this->get( 'approvedGrants' );
        
    if ( $this->_prid ) {
      $query = "SELECT cp.id as pid, cg.amount_granted as total_amount, cp.currency, cp.payment_reason, cp.contact_id as id, cep.entity_id as grant_id FROM civicrm_payment as cp LEFT JOIN civicrm_entity_payment as cep ON cep.payment_id = cp.id LEFT JOIN civicrm_grant as cg ON cg.id = cep.entity_id WHERE cp.id IN (".$this->_prid.")";
    } else {
      $query = "SELECT id as grant_id, amount_granted as total_amount, currency, grant_program_id, grant_type_id, contact_id as id FROM civicrm_grant WHERE id IN (".implode(', ', array_keys($this->_approvedGrants) ).")";

    }
    $dao = CRM_Grant_DAO_Grant::executeQuery($query);
        
    
    while( $dao->fetch() ) {  
      if ( !empty( $payment_details[$dao->id] ) ) {
        $payment_details[$dao->id] .= '</td></tr><tr><td width="15%" >'.date("Y-m-d", strtotime($values['payment_date'])).'</td><td width="15%" >'.$dao->grant_id.'</td><td width="50%" >'.CRM_Grant_BAO_GrantProgram::getDisplayName( $dao->id ).'</td><td width="20%" >CAD :'.CRM_Utils_Money::format( $dao->total_amount,null, null,false );


      } else {
        $payment_details[$dao->id] = date("Y-m-d", strtotime($values['payment_date'])).'</td><td width="15%" >'.$dao->grant_id.'</td><td width="50%" >'.CRM_Grant_BAO_GrantProgram::getDisplayName( $dao->id ).'</td><td width="20%" >CAD :'.CRM_Utils_Money::format( $dao->total_amount,null, null,false );
      }

      if ( !empty( $details[$dao->id]['total_amount'] ) ) {
        $details[$dao->id]['total_amount']  = $details[$dao->id]['total_amount'] + $dao->total_amount;
      } else {
        $details[$dao->id]['total_amount']  = $dao->total_amount;
      }
      $details[$dao->id]['currency']        = $dao->currency;
      
      $contactGrants[$dao->grant_id] = $dao->id;

      if ( !$this->_prid ) {
        $grantProgramSql = "SELECT is_auto_email FROM civicrm_grant_program WHERE id  = ".$dao->grant_program_id;
        $grantProgramDao = CRM_Grant_DAO_GrantProgram::executeQuery( $grantProgramSql );
        while( $grantProgramDao->fetch() ) {
          $mailParams[$dao->grant_id]['is_auto_email']  = $grantProgramDao->is_auto_email;
        }
        $mailParams[$dao->grant_id]['amount_total']     = $dao->total_amount;
        $mailParams[$dao->grant_id]['grant_type_id']    = $dao->grant_type_id;
        $mailParams[$dao->grant_id]['grant_program_id'] = $dao->grant_program_id;
        $grantContctId[$dao->grant_id] = $dao->id;
        $gProgram = CRM_Grant_BAO_Grant::getGrantPrograms( $dao->grant_program_id );
        if( !empty( $gProgram ) ) {
          $details[$dao->id]['grant_program_id'][$gProgram[$dao->grant_program_id]] = $gProgram[$dao->grant_program_id];
        }
      } else {
        $details[$dao->id]['payment_reason'][$dao->payment_reason]   = $dao->payment_reason;
      }
    }
    $totalAmount = 0;
    foreach ( $details as $id => $value ) {
      
      $grantPayment[$id]['contact_id'] = $id;
      $grantPayment[$id]['contribution_type_id'] = $values['contribution_type_id'];
      $grantPayment[$id]['payment_batch_number'] = $values['payment_batch_number'];
      $grantPayment[$id]['payment_number'      ] = $values['payment_number'];
      $grantPayment[$id]['payment_date'        ] = date("Y-m-d", strtotime($values['payment_date']));
      $grantPayment[$id]['payment_created_date'] = date('Y-m-d');
      $grantPayment[$id]['payable_to_name'     ] = CRM_Grant_BAO_GrantProgram::getDisplayName( $id );
      $grantPayment[$id]['payable_to_address'  ] = CRM_Utils_Array::value( 'address', CRM_Grant_BAO_GrantProgram::getAddress( $id ) );
      $grantPayment[$id]['amount'              ] = $details[$id]['total_amount'];
      $grantPayment[$id]['currency'            ] = $details[$id]['currency'];
      $grantPayment[$id]['payment_status_id'   ] = 1;
      if ( $this->_prid ) {
        $grantPayment[$id]['payment_reason'     ]  = implode(', ',  $details[$id]['payment_reason']);
        $grantPayment[$id]['replaces_payment_id']  = $this->_prid;
        $grantPayment[$id]['payment_status_id'   ] = CRM_Core_OptionGroup::getValue( 'grant_payment_status', 'Reprinted', 'name' );
      } else {
        $grantPayment[$id]['payment_reason'     ] = implode(', ',  $details[$id]['grant_program_id']);
        $grantPayment[$id]['replaces_payment_id'] = 'NULL';
      }
      if ( $makePdf ) {
        $grantPayment[$id]['payment_details'] = $payment_details[$id];
      }
      $values['payment_number']++;
      $totalAmount += $details[$id]['total_amount'];
    } 
    
    foreach ( $grantPayment as $grantKey => $values ) {
      $row = array();
      $grantValues = $values;
      if ( $this->_prid ) {
        require_once 'CRM/Grant/DAO/GrantPayment.php';
        $dao = new CRM_Grant_DAO_GrantPayment( );
        $dao->id                    = $this->_prid;
        $dao->payment_status_id     = CRM_Core_OptionGroup::getValue( 'grant_payment_status', 'Stopped', 'name' );
        $dao->save();
      } 
      require_once 'CRM/Grant/Words.php';
      $words = new CRM_Grant_Words();
      $amountInWords = ucwords($words->convert_number_to_words( $values['amount'] ) );
      $values['total_in_words'] = $grantValues['total_in_words'] = $amountInWords;
      $result = CRM_Grant_BAO_GrantPayment::add( &$values, $ids = array() );
      $grantPayment[$grantKey]['amount'] = CRM_Utils_Money::format( $values['amount'], null, null,false );
      $contactPayments[$grantKey] = $result->id;
      unset($grantPayment[$grantKey]['payment_status_id']);
      if ( $makePdf ) {
        $grantPayment[$grantKey]['payment_id'] = $grantValues['payment_id'] = $result->payment_number;
      }
    }
        
    $downloadName  = check_plain('grantPayment');
    $downloadName .= '_'.date('Ymdhis');
    if ( !$makePdf )  {
      $downloadName .= '.csv';
      $fileName = CRM_Utils_File::makeFileName( $downloadName );
      $config = CRM_Core_Config::singleton();
      $file_name = $config->customFileUploadDir . $fileName;
      CRM_Grant_BAO_GrantPayment::createCSV($file_name, $grantPayment);
    } else {
      $downloadName .= '.pdf';
      $fileName = CRM_Utils_File::makeFileName( $downloadName );
      $fileName = CRM_Grant_BAO_GrantPayment::makePDF($fileName, $grantPayment );
    }
    $this->assign( 'date', date('Y-m-d'));
    $this->assign( 'time', date('H:i:s'));
    $this->assign( 'account_name',CRM_Core_DAO::getFieldValue( 'CRM_Contribute_DAO_ContributionType', $values['contribution_type_id'], 'name' ) );
    $this->assign( 'batch_number', $values['payment_batch_number']);
    $this->assign( 'contact',CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', $_SESSION[ 'CiviCRM' ][ 'userID' ], 'display_name' ) );
    $this->assign( 'grantPayment', $grantPayment );
    $this->assign( 'total_payments', count($grantPayment) );
    $this->assign( 'total_amount' , CRM_Utils_Money::format( $totalAmount, null, null,false ) );
    $this->assign( 'domain_name', CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_Domain', CRM_Core_Config::domainID( ) , 'name' ) );
    
    $checkRegisterFile = check_plain('CheckRegister');
    $checkRegisterFile .= '.pdf';
    $checkFile = CRM_Utils_File::makeFileName( $checkRegisterFile );
    $checkRegister = CRM_Grant_BAO_GrantPayment::makeReport( $checkFile, $grantPayment );
    
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
    
    $fileDAO->uri           = $checkFile;
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
                    'status_id'            => CRM_Core_OptionGroup::getValue( 'activity_status', 'Completed', 'name' ),
                    'priority_id'          => 2,
                    'details'              => "<a href=".CRM_Utils_System::url( 'civicrm/file', 'reset=1&id='.$grantPaymentFile.'&eid='.$_SESSION[ 'CiviCRM' ][ 'userID' ].'').">".$downloadName."</a></br><a href=".CRM_Utils_System::url( 'civicrm/file', 'reset=1&id='.$grantPaymentCheckFile.'&eid='.$_SESSION[ 'CiviCRM' ][ 'userID' ].'').">".$checkRegisterFile."</a>",
                     );
    CRM_Activity_BAO_Activity::create( $params );
    
    require_once 'CRM/Grant/DAO/EntityPayment.php';
    if ( $this->_prid ) {
      foreach( $contactGrants as $grantId => $contact ) {
        $entityDAO =& new CRM_Grant_DAO_EntityPayment();
        $entityDAO->entity_table = 'civicrm_grant';
        $entityDAO->entity_id    = $grantId;
        $entityDAO->payment_id   = $contactPayments[$contact];
        $entityDAO->save();
      }
      CRM_Core_Session::setStatus( "Selected payment stopped and reprinted successfully.");
    } else { 
      foreach ( $this->_approvedGrants as $grantId => $status ) {
        $grantDAO =& new CRM_Grant_DAO_Grant();
        $grantDAO->id        = $grantId;
        $grantDAO->status_id = CRM_Core_OptionGroup::getValue( 'grant_status', 'Paid', 'name' );
        $grantDAO->save();
        $entityDAO =& new CRM_Grant_DAO_EntityPayment();
        $entityDAO->entity_table = 'civicrm_grant';
        $entityDAO->entity_id    = $grantId;
        $entityDAO->payment_id   = $contactPayments[$contactGrants[$grantId]];
        $entityDAO->save();
        $grantStatus = CRM_Core_OptionGroup::values( 'grant_status' );
        $grantType   = CRM_Core_OptionGroup::values( 'grant_type' );
        $grantPrograms = CRM_Grant_BAO_Grant::getGrantPrograms();
        $this->assign( 'grant_type', $grantType[$mailParams[$grantId]['grant_type_id']] );
        $this->assign( 'grant_programs', $grantPrograms[$mailParams[$grantId]['grant_program_id']] );
        $this->assign( 'grant_status', 'Paid' );
        $this->assign( 'params', $mailParams[$grantId] );
        CRM_Grant_BAO_Grant::sendMail( $grantContctId[$grantId], $mailParams[$grantId], 'Paid' );
      }
      CRM_Core_Session::setStatus( "Created ".count($details)." payments to pay for ".count($this->_approvedGrants)." grants to ".count($details)." applicants." );
    }
    CRM_Utils_System::redirect(CRM_Utils_System::url( 'civicrm/grant/payment/search', 'reset=1&bid='.$batchNumaber.'&download='.$fileName.'&force=1'));
        
  }
}