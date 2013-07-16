Dear {contact.display_name},

We regret to inform you that the application below is ineligible.
Grant Program Name: {$grant_programs}  <br>
Grant Type: {$grant_type}
Total Amount: {$grant.amount_total|crmMoney:$currency}
{if $grant.grant_rejected_reason}
Grant Ineligible Reason: {$grant.grant_rejected_reason}<br>
{/if}
{if customField}
{foreach from=$customField key=key item=data}
{$customGroup.$key}
{foreach from=$data key=dkey item=ddata}
{$ddata.label}: {$ddata.value}<br>
{/foreach}
{/foreach}
{/if}