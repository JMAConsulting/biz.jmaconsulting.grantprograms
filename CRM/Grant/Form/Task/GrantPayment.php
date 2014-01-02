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
class CRM_Grant_Form_Task_GrantPayment extends CRM_Core_Form
{
    
  protected $_id     = null;
  protected $_fields = null;
  function preProcess( ) {
    parent::preProcess( ); 
    $this->_action     = CRM_Utils_Request::retrieve('action', 'String', $this );
    $this->_prid = CRM_Utils_Request::retrieve('prid', 'Positive', $this );
    if ( $this->_prid ) {
      $session = CRM_Core_Session::singleton();
      $url = CRM_Utils_System::url('civicrm/grant/payment/search', '_qf_PaymentSearch_display=true&force=1&reset=1');
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
    $this->add('select', 'financial_type_id',  ts( 'From account' ),
               array( '' => ts( '- select -' ) ) + $this->_contributionTypes , true);

    $this->add( 'text', 'payment_batch_number', ts( 'Payment Batch number' ),
                $attributes['label'], true );

    $this->add( 'text', 'payment_number', ts( 'Starting cheque number' ),
                $attributes['label'], true );
        
    $this->addDate( 'payment_date', ts('Payment date to appear on cheques'), false, array( 'formatType' => 'custom') );
    $buttonName = "Create Checks and CSV Export";
    if ( $this->_prid ) {
      $buttonName = "Reprint Checks and CSV Export";
    }
    $this->addButtons(array( 
                            array ( 'type'      => 'upload',
                                    'name'      => ts($buttonName), 
                                    'isDefault' => true   ),
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
    $details = $allGrants = $grantPayments = $grantAmount = array();
    $grandTotal = 0;
    CRM_Utils_System::flushCache( 'CRM_Grant_DAO_GrantPayment' );
    $values  = $this->controller->exportValues( $this->_name );
    $batchNumber = $values['payment_batch_number'];
    $this->_approvedGrants = $this->get( 'approvedGrants' );
    $grantThresholds = CRM_Core_OptionGroup::values('grant_thresholds', TRUE);
    $maxLimit = $grantThresholds['Maximum number of checks per pdf file'];

    if ( $this->_prid ) {
      $query = "SELECT cp.id as pid, cg.amount_granted as total_amount, cp.currency, cp.payment_reason, cp.contact_id as id, cep.entity_id as grant_id FROM civicrm_payment as cp LEFT JOIN civicrm_entity_payment as cep ON cep.payment_id = cp.id LEFT JOIN civicrm_grant as cg ON cg.id = cep.entity_id WHERE cp.id IN (".$this->_prid.")";
      $countQuery = "SELECT COUNT(cp.id) as ids FROM civicrm_payment as cp LEFT JOIN civicrm_entity_payment as cep ON cep.payment_id = cp.id LEFT JOIN civicrm_grant as cg ON cg.id = cep.entity_id WHERE cp.id IN (".$this->_prid.")";
    } else {
      $query = "SELECT id as grant_id, amount_granted as total_amount, currency, grant_program_id, " .
          "application_received_date, grant_type_id, contact_id as id FROM civicrm_grant " .
          "WHERE id IN (".implode(', ', array_keys($this->_approvedGrants) ).")";
      $countQuery = "SELECT COUNT(id) as grant_id FROM civicrm_grant WHERE id IN (".implode(', ', array_keys($this->_approvedGrants) ).")";
    }
    $daoCount = CRM_Grant_DAO_Grant::singleValueQuery($countQuery);
    for ($i=0; $i<$daoCount; $i=$i+$maxLimit) {
      $dao = CRM_Grant_DAO_Grant::executeQuery($query." LIMIT $i, $maxLimit");
      $grantPayment = $payment_details = $amountsTotal = $details = array();
      while( $dao->fetch() ) {
	      if (isset($amountsTotal[$dao->id])) {
          $amountsTotal[$dao->id] += $dao->total_amount;
        }
        else {
          $amountsTotal[$dao->id] = $dao->total_amount;
        }
        if ( !empty( $payment_details[$dao->id] ) ) {
        	// List all payments on this cheque (if contact has multiple applications being paid)
          $payment_details[$dao->id] .= '</td></tr><tr><td width="15%" >'.
          	date("Y-m-d", strtotime($values['payment_date'])).'</td><td width="15%" >'.
          	$dao->grant_id.'</td><td width="50%" >'.CRM_Grant_BAO_GrantProgram::getDisplayName( $dao->id ).
          	'</td><td width="20%" >'.CRM_Utils_Money::format( $dao->total_amount,null, null,false );
        } else {
          $payment_details[$dao->id] = date("Y-m-d", strtotime($values['payment_date'])).'</td><td width="15%" >'.
          	$dao->grant_id.'</td><td width="50%" >'.CRM_Grant_BAO_GrantProgram::getDisplayName( $dao->id ).
          	'</td><td width="20%" >'.CRM_Utils_Money::format( $dao->total_amount,null, null,false );
        }
        
        // Aggregate payments per contact id
        if ( !empty( $details[$dao->id]['total_amount'] ) ) {
          $details[$dao->id]['total_amount'] += $dao->total_amount;
        } else {
          $details[$dao->id]['total_amount']  = $dao->total_amount;
        }
        $details[$dao->id]['currency']        = $dao->currency;
      
        $contactGrants[$dao->grant_id] = $dao->id;

        if (isset($grantAmount[$dao->id])) {
          $grantAmount[$dao->id] += $dao->total_amount;
        } else {
          $grantAmount[$dao->id] = $dao->total_amount;
        }
        if ( !$this->_prid ) {
          $grantProgramSql = "SELECT is_auto_email FROM civicrm_grant_program WHERE id  = ".$dao->grant_program_id;
          $mailParams[$dao->grant_id]['is_auto_email'] = CRM_Grant_DAO_GrantProgram::singleValueQuery( $grantProgramSql );
          $mailParams[$dao->grant_id]['amount_total'] = $dao->total_amount;
          $mailParams[$dao->grant_id]['grant_type_id'] = $dao->grant_type_id;
          $mailParams[$dao->grant_id]['grant_program_id'] = $dao->grant_program_id;
          $mailParams[$dao->grant_id]['application_received_date'] = $dao->application_received_date;
          $grantContctId[$dao->grant_id] = $dao->id;
          $gProgram = CRM_Grant_BAO_GrantProgram::getGrantPrograms( $dao->grant_program_id );
          if( !empty( $gProgram ) ) {
            $details[$dao->id]['grant_program_id'][$gProgram[$dao->grant_program_id]] = $gProgram[$dao->grant_program_id];
          }
        } else {
          $details[$dao->id]['payment_reason'][$dao->payment_reason]   = $dao->payment_reason;
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
        $grantPayment[$id]['payable_to_name'] = CRM_Grant_BAO_GrantProgram::getDisplayName( $id );
        $grantPayment[$id]['payable_to_address'] =
          CRM_Utils_Array::value('address', CRM_Grant_BAO_GrantProgram::getAddress($id, NULL, true));
        $grantPayment[$id]['amount']  = $details[$id]['total_amount'];
        $grantPayment[$id]['currency'] = $details[$id]['currency'];
        $grantPayment[$id]['payment_status_id'   ] = 1;
        if ( $this->_prid ) {
          $grantPayment[$id]['payment_reason'     ]  = implode(', ',  $details[$id]['payment_reason']);
          $grantPayment[$id]['replaces_payment_id']  = $this->_prid;
          $grantPayment[$id]['payment_status_id'   ] = CRM_Core_OptionGroup::getValue( 'grant_payment_status', 'Reprinted', 'name' );
        } else {
          $grantPayment[$id]['payment_reason'     ] = implode(', ',  $details[$id]['grant_program_id']);
          $grantPayment[$id]['replaces_payment_id'] = 'NULL';
        }
        $grantPayment[$id]['payment_details'] = $payment_details[$id];
        $values['payment_number']++;
        $totalAmount += $details[$id]['total_amount'];
      }

      foreach ( $grantPayment as $grantKey => $grantInfo ) {
        $row = array();
        $grantValues = $grantInfo;
        if ( $this->_prid ) {
          require_once 'CRM/Grant/DAO/GrantPayment.php';
          $dao = new CRM_Grant_DAO_GrantPayment( );
          $dao->id                    = $this->_prid;
          $dao->payment_status_id     = CRM_Core_OptionGroup::getValue( 'grant_payment_status', 'Stopped', 'name' );
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
        $result = CRM_Grant_BAO_GrantPayment::add(&$savePayment, $ids = array());
        
        $grantPayment[$grantKey]['payment_id'] = $result->payment_number;
        $contactPayments[$grantKey] = $result->id;
        unset($grantPayment[$grantKey]['payment_status_id']);
      }
      $grandTotal += $totalAmount;
      $downloadNamePDF  =  check_plain('grantPayment');
      $downloadNamePDF .= '_'.date('Ymdhis');
      $this->assign('grantPayment', $grantPayment);
      $downloadNamePDF .= '.pdf';
      $fileName = CRM_Utils_File::makeFileName( $downloadNamePDF );
      $files[] = $fileName = CRM_Grant_BAO_GrantPayment::makePDF($fileName, $grantPayment );
    }
    $downloadNameCSV = check_plain('grantPayment');
    $downloadNameCSV .= '_'.date('Ymdhis');
    $this->assign('grantPayment', $grantPayment);
    $downloadNameCSV .= '.csv';
    $fileName = CRM_Utils_File::makeFileName( $downloadNameCSV );
    $config = CRM_Core_Config::singleton();
    $file_name = $config->customFileUploadDir . $fileName;
    foreach($grantAmount as $id => $value) {
      $grantPayment[$id]['amount'] = $value;
    }
    CRM_Grant_BAO_GrantPayment::createCSV($file_name, $grantPayment);
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
    

    $fileDAO =& new CRM_Core_DAO_File();
    $fileDAO->uri           = $fileName;
    $fileDAO->mime_type = 'application/zip';
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
        $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
        $this->assign( 'grant_type', $grantType[$mailParams[$grantId]['grant_type_id']] );
        $this->assign( 'grant_programs', $grantPrograms[$mailParams[$grantId]['grant_program_id']] );
        $this->assign( 'grant_status', 'Paid' );
        $this->assign( 'params', $mailParams[$grantId] );
        $this->assign('grant', $mailParams[$grantId]);
        CRM_Grant_BAO_GrantProgram::sendMail($grantContctId[$grantId], $mailParams[$grantId], 'Paid', $grantId, 'Approved for Payment');
      }
      CRM_Core_Session::setStatus( "Created ".count($details)." payments to pay for ".count($this->_approvedGrants)." grants to ".count($details)." applicants." );
    }
    CRM_Utils_System::redirect(CRM_Utils_System::url( 'civicrm/grant/payment/search', 'reset=1&bid='.$batchNumber.'&download='.$zipFile.'&force=1'));
  }
}
