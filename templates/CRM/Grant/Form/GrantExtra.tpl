{*
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
*}
<table class='removeAfter'><tbody>
  <tr class="crm-grant-form-block-grant_rejected_reason_id grant_rejected_reason_id">
    <td class="label">{$form.grant_rejected_reason_id.label}</td>
    <td>{$form.grant_rejected_reason_id.html}</td>
  </tr>   
  <tr class="crm-grant-form-block-grant_program_id">
    <td class="label">{$form.grant_program_id.label}</td>
    <td>{$form.grant_program_id.html}</td>
  </tr>  
  <tr class="crm-grant-form-block-assessment">
    <td class="label">{$form.assessment.label}</td>
    <td>{$form.assessment.html}</td>
  </tr>
</tbody></table>

{*if $pager->_totalItems*}
  <h3>{ts}Recent Grants{/ts}</h3>
  <div class="form-item" id = "RecentGrants">
    {* include file="CRM/Grant/Form/Selector.tpl" context="DashBoard" *}
  </div>
{*/if*}
<script type="text/javascript">
{literal}
cj(document).ready(function(){
  cj('.crm-grant-form-block-grant_rejected_reason_id').insertAfter('.crm-grant-form-block-status_id');
  cj('.crm-grant-form-block-grant_program_id').insertAfter('.crm-grant-form-block-grant_type_id');
  cj('.crm-grant-form-block-assessment').insertAfter('.crm-grant-form-block-amount_requested');
if ( cj("#status_id option:selected").text() == 'Ineligible') {
  cj('.grant_rejected_reason_id').show();
} else {
  cj('.grant_rejected_reason_id').hide();
}
cj('#status_id').change(function(){
if ( this.options[this.selectedIndex].text == 'Ineligible' ) {
  cj('.grant_rejected_reason_id').show();
} else {
  cj('.grant_rejected_reason_id').hide();
}
});
var dataUrl = {/literal}"{crmURL p='civicrm/grant/search' h=0 q="snippet=1&force=1"}"{literal};
dataUrl = dataUrl + '&key=' + "{/literal}{$qfKey}{literal}";
var response = cj.ajax({
  url: dataUrl,
  async: false,
  success: function(response) {
    cj('#RecentGrants').html(response);
    cj('div.crm-search-form-block, .crm-search-tasks').hide();
  }
 }).responseText;	
});
var total = 0;
cj(".form-select").change(function(){
cj(".form-select").each(function(){
var name = cj(this).attr('id');
  var customName = name.split('_');
if (customName[0] == 'custom') {
  total += parseInt(cj('#'+name).val());
}
});
});
{/literal}
</script>