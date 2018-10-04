<div id="actionDialog" class="crm-container" style="display:none;"></div>

{literal}
<script type="text/javascript">
  function actionTask(task, id) {
    if (task == 'allocation') {
      var msg = {/literal}'{ts}Do you want to do a trial allocation?{/ts}'{literal};
      var data = 'pid=' + id;
      var dataURL = {/literal}"{crmURL p='civicrm/grant_program/allocate'}"{literal};
      CRM.$('#actionDialog').dialog({
        title: {/literal}'{ts}Grant Allocation{/ts}'{literal},
        modal: true,
        open:function() {
          CRM.$(this).show().html(msg);
        },
        buttons: {
          {/literal}"{ts escape='js'}No{/ts}"{literal}: function() {
            CRM.$(this).dialog("close");
          },
          {/literal}"{ts escape='js'}Yes{/ts}"{literal}: function() {
            CRM.$(this).dialog("close");
            cj.ajax({
              url: dataURL,
              data: data,
              type: 'POST',
              success: function(output) {
                CRM.status(CRM.$.parseJSON(output));
              }
            });
            return;
          }
        }
      });
    }
    else if (task == 'finalize') {
      var data = 'pid=' + id;
      var dataURL = {/literal}"{crmURL p='civicrm/grant_program/finalize'}"{literal};
      cj.ajax({
          url: dataURL,
          data: data,
          type: 'POST',
          success: function(output) {
            if (!output){
              CRM.status({/literal}"{ts escape='js'}Eligible grant(s) not found for final allocation{/ts}"{literal});
              return true;
            }
            else {
              var result = CRM.$.parseJSON(output);
              if (result.status == 'confirm') {
                var msg = {/literal}'{ts}Do you want to finalize the award of grants for this grant program to the amounts currently allocated?{/ts}'{literal};
                dataURL = {/literal}"{crmURL p='civicrm/grant_program/processFinalization'}"{literal};
                CRM.$('#actionDialog').dialog({
                  title: {/literal}'{ts}Grant Final Allocation{/ts}'{literal},
                  modal: true,
                  open:function() {
                    CRM.$(this).show().html(msg);
                  },
                  buttons: {
                    {/literal}"{ts escape='js'}No{/ts}"{literal}: function() {
                      CRM.$(this).dialog("close");
                    },
                    {/literal}"{ts escape='js'}Yes{/ts}"{literal}: function() {
                      CRM.$(this).dialog("close");
                      cj.ajax({
                        url: dataURL,
                        data: data,
                        type: 'POST',
                        success: function(output) {
                          CRM.status({/literal}"{ts escape='js'}Grants are approved successfully.{/ts}"{literal});
                        }
                      });
                      return;
                    }
                  }
                });
              }
              else {
                alert("The sum of the grants to be allocated $" + result.amount_granted + ".00 is greater than the total amount available to be allocated by the program $" + result.total_amount + ". Please reduce the amount granted in pending applications or increase the total amount available to be granted.");
              }
            }
          }
        });
    }
    else {
      var msg = {/literal}'{ts}Do you want to reject all Pending grant applications for this Grant Program?{/ts}'{literal};
      var data = 'pid=' + id;
      var dataURL = {/literal}"{crmURL p='civicrm/grant_program/reject'}"{literal};
      CRM.$('#actionDialog').dialog({
        title: {/literal}'{ts}Grant Allocation{/ts}'{literal},
        modal: true,
        open:function() {
          CRM.$(this).show().html(msg);
        },
        buttons: {
          {/literal}"{ts escape='js'}No{/ts}"{literal}: function() {
            CRM.$(this).dialog("close");
          },
          {/literal}"{ts escape='js'}Yes{/ts}"{literal}: function() {
            CRM.$(this).dialog("close");
            cj.ajax({
              url: dataURL,
              data: data,
              type: 'POST',
              success: function(output) {
                CRM.status(CRM.$.parseJSON(output));
              }
            });
            return;
          }
        }
      });
    }
  }
</script>
{/literal}
