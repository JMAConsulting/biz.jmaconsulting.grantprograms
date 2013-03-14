<?php

require_once 'grantprograms.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function grantprograms_civicrm_config(&$config) {
  _grantprograms_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function grantprograms_civicrm_xmlMenu(&$files) {
  _grantprograms_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function grantprograms_civicrm_install() {
  return _grantprograms_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function grantprograms_civicrm_uninstall() {
  return _grantprograms_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function grantprograms_civicrm_enable() {
  return _grantprograms_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function grantprograms_civicrm_disable() {
  return _grantprograms_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function grantprograms_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _grantprograms_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function grantprograms_civicrm_managed(&$entities) {
  return _grantprograms_civix_civicrm_managed($entities);
}


function grantprograms_civicrm_grantAssessment( &$params ) { 
  if ( array_key_exists('custom', $params ) ) {
    $threeYearsBackDate = date('Y',strtotime("-1 year")).'-01-01';
    $previousYear       = date('Y',strtotime("-1 year")).'-12-31';
    $result = CRM_Core_DAO::executeQuery("SELECT id, contact_id, application_received_date, amount_granted, status_id FROM civicrm_grant WHERE status_id = 4 AND application_received_date >= '{$threeYearsBackDate}' AND application_received_date <= '{$previousYear}' AND contact_id = {$params['contact_id']}");
    $grantThresholds = CRM_Core_OptionGroup::values( 'grant_thresholds' );
    $grantThresholds = array_flip($grantThresholds);
    if ( $result->N ) {
      while( $result->fetch() ) {
        if ( $result->amount_granted >= $grantThresholds['Maximum Grant'] ) {
          //$years[$result->application_received_date] = $result->amount_granted;
          $priority = 10;
        } else {
          $priority = 0;
        }
      }
    } else {
      $priority = -10;
    }
    
    if( array_key_exists( 'assessment', $params ) ) {
      if ( $params['assessment'] != 0 ) {
        $params['assessment'] = $params['assessment'] - $priority;//- 5 * count($years) - $priority;
      }
    }
  }
}

function grantprograms_civicrm_buildForm( $formName, &$form  ) {

  if ($formName = 'CRM_Grant_Form_Grant'  ) {
    if ( $form->getVar('_name') == 'Grant' ) {
      $form->_reasonGrantRejected = CRM_Core_OptionGroup::values( 'reason_grant_rejected' );
      $form->add('select', 'grant_rejected_reason_id',  ts( 'Reason Grant Rejected' ),
                 array( '' => ts( '- select -' ) ) + $form->_reasonGrantRejected , false);

      $form->_grantPrograms = CRM_Grant_BAO_Grant::getGrantPrograms();
      $form->add('select', 'grant_program_id',  ts( 'Grant Programs' ),
                 array( '' => ts( '- select -' ) ) + $form->_grantPrograms , true);
    
      if ( CRM_Core_Permission::check('administer CiviGrant')  ) {
        $form->add( 'text', 'assessment', ts( 'Assessment' ) );
      }
    } elseif ( $form->getVar('_name') == 'Search' ) {
      $grantPrograms = CRM_Grant_BAO_Grant::getGrantPrograms();
      $form->add('select', 'grant_program_id',  ts( 'Grant Programs' ),
                 array( '' => ts( '- select -' ) ) + $grantPrograms);
      $form->add('text', 'grant_amount_total_low', ts('From'), array( 'size' => 8, 'maxlength' => 8 ) ); 
      $form->addRule('grant_amount_total_low', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('9.99', ' '))), 'money');
        
      $form->add('text', 'grant_amount_total_high', ts('To'), array( 'size' => 8, 'maxlength' => 8 ) ); 
      $form->addRule('grant_amount_total_high', ts('Please enter a valid money value (e.g. %1).', array(1 => CRM_Utils_Money::format('99.99', ' '))), 'money');
      $form->add('text', 'grant_assessment_low', ts('From'), array( 'size' => 9, 'maxlength' => 9 ) );
        
      $form->add('text', 'grant_assessment_high', ts('To'), array( 'size' => 9, 'maxlength' => 9 ) );
    }
  }
  
  if ($formName == 'CRM_Custom_Form_Field') {
    
    for($i = 1; $i <= $formName::NUM_OPTION; $i++) {
      $form->add('text', 'option_description['.$i.']', 'Marks', array( 'id' => 'marks') );
    } 
  }
  if( $formName == 'CRM_Custom_Form_Option'  ) {
    $form->add('text', 'description', 'Marks', array( 'id' => 'marks') );
  }
}

function grantprograms_civicrm_validate( $formName, &$fields, &$files, &$form ) {
  
  if ( $formName == 'CRM_Grant_Form_Grant' ) {
    $errors = array();
    require_once 'CRM/Grant/BAO/GrantProgram.php';
    $params['grant_program_id'] = $form->_submitValues['grant_program_id'];
    CRM_Grant_BAO_GrantProgram::retrieve( $params, $defaults);
    if ( $defaults['remainder_amount'] < $form->_submitValues['amount_granted'] ) {
      $errors['amount_granted'] = ts( 'You need to increase the Grant Program Total Amount' );
    }
  }
  return empty($errors) ? true : $errors;
}