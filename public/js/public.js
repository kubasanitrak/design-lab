(function ($) {
    'use strict';

    var cfg = window.dlab_public || {};

    $(document).on('click', '[data-dlab-add]', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (this.disabled) {
            return;
        }
        if (cfg.i18n && cfg.i18n.booking_soon) {
            window.alert(cfg.i18n.booking_soon);
        }
    });
})(jQuery);
