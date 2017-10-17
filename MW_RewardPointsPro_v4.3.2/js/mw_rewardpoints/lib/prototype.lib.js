Object.extend(document, {
    isDocReady: false,
    isDocLoaded: false,
    ready: function(fn) { Event.observe(document, "doc:ready", fn); },
    load: function(fn) { Event.observe(document, "doc:loaded", fn); }
});
Event.observe(document, "dom:loaded", function() {
    Event.fire(document, "doc:ready");
    document.isDocReady = true;
    if (document.isDocLoaded)
        Event.fire(document, "doc:loaded");
});
Event.observe(window, "load", function() {
    document.isDocLoaded = true;
    if (!document.isDocReady) return;
    Event.fire(document, "doc:loaded");
});
