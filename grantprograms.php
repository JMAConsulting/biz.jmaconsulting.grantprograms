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
  _grantprograms_civix_civicrm_install();
  
  $smarty = CRM_Core_Smarty::singleton();
  $config = CRM_Core_Config::singleton();
  $data = $smarty->fetch($config->extensionsDir . 'biz.jmaconsulting.grantprograms/sql/civicrm_msg_template.tpl');
  file_put_contents($config->uploadDir . "civicrm_data.sql", $data);
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $config->uploadDir . "civicrm_data.sql");
  return TRUE;
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

/*
 * hook_civicrm_grantAssessment
 *
 * @param array $params to alter
 *
 */
function grantprograms_civicrm_grantAssessment(&$params) { 
  if (array_key_exists('custom', $params)) {
    $threeYearsBackDate = date('Y', strtotime("-1 year")) . '-01-01';
    $previousYear = date('Y',strtotime("-1 year")) . '-12-31';
    $result = CRM_Core_DAO::executeQuery("SELECT id, contact_id, application_received_date, amount_granted, status_id FROM civicrm_grant WHERE status_id = 4 AND application_received_date >= '{$threeYearsBackDate}' AND application_received_date <= '{$previousYear}' AND contact_id = {$params['contact_id']}");
    $grantThresholds = CRM_Core_OptionGroup::values('grant_thresholds');
    $grantThresholds = array_flip($grantThresholds);
    if ($result->N) {
      while ($result->fetch()) {
        if ($result->amount_granted >= $grantThresholds['Maximum Grant']) {
          $priority = 10;
        } 
        else {
          $priority = 0;
        }
      }
    } 
    else {
      $priority = -10;
    }
    
    if (array_key_exists('assessment', $params)) {
      if ($params['assessment'] != 0) {
        $params['assessment'] = $params['assessment'] - $priority; //- 5 * count($years) - $priority;
      }
    }
  }
}

/*
 * hook_civicrm_buildForm civicrm hook
 * 
 * @param string $formName form name
 * @param object $form form object
 *
*/
function grantprograms_civicrm_buildForm($formName, &$form) {

  if ($formName = 'CRM_Grant_Form_Grant') {
    $form->_key= CRM_Utils_Request::retrieve('key', 'String', $form);
    $form->_next= CRM_Utils_Request::retrieve('next', 'Positive', $form);
    $form->_prev= CRM_Utils_Request::retrieve('prev', 'Positive', $form);
    if ($form->getVar('_name') == 'Grant') {
      CRM_Core_Region::instance('page-body')->add(array(
        'template' => 'CRM/Grant/Form/GrantExtra.tpl',
      ));
      $form->_reasonGrantRejected = CRM_Core_OptionGroup::values('reason_grant_rejected');
      $form->add('select', 
        'grant_rejected_reason_id', 
        ts('Reason Grant Rejected'),
        array('' => ts('- select -')) + $form->_reasonGrantRejected, 
        FALSE
      );

      $form->_grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
      $form->add('select', 
        'grant_program_id', 
        ts('Grant Programs'),
        array('' => ts('- select -')) + $form->_grantPrograms, TRUE
      );
    
      if (CRM_Core_Permission::check('administer CiviGrant')) {
        $form->add('text', 'assessment', ts('Assessment'));
      }
      // FIXME: session key errorfor 4.3
      if ($form->getVar('_context') == 'search' && 0) {
        $form->addButtons(array(
          array (
            'type' => 'upload',
            'name' => ts('Save'),
            'isDefault' => TRUE),
          array (
            'type' => 'submit',
            'name' => ts('Save and Next'),
            'subName'=> 'savenext'),
          array (
            'type' => 'upload',
            'name' => ts('Save and New'),
            'js' => array('onclick' => "return verify( );"),
            'subName' => 'new'),
          array (
            'type' => 'cancel',
            'name' => ts('Cancel')),
          )
        );
        $controller = new CRM_Core_Controller_Simple('CRM_Grant_Form_Search', ts('grants'), NULL);
        $controller->setEmbedded(TRUE);
        $controller->reset();
        $controller->set('force', 1);
        $controller->process();
        $controller->run();
      }
    } 
    elseif ($form->getVar('_name') == 'Search') {
      $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
      $form->add('select', 
        'grant_program_id',  
        ts('Grant Programs'),
        array('' => ts('- select -')) + $grantPrograms
      );
      $form->add('text', 
        'grant_amount_total_low', 
        ts('From'), 
        array('size' => 8, 'maxlength' => 8) 
      ); 
      $form->addRule('grant_amount_total_low', 
        ts('Please enter a valid money value (e.g. %1).', 
          array(1 => CRM_Utils_Money::format('9.99', ' '))), 
        'money'
      );
      $form->add('text', 
        'grant_amount_total_high', 
        ts('To'), 
        array('size' => 8, 'maxlength' => 8)
      ); 
      $form->addRule('grant_amount_total_high', 
        ts('Please enter a valid money value (e.g. %1).', 
          array(1 => CRM_Utils_Money::format('99.99', ' '))), 
        'money'
      );
      $form->add('text', 
        'grant_assessment_low', 
        ts('From'), 
        array('size' => 9, 'maxlength' => 9)
      );
        
      $form->add('text', 
        'grant_assessment_high', 
        ts('To'), 
        array('size' => 9, 'maxlength' => 9)
      );
      $form->add('text', 
        'grant_amount_low', 
        ts('From'), 
        array('size' => 8, 'maxlength' => 8)
      ); 
      $form->addRule('grant_amount_low', 
        ts('Please enter a valid money value (e.g. %1).', 
        array(1 => CRM_Utils_Money::format('9.99', ' '))), 
        'money'
      );

      $form->add('text', 
        'grant_amount_high', 
        ts('To'), 
        array('size' => 8, 'maxlength' => 8)
      );
      $form->addRule('grant_amount_high', 
        ts('Please enter a valid money value (e.g. %1).', 
        array(1 => CRM_Utils_Money::format('99.99', ' '))), 
        'money'
      );
    }
  }
  
  if ($formName == 'CRM_Custom_Form_Field') {
    
    for ($i = 1; $i <= $formName::NUM_OPTION; $i++) {
      $form->add('text', 
        'option_description['. $i .']', 
        'Marks', 
        array('id' => 'marks') 
      );
    } 
  }
  if ($formName == 'CRM_Custom_Form_Option') {
    $form->add('text', 
      'description', 
      'Marks', 
      array('id' => 'marks')
    );
  }
}

/*
 * hook_civicrm_validate
 *
 * @param string $formName form name
 * @param array $fields form submitted values
 * @param array $files file properties as sent by PHP POST protocol
 * @param object $form form object
 *
 */
function grantprograms_civicrm_validate($formName, &$fields, &$files, &$form) {
  $errors = NULL;
  if ($formName == 'CRM_Grant_Form_Grant') {
    $defaults = array();
    $params['grant_program_id'] = $form->_submitValues['grant_program_id'];
    CRM_Grant_BAO_GrantProgram::retrieve($params, $defaults);
    if ($defaults['remainder_amount'] < $form->_submitValues['amount_granted']) {
      $errors['amount_granted'] = ts('You need to increase the Grant Program Total Amount');
    }
  }
  return empty($errors) ? TRUE : $errors;
}


function grantprograms_civicrm_pre( $op, $objectName, $id, &$params ) {
}

/*
 * hook_civicrm_postProcess
 *
 * @param string $formName form name
 * @param object $form form object
 *
 */
function grantprograms_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Grant_Form_Grant') {
    // FIXME: cookies error
    if ($form->getVar('_context') == 'search' && 0) {
      $array['contact_id'] = $form->getVar('_contactID');
      $grants = CRM_Grant_BAO_GrantProgram::getGrants($array);
      $grants = array_flip(array_keys($grants));
        
      $foundit = FALSE;
      foreach ($grants as $gKey => $gVal) {
        if ($foundit) {
          $next = $gKey; 
          break;
        }
        if ($gKey == $form->_next) { 
          $next = $gKey; 
          if($gVal == end($grants)) {
            reset($grants);
            $next = key($grants);
          }
          $foundit = TRUE;
        }
      }

      if (CRM_Utils_Array::value($form->getButtonName('submit', 'savenext'), $_POST)) {
        if ($form->_id != $form->_prev) {
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/grant', 
            "reset=1&action=update&id={$form->_next}&cid={$form->_contactID}&context=search&next={$next}&prev={$form->_prev}&key={$form->_key}"));
        } 
        else {
          CRM_Core_Session::setStatus( ts('The next record in the Search no longer exists. Select another record to edit if desired.'));
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/search', 
            "force=1&qfKey={$form->_key}"));
        }
      } 
      elseif (CRM_Utils_Array::value( $form->getButtonName('upload', 'new'), $_POST)) {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/grant', 
          "reset=1&action=add&context=grant&cid={$form->_contactID}"));
      } 
      else {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/search', 
          "force=1&qfKey={$form->_key}"));
      }
    }
  }
}