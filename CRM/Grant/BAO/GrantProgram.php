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
 * This class contains  grant program related functions.
 */
class CRM_Grant_BAO_GrantProgram extends CRM_Grant_DAO_GrantProgram {
  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * grant program status
   *
   * @var array
   * @static
   */
  private static $grantProgramStatus; 


  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Contribute_DAO_GrantProgram object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $program = new CRM_Grant_DAO_GrantProgram();
    $program->copyValues($params);
    if ($program->find(TRUE)) {
      CRM_Core_DAO::storeValues($program, $defaults);
      return $program;
    }
    return NULL;
  }

  /**
   * Function  to delete Grant Program
   * 
   * @param  int  $grantProgramID     ID of the par service fee to be deleted.
   * 
   * @access public
   * @static
   */
  static function del($grantProgramID) {
    if (!$grantProgramID) {
      CRM_Core_Error::fatal(ts('Invalid value passed to delete function'));
    }
    
    $dao = new CRM_Grant_DAO_GrantProgram();
    $dao->id = $grantProgramID;
    if (!$dao->find(TRUE)) {
      return NULL;
    }
    $dao->delete();
  }

  static function getOptionValueID($optioGroupID, $value) {
    $query = "SELECT id FROM civicrm_option_value WHERE  option_group_id = {$optioGroupID} AND value = {$value}";
    return CRM_Core_DAO::singleValueQuery($query);
  }
    
  static function getOptionValue($id) {
    $query = "SELECT value FROM civicrm_option_value WHERE id = {$id}";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  static function getOptionName ($id) {
    $query = "SELECT label FROM civicrm_option_value WHERE id = {$id}";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  static function grantPrograms($id = NULL) {
    $where = 'WHERE is_active = 1';
    if (!empty($id)) {
      $where .= " AND id = {$id}";
    }
    $query = "SELECT id, label FROM civicrm_grant_program {$where}";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      if (!empty($id)) {
        $grantPrograms = $dao->label;
      } 
      else {
        $grantPrograms[$dao->id] = $dao->label;
      }
    }
    return empty($grantPrograms) ? array() : $grantPrograms;
  }  

  static function contributionTypes() {
    $typeDao = new CRM_Financial_DAO_FinancialType();
    $typeDao->find();
    while ($typeDao->fetch()) {
      $contributionTypes[$typeDao->id] = $typeDao->name;
    }
    return $contributionTypes;
  }
    
  static function create(&$params, &$ids) {
    if (empty($params)) {
      return;
    }
    $moneyFields = array( 
      'total_amount',
      'remainder_amount' 
    );
    foreach ($moneyFields as $field) {
      if (isset($params[$field])) {
        $params[$field] = CRM_Utils_Rule::cleanMoney($params[$field]);
      }
    }
    // convert dates to mysql format
    $dates = array('allocation_date');
        
    foreach ($dates as $date) {
      if (isset( $params[$date])) {
        $params[$date] = CRM_Utils_Date::processDate($params[$date], NULL, TRUE);
      }
    }
    $grantProgram = new CRM_Grant_DAO_GrantProgram();
    $grantProgram->id = CRM_Utils_Array::value('grant_program', $ids);
        
    $grantProgram->copyValues($params);
        
    return $result = $grantProgram->save();
  }

  public function getDisplayName($id) {
    $sql = "SELECT display_name FROM civicrm_contact WHERE civicrm_contact.id = $id ";
    return CRM_Core_DAO::singleValueQuery($sql);
  }
     
     
  public function getAddress($id, $locationTypeID = NULL, $twoLines = false) {
    $sql = "
   SELECT civicrm_contact.id as contact_id,
          civicrm_address.street_address as street_address,
          civicrm_address.supplemental_address_1 as supplemental_address_1,
          civicrm_address.supplemental_address_2 as supplemental_address_2,
          civicrm_address.city as city,
          civicrm_address.postal_code as postal_code,
          civicrm_address.postal_code_suffix as postal_code_suffix,
          civicrm_state_province.abbreviation as state,
          civicrm_country.name as country,
          civicrm_location_type.name as location_type
     FROM civicrm_contact
LEFT JOIN civicrm_address ON civicrm_address.contact_id = civicrm_contact.id
LEFT JOIN civicrm_state_province ON civicrm_address.state_province_id = civicrm_state_province.id
LEFT JOIN civicrm_country ON civicrm_address.country_id = civicrm_country.id
LEFT JOIN civicrm_location_type ON civicrm_location_type.id = civicrm_address.location_type_id
WHERE civicrm_contact.id = $id ";

    $params = array();
    if (!$locationTypeID) {
      $sql .= " AND civicrm_address.is_primary = 1";
    } 
    else {
      $sql .= " AND civicrm_address.location_type_id = %1";
      $params[1] = array($locationTypeID, 'Integer');
    }
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $location = array();
    $config = CRM_Core_Config::singleton();
    while ($dao->fetch()) {
      $address = '';
      if ($twoLines) {
      	CRM_Utils_String::append(
      	$address, ' ',
      	array(
	      	$dao->street_address,
	      	$dao->supplemental_address_1,
	      	$dao->supplemental_address_2,
	      	'<br />',
	      	$dao->city,
	      	$dao->state,
	      	$dao->postal_code
	      	)
      	);
      } else {
	      CRM_Utils_String::append( 
	        $address, ', ',
	        array( 
	          $dao->street_address,
	          $dao->supplemental_address_1,
	          $dao->supplemental_address_2,
	          $dao->city,
	          $dao->state,
	          $dao->postal_code,
	          $dao->country
	        ) 
	      );
      }
      $location['address'] = addslashes($address);
    } 
    return $location;
  }  
  /**
   * Get all the n grant program statuses
   *
   * @access public
   * @return array - array reference of all grant program statuses if any
   * @static
   */
  public static function &grantProgramStatus($id = NULL) {
    if (!self::$grantProgramStatus) {
      self::$grantProgramStatus = array();
      self::$grantProgramStatus = CRM_Core_OptionGroup::values('grant_program_status');
    }
    if($id) {
      return self::$grantProgramStatus[$id];
    }    
    return self::$grantProgramStatus;
  }
  
  
  static function getGrantPrograms($id = NULL) {
    $grantPrograms = array();
    $where = ' WHERE is_active = 1';
    if (!empty($id)) {
      $where .= " AND id = {$id}";
    }
    $query = "SELECT id, name FROM civicrm_grant_program " . $where;
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $grantPrograms[$dao->id] = $dao->name;
    }
    return $grantPrograms;
  }
    
  static function getGrants($params) {
    $grants = array();
    if (!empty($params)) {
      $where = "WHERE "; 
      foreach ($params as $key => $value) {
        if ($key == 'status_id') {
          $where .= "{$key} IN ( {$value} ) AND ";
        } 
        else {
          if (strstr($value, 'NULL')) {
            $where .= "{$key} IS {$value} AND ";
          } 
          else {
            $where .= "{$key} = '{$value}' AND ";
          }
        }
      }
      $where = rtrim($where ," AND ");
      $query = "SELECT * FROM civicrm_grant {$where} ORDER BY application_received_date ASC";
      $dao = CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $grants[$dao->id]['assessment'] = $dao->assessment;
        $grants[$dao->id]['amount_total'] = $dao->amount_total;
        $grants[$dao->id]['amount_requested'] = $dao->amount_requested;
        $grants[$dao->id]['amount_granted'] = $dao->amount_granted;
        $grants[$dao->id]['status_id'] = $dao->status_id;
        $grants[$dao->id]['contact_id'] = $dao->contact_id;
        $grants[$dao->id]['grant_id'] = $dao->id;     
      }
    }
    return $grants;
  }
    
  static function sendMail($contactID, &$values, $grantStatus, $grantId = FALSE, $status = '') {
    $value = array();
    if (CRM_Utils_Array::value('is_auto_email', $values)) {
      list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      if (isset($email)) {
        $valueName = strtolower($grantStatus);
        if ($grantStatus == 'Awaiting Information') {
          $explode = explode(' ', $grantStatus);
          $valueName = strtolower($explode[0]) . '_info';
        } 
        elseif (strstr($grantStatus, 'Approved')) {
          $valueName = strtolower('Approved');
        }
        $sendTemplateParams = array(
          'groupName' => 'msg_tpl_workflow_grant',
          'valueName' => 'grant_'.$valueName,
          'contactId' => $contactID,
          'tplParams' => array(
            'email' => $email,
           ),
          'PDFFilename' => '',
        );
        
        $defaultAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
        foreach ($defaultAddress as $id => $value) {
          $sendTemplateParams['from'] = $value;
        }

        $sendTemplateParams['toName'] = $displayName;
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['autoSubmitted'] = TRUE;
        //CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        if ($grantId && $status) {
          self::createStatusChangeActivity($grantId, $grantStatus, $status, $contactID);
        }
      }
    }
  }

  /**
   * Function to get sum of amount granted for a Contact
   *
   * @param int $params
   *
   * @return int sum of amount granted
   * @access public
   * @static
   */
  static function getUserAllocatedAmount($params, $id = NULL) {
    $where = NULL;
    if (!empty($params)) {
      foreach ($params as $key => $value) {
        $where .= " AND {$key} = {$value}";
      }
      if (!empty($id)) {
        $where .= " AND id != {$id}";
      }
      $query = "SELECT SUM(amount_granted) as amount_granted FROM civicrm_grant WHERE " .ltrim($where, ' AND');
      $amountGranted = CRM_Core_DAO::singleValueQuery($query);
    }
    return empty($amountGranted) ? 0 : $amountGranted;
  }
  
  /**
   * Function to get current grant granted amount
   *
   * @return int amount granted
   * @access public
   * @static
   */
  static function getCurrentGrantAmount($id = NULL) {
    if ($id != NULL) {
      $query = "SELECT amount_granted FROM civicrm_grant WHERE id = " . $id;
      $amountGranted = CRM_Core_DAO::singleValueQuery($query);
    }
    return empty($amountGranted) ? 0 : $amountGranted;
  }

  static function getPriorities($id, $contactId) {
    $priority = 10;
    $prevGrantProgram = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantProgram', $id, 'grant_program_id');
    $amount = 0;
    $params = array(
      'grant_program_id' => $prevGrantProgram,
      'contact_id' => $contactId,
    );
    $grants = CRM_Grant_BAO_GrantProgram::getGrants($params);
    if (!empty($grants)) {
      foreach ($grants as $values) {
        $amount += $values['amount_granted'];
      }
    }
    $grantThresholds = CRM_Core_OptionGroup::values('grant_thresholds', TRUE);
    if (!empty($amount)) {
      if ($amount == $grantThresholds['Maximum Grant']) {
        $priority = -10;
      } 
      elseif ($amount == 0) {
        $priority = 10;
      }
      elseif ((0 <= $amount) && ($amount <= $grantThresholds['Maximum Grant'])) {
        $priority = 0;
      }
    } 
    return $priority;
  }

  static function createStatusChangeActivity($grantId, $newStatus, $oldStatus, $contactID) {
    if (($oldStatus == 'Draft' && $newStatus == 'Submitted') || $newStatus == $oldStatus || !$oldStatus) {
      return;
    }
    $activityStatus = CRM_Core_PseudoConstant::activityStatus('name');
    $activityType = CRM_Core_PseudoConstant::activityType();
    $session = CRM_Core_Session::singleton();
    $params = array( 
      'source_contact_id'=> $session->get('userID'),
      'source_record_id' => $grantId,
      'activity_type_id'=> array_search('Grant Status Change', $activityType),
      'assignee_contact_id'=> array($contactID),
      'subject'=> "Grant status changed from {$oldStatus} to {$newStatus}",
      'activity_date_time'=> date('Ymdhis'),
      'status_id'=> array_search('Completed', $activityStatus),
      'priority_id'=> 2,
      'details'=> CRM_Core_Smarty::singleton()->get_template_vars('messageBody'),
    );
    CRM_Activity_BAO_Activity::create($params);
  }
}
