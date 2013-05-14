<?php

require_once 'grantprograms.civix.php';

//define pay grants
define('PAY_GRANTS', 5);

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
  if ( array_key_exists('custom', $params ) ) {
    if ( isset($params['action'] ) ) {
    $threeYearsBackDate = date('Y',strtotime("-1 year")).'-01-01';
    $previousYear       = date('Y',strtotime("-1 year")).'-12-31';
    $result = CRM_Core_DAO::executeQuery("SELECT id, contact_id, application_received_date, amount_granted, status_id FROM civicrm_grant WHERE status_id = 4 AND application_received_date >= '{$threeYearsBackDate}' AND application_received_date <= '{$previousYear}' AND contact_id = {$params['contact_id']}");
    $grantThresholds = CRM_Core_OptionGroup::values( 'grant_thresholds' );
    $grantThresholds = array_flip($grantThresholds);
    if ( $result->N) {
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
    $defaults = array();
    $programParams = array( 'id' => $params['grant_program_id'] );
    $grantProgram = CRM_Grant_BAO_GrantProgram::retrieve($programParams, $defaults);
    $algoType = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $grantProgram->allocation_algorithm, 'grouping');
    if ($algoType == 'immediate' && !CRM_Utils_Array::value('manualEdit', $params) && ($params['status_id'] == 1 || $params['status_id'] == 2 || $params['status_id'] == 5)) {
      $params['amount_granted'] = quickAllocate($grantProgram, $params);
    } 
  }
 }

/**
 * Algorithm for quick allocation
 *
 */
function quickAllocate($grantProgram, $value) {
  $grantThresholds = CRM_Core_OptionGroup::values( 'grant_thresholds' );
  $grantThresholds = array_flip($grantThresholds);
  $amountGranted = NULL; 
  if( $grantProgram->remainder_amount == '0.00' ) {
    $totalAmount = $grantProgram->total_amount;
  } else {
    $totalAmount = $grantProgram->remainder_amount;
  }
  
  if (isset($value['assessment'])) {
    if (((($value['assessment']/100) * $value['amount_total'])*($grantThresholds['Funding factor']/100) ) < $totalAmount) { 
      if((($value['assessment']/100) * $value['amount_total'])*($grantThresholds['Funding factor']/100) <= $grantThresholds['Maximum Grant']) {
        $amountGranted = (($value['assessment']/100) * $value['amount_total'])*($grantThresholds['Funding factor']*100);
      }
    }
  }

  //Update grant program
  $grantProgramParams['remainder_amount'] = $totalAmount - $amountGranted;
  $grantProgramParams['id'] =  $grantProgram->id;
  $ids['grant_program']     =  $grantProgram->id;
  CRM_Grant_BAO_GrantProgram::create( $grantProgramParams, $ids );
  return $amountGranted;
}

/**
 * Get action Links
 *
 * @return array (reference) of action links
 */
function &links() {
  $_links = array(
    CRM_Core_Action::VIEW  => array(
      'name'  => ts('View'),
      'url'   => 'civicrm/grant_program',
      'qs'    => 'action=view&id=%%id%%&reset=1',
      'title' => ts('View Grant Program') 
    ),
    CRM_Core_Action::UPDATE  => array(
      'name'  => ts('Edit'),
      'url'   => 'civicrm/grant_program',
      'qs'    => 'action=update&id=%%id%%&reset=1',
      'title' => ts('Edit Grant Program') 
    ),
    CRM_Core_Action::DELETE  => array(
      'name'  => ts('Delete'),
      'url'   => 'civicrm/grant_program',
      'qs'    => 'action=delete&id=%%id%%&reset=1',
      'title' => ts('Delete') 
    ),
    CRM_Core_Action::ADD  => array(
      'name'  => ts('Allocate Approved (Trial)'),
      'url'   => 'civicrm/grant_program',
      'qs'    => '#',
      'extra'   => 'id=allocation',
      'title' => ts('Allocate Approved (Trial)') 
    ),
    CRM_Core_Action::BROWSE  => array(
      'name'  => ts('Finalize Approved Allocations'),
      'url'   => 'civicrm/grant_program',
      'qs'    => '#',
      'extra'   => 'id=finalize',
      'title' => ts('Finalize Approved Allocations') 
    ),
    CRM_Core_Action::MAP  => array(
      'name'  => ts('Reject Submitted and Approved Grants'),
      'url'   => 'civicrm/grant_program',
      'qs'    => '#',
      'extra'   => 'id=reject',
      'title' => ts('Reject Submitted and Approved Grants') 
    ),
  );
  return $_links;
}

function rnao_civicrm_permissions(&$permissions) {
  $prefix = ts('CiviCRM Grant Program') . ': '; // name of extension or module
  $permissions['edit grant finance'] = $prefix . ts('edit grant finance');
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
      $form->_reasonGrantRejected = CRM_Core_OptionGroup::values('reason_grant_ineligible');
      $form->add('select', 
        'grant_rejected_reason_id', 
        ts('Reason Grant Ineligible'),
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
  if ($formName == 'CRM_Grant_Form_Search' && $form->get('context') == 'dashboard') {
    $query = "SELECT
      approved.amount_granted AS approved,
      paid.amount_granted AS paid, 
      prev.amount_granted AS prev, 
      every.amount_granted AS every, 
      cov.label 
      FROM `civicrm_option_value` cov
      LEFT JOIN civicrm_option_group cog ON cog.id = cov.option_group_id
      LEFT JOIN civicrm_grant approved ON approved.status_id = cov.value AND (cov.value = 7 OR cov.value = 2) AND (YEAR(approved.application_received_date) = YEAR(now()))
      LEFT JOIN civicrm_grant paid ON paid.status_id = cov.value AND cov.value = 4 AND (YEAR(paid.application_received_date) = YEAR(now()))
      LEFT JOIN civicrm_grant prev ON prev.status_id = cov.value AND cov.value = 4 AND (YEAR(prev.application_received_date) < YEAR(now()))
      LEFT JOIN civicrm_grant every ON every.status_id = cov.value AND cov.value = 4
      WHERE cog.name = 'grant_status'";

    $dao = CRM_Core_DAO::executeQuery($query);
    $rows = array();

    while ($dao->fetch()) {
      $rows[$dao->approved]['approved'] = CRM_Utils_Money::format($dao->approved);
      $rows[$dao->paid]['paid'] = CRM_Utils_Money::format($dao->paid);
      $rows[$dao->prev]['prev'] = CRM_Utils_Money::format($dao->prev);
      $rows[$dao->every]['every'] = CRM_Utils_Money::format($dao->every);
    }
    $rows = array_intersect_key($rows, array_flip(array_filter(array_keys($rows))));
    $smarty =  CRM_Core_Smarty::singleton( );
    if (isset($rows)) {
      $smarty->assign('values', $rows);
    }
    //Version of grant program listings
    $grantProgram = array();
    require_once 'CRM/Grant/DAO/GrantProgram.php';
    $dao = new CRM_Grant_DAO_GrantProgram();
      
    $dao->orderBy('label');
    $dao->find();
      
    while ($dao->fetch()) {
      $grantProgram[$dao->id] = array();
      CRM_Core_DAO::storeValues( $dao, $grantProgram[$dao->id]);
      $action = array_sum(array_keys(links()));
      $grantProgram[$dao->id]['action'] = CRM_Core_Action::formLink(links(), $action, 
                                          array('id' => $dao->id));
    }
    require_once 'CRM/Grant/PseudoConstant.php';
    $grantType   = CRM_Grant_PseudoConstant::grantType( );
    $grantStatus = CRM_Grant_PseudoConstant::grantProgramStatus( );
    foreach ( $grantProgram as $key => $value ) {
      $grantProgram[$key]['grant_type_id'] = $grantType[CRM_Grant_BAO_GrantProgram::getOptionValue($grantProgram[$key]['grant_type_id'])];
      $grantProgram[$key]['status_id'] = $grantStatus[CRM_Grant_BAO_GrantProgram::getOptionValue($grantProgram[$key]['status_id'])];
    }
    $form->assign('programs',$grantProgram );
    $form->assign('context', 'dashboard');
  }

 if ($formName == 'CRM_Grant_Form_Grant' ) {
   //freeze fields based on permissions
   if ( CRM_Core_Permission::check('edit grants') && !CRM_Core_Permission::check('edit grant finance')  ) {
     $form->_elements[$form->_elementIndex['amount_granted']]->_flagFrozen = 1;
     $form->_elements[$form->_elementIndex['decision_date']]->_flagFrozen = 1;
     $form->_elements[$form->_elementIndex['money_transfer_date']]->_flagFrozen = 1;
     $form->assign('readOnly', TRUE);
   }
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
  if ($formName == 'CRM_Grant_Form_Search') {
    if (isset($fields['task']) && $fields['task'] == PAY_GRANTS) {
      foreach ($fields as $fieldKey => $fieldValue) {
        if (strstr($fieldKey, 'mark_x_')) {
          $grantID = ltrim( $fieldKey, 'mark_x_' );
          $grantDetails = CRM_Grant_BAO_GrantProgram::getGrants(array('id' => $grantID));
          if (!$grantDetails[$grantID]['amount_granted']) {
            $errors['task'] = ts('Payments are only possible when there is an amount owing.');
            break;
          }
        }
      }
    }
  } 
  return empty($errors) ? TRUE : $errors;
}

function grantprograms_civicrm_pre($op, $objectName, $id, &$params) {
  if ($objectName == 'Grant' && ($op == 'edit' || $op == 'create')) { 
    $assessmentAmount = 0;
    if (empty($params['assessment'])) {
      foreach ($params['custom'] as $key => $value) {
        foreach($value as $fieldKey => $fieldValue) {
          $customParams = array('id' => $key, 'is_active' => 1, 'html_type' => 'Select');
          $customFields = CRM_Core_BAO_CustomField::retrieve($customParams, $default = array());
          if (!empty($customFields)) { 
            $optionValueParams = array('option_group_id' => $customFields->option_group_id, 'value' => $fieldValue['value'], 'is_active' => 1);
            $optionValues = CRM_Core_BAO_OptionValue::retrieve($optionValueParams,  $default = array());
            if(!empty($optionValues->description)) {
              $assessmentAmount += $optionValues->description;
            }
          }
        }
      }
    }
   
    if(!empty($assessmentAmount)) {
      $params['assessment'] = $assessmentAmount;
    }
    CRM_Utils_Hook::grantAssessment($params);
  }
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
    
    $params = $form->_submitValues;
    $params['contact_id'] = $form->getVar('_contactID');
    // added by JMA fixme in module
    $grantProgram  = new CRM_Grant_DAO_GrantProgram();
      $grantProgram->id = $params['grant_program_id'];
      $page = new CRM_Core_Page();
      if ($grantProgram->find(TRUE)) {
        $params['is_auto_email'] = $grantProgram->is_auto_email;
      }
      if ($params['is_auto_email'] == 1 && !array_key_exists('resrictEmail', $params)) {
        // FIXME: for grant profiles
        $customData = array();
        if (!CRM_Utils_Array::value('custom', $params)) {
          $params['custom'] = array();
        }
        foreach($params['custom'] as $key => $value) {
          foreach ($value as $index => $field) {
            if (!empty( $field['value'])) {
              $customData[$field['custom_group_id']][$field['custom_field_id']] = $field['value'];
            }
          }
        }
        if (!empty( $customData)) {
          foreach ($customData as $dataKey => $dataValue) {
            $customGroupName = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_CustomGroup',$dataKey,'title' );
            $customGroup[$customGroupName] = $customGroupName;
            $count = 0;
            foreach ($dataValue  as $dataValueKey => $dataValueValue) {
              $customField[$customGroupName][$count]['label'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $dataValueKey, 'label');
              $customField[$customGroupName][$count]['value'] = $dataValueValue;
              $count++;
            }
          }
          $page->assign('customGroup', $customGroup);
          $page->assign('customField', $customField);
        }
        // EOF FIXME
        
        $grantStatuses = CRM_Core_OptionGroup::values('grant_status');
        $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
        $grantTypes    = CRM_Core_OptionGroup::values('grant_type');
        $grantProgram  = $grantPrograms[$params['grant_program_id']];
        $grantType     = $grantTypes[$params['grant_type_id']];
        $grantStatus   = $grantStatuses[$params['status_id']];
          
        $page->assign('grant_type', $grantType);
        $page->assign('grant_programs', $grantProgram);
        $page->assign('grant_status', $grantStatus);
        $page->assign('params', $params);
        CRM_Grant_BAO_GrantProgram::sendMail($params['contact_id'], $params, $grantStatus);
      }
  }
}

/*
 * hook_civicrm_searchTasks
 *
 * @param string $objectName form name
 * @param array $tasks search task
 *
 */
function grantprograms_civicrm_searchTasks($objectName, &$tasks) {

  if ($objectName == 'grant') {
    $tasks[PAY_GRANTS] = array( 
      'title' => ts('Pay Grants'),
      'class' => array('CRM_Grant_Form_Task_Pay',
        'CRM_Grant_Form_Task_GrantPayment' 
      ),
      'result' => FALSE,
    );
  }
}