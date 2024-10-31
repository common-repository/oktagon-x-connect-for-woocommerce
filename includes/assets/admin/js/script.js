if (typeof(jQuery) !== 'undefined') {
    /* global jQuery ajaxurl parseInt */
    (function($) {
        $(document).ready(function() {

            var wc_oktagon_x_connect_ajax_in_progress = false;

            /**
             * @param <Object> request
             * @param <Function> errorCallback
             * @param <Function> initCallback
             * @param <Function> successCallback
             */
            function wc_oktagon_x_connect_ajax_call(
                request,
                errorCallback,
                initCallback,
                successCallback
            ) {
                if (!wc_oktagon_x_connect_ajax_in_progress) {
                    wc_oktagon_x_connect_ajax_in_progress = true;
                    initCallback();
                    $.ajax({
                        async: true,
                        cache: false,
                        data: request,
                        dataType: 'json',
                        error: function(response) {
                            wc_oktagon_x_connect_ajax_in_progress = false;
                            errorCallback(request, response);
                        },
                        method: 'POST',
                        url: ajaxurl,
                        success: function(response) {
                            wc_oktagon_x_connect_ajax_in_progress = false;
                            successCallback(request, response);
                        }
                    });
                }
            }

            if ($('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service').length) {
                var defaultLocale = 'en';
                var locale = '';
                var shippingServices = {};
                var codeValidator = null;

                $('#woocommerce_oktagon-x-connect-for-woocommerce_logic').change(function() {
                    var parent = $(this).parent();
                    var code = $(this).val();
                    if (codeValidator) {
                        codeValidator.abort();
                    }
                    parent.addClass('oktagon-x-connect-for-woocommerce-ajax-loading');
                    codeValidator = $.ajax({
                        async: true,
                        cache: false,
                        data: {
                            action: 'oktagon-x-connect-for-woocommerce-validate-code',
                            code: code,
                            nonce: $('input#_wpnonce').val()
                        },
                        dataType: 'json',
                        error: function(response) {
                            parent.removeClass('oktagon-x-connect-for-woocommerce-ajax-loading');
                            console.log(response);
                            alert('Failed to validate code! Contact developers of plugin');
                        },
                        method: 'POST',
                        url: ajaxurl,
                        success: function(response) {
                            $('.error-message', parent).remove();
                            parent.removeClass('validation-valid');
                            parent.removeClass('validation-invalid');
                            parent.removeClass('oktagon-x-connect-for-woocommerce-ajax-loading');
                            if (response.valid) {
                                parent.addClass('validation-valid');
                            } else {
                                parent.addClass('validation-invalid');
                                $('#woocommerce_oktagon-x-connect-for-woocommerce_logic').after(
                                    $('<p class="error-message">' + response.error + '</p>')
                                );
                            }
                        }
                    })
                });
                $('#woocommerce_oktagon-x-connect-for-woocommerce_logic').trigger('change');

                // Load services
                $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service').parent().addClass('oktagon-x-connect-for-woocommerce-ajax-loader');
                $.ajax({
                    async: true,
                    cache: false,
                    data: {
                        action: 'oktagon-x-connect-for-woocommerce-get-shipping-options-in-general',
                        nonce: $('input#_wpnonce').val()
                    },
                    dataType: 'json',
                    error: function(response) {
                        $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service').parent().removeClass('oktagon-x-connect-for-woocommerce-ajax-loader');
                        console.log(response);
                        alert('Failed to contact API, verify your credentials!');
                    },
                    method: 'POST',
                    url: ajaxurl,
                    success: function(data) {
                        var locale = data.locale;
                        var carrierShippingServices = data.options;
                        var selected = $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service').val();
                        $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service').parent().removeClass('oktagon-x-connect-for-woocommerce-ajax-loader');
                        var html = '<select ' +
                            'id="woocommerce_oktagon-x-connect-for-woocommerce_shipping_service" ' +
                            'class="select wc-enhanced-select enhanced" ' +
                            'name="woocommerce_oktagon-x-connect-for-woocommerce_shipping_service">' +
                            '<option value=""' +
                            (!selected ? ' selected="selected"' : '') +
                            '>';
                        var shippingServiceKey, shippingOptions;
                        for (shippingServiceKey in carrierShippingServices) {
                            if (carrierShippingServices.hasOwnProperty(shippingServiceKey)) {
                                shippingOptions = carrierShippingServices[shippingServiceKey];
                                var shippingOptionKey, shippingOption, hadOptions = false;
                                var fqShippingServiceKey;
                                for (shippingOptionKey in shippingOptions) {
                                    if (shippingOptions.hasOwnProperty(shippingOptionKey)) {
                                        shippingOption = shippingOptions[shippingOptionKey];
                                        if (
                                            typeof(shippingOption.title) !== 'undefined' &&
                                            typeof(shippingOption.id) !== 'undefined' &&
                                            typeof(shippingOption.carrier) !== 'undefined'
                                        ) {
                                            fqShippingServiceKey = shippingServiceKey + '.' +
                                                shippingOption.id;
                                            shippingServices[fqShippingServiceKey] =
                                                shippingOption;
                                            if (!hadOptions) {
                                                html += '<optgroup label="' + shippingServiceKey + '">';
                                                hadOptions = true;
                                            }
                                            html += '<option ' +
                                                'data-carrier="' + shippingOption.carrier + '" ' +
                                                'value="' + fqShippingServiceKey + '" ' +
                                                (selected === fqShippingServiceKey ? ' selected="selected"' : '') +
                                                '>' + shippingOption.title +
                                                ' (' + shippingOption.id + ')' +
                                                '</option>';
                                        }
                                    }
                                }
                                if (hadOptions) {
                                    html += '</optgroup>';
                                }
                            }
                        }
                        html += '</select>';
                        $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service').replaceWith(html);
                        $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service').change(function() {
                            var service = $('option:selected', this).val();
                            var carrier = $('option:selected', this).attr('data-carrier');
                            $('#woocommerce_oktagon-x-connect-for-woocommerce_carrier').val(carrier);
                            var service = typeof(shippingServices[service]) !== 'undefined'
                                ? shippingServices[service]
                                : false;

                            // Show / hide shipping_service_agent_service depending of it being available or not
                            $('.service-point-enabler').remove();
                            if (service && service.agentService) {
                                $('label[for="woocommerce_oktagon-x-connect-for-woocommerce_enabled_pick_up_point_selection"]').show();
                                var agentServiceLabel = $('label[for="woocommerce_oktagon-x-connect-for-woocommerce_enabled_pick_up_point_selection"]').text();
                                var agentServiceSelected = $('#woocommerce_oktagon-x-connect-for-woocommerce_enabled_pick_up_point_selection').val() !== '';
                                $('#woocommerce_oktagon-x-connect-for-woocommerce_enabled_pick_up_point_selection').before(
                                    $('<label class="service-point-enabler">' +
                                        '<input type="checkbox" value="' + service.agentService + '"' +
                                        (agentServiceSelected ? ' checked="checked"' : '') +
                                        ' />' +
                                        agentServiceLabel +
                                        '</label>'
                                    )
                                );
                                $('.service-point-enabler input').change(function(event) {
                                    if ($(this).is(':checked')) {
                                        $('#woocommerce_oktagon-x-connect-for-woocommerce_enabled_pick_up_point_selection').val(
                                            $(this).val()
                                        );
                                    } else {
                                        $('#woocommerce_oktagon-x-connect-for-woocommerce_enabled_pick_up_point_selection').val('');
                                    }
                                });
                                $('.service-point-enabler input').trigger('change');
                            } else {
                                $('#woocommerce_oktagon-x-connect-for-woocommerce_enabled_pick_up_point_selection').val('');
                                $('label[for="woocommerce_oktagon-x-connect-for-woocommerce_enabled_pick_up_point_selection"]').hide();
                            }

                            // Build shipping_service_options if any options are available
                            $('.service-options-wrapper').remove();
                            if (service && service.options) {
                                $('label[for="woocommerce_oktagon-x-connect-for-woocommerce_shipping_service_options"]').show();
                                // Parse existing values
                                var oldServiceOptions = {};
                                if ($('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service_options').val() !== '') {
                                    oldServiceOptions = JSON.parse(
                                        $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service_options').val()
                                    );
                                }

                                // Build interfaces elements for service options
                                var serviceOptionsHtml = '<div class="service-options-wrapper">';
                                var serviceOptionKey, serviceOption, serviceOptionOptions, serviceOptionOptionKey, serviceOptionLabel, serviceOptionDescription, serviceOptionInputValue;
                                var serviceOptionOptionValue, serviceOptionSelected, serviceOptionOptionsHtml, selectedServiceOptionOptionKey;
                                for (serviceOptionKey in service.options) {
                                    if (service.options.hasOwnProperty(serviceOptionKey)) {
                                        serviceOption = service.options[serviceOptionKey];
                                        if (typeof(serviceOption.type) === 'string') {
                                            serviceOptionLabel = typeof(serviceOption.description.title[locale]) === 'string' ?
                                                serviceOption.description.title[locale] :
                                                serviceOption.description.title[defaultLocale];
                                            serviceOptionLabel = '<strong>' + serviceOptionLabel + '</strong>';
                                            serviceOptionDescription = '';
                                            if (
                                                typeof(serviceOption.description.description[locale]) === 'string'
                                                    && serviceOption.description.description[locale] !== ''
                                            ) {
                                                serviceOptionDescription = serviceOption.description.description[locale];
                                            } else if (
                                                typeof(serviceOption.description.description[defaultLocale]) === 'string'
                                                    && serviceOption.description.description[defaultLocale] !== ''
                                            ) {
                                                serviceOptionDescription = serviceOption.description.description[defaultLocale];
                                            }
                                            if (serviceOptionDescription !== '') {
                                                serviceOptionLabel += '<br /><small>' + serviceOptionDescription + '</small>';
                                            }
                                            if (
                                                serviceOption.type === 'Select' &&
                                                typeof(serviceOption.options) === 'object'
                                            ) {
                                                serviceOptionOptionsHtml = '<select name="' + serviceOptionKey + '">';
                                                selectedServiceOptionOptionKey =
                                                    typeof(oldServiceOptions[serviceOptionKey]) === 'string' ?
                                                    oldServiceOptions[serviceOptionKey] :
                                                    '';
                                                for (serviceOptionOptionKey in serviceOption.options) {
                                                    if (serviceOption.options.hasOwnProperty(serviceOptionOptionKey)) {
                                                        serviceOptionOptionValue =
                                                            serviceOption.options[serviceOptionOptionKey];
                                                        if (typeof(serviceOption.localizedOptions[serviceOptionOptionKey][locale]) === 'string') {
                                                            serviceOptionOptionValue =
                                                                serviceOption.localizedOptions[serviceOptionOptionKey][locale];
                                                        }
                                                        serviceOptionOptionsHtml +=
                                                            '<option value="' + serviceOptionOptionKey + '"' +
                                                            (serviceOptionOptionKey === selectedServiceOptionOptionKey ? ' selected="selected"' : '') +
                                                            '>' + serviceOptionOptionValue +
                                                            '</option>';
                                                    }
                                                }
                                                serviceOptionOptionsHtml += '</select>';
                                                serviceOptionsHtml +=
                                                    '<p><label>' +
                                                    serviceOptionLabel +
                                                    '<br />' + serviceOptionOptionsHtml +
                                                    '</label></p>';
                                            } else if (
                                                serviceOption.type === 'Text'
                                            ) {
                                                serviceOptionInputValue = typeof(oldServiceOptions[serviceOptionKey]) === 'string' &&
                                                    oldServiceOptions[serviceOptionKey] !== ''
                                                    ? oldServiceOptions[serviceOptionKey]
                                                    : '';
                                                serviceOptionsHtml +=
                                                    '<p><label>' +
                                                    serviceOptionLabel +
                                                    '<br /><input type="text" name="' + serviceOptionKey + '" value="' +
                                                    serviceOptionInputValue + '" />' +
                                                    '</label></p>';
                                            } else if (serviceOption.type === 'Checkbox') {
                                                serviceOptionSelected = typeof(oldServiceOptions[serviceOptionKey]) !== 'undefined' &&
                                                    oldServiceOptions[serviceOptionKey];
                                                serviceOptionsHtml +=
                                                    '<p><label>' +
                                                    '<input type="checkbox" name="' + serviceOptionKey + '" value="1"' +
                                                    (serviceOptionSelected ? ' checked="checked"' : '') +
                                                    ' />' +
                                                    serviceOptionLabel +
                                                    '</label></p>';
                                            }
                                        }
                                    }
                                }
                                serviceOptionsHtml += '</div>';
                                $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service_options').before(
                                    $(serviceOptionsHtml)
                                );

                                // Add service options events
                                $('.service-options-wrapper select, .service-options-wrapper input').change(function(event) {
                                    var newServiceOptions = {};
                                    $('.service-options-wrapper select').each(function(i, object) {
                                        var serviceOptionKey = $(this).attr('name');
                                        var serviceOptionValue = $('option:selected', this).val();
                                        newServiceOptions[serviceOptionKey] = serviceOptionValue;
                                    });
                                    $('.service-options-wrapper input').each(function(i, object) {
                                        var serviceOptionKey = $(this).attr('name');
                                        var serviceInputType = $(this).attr('type');
                                        if (serviceInputType === 'checkbox') {
                                            newServiceOptions[serviceOptionKey] = $(this).is(':checked');
                                        } else if (serviceInputType === 'text') {
                                            newServiceOptions[serviceOptionKey] = $(this).val();
                                        }
                                    });
                                    $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service_options').val(
                                        JSON.stringify(newServiceOptions)
                                    );
                                });

                                // Trigger iniitial values
                                $('.service-options-wrapper select').trigger('change');
                                $('.service-options-wrapper input').trigger('change');
                            } else {
                                $('label[for="woocommerce_oktagon-x-connect-for-woocommerce_shipping_service_options"]').hide();
                                $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service_options').val('');
                            }

                        });
                        $('#woocommerce_oktagon-x-connect-for-woocommerce_shipping_service').trigger('change');
                    }
                });
            }

            if ($('.oktagon-x-connect-for-woocommerce-order-wrapper').length) {
                $('.oktagon-x-connect-for-woocommerce-order-wrapper .custom-parcels .customize-weight').change(function(event) {
                    var isChecked = $(this).is(':checked');
                    if (isChecked) {
                        $(this).parent().parent().removeClass('closed').addClass('open');
                    } else {
                        $(this).parent().parent().removeClass('open').addClass('closed');
                    }
                });

                $('.oktagon-x-connect-for-woocommerce-order-wrapper .custom-parcels .add-row').click(function(event) {
                    event.preventDefault();
                    var packageIndex =
                        $(this).parents('.oktagon-x-connect-for-woocommerce-order-wrapper').first().attr('data-package');
                    var parcelIndex =
                        $('.oktagon-x-connect-for-woocommerce-order-wrapper .custom-parcels table > tbody > tr').length;
                    var newRowHtml =
                        '<tr data-package="' + parcelIndex + '">' +
                        '<td><input type="text" name="wc_oktagon_x_connect_custom_parcels[' +
                        packageIndex + '][' + parcelIndex + '][description]" value="" /></td>' +
                        '<td><input type="text" name="wc_oktagon_x_connect_custom_parcels[' +
                        packageIndex + '][' + parcelIndex + '][height]" value="0.00" /></td>' +
                        '<td><input type="text" name="wc_oktagon_x_connect_custom_parcels[' +
                        packageIndex + '][' + parcelIndex + '][length]" value="0.00" /></td>' +
                        '<td><input type="text" name="wc_oktagon_x_connect_custom_parcels[' +
                        packageIndex + '][' + parcelIndex + '][weight]" value="0.00" /></td>' +
                        '<td><input type="text" name="wc_oktagon_x_connect_custom_parcels[' +
                        packageIndex + '][' + parcelIndex + '][width]" value="0.00" /></td>' +
                        '</tr>';
                    $('.oktagon-x-connect-for-woocommerce-order-wrapper .custom-parcels table > tbody').append(
                        $(newRowHtml)
                    );
                });
                $('.oktagon-x-connect-for-woocommerce-order-wrapper .custom-parcels .sub-row').click(function(event) {
                    event.preventDefault();
                    if ($('.oktagon-x-connect-for-woocommerce-order-wrapper .custom-parcels table > tbody > tr').length > 1) {
                        $('.oktagon-x-connect-for-woocommerce-order-wrapper .custom-parcels table > tbody > tr:last-child').remove();
                    }
                });
                $('.oktagon-x-connect-for-woocommerce-order-wrapper .changeable > label > input[type="checkbox"]').click(function(event) {
                    if ($(this).is(':checked')) {
                        $(this).parents('.changeable').removeClass('closed').addClass('open');
                    } else {
                        $(this).parents('.changeable').removeClass('open').addClass('closed');
                    }
                });

                $('a.oktagon-x-connect-for-woocommerce-order-action-button').click(function(event) {
                    event.preventDefault();
                    var parent = $(this).parents('fieldset.oktagon-x-connect-for-woocommerce-order-wrapper').first();
                    var orderId = parseInt($(parent).attr('data-order'));
                    var packageId = parseInt($(parent).attr('data-package'));
                    var nonce = $(parent).attr('data-nonce');
                    if (!orderId) {
                        alert('Missing order, contact developers!');
                        return;
                    }
                    if (!nonce) {
                        alert('Missing nonce, contact developers!');
                        return;
                    }
                    var action = $(this).attr('data-action');
                    if (!action) {
                        alert('Missing action, contact developers!');
                        return;
                    }

                    var request = {
                        action: '',
                        orderId: orderId,
                        packageId: packageId,
                        nonce: nonce
                    };

                    if (action === 'change-additional-options') {
                        request.action =
                            'oktagon-x-connect-for-woocommerce-order-action-change-additional-options';
                        var additionalOptions =
                            {};
                        $('.change-additional-options .change-form input[type="checkbox"]', parent).each(function(i, object) {
                            var key = $(object).attr('data-key');
                            var value = $(object).is(':checked');
                            additionalOptions[key] = value;
                        });
                        $('.change-additional-options .change-form input[type="text"]', parent).each(function(i, object) {
                            var key = $(object).attr('data-key');
                            var value = $(object).val();
                            additionalOptions[key] = value;
                        });
                        $('.change-additional-options .change-form select', parent).each(function(i, object) {
                            var key = $(object).attr('data-key');
                            var value = $(object).find(':selected').val();
                            additionalOptions[key] = value;
                        });
                        request.additionalOptions =
                            additionalOptions;
                    } else if (action === 'change-service') {
                        request.action =
                            'oktagon-x-connect-for-woocommerce-order-action-change-service';
                        request.service =
                            $('dd.change-shipping-service select', parent).find(':selected').val();
                    } else if (action === 'change-service-point') {
                        request.action =
                            'oktagon-x-connect-for-woocommerce-order-action-change-service-point';
                        request.servicePoint =
                            $('dd.change-service-point select > option:selected', parent).val();
                    } else if (action === 'clear-errors') {
                        request.action =
                            'oktagon-x-connect-for-woocommerce-order-action-clear-errors';
                    } else if (action === 'process-package') {
                        request.action =
                            'oktagon-x-connect-for-woocommerce-order-action-process-package';
                    } else if (action === 'save-package') {
                        request.action =
                            'oktagon-x-connect-for-woocommerce-order-action-update-parcels';
                        request.customizeParcels =
                            $('input.customize-weight').is(':checked') ? '1' : '';
                        var packages = [];
                        var prefix = 'input[name="wc_oktagon_x_connect_custom_parcels[' + packageId + ']'
                        $('.custom-parcels table tbody > tr').each(function(i, object) {
                            var packageIndex = parseInt($(object).attr('data-package'));
                            console.log('selector: ' + prefix + '[' + packageIndex + '][description]"]');
                            packages.push({
                                description: $(prefix + '[' + packageIndex + '][description]"]').val(),
                                height: $(prefix + '[' + packageIndex + '][height]"]').val(),
                                length: $(prefix + '[' + packageIndex + '][length]"]').val(),
                                weight: $(prefix + '[' + packageIndex + '][weight]"]').val(),
                                width: $(prefix + '[' + packageIndex + '][width]"]').val()
                            });
                        });
                        request.packages =
                            packages;
                    } else {
                        alert('Invalid action, contact developers!');
                        return;
                    }

                    wc_oktagon_x_connect_ajax_call(
                        request,
                        function(request, jqXHR, textStatus, errorThrown) { // Error
                            console.log(request);
                            console.log(jqXHR);
                            console.log(textStatus);
                            console.log(errorThrown);
                        },
                        function(request, response) { // Init
                            $(parent).addClass('ajax-loader');
                        },
                        function(request, response) { // Success
                            $(parent).removeClass('ajax-loader');
                            if (
                                typeof(response.error) === 'string'
                                && typeof(response.success) === 'boolean'
                            ) {
                                if (response.error === '') {
                                    $(parent).addClass('ajax-loader');
                                    window.location = window.location;
                                } else {
                                    alert('Failed performing action with error: ' + response.error);
                                }
                            } else {
                                alert('Unexpected ajax / xHR response, contact developers!');
                            }
                        }
                    );
                });
            }

            $('a.oktagon-x-connect-for-woocommerce-order-table-action').click(function(event) {
                event.preventDefault();
                var parent = $(this).parents('td').first();
                var orderId = parseInt($(this).attr('data-id'));
                var nonce = $(this).attr('data-nonce');
                if (!orderId) {
                    alert('Missing order, contact developers!');
                    return;
                }
                if (!nonce) {
                    alert('Missing nonce, contact developers!');
                    return;
                }
                var action = $(this).attr('data-action');
                if (!action) {
                    alert('Missing action, contact developers!');
                    return;
                }

                var request = {
                    action: '',
                    orderId: orderId,
                    nonce: nonce
                };

                if (action === 'process') {
                    request.action =
                        'oktagon-x-connect-for-woocommerce-order-action-process-order';
                } else {
                    alert('Invalid action, contact developers!');
                    return;
                }

                wc_oktagon_x_connect_ajax_call(
                    request,
                    function(request, jqXHR, textStatus, errorThrown) { // Error
                        console.log(request);
                        console.log(jqXHR);
                        console.log(textStatus);
                        console.log(errorThrown);
                    },
                    function(request, response) { // Init
                        $(parent).addClass('ajax-loader');
                    },
                    function(request, response) { // Success
                        $(parent).removeClass('ajax-loader');
                        if (
                            typeof(response.error) === 'string'
                            && typeof(response.success) === 'boolean'
                        ) {
                            if (response.error === '') {
                                $(parent).addClass('ajax-loader');
                                window.location = window.location;
                            } else {
                                alert('Failed performing action with error: ' + response.error);
                            }
                        } else {
                            alert('Unexpected ajax / xHR response, contact developers!');
                        }
                    }
                );
            });
        });
    })(jQuery);
}
