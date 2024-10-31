if (typeof(jQuery) !== 'undefined') {
    /* global jQuery */
    "use strict";
    (function($) {
        $(document).ready(function() {
            $('#oktagon-x-connect-for-woocommerce-setup-body-wrapper .toggle-service').change(function(event) {
                var isChecked = $(this).is(':checked');
                var parent = $(this).parents('.service').first();
                if (isChecked) {
                    $(parent).removeClass('not-active-service');
                    $(parent).addClass('active-service');
                } else {
                    $(parent).removeClass('active-service');
                    $(parent).addClass('not-active-service');
                }
            });
        });
    })(jQuery);
}
