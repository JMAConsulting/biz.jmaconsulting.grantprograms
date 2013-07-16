Dear {contact.display_name},

Thank you for the notification that you want the grant application below and any associated award to be withdrawn. We have completed the withdrawal now.
Grant Program Name: {$grant_programs}  <br>
Grant Type:    {$grant_type}
Total Amount: {$grant.amount_total|crmMoney:$currency}
{if customField}
  {foreach from=$customField key=key item=data}
    {$customGroup.$key}
    {foreach from=$data key=dkey item=ddata}
{$ddata.label}: {$ddata.value}<br>
    {/foreach}
  {/foreach}
{/if}