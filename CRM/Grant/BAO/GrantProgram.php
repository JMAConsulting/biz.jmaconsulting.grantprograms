<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
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
    $query = "SELECT id FROM civicrm_option_value WHERE  option_group_id = {$optioGroupID} AND value = {$value} ";
    return CRM_Core_DAO::singleValueQuery($query);
  }
    
  static function getOptionValue($id) {
    $query = "SELECT value FROM civicrm_option_value WHERE id = {$id} ";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  static function getOptionName ($id) {
    $query = "SELECT label FROM civicrm_option_value WHERE id = {$id} ";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  static function grantPrograms($id = NULL) {
    $where = '';
    if (!empty($id)) {
      $where = "WHERE id = {$id}";
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
    return $grantPrograms;
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
     
     
  public function getAddress($id, $locationTypeID = NULL) {
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
      $location['address'] = addslashes($address);
    } 
    return $location;
  }
}
