<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.0                                                |
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

require_once 'CRM/Grant/DAO/EntityPayment.php';

/**
 * This class contains  entity payment related functions.
 */
class CRM_Grant_BAO_EntityPayment extends CRM_Grant_DAO_EntityPayment 
{
  /**
   * class constructor
   */
  function __construct( ) 
  {
    parent::__construct( );
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Grant_DAO_EntityPayment object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve( &$params, &$defaults ) 
  {
    $entityPayment = new CRM_Grant_DAO_EntityPayment( );
    $entityPayment->copyValues( $params );
    if ( $entityPayment->find( true ) ) {
      CRM_Core_DAO::storeValues( $entityPayment, $defaults );
      return $entityPayment;
    }
    return null;
  }
  /**
   * Function  to delete Entity Payment
   * 
   * @param  int  $entityPaymentID  ID of the entity payment to be deleted.
   * 
   * @access public
   * @static
   */
  static function del( $id )
  { 
    require_once 'CRM/Utils/Hook.php';
    CRM_Utils_Hook::pre( 'delete', 'EntityPayment', $id, CRM_Core_DAO::$_nullArray );

    require_once 'CRM/Grant/DAO/EntityPayment.php';
    $entityPayment    = new CRM_Grant_DAO_EntityPayment( );
    $entityPayment->id = $id; 

    $entityPayment->find();

    // delete the recently created Grant
    require_once 'CRM/Utils/Recent.php';
    $entityPaymentRecent = array(
                                 'id'   => $id,
                                 'type' => 'EntityPayment'
                                 );
    CRM_Utils_Recent::del( $entityPaymentRecent );

    if ( $entityPayment->fetch() ) {
      $results = $entityPayment->delete();
      CRM_Utils_Hook::post( 'delete', 'EntityPayment', $entityPayment->id, $entityPayment );
      return $results;
    }
    return false;
  }
}
