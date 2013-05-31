{literal}
<script type="text/javascript">
cj(document).ready( function() {

// freeze decision date 
cj('#decision_date_display').hide();
cj('#decision_date_display').next().hide();

cj('#decision_date').show(); 
cj('#decision_date').replaceWith( function() {
    return cj( this ).val();
});

});
</script>
{/literal}