/*
* Develop by ANH To - anh.to(@)gmail.com - skype: anh.to87
*
**/
if(typeof RewardPoint=='undefined') {
    var RewardPoint = {
        SellPoint: null,
        EarnPoint: null
    };
}

RewardPoint.SellPoint = Class.create();
RewardPoint.SellPoint.prototype = {
    initialize: function(){

    }
}

RewardPoint.EarnPoint = Class.create();
RewardPoint.EarnPoint.prototype = {
    initialize: function(){
        this.settingsSuper   = $$('.super-attribute-select');
        this.settingsCustom   = $$('.product-custom-option');
        this.settingsBundle   = $$("input[name*=bundle_option\\[], select[name*=bundle_option\\[]");
        // Put events to check select reloads
        this.settingsBundle.each(function(element){
            console.log();
            Event.observe(element, 'change', this.bundle.bind(this));
        }.bind(this));

        this.settingsSuper.each(function(element){
            Event.observe(element, 'change', this.configure.bind(this));
        }.bind(this));

        this.settingsCustom.each(function(element){
            if(['select-multiple', 'select-one'].include(element.type)){}else if(['radio', 'checkbox'].include(element.type)){}
            Event.observe(element, 'change', this.configure.bind(this));
        }.bind(this));

        this.reloadPoint();
        this.bunReloadPoint();
    },
    bundle: function(event){
        var element = Event.element(event);
        this.bunReloadPoint();
    },
    bunReloadPoint: function(){
        var earnPoint = 0;

        this.settingsBundle.each(function(element){
            var optionId = 0;
            element.name.sub(/[0-9]+/, function(match){
                optionId = parseInt(match[0], 10);
            });

            if(typeof bundle != 'undefined' && bundle.config.options[optionId]){
                var configOptions = bundle.config.options[optionId];
                if (element.type == 'checkbox' || element.type == 'radio') {
                    if (element.checked) {
                        if (typeof configOptions.selections[element.getValue()] != 'undefined') {
                            try{
                                if(configOptions.selections[element.getValue()]['earn_point'] != null)
                                    earnPoint += parseInt(configOptions.selections[element.getValue()]['earn_point']);
                            }catch(e){
                                console.log(e);
                            }
                        }
                    }
                }else if(element.type == 'select-one' || element.type == 'select-multiple'){
                    if ('options' in element) {
                        $A(element.options).each(function(selectOption){
                            if ('selected' in selectOption && selectOption.selected) {
                                if (typeof(configOptions.selections[selectOption.value]) != 'undefined') {
                                    try{
                                        if(configOptions.selections[selectOption.value]['earn_point'] != null)
                                            earnPoint += parseInt(configOptions.selections[selectOption.value]['earn_point']);
                                    }catch(e){
                                        console.log(e);
                                    }
                                }
                            }
                        });
                    }
                }
            }
        });

        var pointFixed = 0;
        $$("input[name=mw_inp_earn_point]").each(function(element){
            pointFixed += parseInt(element.value);
        });

        var totalPoints = parseInt(pointFixed) + earnPoint;


        if(Object.isNumber(totalPoints) && totalPoints > 0) {
            $$(".mw_display_point .mw_rewardpoints").each(function(element){
                element.innerText = element.innerText.replace(/([0-9]+)/gi, totalPoints);
            });
            $$(".mw_display_point").each(function(element){
                element.removeClassName('hide');
                element.addClassName('show');
            });
        }
    },
    configure: function(event){
        var element = Event.element(event);
        this.reloadPoint();
    },
    reloadPoint: function(){
        /** global spConfig included config of product configurable
         *  global opConfig included config of product simple
         **/
        var earnPoint = 0;
        this.settingsSuper.each(function(element){
            var optionId = 0;
            element.name.sub(/[0-9]+/, function(match){
                optionId = parseInt(match[0], 10);
            });

            $A(element.options).each(function(selectOption){
                if ('selected' in selectOption && selectOption.selected) {
                    for(var i = 0; i < spConfig.config.attributes[optionId].options.length; i++){
                        if(spConfig.config.attributes[optionId].options[i]['id'] == selectOption.value){
                            if(spConfig.config.attributes[optionId].options[i]['earn_point'] != null){
                                earnPoint += parseInt(spConfig.config.attributes[optionId].options[i]['earn_point']);
                            }
                        }
                    }
                }
            });
        });
        this.settingsCustom.each(function(element){
            var optionId = 0;
            element.name.sub(/[0-9]+/, function(match){
                optionId = parseInt(match[0], 10);
            });
            if (opConfig.config[optionId]) {
                var configOptions = opConfig.config[optionId];
                if (element.type == 'checkbox' || element.type == 'radio') {
                    if (element.checked) {
                        if (typeof configOptions[element.getValue()] != 'undefined') {
                            try{
                                if(configOptions[element.getValue()]['earnPoint'] != null)
                                    earnPoint += parseInt(configOptions[element.getValue()]['earnPoint']);
                            }catch(e){
                                console.log(e);
                            }
                        }
                    }
                }else if(element.type == 'select-one' || element.type == 'select-multiple'){
                    if ('options' in element) {
                        $A(element.options).each(function(selectOption){
                            if ('selected' in selectOption && selectOption.selected) {
                                if (typeof(configOptions[selectOption.value]) != 'undefined') {
                                    try{
                                        if(configOptions[selectOption.value]['earnPoint'] != null)
                                            earnPoint += parseInt(configOptions[selectOption.value]['earnPoint']);
                                    }catch(e){
                                        console.log(e);
                                    }
                                }
                            }
                        });
                    }
                }else{

                }
            }
        });
        var pointFixed = 0;
        $$("input[name=mw_inp_earn_point]").each(function(element){
            pointFixed += parseInt(element.value);
        });

        var totalPoints = parseInt(pointFixed) + earnPoint;


        if(Object.isNumber(totalPoints) && totalPoints > 0) {
            $$(".mw_display_point .mw_rewardpoints").each(function(element){
                element.innerText = element.innerText.replace(/([0-9]+)/gi, totalPoints);
            });
            $$(".mw_display_point").each(function(element){
                element.removeClassName('hide');
                element.addClassName('show');
            });
        }
    }
}

document.ready(function(){
    var mwSellPoint = new RewardPoint.EarnPoint();
});