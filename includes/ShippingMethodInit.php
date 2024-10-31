<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class ShippingMethodInit
{
    public function __construct()
    {
        /** @since WooCommerce 2.6.0 */
        \add_filter(
            'woocommerce_shipping_methods',
            [
                $this,
                'add'
            ]
        );
    }
    public function add(array $methods): array
    {
        $methods[Meta::METHOD_ID] =
            '\\Oktagon\\WooCommerce\\XConnect\\ShippingMethod';
        return $methods;
    }
}
