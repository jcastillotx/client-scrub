(function($) {
    function notify(message, type) {
        var notice = $('<div class="notice notice-' + type + '"><p>' + message + '</p></div>');
        $('#wpbody-content').prepend(notice);
        setTimeout(function() {
            notice.fadeOut(300, function() { $(this).remove(); });
        }, 4000);
    }

    window.BrandMonitorNotify = {
        success: function(message) { notify(message, 'success'); },
        error: function(message) { notify(message, 'error'); }
    };
})(jQuery);
