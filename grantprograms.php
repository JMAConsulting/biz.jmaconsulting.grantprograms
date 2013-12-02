<?php
require_once 'grantprograms.civix.php';
require_once 'grantprograms_data_define.php';

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
  grantprograms_define($config->extensionsDir);
  $data = $smarty->fetch($config->extensionsDir . DIRECTORY_SEPARATOR . 'biz.jmaconsulting.grantprograms/sql/civicrm_msg_template.tpl');
  file_put_contents($config->uploadDir . "civicrm_data.sql", $data);
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $config->uploadDir . "civicrm_data.sql");
  grantprograms_addRemoveMenu(TRUE);
  return TRUE;
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function grantprograms_civicrm_uninstall() {
  $config = CRM_Core_Config::singleton();
  $file = fopen($config->extensionsDir .'biz.jmaconsulting.grantprograms/grantprograms_data_define.php', 'w'); 
  fwrite($file, "<?php\n// placeholder which ensures custom group and custom fields and custom tables.\n?>");
  fclose($file);
  return _grantprograms_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function grantprograms_civicrm_enable() {
  $config = CRM_Core_Config::singleton();
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $config->extensionsDir.'biz.jmaconsulting.grantprograms/sql/grantprograms_enable.sql');
  grantprograms_addRemoveMenu(TRUE);
  return _grantprograms_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function grantprograms_civicrm_disable() {
  $config = CRM_Core_Config::singleton();
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $config->extensionsDir.'biz.jmaconsulting.grantprograms/sql/grantprograms_disable.sql');
  grantprograms_addRemoveMenu(FALSE);
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
  if ($algoType == 'immediate' && !CRM_Utils_Array::value('manualEdit', $params) && $params['status_id'] == $grantStatuses['Eligible']) {
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
    $requestedAmount = CRM_Utils_Money::format((($value['assessment']/100) * $value['amount_total'] * ($grantThresholds['Funding factor'] / 100)), NULL, NULL, TRUE);
    // Don't grant more money than originally requested
    if ($requestedAmount > $value['amount_total']) {
    	$requestedAmount = $value['amount_total'];
    }
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
  $permissions['edit grant program'] = $prefix . ts('edit grant programs in CiviGrant');
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

  if ($formName == 'CRM_Activity_Form_Activity'
    && ($form->getVar('_action') == CRM_Core_Action::UPDATE || $form->getVar('_action') == CRM_Core_Action::VIEW)) {
    $activityType = CRM_Core_PseudoConstant::activityType();
    $activityValues = $form->getVar('_values');
    if (array_search('Grant Status Change', $activityType) != $activityValues['activity_type_id']) {
      return FALSE;
    }
    $grantUrl = CRM_Utils_System::url('civicrm/contact/view/grant', 
      'reset=1&action=view&id=' . $activityValues['source_record_id'] . '&cid=' . current($activityValues['assignee_contact']));
    $form->assign('grantUrl', $grantUrl);
  }
  
  if ($formName == 'CRM_Grant_Form_Grant' && ($form->getVar('_action') != CRM_Core_Action::DELETE)) {
    $form->_key = CRM_Utils_Request::retrieve('key', 'String', $form);
    $form->_next = CRM_Utils_Request::retrieve('next', 'Positive', $form);
    $form->_prev = CRM_Utils_Request::retrieve('prev', 'Positive', $form);
    $form->_searchGrants = CRM_Utils_Request::retrieve('searchGrants', 'String', $form);
    $form->_ncid = CRM_Utils_Request::retrieve('ncid', 'String', $form);
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
    
      $form->assign('employment', 'custom_'.EMPLOYED_INDICATE.'_'.$empId);
      $form->assign('employment_other', 'custom_'.EMPLOYMENT_OTHER.'_'.$empId);
      $form->assign('position', 'custom_'.POSITION.'_'.$empId);
      $form->assign('position_other', 'custom_'.POSITION_OTHER.'_'.$empId);
      $form->assign('employment_setting', 'custom_'.EMPLOYMENT_SETTING.'_'.$empId);
      $form->assign('employment_setting_other', 'custom_'.EMPLOYMENT_SETTING_OTHER.'_'.$empId);
      $form->assign('init', 'custom_'.NEI_HEAR_ABOUT.'_'.$genId);
      $form->assign('init_other', 'custom_'.INITIATIVE_OTHER.'_'.$genId);
      $form->assign('course', 'custom_'.COURSE_CONFERENCE_TYPE.'_'.$ccId);
      $form->assign('course_other', 'custom_'.COURSE_CONFERENCE_TYPE_OTHER.'_'.$ccId);
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

    $form->_reasonGrantIncomplete = CRM_Core_OptionGroup::values('reason_grant_incomplete');
    $form->add('select', 
      'grant_incomplete_reason_id', 
      ts('Reason Grant Incomplete'),
      array('' => ts('- select -')) + $form->_reasonGrantIncomplete, 
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
        }
        if (CRM_Utils_Array::value('assessment', $form->_elementIndex)) {
          $form->_elements[$form->_elementIndex['assessment']]->freeze();
        }
        if (CRM_Utils_Array::value('amount_total', $form->_elementIndex)) {
          $form->_elements[$form->_elementIndex['amount_total']]->freeze();
        }
        if (CRM_Utils_Array::value('amount_requested', $form->_elementIndex)) {
          $form->_elements[$form->_elementIndex['amount_requested']]->freeze();
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
    $grantType   = CRM_Core_OptionGroup::values('grant_type');
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
    //Freeze Prior Year's Assessment field
    $form->_elements[$form->_elementIndex['prev_assessment']]->freeze();
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
    CRM_Core_BAO_CustomField::retrieve($params, $defaults);
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
    if (isset($fields['task']) && CRM_Utils_Array::value('task', $fields) == PAY_GRANTS || CRM_Utils_Array::value('task', $fields) == DELETE_GRANTS) {
      foreach ($fields as $fieldKey => $fieldValue) {
        if (strstr($fieldKey, 'mark_x_')) {
          $grantID = ltrim( $fieldKey, 'mark_x_' );
          if ($fields['task'] == PAY_GRANTS) {
            $grantDetails = CRM_Grant_BAO_GrantProgram::getGrants(array('id' => $grantID));
            if (!$grantDetails[$grantID]['amount_granted']) {
              $errors['task'] = ts('Payments are only possible when there is an amount owing.');
              break;
            }
          }
          elseif ($fields['task'] == DELETE_GRANTS) {
            $params['entity_table'] = 'civicrm_grant';
            $params['entity_id'] = $grantID;
            $grantPayment = CRM_Grant_BAO_EntityPayment::retrieve($params, $defaults = CRM_Core_DAO::$_nullArray);
            if ($grantPayment) {
              $errors['task'] = ts('You cannot delete grant because grant payment is currently using it.');
              break;
            }
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
    $smarty = CRM_Core_Smarty::singleton();
    $sendMail = TRUE;
    if ($objectName == 'Grant' && $op == "edit") {
      $grantParams = array('id' => $id);
      $previousGrant = CRM_Grant_BAO_Grant::retrieve($grantParams, CRM_Core_DAO::$_nullArray);
      if ($params['status_id'] == $previousGrant->status_id) {
        $sendMail = FALSE;
      }
      if ((CRM_Utils_Array::value('assessment', $params) == $previousGrant->assessment)) {
        $calculateAssessment = TRUE;
      }
    }
    $smarty->assign('sendMail', $sendMail);
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
      if (!empty($previousGrant->amount_granted) && array_key_exists('amount_granted', $params) && CRM_Utils_Money::format($previousGrant->amount_granted) != CRM_Utils_Money::format($params['amount_granted']) && !CRM_Utils_Array::value('allocation', $params)) {
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
        $entityValues = CRM_Core_BAO_CustomValueTable::getEntityValues($objectId, 'Grant');
        getCustomFields(array_filter($entityValues), $customData);
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
      $customGroup = $customField = array();
      if (!empty( $customData)) {
        foreach ($customData as $dataKey => $dataValue) {
          $customGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup',$dataKey,'title');
          $customGroup[$customGroupName] = $customGroupName;
          $count = 0;
          foreach ($dataValue  as $dataValueKey => $dataValueValue) {
            $customField[$customGroupName]['custom_'.$dataValueKey]['label'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $dataValueKey, 'label');
            $customFieldData = grantprograms_getCustomFieldData($dataValueKey);
            if (CRM_Utils_Array::value('html_type', $customFieldData) == 'Select') {
              $customField[$customGroupName]['custom_'.$dataValueKey]['value'] = grantprograms_getOptionValueLabel($customFieldData['option_group_id'],$dataValueValue);
            } 
            elseif (CRM_Utils_Array::value('html_type', $customFieldData) == 'Select Date') {
              $customField[$customGroupName]['custom_'.$dataValueKey]['value'] = date('Y-m-d', strtotime($dataValueValue));
            } 
            else {
              $customField[$customGroupName]['custom_'.$dataValueKey]['value'] = $dataValueValue;
            }
            $count++;
          }
        }
      }
      $page->assign('customGroup', $customGroup);
      $page->assign('customField', $customField);
      
      $grantStatuses = $grantStatus = CRM_Core_OptionGroup::values('grant_status');
      $grantPrograms = CRM_Grant_BAO_GrantProgram::getGrantPrograms();
      $grantTypes = CRM_Core_OptionGroup::values('grant_type');
      $grantProgram = $grantPrograms[$params['grant_program_id']];
      $grantType = $grantTypes[$params['grant_type_id']];
      $grantStatus = $grantStatus[$params['status_id']];
      $grantIneligibleReasons = CRM_Core_OptionGroup::values('reason_grant_ineligible');
      $grantIncompleteReasons = CRM_Core_OptionGroup::values('reason_grant_incomplete');
      
      $page->assign('grant_type', $grantType);
      $page->assign('grant_programs', $grantProgram);
      $page->assign('grant_status', $grantStatus);
      if (CRM_Utils_Array::value('grant_rejected_reason_id', $params)) {
        $params['grant_rejected_reason'] = $grantIneligibleReasons[$params['grant_rejected_reason_id']];
      }
      if (CRM_Utils_Array::value('grant_incomplete_reason_id', $params)) {
        $params['grant_incomplete_reason'] = $grantIncompleteReasons[$params['grant_incomplete_reason_id']];
      }
      $page->assign('grant', $params);
      $smarty = CRM_Core_Smarty::singleton();
      $sendMail = $smarty->get_template_vars('sendMail');
      if ($sendMail) {
        $previousGrant = $smarty->get_template_vars('previousGrant');
        $previousStatus = '';
        if ($previousGrant && property_exists($previousGrant, 'status_id')) {
          $previousStatus = $grantStatuses[$previousGrant->status_id];
        }
        CRM_Grant_BAO_GrantProgram::sendMail($params['contact_id'], $params, $grantStatus, $objectId, $previousStatus);
      }
    }
    
    $grantStatus = CRM_Core_OptionGroup::values('grant_status', TRUE);
    if (isset($endDate)) {
      $infoTooLate = key(CRM_Core_PseudoConstant::accountOptionValues('grant_info_too_late'));
      $days = ' +' . $infoTooLate . ' days';
      $newDate = date('Y-m-d', strtotime($endDate . $days));
      if (($newDate <= date('Y-m-d') || date('Y') < date('Y',strtotime($endDate))) && $params['status_id'] == $grantStatus['Submitted']) {
        $reasonGranItneligible = CRM_Core_OptionGroup::values('reason_grant_ineligible');
        $reasonGranItneligible = array_flip($reasonGranItneligible);
        $params['status_id'] = $grantStatus['Ineligible'];
        $params['grant_rejected_reason_id'] = $reasonGranItneligible['Outside dates'];
        $ids['grant_id'] = $objectId;
        $result = CRM_Grant_BAO_Grant::create($params, $ids);
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
    $status = CRM_Core_OptionGroup::values('grant_status');
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
      $createItem = FALSE;
    }
    elseif ($objectRef->status_id == array_search('Withdrawn', $status)) {
      $params['to_financial_account_id'] = CRM_Core_DAO::singleValueQuery("SELECT to_financial_account_id FROM civicrm_financial_trxn  cft
INNER JOIN civicrm_entity_financial_trxn ecft ON ecft.financial_trxn_id = cft.id
WHERE  ecft.entity_id = {$objectRef->id} and ecft.entity_table = 'civicrm_grant'
ORDER BY cft.id DESC LIMIT 1");
      $statusID = array_search('Cancelled', $contributionStatuses);
      $financialItemStatusID = array_search('Unpaid', $financialItemStatus);
      $amount = -$amount;
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
        $sql = "UPDATE civicrm_option_value SET description = %1 WHERE option_group_id = %2 AND label = %3";
        $params = array(
          1 => array($form->_submitValues['option_description'][$key], 'String'),
          2 => array($customGroupID, 'Integer'),
          3 => array($value, 'String'),
        );
        CRM_Core_DAO::executeQuery($sql, $params);
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
      $array['contact_id'] = $form->_ncid;
      $searchGrants = explode(',', $form->_searchGrants);
      $grants = array_flip($searchGrants);
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
      $grantParams['id'] = $next;
      $result = CRM_Grant_BAO_GrantProgram::getGrants($grantParams);
      if (CRM_Utils_Array::value($form->getButtonName('submit', 'savenext'), $_POST)) {
        if ($form->getVar('_id') != $form->_prev) {
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/grant', 
            "reset=1&action=update&id={$form->_next}&cid={$array['contact_id']}&context=search&next={$next}&prev={$form->_prev}&key={$form->_key}&ncid={$result[$next]['contact_id']}&searchGrants={$form->_searchGrants}"));
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
  $value = CRM_Core_DAO::escapeString($value);
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

function grantprograms_define($extensionsDir) {
  $file  = fopen($extensionsDir .'biz.jmaconsulting.grantprograms/grantprograms_data_define.php', 'w'); 
  fwrite($file, "<?php\n\n//define custom table Names.\ndefine('COURSE_CONFERENCE_DETAILS', 'civicrm_value_nei_course_conference_details');\ndefine('EMPLOYMENT_INFORMATION', 'civicrm_value_nei_employment_information');\ndefine('GENERAL_INFORMATION', 'civicrm_value_nei_general_information');\ndefine('NEI_ID_TABLE', 'civicrm_value_nei_id');\n\n");

  fwrite($file, "//define custom group Names.\ndefine('NEI_EMPLOYMENT', 'NEI_Employment_Information');\ndefine('NEI_GENERAL', 'NEI_General_information');\ndefine('NEI_CONFERENCE', 'NEI_Course_conference_details');\n\n");

  fwrite($file, "//define custom groups Ids.\n");
  $tables = array(
    'civicrm_value_nei_employment_information' => 'COURSE_CONFERENCE_DETAILS_ID',
    'civicrm_value_nei_general_information' => 'EMPLOYMENT_INFORMATION_ID',
    'civicrm_value_nei_course_conference_details' => 'GENERAL_INFORMATION_ID',
    'civicrm_value_nei_id' => 'NEI_ID',
  );
  foreach ($tables as $tableKey => $tableValue) {
    fwrite($file, "define('".$tableValue."', '".CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $tableKey, 'id' ,'table_name')."');\n");
  }
  
  fwrite($file, "\n//define custom field Ids and Columns.\n");
  $customFields = array(
    'predominant_clinical_area_of_pra' => 'NEI_PRACTICE_AREA',
    'nei_employment_status' => 'NEI_EMPLOYMENT_STATUS',
    'if_you_are_not_employed_indicate' => 'EMPLOYED_INDICATE',
    'other' => 'EMPLOYMENT_OTHER',
    'employer_name' => 'EMPLOYER_NAME',
    'province_of_employment' => 'PROVINCES_OF_EMPLOYMENT',
    'position' => 'POSITION',
    'select_or_other' => 'POSITION_OTHER',
    'employment_setting' => 'EMPLOYMENT_SETTING',
    'employment_setting_other' => 'EMPLOYMENT_SETTING_OTHER',
    'work_phone' => 'WORK_PHONE',
    'work_phone_extension' => 'WORK_PHONE_EXTENSION',
    'how_did_you_hear_about_this_init' => 'NEI_HEAR_ABOUT',
    'other_initiative' => 'INITIATIVE_OTHER',
    'course_conference_type' => 'COURSE_CONFERENCE_TYPE',
    'course_conference_type_other' => 'COURSE_CONFERENCE_TYPE_OTHER',
    'course_conference_code' => 'COURSE_CONFERENCE_CODE',
    'course_conference_name' => 'COURSE_CONFERENCE_NAME',
    'course_conference_provider' => 'COURSE_CONFERENCE_PROVDER',
    'how_will_this_course_enhance_the' => 'NEI_COURSE_ENHANCEMENT',
    'proof_of_completion' => 'PROOF_OF_COMPELTION',
    'proof_of_payment' => 'PROOF_OF_PAYMENT',
    'type_of_course_provider' => 'COURSE_PROVIDER_TYPE',
    'start_date' => 'START_DATE',
    'end_date' => 'END_DATE',
    'college_of_nurses_of_ontario_reg' => 'NEI_CNO_REGISTRATION_ID',
    'social_insurance_number' => 'NEI_CIN',
  );
  
  foreach ($customFields as $tableKey => $tableValue) {
    fwrite($file, "define('".$tableValue."', '".CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $tableKey, 'id' ,'column_name')."');\n");
    fwrite($file, "define('".$tableValue."_COLUMN', '".$tableKey."');\n");
  } 
  fwrite($file, "\ndefine('PAY_GRANTS', 5);\ndefine('DELETE_GRANTS', 1);\n\n?>");
  fclose($file);
  return;
}

function grantprograms_addRemoveMenu($enable) {
  $config_backend = unserialize(CRM_Core_DAO::singleValueQuery('SELECT config_backend FROM civicrm_domain WHERE id = 1'));
  $params['enableComponents'] = $config_backend['enableComponents'];
  $params['enableComponentIDs'] = $config_backend['enableComponentIDs'];
  if ($enable) {
    $params['enableComponents'][] = 'CiviGrant';
    $params['enableComponentIDs'][] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_component WHERE name = 'CiviGrant'");
  }
  else {
    foreach (array_keys($params['enableComponents'], 'CiviGrant', TRUE) as $key) {
      unset($params['enableComponents'][$key]);
    }
    foreach (array_keys($params['enableComponentIDs'], (int)CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_component WHERE name = 'CiviGrant'"), TRUE) as $key) {
      unset($params['enableComponentIDs'][$key]);
    }
  }
  CRM_Core_BAO_ConfigSetting::create($params);
  return;
}
 
function getCustomFields($params, &$values) {
  static $_customGroup = array();
  if (empty($_customGroup)) {
    $query = "SELECT ccf.id, ccg.id custom_group FROM civicrm_custom_group ccg
LEFT JOIN civicrm_custom_field ccf ON ccf.custom_group_id = ccg.id
WHERE ccg.name LIKE 'NEI_%' ORDER BY ccg.id";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $_customGroup[$dao->custom_group][$dao->id] = $dao->id;
    }
  }
  foreach ($_customGroup as $key => $val) {
    $values[$key] = array_intersect_key($params, $val);
  }
}

/**
 * Hook implementation when an email is about to be sent by CiviCRM.
 *
 */
function grantprograms_civicrm_alterMailParams(&$params) {
  if (substr($params['valueName'], 0, 6) == 'grant_') {
    CRM_Core_Smarty::singleton()->assign('messageBody', $params['html']);
  }
}

function grantprograms_civicrm_links( $op, $objectName, $objectId, &$links ) {
      if ($op == 'create.new.shorcuts' && (CRM_Core_Permission::check('access CiviGrant') &&
      CRM_Core_Permission::check('edit grant program')) ) {
      // add link to create new profile
      $links[] = array( 'url'   => CRM_Utils_System::url('civicrm/grant_program', 'reset=1&action=browse', FALSE),
                 'title' => ts('Grant Program'),
                 'ref'   => 'new-grant program');
    }
}