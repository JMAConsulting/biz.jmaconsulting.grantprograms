<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title></title>
  </head>
  {foreach from=$grantPayment key=key item=data}
  <body >
  
  <!-- Stub 1 -->
  <div style="height:300px; font-size: 11px;">
    <div style="float:right; text-align:right;">{$data.payment_id}</div>
    <table width="100%"  cellpadding=0 cellspacing=0 style="font-size: 11px;">
      <tr><td width="100%" colspan="4">RE: {$data.payment_reason}</td></tr>
      <tr><td width="15%">Payment Date</td>
        <td width="15%">Grant ID</td><td width="50%">Payee</td>
        <td width="20%">Amount</td>
      </tr>
      <tr><td width="15%">{$data.payment_details}</td> </tr> 
    <tr>
    <td width="15%"></td><td width="15%"></td><td width="50%" style="text-align:right;">Total Amount&nbsp;&nbsp;&nbsp;&nbsp;</td><td width="15%">{$data.amount|crmMoney:$data.currency}</td>
    </tr> 
    </table>
  </div>
  
  <!-- Cheque portion -->
  <div style="height:100px" >
    <table width="100%" cellpadding=0 cellspacing=0>
      <tr style="height: 50px;">
        <td width="85%"></td>
        <td width="15%" style="text-align:right; vertical-align: top;">{$data.payment_date|date_format:"%d-%m-%Y"}</td>
      </tr>
      <tr style="height: 50px;">
	<!-- Total in words and amount not quite aligned on cheque so... we HACK -->
        <td width="85%;" style="padding-bottom: 18px;">{$data.total_in_words}</td>
        <td width="15%" style="text-align:right; vertical-align: top;">{$data.amount|string_format:"%.2f"}</td>
      </tr>
    </table>
  </div>
  <div style="height:260px" >
    <table width="100%" cellpadding=0 cellspacing=0>
      <tr>
        <td width="100%">{$data.payable_to_name}</td>
      </tr> 
      <tr>
        <td width="100%">{$data.payable_to_address}</td>
      </tr>
    </table>
  </div>
    
  <!-- Stub 2 -->
  <div style="page-break-after: always; font-size: 10px;" >
  <div style="float:right; text-align:right;"> {$data.payment_id} </div>
    <table width="100%" cellpadding=0 cellspacing=0 style="font-size: 11px;">
      <tr><td width="100%" colspan="4">RE: {$data.payment_reason}</td></tr>
      <tr><td width="15%">Payment Date</td><td width="15%">Grant ID</td><td width="50%">Payee</td>
        <td width="20%">Amount</td></tr>
      <tr><td width="15%">{$data.payment_details}</td>
      </tr> 
      <tr>
	<td width="15%"></td><td width="15%"></td>
	<td width="50%" style="text-align:right; ">Total Amount&nbsp;&nbsp;&nbsp;&nbsp;</td>
	<td width="20%">{$data.amount|crmMoney:$data.currency}</td>
      </tr> 
    </table>
  </div>
  </body>
{/foreach}
</html>