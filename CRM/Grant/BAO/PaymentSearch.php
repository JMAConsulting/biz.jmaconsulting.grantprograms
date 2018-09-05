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

class CRM_Grant_BAO_PaymentSearch {

  const
    MODE_GRANT_PAYMENT = 1;

  static function &getFields() {
    $fields = array();
    $fields = CRM_Grant_BAO_GrantPayment::exportableFields();
    return $fields;
  }

  function __construct($params = NULL,
    $returnProperties = NULL,
    $fields = NULL,
    $includeContactIds = FALSE,
    $strict = FALSE,
    $mode = 1,
    $skipPermission = FALSE,
    $searchDescendentGroups = TRUE,
    $smartGroupCache = TRUE,
    $displayRelationshipType = NULL,
    $operator = 'AND'
  ) {
    $this->_params =& $params;

    if ($this->_params == NULL) {
      $this->_params = array();
    }
    if (empty($returnProperties)) {
      $this->_returnProperties = self::defaultReturnProperties($mode);
    }
    else {
      $this->_returnProperties =& $returnProperties;
    }
    $this->_includeContactIds = $includeContactIds;
    $this->_strict = $strict;
    $this->_mode = $mode;
    $this->_skipPermission = $skipPermission;
    $this->_smartGroupCache = $smartGroupCache;
    $this->_displayRelationshipType = $displayRelationshipType;
    $this->setOperator($operator);

    if ($fields) {
      $this->_fields =& $fields;
      $this->_search = FALSE;
      $this->_skipPermission = TRUE;
    } else {
      $this->_fields = CRM_Grant_BAO_GrantPayment::exportableFields('All', FALSE, TRUE, TRUE);
    }
    // basically do all the work once, and then reuse it
    $this->initialize();
  }

  function initialize() {
    $this->_select = array();
    $this->_element = array();
    $this->_tables = array();
    $this->_whereTables = array();
    $this->_where = array();
    $this->_qill = array();
    $this->_options = array();
    $this->_cfIDs = array();
    $this->_paramLookup = array();
    $this->_having = array();

    $this->_customQuery = NULL;
    $this->select();
    $this->element();
    if (!empty($this->_params)) {
      $this->buildParamsLookup();
    }
    $this->_whereClause = $this->whereClause();
    $this->_tables = array('civicrm_payment' => 1);
    $this->_whereTables = array('civicrm_payment' => 1);

    $this->_fromClause = "FROM civicrm_payment";
    $this->_simpleFromClause = "FROM civicrm_payment";
  }

  function buildParamsLookup() {
    foreach ($this->_params as $value) {
      if (!CRM_Utils_Array::value(0, $value)) {
        continue;
      }

      $cfID = CRM_Core_BAO_CustomField::getKeyID($value[0]);
      if ($cfID) {
        if (!array_key_exists($cfID, $this->_cfIDs)) {
          $this->_cfIDs[$cfID] = array();
        }
        $this->_cfIDs[$cfID][] = $value;
      }

      if (!array_key_exists($value[0], $this->_paramLookup)) {
        $this->_paramLookup[$value[0]] = array();
      }
      $this->_paramLookup[$value[0]][] = $value;
    }
  }

  function whereClause() {
    $this->_where[0] = array();
    $this->_qill[0] = array();
    $config = CRM_Core_Config::singleton();
    if (!empty($this->_params)) {
      foreach (array_keys($this->_params) as $id) {
        if (!CRM_Utils_Array::value(0, $this->_params[$id])) {
          continue;
        }
        $this->whereClauseSingle($this->_params[$id]);
      }
    }

    if ($this->_customQuery) {
      // Added following if condition to avoid the wrong value diplay for 'myaccount' / any UF info.
      // Hope it wont affect the other part of civicrm.. if it does please remove it.
      if (!empty($this->_customQuery->_where)) {
        $this->_where = CRM_Utils_Array::crmArrayMerge($this->_where, $this->_customQuery->_where);
      }
      $this->_qill  = CRM_Utils_Array::crmArrayMerge($this->_qill , $this->_customQuery->_qill);
    }

    $clauses    = array();
    $andClauses = array();

    $validClauses = 0;
    if (!empty($this->_where)) {
      foreach ($this->_where as $grouping => $values) {
        if ($grouping > 0 && ! empty( $values)) {
          $clauses[$grouping] = ' ( ' . implode(" {$this->_operator} ", $values) . ' ) ';
          $validClauses++;
        }
      }

      if (!empty($this->_where[0])) {
        $andClauses[] = ' ( ' . implode(" AND ", $this->_where[0]) . ' ) ';
      }
      if (!empty($clauses)) {
        $andClauses[] = ' ( ' . implode(' OR ', $clauses) . ' ) ';
      }

      if ($validClauses > 1) {
        $this->_useDistinct = TRUE;
      }
    }

    return implode(' AND ', $andClauses);
  }

  function whereClauseSingle(&$values) {
    list($name, $op, $value, $grouping, $wildcard) = $values;
    switch ($values[0]) {
    case 'payment_status_id':
      $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("p.$name", $op, $value, 'Int');
      $this->_qill[$grouping][] = ts('Payment Status is ') . CRM_Core_PseudoConstant::getLabel('CRM_Grant_DAO_GrantPayment', 'payment_status_id', $value);
      $this->_tables['civicrm_payment'] = $this->_whereTables['civicrm_payment'] = 1;
      return;

    case 'payable_to_name':
      $op = 'LIKE';

      if (empty(trim($value))) {
        return;
      }
      $this->_qill[$grouping][] = ts(sprintf('Payee Name contains "%s"', $value));

      $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';
      $value = '%' . $strtolower(CRM_Core_DAO::escapeString($value)) . '%';
      $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("cc.display_name", $op, $value);
      return;

    case 'payment_batch_number':
      $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("b.id", $op, $value, 'Int');
      $this->_qill[$grouping][] = ts('Batch is - ') . CRM_Utils_Array::value($value, CRM_Contribute_PseudoConstant::batch());
      return;

    case 'payment_number':
      $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("ft.check_number", $op, $value);
      $this->_qill[$grouping][] = ts('Payment Number is ') .  $value;
      return;

    case 'payment_created_date_relative':
      $from = $to = NULL;
      if ($value) {
        list($from, $to) = CRM_Utils_Date::getFromTo($value, $from, $to);
        $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("p.payment_created_date", '>=', $from);
        $this->_where[$grouping][] = CRM_Contact_BAO_Query::buildClause("p.payment_created_date", '<=', $to);
        $this->_qill[$grouping][] = ts('Payment Created date from "%1" to "%2" ', [
          1 => CRM_Utils_Date::customFormat($from),
          2 => CRM_Utils_Date::customFormat($to),
        ]);
      }
      return;

    case 'amount_low':
    case 'amount_high':
      // process min/max amount
      $this->numberRangeBuilder($values,
        'ft', 'amount',
        'total_amount', 'Amount',
        NULL
      );
      return;

    case 'payment_created_date_low':
    case 'payment_created_date_high':
    $this->dateQueryBuilder($values,
      'p', 'payment_created_date', 'payment_created_date', ts('Payment Created Date')
    );
      return;

    }
  }

  function &getWhereValues($name, $grouping)  {
    $result = NULL;
    foreach ($this->_params as $id => $values) {
      if ($values[0] == $name && $values[3] == $grouping) {
        return $values;
      }
    }
    return $result;
  }

  function grantID(&$values) {
    $this->_where[$grouping][] = " ( civicrm_entity_payment.entity_id = $values )";
    $this->_tables['civicrm_entity_payment'] = $this->_whereTables['civicrm_entity_payment'] = 1;
  }

  /**
   * build select for CiviGrant
   *
   * @return void
   * @access public
   */
  function select() {
    $this->_select['id'] = 'p.id as id';
    $this->_select['payable_to_name'] = 'cc.display_name as payable_to_name';
    $this->_select['payment_batch_number'] = 'b.id as payment_batch_number';
    $this->_select['payment_status_id'] = 'p.payment_status_id';
    $this->_select['payment_created_date'] = 'p.payment_created_date';
    $this->_select['amount'] = 'ft.total_amount as amount';
    $this->_select['payment_number'] = 'ft.check_number as payment_number';
    return $this->_select;
  }

  function element() {
    $this->_element['id'] = 1;
    $this->_element['payable_to_name'] = 1;
    $this->_element['payment_batch_number'] = 1;
    $this->_element['payment_number'] = 1;
    $this->_element['payment_status_id'] = 1;
    $this->_element['payment_created_date'] = 1;
    $this->_element['amount'] = 1;
    return $this->_select;
    return $this->_element;
  }

  /**
   * Given a list of conditions in params generate the required
   * where clause
   *
   * @return void
   * @access public
   */
  static function where(&$query) {
    foreach (array_keys($query->_params) as $id) {
      if (substr($query->_params[$id][0], 0, 6) == 'grant_') {
        self::whereClauseSingle($query->_params[$id], $query);
      }
    }
  }

  /**
   * getter for the qill object
   *
   * @return string
   * @access public
   */
  function qill() {
    return (isset($this->_qill)) ? $this->_qill : "";
  }

  static function defaultReturnProperties($mode,
    $includeCustomFields = TRUE
  ) {
    $properties = NULL;
    if ($mode & CRM_Grant_BAO_PaymentSearch::MODE_GRANT_PAYMENT) {
      $properties = array(
        'id' => 1,
        'payable_to_name' => 1,
        'payment_batch_number' => 1,
        'payment_number' => 1,
        'payment_status_id' => 1,
        'payment_created_date' => 1,
        'amount' => 1,
      );
    }
    return $properties;
  }

  /**
   * add all the elements shared between grant search and advanaced search
   *
   * @access public
   * @return void
   * @static
   */

  static function addShowHide(&$showHide) {
    $showHide->addHide('grantForm');
    $showHide->addShow('grantForm_show');
  }

  static function searchAction(&$row, $id) {
  }

  static function tableNames(&$tables) {
  }


  function query($count = FALSE, $sortByChar = FALSE, $groupContacts = FALSE) {
    $select = 'SELECT ';
    if (!$count) {
      if (! empty($this->_select)) {
        $select .= implode(', ', $this->_select);
      }
    }
    else {
      $select .= "count( DISTINCT ".$this->_distinctComponentClause." ) ";
    }
    $from = " FROM civicrm_payment p
     LEFT JOIN civicrm_financial_trxn ft ON ft.id = p.financial_trxn_id
     LEFT JOIN civicrm_entity_financial_trxn eft ON eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_grant'
     LEFT JOIN civicrm_grant g ON g.id = eft.entity_id
     LEFT JOIN civicrm_entity_financial_trxn eft1 ON eft1.financial_trxn_id = ft.id AND eft1.entity_table = 'civicrm_financial_item'
     LEFT JOIN civicrm_financial_item fi ON fi.id = eft1.entity_id
     LEFT JOIN civicrm_grant_program gp ON gp.id = g.grant_program_id
     LEFT JOIN civicrm_entity_batch eb ON eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn'
     LEFT JOIN civicrm_batch b ON eb.batch_id = b.id
     LEFT JOIN civicrm_contact cc ON cc.id = fi.contact_id
    ";

    $where = '';
    if (!empty($this->_whereClause)) {
      $where = "WHERE {$this->_whereClause}";
    }

    $having = '';
    if (!empty($this->_having)) {
      foreach ($this->_having as $havingsets) {
        foreach ($havingsets as $havingset) {
          $havingvalue[] = $havingset;
        }
      }
      $having = ' HAVING ' . implode(' AND ', $havingvalue);
    }
    return array($select, $from, $where, $having);
  }

  function searchQuery(
    $offset = 0,
    $rowCount = 0,
    $sort = NULL,
    $count = FALSE,
    $includeContactIds = FALSE,
    $sortByChar = FALSE,
    $groupContacts = FALSE,
    $returnQuery = FALSE,
    $additionalWhereClause = NULL,
    $sortOrder = NULL,
    $additionalFromClause = NULL,
    $skipOrderAndLimit = FALSE
  ) {

    list($select, $from, $where, $having) = $this->query($count, $sortByChar, $groupContacts);
    $order = $orderBy = $limit = '';
    if (!$count)  {

      $config = CRM_Core_Config::singleton();
      if ($config->includeOrderByClause ||
        isset($this->_distinctComponentClause)) {

        if ($sort) {
          if (is_string($sort)) {
            $orderBy = $sort;
          }
          else {
            $orderBy = trim($sort->orderBy());
          }
          if (!empty($orderBy)) {
            $order = " ORDER BY $orderBy";
            if ($sortOrder) {
              $order .= " $sortOrder";
            }
          }
        }
        elseif ($sortByChar) {
          $order = " ORDER BY UPPER(LEFT(cc.display_name, 1)) asc";
        }
        else {
          $order = " ORDER BY cc.display_name asc, ft.id";
        }
      }

      if ($rowCount > 0 && $offset >= 0) {
        $limit = " LIMIT $offset, $rowCount ";

        if (isset($this->_distinctComponentClause)) {
          $limitSelect = "SELECT {$this->_distinctComponentClause}";
        }
        else {
          $limitSelect = ($this->_useDistinct) ?
            'SELECT DISTINCT(p.id) as id' :
            'SELECT p.id as id';
        }
      }
      $groupBy = 'GROUP BY p.id';
      $query = "$select $from $where $having $groupBy $order $limit";
    }

    if ($count) {
      $query = "$select $from $where";
      return CRM_Core_DAO::singleValueQuery($query);
    }
    elseif (empty($query)) {
      $query = "$select $from $where $having $groupBy $order $limit";
    }
    //CRM_Core_Error::debug_var('q', $query);

    $dao = CRM_Core_DAO::executeQuery($query);
    if ($groupContacts) {
      $ids = array();
      while ($dao->fetch()) {
        $ids[] = $dao->id;
      }
      return implode(',', $ids);
    }
    return $dao;
  }

  static function convertFormValues(&$formValues, $wildcard = 0, $useEquals = FALSE) {
    $params = array();
    if (empty($formValues)) {
      return $params;
    }
    foreach ($formValues as $id => $values) {
      $values = self::fixWhereValues($id, $values, $wildcard, $useEquals);
      if (!$values) {
        continue;
      }
      $params[] = $values;
    }
    return $params;
  }

  static function &fixWhereValues($id, &$values, $wildcard = 0, $useEquals = FALSE) {
    // skip a few search variables
    static $skipWhere = NULL;
    static $arrayValues = NULL;
    static $likeNames = NULL;
    $result = NULL;

    if (CRM_Utils_System::isNull($values)) {
      return $result;
    }

    if (!$skipWhere) {
      $skipWhere = array(
        'task',
        'radio_ts',
        'uf_group_id',
        'component_mode',
        'qfKey',
        'operator',
        'display_relationship_type'
      );
    }

    if (in_array($id, $skipWhere) ||
      substr($id, 0, 4) == '_qf_' ||
      substr($id, 0, 7) == 'hidden_') {
      return $result;
    }

    if (!$likeNames) {
      $likeNames = array('sort_name', 'email', 'note', 'display_name');
    }

    if (!$useEquals &&
      in_array($id, $likeNames)) {
      $result = array($id, 'LIKE', $values, 0, 1);
    }
    elseif (is_string($values) && strpos($values, '%') !== FALSE) {
      $result = array($id, 'LIKE', $values, 0, 0);
    }
    elseif ($id == 'group') {
      if (is_array($values)) {
        foreach ($values as $groupIds => $val) {
          $matches = array();
          if (preg_match('/-(\d+)$/', $groupIds, $matches)) {
            if (strlen($matches[1]) > 0) {
              $values[$matches[1]] = 1;
              unset($values[$groupIds]);
            }
          }
        }
      }
      else {
        $groupIds = explode(',', $values);
        unset($values);
        foreach($groupIds as $groupId) {
          $values[$groupId] = 1;
        }
      }
      $result = array($id, 'IN', $values, 0, 0);
    }
    elseif ($id == 'contact_tags' || $id == 'tag') {
      if (!is_array($values)) {
        $tagIds = explode(',', $values);
        unset($values);
        foreach($tagIds as $tagId) {
          $values[$tagId] = 1;
        }
      }
      $result = array($id, 'IN', $values, 0, 0);
    }
    else {
      $result = array($id, '=', $values, 0, $wildcard);
    }
    return $result;
  }

  function setOperator($operator) {
    $validOperators = array('AND', 'OR');
    if (!in_array($operator, $validOperators)) {
      $operator = 'AND';
    }
    $this->_operator = $operator;
  }

  /**
   * Build query for a date field.
   *
   * @param array $values
   * @param string $tableName
   * @param string $fieldName
   * @param string $dbFieldName
   * @param string $fieldTitle
   * @param bool $appendTimeStamp
   * @param string $dateFormat
   */
  public function dateQueryBuilder(
    &$values, $tableName, $fieldName,
    $dbFieldName, $fieldTitle,
    $appendTimeStamp = TRUE,
    $dateFormat = 'YmdHis'
  ) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if ($name == "{$fieldName}_low" ||
      $name == "{$fieldName}_high"
    ) {

      $secondOP = $secondPhrase = $secondValue = $secondDate = $secondDateFormat = NULL;

      if ($name == $fieldName . '_low') {
        $firstOP = '>=';
        $firstPhrase = ts('greater than or equal to');
        $firstDate = CRM_Utils_Date::processDate($value, NULL, FALSE, $dateFormat);

        $secondValues = $this->getWhereValues("{$fieldName}_high", $grouping);
        if (!empty($secondValues) && $secondValues[2]) {
          $secondOP = '<=';
          $secondPhrase = ts('less than or equal to');
          $secondValue = $secondValues[2];

          if ($appendTimeStamp && strlen($secondValue) == 10) {
            $secondValue .= ' 23:59:59';
          }
          $secondDate = CRM_Utils_Date::processDate($secondValue, NULL, FALSE, $dateFormat);
        }
      }
      elseif ($name == $fieldName . '_high') {
        $firstOP = '<=';
        $firstPhrase = ts('less than or equal to');

        if ($appendTimeStamp && strlen($value) == 10) {
          $value .= ' 23:59:59';
        }
        $firstDate = CRM_Utils_Date::processDate($value, NULL, FALSE, $dateFormat);

        $secondValues = $this->getWhereValues("{$fieldName}_low", $grouping);
        if (!empty($secondValues) && $secondValues[2]) {
          $secondOP = '>=';
          $secondPhrase = ts('greater than or equal to');
          $secondValue = $secondValues[2];
          $secondDate = CRM_Utils_Date::processDate($secondValue, NULL, FALSE, $dateFormat);
        }
      }

      if (!$appendTimeStamp) {
        $firstDate = substr($firstDate, 0, 8);
      }
      $firstDateFormat = CRM_Utils_Date::customFormat($firstDate);

      if ($secondDate) {
        if (!$appendTimeStamp) {
          $secondDate = substr($secondDate, 0, 8);
        }
        $secondDateFormat = CRM_Utils_Date::customFormat($secondDate);
      }

      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;
      if ($secondDate) {
        $this->_where[$grouping][] = "
( {$tableName}.{$dbFieldName} $firstOP '$firstDate' ) AND
( {$tableName}.{$dbFieldName} $secondOP '$secondDate' )
";
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$firstDateFormat\" " . ts('AND') . " $secondPhrase \"$secondDateFormat\"";
      }
      else {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $firstOP '$firstDate'";
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$firstDateFormat\"";
      }
    }

    if ($name == $fieldName) {
      //In Get API, for operators other then '=' the $value is in array(op => value) format
      if (is_array($value) && !empty($value) && in_array(key($value), CRM_Core_DAO::acceptedSQLOperators(), TRUE)) {
        $op = key($value);
        $value = $value[$op];
      }

      $date = $format = NULL;
      if (strstr($op, 'IN')) {
        $format = array();
        foreach ($value as &$date) {
          $date = CRM_Utils_Date::processDate($date, NULL, FALSE, $dateFormat);
          if (!$appendTimeStamp) {
            $date = substr($date, 0, 8);
          }
          $format[] = CRM_Utils_Date::customFormat($date);
        }
        $date = "('" . implode("','", $value) . "')";
        $format = implode(', ', $format);
      }
      elseif ($value && (!strstr($op, 'NULL') && !strstr($op, 'EMPTY'))) {
        $date = CRM_Utils_Date::processDate($value, NULL, FALSE, $dateFormat);
        if (!$appendTimeStamp) {
          $date = substr($date, 0, 8);
        }
        $format = CRM_Utils_Date::customFormat($date);
        $date = "'$date'";
      }

      if ($date) {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $op $date";
      }
      else {
        $this->_where[$grouping][] = self::buildClause("{$tableName}.{$dbFieldName}", $op);
      }

      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;

      $op = CRM_Utils_Array::value($op, CRM_Core_SelectValues::getSearchBuilderOperators(), $op);
      $this->_qill[$grouping][] = "$fieldTitle $op $format";
    }
  }

  /**
   * @param $values
   * @param string $tableName
   * @param string $fieldName
   * @param string $dbFieldName
   * @param $fieldTitle
   * @param null $options
   */
  public function numberRangeBuilder(
    &$values,
    $tableName, $fieldName,
    $dbFieldName, $fieldTitle,
    $options = NULL
  ) {
    list($name, $op, $value, $grouping, $wildcard) = $values;

    if ($name == "{$fieldName}_low" ||
      $name == "{$fieldName}_high"
    ) {
      if (isset($this->_rangeCache[$fieldName])) {
        return;
      }
      $this->_rangeCache[$fieldName] = 1;

      $secondOP = $secondPhrase = $secondValue = NULL;

      if ($name == "{$fieldName}_low") {
        $firstOP = '>=';
        $firstPhrase = ts('greater than');

        $secondValues = $this->getWhereValues("{$fieldName}_high", $grouping);
        if (!empty($secondValues)) {
          $secondOP = '<=';
          $secondPhrase = ts('less than');
          $secondValue = $secondValues[2];
        }
      }
      else {
        $firstOP = '<=';
        $firstPhrase = ts('less than');

        $secondValues = $this->getWhereValues("{$fieldName}_low", $grouping);
        if (!empty($secondValues)) {
          $secondOP = '>=';
          $secondPhrase = ts('greater than');
          $secondValue = $secondValues[2];
        }
      }

      if ($secondOP) {
        $this->_where[$grouping][] = "
( {$tableName}.{$dbFieldName} $firstOP {$value} ) AND
( {$tableName}.{$dbFieldName} $secondOP {$secondValue} )
";
        $displayValue = $options ? $options[$value] : $value;
        $secondDisplayValue = $options ? $options[$secondValue] : $secondValue;

        $this->_qill[$grouping][]
          = "$fieldTitle - $firstPhrase \"$displayValue\" " . ts('AND') . " $secondPhrase \"$secondDisplayValue\"";
      }
      else {
        $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $firstOP {$value}";
        $displayValue = $options ? $options[$value] : $value;
        $this->_qill[$grouping][] = "$fieldTitle - $firstPhrase \"$displayValue\"";
      }
      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;

      return;
    }

    if ($name == $fieldName) {
      $op = '=';
      $phrase = '=';

      $this->_where[$grouping][] = "{$tableName}.{$dbFieldName} $op {$value}";

      $this->_tables[$tableName] = $this->_whereTables[$tableName] = 1;
      $displayValue = $options ? $options[$value] : $value;
      $this->_qill[$grouping][] = "$fieldTitle - $phrase \"$displayValue\"";
    }
  }

}
