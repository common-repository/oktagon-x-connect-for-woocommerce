<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Woocommerce
{
    public function __construct()
    {
        \add_action(
            'woocommerce_after_shipping_rate',
            [
                $this,
                'afterShippingRate'
            ],
            10,
            2
        );

        // AJAX / xHR backends
        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-frontend-select-pick-up-point',
            [
                $this,
                'ajaxFrontendSelectPickUpPoint'
            ]
        );
        \add_action(
            'wp_ajax_nopriv_oktagon-x-connect-for-woocommerce-frontend-select-pick-up-point',
            [
                $this,
                'ajaxFrontendSelectPickUpPoint'
            ]
        );
        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-frontend-select-custom-zip-code',
            [
                $this,
                'ajaxFrontendSelectCustomZipCode'
            ]
        );
        \add_action(
            'wp_ajax_nopriv_oktagon-x-connect-for-woocommerce-frontend-select-custom-zip-code',
            [
                $this,
                'ajaxFrontendSelectCustomZipCode'
            ]
        );

        \add_action(
            'woocommerce_email_after_order_table',
            [
                $this,
                'emailAfterOrderTable'
            ],
            10,
            3
        );
        \add_action(
            'wp_enqueue_scripts',
            [
                $this,
                'enqueueScripts'
            ]
        );
        \add_action(
            'woocommerce_checkout_order_processed',
            [
                $this,
                'checkoutOrderProcessed'
            ],
            10,
            3
        );
        /** @since Woocommerce 2.6.0 */
        \add_action(
            'woocommerce_shipping_init',
            [
                $this,
                'clearShippingRatesCache'
            ]
        );
        /** @since Woocommerce 2.2 */
        \add_action(
            'woocommerce_update_order',
            [
                $this,
                'orderAutomationTick'
            ]
        );
        /** @since Woocommerce 3.0.0 */
        \add_action(
            'woocommerce_new_order',
            [
                $this,
                'orderAutomationTick'
            ]
        );
    }

    public function orderAutomationTick(
        int $orderId
    ) {
        $automationEnabled = (bool) Meta::getOption(
            'automation_enabled',
            ''
        );
        $automationOrderStatus = Meta::getOption(
            'automation_order_status',
            'wc-processing'
        );
        if (
            $automationEnabled
            && $automationOrderStatus
            && $order = \wc_get_order($orderId)
        ) {
            /* @var \WC_Order $order */
            $orderStatus = 'wc-' . $order->get_status();
            if ($orderStatus === $automationOrderStatus) {
                $shipPackageIndexes = [];
                if (
                    $shipping = $order->get_items('shipping')
                ) {
                    $packageIndex = 0;
                    $packageCount = count($shipping);
                    for ($packageIndex = 0; $packageIndex < $packageCount; $packageIndex++) {
                        $shippingService = Order::getShippingService(
                            $orderId,
                            $packageIndex
                        );
                        $shipmentStatus = Order::getShipmentStatus(
                            $orderId,
                            $packageIndex
                        );
                        if (
                            $shippingService
                            && empty($shipmentStatus)
                        ) {
                            $shipPackageIndexes[] =
                                $packageIndex;
                        }
                    }
                }
                if (
                    empty($shipPackageIndexes)
                    && Order::getShippingService(
                        $orderId,
                        -1
                    )
                ) {
                    $shippingService = Order::getShippingService(
                        $orderId,
                        -1
                    );
                    $shipmentStatus = Order::getShipmentStatus(
                        $orderId,
                        -1
                    );
                    if (
                        $shippingService
                        && empty($shipmentStatus)
                    ) {
                        $shipPackageIndexes[] =
                            -1;
                    }
                }
                if ($shipPackageIndexes) {
                    foreach ($shipPackageIndexes as $shipPackageIndex) {
                        Shipment::processOrderPackageIndex(
                            $orderId,
                            $shipPackageIndex,
                            false,
                            $order
                        );
                    }
                }
            }
        }
    }

    public function clearShippingRatesCache()
    {
        if (isset(\WC()->cart)) {
            // WPML will cause this to be false the first time
            if (\did_action('wp_loaded')) {
                /**
                 * This stuff right here is to prevent a PHP Warning
                 * where WooCommerce expects line_total to be there
                 * but it isn't, it is a race condition happening when added the first item
                 * to a cart.
                 */
                $cartIsShippable = false;
                $cartIsReady = true;
                $cartItems = \WC()->cart->get_cart();
                foreach ($cartItems as $cartItem) {
                    if (
                        !empty($cartItem)
                        && is_array($cartItem)
                        && isset($cartItem['data'])
                        && $cartItem['data']->needs_shipping()
                    ) {
                        $cartIsShippable = true;
                    }
                    if (!isset($cartItem['line_total'])) {
                        $cartIsReady = false;
                        break;
                    }
                }
                if ($cartIsShippable && $cartIsReady) {
                    if ($packages = \WC()->cart->get_shipping_packages()) {
                        foreach (array_keys($packages) as $key) {
                            $shippingSession = "shipping_for_package_$key";
                            unset(\WC()->session->$shippingSession);
                        }
                    }
                }
            } else {
                \add_action(
                    'wp_loaded',
                    [
                        $this,
                        'clearShippingRatesCache'
                    ]
                );
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Exception
     */
    public function checkoutOrderProcessed(
        int $orderId,
        array $postData,
        \WC_Order $order
    ): void {
        Meta::log(
            sprintf(
                'checkoutOrderProcessed(%s), ip: %s, SESSION: %s',
                var_export(func_get_args(), true),
                sanitize_text_field($_SERVER['REMOTE_ADDR']),
                Session::getDump()
            )
        );

        $packages = \WC()->shipping()->get_packages();
        $cart = \WC()->cart->get_cart_contents();

        if ($shipping = $order->get_items('shipping')) {
            $packageIndex = 0;
            foreach ($shipping as $item) {
                if (
                    is_a(
                        $item,
                        '\WC_Order_Item_Shipping'
                    )
                ) {
                    if (
                        strpos(
                            $item->get_method_id(),
                            Meta::METHOD_ID
                        ) === 0
                    ) {
                        $packageHashKey = '';
                        if (isset($packages[$packageIndex])) {
                            $packageHashKey =
                                Meta::getPackageHashKey($packages[$packageIndex]);
                        } elseif (
                            !empty($cart)
                            && is_array($cart)
                        ) {
                            $packageHashKey = implode(
                                ',',
                                array_keys($cart)
                            );
                        }

                        if (!$packageHashKey) {
                            throw new \Exception(
                                sprintf(
                                    'Unexpected missing package data for index: %d in store: %s and cart: %s',
                                    $packageIndex,
                                    var_export($packages, true),
                                    var_export(\WC()->cart->get_cart_contents(), true)
                                )
                            );
                        }

                        $instanceOptions = Meta::getInstanceOptions(
                            (int) $item->get_instance_id()
                        );
                        $shippingService = !empty($instanceOptions)
                            && !empty($instanceOptions['shipping_service'])
                            ? $instanceOptions['shipping_service']
                            : '';
                        if (!empty($shippingService)) {
                            Order::setShippingService(
                                $orderId,
                                $packageIndex,
                                $shippingService
                            );

                            if (
                                !empty($instanceOptions['shipping_service_options'])
                                && is_string($instanceOptions['shipping_service_options'])
                            ) {
                                Order::setAdditionalOptions(
                                    $orderId,
                                    $packageIndex,
                                    $instanceOptions['shipping_service_options']
                                );
                            }

                            if (
                                $selectedServicePoint = Session::getSelectedServicePoint(
                                    $packageHashKey,
                                    $shippingService
                                )
                            ) {
                                Order::setSelectedServicePoint(
                                    $orderId,
                                    $packageIndex,
                                    $selectedServicePoint
                                );
                                Session::clearSelectedServicePoints(
                                    $packageHashKey
                                );
                            }

                            Session::clearSelectedCustomZipCode();
                        }
                    }
                } else {
                    throw new \Exception(
                        sprintf(
                            'Unexpected type: %s',
                            var_export($item, true)
                        )
                    );
                }
                $packageIndex++;
            }
        }
    }

    /**
     * templates/cart/cart-shipping.php will call this after each shipping rate with rate and package-index parameter.
     *
     * @param \WC_Shipping_Rate $rate
     * @param int $packageIndex
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @suppress PhanTypeArraySuspiciousNullable
     */
    public function afterShippingRate(
        \WC_Shipping_Rate $rate,
        int $packageIndex
    ): void {
        if ($rate->get_method_id() === Meta::METHOD_ID) {
            $packages = \WC()->shipping()->get_packages();
            $package = $packages[$packageIndex] ?? [];
            $packageHashKey = Meta::getPackageHashKey($package);
            Meta::log(
                sprintf(
                    esc_html__(
                        'Package hash-key: %s',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    var_export($packageHashKey, true)
                )
            );

            $id = sprintf(
                '%s:%s',
                $rate->get_method_id(),
                $rate->get_instance_id()
            );
            $isSelected = isset(\WC()->session->chosen_shipping_methods[$packageIndex])
                && \WC()->session->chosen_shipping_methods[$packageIndex] === $id;

            $price = 0.;
            $hasCost = (bool) $rate->cost;
            if ($hasCost) {
                if (\WC()->cart->display_prices_including_tax()) {
                    $price = $rate->cost + $rate->get_shipping_tax();
                } else {
                    $price = $rate->cost;
                }
            }

            $instanceOptions =
                Meta::getInstanceOptions($rate->get_instance_id());

            $enabledPickUpPointSelection = !empty($instanceOptions)
                && !empty($instanceOptions['enabled_pick_up_point_selection'])
                && $instanceOptions['enabled_pick_up_point_selection'] !== 'no';

            $shippingService = !empty($instanceOptions)
                && !empty($instanceOptions['shipping_service'])
                ? $instanceOptions['shipping_service']
                : '';
            $pickUpPoints = [];
            $selectedPickUpPoint = [];
            if (
                $shippingService
                && $enabledPickUpPointSelection
            ) {
                $street = $package['destination']['address'];
                $zipCode = $package['destination']['postcode'];
                $country = $package['destination']['country'];

                if (
                    !empty($country)
                    && empty($zipCode)
                    && Session::getSelectedCustomZipCode()
                ) {
                    $zipCode = Session::getSelectedCustomZipCode();
                    Meta::log(
                        sprintf(
                            esc_html__(
                                'Setting destination postcode from session to: %s',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            var_export($zipCode, true)
                        )
                    );
                }

                if (
                    !empty($zipCode)
                    && !empty($country)
                ) {
                    $pickUpPoints = Meta::getPickUpAgentsForSpecificRequest(
                        $shippingService,
                        $country,
                        $street,
                        $zipCode
                    );
                    Meta::log(
                        sprintf(
                            esc_html__(
                                'Pick-up points: %s',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            var_export($pickUpPoints, true)
                        )
                    );
                }

                $sessionSelectedPickUpPoint = Session::getSelectedServicePoint(
                    $packageHashKey,
                    $shippingService
                );
                Meta::log(
                    sprintf(
                        esc_html__(
                            'Session ready: %s',
                            'oktagon-x-connect-for-woocommerce'
                        ),
                        Session::isReady()
                        ? esc_html__('Yes', 'oktagon-x-connect-for-woocommerce')
                        : esc_html__('No', 'oktagon-x-connect-for-woocommerce')
                    )
                );
                Meta::log(
                    sprintf(
                        esc_html__(
                            'Session service point: %s',
                            'oktagon-x-connect-for-woocommerce'
                        ),
                        var_export($sessionSelectedPickUpPoint, true)
                    )
                );

                if (
                    $sessionSelectedPickUpPoint
                    && !empty($sessionSelectedPickUpPoint['id'])
                    && $pickUpPoints
                    && !empty($pickUpPoints[$sessionSelectedPickUpPoint['id']])
                ) {
                    $selectedPickUpPoint = $sessionSelectedPickUpPoint;
                } elseif ($pickUpPoints) {
                    // If no pick-up point is selected select first
                    $firstPickUpPoint = reset($pickUpPoints);
                    $selectedPickUpPoint = [
                        'address' => $firstPickUpPoint['address'],
                        'id' => $firstPickUpPoint['id'],
                        'title' => $firstPickUpPoint['title'],
                    ];
                    if ($isSelected) {
                        Session::setSelectedServicePoint(
                            $packageHashKey,
                            $shippingService,
                            $selectedPickUpPoint
                        );
                        Meta::log(
                            sprintf(
                                esc_html__(
                                    'Updated session agent after shipping rate (%s) for shipping-service (%s): %s',
                                    'oktagon-x-connect-for-woocommerce'
                                ),
                                $packageHashKey,
                                var_export($shippingService, true),
                                var_export($selectedPickUpPoint, true)
                            )
                        );
                    }
                }
            }

            $predefinedLogo = !empty($instanceOptions['predefined_logo'])
                ? $instanceOptions['predefined_logo']
                : '';
            if ($predefinedLogo) {
                $predefinedLogo = sprintf(
                    '%s/%s',
                    \plugins_url(
                        'includes/assets/frontend/img',
                        dirname(__FILE__)
                    ),
                    $predefinedLogo
                );
            }

            $nonce = \wp_create_nonce(
                'wc_oktagon_x_connect_checkout_action'
            );

            static $isFirst = true;
            echo \wp_kses(
                Template::processTemplate(
                    'checkout/after-shipping-rate',
                    [
                        'carrier' => !empty($instanceOptions['carrier'])
                            ? $instanceOptions['carrier']
                            : '',
                        'customLogo' => !empty($instanceOptions)
                            && !empty($instanceOptions['logo'])
                            ? $instanceOptions['logo']
                            : '',
                        'description' => !empty($instanceOptions['description'])
                            ? $instanceOptions['description']
                            : '',
                        'enabledPickUpPointSelection' => $enabledPickUpPointSelection,
                        'id' => $id,
                        'isFirst' => $isFirst,
                        'isSelected' => $isSelected,
                        'nonce' => $nonce,
                        'packageHashKey' => $packageHashKey,
                        'pickUpAgents' => $pickUpPoints,
                        'predefinedLogo' => $predefinedLogo,
                        'price' => $price,
                        'rate' => $rate,
                        'selectedPickUpPoint' => $selectedPickUpPoint,
                        'shippingService' => $shippingService,
                        'title' => $rate->get_label(),
                    ]
                ),
                [
                    'p' => ['class' => [], 'id' => []],
                    'div' => ['class' => [], 'data-nonce' => [], 'id' => [], 'style' => []],
                    'span' => ['class' => [], 'id' => []],
                    'button' => ['class' => [], 'id' => [], 'type' => []],
                    'img' => ['alt' => [], 'class' => [], 'id' => [], 'src' => []],
                    'label' => ['class' => [], 'id' => []],
                    'option' => ['data-address' => [], 'data-title' => [], 'value' => [], 'selected' => []],
                    'select' => ['class' => [], 'data-package' => [], 'data-service' => [], 'id' => []],
                    'strong' => ['class' => [], 'id' => []],
                    'input' => ['checked' => [], 'class' => [], 'id' => [], 'name' => [], 'type' => [], 'value' => []],
                ]
            );
            $isFirst = false;
        }
        return;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    public function emailAfterOrderTable(
        \WC_Order $order,
        bool $sentToAdmin,
        bool $plainText
    ): void {
        /* @codingStandardsIgnoreEnd */
        if ($shipping = $order->get_items('shipping')) {
            $isSinglePackage = count($shipping) === 1;
            $shippingMethodCount = count($shipping);
            for ($packageIndex = 0; $packageIndex < $shippingMethodCount; $packageIndex++) {
                if (
                    $trackingLinks = Order::getTrackingLinks(
                        $order->get_id(),
                        $packageIndex
                    )
                ) {
                    if ($plainText) {
                        if ($isSinglePackage) {
                            printf(
                                "\n\n%s: %s\n\n",
                                esc_html__(
                                    'Click here to track your order shipment.',
                                    'oktagon-x-connect-for-woocommerce'
                                ),
                                esc_html(reset($trackingLinks))
                            );
                        } else {
                            printf(
                                "\n\n%s: %s\n\n",
                                sprintf(
                                    esc_html__(
                                        'Click here to track your shipment for package %d.',
                                        'oktagon-x-connect-for-woocommerce'
                                    ),
                                    \esc_html((string) ($packageIndex + 1))
                                ),
                                esc_html(reset($trackingLinks))
                            );
                        }
                    } else {
                        if ($isSinglePackage) {
                            printf(
                                '<p><a href="%s">%s</a></p>',
                                esc_attr(reset($trackingLinks)),
                                esc_html__(
                                    'Click here to track your order shipment.',
                                    'oktagon-x-connect-for-woocommerce'
                                )
                            );
                        } else {
                            printf(
                                '<p><a href="%s">%s</a></p>',
                                esc_attr(reset($trackingLinks)),
                                sprintf(
                                    esc_html__(
                                        'Click here to track your shipment for package %d.',
                                        'oktagon-x-connect-for-woocommerce'
                                    ),
                                    esc_html((string) ($packageIndex + 1))
                                )
                            );
                        }
                    }
                }
            }
        } else {
            if (
                $trackingLinks = Order::getTrackingLinks(
                    $order->get_id(),
                    -1
                )
            ) {
                if ($plainText) {
                    printf(
                        "\n\n%s: %s\n\n",
                        esc_html__(
                            'Click here to track your order shipment.',
                            'oktagon-x-connect-for-woocommerce'
                        ),
                        esc_html(reset($trackingLinks))
                    );
                } else {
                    printf(
                        '<p><a href="%s">%s</a></p>',
                        \esc_attr(reset($trackingLinks)),
                        esc_html__(
                            'Click here to track your order shipment.',
                            'oktagon-x-connect-for-woocommerce'
                        )
                    );
                }
            }
        }
    }

    public function ajaxFrontendSelectCustomZipCode(): void
    {
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        $zipCode = !empty($_POST['zipCode'])
            ? sanitize_text_field($_POST['zipCode'])
            : '';
        $success = false;
        if (
            \wp_verify_nonce(
                $nonce,
                'wc_oktagon_x_connect_checkout_action'
            )
            && Session::setSelectedCustomZipCode($zipCode)
        ) {
            $success = true;
            Meta::log(
                sprintf(
                    esc_html__(
                        'Setting selected custom zip code: %s',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    var_export($zipCode, true)
                )
            );
        } else {
            Meta::log(
                esc_html__(
                    'Failed to update selected custom zip code',
                    'oktagon-x-connect-for-woocommerce'
                )
            );
        }
        \wp_die(
            \wp_json_encode(
                [
                    'success' => $success,
                    'zipCode' => $zipCode,
                ]
            )
        );
    }

    /**
     * Save updates in selected-option id, agent, addons and fields from widget here.
     */
    public function ajaxFrontendSelectPickUpPoint(): void
    {
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        $package = !empty($_POST['package'])
            ? sanitize_text_field($_POST['package'])
            : '';
        $shippingService = !empty($_POST['shippingService'])
            ? sanitize_text_field($_POST['shippingService'])
            : '';
        $address = !empty($_POST['address'])
            ? sanitize_text_field($_POST['address'])
            : '';
        $id = !empty($_POST['id'])
            ? sanitize_text_field($_POST['id'])
            : '';
        $title = !empty($_POST['title'])
            ? sanitize_text_field($_POST['title'])
            : '';
        $sessionAgent = [
            'address' => $address,
            'id' => $id,
            'title' => $title
        ];
        $success = false;
        if (
            \wp_verify_nonce(
                $nonce,
                'wc_oktagon_x_connect_checkout_action'
            )
            && Session::setSelectedServicePoint(
                $package,
                $shippingService,
                $sessionAgent
            )
        ) {
            $success = true;
            Meta::log(
                sprintf(
                    esc_html__(
                        'Updated session agent via AJAX (%s) for shipping-service (%s): %s',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    $package,
                    $shippingService,
                    var_export($sessionAgent, true)
                )
            );
        } else {
            Meta::log(
                esc_html__(
                    'Failed to update session agent via AJAX',
                    'oktagon-x-connect-for-woocommerce'
                )
            );
        }
        \wp_die(
            \wp_json_encode(
                [
                    'address' => $address,
                    'id' => $id,
                    'package' => $package,
                    'shippingService' => $shippingService,
                    'success' => $success,
                    'title' => $title,
                ]
            )
        );
    }

    public function enqueueScripts(): void
    {
        if (
            \is_checkout()
            || \is_cart()
        ) {
            \wp_register_style(
                'oktagon-x-connect-for-woocommerce-frontend-style',
                \plugins_url(
                    'includes/assets/frontend/css/style.css',
                    dirname(__FILE__)
                ),
                [],
                '1.0.2'
            );
            \wp_enqueue_style(
                'oktagon-x-connect-for-woocommerce-frontend-style'
            );
        }
        if (\is_checkout()) {
            \wp_enqueue_script(
                'oktagon-x-connect-for-woocommerce-frontend-script',
                \plugins_url(
                    'includes/assets/frontend/js/script.js',
                    dirname(__FILE__)
                ),
                ['jquery'],
                '1.0.2'
            );
            \wp_localize_script(
                'oktagon-x-connect-for-woocommerce-frontend-script',
                'oktagon_wc_xconnect_frontend_data',
                [
                    'ajax_url' => (string) \admin_url('admin-ajax.php'),
                    'adjust_order_review_design' => Meta::getOption(
                        'adjust_order_review_design',
                        '1'
                    ),
                    'is_debug' => (bool) Meta::getOption('is_debug'),
                ]
            );
        }
    }

    /**
     * Get a shipping methods full label including price.
     *
     * @see \wc_cart_totals_shipping_method_label()
     */
    public function methodPrice(\WC_Shipping_Rate $method): string
    {
        $label = '';
        if (
            $method->cost >= 0
            && $method->get_method_id() !== 'free_shipping'
        ) {
            if (
                \WC()->cart->display_prices_including_tax()
            ) {
                $tax = array_sum(
                    $method->get_shipping_tax()
                );
                $label .= wc_price(
                    $method->cost + $tax
                );
                if (
                    $tax > 0
                    && !wc_prices_include_tax()
                ) {
                    $label .= ' <small class="tax_label">'
                        . \WC()->countries->inc_tax_or_vat() . '</small>';
                }
            } else {
                $tax = array_sum(
                    $method->get_shipping_tax()
                );
                $label .= \wc_price($method->cost);
                if (
                    $tax > 0
                    && \wc_prices_include_tax()
                ) {
                    $label .= ' <small class="tax_label">'
                        . \WC()->countries->ex_tax_or_vat() . '</small>';
                }
            }
        }
        $label = apply_filters(
            'woocommerce_cart_shipping_method_full_label',
            $label,
            $method
        );
        return (string) $label;
    }
}
