{if $view_form}
<div id="field_page_new">
      <p></p>
      <div class="form-item">
        {strip}
        {* handle enable/disable actions*}
         {include file="CRM/common/enableDisable.tpl"}
        <table class="selector">
          <tr class="columnheader">
            <th>{ts}Label{/ts}</th>
            <th>{ts}Value{/ts}</th>
	    <th>{ts}Mark{/ts}</th>
            <th>{ts}Default{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Enabled?{/ts}</th>
            <th>&nbsp;</th>
          </tr>
          {foreach from=$customOption item=row key=id}
            <tr id="OptionValue-{$id}"class="crm-entity {cycle values="odd-row,even-row"} {$row.class} crm-custom_option {if NOT $row.is_active} disabled{/if}">
              <td><span class="crm-custom_option-label crm-editable crmf-label">{$row.label}</span></td>
              <td><span class="crm-custom_option-value disabled-crm-editable" data-field="value" data-action="update">{$row.value}</span></td>
              <td class="crm-custom_option-description disabled-crm-editable" data-field="description" data-action="update">{$row.description}</td>
              <td class="crm-custom_option-default_value crmf-value">{$row.default_value}</td>
              <td class="nowrap crm-custom_option-weight crmf-weight">{$row.weight}</td>
              <td id="row_{$id}_status" class="crm-custom_option-is_active crmf-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td>{$row.action|replace:'xx':$id}</td>
            </tr>
          {/foreach}
          </table>
        {/strip}

        <div class="action-link">
            <a href="{crmURL q="reset=1&action=add&fid=$fid&gid=$gid"}" class="button"><span><div class="icon add-icon"></div> {ts 1=$fieldTitle}Add Option for '%1'{/ts}</span></a>
        </div>
      </div>
    </div>
{/if}
{literal}
<script type="text/javascript">
cj(document).ready( function(){
cj('div#field_page').replaceWith(cj('div#field_page_new'));
});
</script>
{/literal}