<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
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
   */
  static $_links = NULL;

  /**
   * We use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   */
  static $_columnHeaders;

  /**
   * Properties of contact we're interested in displaying
   * @var array
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
<<<<<<< f38d014fc2c8a69496dc2881bc60ad4759f04353
=======
    'grant_report_received',
    'grant_money_transfer_date',
>>>>>>> Initial changes to make it 5.* compatible
    'grant_payment_created',
    'program_name',
    'program_id',
  );

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * Are we restricting ourselves to a single contact.
   *
   * @var boolean
   */
  protected $_limit = NULL;

  /**
   * What context are we being invoked from.
   *
   * @var string
   */
  protected $_context = NULL;

  /**
   * QueryParams is the array returned by exportValues called on.
   * the HTML_QuickForm_Controller for that page.
   *
   * @var array
   */
  public $_queryParams;

  /**
   * Represent the type of selector.
   *
   * @var int
   */
  protected $_action;

  /**
   * The additional clause that we restrict the search with.
   *
   * @var string
   */
  protected $_grantClause = NULL;

  /**
   * The query object.
   *
   * @var string
   */
  protected $_query;

  /**
   * Class constructor.
   *
   * @param array $queryParams
   *   Array of parameters for query.
   * @param \const|int $action - action of search basic or advanced.
   * @param string $grantClause
   *   If the caller wants to further restrict the search.
   * @param bool $single
   *   Are we dealing only with one contact?.
   * @param int $limit
   *   How many participations do we want returned.
   *
   * @param string $context
   *
   * @return \CRM_Grant_Selector_Search
   */
  public function __construct(
    &$queryParams,
    $action = CRM_Core_Action::NONE,
    $grantClause = NULL,
    $single = FALSE,
    $limit = NULL,
    $context = 'search'
  ) {
    // submitted form values
    $this->_queryParams = &$queryParams;
    $this->_grantClause = $grantClause;

    $this->_single = $single;
    $this->_limit = $limit;
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

  /**
   * This method returns the links that are given for each search row.
   * currently the links added for each row are
   *
   * - View
   * - Edit
   *
   * @param string|null $key
   *
   * @return array
   */
  public static function &links($key = NULL) {
    $cid = CRM_Utils_Request::retrieve('cid', 'Integer');
    $next = CRM_Utils_Request::retrieve('next', 'Integer');
    $prev = CRM_Utils_Request::retrieve('prev', 'Integer');
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
        $delLink = array(
          CRM_Core_Action::DELETE => array(
            'name' => ts('Delete'),
            'url' => 'civicrm/contact/view/grant',
            'qs' => 'action=delete&reset=1&cid=%%cid%%&id=%%id%%&selectedChild=grant' . $extraParams,
            'title' => ts('Delete Grant'),
          ),
        );
        self::$_links = self::$_links + $delLink;
      }
    }
    return self::$_links;
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param $action
   * @param array $params
   */
  public function getPagerParams($action, &$params) {
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

  /**
   * Returns total number of rows for the query.
   *
   * @param int $action
   *
   * @return int
   *   Total number of rows
   */
  public function getTotalCount($action) {
    return $this->_query->searchQuery(0, 0, NULL,
      TRUE, FALSE,
      FALSE, FALSE,
      FALSE,
      $this->_grantClause
    );
  }

  /**
   * Returns all the rows in the given offset and rowCount     *
   *
   * @param string $action
   *   The action being performed.
   * @param int $offset
   *   The row number to start from.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string $sort
   *   The sql string that describes the sort order.
   * @param string $output
   *   What should the result set include (web/email/csv).
   *
   * @return int
   *   the total number of rows for this action
   */
  public function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
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
       CRM_Core_Error::Debug_var('as', $grant);
       $grants[$grant->grant_id] = $grant->grant_id;
       $grantContacts[$grant->grant_id] = $grant->contact_id;
     }
     while ($result->fetch()) {
     $row = array();
      if ($result->grant_id == CRM_Utils_Array::value('id', $_GET)) {
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
<<<<<<< f38d014fc2c8a69496dc2881bc60ad4759f04353
        if ($gKey == $result->id) {
=======
        if ($gKey == $result->grant_id) {
>>>>>>> Initial changes to make it 5.* compatible
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
      if (empty($prev)) {
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
        ),
        ts('more'),
        FALSE,
        'grant.selector.row',
        'Grant',
        $result->grant_id
      );

      $row['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage($result->contact_sub_type ? $result->contact_sub_type : $result->contact_type, FALSE, $result->contact_id
      );

      $rows[] = $row;
    }

    return $rows;
  }

  /**
   * @inheritDoc
   */
  public function getQILL() {
    return $this->_query->qill();
  }

  /**
   * Returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action
   *   The action being performed.
   * @param string $output
   *   What should the result set include (web/email/csv).
   *
   * @return array
   *   the column headers that need to be displayed
   */
  public function &getColumnHeaders($action = NULL, $output = NULL) {
    $statusHeader = array();
    if (!isset(self::$_columnHeaders)) {
<<<<<<< f38d014fc2c8a69496dc2881bc60ad4759f04353
      if (CRM_Core_DAO::singleValueQuery("SELECT is_active FROM civicrm_extension WHERE full_name = 'biz.jmaconsulting.grantapplications'") != 1) {
        $statusHeader[] = array('name' => ts('Status'),
          'sort' => 'status_weight',
          'direction' => CRM_Utils_Sort::ASCENDING,
        );
      }
=======
      $statusHeader = array(
         array('name' => ts('Status'),
          'sort' => 'status_weight',
          'direction' => CRM_Utils_Sort::ASCENDING,
         ),
      );
>>>>>>> Initial changes to make it 5.* compatible
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
          'direction' => CRM_Utils_Sort::DONTCARE,
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

  /**
   * @return string
   */
  public function &getQuery() {
    return $this->_query;
  }

  /**
   * Name of export file.
   *
   * @param string $output
   *   Type of output.
   *
   * @return string
   *   name of the file
   */
  public function getExportFileName($output = 'csv') {
    return ts('CiviCRM Grant Search');
  }
<<<<<<< f38d014fc2c8a69496dc2881bc60ad4759f04353
}
//end of class
=======

}
>>>>>>> Initial changes to make it 5.* compatible
