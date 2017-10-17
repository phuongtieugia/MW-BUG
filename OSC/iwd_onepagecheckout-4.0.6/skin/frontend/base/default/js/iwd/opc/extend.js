;
//DUMMY FOR EE CHECKOUT
var checkout =  {
		steps : new Array("login", "billing", "shipping", "shipping_method", "payment", "review"),
		
		gotoSection: function(section){
			IWD.OPC.backToOpc();
		},
		accordion:{
			
		}
};


IWD.OPC.prepareExtendPaymentForm =  function(){
	$j('.opc-col-left').hide();
	$j('.opc-col-center').hide();
	$j('.opc-col-right').hide();
	$j('.opc-menu p.left').hide();	
	$j('#checkout-review-table-wrapper').hide();
	$j('#checkout-review-submit').hide();
	
	$j('.review-menu-block').addClass('payment-form-full-page');
	
};

IWD.OPC.backToOpc =  function(){
	$j('.opc-col-left').show();
	$j('.opc-col-center').show();
	$j('.opc-col-right').show();
	$j('#checkout-review-table-wrapper').show();
	$j('#checkout-review-submit').show();
	
	
	
	//hide payments form
	$j('#payflow-advanced-iframe').hide();
	$j('#payflow-link-iframe').hide();
	$j('#hss-iframe').hide();

	
	$j('.review-menu-block').removeClass('payment-form-full-page');
	
	IWD.OPC.saveOrderStatus = false;
	
};



IWD.OPC.Plugin = {
		
		observer: {},
		
		
		dispatch: function(event, data){
				
			
			if (typeof(IWD.OPC.Plugin.observer[event]) !="undefined"){
				
				var callback = IWD.OPC.Plugin.observer[event];
				callback(data);
				
			}
		},
		
		event: function(eventName, callback){
			IWD.OPC.Plugin.observer[eventName] = callback;
		}
};

/** 3D Secure Credit Card Validation - CENTINEL **/
IWD.OPC.Centinel = {
	init: function(){
		IWD.OPC.Plugin.event('savePaymentAfter', IWD.OPC.Centinel.validate);
	},
	
	validate: function(){
		var c_el = $j('#centinel_authenticate_block');
		if(typeof(c_el) != 'undefined' && c_el != undefined && c_el){
			if(c_el.attr('id') == 'centinel_authenticate_block'){
				IWD.OPC.prepareExtendPaymentForm();
			}
		}
	},
	
	success: function(){
		var exist_el = false;
		if(typeof(c_el) != 'undefined' && c_el != undefined && c_el){
			if(c_ell.attr('id') == 'centinel_authenticate_block'){
				exist_el = true;
			}
		}
		
		if (typeof(CentinelAuthenticateController) != "undefined" || exist_el){
			IWD.OPC.backToOpc();
		}
	}
	
};


function toggleContinueButton(){}//dummy

$j(document).ready(function(){
	IWD.OPC.Centinel.init();
});
