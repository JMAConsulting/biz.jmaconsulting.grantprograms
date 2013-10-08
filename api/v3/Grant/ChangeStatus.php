<?php

/**
 * Grant.ChangeStatus API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_grant_changestatus_spec(&$spec) {
}

/**
 * Grant.ChangeStatus API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_grant_changestatus($params) {

  $status = CRM_Core_OptionGroup::values('grant_status');
  $infoTooLate = key(CRM_Core_PseudoConstant::accountOptionValues('grant_info_too_late'));
  $reasonGrantIneligible = CRM_Core_OptionGroup::values('reason_grant_ineligible');

  $days = ' -' . $infoTooLate . ' days';
  $endDate = date('Y-m-d', strtotime(date('ymd') . $days));
  $awaitingInfo = array_search('Awaiting Information', $status);
  $ineligible = array_search('Ineligible', $status);
  $ineligibleReason = array_search('Information not received in time', $reasonGrantIneligible);

  $error = array();
  if (!$awaitingInfo) {
    $error[] = "'Awaiting Information'";
  }
  if (!$ineligible) {
    $error[] = "'Ineligible'";
  }
  if (!$ineligibleReason) {
    $error[] = "'Information not received in time'";
  }
  if (!empty($error)) {
    return civicrm_api3_create_error(implode(',', $error) . ' option(s) not found.');
  }
  
  $sql = "UPDATE civicrm_grant cg
LEFT JOIN civicrm_value_nei_course_conference_details cd ON cd.entity_id = cg.id
SET cg.status_id = {$ineligible},
grant_rejected_reason_id = {$ineligibleReason}
WHERE cg.status_id = {$awaitingInfo} AND end_date <= '{$endDate}'";
  CRM_Core_DAO::executeQuery($sql);
}

