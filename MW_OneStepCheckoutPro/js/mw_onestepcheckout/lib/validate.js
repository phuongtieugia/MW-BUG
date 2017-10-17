var $j_mw = jQuery.noConflict();
var tmp = 0;
$j_mw(function(){	
	$j_mw('#onestepcheckout_display_setting_style_color, #onestepcheckout_display_setting_checkout_button_color').ColorPicker({
		onSubmit: function(hsb, hex, rgb, el) {
		$j_mw(el).val(hex);
		$j_mw(el).ColorPickerHide();
		},
		onBeforeShow: function () {
			$j_mw(this).ColorPickerSetColor(this.value);
		}
	})
	.bind('keyup', function(){
		$j_mw(this).ColorPickerSetColor(this.value);
	});
});
