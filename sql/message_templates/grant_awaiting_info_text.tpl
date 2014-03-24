Dear {contact.display_name},

Processing of your grant application has been put on hold while we await further information from you.
Grant Program Name: {$grant_programs}
Grant Type: {$grant_type}<br>
Total Amount: {$grant.amount_total|crmMoney:$currency}
{if $grant.grant_incomplete_reason}
Grant Incomplete Reason: {$grant.grant_incomplete_reason}
{/if}
{if $customField}
{foreach from=$customField key=key item=data}
{$customGroup.$key}
{foreach from=$data key=dkey item=ddata}
{$ddata.label}: {$ddata.value}<br>
{/foreach}
{/foreach}
{/if}