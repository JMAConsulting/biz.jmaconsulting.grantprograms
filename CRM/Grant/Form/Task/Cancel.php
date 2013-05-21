<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
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

require_once 'CRM/Grant/Form/PaymentTask.php';
require_once 'CRM/Core/OptionGroup.php';

/**
 * This class provides the functionality to delete a group of
 * participations. This class provides functionality for the actual
 * deletion.
 */
class CRM_Grant_Form_Task_Cancel extends CRM_Grant_Form_PaymentTask 
{
    /**
     * Are we operating in "single mode", i.e. deleting one
     * specific participation?
     *
     * @var boolean
     */
    protected $_single = false;

    /**
     * build all the data structures needed to build the form
     *
     * @return void
     * @access public
     */
    function preProcess( ) 
    {
        parent::preProcess( );

        //check permission for delete.
        if ( !CRM_Core_Permission::checkActionPermission( 'CiviGrant', CRM_Core_Action::DELETE ) ) {
            CRM_Core_Error::fatal( ts( 'You do not have permission to access this page' ) );  
        }
    }

    /**
     * Build the form
     *
     * @access public
     * @return void
     */
    function buildQuickForm( ) 
    {
        $this->addDefaultButtons( ts( 'Cancel Grants' ), 'done' );
    }

    /**
     * process the form after the input has been submitted and validated
     *
     * @access public
     * @return None
     */
    public function postProcess( ) 
    {
      $deletedGrantPayments = 0;
      require_once 'CRM/Grant/DAO/EntityPayment.php';
      require_once 'CRM/Grant/BAO/GrantPayment.php';
      require_once 'CRM/Grant/BAO/EntityPayment.php';
      foreach ( $this->_grantPaymentIds as $paymentId ) {
        $entityDAO =& new CRM_Grant_DAO_EntityPayment();
        $entityDAO->payment_id = $paymentId;
        $entityDAO->find();
        
        while( $entityDAO->fetch() ) {
          CRM_Grant_BAO_EntityPayment::del( $entityDAO->id );
          $grantDAO =& new CRM_Grant_DAO_Grant();
          $grantDAO->id        = $entityDAO->entity_id;
          $grantDAO->status_id = CRM_Core_OptionGroup::getValue( 'grant_status', 'Approved for Payment', 'name' );
          $grantDAO->save();
        }
        if ( CRM_Grant_BAO_GrantPayment::del( $paymentId ) ) {
          $deletedGrantPayments++;
        }
      }

      $status = array(
                      ts( 'Cancel Grant Payments(s): %1',        array( 1 => $deletedGrantPayments ) ),
                      ts( 'Total Selected Grant Payments(s): %1', array( 1 => count($this->_grantPaymentIds ) ) ),
                      );
      CRM_Core_Session::setStatus( $status );
    }
}


