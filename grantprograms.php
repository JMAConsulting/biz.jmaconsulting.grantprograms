<?php

require_once 'grantprograms.civix.php';

//define pay grants
define('PAY_GRANTS', 5);
//RG-116 hide other fields
define('EMPLOYMENT', 62);
define('EMPLOYMENT_OTHER', 63);
define('POSITION', 66);
define('POSITION_OTHER', 67);
define('EMPLOYMENT_SETTING', 68);
define('EMPLOYMENT_SETTING_OTHER', 69);
define('INITIATIVE', 72);
define('INITIATIVE_OTHER', 73);
define('COURSE', 74);
define('COURSE_OTHER', 75);
//define custom groups
define('NEI_EMPLOYMENT', 'NEI_Employment_Information');
define('NEI_GENERAL', 'NEI_General_information');
define('NEI_CONFERENCE', 'NEI_Course_conference_details');
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
  $data = $smarty->fetch($config->extensionsDir . DIRECTORY_SEPARATOR . 'biz.jmaconsulting.grantprograms/sql/civicrm_msg_template.tpl');
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
  $grantProgramParams['id'] = $params['grant_program_id'];
  $grantProgram = CRM_Grant_BAO_GrantProgram::retrieve($grantProgramParams, CRM_Core_DAO::$_nullArray);
  if (!empty($grantProgram->grant_program_id)) {
    $sumAmountGranted = CRM_Core_DAO::singleValueQuery("SELECT SUM(amount_granted) as sum_amount_granted  FROM civicrm_grant WHERE status_id = " . CRM_Core_OptionGroup::getValue('grant_status', 'Paid', 'name') . " AND grant_program_id = {$grantProgram->grant_program_id} AND contact_id = {$params['contact_id']}");
    $grantThresholds = CRM_Core_OptionGroup::values('grant_thresholds', TRUE);
    if (!empty($sumAmountGranted)) {
      if ($sumAmountGranted >= $grantThresholds['Maximum Grant']) {
        $priority = 10;
      } 
      elseif ($sumAmountGranted > 0) {
        $priority = 0;
      }
    } 
    else {
      $priority = -10;
    }
    if (array_key_exists('assessment', $params) && $params['adjustment_value']) {
      if ($params['assessment'] != 0) {
        $params['assessment'] = $params['assessment'] - $priority;
      }
    }
  }
   
  $defaults = array();
  $programParams = array('id' => $params['grant_program_id']);
  $grantProgram = CRM_Grant_BAO_GrantProgram::retrieve($programParams, $defaults);
  $algoType = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $grantProgram->allocation_algorithm, 'grouping');
  $grantStatuses = CRM_Core_OptionGroup::values('grant_status', TRUE);
  if ($algoType == 'immediate' && !CRM_Utils_Array::value('manualEdit', $params) && ($params['status_id'] == $grantStatuses['Submitted'] || $params['status_id'] == $grantStatuses['Eligible'] || $params['status_id'] == $grantStatuses['Awaiting Information'])) {
    $params['amount_granted'] = quickAllocate($grantProgram, $params);
    if (empty($params['amount_granted'])) {
      unset($params['amount_granted']);
    }
  } 
}

/**
 * Algorithm for quick allocation
 *
 */
function quickAllocate($grantProgram, $value) {
  $grantThresholds = CRM_Core_OptionGroup::values('grant_thresholds', TRUE);
  $amountGranted = NULL;
  $grant_id = NULL;
  if (CRM_Utils_Array::value('assessment', $value)) {
    $userparams['contact_id'] = $value['contact_id'];
    $userparams['grant_program_id'] = $grantProgram->id;
    if (!empty($value['id'])) {
      $grant_id = $value['id'];
    }
    $userAmountGranted = CRM_Grant_BAO_GrantProgram::getUserAllocatedAmount($userparams, $grant_id);
    $defalutGrantedAmount = CRM_Grant_BAO_GrantProgram::getCurrentGrantAmount($grant_id);
    $amountEligible = $grantThresholds['Maximum Grant'] - $userAmountGranted;
    if ($amountEligible > $grantProgram->remainder_amount) {
      $amountEligible = $grantProgram->remainder_amount;
    }
    $value['amount_total'] = str_replace(',', '', $value['amount_total']);
    $requestedAmount = ((($value['assessment'] / 100) * $value['amount_total']) * ($grantThresholds['Funding factor'] / 100));
    if ($requestedAmount > $amountEligible) {
      $requestedAmount = $amountEligible;
    }
    if ($requestedAmount > 0) {
      $remainderDifference = $requestedAmount - $defalutGrantedAmount;
      if ($remainderDifference < $grantProgram->remainder_amount) {
        $amountGranted = $requestedAmount;
      }
    }
  }
   
  //Update grant program
  if ($amountGranted > 0) {
    $grantProgramParams['remainder_amount'] = $grantProgram->remainder_amount - $remainderDifference;
    $grantProgramParams['id'] =  $grantProgram->id;
    $ids['grant_program']     =  $grantProgram->id;
    CRM_Grant_BAO_GrantProgram::create($grantProgramParams, $ids);
  }
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
      'name'  => ts('Mark remaining unapproved Grants as Ineligible'),
      'url'   => 'civicrm/grant_program',
      'qs'    => '#',
      'extra'   => 'id=reject',
      'title' => ts('Mark remaining unapproved Grants as Ineligible') 
    ),
  );
  return $_links;
}

function grantprograms_civicrm_permission(&$permissions) {
  $prefix = ts('CiviCRM Grant Program') . ': '; // name of extension or module
  $permissions['edit grant finance'] = $prefix . ts('edit grant finance');
  $permissions['cancel payments in CiviGrant'] = $prefix . ts('cancel payments in CiviGrant');
  $permissions['edit payments in CiviGrant'] = $prefix . ts('edit payments in CiviGrant');
  $permissions['create payments in CiviGrant'] = $prefix . ts('create payments in CiviGrant');
}
/*
 * hook_civicrm_buildForm civicrm hook
 * 
 * @param string $formName form name
 * @param object $form form object
 *
*/
function grantprograms_civicrm_buildForm($formName, &$form) {
  
  if ($formName == 'CRM_Grant_Form_Grant' && ($form->getVar('_action') != CRM_Core_Action::DELETE)) {
    $form->_key = CRM_Utils_Request::retrieve('key', 'String', $form);
    $form->_next = CRM_Utils_Request::retrieve('next', 'Positive', $form);
    $form->_prev = CRM_Utils_Request::retrieve('prev', 'Positive', $form);
    if (CRM_Utils_Request::retrieve('context', 'String', $form) == 'search' && isset($form->_next)) {
      $form->addButtons(array( 
        array ('type' => 'upload',
          'name' => ts('Save'), 
          'isDefault' => true),
        array ('type' => 'submit',
          'name' => ts('Save and Next'),
          'subName' => 'savenext'),   
        array ('type' => 'upload',
          'name' => ts('Save and New'), 
          'js' => array('onclick' => "return verify();"),
          'subName' => 'new' ),
        array ('type' => 'cancel', 
          'name' => ts('Cancel')),
        ) 
      );
    }
    $empId = $genId = $ccId = '-1';
    if ($form->getVar('_id')) {
      $tableName1 = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_CustomGroup', NEI_EMPLOYMENT, 'table_name', 'name');
      $query1 = "SELECT id FROM {$tableName1} WHERE entity_id = {$form->getVar('_id')}";
      $empId = CRM_Core_DAO::singleValueQuery($query1);
      $tableName2 = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_CustomGroup', NEI_GENERAL, 'table_name', 'name');
      $query2 = "SELECT id FROM {$tableName2} WHERE entity_id = {$form->getVar('_id')}";
      $genId = CRM_Core_DAO::singleValueQuery($query2);
      $tableName3 = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_CustomGroup', NEI_CONFERENCE, 'table_name', 'name');
      $query3 = "SELECT id FROM {$tableName3} WHERE entity_id = {$form->getVar('_id')}";
      $ccId = CRM_Core_DAO::singleValueQuery($query3);
    }
    
      $form->assign('employment', 'custom_'.EMPLOYMENT.'_'.$empId);
      $form->assign('employment_other', 'custom_'.EMPLOYMENT_OTHER.'_'.$empId);
      $form->assign('position', 'custom_'.POSITION.'_'.$empId);
      $form->assign('position_other', 'custom_'.POSITION_OTHER.'_'.$empId);
      $form->assign('employment_setting', 'custom_'.EMPLOYMENT_SETTING.'_'.$empId);
      $form->assign('employment_setting_other', 'custom_'.EMPLOYMENT_SETTING_OTHER.'_'.$empId);
      $form->assign('init', 'custom_'.INITIATIVE.'_'.$genId);
      $form->assign('init_other', 'custom_'.INITIATIVE_OTHER.'_'.$genId);
      $form->assign('course', 'custom_'.COURSE.'_'.$ccId);
      $form->assign('course_other', 'custom_'.COURSE_OTHER.'_'.$ccId);
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
      array('' => ts('- select -')) + $form->_grantPrograms,
      TRUE
    );
         
    //Financial Type RG-125
    $financialType = CRM_Contribute_PseudoConstant::financialType();
    if (count($financialType)) {
      $form->assign('financialType', $financialType);
    }
    $form->add('select', 'financial_type_id', 
      ts('Financial Type'), 
      array('' => ts('- Select Financial Type -')) + $financialType,
      FALSE 
    );      
    $showFields = FALSE;
    if ($form->getVar('_id')) {
      if (CRM_Core_Permission::check('administer CiviGrant')) {
        $form->add('text', 'assessment', ts('Assessment'));
      }
    
      // freeze fields based on permissions
      if (CRM_Core_Permission::check('edit grants') && !CRM_Core_Permission::check('edit grant finance')) {
        if (CRM_Utils_Array::value('amount_granted', $form->_elementIndex)) {
          $form->_elements[$form->_elementIndex['amount_granted']]->freeze();
          if (array_key_exists('assessment', $form->_elementIndex)) {
            $form->_elements[$form->_elementIndex['assessment']]->freeze();
          }
        }
        CRM_Core_Region::instance('page-body')->add(array(
          'template' => 'CRM/Grant/Form/Freeze.tpl',
        ));
      }
      $showFields = TRUE;
    }
    $form->assign('showFields', $showFields);
  }
  if ($formName == "CRM_Grant_Form_Search") {
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

  
  if ($formName == 'CRM_Custom_Form_Field') {
    
    for ($i = 1; $i <= $formName::NUM_OPTION; $i++) {
      $form->add('text', 
        'option_description['. $i .']', 
        'Mark', 
        array('id' => 'marks') 
      );
    } 
    $form->assign('edit_form', 1);
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Grant/Form/CustomFields.tpl',
    ));
  }
  if ($formName == 'CRM_Custom_Form_Option') {
    $form->add('text', 
      'description', 
      'Mark', 
      array('id' => 'marks')
    );
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Grant/Form/CustomFields.tpl',
    ));
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
    $grantType   = CRM_Grant_PseudoConstant::grantType( );
    $grantStatus = CRM_Grant_BAO_GrantProgram::grantProgramStatus( );
    foreach ( $grantProgram as $key => $value ) {
      $grantProgram[$key]['grant_type_id'] = $grantType[$grantProgram[$key]['grant_type_id']];
      $grantProgram[$key]['status_id'] = $grantStatus[CRM_Grant_BAO_GrantProgram::getOptionValue($grantProgram[$key]['status_id'])];
    }
    $form->assign('programs',$grantProgram );
    $form->assign('context', 'dashboard');
  }

  if ($formName == 'CRM_Grant_Form_Grant' && ($form->getVar('_action') & CRM_Core_Action::UPDATE) && $form->getVar('_id') && $form->getVar('_name') == 'Grant') {
    // RG-116 Hide attachments on edit
    $form->assign('hideAttachments', 1);
    $form->add('text', 'prev_assessment', ts('Prior Year\'s Assessment'));
    $priority = CRM_Grant_BAO_GrantProgram::getPriorities($form->_defaultValues['grant_program_id'], $form->getVar('_contactID'));
    $form->setDefaults(array('prev_assessment' => $priority));
    // Filter out grant being edited from search results
    $form->assign('grant_id', $form->getVar('_id'));
 }
}

function grantprograms_civicrm_pageRun( &$page ) {
  if ($page->getVar('_name') == "CRM_Grant_Page_Tab") {
    $contactId = $page->getVar('_contactId');
    if ($contactId) {
      $name = CRM_Contact_BAO_Contact::getDisplayAndImage($contactId);
      CRM_Utils_System::setTitle('Grant - '.$name[0] );
    }
    $smarty = CRM_Core_Smarty::singleton();
    if ($smarty->_tpl_vars['action'] & CRM_Core_Action::VIEW) {
      $smarty->_tpl_vars['assessment'] = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $smarty->_tpl_vars['id'], 'assessment', 'id');
      $grantProgram = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $smarty->_tpl_vars['id'], 'grant_program_id', 'id');
      $smarty->_tpl_vars['prev_assessment'] = CRM_Grant_BAO_GrantProgram::getPriorities($grantProgram, $smarty->_tpl_vars['contactId']);
      CRM_Core_Region::instance('page-body')->add(array(
        'template' => 'CRM/Grant/Page/GrantExtra.tpl',
      ));
    }
  }
  
  if ($page->getVar('_name') == "CRM_Custom_Page_Option") { 
    $params['id'] = $page->getVar('_fid');
    $params['custom_group_id'] = $page->getVar('_gid');
    CRM_Core_BAO_CustomField::retrieve(&$params, &$defaults);
    $optionValues = CRM_Core_BAO_OptionValue::getOptionValuesArray($defaults['option_group_id']);
    $smarty = CRM_Core_Smarty::singleton();
    foreach ($optionValues as $key => $value) {
      if (!empty($value['description'])) {
        $smarty->_tpl_vars['customOption'][$key]['description'] = $value['description'];
      }
    }
    $page->assign('view_form', 1);
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/Grant/Form/CustomFieldsView.tpl',
    ));
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
  if ($formName == "CRM_Admin_Form_Options" && ($form->getVar('_action') & CRM_Core_Action::DELETE) && $form->getVar('_gName') == "grant_type") {
    $defaults = array();
    $valId = $form->getVar('_values');
    $isGrantPresent = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $valId['value'], 'id', 'grant_type_id');
    $isProgramPresent = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantProgram', $form->getVar('_id'), 'id', 'grant_type_id');
    if ($isGrantPresent || $isProgramPresent) {
      $errors[''] = ts('Error');
      if ($isGrantPresent) {
        $error[] = l('Grant(s)', 'civicrm/grant?reset=1');
      }
      if ($isProgramPresent) {
        $error[] = l('Grant Program(s)', 'civicrm/grant_program?reset=1');
      }
      CRM_Core_Session::setStatus(ts('You cannot delete this Grant Type because '. implode(' and ', $error ) .' are currently using it.'), ts("Sorry"), "error");
    }
  }
  if ($formName == 'CRM_Grant_Form_Grant' && ($form->getVar('_action') != CRM_Core_Action::DELETE)) {
    $defaults = array();
    $params['id'] = $form->_submitValues['grant_program_id'];
    CRM_Grant_BAO_GrantProgram::retrieve($params, $defaults);
    if (array_key_exists('amount_granted', $form->_submitValues) && CRM_Utils_Array::value('remainder_amount', $defaults) < $form->_submitValues['amount_granted']) {
      $errors['amount_granted'] = ts('You need to increase the Grant Program Total Amount');
    }
    
    if (CRM_Utils_Array::value('amount_granted', $fields) && $fields['amount_granted'] > 0 && !CRM_Utils_Array::value('financial_type_id', $fields) && CRM_Utils_Array::value('money_transfer_date', $fields)) {
      $errors['financial_type_id'] = ts('Financial Type is a required field if Amount is Granted');
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
    $grantStatus = CRM_Core_OptionGroup::values('grant_status');
    $assessmentAmount = 0;
    $calculateAssessment = FALSE;
    $params['adjustment_value'] = TRUE;
    $previousGrant = NULL;
    if ($objectName == 'Grant' && $op == "edit") {
      $grantParams = array('id' => $id);
      $previousGrant = CRM_Grant_BAO_Grant::retrieve($grantParams, CRM_Core_DAO::$_nullArray);
      if ((CRM_Utils_Array::value('assessment', $params) == $previousGrant->assessment)) {
        $calculateAssessment = TRUE;
      }
    }
    $grantStatusApproved = array_search('Approved for Payment', $grantStatus);
    if ($grantStatusApproved == $params['status_id']  && empty($params['decision_date']) && 
      ($op == 'create') || ($previousGrant && !$previousGrant->decision_date && 
      $previousGrant->status_id != $params['status_id'])) {
      $params['decision_date'] = date('Ymd');
    }
    if ((empty($params['assessment']) || $calculateAssessment) && ($op == 'create' || $op == 'edit')) {
      if (CRM_Utils_Array::value('custom', $params)) {
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
    }
    if(!empty($assessmentAmount)) {
      $params['assessment'] = $assessmentAmount;
    } 
    elseif ($objectName == 'Grant' && $op == "edit") {
      $params['adjustment_value'] = FALSE;
    }
    
    if ($objectName == 'Grant' && $op == "edit") {
      if (!empty($previousGrant->amount_granted) && CRM_Utils_Array::value('amount_granted', $params) && CRM_Utils_Money::format($previousGrant->amount_granted) != CRM_Utils_Money::format($params['amount_granted']) && !CRM_Utils_Array::value('allocation', $params)) {
        $programParams = array('id' => $previousGrant->grant_program_id);
        $grantProgram = CRM_Grant_BAO_GrantProgram::retrieve($programParams, CRM_Core_DAO::$_nullArray);
        $remainderDifference = CRM_Utils_Rule::cleanMoney($params['amount_granted']) - $previousGrant->amount_granted;
        $grantProgramParams['remainder_amount'] = $grantProgram->remainder_amount - $remainderDifference;
        $grantProgramParams['id'] =  $grantProgram->id;
        $ids['grant_program'] =  $grantProgram->id;
        CRM_Grant_BAO_GrantProgram::create($grantProgramParams, $ids);
        $params['manualEdit'] = TRUE;
      }
    }
    elseif ($objectName == 'Grant' && $op == "create" && CRM_Utils_Array::value('amount_granted', $params)) {
      $params['manualEdit'] = TRUE;
    }
    
    if (!empty($id)) {
      $params['id'] = $id;
    }
    CRM_Utils_Hook::grantAssessment($params);
    if ($op == 'edit') {
      $smarty = CRM_Core_Smarty::singleton();
      $smarty->assign('previousGrant', $previousGrant);
    }
    $config = CRM_Core_Config::singleton();
    $config->_params = $params;
  }
}

/*
 * hook_civicrm_post
 *
 */
function grantprograms_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  //send mail after grant save
  $config = CRM_Core_Config::singleton();
  if ($objectName == 'Grant' && isset($config->_params) && !isset($config->_params['restrictEmail'])) {
    $params = $config->_params;
    // added by JMA fixme in module
    $grantProgram  = new CRM_Grant_DAO_GrantProgram();
    $grantProgram->id = $params['grant_program_id'];
    $page = new CRM_Core_Page();
    if ($grantProgram->find(TRUE)) {
      $params['is_auto_email'] = $grantProgram->is_auto_email;
    }
     
    if ($params['is_auto_email'] == 1) {
      // FIXME: for grant profiles
      $customData = array();
      if (!CRM_Utils_Array::value('custom', $params)) {
        $params['custom'] = array();
      }
      foreach ($params['custom'] as $key => $value) {
        foreach ($value as $index => $field) {
          if (!empty($field['value'])) {
            $customData[$field['custom_group_id']][$field['custom_field_id']] = $field['value'];
            if (strstr($field['column_name'], 'start_date')) {
              $startDate = $field['value'];
            }
            if (strstr($field['column_name'], 'end_date')) {
              $endDate = $field['value'];
            }
          }
        }
      }
      if (!empty( $customData)) {
        foreach ($customData as $dataKey => $dataValue) {
          $customGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',$dataKey,'title');
          $customGroup[$customGroupName] = $customGroupName;
          $count = 0;
          foreach ($dataValue  as $dataValueKey => $dataValueValue) {
            $customField[$customGroupName][$count]['label'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $dataValueKey, 'label');
            $customFieldData = grantprograms_getCustomFieldData($dataValueKey);
            if (CRM_Utils_Array::value('html_type', $customFieldData) == 'Select') {
              $customField[$customGroupName][$count]['value'] = grantprograms_getOptionValueLabel($customFieldData['option_group_id'],$dataValueValue);
            } 
            elseif (CRM_Utils_Array::value('html_type', $customFieldData) == 'Select Date') {
              $customField[$customGroupName][$count]['value'] = date('Y-m-d', strtotime($dataValueValue));
            } 
            else {
              $customField[$customGroupName][$count]['value'] = $dataValueValue;
            }
            $count++;
          }
        }
        $page->assign('customGroup', $customGroup);
        $page->assign('customField', $customField);
      }
      // EOF FIXME
      
      $grantStatus = CRM_Core_OptionGroup::values('grant_status');
      $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
      $grantTypes = CRM_Core_OptionGroup::values('grant_type');
      $grantProgram = $grantPrograms[$params['grant_program_id']];
      $grantType = $grantTypes[$params['grant_type_id']];
      $grantStatus = $grantStatus[$params['status_id']];
      
      $page->assign('grant_type', $grantType);
      $page->assign('grant_programs', $grantProgram);
      $page->assign('grant_status', $grantStatus);
      $page->assign('params', $params);
      CRM_Grant_BAO_GrantProgram::sendMail($params['contact_id'], $params, $grantStatus);
    }

    $grantStatus = CRM_Core_OptionGroup::values('grant_status', TRUE);
    if (isset($endDate)) {
      $newDate = date('Y-m-d', strtotime($endDate." +60 days"));
      if (($newDate <= date('Y-m-d') || date('Y') < date('Y',strtotime($endDate))) && $params['status_id'] == $grantStatus['Submitted']) {
        $reasonGranItneligible = CRM_Core_OptionGroup::values('reason_grant_ineligible');
        $reasonGranItneligible = array_flip($reasonGranItneligible);
        $params['status_id'] = $grantStatus['Ineligible'];
        $params['grant_rejected_reason_id'] = $reasonGranItneligible['Outside dates'];
        $result = CRM_Grant_BAO_Grant::create( $params, $ids );
      }
    }
  }
  //create financial account entry on grant create
  if ($objectName == 'Grant' && ($op == 'edit' || $op == 'create') && $objectRef->financial_type_id) {
    $smarty = CRM_Core_Smarty::singleton();
    $createItem = TRUE;
    $previousGrant = $smarty->get_template_vars('previousGrant');
    if ($previousGrant && $previousGrant->status_id == $objectRef->status_id) {
      return FALSE;
    }
    $status = CRM_Grant_PseudoConstant::grantStatus();
    $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $financialItemStatus = CRM_Core_PseudoConstant::accountOptionValues('financial_item_status');
    $amount = $objectRef->amount_granted;
    $params = array();
    if ($objectRef->status_id == array_search('Approved for Payment', $status)) {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Payable Account is' "));
      $params['to_financial_account_id'] = CRM_Contribute_PseudoConstant::financialAccountType($objectRef->financial_type_id, $relationTypeId);
      $financialItemStatusID = array_search('Unpaid', $financialItemStatus);
      $statusID = array_search('Pending', $contributionStatuses);
    }
    elseif ($objectRef->status_id == array_search('Paid', $status)) {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));
      $params['to_financial_account_id'] = CRM_Contribute_PseudoConstant::financialAccountType($objectRef->financial_type_id, $relationTypeId);
      $statusID = array_search('Completed', $contributionStatuses);
      $financialItemStatusID = array_search('Paid', $financialItemStatus);
      $createItem = FALSE;
    }
    if (CRM_Utils_Array::value('to_financial_account_id', $params)) {
      //build financial transaction params
      $trxnParams = array(
        'to_financial_account_id' => $params['to_financial_account_id'],
        'trxn_date' => date('YmdHis'),
        'total_amount' => $amount,
        'currency' => $objectRef->currency,
        'status_id' => $statusID,
      );
      $trxnEntityTable = array(
        'entity_table' => 'civicrm_grant',
        'entity_id' => $objectId,
      );
      if ($previousGrant && $previousGrant->status_id == array_search('Approved for Payment', $status) && $objectRef->status_id == array_search('Paid', $status)) {
        $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Payable Account is' "));
        $trxnParams['from_financial_account_id'] = CRM_Contribute_PseudoConstant::financialAccountType($objectRef->financial_type_id, $relationTypeId);
      }
      $trxnId = CRM_Core_BAO_FinancialTrxn::create($trxnParams, $trxnEntityTable);
      if ($createItem) {
        $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Expense Account is' "));
        $financialAccountId = CRM_Contribute_PseudoConstant::financialAccountType($objectRef->financial_type_id, $relationTypeId);
        $itemParams = array(
          'transaction_date' => date('YmdHis'),
          'contact_id' => $objectRef->contact_id, 
          'currency' => $objectRef->currency,
          'amount' => $amount,
          'description' => NULL, 
          'status_id' => $financialItemStatusID,
          'financial_account_id' => $financialAccountId,
          'entity_table' => 'civicrm_grant',
          'entity_id' => $objectId
        );
        $trxnIds['id'] = $trxnId->id;
        CRM_Financial_BAO_FinancialItem::create($itemParams, NULL, $trxnIds);
      }
    }
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

  if ($formName == "CRM_Custom_Form_Field") {
    $customGroupID = CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_OptionGroup', $form->_submitValues['label'], 'id', 'title');
    foreach ($form->_submitValues['option_label'] as $key => $value) {
      if (!empty($value)) {
        $sql = "UPDATE civicrm_option_value SET description = ".$form->_submitValues['option_description'][$key]." WHERE option_group_id = {$customGroupID} AND label = '{$value}'";
        CRM_Core_DAO::executeQuery($sql);
      }
    }
  }
  
  if ($formName == "CRM_Custom_Form_Option") {
    $params = array(
      'id' => $form->_submitValues['optionId'],
      'description' => $form->_submitValues['description'],
      'option_group_id' => $form->getVar('_optionGroupID'),
    );
    CRM_Core_BAO_OptionValue::create($params);
  }

  if ($formName == 'CRM_Grant_Form_Grant' && ($form->getVar('_action') != CRM_Core_Action::DELETE)) {
   
    // FIXME: cookies error
    if ($form->getVar('_context') == 'search') {
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
        if ($form->getVar('_id') != $form->_prev) {
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/grant', 
            "reset=1&action=update&id={$form->_next}&cid={$array['contact_id']}&context=search&next={$next}&prev={$form->_prev}&key={$form->_key}"));
        } 
        else {
          CRM_Core_Session::setStatus( ts('The next record in the Search no longer exists. Select another record to edit if desired.'));
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/search', 
            "force=1&qfKey={$form->_key}"));
        }
      } 
      elseif (CRM_Utils_Array::value( $form->getButtonName('upload', 'new'), $_POST)) {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/grant', 
          "reset=1&action=add&context=grant&cid={$array['contact_id']}"));
      } 
      else {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/search', 
          "force=1&qfKey={$form->_key}"));
      }
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
  if ($objectName == 'grant' && !strstr($_GET['q'], 'payment/search') 
    && CRM_Core_Permission::check('create payments in CiviGrant')) {
    $tasks[PAY_GRANTS] = array( 
      'title' => ts('Pay Grants'),
      'class' => array('CRM_Grant_Form_Task_Pay',
        'CRM_Grant_Form_Task_GrantPayment' 
      ),
      'result' => FALSE,
    );
  }
}

function grantprograms_getOptionValueLabel($optioGroupID, $value) {
  $query = "SELECT label FROM civicrm_option_value WHERE  option_group_id = {$optioGroupID} AND value = '{$value}' ";
  return CRM_Core_DAO::singleValueQuery($query);
}
function grantprograms_getCustomFieldData($id) {
  $customFieldData = array();
  $query = "SELECT html_type, option_group_id FROM civicrm_custom_field WHERE id = {$id} ";
  $DAO = CRM_Core_DAO::executeQuery($query);
  while ($DAO->fetch()) {
    $customFieldData['html_type'] = $DAO->html_type;
    $customFieldData['option_group_id'] = $DAO->option_group_id;
  }
  return $customFieldData;
}
