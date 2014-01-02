<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 * This class is used to retrieve and display a range of
 * contacts that match the given criteria (specifically for
 * results of advanced search options.
 *
 */
class CRM_Grant_Selector_Search extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * This defines two actions- View and Edit.
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * we use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   * @static
   */
  static $_columnHeaders;

  /**
   * Properties of contact we're interested in displaying
   * @var array
   * @static
   */
  static $_properties = array(
    'contact_id',
    'contact_type',
    'sort_name',
    'grant_id',
    'grant_status_id',
    'grant_status',
    'grant_type_id',
    'grant_type',
    'grant_amount_total',
    'grant_amount_requested',
    'grant_amount_granted',
    'grant_application_received_date', 
    'grant_payment_created',
    'program_name',
    'program_id',
    'grant_report_received',
    'grant_money_transfer_date',
  );

  /**
   * are we restricting ourselves to a single contact
   *
   * @access protected
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * are we restricting ourselves to a single contact
   *
   * @access protected
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * what context are we being invoked from
   *
   * @access protected
   * @var string
   */
  protected $_context = NULL;

  /**
   * queryParams is the array returned by exportValues called on
   * the HTML_QuickForm_Controller for that page.
   *
   * @var array
   * @access protected
   */
  public $_queryParams;

  /**
   * represent the type of selector
   *
   * @var int
   * @access protected
   */
  protected $_action;

  /**
   * The additional clause that we restrict the search with
   *
   * @var string
   */
  protected $_grantClause = NULL;

  /**
   * The query object
   *
   * @var string
   */
  protected $_query;

  /**
   * Class constructor
   *
   * @param array   $queryParams array of parameters for query
   * @param int     $action - action of search basic or advanced.
   * @param string  $grantClause if the caller wants to further restrict the search
   * @param boolean $single are we dealing only with one contact?
   * @param int     $limit  how many participations do we want returned
   *
   * @return CRM_Contact_Selector
   * @access public
   */
  function __construct(&$queryParams,
    $action      = CRM_Core_Action::NONE,
    $grantClause = NULL,
    $single      = FALSE,
    $limit       = NULL,
    $context     = 'search'
  ) {
    // submitted form values
    $this->_queryParams = &$queryParams;

    $this->_grantClause = $grantClause;
    $this->_single  = $single;
    $this->_limit   = $limit;
    $this->_context = $context;

    $this->_grantClause = $grantClause;

    // type of selector
    $this->_action = $action;

    $this->_query = new CRM_Contact_BAO_Query($this->_queryParams, NULL, NULL, FALSE, FALSE,
      CRM_Contact_BAO_Query::MODE_GRANT
    );
    $this->_query->_distinctComponentClause = " civicrm_grant.id";
    $this->_query->_groupByComponentClause = " GROUP BY civicrm_grant.id ";
  }
  //end of constructor

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @return array
   * @access public
   *
   */
  static function &links($key = NULL) {
    $cid = CRM_Utils_Request::retrieve('cid', 'Integer', $this);
    $next = CRM_Utils_Request::retrieve('next', 'Integer', $this);
    $prev = CRM_Utils_Request::retrieve('prev', 'Integer', $this);
    $extraParams = ($key) ? "&key={$key}" : NULL;

    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::VIEW => array(
          'name' => ts('View'),
          'url' => 'civicrm/contact/view/grant',
          'qs' => 'reset=1&id=%%id%%&cid=%%cid%%&action=view&context=%%cxt%%&selectedChild=grant' . $extraParams,
          'title' => ts('View Grant'),
        ),
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/contact/view/grant',
          'qs' => 'reset=1&action=update&id=%%id%%&cid=%%cid%%&context=%%cxt%%&next=%%next%%&prev=%%prev%%' . $extraParams.'&ncid=%%ncid%%&searchGrants=%%searchGrants%%',
          'title' => ts('Edit Grant'),
        ),
      );

      if ($cid) {
        $deleteExtra = ts('Are you sure you want to delete this grant?');

        $delLink = array(
          CRM_Core_Action::DELETE => array('name' => ts('Delete'),
            'url' => 'civicrm/contact/view/grant',
            'qs' => 'action=delete&reset=1&cid=%%cid%%&id=%%id%%&selectedChild=grant' . $extraParams,
            'extra' => 'onclick = "if (confirm(\'' . $deleteExtra . '\') ) this.href+=\'&amp;confirmed=1\'; else return false;"',
            'title' => ts('Delete Grant'),
          ),
        );
        self::$_links = self::$_links + $delLink;
      }
    }
    return self::$_links;
  }
  //end of function

  /**
   * getter for array of the parameters required for creating pager.
   *
   * @param
   * @access public
   */
  function getPagerParams($action, &$params) {
    $params['status'] = ts('Grant') . ' %%StatusMessage%%';
    $params['csvString'] = NULL;
    if ($this->_limit) {
      $params['rowCount'] = $this->_limit;
    }
    else {
      $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    }

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }
  //end of function

  /**
   * Returns total number of rows for the query.
   *
   * @param
   *
   * @return int Total number of rows
   * @access public
   */
  function getTotalCount($action) {
    return $this->_query->searchQuery(0, 0, NULL,
      TRUE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_grantClause
    );
  }

  /**
   * returns all the rows in the given offset and rowCount     *
   *
   * @param enum   $action   the action being performed
   * @param int    $offset   the row number to start from
   * @param int    $rowCount the number of rows to return
   * @param string $sort     the sql string that describes the sort order
   * @param enum   $output   what should the result set include (web/email/csv)
   *
   * @return int   the total number of rows for this action
   */
  function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
    foreach ($sort->_vars as $key => $value) {
      if ($value['name'] == "status_weight" && 1 == $key) {
        $sort = trim($sort->orderBy());
        $sort .= ', application_received_date DESC';
        break;
      }
    }
    $result = $this->_query->searchQuery($offset, $rowCount, $sort,
      FALSE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_grantClause
    );

    // process the result of the query
    $rows = array();

    //CRM-4418 check for view, edit, delete
    $permissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('edit grants')) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviGrant')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);
    //added by JMA
     $grant = $this->_query->searchQuery( 
       $offset, $rowCount, $sort,
       false, false, 
       false, false, 
       false, 
       $this->_grantClause );
     while ($grant->fetch()) {
       $grants[$grant->id] = $grant->id;
       $grantContacts[$grant->id] = $grant->contact_id;
     }
     while ($result->fetch()) {
      $row = array();
      if ($result->id == CRM_Utils_Array::value('id',$_GET)) {
        continue;
      }
      $prev = $next = null;
      $foundit = false;
      $contactGrants = $grants;
      $searchGrants = implode(',', $grants);
      foreach( $contactGrants as $gKey => $gVal) {
        if ($foundit) {
          $next = $gKey; 
          break;
        }
        if ($gKey == $result->id) {
          $next = $gKey; 
          if($gKey == end($contactGrants)) {
            reset($contactGrants);
            $next = key($contactGrants);
          }
          $foundit = true;
        } else {
          $prev = $gKey;
        }
      }
      if(empty($prev)) {
        $prev = end($contactGrants);
      }

      // the columns we are interested in
      foreach (self::$_properties as $property) {
        if (isset($result->$property)) {
          $row[$property] = $result->$property;
        }
      }

      if ($this->_context == 'search') {
        $row['checkbox'] = CRM_Core_Form::CB_PREFIX . $result->grant_id;
      }

      $row['action'] = CRM_Core_Action::formLink(self::links($this->_key),
        $mask,
        array(
          'id' => $result->grant_id,
          'cid' => $result->contact_id,
          'cxt' => $this->_context,
          'prev' => $prev,
          'next' => $next,
          'searchGrants' => $searchGrants,
          'ncid' => $grantContacts[$next],
        )
      );

      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ?
        $result->contact_sub_type : $result->contact_type, FALSE, $result->contact_id
      );

      $rows[] = $row;
    }

    return $rows;
  }

  /**
   *
   * @return array              $qill         which contains an array of strings
   * @access public
   */

  // the current internationalisation is bad, but should more or less work
  // for most of "European" languages
  public function getQILL() {
    return $this->_query->qill();
  }

  /**
   * returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action the action being performed
   * @param enum   $output what should the result set include (web/email/csv)
   *
   * @return array the column headers that need to be displayed
   * @access public
   */
  public function &getColumnHeaders($action = NULL, $output = NULL) {
    if (!isset(self::$_columnHeaders)) {
      $statusHeader = array(
        array('name' => ts('Status'),
          'sort' => 'status_weight',
          'direction' => CRM_Utils_Sort::ASCENDING,
        ),
      );
      self::$_columnHeaders = array(
        array(
          'name' => ts('Program Name'),
          'sort' => 'program_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Requested'),
          'sort' => 'grant_amount_total',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Granted'),
          'sort' => 'grant_amount_granted',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Application Received'),
          'sort' => 'grant_application_received_date',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ),
        array(
          'name' => ts('Payment Created'),
          'sort' => 'grant_payment_created',
          'direction' => CRM_Utils_Sort::DONTCARE,
 	      ),
        array('desc' => ts('Actions')),
      );

      if (!$this->_single) {
        $pre = array(
          array('desc' => ts('Contact Type')),
          array(
            'name' => ts('Name'),
            'sort' => 'sort_name',
            'direction' => CRM_Utils_Sort::DONTCARE,
          ),
        );
        self::$_columnHeaders = array_merge($pre, $statusHeader, self::$_columnHeaders);
      }
      else {
        self::$_columnHeaders = array_merge($statusHeader, self::$_columnHeaders);
      }
    }
    return self::$_columnHeaders;
  }

  function &getQuery() {
    return $this->_query;
  }

  /**
   * name of export file.
   *
   * @param string $output type of output
   *
   * @return string name of the file
   */
  function getExportFileName($output = 'csv') {
    return ts('CiviCRM Grant Search');
  }
}
//end of class

