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
{if $action neq 8}
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
  {if $form.prev_assessment}
  <tr class="crm-grant-form-block-prev_assessment">
    <td class="label">{$form.prev_assessment.label}</td>
    <td>{$form.prev_assessment.html}</td>
  </tr>
  {/if}
  <tr class="crm-grant-form-block-financial_type">
    <td class="label">{$form.financial_type_id.label}</td>
    <td>
      {if !$financialType}
        {capture assign=ftUrl}{crmURL p='civicrm/admin/financial/financialType' q="reset=1"}{/capture}
        {ts 1=$ftUrl}There is no Financial Type configured.<a href='%1'> Click here</a> if you want to configure financial type for your site.{/ts}
      {else}
        {$form.financial_type_id.html}
      {/if}
    </td>
  </tr>  
</tbody></table>

{*if $pager->_totalItems*}
  <h3>{ts}Recent Grants{/ts}</h3>
  <div class="form-item" id = "RecentGrants">
    {* include file="CRM/Grant/Form/Selector.tpl" context="DashBoard" *}
  </div>
{*/if*}
{/if}
<script type="text/javascript">
{literal}
cj(document).ready(function(){
  cj('.crm-grant-form-block-grant_rejected_reason_id').insertAfter('.crm-grant-form-block-status_id');
  cj('.crm-grant-form-block-grant_program_id').insertAfter('.crm-grant-form-block-grant_type_id');
  cj('.crm-grant-form-block-assessment').insertAfter('.crm-grant-form-block-amount_requested');
  cj('.crm-grant-form-block-prev_assessment').insertAfter('.crm-grant-form-block-assessment');
  cj('.crm-grant-form-block-financial_type').insertAfter('.crm-grant-form-block-money_transfer_date');
{/literal} 
{if !$showFields}
{literal} 
  cj('.crm-grant-form-block-amount_granted').remove();
{/literal} 
{/if}
{literal} 
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
var grantId = {/literal}{if $grant_id}{$grant_id}{else}{literal}0{/literal}{/if}{literal};
var dataUrl = {/literal}"{crmURL p='civicrm/grant/search' h=0 q="snippet=1&force=1"}"{literal};
dataUrl = dataUrl + '&cid=' + "{/literal}{$contactId}{literal}" + '&key=' + "{/literal}{$qfKey}{literal}";
var response = cj.ajax({
  url: dataUrl,
  async: false,
  success: function(response) {
    cj('#RecentGrants').html(response);
    cj('div.crm-search-form-block, .crm-search-tasks').hide();
    cj('tr#crm-grant_'+grantId).hide();
  }
 }).responseText;	
});
{/literal}
</script>