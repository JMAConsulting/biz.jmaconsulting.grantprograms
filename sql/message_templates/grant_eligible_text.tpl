Dear {contact.display_name},

We have determined that the grant application below is eligible to receive an award. The actual amount to be awarded will be determined at a later date.
Grant Program Name: {$grant_programs}  <br>
Grant Type: {$grant_type}
Total Amount: {$grant.amount_total|crmMoney:$currency}
{if customField}
{foreach from=$customField key=key item=data}
{$customGroup.$key}
{foreach from=$data key=dkey item=ddata}
{$ddata.label}: {$ddata.value}<br>
{/foreach}
{/foreach}
{/if}