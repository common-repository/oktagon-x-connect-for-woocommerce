<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Physics
{
    public static function calculateOrderItems(
        \WC_Order $order
    ): array {
        $packageObject =
            new Package();
        foreach ($order->get_items() as $orderItem) {
            if (
                is_a(
                    $orderItem,
                    '\WC_Order_Item_Product'
                )
            ) {
                $productId =
                    $orderItem->get_variation_id()
                    ? $orderItem->get_variation_id()
                    : $orderItem->get_product_id();
                $product = \wc_get_product($productId);
                if ($product) {
                    if ($product->needs_shipping()) {
                        $quantity =
                            (int) $orderItem->get_quantity();
                        $packageItem = new PackageItem(
                            $product,
                            $quantity
                        );
                        $packageObject->addPackageItem(
                            $packageItem
                        );
                    }
                }
            }
        }
        return [self::estimatePackagePhysics($packageObject)];
    }

    private static function estimatePackagePhysics(
        Package $package
    ): array {
        $toDimensionUnit = 'm';
        $toWeightUnit = 'kg';
        $stackingDimension = Meta::getOption('stacking_dimension');
        $fromWeightUnit = (string) \get_option('woocommerce_weight_unit');
        $fromDimensionUnit = (string) \get_option('woocommerce_dimension_unit');
        $physics = [
            'description' => '',
            'height' => 0.,
            'length' => 0.,
            'volume' => 0.,
            'weight' => 0.,
            'width' => 0.,
        ];

        $packageProducts = [];
        foreach ($package->getPackageItems() as $packageItem) {
            /** @var PackageItem $packageItem */
            $product = $packageItem->getProduct();
            $packageProducts[] = $product;
            $quantity = $packageItem->getQuantity();
            $convertedItemHeight = 0;
            $convertedItemLength = 0;
            $convertedItemWidth = 0;

            // Height
            $itemHeight =
                (float) $product->get_height();
            if ($stackingDimension == 'height') {
                $itemHeight *= $quantity;
            }
            if ($itemHeight) {
                $convertedItemHeight = self::getConvertedDimensionUnitValue(
                    $itemHeight,
                    $fromDimensionUnit,
                    $toDimensionUnit
                );
                if (
                    $convertedItemHeight > $physics['height']
                ) {
                    $physics['height'] = $convertedItemHeight;
                }
            }

            // Length
            $itemLength = (float) $product->get_length();
            if ($stackingDimension == 'length') {
                $itemLength *= $quantity;
            }
            if ($itemLength) {
                $convertedItemLength = self::getConvertedDimensionUnitValue(
                    $itemLength,
                    $fromDimensionUnit,
                    $toDimensionUnit
                );
                if (
                    $convertedItemLength > $physics['length']
                ) {
                    $physics['length'] = $convertedItemLength;
                }
            }

            // Weight
            $itemWeight =
                (float) $product->get_weight();
            $itemWeight *= $quantity;
            if ($itemWeight) {
                $convertedWeight = self::getConvertedWeightUnitValue(
                    $itemWeight,
                    $fromWeightUnit,
                    $toWeightUnit
                );
                $physics['weight'] += $convertedWeight;
            }

            // Width
            $itemWidth =
                (float) $product->get_width();
            if ($stackingDimension == 'width') {
                $itemWidth *= $quantity;
            }
            if ($itemWidth) {
                $convertedItemWidth = self::getConvertedDimensionUnitValue(
                    $itemWidth,
                    $fromDimensionUnit,
                    $toDimensionUnit
                );
                if (
                    $convertedItemWidth > $physics['width']
                ) {
                    $physics['width'] = $convertedItemWidth;
                }
            }

            // Add to volume
            if (
                $convertedItemHeight
                && $convertedItemLength
                && $convertedItemWidth
            ) {
                $physics['volume'] +=
                    $convertedItemHeight
                    * $convertedItemLength
                    * $convertedItemWidth;
            }
        }

        $physics['volume'] = round(
            $physics['height']
            * $physics['length']
            * $physics['width'],
            2
        );

        $minimumPackageWeightString =
            Meta::getOption('minimum_package_weight');
        if ($minimumPackageWeightString !== '') {
            $minimumPackageWeightFloat = floatval(
                str_replace(
                    ',',
                    '.',
                    $minimumPackageWeightString
                )
            );
            if ($physics['weight'] < $minimumPackageWeightFloat) {
                $physics['weight'] = $minimumPackageWeightFloat;
            }
        }

        $physics['description'] = self::getProductsDescription(
            $packageProducts,
            Meta::getOption('package_description')
        );

        return $physics;
    }

    private static function getConvertedDimensionUnitValue(
        float $value,
        string $fromUnit,
        string $toUnit = 'm'
    ): float {
        if ($toUnit === null) {
            $toUnit = 'm';
        }
        $converted = $value;
        if (
            $value !== null
            && !empty($fromUnit)
            && !empty($toUnit)
            && $fromUnit !== $toUnit
        ) {
            if ($toUnit === 'm') {
                if ($fromUnit === 'cm') {
                    $converted = $value / 100;
                } elseif ($fromUnit === 'mm') {
                    $converted = $value / 1000;
                } elseif ($fromUnit === 'in') {
                    $converted = $value / 39.370;
                } elseif ($fromUnit === 'yd') {
                    $converted = $value / 1.0936;
                }
            } elseif ($toUnit === 'cm') {
                if ($fromUnit === 'm') {
                    $converted = $value * 100;
                } elseif ($fromUnit === 'mm') {
                    $converted = $value / 10;
                } elseif ($fromUnit === 'in') {
                    $converted = ($value / 39.370) * 100;
                } elseif ($fromUnit === 'yd') {
                    $converted = ($value / 1.0936) * 100;
                }
            } elseif ($toUnit === 'mm') {
                if ($fromUnit === 'm') {
                    $converted = $value * 1000;
                } elseif ($fromUnit === 'cm') {
                    $converted = $value * 10;
                } elseif ($fromUnit === 'in') {
                    $converted = ($value / 39.370) * 1000;
                } elseif ($fromUnit === 'yd') {
                    $converted = ($value / 1.0936) * 1000;
                }
            } elseif ($toUnit === 'in') {
                if ($fromUnit === 'm') {
                    $converted = $value * 39.370;
                } elseif ($fromUnit === 'cm') {
                    $converted = ($value * 39.370) * 100;
                } elseif ($fromUnit === 'mm') {
                    $converted = ($value * 39.370) * 1000;
                } elseif ($fromUnit === 'yd') {
                    $converted = $value * 36;
                }
            } elseif ($toUnit === 'yd') {
                if ($fromUnit === 'm') {
                    $converted = $value * 1.0936;
                } elseif ($fromUnit === 'cm') {
                    $converted = ($value / 100) * 1.0936;
                } elseif ($fromUnit === 'mm') {
                    $converted = ($value / 1000) * 1.0936;
                } elseif ($fromUnit === 'in') {
                    $converted = $value / 36;
                }
            }
        }
        return $converted;
    }

    private static function getConvertedWeightUnitValue(
        float $value,
        string $fromUnit,
        string $toUnit = 'kg'
    ): float {
        if ($toUnit === null) {
            $toUnit = 'kg';
        }
        $converted = $value;
        if (
            $value !== null
            && !empty($fromUnit)
            && !empty($toUnit)
            && $fromUnit !== $toUnit
        ) {
            if ($toUnit === 'kg') {
                if ($fromUnit === 'g') {
                    $converted = $value / 1000;
                } elseif ($fromUnit === 'lbs') {
                    $converted = $value / 2.204623;
                } elseif ($fromUnit === 'oz') {
                    $converted = $value / 35.27396;
                }
            } elseif ($toUnit === 'g') {
                if ($fromUnit === 'kg') {
                    $converted = $value * 1000;
                } elseif ($fromUnit === 'lbs') {
                    $converted = ($value / 2.204623) * 1000;
                } elseif ($fromUnit === 'oz') {
                    $converted = ($value / 35.27396) * 1000;
                }
            } elseif ($toUnit === 'lbs') {
                if ($fromUnit === 'kg') {
                    $converted = $value * 2.204623;
                } elseif ($fromUnit === 'g') {
                    $converted = ($value * 2.204623) / 1000;
                } elseif ($fromUnit === 'oz') {
                    $converted = $value * 0.0625;
                }
            } elseif ($toUnit === 'oz') {
                if ($fromUnit === 'kg') {
                    $converted = $value * 35.27396;
                } elseif ($fromUnit === 'g') {
                    $converted = ($value / 1000) * 35.27396;
                } elseif ($fromUnit === 'lbs') {
                    $converted = $value / 0.0625;
                }
            }
        }
        return $converted;
    }

    /**
     * @suppress PhanTypeSuspiciousNonTraversableForeach
     */
    private static function getProductsDescription(
        array $products,
        string $option
    ) {
        $description = '';
        if (
            !empty($option)
            && $option !== 'empty'
        ) {
            if ($option === "categories") {
                $categories = [];
                foreach ($products as $product) {
                    /** @var \WC_Product $product */
                    $id = $product->get_id();
                    if (
                        is_a(
                            $product,
                            '\WC_Product_Variation'
                        )
                    ) {
                        $id = $product->get_parent_id();
                    }
                    if (
                        $terms = \get_the_terms(
                            $id,
                            'product_cat'
                        )
                    ) {
                        foreach ($terms as $term) {
                            $productCategory = $term->name;
                            if (!isset($categories[$productCategory])) {
                                $categories[$productCategory] = true;
                            }
                        }
                    }
                }
                if (!empty($categories)) {
                    $description = implode(
                        ', ',
                        array_keys($categories)
                    );
                }
            } elseif ($option === "products") {
                $names = [];
                foreach ($products as $product) {
                    /** @var \WC_Product $product */
                    $productName = $product->get_name();
                    if (!isset($names[$productName])) {
                        $names[$productName] = true;
                    }
                }
                if (!empty($names)) {
                    $description = implode(
                        ', ',
                        array_keys($names)
                    );
                }
            } elseif ($option === 'skus') {
                $skus = [];
                foreach ($products as $product) {
                    /** @var \WC_Product_Simple $product */
                    $productSku = $product->get_sku();
                    if (!isset($skus[$productSku])) {
                        $skus[$productSku] = true;
                    }
                }
                if (!empty($skus)) {
                    $description = implode(
                        ', ',
                        array_keys($skus)
                    );
                }
            }
        }
        return $description;
    }
}
