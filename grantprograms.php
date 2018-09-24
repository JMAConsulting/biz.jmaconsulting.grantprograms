<?php
require_once 'grantprograms.civix.php';
define('PAY_GRANTS', 5);
define('DELETE_GRANTS', 1);
define('PANEL_REVIEW_EVALUATION', 'civicrm_value_panel_review_evaluation_19');
define('GRANT_COMMITTEE_REVIEW', 'civicrm_value_grant_committee_review_20');
define('FULL_BOARD_REVIEW', 'civicrm_value_full_board_review_21');
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
  grantprograms_addRemoveMenu(TRUE);
  return TRUE;
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function grantprograms_civicrm_uninstall() {
  $config = CRM_Core_Config::singleton();
  return _grantprograms_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function grantprograms_civicrm_enable() {
  $config = CRM_Core_Config::singleton();
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $config->extensionsDir . 'biz.jmaconsulting.grantprograms/sql/grantprograms_enable.sql');
  grantprograms_addRemoveMenu(TRUE);
  return _grantprograms_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function grantprograms_civicrm_disable() {
  $config = CRM_Core_Config::singleton();
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $config->extensionsDir . 'biz.jmaconsulting.grantprograms/sql/grantprograms_disable.sql');
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
  if (!CRM_Utils_Array::value('grant_program_id', $params)) {
    return;
  }

  if (CRM_Utils_Array::value('custom', $params)) {
    $assessmentAmount = 0;
    foreach ($params['custom'] as $key => $value) {
      foreach($value as $fieldKey => $fieldValue) {
        if (in_array($fieldValue['table_name'], array(PANEL_REVIEW_EVALUATION, GRANT_COMMITTEE_REVIEW, FULL_BOARD_REVIEW))
          && CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $fieldValue['custom_field_id'], 'html_type') == 'Select') {
          if (is_nan((float)$fieldValue['value']) === FALSE) {
            $assessmentAmount += $fieldValue['value'];
          }
        }
      }
    }
    if ($assessmentAmount) {
      $params['assessment'] = $assessmentAmount;
    }
  }
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
  if ($algoType == 'immediate' && !CRM_Utils_Array::value('manualEdit', $params) && $params['status_id'] == CRM_Utils_Array::value('Eligible', $grantStatuses)) {
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
      'qs'    => 'id=allocation',
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

function grantprograms_civicrm_preProcess($formName, &$form) {
  if ($formName == 'CRM_Grant_Form_Search') {
    $programID = CRM_Utils_Request::retrieve('pid', 'String',
      CRM_Core_DAO::$_nullObject
    );
    if ($programID) {
      $form->_formValues['grant_program_id'] = $programID;
      $form->defaults['grant_program_id'] = $programID;
    }
  }
}

function _setDefaultFinancialEntries($grantID) {
  $sql = "SELECT ft.check_number, ft.trxn_date, ft.trxn_id, b.id as contribution_batch_id, fi.description
    FROM civicrm_entity_financial_trxn eft
    INNER JOIN civicrm_financial_trxn ft ON eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_grant' AND eft.entity_id = $grantID
    LEFT JOIN civicrm_entity_financial_trxn eft1 ON eft1.financial_trxn_id = ft.id AND eft1.entity_table = 'civicrm_financial_item'
    LEFT JOIN civicrm_financial_item fi ON eft1.entity_id = fi.id
    LEFT JOIN civicrm_entity_batch eb ON eb.entity_table ='civicrm_financial_trxn' AND eb.entity_id = ft.id
    LEFT JOIN civicrm_batch b ON b.id = eb.batch_id
    ORDER BY eft.id DESC
    LIMIT 1
  ";
  return CRM_Utils_Array::value(0, CRM_Core_DAO::executeQuery($sql)->fetchAll(), []);
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

    $form->add('select',
      'grant_program_id',
      ts('Grant Programs'),
      array('' => ts('- select -')) + CRM_Grant_BAO_GrantProgram::getGrantPrograms(),
      TRUE
    );

    //Financial Type RG-125
    $financialType = CRM_Contribute_PseudoConstant::financialType();
    if (count($financialType)) {
      $form->assign('financialType', $financialType);
    }
    $form->add('select', 'financial_type_id',
      ts('Financial Type'),
      array('' => ts('- select -')) + $financialType,
      FALSE
    );
    $showFields = FALSE;

    if ( $form->getVar('_action') == CRM_Core_Action::UPDATE && $form->getVar('_id')) {
      $grantStatuses = CRM_Core_OptionGroup::values('grant_status');
      $grantWeight = CRM_Core_OptionGroup::values('grant_status', FALSE, FALSE, FALSE, NULL, 'weight');
      $currentStatus = $form->_defaultValues['status_id'];
      $coreStatus = array(
        array_search('Eligible', $grantStatuses),
        array_search('Awaiting Information', $grantStatuses) => array('Eligible', '', ''),
        array_search('Withdrawn', $grantStatuses),
      );
      if ($currentStatus == array_search('Paid', $grantStatuses)) {
        $form->setDefaults(_setDefaultFinancialEntries($form->getVar('_id')));
      }

        $currentStatusWeight = $grantWeight[$currentStatus] + 1;
        foreach ($grantStatuses as $statusId => $statusName) {
          if ((($grantWeight[$currentStatus] >= 7 && $statusId == array_search('Ineligible', $grantStatuses))
               || ($grantWeight[$currentStatus] >= 1 && $grantWeight[$currentStatus] <= 7 && $grantWeight[$statusId] > 7 && $statusId != array_search('Ineligible', $grantStatuses))
              || $grantWeight[$currentStatus] > 7
              || $grantWeight[$statusId] < $grantWeight[$currentStatus])
              && $statusId != $currentStatus
              && $statusId != array_search('Withdrawn', $grantStatuses)
              && $currentStatusWeight != $grantWeight[$statusId]) {
            unset($grantStatuses[$statusId]);
          }
        }
      $form->removeElement('status_id');

      $element = $form->add('select', 'status_id', ts('Grant Status'),
        $grantStatuses,
        TRUE
      );
      if ($grantStatuses[$currentStatus] == 'Withdrawn') {
        $element->freeze();
      }
    }
    elseif ($form->getVar('_action') == CRM_Core_Action::ADD) {
      $grantStatuses = CRM_Core_OptionGroup::values('grant_status');
      unset($grantStatuses[array_search('Withdrawn', $grantStatuses)]);
      $form->add('select', 'status_id', ts('Grant Status'), $grantStatuses, TRUE);
    }

    CRM_Grant_Form_Task_GrantPayment::buildPaymentBlock($form, FALSE);

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

  // Expose value field for option value edit
  if ($formName == 'CRM_Admin_Form_Options') {
    $form->add('text',
      'value',
      ts('Value'),
      CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'value'),
      true
    );
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

  if ($page->getVar('_name') == "CRM_Grant_Page_DashBoard") {
    $page->assign('grantSummary', CRM_Grant_BAO_GrantPayment::getGrantSummary(CRM_Core_Permission::check('administer CiviCRM')));
  }
}

function grantprograms_civicrm_alterTemplateFile($formName, &$form, $context, &$tplName) {
  if ($tplName == 'CRM/Grant/Page/DashBoard.tpl') {
    $tplName = 'CRM/Grant/Page/DashBoardExtra.tpl';
  }
}

function grantprograms_civicrm_queryObjects(&$queryObjects, $type) {
  if ($type == 'Contact') {
     $queryObjects[] = new CRM_Grantprograms_Query();
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
function grantprograms_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
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
  if ($formName == 'CRM_Grant_Form_Grant') {
    $defaults = array();
    $params['id'] = $form->_submitValues['grant_program_id'];
    CRM_Grant_BAO_GrantProgram::retrieve($params, $defaults);
    if (array_key_exists('amount_granted', $form->_submitValues) && CRM_Utils_Array::value('remainder_amount', $defaults) < $form->_submitValues['amount_granted']) {
      $errors['amount_granted'] = ts('You need to increase the Grant Program Remainder Amount');
    }

    if (CRM_Utils_Array::value('amount_granted', $fields) && $fields['amount_granted'] > 0 && !CRM_Utils_Array::value('financial_type_id', $fields) && CRM_Utils_Array::value('money_transfer_date', $fields)) {
      $errors['financial_type_id'] = ts('Financial Type is a required field if Amount is Granted');
    }
    if (!empty($fields['status_id'])) {
      $grantStatuses = CRM_Core_OptionGroup::values('grant_status');
      if (in_array($grantStatuses[$fields['status_id']], ['Paid', 'Withdrawn', 'Approved for Payment']) && empty($fields['financial_type_id'])) {
        $errors['financial_type_id'] = ts('Financial Type is a required field');
      }
      if ($grantStatuses[$fields['status_id']] == 'Paid') {
        foreach([
          'check_number' => ts('Check Number'),
          'contribution_batch_id' => ts('Batch'),
        ] as $attr => $label) {
          if (empty($fields[$attr])) {
            $errors[$attr] = ts($label . ' is a required field');
          }
        }
      }
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
  if ($objectName == 'Grant' && in_array($op, ['edit', 'create'])) {
    $grantStatuses = CRM_Core_OptionGroup::values('grant_status');
    $grantStatusApproved = array_search('Approved for Payment', $grantStatuses);
    $calculateAssessment = FALSE;
    $previousGrant = [];
    $assessmentAmount = 0;
    if ($op == 'edit') {
      $previousGrant = civicrm_api3('Grant', 'getsingle', ['id' => $id]);
      $sendMail = (CRM_Utils_Array::value('status_id', $params) !== $previousGrant['status_id']);
      $calculateAssessment = (CRM_Utils_Array::value('assessment', $params) == $previousGrant['assessment']);
      $params['id'] = $id;
    }
    if (($grantStatusApproved == CRM_Utils_Array::value('status_id', $params) && empty($params['decision_date'])) ||
      (empty($previousGrant['decision_date']) && CRM_Utils_Array::value('status_id', $previousGrant) != CRM_Utils_Array::value('status_id', $params))
    ) {
      $params['decision_date'] = date('Ymd');
    }
    if ((empty($params['assessment']) || $calculateAssessment) && !empty($params['custom'])) {
      foreach($params['custom'] as $key => $value) {
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
    $params['assessment'] = $assessmentAmount;

    if ($op == 'edit') {
      if ($assessmentAmount == 0) {
        $params['adjustment_value'] = FALSE;
      }
      if (!CRM_Utils_Array::value('allocation', $params) &&
        !empty($previousGrant['amount_granted']) &&
        CRM_Utils_Array::value('amount_granted', $params) != $previousGrant['amount_granted'] &&
        !empty($previousGrant['grant_program_id'])
      ) {
        $programParams = ['id' => $previousGrant['grant_program_id']];
        $grantProgram = CRM_Grant_BAO_GrantProgram::retrieve($programParams, CRM_Core_DAO::$_nullArray);
        $grantProgram->remainder_amount -= CRM_Utils_Rule::cleanMoney($params['amount_granted']) - $previousGrant['amount_granted'];
        $grantProgram->save();
        $params['manualEdit'] = TRUE;
      }
      CRM_Core_Smarty::singleton()->assign('previousGrant', $previousGrant);
    }
    elseif (!empty($params['amount_granted'])) {
      $params['manualEdit'] = TRUE;
    }
    CRM_Core_BAO_Cache::setItem($params, 'grant params', __FUNCTION__);
  }
}


function grantprograms_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($objectName == 'Grant' && in_array($op, ['edit', 'create'])) {
    $params = CRM_Core_BAO_Cache::getItem("grant params", 'grantprograms_civicrm_pre');
    $smarty = CRM_Core_Smarty::singleton();
    $previousGrant = $smarty->get_template_vars('previousGrant');
    // core bug $op always return 'create'
    $op = ($op == 'create' && !empty($previousGrant)) ? 'edit' : $op;
    $grantStatuses = CRM_Core_OptionGroup::values('grant_status');
    $grantProgram  = new CRM_Grant_DAO_GrantProgram();
    if ($grantProgram->id = CRM_Utils_Array::value('grant_program_id', $params)) {
      $grantProgram->find(TRUE);
      $isAutoEmail = (!empty($grantProgram->is_auto_email));
      if ($isAutoEmail) {
        $params['is_auto_email'] = TRUE;
        $params['tplParams'] = [];
        // FIXME: for grant profiles
        $grantStatuses = CRM_Core_OptionGroup::values('grant_status');
        if (CRM_Utils_Array::value('grant_program_id', $params)) {
          $params['tplParams']['grant_programs'] = CRM_Utils_Array::value($params['grant_program_id'], CRM_Grant_BAO_GrantProgram::getGrantPrograms(), '');
        }
        $params['tplParams']['grant_type'] = CRM_Utils_Array::value($params['grant_type_id'], CRM_Core_OptionGroup::values('grant_type'));
        $params['tplParams']['grant_status'] = CRM_Utils_Array::value($params['status_id'], $grantStatuses);

        if (CRM_Utils_Array::value('grant_rejected_reason_id', $params)) {
          $params['tplParams']['grant_rejected_reason'] = CRM_Utils_Array::value($params['grant_rejected_reason_id'], CRM_Core_OptionGroup::values('reason_grant_ineligible'));
        }
        if (CRM_Utils_Array::value('grant_incomplete_reason_id', $params)) {
          $params['tplParams']['grant_incomplete_reason'] = CRM_Utils_Array::value($params['grant_incomplete_reason_id'], CRM_Core_OptionGroup::values('reason_grant_incomplete'));
        }
        $params['tplParams']['grant'] = $params;
      }

      $previousStatus = '';
      if ($previousGrant && !empty($previousGrant['status_id'])) {
        $previousGrantStatus = CRM_Utils_Array::value($previousGrant['status_id'], $grantStatuses, '');
        $currentGrantStatus = CRM_Utils_Array::value($params['status_id'], $grantStatuses, '');
        if ($isAutoEmail) {
          CRM_Grant_BAO_GrantProgram::sendMail($params['contact_id'], $params, $currentGrantStatus, $objectId, $previousGrantStatus);
        }
        else {
          CRM_Grant_BAO_GrantProgram::createStatusChangeActivity(
            $params['contact_id'],
            $currentGrantStatus,
            $previousGrantStatus,
            $params['contact_id']
          );
        }
      }
    }

    // record financial record only on 'New Grant' form or on edit when grant status is changed
    $recordFinancialRecords = ($op == 'create' || (!empty($previousGrant['status_id']) && $previousGrant['status_id'] != $objectRef->status_id));
    if (!empty($objectRef->financial_type_id) && $recordFinancialRecords) {
      $grantParams = (array) $objectRef;
      _createFinancialEntries($previousGrant['status_id'], $grantParams, $params);
    }
  }
  elseif ($objectName == 'Grant' && $op == 'delete') {
    CRM_Grant_BAO_GrantPayment::deleteGrantFinancialEntries($objectId);
  }
}

function _createFinancialEntries($previousStatusID, $grantParams, $params) {
  $grantStatuses = CRM_Core_OptionGroup::values('grant_status');
  $multiEntries = CRM_Core_BAO_Cache::getItem("multifund entries", 'multifund_civicrm_postProcess');
  $amount = $grantParams['amount_total'];
  $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
  $financialItemStatus = CRM_Core_PseudoConstant::accountOptionValues('financial_item_status');
  $currentStatusID = $grantParams['status_id'];
  $createItem = TRUE;
  $trxnParams = [];
  $financialItemStatusID = array_search('Paid', $financialItemStatus);
  if ($currentStatusID == array_search('Approved for Payment', $grantStatuses)) {
    $trxnParams['to_financial_account_id'] = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($grantParams['financial_type_id'], 'Accounts Receivable Account is');
    $financialItemStatusID = array_search('Unpaid', $financialItemStatus);
    $trxnParams['status_id'] = array_search('Pending', $contributionStatuses);
  }
  elseif ($currentStatusID == array_search('Paid', $grantStatuses)) {
    $trxnParams['to_financial_account_id'] = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($grantParams['financial_type_id'], 'Asset Account is') ?: CRM_Grant_BAO_GrantProgram::getAssetFinancialAccountID();
    $trxnParams['status_id'] = array_search('Completed', $contributionStatuses);
    $createItem = empty($previousStatusID);
  }
  elseif ($currentStatusID == array_search('Withdrawn', $grantStatuses)) {
    $trxnParams['to_financial_account_id'] = CRM_Grant_BAO_GrantProgram::getAssetFinancialAccountID();
    $trxnParams['from_financial_account_id'] = CRM_Core_DAO::singleValueQuery("
    SELECT to_financial_account_id FROM civicrm_financial_trxn  cft
      INNER JOIN civicrm_entity_financial_trxn ecft ON ecft.financial_trxn_id = cft.id
    WHERE  ecft.entity_id = " . $grantParams['id'] . " and ecft.entity_table = 'civicrm_grant'
    ORDER BY cft.id DESC LIMIT 1");
    $trxnParams['status_id'] = array_search('Cancelled', $contributionStatuses);
    $financialItemStatusID = array_search('Unpaid', $financialItemStatus);
    $amount = -$amount;
  }

  //build financial transaction params
  $trxnParams = array_merge($trxnParams, array(
    'trxn_date' => date('YmdHis'),
    'currency' => $grantParams['currency'],
    'entity_table' => 'civicrm_grant',
    'entity_id' => $grantParams['id'],
  ));

  if (empty($multiEntries)) {
    if ($previousStatusID == array_search('Approved for Payment', $grantStatuses) &&
      $currentStatusID == array_search('Paid', $grantStatuses)
    ) {
      $multiEntries[] = [
        'from_financial_account_id' => CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($grantParams['financial_type_id'], 'Accounts Receivable Account is'),
        'total_amount' => $amount,
      ];
    }
    else {
      $multiEntries[] = [
        'from_financial_account_id' => CRM_Utils_Array::value('from_financial_account_id', $trxnParams),
        'total_amount' => $amount,
      ];
    }
  }
  else {
    CRM_Core_BAO_Cache::deleteGroup("multifund entries");
  }

  $financialItemID = NULL;
  foreach ($multiEntries as $key => $entry) {
    $trxnParams = array_merge($trxnParams, $entry);
    $trxnId = CRM_Core_BAO_FinancialTrxn::create($trxnParams);

    if ($currentStatusID == array_search('Paid', $grantStatuses)) {
      CRM_Grant_Form_Task_GrantPayment::processPaymentDetails([
        'trxn_id' => $trxnId->id,
        'batch_id' => $params['contribution_batch_id'],
        'check_number' => $params['check_number'],
        'description' => CRM_Utils_Array::value('description', $params),
      ]);
    }

    if ($createItem) {
      $financialAccountId = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($grantParams['financial_type_id'], 'Accounts Receivable Account is');
      if ($financialItemID) {
        civicrm_api3('EntityFinancialTrxn', 'create', [
          'entity_table' => 'civicrm_financial_item',
          'entity_id' => $financialItemID,
          'financial_trxn_id' => $trxnId->id,
          'amount' => $entry['total_amount'],
        ]);
      }
      else {
        $itemParams = array(
          'transaction_date' => date('YmdHis'),
          'contact_id' => $grantParams['contact_id'],
          'currency' => $grantParams['currency'],
          'amount' => $entry['total_amount'],
          'description' => CRM_Utils_Array::value('description', $params),
          'status_id' => $financialItemStatusID,
          'financial_account_id' => $financialAccountId,
          'entity_table' => 'civicrm_grant',
          'entity_id' => $grantParams['id'],
        );
        $trxnIds['id'] = $trxnId->id;
        $financialItemID = CRM_Financial_BAO_FinancialItem::create($itemParams, NULL, $trxnIds)->id;
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

function grantprograms_addRemoveMenu($enable) {
  $config = CRM_Core_Config::singleton();
  $params['enableComponents'] = $config->enableComponents;
  if ($enable) {
    if (array_search('CiviGrant', $config->enableComponents)) {
      return NULL;
    }
    $params['enableComponents'][] = 'CiviGrant';
  }
  else {
    $key = array_search('CiviGrant', $params['enableComponents']);
    if ($key) {
      unset($params['enableComponents'][$key]);
    }
  }
  CRM_Core_BAO_Setting::setItem($params['enableComponents'], CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,'enable_components');
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

/**
 * Implements hook_civicrm_merge().
 * Move grant payments to new contact.
 *
 * @param $type
 * @param $data
 * @param null $mainId
 * @param null $otherId
 * @param null $tables
 */
function grantprograms_civicrm_merge($type, &$data, $mainId = NULL, $otherId = NULL, $tables = NULL) {
  if ($type == 'cidRefs') {
    global $db_url;
    if (!empty($db_url)) {
      $db_default = is_array($db_url) ? $db_url['default'] : $db_url;
      $db_default = ltrim(parse_url($db_default, PHP_URL_PATH), '/');
    } else {
      $db_default = '';
    }

    $data[$db_default . 'civicrm_payment'] = array('contact_id');
  }
}
