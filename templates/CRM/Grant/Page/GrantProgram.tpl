{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.0                                                |
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
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Grant/Form/GrantProgram.tpl"}
{elseif $action eq 4}
   {include file="CRM/Grant/Form/GrantProgramView.tpl"}
{else}
{if $programs}
<div id="ltype">
<p></p>
    <div class="form-item">
    {if $action ne 1 and $action ne 2 and $context ne 'dashboard'}
	    <div class="action-link">
    	<a href="{crmURL q="action=add&reset=1"}" id="grant_program" class="button"><span><div class="icon add-icon"></div>{ts}Add Grant Program{/ts}</span></a>
        </div>
    {/if}
    {strip}
        <table cellpadding="0" cellspacing="0" border="0">
           <thead class="sticky">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Type{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Total{/ts}</th>
            <th>{ts}Status{/ts}</th>
	    <th>{ts}Allocation Date{/ts}</th>
	    <th>{ts}Enabled?{/ts}</th>	
            <th></th>
          </thead>
         {foreach from=$programs item=row}
        <tr id="row_{$row.id}"class="{cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
	        <td>{$row.label}</td>	
	        <td>{$row.grant_type_id}</td>
            	<td>{$row.description}</td>
	        <td>{$row.total_amount}</td>
	        <td>{$row.status_id}</td>
		<td>{$row.allocation_date|truncate:10:''|crmDate}</td>
	        <td>{if $row.is_active}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
	        <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
         </table>
        {/strip}

        {if $action ne 1 and $action ne 2 and $context ne 'dashboard'}
	    <div class="action-link">
    	<a href="{crmURL q="action=add&reset=1"}" id="grant_program" class="button"><span><div class="icon add-icon"></div>{ts}Add Grant Program{/ts}</span></a>
        </div>
        {/if}
    </div>
</div>
{else}
    <div class="messages status">
        <div class="icon inform-icon"></div>
        {capture assign=crmURL}{crmURL q="action=add&reset=1"}{/capture}
        {ts 1=$crmURL}There are no Grant Programs entered. You can <a href='%1'>add one</a>.{/ts}
    </div>    
{/if}
{/if}

{if $action neq 4}{literal}
<script type="text/javascript">

cj(document).ready(function(){
cj('ul.panel').css('width','250px');

cj('#allocation').click(function(event){
var r=confirm("Do you want to do a trial allocation?");
if (r==true)
  {  
     event.preventDefault();
     var data = 'pid={/literal}{$id}{literal}&amount={/literal}{$total_amount}{literal}&remainder_amount={/literal}{$remainder_amount}{literal}&algorithm={/literal}{$grantProgramAlgorithm}{literal}';
     var dataURL = {/literal}"{crmURL p='civicrm/grant_program/allocate'}"{literal};
     cj.ajax({ 
         url: dataURL,	
         data: data,
         type: 'POST',
         success: function(output) { 
          setTimeout("location.reload(true);",1500);
	 }
      });
   }
else {
return false;
}
});

cj('#finalize').click(function(event){
 var confirmed = 0;
 var totalAmounts = 0;
 var grantedAmount = 0;
 event.preventDefault();
 var data = 'pid={/literal}{$id}{literal}}&amount={/literal}{$total_amount}{literal}';
     var dataURL = {/literal}"{crmURL p='civicrm/grant_program/finalize'}"{literal};
     cj.ajax({ 
         url: dataURL,	
         data: data,
         type: 'POST',
         success: function(output) { 
	 var result = eval('(' + output + ')');
	 cj.each( result, function( index, value ) {
alert(index);alert(value);
	 if( index == 'confirm' ) {
	   confirmed = value;
	 }
	 if( index == 'total_amount' ) {
	   totalAmounts = value;
	 }
	 if( index == 'amount_granted' ) {
	   grantedAmount = value;
	   var data = 'amount_granted = '+value;
	   alert(data);
	 }
         });
	 alert(data);
	 if (confirmed == 'confirm' ) {
	    var r=confirm("Do you want finalize the award of grants for this grant program to the amounts currently allocated?");
	    if (r==true)
  	    { 
	    var dataURL = {/literal}"{crmURL p='civicrm/grant_program/processFinalization'}"{literal};
     	    cj.ajax({ 
              url: dataURL,	
              data: data,
              type: 'POST',
              success: function(output) { 
	      setTimeout("location.reload(true);",1500);
	      }
	      });
	    }
	 } else {
alert("The sum of the grants to be allocated ($"+grantedAmount+".00) is greater than the total amount available to be allocated by the program ($"+totalAmounts+"). Please reduce the amount granted in pending applications or increase the total amount available to be granted.");
	  }
	}
   });  
});



cj('#reject').click(function(event){

var r=confirm("Do you want to reject all Pending grant applications for this Grant Program??");
if (r==true)
  {
  event.preventDefault();
 var data = 'pid={/literal}{$id}{literal}';
     var dataURL = {/literal}"{crmURL p='civicrm/grant_program/reject'}"{literal};
     cj.ajax({ 
         url: dataURL,	
         data: data,
         type: 'POST',
         success: function(output) { 
	   setTimeout("location.reload(true);",1500);
	 }
   });
  }     
});

});

</script>
{/literal}
{/if}
