Dear {contact.display_name},
        This is being sent to you as a receipt of {$grant_status} grant.
Grant Program Name: {$grant_programs}  <br>
Grant  Type    {$grant_type}<br>
Total Amount: {$grant.amount_total}<br>
{if $grant.grant_incomplete_reason}
Grant Incomplete Reason:
{$grant.grant_incomplete_reason}<br>
{/if}
{if customField}
{foreach from=$customField key=key item=data}
{$customGroup.$key}
{foreach from=$data key=dkey item=ddata}
{$ddata.label} : {$ddata.value}<br>
{/foreach}
{/foreach}
{/if}