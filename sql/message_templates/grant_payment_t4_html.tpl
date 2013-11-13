<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title></title>
</head>
{foreach from=$grantPayment key=key item=data}
  <body>
  <!-- Stub 1 -->
  <div style="height:500px; width: 500px;">
    <table width="100%"  cellpadding=0 cellspacing=0 style="font-size: 16px; width: 500px; height: 500px;">
      <tr><td colspan="2" style="vertical-align: top; height: 138px;">
          <table width="100%"  cellpadding=0 cellspacing=0 style="font-size: 16px; width: 500px;"><tr>
              <td style="vertical-align: top; height: 138px; padding-top: 30px; width: 315px;">{$data.payer}</td>
              <td style="vertical-align: top; height: 138px; width: 185px">{$data.t4_year}</td>
            </tr></table>
        </td></tr>
      <tr>
        <td colspan="2" style="height: 64px; vertical-align: top;">{$data.sin}</td>
      </tr>
      <tr><td colspan="2" style="vertical-align: top; height: 53px;">
          <table width="100%"  cellpadding=0 cellspacing=0 style="font-size: 16px; width: 500px;"><tr>
              <td style="height: 53px; padding-top: 4px; width: 205px; vertical-align: top;">{$data.last_name|upper}</td>
              <td style="height: 53px; padding-top: 4px; width: 295px; vertical-align: top;">{$data.first_name}</td>
            </tr></table>
      </tr>
      <tr>
        <td colspan="2" style="height: 115px; vertical-align: top;">{$data.payable_to_address}</td>
      </tr>
      <tr>
        <td colspan="2" style="padding-top: 6px; padding-left: 4px; vertical-align: top;">{$data.box}&nbsp;&nbsp;&nbsp;&nbsp;{$data.amount}</td>
      </tr>
    </table>
  </div>

  <!-- Stub 2 -->
  <div style="width: 500px;">
    <table width="100%"  cellpadding=0 cellspacing=0 style="font-size: 16px; width: 500px;">
      <tr><td colspan="2" style="vertical-align: top; height: 138px;">
          <table width="100%"  cellpadding=0 cellspacing=0 style="font-size: 16px; width: 500px;"><tr>
              <td style="vertical-align: top; height: 138px; padding-top: 30px; width: 315px;">{$data.payer}</td>
              <td style="vertical-align: top; height: 138px; width: 185px">{$data.t4_year}</td>
            </tr></table>
        </td></tr>
      <tr>
        <td colspan="2" style="height: 64px; vertical-align: top;">{$data.sin}</td>
      </tr>
      <tr><td colspan="2" style="vertical-align: top; height: 55px;">
          <table width="100%"  cellpadding=0 cellspacing=0 style="font-size: 16px; width: 500px;"><tr>
              <td style="height: 53px; padding-top: 4px; width: 205px; vertical-align: top;">{$data.last_name|upper}</td>
              <td style="height: 53px; padding-top: 4px; width: 295px; vertical-align: top;">{$data.first_name}</td>
            </tr></table>
      </tr>
      <tr>
        <td colspan="2" style="height: 115px; vertical-align: top;">{$data.payable_to_address}</td>
      </tr>
      <tr>
        <td colspan="2" style="padding-top: 6px; padding-left: 4px; vertical-align: top;">{$data.box}&nbsp;&nbsp;&nbsp;&nbsp;{$data.amount}</td>
      </tr>
    </table>
  </div>

  <div style="page-break-after: always;"></div>
  </body>
{/foreach}
</html>