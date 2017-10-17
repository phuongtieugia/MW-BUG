window.OneStep = {
    Models: {},
    Collections: {},
    Views: {},
    Plugins: {},
    $: null
};

window.withoutIE = false;

if(navigator.appName.indexOf("Internet Explorer") != -1){

    window.withoutIE = (
        navigator.appVersion.indexOf("MSIE 6") > -1 ||
            navigator.appVersion.indexOf("MSIE 7") > -1 ||
            navigator.appVersion.indexOf("MSIE 8") > -1 ||
            navigator.appVersion.indexOf("MSIE 9") > -1
        );
}