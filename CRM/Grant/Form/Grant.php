<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for processing a case
 *
 */
class CRM_Grant_Form_Grant extends CRM_Core_Form {

  /**
   * the id of the case that we are proceessing
   *
   * @var int
   * @protected
   */
  protected $_id;

  /**
   * the id of the contact associated with this contribution
   *
   * @var int
   * @protected
   */
  protected $_contactID;

  protected $_context;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    //custom data related code
    $this->_cdType = CRM_Utils_Array::value('type', $_GET);
    $this->assign('cdType', FALSE);
    if ($this->_cdType) {
      $this->assign('cdType', TRUE);
      CRM_Custom_Form_CustomData::preProcess($this);
      return;
    }

    $this->_contactID = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_id= CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_key= CRM_Utils_Request::retrieve('key', 'String', $this);
    $this->_next= CRM_Utils_Request::retrieve('next', 'Positive', $this);
    $this->_prev= CRM_Utils_Request::retrieve('prev', 'Positive', $this);
    $this->_grantType = NULL;
    if ($this->_id) {
      $this->_grantType =
        CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $this->_id, 'grant_type_id');
    }
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);

    $this->assign('action', $this->_action);
    $this->assign('context', $this->_context);

    //check permission for action.
    if (!CRM_Core_Permission::checkActionPermission('CiviGrant', $this->_action)) {
      CRM_Core_Error::fatal(ts('You do not have permission to access this page'));
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      return;
    }

    $this->_noteId = NULL;
    if ($this->_id) {
      $noteDAO = new CRM_Core_BAO_Note();
      $noteDAO->entity_table = 'civicrm_grant';
      $noteDAO->entity_id = $this->_id;
      if ($noteDAO->find(TRUE)) {
        $this->_noteId = $noteDAO->id;
      }
    }

    // when custom data is included in this page
    if (CRM_Utils_Array::value('hidden_custom', $_POST)) {
      $this->set('type', 'Grant');
      $this->set('subType', CRM_Utils_Array::value('grant_type_id', $_POST));
      $this->set('entityId', $this->_id);
      CRM_Custom_Form_CustomData::preProcess($this);
      CRM_Custom_Form_CustomData::buildQuickForm($this);
      CRM_Custom_Form_CustomData::setDefaultValues($this);
    }
  }

  function setDefaultValues() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::setDefaultValues($this);
    }

    $defaults = array();
    $defaults = parent::setDefaultValues();

    if ($this->_action & CRM_Core_Action::DELETE) {
      return $defaults;
    }

    $params['id'] = $this->_id;
    if ($this->_noteId) {
      $defaults['note'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Note', $this->_noteId, 'note');
    }
    if ($this->_id) {
      CRM_Grant_BAO_Grant::retrieve($params, $defaults);

      // fix the display of the monetary value, CRM-4038
      if (isset($defaults['amount_total'])) {
        $defaults['amount_total'] = CRM_Utils_Money::format($defaults['amount_total'], NULL, '%a');
      }
      if (isset($defaults['amount_requested'])) {
        $defaults['amount_requested'] = CRM_Utils_Money::format($defaults['amount_requested'], NULL, '%a');
      }
      if (isset($defaults['amount_granted'])) {
        $defaults['amount_granted'] = CRM_Utils_Money::format($defaults['amount_granted'], NULL, '%a');
      }

      $dates = array(
        'application_received_date',
        'decision_date',
        'money_transfer_date',
        'grant_due_date',
      );

      foreach ($dates as $key) {
        if (CRM_Utils_Array::value($key, $defaults)) {
          list($defaults[$key]) = CRM_Utils_Date::setDateDefaults($defaults[$key]);
        }
      }
    }
    else {
      list($defaults['application_received_date']) = CRM_Utils_Date::setDateDefaults();
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_cdType) {
      return CRM_Custom_Form_CustomData::buildQuickForm($this);
    }

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Delete'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
      return;
    }

    $attributes = CRM_Core_DAO::getAttribute('CRM_Grant_DAO_Grant');
    $this->_grantTypes = CRM_Core_OptionGroup::values('grant_type');
    $this->add('select', 'grant_type_id', ts('Grant Type'),
      array(
        '' => ts('- select -')) + $this->_grantTypes, TRUE,
      array('onChange' => "CRM.buildCustomData( 'Grant', this.value );")
    );

    //need to assign custom data type and subtype to the template
    $this->assign('customDataType', 'Grant');
    $this->assign('customDataSubType', $this->_grantType);
    $this->assign('entityID', $this->_id);

    $this->_grantStatus = CRM_Core_OptionGroup::values('grant_status');
    $this->add('select', 'status_id', ts('Grant Status'),
      array(
        '' => ts('- select -')) + $this->_grantStatus, TRUE
    );

    $this->addDate('application_received_date', ts('Application Received'), FALSE, array('formatType' => 'custom'));
    $this->addDate('decision_date', ts('Grant Decision'), FALSE, array('formatType' => 'custom'));
    $this->addDate('money_transfer_date', ts('Money Transferred'), FALSE, array('formatType' => 'custom'));
    $this->addDate('grant_due_date', ts('Grant Report Due'), FALSE, array('formatType' => 'custom'));

    $this->addElement('checkbox', 'grant_report_received', ts('Grant Report Received?'), NULL);
    $this->add('textarea', 'rationale', ts('Rationale'), $attributes['rationale']);
    $this->add('text', 'amount_total', ts('Amount Requested'), NULL, TRUE);
    $this->addRule('amount_total', ts('Please enter a valid amount.'), 'money');

    $this->add('text', 'amount_granted', ts('Amount Granted'));
    $this->addRule('amount_granted', ts('Please enter a valid amount.'), 'money');

    $this->add('text', 'amount_requested', ts('Amount Requested<br />(original currency)'));
    $this->addRule('amount_requested', ts('Please enter a valid amount.'), 'money');

    $noteAttrib = CRM_Core_DAO::getAttribute('CRM_Core_DAO_Note');
    $this->add('textarea', 'note', ts('Notes'), $noteAttrib['note']);

    // add attachments part
    CRM_Core_BAO_File::buildAttachment($this,
      'civicrm_grant',
      $this->_id
    );

    // make this form an upload since we dont know if the custom data injected dynamically
    // is of type file etc $uploadNames = $this->get( 'uploadNames' );
    $this->addButtons(array(
      array(
        'type' => 'upload',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'upload',
        'name' => ts('Save and New'),
        'js' => array('onclick' => "return verify( );"),
        'subName' => 'new',
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
     )
    );

    if ($this->_context == 'standalone') {
      CRM_Contact_Form_NewContact::buildQuickForm($this);
      $this->addFormRule(array('CRM_Grant_Form_Grant', 'formRule'), $this);
    }
    if ($this->_context == 'search') {
      $this->addButtons(array( 
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
          'js' => array( 'onclick' => "return verify( );" ),
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

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $self) {
    $errors = array();

    //check if contact is selected in standalone mode
    if (isset($fields['contact_select_id'][1]) && !$fields['contact_select_id'][1]) {
      $errors['contact[1]'] = ts('Please select a contact or create new contact');
    }

    return $errors;
  }

  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Grant_BAO_Grant::del($this->_id);
      return;
    }

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $ids['grant'] = $this->_id;
    }

    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    if (!CRM_Utils_Array::value('grant_report_received', $params)) {
      $params['grant_report_received'] = "null";
    }

    // set the contact, when contact is selected
    if (CRM_Utils_Array::value('contact_select_id', $params)) {
      $this->_contactID = $params['contact_select_id'][1];
    }

    $params['contact_id'] = $this->_contactID;
    $ids['note'] = array();
    if ($this->_noteId) {
      $ids['note']['id'] = $this->_noteId;
    }
    // added by JMA
    $array['contact_id'] = $this->_contactID;
    
    // build custom data getFields array
    $customFieldsGrantType = CRM_Core_BAO_CustomField::getFields('Grant', FALSE, FALSE,
      CRM_Utils_Array::value('grant_type_id', $params)
    );
    $customFields = CRM_Utils_Array::crmArrayMerge($customFieldsGrantType,
      CRM_Core_BAO_CustomField::getFields('Grant', FALSE, FALSE, NULL, NULL, TRUE)
    );
    $params['custom'] = CRM_Core_BAO_CustomField::postProcess($params,
      $customFields,
      $this->_id,
      'Grant'
    );

    // add attachments as needed
    CRM_Core_BAO_File::formatAttachment($params,
      $params,
      'civicrm_grant',
      $this->_id
    );
    // Added by JMA
    // FIXME transfer in module
    $grantProgram = new CRM_Grant_DAO_GrantProgram();
    $grantProgram->id = $params['grant_program_id'];
    if ($grantProgram->find(TRUE)) {
      $params['is_auto_email'] = $grantProgram->is_auto_email;
    }
    $params['grant_status'] = $grantStatus = $this->_grantStatus[$params['status_id']];
    $params['grant_type'] = $grantType = $this->_grantType[$params['grant_type_id']];
    $params['grant_programs'] = $grantPrograms = $this->_grantPrograms[$params['grant_program_id']];
    // EOF FIXME
    $grant = CRM_Grant_BAO_Grant::create($params, $ids);
      
    $grants = CRM_Grant_BAO_GrantProgram::getGrants($array);
    $grants = array_flip(array_keys($grants));
        
    $foundit = FALSE;
    foreach ($grants as $gKey => $gVal) {
      if ($foundit) {
        $next = $gKey; 
        break;
      }
      if ($gKey == $this->_next) { 
        $next = $gKey; 
        if($gVal == end($grants)) {
          reset($grants);
          $next = key($grants);
        }
        $foundit = TRUE;
      }
    }

    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();

    //added by JMA
    if ( $this->_context == 'search' ) {
      if ( CRM_Utils_Array::value( $this->getButtonName( 'submit', 'savenext' ), $_POST ) ) {
        if ($this->_id != $this->_prev) {
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/grant', 
                                                           "reset=1&action=update&id={$this->_next}&cid={$this->_contactID}&context=search&next={$next}&prev={$this->_prev}&key={$this->_key}") );

        } else {
          CRM_Core_Session::setStatus( ts( 'The next record in the Search no longer exists. Select another record to edit if desired.' ) );
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/search', 
                                                           "force=1&qfKey={$this->_key}") );
        }
      } else if( CRM_Utils_Array::value( $this->getButtonName( 'upload', 'new' ), $_POST ) ) {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/grant', 
                                                         "reset=1&action=add&context=grant&cid={$this->_contactID}") );

      } else {
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/grant/search', 
                                                         "force=1&qfKey={$this->_key}") );
      }
    }



    if ($this->_context == 'standalone') {
      if ($buttonName == $this->getButtonName('upload', 'new')) {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/grant/add',
            'reset=1&action=add&context=standalone'
          ));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
            "reset=1&cid={$this->_contactID}&selectedChild=grant"
          ));
      }
    }
    elseif ($buttonName == $this->getButtonName('upload', 'new')) {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/grant',
          "reset=1&action=add&context=grant&cid={$this->_contactID}"
        ));
    }
  }
}

