<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

/**
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class ShippingMethod extends \WC_Shipping_Method
{
    public $cost = 0.0;

    public $description = '';

    public $enabled = 'yes';

    public $fee_cost = 0.0;

    public $id = Meta::METHOD_ID;

    public $instance_id = 0;

    public $instance_form_fields = [];

    public $method_title = 'X-Connect';

    public $method_description = 'Shipping method with connection to a shipping partner.';

    public $supports = [
        'instance-settings',
        'shipping-zones',
    ];

    public $tax_status = 'taxable';

    public $title;

    /**
     * phpcs:disable Generic.Files.LineLength.TooLong
     */
    public function __construct(
        int $instanceId = 0,
        array $settings = null
    ) {
        $this->method_description = esc_html__(
            'Shipping method with connection to a shipping partner.',
            'oktagon-x-connect-for-woocommerce'
        );
        $this->instance_id = absint($instanceId);
        $this->instance_form_fields = [
            'title' => [
                'title' => esc_html__('Title', 'oktagon-x-connect-for-woocommerce'),
                'type' => 'text',
                'description' => esc_html__(
                    'Will be displayed to customer in checkout.',
                    'oktagon-x-connect-for-woocommerce'
                ),
                'default' => (string) esc_html__('X-Connect Shipping Method', 'oktagon-x-connect-for-woocommerce'),
                'desc_tip' => false,
            ],
            'description' => [
                'title' => esc_html__('Description', 'oktagon-x-connect-for-woocommerce'),
                'type' => 'text',
                'description' => esc_html__(
                    'Will be displayed to customer in checkout.',
                    'oktagon-x-connect-for-woocommerce'
                ),
                'default' => '',
                'desc_tip' => false,
            ],
            'cost' => [
                'description' =>
                    esc_html__(
                        'Enter a cost (excl. tax) or sum, e.g. '
                        . '10.00 * [qty].',
                        'oktagon-x-connect-for-woocommerce'
                    )
                    . '<br/><br/>'
                    . esc_html__(
                        'Use [qty] for the number of items, '
                        . '<code>[cost]</code> for the total cost of items, '
                        . 'and [fee percent="10" min_fee="20" max_fee=""] '
                        . 'for percentage based fees.',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                'desc_tip' => true,
                'title' => esc_html__('Cost', 'oktagon-x-connect-for-woocommerce'),
                'type' => 'text',
            ],
            'tax_status' => [
                'title' => esc_html__('Tax status', 'oktagon-x-connect-for-woocommerce'),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default' => 'taxable',
                'options' => [
                    'taxable' => esc_html__('Taxable', 'oktagon-x-connect-for-woocommerce'),
                    'none' => _x('None', 'Tax status', 'oktagon-x-connect-for-woocommerce'),
                ],
            ],
            'shipping_service' => [
                'default' => '',
                'title' => esc_html__('Shipping Service', 'oktagon-x-connect-for-woocommerce'),
                'type' => 'hidden',
            ],
            'shipping_service_options' => [
                'default' => '',
                'title' => esc_html__('Additional options', 'oktagon-x-connect-for-woocommerce'),
                'type' => 'hidden',
            ],
            'enabled_pick_up_point_selection' => [
                'default' => '',
                'title' => esc_html__('Enable service point selection', 'oktagon-x-connect-for-woocommerce'),
                'type' => 'hidden',
            ],
            'logo' => [
                'description' => esc_html__(
                    'If you leave this field empty the default logotype below will be used.',
                    'oktagon-x-connect-for-woocommerce'
                ),
                'default' => '',
                'title' => esc_html__('Custom URL to logotype', 'oktagon-x-connect-for-woocommerce'),
                'type' => 'text',
            ],
            'predefined_logo' => [
                'class' => 'wc-enhanced-select',
                'description' => esc_html__(
                    'Select a logotype that is built-in in the plugin.',
                    'oktagon-x-connect-for-woocommerce'
                ),
                'default' => '',
                'options' => [
                    '' => esc_html__('None', 'oktagon-x-connect-for-woocommerce'),
                    'airmee.svg' => 'Airmee',
                    'bring.jpg' => 'Bring',
                    'budbee.svg' => 'Budbee',
                    'dbschenker.jpg' => 'DBSchenker',
                    'dhl.svg' => 'DHL',
                    'dpd.jpg' => 'DPD',
                    'fedex.jpg' => 'FedEx',
                    'fiuge.jpg' => 'Fiuge',
                    'gls.jpg' => 'GLS',
                    'instabox.png' => 'Instabox',
                    'itella.jpg' => 'Itella',
                    'jetpak.jpg' => 'Jetpak',
                    'matkahuolto.jpg' => 'Matkahuolto',
                    'netlux.jpg' => 'Netlux',
                    'omniva.jpg' => 'Omniva',
                    'posti.jpg' => 'Posti',
                    'postnord.svg' => 'PostNord',
                    'ups.jpg' => 'UPS',
                    'wolt.jpg' => 'Wolt',
                ],
                'title' => esc_html__('Default Logotype', 'oktagon-x-connect-for-woocommerce'),
                'type' => 'select',
            ],
            'carrier' => [
                'default' => '',
                'title' => esc_html__('Carrier', 'oktagon-x-connect-for-woocommerce'),
                'type' => 'hidden',
            ],
            'logic' => [
                'description' => esc_html__(
                    'Use this to limit when a shipping method is available. Use $weight for cart weight and $subtotal for cart subtotal before discounts and $subtotalPostDiscounts for cart subtotal after discounts. Allowed operators are: &lt;, &lt;=, &gt;, &gt;=, =, &lt;&gt;, &amp;, |. Example weight above 200: $weight &gt; 200. Example weight above or equal 100 or subtotal above 500: $weight &gt;= 100 | $subtotal &gt; 500. Example weight and subtotal after discounts is below 100: $weight &lt; 100 &amp; $subtotalPostDiscounts &lt; 100',
                    'oktagon-x-connect-for-woocommerce'
                ),
                'title' => esc_html__(
                    'Logic',
                    'oktagon-x-connect-for-woocommerce'
                ),
                'type' => 'text',
            ],
        ];
        parent::init_form_fields();
        parent::init_settings();
        parent::init_instance_settings();
        $this->cost =
            $this->instance_settings['cost'];
        $this->title =
            $this->instance_settings['title'];
        $this->tax_status =
            $this->instance_settings['tax_status'];
        if (
            !empty($settings)
            && is_array($settings)
        ) {
            $this->enabled = $settings['enabled'];
        }
    }

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /**
     * @param array $package
     * @return bool
     * @since Woocommerce 3.0.0
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function is_available($package)
    {
        Meta::log(
            sprintf(
                esc_html__(
                    '%s args: %s',
                    'oktagon-x-connect-for-woocommerce'
                ),
                __METHOD__,
                var_export(func_get_args(), true)
            )
        );
        $available = false;
        if ($this->enabled === 'yes') {
            $available = true;
        }

        $logic = $this->instance_settings['logic'];
        if (
            !empty($logic)
            && $available
        ) {
            $logic = str_replace(
                [
                    '&gt;',
                    '&lt;',
                    '&amp;',
                ],
                [
                    '>',
                    '<',
                    '&',
                ],
                $logic
            );

            // Calculate arguments
            $weight = 0.0;
            $subtotal = 0.0;
            $subtotalPostDiscounts = 0.0;
            if (!empty($package['contents'])) {
                foreach ($package['contents'] as $item) {
                    if (isset($item['line_subtotal'])) {
                        $subtotal += (float) $item['line_subtotal'];
                    }
                    if (isset($item['line_subtotal_tax'])) {
                        $subtotal += (float) $item['line_subtotal_tax'];
                    }
                    if (!empty($item['line_total'])) {
                        $subtotalPostDiscounts += (float) $item['line_total'];
                    }
                    if (!empty($item['line_tax'])) {
                        $subtotalPostDiscounts += (float) $item['line_tax'];
                    }
                    $weight +=
                        (float) $item['data']->get_weight() * (int) $item['quantity'];
                }
            }

            Meta::log(
                sprintf(
                    esc_html__(
                        'Cart logic args: %s',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    var_export(
                        [
                            'subtotal' => $subtotal,
                            'subtotalPostDiscounts' => $subtotalPostDiscounts,
                            'weight' => $weight,
                        ],
                        true
                    )
                )
            );

            $parser = new Parser($logic);
            $available = $parser->evaluate(
                $weight,
                $subtotal,
                $subtotalPostDiscounts
            );
        }

        $available = (bool) apply_filters(
            'woocommerce_shipping_' . $this->id . '_is_available',
            $available,
            $package
        );

        return $available;
    }
    // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    /**
     * Based on WooCommerce Flat Rate shipping method.
     * @param array $package
     * @return void
     * @see \WC_Shipping_Flat_Rate->calculate_shipping()
     * @since WooCommerce 3.0.0, Wordpress 3.1.0
     * @see $package defined in \WC_Cart->get_shipping_packages()
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     */
    public function calculate_shipping(
        $package = []
    ) {
        Meta::log(
            sprintf(
                esc_html__(
                    '%s args: %s',
                    'oktagon-x-connect-for-woocommerce'
                ),
                __METHOD__,
                var_export(func_get_args(), true)
            )
        );

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

        $rate = [
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => $this->cost,
            'package' => $package,
        ];

        // Support dynamic costs
        $rate['cost'] = $this->evaluateCost(
            $rate['cost'],
            [
                'cost' => $package['contents_cost'],
                'qty'  => $this->getPackageItemQty($package),
            ]
        );

        // Support free-shipping coupons
        $hasFreeShippingCoupon = false;
        if ($coupons = \WC()->cart->get_coupons()) {
            foreach ($coupons as $coupon) {
                if (
                    $coupon->is_valid()
                    && $coupon->get_free_shipping()
                ) {
                    $hasFreeShippingCoupon = true;
                    break;
                }
            }
        }
        if ($hasFreeShippingCoupon) {
            $rate['cost'] = 0;
            $rate['taxes'] = false;
        }

        \do_action(
            'oktagon-x-connect-for-woocommerce-add-rate',
            $this,
            $rate
        );
        \do_action(
            'woocommerce_' . $this->id . '_shipping_add_rate',
            $this,
            $rate
        );
        $this->add_rate($rate);
        Meta::log(
            sprintf(
                esc_html__(
                    '%s adding shipping rate: %s',
                    'oktagon-x-connect-for-woocommerce'
                ),
                __METHOD__,
                var_export(
                    [
                        'id' => $rate['id'],
                        'label' => $rate['label'],
                        'cost' => $rate['cost'],
                    ],
                    true
                )
            )
        );
        return;
    }
    // phpcs:enable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

    /**
     * Get items in package.
     * Based on WooCommerce Flat Rates shipping method.
     *
     * @see \WC_Shipping_Flat_Rate->get_package_item_qty()
     * @since Woocommerce 3.0.0
     */
    private function getPackageItemQty(array $package): int
    {
        $totalQuantity = 0;
        if (
            !empty($package['contents'])
            && is_array($package['contents'])
        ) {
            foreach ($package['contents'] as $values) {
                if (
                    isset($values['quantity'])
                    && $values['quantity'] > 0
                    && isset($values['data'])
                    && $values['data']->needs_shipping()
                ) {
                    $totalQuantity += (int) round(
                        $values['quantity']
                    );
                }
            }
        }
        return $totalQuantity;
    }

    /**
     * Evaluate a cost from a sum/string.
     * Based on WooCommerce Flat Rate shipping method.
     *
     * @see \WC_Shipping_Flat_Rate->evaluate_cost()
     * @since Woocommerce 3.0.0
     */
    private function evaluateCost(
        string $sum,
        array $args = array()
    ): float {
        require_once(
            \WC()->plugin_path()
            . '/includes/libraries/class-wc-eval-math.php'
        );

        $cost = ($args['cost'] ?? '');
        $qty = ($args['qty'] ?? '');

        // Allow 3rd parties to process shipping cost arguments
        $args = \apply_filters(
            'woocommerce_evaluate_shipping_cost_args',
            $args,
            $sum,
            $this
        );
        $locale = \localeconv();
        /** @since Woocommerce 2.3 */
        $decimals = array(
            \wc_get_price_decimal_separator(),
            $locale['decimal_point'],
            $locale['mon_decimal_point'],
            ','
        );
        $this->fee_cost = $cost;

        // Expand short-codes
        \add_shortcode(
            'fee',
            [$this, 'fee']
        );
        $sum = \do_shortcode(
            str_replace(
                [
                    '[qty]',
                    '[cost]',
                ],
                [
                    $qty,
                    $cost,
                ],
                $sum
            )
        );
        \remove_shortcode(
            'fee'
        );

        // Remove whitespace from string
        $sum = preg_replace(
            '/\s+/',
            '',
            $sum
        );

        // Remove locale from string
        $sum = str_replace(
            $decimals,
            '.',
            $sum
        );

        // Trim invalid start/end characters
        $sum = rtrim(
            ltrim(
                $sum,
                "\t\n\r\0\x0B+*/"
            ),
            "\t\n\r\0\x0B+-*/"
        );

        // Do the math
        return $sum ? (float) \WC_Eval_Math::evaluate($sum) : 0.;
    }

    /**
     * Work out fee (shortcode).
     */
    public function fee(array $atts): float
    {
        $atts = \shortcode_atts(
            [
                'percent' => '',
                'min_fee' => '',
                'max_fee' => '',
            ],
            $atts,
            'fee'
        );

        $calculatedFee = 0;

        if ($atts['percent']) {
            $calculatedFee =
                $this->fee_cost * (floatval($atts['percent']) / 100);
        }

        if (
            $atts['min_fee']
            && $calculatedFee < $atts['min_fee']
        ) {
            $calculatedFee = $atts['min_fee'];
        }

        if (
            $atts['max_fee']
            && $calculatedFee > $atts['max_fee']
        ) {
            $calculatedFee = $atts['max_fee'];
        }

        return $calculatedFee;
    }
}
