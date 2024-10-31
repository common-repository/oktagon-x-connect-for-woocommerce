<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class PackageItem
{
    /**
     * @var int
     */
    private $quantity = 0;

    /**
     * @var \WC_Product
     */
    private $product;

    public function __construct(
        \WC_Product $product,
        int $quantity
    ) {
        $this->product =
            $product;
        $this->quantity =
            $quantity;
    }

    public function getProduct(): \WC_Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}
