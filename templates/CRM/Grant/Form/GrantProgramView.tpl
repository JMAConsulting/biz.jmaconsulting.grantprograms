{*
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
*}
{* this template is used for viewing grants *}

<div class="crm-block crm-content-block crm-grant-view-block">
    <div class="crm-submit-buttons">
        {if call_user_func(array('CRM_Core_Permission','check'), 'edit grants')}
            {assign var='urlParams' value="action=update&id=$id&reset=1"}
            {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
                {assign var='urlParams' value="action=update&id=$id&reset=1"}
            {/if}
            <a class="button" href="{crmURL p='civicrm/grant_program' q=$urlParams}" accesskey="e"><span><div class="icon edit-icon"></div> {ts}Edit{/ts}</span></a>
        {/if}
        {if call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviGrant')}
    	    {assign var='urlParams' value="action=delete&id=$id&reset=1"}
            {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
                {assign var='urlParams' value="action=delete&id=$id&reset=1"}
            {/if}
            <a class="button" href="{crmURL p='civicrm/grant_program' q=$urlParams}"><span><div class="icon delete-icon"></div>{ts}Delete{/ts}</span></a>
        {/if}
        {include file="CRM/common/formButtons.tpl" location="top"}<br/>

    </div>
    <table class="crm-info-panel">
        <tr class="crm-grant-program-view-form-block-name"><td class="label">{ts}Name{/ts}</td><td class="bold">{$name}</td></tr>
        <tr class="crm-grant-program-view-form-block-grant_type_id"><td class="label">{ts}Grant Type{/ts}</td> <td>{$grantType}</td></tr>
	<tr class="crm-grant-program-view-form-block-total_amount"><td class="label">{ts}Total Amount{/ts}</td> <td>{$total_amount|crmMoney}</td></tr>
	<tr class="crm-grant-program-view-form-block-remainder_amount"><td class="label">{ts}Remainder Amount{/ts}</td> <td>{$remainder_amount|crmMoney}</td></tr>
      <tr class="crm-grant-program-view-form-block-from_email_address"><td class="label">{ts}FROM Email Address{/ts}</td>
        <td>{$from_email_address}</td></tr>
	<tr class="crm-grant-program-view-form-block-contribution_type_id"><td class="label">{ts}Contribution Type{/ts}</td> <td>{$contributionType}</td></tr>
        <tr class="crm-grant-program-view-form-block-status_id"><td class="label">{ts}Grant Status{/ts}</td> <td>{$grantProgramStatus}</td></tr>
	<tr class="crm-grant-program-view-form-block-allocation_algorithm"><td class="label">{ts}Allocation Algorithm{/ts}</td> <td>{$grantProgramAlgorithm}</td></tr>
	<tr class="crm-grant-program-view-form-block-grant_program_id"><td class="label">{ts}Previous Year's Grant{/ts}</td> <td>{$grant_program_id}</td></tr>
        <tr class="crm-grant-program-view-form-block-allocation_date"><td class="label">{ts}Allocation Date{/ts}</td> <td>{$allocation_date|crmDate}</td></tr>
        <tr class="crm-grant-program-view-form-block-is_active"><td class="label">{ts}Enabled?{/ts}</td> <td>{if $is_active}{ts}Yes{/ts} {else}{ts}No{/ts}{/if}</td></tr>
	<tr class="crm-grant-program-view-form-block-is_active"><td class="label">{ts}Auto Email?{/ts}</td> <td>{if $is_auto_email}{ts}Yes{/ts} {else}{ts}No{/ts}{/if}</td></tr>
    </table>
    {include file="CRM/Custom/Page/CustomDataView.tpl"}
    <div class="crm-submit-buttons">
        {if call_user_func(array('CRM_Core_Permission','check'), 'edit grants')}
            {assign var='urlParams' value="action=update&id=$id&reset=1"}
	        {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
	            {assign var='urlParams' value="action=update&id=$id&reset=1"}
	        {/if}
            <a class="button" href="{crmURL p='civicrm/grant_program' q=$urlParams}" accesskey="e"><span><div class="icon edit-icon"></div> {ts}Edit{/ts}</span></a>
        {/if}
        {if call_user_func(array('CRM_Core_Permission','check'), 'delete in CiviGrant')}
		    {assign var='urlParams' value="action=update&id=$id&reset=1"}
	        {if ( $context eq 'fulltext' || $context eq 'search' ) && $searchKey}
	            {assign var='urlParams' value="action=update&id=$id&reset=1"}
	        {/if}
            <a class="button" href="{crmURL p='civicrm/grant_program' q=$urlParams}"><span><div class="icon delete-icon"></div>{ts}Delete{/ts}</span></a>
        {/if}
        {include file="CRM/common/formButtons.tpl" location="bottom"}<br/>
    </div>
</div>
<div>
    <a class="action-item crm-hover-button" href="#" onclick="actionTask('allocation', {$id});return false;">{ts}Allocate Approved (Trial){/ts}</a>
    <a class="action-item crm-hover-button" href="#" onclick="actionTask('finalize', {$id});return false;">{ts}Finalize Approved Allocations{/ts}</a>
    <a class="action-item crm-hover-button" href="#" onclick="actionTask('reject', {$id});return false;" >{ts}Mark remaining unapproved Grants as Ineligible{/ts}</a>
</div>
{include file="CRM/Grant/Form/GrantActionTask.tpl"}
