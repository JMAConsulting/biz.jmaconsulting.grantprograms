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
 * This class generates form components for Par Service Fees
 * 
 */
class CRM_Grant_Form_GrantProgram extends CRM_Core_Form {
  protected $_id = NULL;

  protected $_fields = NULL;


  function preProcess() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
        
    $title = NULL;
    if ($this->_action & CRM_Core_Action::UPDATE) $title = ts('Edit Grant Program');
    if ($this->_action & CRM_Core_Action::DELETE) $title = ts('Delete Grant Program');
    if ($title) CRM_Utils_System::setTitle($title);
        
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/grant_program', 'reset=1'));
    $this->assign('action', $this->_action);

    $this->_values = $this->get('values');
    if (!is_array($this->_values)) {
      $this->_values = array( );
            
      // if we are editing
      if (isset($this->_id) && $this->_id) {
        $params = array('id' => $this->_id);
        CRM_Grant_BAO_GrantProgram::retrieve($params, $this->_values);
      }
      //lets use current object session.
      $this->set('values', $this->_values);
    }
  }
    
  function setDefaultValues() {
    $defaults = $this->_values;
    if (!empty( $defaults)) {
      $defaults['status_id'] = CRM_Grant_BAO_GrantProgram::getOptionValue($defaults['status_id']);
      if (!empty($defaults['allocation_date'])) {
        $defaults['allocation_date']  = strftime("%m/%d/%Y", strtotime($defaults['allocation_date']));
      }
      $defaults['allocation_algorithm'] = CRM_Grant_BAO_GrantProgram::getOptionValue($defaults['allocation_algorithm']);
    }
    if (!$this->_id) {
      $defaults['is_active'] = 1;
      $defaults['is_auto_email'] = 1;
      return $defaults;
    }

    if (!isset($defaults['from_email_address']) || $defaults['from_email_address'] == '') {
      // Set to default identity
      $defaultEmails =
        CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
      $defaults['from_email_address'] = array_pop($defaultEmails);
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm($check = FALSE) {
    parent::buildQuickForm();
        
    if ($this->_action & CRM_Core_Action::DELETE) {
            
      $this->addButtons(array(
        array ( 
          'type' => 'next',
          'name'=> ts('Delete'),
          'isDefault' => TRUE),
        array ( 'type'=> 'cancel',
                'name'=> ts('Cancel')),
        )
      );
      return;
    }

    $this->applyFilter('__ALL__','trim');
    $attributes = CRM_Core_DAO::getAttribute('CRM_Grant_DAO_GrantProgram');
    $grantPrograms = CRM_Grant_BAO_GrantProgram::grantPrograms();
        
    $this->add('text', 'label', ts('Label'),
      $attributes['label'], TRUE);
        
    $grantType = CRM_Core_OptionGroup::values('grant_type');
    $this->add('select', 'grant_type_id', ts('Grant Type'),
      array('' => ts('- select -' )) + $grantType , TRUE);

    $this->add('text', 'total_amount', ts('Total Amount'),
      $attributes['total_amount'], FALSE);         
    $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money'); 
        
    $this->add('text', 'remainder_amount', ts('Remainder Amount'),
      $attributes['remainder_amount'], FALSE);
    $this->addRule('remainder_amount', ts('Please enter a valid amount.'), 'money');

    $this->registerRule('from_identity', 'callback', '_validateIdentity', 'CRM_Grant_Form_GrantProgram');
    $this->add('text', 'from_email_address', ts('FROM Email Address'),
      $attributes['from_email_address'], FALSE);
    $this->addRule('from_email_address', ts('Please follow the proper format for From Email Address'), 'from_identity');

    $contributionTypes = CRM_Grant_BAO_GrantProgram::contributionTypes();
    $this->add('select', 'financial_type_id', ts('Financial Types'),
      array('' => ts('- select -')) + $contributionTypes, TRUE);
        
    $grantStatus = CRM_Core_OptionGroup::values('grant_program_status');
    $this->add('select', 'status_id', ts('Grant Status'),
      array('' => ts('- select -')) + $grantStatus, TRUE);

    $grantAlgorithm = CRM_Core_OptionGroup::values('allocation_algorithm');
    $this->add('select', 'allocation_algorithm', ts('Allocation Algorithm'),
      array('' => ts('- select -')) + $grantAlgorithm , TRUE); 
    
    $this->add('select', 'grant_program_id', ts("Previous Year's NEI Grant Program"),
      array('' => ts('- select -')) + $grantPrograms, FALSE);
    
    $this->addDate('allocation_date', ts('Allocation Date'), FALSE, array('formatType' => 'custom'));

    $this->add('checkbox', 'is_active', ts('Enabled?'));

    $this->add('checkbox', 'is_auto_email', ts('Auto email?'));

    $this->addButtons(array( 
      array ( 
        'type' => 'upload',
        'name' => ts('Save'), 
        'isDefault' => TRUE),
      array ( 
        'type' => 'cancel', 
        'name' => ts('Cancel')), 
      ) 
    );     
  }

  /**
   * Function to process the form
   *
   * @access public
   * @return None
   */
  public function postProcess() {
    CRM_Utils_System::flushCache('CRM_Grant_DAO_GrantProgram');

    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Grant_BAO_GrantProgram::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Grant Program has been deleted successfully.'));
      return;
    }

    $values   = $this->controller->exportValues($this->_name);
    $values['from_email_address'] = htmlspecialchars_decode($values['from_email_address']); // avoid QuickForm's safe value for this field
    $domainID = CRM_Core_Config::domainID();

    $result = $this->updateGrantProgram($values, $domainID);
    if ($result) {
      CRM_Core_Session::setStatus(ts('Grant Program  %1 has been saved.', array(1 => $result->label)));
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/grant_program', 'reset=1&action=browse&id=' . $result->id));
    }
        
  }

  function updateGrantProgram(&$values, $domainID) {
    $dao = new CRM_Grant_DAO_GrantProgram();
    if (empty($values['is_active']))
      $values['is_active'] = 0;
    if (empty($values['is_auto_email']))
      $values['is_auto_email'] = 0;
    $dao->id = $this->_id;
    $dao->domain_id = $domainID;
    $dao->label = $values['label'];
    $dao->name = $values['label'];
    $dao->grant_type_id = $values['grant_type_id'];
    $dao->total_amount = $values['total_amount'];
    $dao->remainder_amount = $values['remainder_amount'];
    $dao->from_email_address = str_replace('"<', '" <', $values['from_email_address']); // apparently we need a space
    $dao->financial_type_id = $values['financial_type_id'];
    $dao->status_id = CRM_Grant_BAO_GrantProgram::getOptionValueID(CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup','grant_program_status','id','name'), $values['status_id']);
    $dao->allocation_date = CRM_Utils_Date::processDate($values['allocation_date']);
    $dao->is_active = $values['is_active'];
    $dao->is_auto_email = $values['is_auto_email'];
    $dao->allocation_algorithm = CRM_Grant_BAO_GrantProgram::getOptionValueID(CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup','allocation_algorithm','id','name'), $values['allocation_algorithm']);
    $dao->grant_program_id = $values['grant_program_id'];
    return $dao->save();
  }

  /**
   * Validate FROM identity.
   *
   * @param $data
   * @return bool
   */
  static function _validateIdentity($data) {
    $formEmail = CRM_Utils_Mail::pluckEmailFromHeader($data);
    if (!CRM_Utils_Rule::email($formEmail)) {
      return false;
    }

    $formName = explode('"', $data);
    if (empty($formName[1]) || count($formName) != 3) {
      return false;
    }

    return true;
  }
}


