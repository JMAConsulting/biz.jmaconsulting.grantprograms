{literal}
<script type="text/javascript">
cj(document).ready( function() {
cj('<tr class="crm-grant-view-form-block-assessment"><td class="label">Assessment</td><td>{/literal}{$assessment}{literal}</td></tr>').insertAfter('tr.crm-grant-view-form-block-grant_due_date');
cj('<tr class="crm-grant-view-form-block-prev_assessment"><td class="label">Prior Year\'s Assessment</td><td>{/literal}{$prev_assessment}{literal}</td></tr>').insertAfter('tr.crm-grant-view-form-block-assessment');
});
</script>
{/literal}