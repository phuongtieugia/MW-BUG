if(typeof MWSagePaySuite == 'undefined') {
    var MWSagePaySuite = {};
}
MWSagePaySuite.Checkout = Class.create();
MWSagePaySuite.Checkout.prototype = {

    initialize: function(config){
        this.config 		    = config;
        this.servercode			= 'sagepayserver';
        this.directcode			= 'sagepaydirectpro';
        this.paypalcode			= 'sagepaypaypal';
        this.formcode			= 'sagepayform';
        this.code               = '';
        this.oldUrl             = '';
        this._mobile            = sagePayIsMobile();
        this.customckout = null;
        if(this._mobile) {
            Position.prepare();
        }
        this.oldUrl = this.getConfig('review').saveUrl;
        this.getConfig('review').saveUrl = SuiteConfig.getConfig('global', 'sgps_saveorder_url');
        window._sagepayonepageFormId = "onestep_form";
    },
    evalTransport: function(transport){
        try {
            response = eval('('+transport.responseText+')')
        } catch(e) {
            response = {}
        }
        return response;
    },
    getConfig: function(instance){
        return (typeof this.config[instance] != 'undefined' ? this.config[instance] : false);
    },
    getCurrentCheckoutStep: function(){
        return this.getConfig('checkout').accordion.currentSection;
    },
    getShippingMethodSubmit: function(){
        var elements 	= $$("#opc-shipping_method [onclick]");
        for(var i=0; i<elements.length; i++) {
            var attrubutes = [elements[i].readAttribute('onclick'), elements[i].getAttribute('onclick')];
            for(var j=0; j<attrubutes.length; j++) {
                if(Object.isString(attrubutes[j]) && -1 !== attrubutes[j].search(/shippingMethod\.save/)) {
                    return elements[i];
                }
            }
        }
        return false;
    },
    getPaymentMethod: function(){

        var form = null;

        if(form === null){
            return SageServer.code;
        }
        return SageServer.code;
    },
    isFormPaymentMethod: function(){
        return (this.getPaymentMethod() === this.formcode);
    },
    isServerPaymentMethod: function(){
        return (this.getPaymentMethod() === this.servercode || ($('suite_ms_payment_method') && $('suite_ms_payment_method').getValue()==this.servercode));
    },
    isDirectPaymentMethod: function(){
        return (this.getPaymentMethod() === this.directcode);
    },
    isSagePay: function(){
        var isSagePay = false;
        if( (this.getPaymentMethod() === this.formcode) || (this.getPaymentMethod() === this.directcode) ||
            (this.getPaymentMethod() === this.servercode) || (this.getPaymentMethod() === this.paypalcode) ) {
            isSagePay = true;
        }

        return isSagePay;
    },
    growlError: function(msg){
        alert(msg);
        return;
        try{
            var ng = new k.Growler({
                location:"tc"
            });
            ng.error(msg, {
                life:10
            });
        }catch(grwlerror){
            alert(msg);
        }
    },
    growlWarn: function(msg){
        alert(msg);
        return;
        try{
            var ng = new k.Growler({
                location:"tc"
            });
            ng.warn(msg, {
                life:10
            });
        }catch(grwlerror){
            alert(msg);
        }
    },
    isDirectTokenTransaction: function(){
        var tokenRadios = $$('div#payment_form_sagepaydirectpro ul.tokensage li.tokencard-radio input');
        if(tokenRadios.length){
            if(tokenRadios[0].disabled === false){
                return true;
            }
        }
        return false;
    },
    isServerTokenTransaction: function(){
        var tokenRadios = $$('div#payment_form_sagepayserver ul.tokensage li.tokencard-radio input');
        if(tokenRadios.length){
            if(tokenRadios[0].disabled === false){
                return true;
            }
        }
        return false;
    },
    getServerSecuredImage: function(){
        return new Element('img', {
            'src':SuiteConfig.getConfig('server', 'secured_by_image'),
            'style':'margin-bottom:5px'
        });
    },
    setShippingMethod: function(){
        try{
            if($('sagepaydirectpro_cc_type')){
                $('sagepaydirectpro_cc_type').selectedIndex = 0;
            }
        }catch(ser){
            alert(ser);
        }
    },
    setPaymentMethod: function(modcompat){

        if(this.getConfig('review')){
            if(!this.isSagePay()) {
                this.getConfig('review').saveUrl = this.oldUrl;
            }
            else{
                this.getConfig('review').saveUrl = SuiteConfig.getConfig('global', 'sgps_saveorder_url');
            }
        }

        // Remove Server InCheckout iFrame if exists
        if($('sagepaysuite-server-incheckout-iframe')){
            $('checkout-review-submit').show();
            $('sagepaysuite-server-incheckout-iframe').remove();
        }

    },
    getTokensHtml: function(){

        new Ajax.Updater(('tokencards-payment-' + this.getPaymentMethod()), SuiteConfig.getConfig('global', 'html_paymentmethods_url'), {
            parameters: {
                payment_method: this.getPaymentMethod()
            },
            onComplete:function(){
                if($$('a.addnew').length > 1){
                    $$('a.addnew').each(function(el){
                        if(!el.visible()){
                            el.remove();
                        }
                    })
                }
                toggleNewCard(2);

                if($(window._sagepayonepageFormId) && this.isServerPaymentMethod()){
                    toggleNewCard(1);

                    var tokens = $$('div#payment_form_sagepayserver ul li.tokencard-radio input');
                    if(tokens.length){
                        tokens.each(function(radiob){
                            radiob.disabled = true;
                            radiob.removeAttribute('checked');
                        });
                        tokens.first().writeAttribute('checked', 'checked');
                        tokens.first().disabled = false;
                        $(window._sagepayonepageFormId).submit();
                    }else{
                        this.resetOscLoading();
                    }

                }
            }.bind(this)
        });

    },
    resetOscLoading: function(){
        restoreOscLoad();
    },
    resetMACLoading: function() {
        if(this.getConfig('msform')) {
            submitted = false;
            var step='review';
            Element.hide(step+'-please-wait');
            $(step+'-buttons-container').setStyle({opacity:1});
            $(step+'-buttons-container').descendants().each(function(s) {
                s.disabled = false;
            });
        }
    },
    save: function(){
        var view = this;
        var params = Form.serialize(payment.form);
        if (this.getConfig('review').agreementsForm) {
            params += '&'+Form.serialize(this.getConfig('review').agreementsForm);
        }
        OneStep.trigger("event:sagepay:server:before_post_to_gateway", {});
        params.save = true;

        var request = new Ajax.Request(
            this.getConfig('review').saveUrl,
            {
                method      :   'post',
                parameters  :   params,
                onSuccess   :   this.reviewSave,
                onComplete  :   this.onComplete
            }
        );
    },
    onComplete: function(transport){
        OneStep.trigger("event:sagepay:server:after_post_to_gateway", {});
        if (transport && transport.responseText) {
            try{
                response = eval('(' + transport.responseText + ')');
            }
            catch (e) {
                response = {};
            }
            if (!response.success) {
                alert(response.response_status_detail);
            }
        }
    },
    reviewSave: function(transport){
        OneStep.trigger("event:sagepay:server:after_post_to_gateway", {});
        if((typeof transport) == 'undefined'){
            var transport = {};
        }

        if(typeof window._sagepayprocessingorder == "undefined") {
            window._sagepayprocessingorder = false;
        }

        var response = eval('('+transport.responseText+')');

        console.log(response.response_status);
        if((typeof response.response_status != 'undefined') && response.response_status != 'OK' && response.response_status != 'threed' && response.response_status != 'paypal_redirect'){
            this.resetOscLoading();

            if("REDIRECT_CART" == response.response_status_detail.toString()) {
                setLocation(SuiteConfig.getConfig('global','cart_url'));
                return;
            }

            this.resetMACLoading();

            this.growlWarn(Translator.translate("An error occurred") + ":\n" + response.response_status_detail.toString());
            return;
        }
        if(response.response_status == 'paypal_redirect'){
            setLocation(response.redirect);
            return;
        }

        if(SageServer.getConfig('osc') && response.success && response.response_status == 'OK' && (typeof response.next_url == 'undefined')){
            setLocation(SuiteConfig.getConfig('global','onepage_success_url'));
            return;
        }
        if(!response.redirect || !response.success) {
            SageServer.getConfig('review').nextStep(transport);
            return;
        }
        if(SageServer.isServerPaymentMethod()){
            $('sagepayserver-dummy-link').writeAttribute('href', response.redirect);

            var rbButtons = $('review-buttons-container');

            var lcont = new Element('div',{
                className: 'lcontainer'
            });
            var heit = parseInt(SuiteConfig.getConfig('server','iframe_height'));
            if(Prototype.Browser.IE){
                heit = heit-65;
            }

            var wtype = SuiteConfig.getConfig('server','payment_iframe_position').toString();
            console.log(wtype);
            if(wtype == 'modal'){

                var wm = new Control.Modal('sagepayserver-dummy-link',{
                    className: 'modal',
                    iframe: true,
                    closeOnClick: false,
                    insertRemoteContentAt: lcont,
                    height: SuiteConfig.getConfig('server','iframe_height'),
                    width: SuiteConfig.getConfig('server','iframe_width'),
                    fade: true,
                    afterOpen: function(){
                        if(rbButtons){
                            rbButtons.addClassName('disabled');
                        }
                    },
                    afterClose: function(){
                        if(rbButtons){
                            rbButtons.removeClassName('disabled');
                        }
                    }
                });
                wm.container.insert(lcont);
                wm.container.down().setStyle({
                    'height':heit.toString() + 'px'
                });
                wm.container.down().insert(SageServer.getServerSecuredImage());
                wm.open();

            }else if(wtype == 'incheckout') {

                var iframeId = 'sagepaysuite-server-incheckout-iframe';
                var paymentIframe = new Element('iframe', {
                    'src': response.redirect,
                    'id': iframeId
                });

                if(SageServer.getConfig('osc')){
                    var placeBtn = $('onestepcheckout-place-order');

                    placeBtn.hide();

                    $(window._sagepayonepageFormId).insert( {
                        after:paymentIframe
                    } );
                    $(iframeId).scrollTo();

                }else{

                    if( (typeof $('checkout-review-submit')) == 'undefined' ){
                        var btnsHtml  = $$('div.content.button-set').first();
                    }else{
                        var btnsHtml  = $('checkout-review-submit');
                    }

                    btnsHtml.hide();
                    btnsHtml.insert( {
                        after:paymentIframe
                    } );

                }

            }else if(wtype == 'full_redirect') {
                setLocation(response.redirect);
                return;
            }

        }else if(SageServer.isDirectPaymentMethod() && (typeof response.response_status != 'undefined') && response.response_status == 'threed'){

            $('sagepaydirectpro-dummy-link').writeAttribute('href', response.redirect);

            var lcontdtd = new Element('div',{
                className: 'lcontainer'
            });
            var dtd = new Control.Modal('sagepaydirectpro-dummy-link',{
                className: 'modal sagepaymodal',
                closeOnClick: false,
                insertRemoteContentAt: lcontdtd,
                iframe: true,
                height: SuiteConfig.getConfig('direct','threed_iframe_height'),
                width: SuiteConfig.getConfig('direct','threed_iframe_width'),
                fade: true,
                afterOpen: function(){

                    if(true === Prototype.Browser.IE){
                        var ie_version = parseFloat(navigator.appVersion.split("MSIE")[1]);
                        if(ie_version<8){
                            return;
                        }
                    }

                    try{
                        var daiv = SageServer.container;

                        if($$('.sagepaymodal').length > 1){
                            $$('.sagepaymodal').each(function(elem){
                                if(elem.visible()){
                                    daiv = elem;
                                    throw $break;
                                }
                            });
                        }

                        if(!SageServer._mobile) {
                            daiv.down().down('iframe').insert({
                                before:new Element('div', {
                                    'id':'sage-pay-direct-ddada',
                                    'style':'background:#FFF'
                                }).update(
                                        SuiteConfig.getConfig('direct','threed_after').toString() + SuiteConfig.getConfig('direct','threed_before').toString())
                            });
                        }

                    }catch(er){}

                    if(false === Prototype.Browser.IE) {
                        if(!SageServer._mobile) {
                            daiv.down().down('iframe').setStyle({
                                'height':(parseInt(daiv.down().getHeight())-60)+'px'
                            });
                            daiv.setStyle({
                                'height':(parseInt(daiv.down().getHeight())+57)+'px'
                            });
                        }
                    }
                    else {
                        daiv.down().down('iframe').setStyle({
                            'height':(parseInt(daiv.down().getHeight())+116)+'px'
                        });
                    }

                },
                afterClose: function(){
                    if($('sage-pay-direct-ddada')){
                        $('sage-pay-direct-ddada').remove();
                    }
                    $('sagepaydirectpro-dummy-link').writeAttribute('href', '');
                }
            });

            if(SageServer._mobile) {
                var offset_left = (Position.deltaX + Math.floor((document.viewport.getDimensions().width - parseInt(dtd.container.getDimensions().width)) / 2));
                var ye          = (dtd.container.getDimensions().width <= document.viewport.getDimensions().width) ? ((offset_left != null && offset_left > 0) ? offset_left : 0) : 0;
                dtd.options.position = [ye,0];
            }

            dtd.container.insert(lcontdtd);
            dtd.open();

        }else if(SageServer.isDirectPaymentMethod()){
            new Ajax.Request(SuiteConfig.getConfig('direct','sgps_registertrn_url'),{
                onSuccess:function(f){

                    try{

                        var d=f.responseText.evalJSON();

                        if(d.response_status=="INVALID"||d.response_status=="MALFORMED"||d.response_status=="ERROR"||d.response_status=="FAIL"){
                            SageServer.getConfig('checkout').accordion.openSection('opc-payment');
                            SageServer.growlWarn(Translator.translate("An error occurred") + ":\n" + d.response_status_detail.toString());
                        }else if(d.response_status == 'threed'){
                            $('sagepaydirectpro-dummy-link').writeAttribute('href', d.url);
                        }

                    }catch(alfaEr){
                        SageServer.growlError(f.responseText.toString());
                    }

                }.bind(SageServer)
            });
        }
        else{
            SageServer.getConfig('review').nextStep(transport);
            return;
        }
    }
}