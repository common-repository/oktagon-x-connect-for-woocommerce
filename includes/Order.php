<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Order
{
    const META_ADDITIONAL_OPTIONS = 'additional_options';
    const META_API_LIVE_TRANSACTION = 'live_transaction';
    const META_API_DRAFT_TRANSACTION = 'draft_transaction';
    const META_CUSTOMIZE_PARCELS = 'customize_parcels';
    const META_CUSTOM_PARCELS = 'custom_parcels';
    const META_SHIPPING_SERVICE = 'shipping_service';
    const META_SELECTED_SERVICE_POINT = 'selected_service_point';
    const META_SHIPMENT_NUMBER = 'shipment_number';
    const META_SHIPMENT_STATUS = 'shipment_status';
    const META_SHIPPING_LABELS = 'shipping_labels';
    const META_TRACKING_LINKS = 'tracking_links';
    const PREFIX = 'oktagon-x-connect-for-woocommerce_';
    const SHIPMENT_STATUS_DRAFT = 'draft';
    const SHIPMENT_STATUS_LIVE = 'live';
    const SHIPMENT_STATUS_FAILED = 'failed';

    private static $isHposActivated = null;

    private static $hposOrderPointers = [];

    private static $hposOrderIdsToUpdate = [];

    public static function propagateChanges()
    {
        if (
            self::isHposActivated()
            && self::$hposOrderIdsToUpdate
        ) {
            foreach (array_keys(self::$hposOrderIdsToUpdate) as $id) {
                if ($order = self::getHposOrderPointer($id)) {
                    $order->save();
                }
            }
        }
    }

    public static function clearCustomParcels(
        int $orderId,
        int $packageIndex
    ): void {
        self::clearPackageValue(
            $orderId,
            $packageIndex,
            self::META_CUSTOM_PARCELS
        );
    }

    public static function clearShipmentStatus(
        int $orderId,
        int $packageIndex
    ): void {
        self::clearPackageValue(
            $orderId,
            $packageIndex,
            self::META_SHIPMENT_STATUS
        );
    }

    public static function getAdditionalOptions(
        int $orderId,
        int $packageIndex
    ): string {
        $shippingService = self::getPackageValue(
            $orderId,
            $packageIndex,
            self::META_ADDITIONAL_OPTIONS
        );
        return (string) \apply_filters(
            'wc_shipit_order_get_additional_options',
            $shippingService,
            $orderId,
            $packageIndex
        );
    }

    public static function getApiLiveTransaction(
        int $orderId,
        int $packageIndex
    ): string {
        $apiTransaction = self::getPackageValue(
            $orderId,
            $packageIndex,
            self::META_API_LIVE_TRANSACTION
        );
        return (string) \apply_filters(
            'wc_shipit_order_get_api_live_transaction',
            $apiTransaction,
            $orderId,
            $packageIndex
        );
    }

    public static function getApiDraftTransaction(
        int $orderId,
        int $packageIndex
    ): string {
        $apiTransaction = self::getPackageValue(
            $orderId,
            $packageIndex,
            self::META_API_DRAFT_TRANSACTION
        );
        return (string) \apply_filters(
            'wc_shipit_order_get_api_draft_transaction',
            $apiTransaction,
            $orderId,
            $packageIndex
        );
    }

    public static function getCustomizeParcels(
        int $orderId,
        int $packageIndex
    ): string {
        $customizeParcels = self::getPackageValue(
            $orderId,
            $packageIndex,
            self::META_CUSTOMIZE_PARCELS
        );
        return (string) \apply_filters(
            'wc_shipit_order_get_customize_parcels',
            $customizeParcels,
            $orderId,
            $packageIndex
        );
    }

    public static function getCustomParcels(
        int $orderId,
        int $packageIndex
    ): array {
        $customParcels = [];
        if (
            $encodedValue = self::getPackageValue(
                $orderId,
                $packageIndex,
                self::META_CUSTOM_PARCELS
            )
        ) {
            if (
                $decodedValue = json_decode(
                    base64_decode($encodedValue),
                    true
                )
            ) {
                $customParcels = $decodedValue;
            }
        }
        return (array) \apply_filters(
            'wc_shipit_order_get_custom_parcels',
            $customParcels,
            $orderId,
            $packageIndex
        );
    }

    public static function getShippingService(
        int $orderId,
        int $packageIndex
    ): string {
        $shippingService = self::getPackageValue(
            $orderId,
            $packageIndex,
            self::META_SHIPPING_SERVICE
        );
        return (string) \apply_filters(
            'wc_shipit_order_get_shipping_service',
            $shippingService,
            $orderId,
            $packageIndex
        );
    }

    public static function getSelectedServicePoint(
        int $orderId,
        int $packageIndex
    ): array {
        $servicePoint = [];
        if (
            $selectedServicePoint = self::getPackageValue(
                $orderId,
                $packageIndex,
                self::META_SELECTED_SERVICE_POINT
            )
        ) {
            if (
                $decodedServicePoint = json_decode(
                    base64_decode(
                        $selectedServicePoint
                    ),
                    true
                )
            ) {
                $servicePoint = $decodedServicePoint;
            }
        }
        return (array) \apply_filters(
            'wc_shipit_order_get_selected_service_point',
            $servicePoint,
            $orderId,
            $packageIndex
        );
    }

    public static function getShipmentNumber(
        int $orderId,
        int $packageIndex
    ): string {
        $shipmentNumber = self::getPackageValue(
            $orderId,
            $packageIndex,
            self::META_SHIPMENT_NUMBER
        );
        return (string) \apply_filters(
            'wc_shipit_order_get_shipment_number',
            $shipmentNumber,
            $orderId,
            $packageIndex
        );
    }

    public static function getShipmentStatus(
        int $orderId,
        int $packageIndex
    ): string {
        $shipmentStatus = self::getPackageValue(
            $orderId,
            $packageIndex,
            self::META_SHIPMENT_STATUS
        );
        return (string) \apply_filters(
            'wc_shipit_order_get_shipment_status',
            $shipmentStatus,
            $orderId,
            $packageIndex
        );
    }

    public static function getShippingLabels(
        int $orderId,
        int $packageIndex
    ): array {
        $shippingLabels = self::getPackageValues(
            $orderId,
            $packageIndex,
            self::META_SHIPPING_LABELS
        );
        return (array) \apply_filters(
            'wc_shipit_order_get_shipping_labels',
            $shippingLabels,
            $orderId,
            $packageIndex
        );
    }

    public static function getTrackingLinks(
        int $orderId,
        int $packageIndex
    ): array {
        $trackingLinks = self::getPackageValues(
            $orderId,
            $packageIndex,
            self::META_TRACKING_LINKS
        );
        return (array) \apply_filters(
            'wc_shipit_order_get_tracking_links',
            $trackingLinks,
            $orderId,
            $packageIndex
        );
    }

    public static function setAdditionalOptions(
        int $orderId,
        int $packageId,
        string $data
    ): void {
        self::setPackageValue(
            $orderId,
            $packageId,
            self::META_ADDITIONAL_OPTIONS,
            $data
        );
        return;
    }

    public static function setApiDraftTransaction(
        int $orderId,
        int $packageId,
        string $data
    ): void {
        self::setPackageValue(
            $orderId,
            $packageId,
            self::META_API_DRAFT_TRANSACTION,
            $data
        );
        return;
    }

    public static function setApiLiveTransaction(
        int $orderId,
        int $packageId,
        string $data
    ): void {
        self::setPackageValue(
            $orderId,
            $packageId,
            self::META_API_LIVE_TRANSACTION,
            $data
        );
        return;
    }

    public static function setCustomizeParcels(
        int $orderId,
        int $packageId,
        string $data
    ): void {
        self::setPackageValue(
            $orderId,
            $packageId,
            self::META_CUSTOMIZE_PARCELS,
            $data
        );
        return;
    }

    public static function setCustomParcels(
        int $orderId,
        int $packageId,
        array $data
    ): void {
        self::setPackageValue(
            $orderId,
            $packageId,
            self::META_CUSTOM_PARCELS,
            base64_encode(wp_json_encode($data))
        );
        return;
    }

    public static function setShippingService(
        int $orderId,
        int $packageId,
        string $data
    ): void {
        self::setPackageValue(
            $orderId,
            $packageId,
            self::META_SHIPPING_SERVICE,
            $data
        );
        return;
    }

    public static function setSelectedServicePoint(
        int $orderId,
        int $packageId,
        array $data
    ): void {
        self::setPackageValue(
            $orderId,
            $packageId,
            self::META_SELECTED_SERVICE_POINT,
            base64_encode(wp_json_encode($data))
        );
        return;
    }

    public static function setShipmentStatus(
        int $orderId,
        int $packageId,
        string $data
    ): void {
        self::setPackageValue(
            $orderId,
            $packageId,
            self::META_SHIPMENT_STATUS,
            $data
        );
        return;
    }

    public static function setShipmentNumber(
        int $orderId,
        int $packageId,
        string $data
    ): void {
        self::setPackageValue(
            $orderId,
            $packageId,
            self::META_SHIPMENT_NUMBER,
            $data
        );
        return;
    }

    public static function setShippingLabels(
        int $orderId,
        int $packageId,
        array $data
    ): void {
        self::setPackageValues(
            $orderId,
            $packageId,
            self::META_SHIPPING_LABELS,
            $data
        );
        return;
    }

    public static function setTrackingLinks(
        int $orderId,
        int $packageId,
        array $data
    ): void {
        self::setPackageValues(
            $orderId,
            $packageId,
            self::META_TRACKING_LINKS,
            $data
        );
        return;
    }

    private static function getKey(
        string $key
    ): string {
        return sprintf(
            '%s%s',
            self::PREFIX,
            $key
        );
    }

    private static function clearPackageValue(
        int $orderId,
        int $packageIndex,
        string $key
    ): void {
        if (
            self::getPackageValue(
                $orderId,
                $packageIndex,
                $key
            )
        ) {
            $compositeKey = self::getKey(
                $key
            );
            $oldValues = self::getPostMeta(
                $orderId,
                $compositeKey
            );
            if (isset($oldValues[$packageIndex])) {
                unset($oldValues[$packageIndex]);
            }
            if (empty($oldValues)) {
                self::deletePostMeta(
                    $orderId,
                    $compositeKey
                );
            } else {
                self::updatePostMeta(
                    $orderId,
                    $compositeKey,
                    $oldValues
                );
            }
        }
        return;
    }

    private static function getPackageValue(
        int $orderId,
        int $packageId,
        string $key
    ): string {
        $compositeKey = self::getKey(
            $key
        );
        $value = self::getPostMeta(
            $orderId,
            $compositeKey
        );
        if (
            !empty($value)
            && is_array($value)
            && isset($value[$packageId])
            && is_string($value[$packageId])
        ) {
            return $value[$packageId];
        }
        return '';
    }

    private static function getPackageValues(
        int $orderId,
        int $packageId,
        string $key
    ): array {
        $compositeKey = self::getKey(
            $key
        );
        $values = self::getPostMeta(
            $orderId,
            $compositeKey
        );
        if (
            !empty($values)
            && is_array($values)
            && isset($values[$packageId])
            && is_array($values[$packageId])
        ) {
            return $values[$packageId];
        }
        return [];
    }

    private static function setPackageValue(
        int $orderId,
        int $packageId,
        string $key,
        string $value
    ): void {
        $compositeKey = self::getKey(
            $key
        );
        $oldValue = self::getPostMeta(
            $orderId,
            $compositeKey
        );
        if (
            empty($oldValue)
            || !is_array($oldValue)
        ) {
            $oldValue = [];
        }
        $oldValue[$packageId] =
            $value;
        self::updatePostMeta(
            $orderId,
            $compositeKey,
            $oldValue
        );
        return;
    }

    private static function setPackageValues(
        int $orderId,
        int $packageId,
        string $key,
        array $values
    ): void {
        $compositeKey = self::getKey(
            $key
        );
        $oldValues = self::getPostMeta(
            $orderId,
            $compositeKey
        );
        if (
            empty($oldValues)
            || !is_array($oldValues)
        ) {
            $oldValues = [
                $packageId => []
            ];
        }
        $oldValues[$packageId] =
            $values;
        self::updatePostMeta(
            $orderId,
            $compositeKey,
            $oldValues
        );
        return;
    }

    private static function getPostMeta(
        int $id,
        string $key
    ) {
        if (self::isHposActivated()) {
            if ($order = self::getHposOrderPointer($id)) {
                return $order->get_meta($key, true);
            }
        } else {
            return \get_post_meta(
                $id,
                $key,
                true
            );
        }
        return null;
    }

    private static function getHposOrderPointer(
        int $id
    ): ?\WC_Order {
        if (!isset(self::$hposOrderPointers[$id])) {
            if ($order = \wc_get_order($id)) {
                self::$hposOrderPointers[$id] = $order;
            } else {
                self::$hposOrderPointers[$id] = null;
            }
        }
        return self::$hposOrderPointers[$id];
    }

    private static function deletePostMeta(
        int $id,
        string $key
    ) {
        if (self::isHposActivated()) {
            if ($order = self::getHposOrderPointer($id)) {
                $order->delete_meta_data($key);
                self::$hposOrderIdsToUpdate[$id] = true;
            }
        } else {
            \delete_post_meta(
                $id,
                $key
            );
        }
    }

    private static function updatePostMeta(
        int $id,
        string $key,
        $value
    ) {
        if (self::isHposActivated()) {
            if ($order = self::getHposOrderPointer($id)) {
                $order->update_meta_data($key, $value);
                self::$hposOrderIdsToUpdate[$id] = true;
            }
        } else {
            \update_post_meta(
                $id,
                $key,
                $value
            );
        }
    }

    private static function isHposActivatedInStore(): bool
    {
        return \get_option('woocommerce_custom_orders_table_enabled') === 'yes';
    }

    private static function isHposActivated(): bool
    {
        if (self::$isHposActivated === null) {
            self::$isHposActivated = self::isHposActivatedInStore();
        }
        return self::$isHposActivated;
    }
}
