if(typeof MWPinPayments == 'undefined') {
    var MWPinPayments = {};
}
MWPinPayments = Class.create();
MWPinPayments.prototype = {
	initialize: function (publishableKey) {
        if (publishableKey) {
            Pin.setPublishableKey(publishableKey);
        }
    },
    getCardDetails: function () {
        var card = {
            number: $('pinpayments_cc_number').value,
            name: $('pinpayments_cc_name').value,
            expiry_month: $('pinpayments_expiration').value,
            expiry_year: $('pinpayments_expiration_yr').value,
            cvc: $('pinpayments_cc_cid').value,
            address_line1: $('billing:street1').value,
            address_line2: $('billing:street2').value,
            address_city: $('billing:city').value,
            address_state: $('billing:region_id').value,
            address_postcode: $('billing:postcode').value,
            address_country: $('billing:country_id').value
        };
        if (card.address_line1 == '') {
            // workaround for checkout modules which do not populate the address data
            if ($('billing-address-select')) {
                var selectedAddress = $('billing-address-select');
                var addressString = selectedAddress.options[selectedAddress.selectedIndex].innerHTML;
                var addressParts = addressString.split(',');
                card.address_line1 = addressParts[1];
                card.address_city = addressParts[3];
            }
        }
        return card;
    },
    getToken: function (that) {
        if ($('pinpayments_cc_token') && $('pinpayments_cc_token').checked) {
            this.form = that.form;
            this.save = that.save;
            var request = new Ajax.Request(
                '/pinpayments/card/get/',
                {
                    method: 'post',
                    onSuccess: this.handleResponseFromPIN.bind(that),
                    parameters: Form.serialize(that.form),
                    onFailure: checkout.setLoadWaiting(false)
                }
            );
        } else {
            var card = this.getCardDetails();
            try {
                Pin.createToken(card, this.handleResponseFromPIN.bind(that));
            } catch (err) {
                alert(err.message);
            }
        }
    }
    ,
    handleResponseFromPIN: function (response) {
        if (response.responseJSON) {
            var response = response.responseJSON;
        }
        if (response.response) {
            //manipulate the form data
            //insert form elements containing the token data
            var currentForm = $('onestep_form');
            currentForm.insert(new Element('input',
                {
                    name: 'payment[cc_number]',
                    value: response.response.display_number,
                    type: 'hidden'
                }));
            currentForm.insert(new Element('input',
                {
                    name: 'payment[cc_number_enc]',
                    value: response.response.token,
                    type: 'hidden'
                }));
            currentForm.insert(new Element('input',
                {
                    name: 'payment[cc_type]',
                    value: response.response.scheme,
                    type: 'hidden'
                }));
            currentForm.insert(new Element('input',
                {
                    name: 'payment[cc_exp_month]',
                    value: response.response.expiry_month,
                    type: 'hidden'
                }));
            currentForm.insert(new Element('input',
                {
                    name: 'payment[cc_exp_year]',
                    value: response.response.expiry_year,
                    type: 'hidden'
                }));
            currentForm.insert(new Element('input',
                {
                    name: 'payment[cc_last4]',
                    value: response.response.display_number,
                    type: 'hidden'
                }));
            try {
                // customer save request
                if ($('pinpayments_cc_save')) {
                    if ($('pinpayments_cc_save').checked) {
                        var request = new Ajax.Request(
                            '/pinpayments/card/save',
                            {
                                method: 'post',
                                parameters: currentForm.serialize(true)
                            }
                        );
                    }
                }
            } catch ($err) {
                // simply ignore as saving card should not break checkout ability
            }

            view_onestep_plugin_pin_payments.save();
        } else {
            var message = 'Unexpected response from gateway.';
            if (response.messages) {
                message = response.error_description + '\n\n';
                response.messages.each(function (pair) {
                    message = message + '\n' + pair.message;
                });
                alert(message);
            } else if (response.error) {
                alert(response.error + '\n' + response.error_description);
            }
        }
    }
}
