Dear {contact.display_name},
        This is being sent to you as a receipt of {$grant_status} grant.
Grant Program Name: {$grant_programs}  <br>
Grant  Type    {$grant_type}
Total Amount: {$grant.amount_total}
{if $grant.grant_rejected_reason}
Grant Ineligible Reason:
{$grant.grant_rejected_reason}<br>
{/if}
{if customField}
{foreach from=$customField key=key item=data}
{$customGroup.$key}
{foreach from=$data key=dkey item=ddata}
{$ddata.label} : {$ddata.value}<br>
{/foreach}
{/foreach}
{/if}