<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Session
{
    const MESSAGE_DOWNLOAD =
        'oktagon-x-connect-for-woocommerce-download';

    const MESSAGE_ERROR =
        'oktagon-x-connect-for-woocommerce-error';

    const MESSAGE_SUCCESS =
        'oktagon-x-connect-for-woocommerce-success';

    const SELECTED_CUSTOM_ZIP_CODE =
        'oktagon-x-connect-for-woocommerce-selected-custom-zip-code';

    const SELECTED_SERVICE_POINTS =
        'oktagon-x-connect-for-woocommerce-selected-service-points';

    const SESSION_NAME =
        'oktagon-x-connect-for-woocommerce';

    const TRANSIENT_EXPIRATION =
        60 * 60; // 1 hour

    private static $trap = [];

    private static $storageReference = null;

    private static $nativeStorageReference;

    private static $useTrap = false;

    private static $transientPath = '';

    public static function clearSelectedCustomZipCode(): void
    {
        self::clearValue(
            self::SELECTED_CUSTOM_ZIP_CODE
        );
    }

    public static function setSelectedCustomZipCode(
        string $customZipCode
    ): bool {
        return self::setValue(
            self::SELECTED_CUSTOM_ZIP_CODE,
            $customZipCode
        );
    }

    public static function setSelectedServicePoint(
        string $packageHashKey,
        string $shippingService,
        array $selectedServicePoint
    ): bool {
        $selectedServicePoints = self::getSelectedServicePoints(
            $packageHashKey
        );
        $selectedServicePoints[$shippingService] = $selectedServicePoint;
        return self::setSubValue(
            self::SELECTED_SERVICE_POINTS,
            $packageHashKey,
            $selectedServicePoints
        );
    }

    public static function clearSelectedServicePoints(
        string $packageHashKey
    ): void {
        self::clearSubValue(
            self::SELECTED_SERVICE_POINTS,
            $packageHashKey
        );
        return;
    }

    public static function getSelectedCustomZipCode(): string
    {
        $customZipCode = self::getValue(
            self::SELECTED_CUSTOM_ZIP_CODE
        );
        return (string) \apply_filters(
            'wc_oktagon_xconnect_session_get_selected_custom_zip_code',
            $customZipCode
        );
    }

    public static function getSelectedServicePoint(
        string $packageHashKey,
        string $shippingService
    ): array {
        $selectedServicePoints = self::getSelectedServicePoints(
            $packageHashKey
        );
        $selectedServicePoint = $selectedServicePoints[$shippingService] ?? [];
        return (array) \apply_filters(
            'wc_oktagon_xconnect_session_get_selected_service_point',
            $selectedServicePoint,
            $packageHashKey,
            $shippingService
        );
    }

    public static function startTrap(): void
    {
        self::$useTrap = true;
        self::$trap = [];
    }

    public static function endTrap(): void
    {
        self::$useTrap = false;
        self::$trap = [];
    }

    public static function hasDownload(): bool
    {
        return self::hasNativeValue(
            self::MESSAGE_DOWNLOAD
        );
    }

    public static function popDownload(): string
    {
        return self::popNativeValue(
            self::MESSAGE_DOWNLOAD
        );
    }

    public static function pushDownload(
        string $url
    ): void {
        self::pushNativeValue(
            self::MESSAGE_DOWNLOAD,
            $url
        );
        return;
    }

    public static function hasError(): bool
    {
        return self::hasNativeValue(
            self::MESSAGE_ERROR
        );
    }

    public static function pushError(
        string $message
    ): void {
        self::pushNativeValue(
            self::MESSAGE_ERROR,
            $message
        );
        return;
    }

    public static function popError(): string
    {
        return self::popNativeValue(
            self::MESSAGE_ERROR
        );
    }

    public static function hasSuccess(): bool
    {
        return self::hasNativeValue(
            self::MESSAGE_SUCCESS
        );
    }

    public static function pushSuccess(
        string $message
    ): void {
        self::pushNativeValue(
            self::MESSAGE_SUCCESS,
            $message
        );
        return;
    }

    public static function popSuccess(): string
    {
        return self::popNativeValue(
            self::MESSAGE_SUCCESS
        );
    }

    public static function getDump(): string
    {
        self::isReady();
        return (!empty(self::$storageReference)
            ? var_export(self::$storageReference, true)
            : '');
    }

    public static function isReady(): bool
    {
        if (self::$useTrap) {
            if (!isset(self::$storageReference)) {
                self::$storageReference =
                    &self::$trap;
            }
            return true;
        }
        if (isset(\WC()->session)) {
            if (!\WC()->session->has_session()) {
                \WC()->session->set_customer_session_cookie(true);
            }
            self::$storageReference =
                (array) \WC()->session->get(self::SESSION_NAME);
            return true;
        }
        return false;
    }

    public static function isNativeReady(): bool
    {
        if (self::$useTrap) {
            if (!isset(self::$nativeStorageReference)) {
                self::$nativeStorageReference =
                    &self::$trap;
            }
            return true;
        }
        if (!isset(self::$nativeStorageReference)) {
            self::$transientPath = self::getTransientPath(
                \wp_get_session_token()
            );
            $data =
                \get_transient(self::$transientPath);
            if (!is_array($data)) {
                $data = [];
            }
            self::$nativeStorageReference = $data;
        }
        return true;
    }

    private static function getTransientPath(
        string $token
    ): string {
        return sprintf(
            '%s_%s',
            $token,
            self::SESSION_NAME
        );
    }

    private static function hasValue(
        string $key
    ): bool {
        return (
            self::isReady()
            && isset(self::$storageReference[$key])
        );
    }

    private static function pushNativeValue(
        string $key,
        string $message
    ): void {
        if (self::isNativeReady()) {
            if (!isset(self::$nativeStorageReference[$key])) {
                self::$nativeStorageReference[$key] = [];
            }
            array_push(
                self::$nativeStorageReference[$key],
                $message
            );
            \set_transient(
                self::$transientPath,
                self::$nativeStorageReference,
                self::TRANSIENT_EXPIRATION
            );
        }
        return;
    }

    private static function popNativeValue(
        string $key
    ): string {
        if (
            self::isNativeReady()
            && isset(self::$nativeStorageReference[$key])
        ) {
            $popped = (string) array_pop(
                self::$nativeStorageReference[$key]
            );
            \set_transient(
                self::$transientPath,
                self::$nativeStorageReference,
                self::TRANSIENT_EXPIRATION
            );
            return $popped;
        }
        return '';
    }

    private static function hasNativeValue(
        string $key
    ): bool {
        return (
            self::isNativeReady()
            && !empty(self::$nativeStorageReference[$key])
        );
    }

    private static function clearSubValue(
        string $key,
        string $packageHashKey
    ): void {
        if (
            self::isReady()
            && self::hasSubValue($key, $packageHashKey)
        ) {
            unset(self::$storageReference[$key][$packageHashKey]);
            if (!self::$useTrap) {
                \WC()->session->set(
                    self::SESSION_NAME,
                    self::$storageReference
                );
            }
        }
    }

    private static function clearValue(
        string $key
    ): void {
        if (
            self::isReady()
            && self::hasValue($key)
        ) {
            unset(self::$storageReference[$key]);
            if (!self::$useTrap) {
                \WC()->session->set(
                    self::SESSION_NAME,
                    self::$storageReference
                );
            }
        }
    }

    private static function setSubValue(
        string $key,
        string $packageHashKey,
        array $value
    ): bool {
        if (self::isReady()) {
            if (!isset(self::$storageReference[$key])) {
                self::$storageReference[$key] = [];
            }
            self::$storageReference[$key][$packageHashKey] =
                $value;
            if (!self::$useTrap) {
                \WC()->session->set(
                    self::SESSION_NAME,
                    self::$storageReference
                );
            }
            return true;
        }
        return false;
    }

    private static function setValue(
        string $key,
        string $value
    ): bool {
        if (self::isReady()) {
            self::$storageReference[$key] = $value;
            if (!self::$useTrap) {
                \WC()->session->set(
                    self::SESSION_NAME,
                    self::$storageReference
                );
            }
            return true;
        }
        return false;
    }

    private static function getSubValue(
        string $key,
        string $packageHashKey
    ): array {
        return
            self::hasSubValue($key, $packageHashKey)
            ? (array) self::$storageReference[$key][$packageHashKey]
            : [];
    }

    private static function getValue(
        string $key
    ): string {
        return self::hasValue($key)
            ? (string) self::$storageReference[$key]
            : '';
    }

    private static function hasSubValue(
        string $key,
        string $packageHashKey
    ): bool {
        return (
            self::isReady()
            && isset(self::$storageReference[$key])
            && isset(self::$storageReference[$key][$packageHashKey])
        );
    }

    private static function getSelectedServicePoints(
        string $packageHashKey
    ): array {
        return self::getSubValue(
            self::SELECTED_SERVICE_POINTS,
            $packageHashKey
        );
    }
}
