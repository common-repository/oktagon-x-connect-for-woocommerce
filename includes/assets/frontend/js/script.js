if (typeof(jQuery) !== 'undefined') {
    /* global jQuery, setTimeout, clearTimeout */
    (function($) {
        "use strict";
        $(document).ready(function() {
            if (typeof(oktagon_wc_xconnect_frontend_data) !== 'undefined') {
                /* global oktagon_wc_xconnect_frontend_data */

                var oktagon_wc_xconnect_frontend_select_pick_up_point_request = null;

                var oktagon_wc_xconnect_frontend_select_custom_zip_code_request = null;

                /**
                 * @param {Function} callback
                 */
                function oktagon_wc_xconnect_frontend_debug(callback) {
                    if (oktagon_wc_xconnect_frontend_data.is_debug === '1') {
                        callback();
                    }
                }

                /**
                 * @param {String} nonce
                 * @param {String} zipCode
                 */
                function oktagon_wc_xconnect_frontend_select_custom_zip_code(
                    nonce,
                    zipCode
                ) {
                    if (oktagon_wc_xconnect_frontend_select_custom_zip_code_request) {
                        oktagon_wc_xconnect_frontend_select_custom_zip_code_request.abort();
                    }
                    oktagon_wc_xconnect_frontend_select_custom_zip_code_request = $.ajax({
                        data: {
                            action: 'oktagon-x-connect-for-woocommerce-frontend-select-custom-zip-code',
                            nonce: nonce,
                            zipCode: zipCode
                        },
                        dataType: 'json',
                        error: function(response) {
                            console.log('ajax error response');
                            console.log(response);
                        },
                        success: function(response) {
                            oktagon_wc_xconnect_frontend_debug(function() {
                                console.log('ajax response:');
                                console.log(response);
                            });

                            if (
                                typeof(response.success) === 'boolean'
                                && response.success === true
                                && typeof(response.zipCode) === 'string'
                            ) {
                                var newZipCode = response.zipCode;
                                var updated = false;
                                // Set zip-code here and then update checkout if any values changed
                                if ($('#billing_postcode').length) {
                                    var old = $('#billing_postcode').val();
                                    if (old !== newZipCode) {
                                        $('#billing_postcode').val(newZipCode);
                                        updated = true;
                                    }
                                }
                                if (updated) {
                                    $(document.body).trigger('update_checkout');
                                }
                            }
                        },
                        type: 'post',
                        url: oktagon_wc_xconnect_frontend_data.ajax_url
                    });
                }

                /**
                 * @param {String} nonce
                 * @param {String} packageHashKey
                 * @param {String} shippingService
                 * @param {String} address
                 * @param {String} id
                 * @param {String} title
                 * @param {Object} element
                 */
                function oktagon_wc_xconnect_frontend_select_pick_up_point(
                    nonce,
                    packageHashKey,
                    shippingService,
                    address,
                    id,
                    title,
                    element
                ) {
                    if (oktagon_wc_xconnect_frontend_select_pick_up_point_request) {
                        oktagon_wc_xconnect_frontend_select_pick_up_point_request.abort();
                    }
                    oktagon_wc_xconnect_frontend_select_pick_up_point_request = $.ajax({
                        data: {
                            action: 'oktagon-x-connect-for-woocommerce-frontend-select-pick-up-point',
                            address: address,
                            id: id,
                            nonce: nonce,
                            package: packageHashKey,
                            shippingService: shippingService,
                            title: title
                        },
                        dataType: 'json',
                        error: function(response) {
                            console.log('ajax error response');
                            console.log(response);
                        },
                        success: function(response) {
                            oktagon_wc_xconnect_frontend_debug(function() {
                                console.log('ajax response:');
                                console.log(response);
                            });
                            $(
                                '.xconnect-checkout-chosen-agent',
                                $(element).parent().parent()
                            ).text(response.title);
                            $(
                                '.xconnect-checkout-chosen-agent',
                                $(element).parent().parent()
                            ).attr('data-id', response.id);
                            $(
                                '.xconnect-checkout-chosen-agent-description',
                                $(element).parent().parent()
                            ).text(response.address);
                            $(element).val('');
                        },
                        type: 'post',
                        url: oktagon_wc_xconnect_frontend_data.ajax_url
                    });
                }

                oktagon_wc_xconnect_frontend_debug(function() {
                    console.log('Oktagon WooCommerce X-Connect Configuration:');
                    console.log(oktagon_wc_xconnect_frontend_data);
                });

                // Clicking on a not selected shipping method
                $('.woocommerce').on('click', '.xconnect-checkout-option-not-selected', function(event) {
                    $('input.shipping_method', $(this).parent()).trigger('click');
                });

                // Selecting a pick up point
                $('.woocommerce').on('change', '.xconnect-checkout-option-select', function(event) {
                    var nonce = $(this).parents('.xconnect-checkout-option').first().attr('data-nonce');
                    var shippingService = $(this).attr('data-service');
                    var packageHashKey = $(this).attr('data-package');
                    var selectedOption = $('option:selected', this);
                    var address = $(selectedOption).attr('data-address');
                    var id = $(selectedOption).val();
                    var title = $(selectedOption).attr('data-title');
                    if (id !== '') {
                        oktagon_wc_xconnect_frontend_debug(function() {
                            console.log('Selected pick up point:');
                            console.log(id);
                        });
                        oktagon_wc_xconnect_frontend_select_pick_up_point(
                            nonce,
                            packageHashKey,
                            shippingService,
                            address,
                            id,
                            title,
                            this
                        );
                    }
                });

                // Searching a zip-code in the zip-code selector
                $('.woocommerce').on('keypress', '.xconnect-checkout-search-input', function(event) {
                    var code = event.keyCode || event.which;
                    if (code == 13) {
                        event.preventDefault();
                        var nonce = $('.xconnect-checkout-option').attr('data-nonce');
                        var zipCode = $(this).val();
                        if (zipCode !== '') {
                            oktagon_wc_xconnect_frontend_select_custom_zip_code(
                                nonce,
                                zipCode
                            );
                        }
                    }
                });
                $('.woocommerce').on('click', '.xconnect-checkout-search-input-button', function(event) {
                    event.preventDefault();
                    var nonce = $('.xconnect-checkout-option').attr('data-nonce');
                    var zipCode = $('.woocommerce .xconnect-checkout-search-input').val();
                    if (zipCode !== '') {
                        oktagon_wc_xconnect_frontend_select_custom_zip_code(
                            nonce,
                            zipCode
                        );
                    }
                });

                $('body').addClass('oktagon-x-connect-for-woocommerce-frontend');

                if (oktagon_wc_xconnect_frontend_data.adjust_order_review_design) {
                    $('body').addClass('oktagon-x-connect-for-woocommerce-frontend-do-adjust-order-review');
                } else {
                    $('body').addClass('oktagon-x-connect-for-woocommerce-frontend-do-not-adjust-order-review');
                }
            }
        });
    })(jQuery);
}
