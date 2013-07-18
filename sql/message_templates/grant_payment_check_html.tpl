<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title></title>
  </head>
 {foreach from=$grantPayment key=key item=data}
  <body >
    <div style="height:140px" >
    <div style="float:right; text-align:right; margin-top:-40px;">{$data.payment_id}</div>
    <table width="100%" cellpadding=0 cellspacing=0 ; style="margin-top:40px;">
      <tr>
        <td width="85%"></td><td width="15%" style="float:right; text-align:right;">{$data.payment_date}</td>
      </tr>
      <tr>
        <td width="85%">{$data.total_in_words}</td><td width="15%" style="float:right; text-align:right;">{$data.amount|crmMoney:$data.currency}</td>
      </tr> 
      </table>
      </div>
      <div style="height:60px" >
      <table width="100%" cellpadding=0 cellspacing=0 ;">
      <tr>
         <td width="100%">{$data.payable_to_name}</td>
      </tr> 
      <tr>
         <td width="100%">{$data.payable_to_address}</td>
      </tr>
    </table>
    </div>
  <div style="height:100px" >
    <table width="100%" cellpadding=0 cellspacing=0 ;>
       <tr>
         <td width="100%">RE: {$data.payment_reason}</td>
      </tr>
    </table>
    </div>
    <div style="height:300px" >
    <div style="float:right; text-align:right;">{$data.payment_id}</div>
     <table width="100%"  cellpadding=0 cellspacing=0 >
       <tr><td width="100%" colspan="4">RE: {$data.payment_reason}</td></tr>
       <tr><td width="15%">Payment Date</td><td width="15%">Grant ID</td><td width="50%">Payee</td><td width="20%">Amount</td></tr>
       <tr><td width="15%">{$data.payment_details}</td> </tr> 
      <tr>
      <td width="15%"></td><td width="15%%"></td><td width="50%" style="text-align:right;" >Total Amount&nbsp;&nbsp;&nbsp;&nbsp;</td><td width="15%"  >CAD: {$data.amount|crmMoney:$data.currency}</td>
      </tr> 
     </table>
   </div>
    <div style="height:265px" >
   <div style="float:right; text-align:right;"> {$data.payment_id} </div>
    <table width="100%"  cellpadding=0 cellspacing=0 >
      <tr><td width="100%" colspan="4">RE: {$data.payment_reason}</td></tr>
      <tr><td width="15%">Payment Date</td><td width="15%">Grant ID</td><td width="50%">Payee</td><td width="20%">Amount</td></tr>
      <tr><td width="15%">{$data.payment_details}</td>
      </tr> 
      <tr>
      <td width="15%"></td><td width="15%%"></td><td width="50%" style="text-align:right; ">Total Amount&nbsp;&nbsp;&nbsp;&nbsp;</td><td width="20%"  >CAD: {$data.amount|crmMoney:$data.currency}</td>
      </tr> 
    </table>
    </div>
  </body>
{/foreach}
</html>