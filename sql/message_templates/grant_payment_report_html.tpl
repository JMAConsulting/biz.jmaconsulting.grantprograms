<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title></title>
  </head>
  <body>
  {assign var='page' value=1}
  <div style="float:right; text-align:right; margin-top:-50px;"> Page: {$page} </div>
    <table width="100%" cellpadding=0 cellspacing=0  > 
      <tr>
	<td width="50%" align="right"><b><font size="2">{$domain_name}</font></b></td><td width="25%" align="right" ><b><font size="2">Run By :</font></b></td><td width="25%" align="left" ><font size="2">{$contact}</font></td>
      </tr>
      <tr>
       <td width="50%" align="right"></td><td width="25%" align="right" ><b><font size="2">Date :</font></b></td><td width="25%" align="left" ><font size="2">{$date}</font></td>
       </tr>
       <tr>
       <td width="50%" align="right"><b><font size="2">A/P Cheque Register</font></b></td><td width="25%" align="right" ><b><font size="2">Time :</font></b></td><td width="25%" align="left" ><font size="2">{$time}</font></td>
       </tr> 
    </table>
  </br>
  <table width="100%" cellpadding=0 cellspacing=0  > 
    <tr>
      <td width="25%" align="center" ><b><font size="2">Batch ID:{$batch_number}</font></b></td><td width="25%" align="center" ><b><font size="2">Currency: CAD</font></b></td><td width="25%" align="center" ><b><font size="2">Accounting Year: 2012</font></b></td>
    </tr>
  </table> <hr/></br></br>
  <table width="100%" cellpadding=0 cellspacing=0  >
    <tr>
      <td width="10%"><font size="2">Cheque Number</font></td><td width="10%"><font size="2">Ref #</font></td><td width="20%"><font size="2">Payee</font></td><td width="12%"  align="center"><font size="2" >Cheque Amount</font></td><td width="12%"><font size="2">Cheque Issue Date</font></td><td width="15%"><font size="2">Cheque Printed By</font></td><td width="14%"><font size="2">Print Date</font></td><td width="12%"><font size="2">Replaces Cheque Number</font></td>
    </tr>
    {if $grantPayment}
    {assign var ='break' value =40}
    {assign var ='count' value =1}
    {foreach from=$grantPayment key=key item=data}
    {if $break eq $count }
    {assign var =page value =$page+1}
    <tr style="page-break-after:always">
<td width="10%"><font size="2">{$data.payment_number}</font></td><td width="10%"><font size="2">{$key}</font></td><td width="20%"><font size="2">{$data.payable_to_name}</font></td><td width="25%" align="center" ><b><font size="2">Account:{$data.account_name}</font></b></td><td width="12%" align="right" ><font size="2" >{$data.amount}&nbsp;</font></td><td width="12%"><font size="2">{$data.payment_date}</font></td><td width="15%"><font size="2">&nbsp;{$contact}</font></td><td width="14%"><font size="2">{$data.payment_created_date}</font></td><td width="12%" ><font size="2">{$data.replaces_payment_id}</font></td>
    </tr>
     </table>  
    <div style="float:right; text-align:right; margin-top:-50px;"> Page: {$page} </div>
     <table width="100%" cellpadding=0 cellspacing=0  > 
      <tr>
	<td width="50%" align="right"><b><font size="2">{$domain_name}</font></b></td><td width="25%" align="right" ><b><font size="2">Run By :</font></b></td><td width="25%" align="left" ><font size="2">{$contact}</font></td>
      </tr>
      <tr>
       <td width="50%" align="right"></td><td width="25%" align="right" ><b><font size="2">Date :</font></b></td><td width="25%" align="left" ><font size="2">{$date}</font></td>
       </tr>
       <tr>
       <td width="50%" align="right"><b><font size="2">A/P Cheque Register</font></b></td><td width="25%" align="right" ><b><font size="2">Time :</font></b></td><td width="25%" align="left" ><font size="2">{$time}</font></td>
       </tr> 
    </table>
  </br>
  <table width="100%" cellpadding=0 cellspacing=0  > 
    <tr>
      <td width="25%" align="center" ><b><font size="2">Batch ID:{$batch_number}</font></b></td><td width="25%" align="center" ><b><font size="2">Currency: CAD</font></b></td><td width="25%" align="center" ><b><font size="2">Account:{$account_name}</font></b></td><td width="25%" align="center" ><b><font size="2">Accounting Year: 2012</font></b></td>
    </tr>
  </table> <hr/></br></br>
  <table width="100%" cellpadding=0 cellspacing=0  >
    <tr>
      <td width="10%"><font size="2">Cheque Number</font></td><td width="10%"><font size="2">Ref #</font></td><td width="20%"><font size="2">Payee</font></td><td width="12%" align="center"><font size="2" >Cheque Amount</font></td><td width="12%"><font size="2">Cheque Issue Date</font></td><td width="15%"><font size="2">Cheque Printed By</font></td><td width="14%"><font size="2">Print Date</font></td><td width="12%"><font size="2">Replaces Cheque Number</font></td>
    </tr>
    {assign var='break' value=$break+$break}
    {else}
    <tr>
<td width="10%"><font size="2">{$data.payment_number}</font></td><td width="10%"><font size="2">{$key}</font></td><td width="20%"><font size="2">{$data.payable_to_name}</font></td><td width="12%" align="right"><font size="2" >{$data.amount}&nbsp;</font></td><td width="12%"><font size="2">{$data.payment_date}</font></td><td width="15%"><font size="2">&nbsp;{$contact}</font></td><td width="14%"><font size="2">{$data.payment_created_date}</font></td><td width="12%" ><font size="2">{$data.replaces_payment_id}</font></td>
    </tr>
    {/if}
    {assign var='count' value=$count+1}
    {/foreach}
    {/if}
  </table>  
  <br><br><hr>
  <table width="100%" cellpadding=0 cellspacing=0  >
  <tr><td colspan="2"><b>Statistics:</b></td></tr> 
  <tr><td width="50%">Number of cheques : {$total_payments}</td><td width="50%">Total amount : {$total_amount} </td></tr>
  </table>  
</body>
</html>