<?php if(Mage::getStoreConfig('freegift/config/enabled')):?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
	$( "#header-items a.btn-remove" ).each(function( index ) {
		$(this).attr("data-click",$(this).attr('href'));
		$(this).attr("href","#");
		$(this).attr("onclick","mw_top_minicart(this)");
	});
    $("#header-items a.btn-remove").click(function(){
		var mwhref = $(this).attr('data-click');
		confirm = confirm("Are you sure you would like to remove this item from the shopping cart?");
		if(confirm){
			$(this).parents('li').slideUp('slow');
			$.ajax({
				type : 'POST',
				url  : mwhref,
				data : 'ajax=true',
				dataType: 'json',
				success: function(data){
					console.log(data);
					if(data.error == "1"){

					}else{
						$(this).parent('li').closest().slideUp();
						location.reload();
					}
				}
			});
			return false;
		}
		return false;
	});
	function mw_top_minicart(e)
	{
		
	}
});
</script>

<?php endif;?>