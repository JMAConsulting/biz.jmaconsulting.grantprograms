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
<script type="text/javascript">
{literal}
cj(document).ready(function(){
  cj('.crm-grant-form-block-grant_rejected_reason_id').insertAfter('.crm-grant-form-block-status_id');
  cj('.crm-grant-form-block-grant_program_id').insertAfter('.crm-grant-form-block-grant_type_id');
  cj('.crm-grant-form-block-assessment').insertAfter('.crm-grant-form-block-amount_requested');
  cj('.crm-grant-form-block-financial_type').insertAfter('.crm-grant-form-block-money_transfer_date');
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
dataUrl = dataUrl + '&key=' + "{/literal}{$qfKey}{literal}";
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
cj(document).ready( function(){

// RG-116 hide attachments
{/literal}{if $hideAttachments}{literal}
cj('div.crm-grant-form-block-attachment').hide();
{/literal}{/if}{literal}

// RG-116 hide other fields unless selected 
var emp = "{/literal}{$employment}{literal}";
var emp_other = "{/literal}{$employment_other}{literal}";

var pos = "{/literal}{$position}{literal}";
var pos_other = "{/literal}{$position_other}{literal}";

var emp_setting = "{/literal}{$employment_setting}{literal}";
var emp_setting_other = "{/literal}{$employment_setting_other}{literal}";

var init = "{/literal}{$init}{literal}";
var init_other = "{/literal}{$init_other}{literal}";

var course = "{/literal}{$course}{literal}";
var course_other = "{/literal}{$course_other}{literal}";

if (!cj('#'+emp_other).val()) {
  cj('tr.'+emp_other+'-row').hide();
}
if (!cj('#'+pos_other).val()) {
  cj('tr.'+pos_other+'-row').hide();
}
if (!cj('#'+emp_setting_other).val()) {
  cj('tr.'+emp_setting_other+'-row').hide();
}
if (!cj('#'+init_other).val()) {
  cj('tr.'+init_other+'-row').hide();
}
if (!cj('#'+course_other).val()) {
  cj('tr.'+course_other+'-row').hide();
}

cj('#'+emp).change( function() {
  if (cj("option:selected", this).html() == "Other Counselling Service") {
     cj('tr.'+emp_other+'-row').show();
  }
  else {
     cj('tr.'+emp_other+'-row').hide();
  }
});
cj('#'+pos).change( function() {
  if (cj("option:selected", this).html() == "Other") {
     cj('tr.'+pos_other+'-row').show();
  }
  else {
     cj('tr.'+pos_other+'-row').hide();
  }
});
cj('#'+emp_setting).change( function() {
  if (cj("option:selected", this).html() == "Other") {
     cj('tr.'+emp_setting_other+'-row').show();
  }
  else {
     cj('tr.'+emp_setting_other+'-row').hide();
  }
});
cj('#'+init).change( function() {
  if (cj("option:selected", this).html() == "Other") {
     cj('tr.'+init_other+'-row').show();
  }
  else {
     cj('tr.'+init_other+'-row').hide();
  }
});
cj('#'+course).change( function() {
  if (cj("option:selected", this).html() == "Other") {
     cj('tr.'+course_other+'-row').show();
  }
  else {
     cj('tr.'+course_other+'-row').hide();
  }
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
});
{/literal}
</script>