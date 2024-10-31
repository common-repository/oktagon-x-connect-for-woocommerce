<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Meta
{
    const METHOD_ID = 'oktagon-x-connect-for-woocommerce';

    const ORDER_PROCESSING_CREATE_DRAFT_SHIPMENTS = 'draft';

    const ORDER_PROCESSING_CREATE_LIVE_SHIPMENTS = 'live';

    const UPLOAD_LABEL_PREFIX = 'oktagon-x-connect-for-woocommerce-shipping-labels';

    /**
     * @var Api
     */
    private static $apiConnection;

    /**
     * @var \WC_Logger
     */
    private static $logger;

    /**
     * @var array
     */
    private static $options;

    public static function getHostname(): string
    {
        return (string) \apply_filters(
            'oktagon-x-connect-for-woocommerce-get-hostname',
            self::getWordpressHostname()
        );
    }

    public static function getWordpressHostname(): string
    {
        return parse_url(
            \home_url(),
            \PHP_URL_HOST
        );
    }

    public static function getLicenseAndServiceCacheKey(): string
    {
        static $cacheKey = null;
        if ($cacheKey === null) {
            $cacheKey = sprintf(
                '%s_%s',
                md5(wp_json_encode(self::getLicense())),
                md5(wp_json_encode(self::getServiceCredentials()))
            );
        }
        return $cacheKey;
    }

    public static function getLicense(
        bool $refresh = false
    ): array {
        static $license = null;
        if (
            $refresh
            || $license === null
        ) {
            $license = [
                'domain' =>
                    self::getHostname(),
                'password' =>
                    self::getOption('license_password'),
            ];
        }
        return $license;
    }

    public static function getActivatedServices(
        bool $refresh = false
    ): array {
        static $activatedServices = null;
        if (
            $refresh
            || $activatedServices === null
        ) {
            $activatedServices = [];
            $serviceCredentials = self::getServiceCredentials($refresh);
            $services = self::getServices();
            foreach ($services as $serviceName => $service) {
                $isActiveService = false;
                if (
                    !empty($service['options'])
                    && is_array($service['options'])
                ) {
                    $isActiveService = true;
                    foreach ($service['options'] as $serviceOptionName => $serviceOption) {
                        if (
                            !empty($serviceOption['required'])
                            && (
                                empty($serviceCredentials[$serviceName])
                                || empty($serviceCredentials[$serviceName][$serviceOptionName])
                            )
                        ) {
                            $isActiveService = false;
                            break;
                        }
                    }
                    if ($isActiveService) {
                        $activatedServices[] = $serviceName;
                    }
                }
            }
        }
        return $activatedServices;
    }

    public static function getEnabledServices(): array
    {
        $enabledServices = [];
        $encodedEnabledServices = self::getOption(
            'enabled_services',
            ''
        );
        if ($encodedEnabledServices) {
            try {
                $decodedEnabledServices = json_decode(
                    base64_decode($encodedEnabledServices),
                    true
                );
            } catch (\Exception $e) {
                $decodedEnabledServices = [];
            }
            if (!empty($decodedEnabledServices)) {
                $enabledServices = $decodedEnabledServices;
            }
        }
        return $enabledServices;
    }

    public static function getServiceCredentials(
        bool $refresh = false
    ): array {
        static $serviceCredentials = null;
        if (
            $refresh
            || $serviceCredentials === null
        ) {
            $serviceCredentials = [];
            $encodedServiceCredentials = self::getOption(
                'service_credentials',
                ''
            );
            if ($encodedServiceCredentials) {
                try {
                    $decodedServiceCredentials = json_decode(
                        base64_decode($encodedServiceCredentials),
                        true
                    );
                } catch (\Exception $e) {
                    $decodedServiceCredentials = [];
                }
                if (!empty($decodedServiceCredentials)) {
                    $serviceCredentials = $decodedServiceCredentials;
                } else {
                    try {
                        $decodedServiceCredentials = json_decode(
                            $encodedServiceCredentials,
                            true
                        );
                    } catch (\Exception $e) {
                        $decodedServiceCredentials = [];
                    }
                    if (!empty($decodedServiceCredentials)) {
                        $serviceCredentials = $decodedServiceCredentials;
                    }
                }
            }
        }
        return $serviceCredentials;
    }

    /**
     * @throws \Exception
     */
    public static function getLocale(): string
    {
        // Do we have a WPML language code specified?
        if (
            defined('\ICL_LANGUAGE_CODE')
            && !empty(\ICL_LANGUAGE_CODE)
        ) {
            return (string) \ICL_LANGUAGE_CODE;
        }

        if ($wpLocale = \get_user_locale()) {
            $langCountry = explode(
                '_',
                $wpLocale
            );
            if (
                $langCountry
                && !empty($langCountry[0])
            ) {
                return (string) $langCountry[0];
            }
        }

        throw new \Exception(
            esc_html__(
                'Failed to get system locale!',
                'oktagon-x-connect-for-woocommerce'
            )
        );
    }

    public static function getShippingLabelUrl(
        string $shippingLabel
    ): string {
        $wpUploadDir = \wp_upload_dir();
        if (
            strpos(
                $shippingLabel,
                '/'
            ) === 0
        ) {
            return $wpUploadDir['baseurl'] . $shippingLabel;
        } else {
            return $wpUploadDir['baseurl'] . '/' . $shippingLabel;
        }
    }

    public static function getShippingLabelFilename(
        string $shippingLabel
    ): string {
        $wpUploadDir = \wp_upload_dir();
        if (
            strpos(
                $shippingLabel,
                '/'
            ) === 0
        ) {
            return $wpUploadDir['basedir'] . $shippingLabel;
        } else {
            return $wpUploadDir['basedir'] . '/' . $shippingLabel;
        }
    }

    public static function getShippingOptionsInGeneral(): array
    {
        static $shippingOptionsInGeneral = null;
        if ($shippingOptionsInGeneral === null) {
            $shippingOptionsInGeneral = [];
            $cacheKey = sprintf(
                'shipping_options_in_general_%s',
                self::getLicenseAndServiceCacheKey()
            );
            if (Cache::test($cacheKey)) {
                $shippingOptionsInGeneral = Cache::load($cacheKey);
            } else {
                $activatedServices = self::getActivatedServices();
                foreach ($activatedServices as $serviceName) {
                    $shippingOptionsInGeneralByService = [];
                    try {
                        $transaction = self::getApiConnection()->getShippingOptionsInGeneral(
                            $serviceName
                        );
                        if ($transaction->getResponseStatusCode() === 200) {
                            $shippingOptionsInGeneralByService =
                                $transaction->getResponseBodyDecoded();
                        }
                    } catch (\Exception $e) {
                        $shippingOptionsInGeneralByService = [];
                    }
                    if (!empty($shippingOptionsInGeneralByService)) {
                        $shippingOptionsInGeneral[$serviceName] =
                            $shippingOptionsInGeneralByService;
                    }
                }
                Cache::save(
                    $shippingOptionsInGeneral,
                    $cacheKey,
                    21600
                );
            }
        }
        return $shippingOptionsInGeneral;
    }

    public static function isValidCredentials(array $cacheQuery): array
    {
        $apiConnection = self::getApiConnection(true);
        $cacheKey = sprintf(
            'service_credentials_%s',
            md5(wp_json_encode($cacheQuery))
        );
        $isValidCredentials = [];
        if (Cache::test($cacheKey)) {
            $cached = Cache::load($cacheKey);
            return $cached;
        } else {
            $activatedServices = self::getActivatedServices(true);
            foreach ($activatedServices as $serviceName) {
                try {
                    $transaction = $apiConnection->getShippingOptionsInGeneral(
                        $serviceName
                    );
                    $valid = $transaction->getResponseStatusCode() === 200;
                    self::log(
                        sprintf(
                            esc_html__(
                                'Service %s response: (%s) %s',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            $serviceName,
                            $cacheKey,
                            var_export($transaction, true)
                        )
                    );
                    $isValidCredentials[$serviceName] = $valid ? 1 : -1;
                } catch (\Exception $e) {
                    $isValidCredentials[$serviceName] = -1;
                }
            }
            Cache::save(
                $isValidCredentials,
                $cacheKey
            );
        }
        return $isValidCredentials;
    }

    public static function getServices(): array
    {
        static $services = null;
        if ($services === null) {
            $services = [];
            $cacheKey = sprintf(
                'shipping_services_v2_%s',
                self::getLicenseAndServiceCacheKey()
            );
            if (Cache::test($cacheKey)) {
                $services = Cache::load($cacheKey);
            } else {
                $transaction = self::getApiConnection()->metaGetServices();
                if ($transaction->getResponseStatusCode() === 200) {
                    $rawServices =
                        $transaction->getResponseBodyDecoded();
                    foreach ($rawServices as $serviceName => $serviceData) {
                        if (
                            !empty($serviceName)
                            && is_string($serviceName)
                            && !empty($serviceData)
                            && is_array($serviceData)
                            && !empty($serviceData['features'])
                            && !empty($serviceData['features']['GetShippingOptionsInGeneral'])
                        ) {
                            $services[$serviceName] = $serviceData;
                        }
                    }
                }
                Cache::save(
                    $services,
                    $cacheKey,
                    21600
                );
            }
        }
        return $services;
    }

    /**
     * @throws \Exception
     */
    public static function getApiConnection(
        bool $refresh = false
    ): Api {
        if (
            $refresh
            || !isset(self::$apiConnection)
        ) {
            $license = self::getLicense($refresh);
            try {
                self::$apiConnection = new Api(
                    $license['domain'],
                    $license['password'],
                    self::getServiceCredentials($refresh)
                );
            } catch (Curl\Exceptions\InvalidArgument $e) {
                throw new \Exception(
                    sprintf(
                        esc_html__(
                            'Unexpected implementation error, contact developer of plugin! Errors: %s',
                            'oktagon-x-connect-for-woocommerce'
                        ),
                        $e->getMessage()
                    )
                );
            } catch (Curl\Exceptions\InvalidEnvironment $e) {
                Session::pushError(
                    sprintf(
                        esc_html__(
                            'Your environment is not supported by the extension, error message: %s',
                            'oktagon-x-connect-for-woocommerce'
                        ),
                        $e->getMessage()
                    )
                );
            }
        }
        return self::$apiConnection;
    }

    /**
     * @throws Curl\Exceptions\InvalidArgument
     * @throws Curl\Exceptions\General
     * @throws Curl\Exceptions\Curl
     */
    public static function createOrderPackageDraftShipment(
        \WC_Order $order,
        int $packageIndex,
        bool $notify = false
    ): bool {
        // Generate shipment data
        $serviceName = '';
        $serviceId = '';
        if (
            $shippingService = Order::getShippingService(
                $order->get_id(),
                $packageIndex
            )
        ) {
            $explode = explode('.', $shippingService, 2);
            $serviceName = $explode[0];
            $serviceId = $explode[1];
        }
        $shipmentData = Shipment::generateShipmentData(
            $order,
            $packageIndex
        );
        $shipmentData['serviceId'] = $serviceId;

        $transaction = self::getApiConnection()->createDraftShipment(
            $serviceName,
            $shipmentData
        );
        $transactionId = Transactions::saveTransaction(
            $transaction
        );
        Order::setApiDraftTransaction(
            $order->get_id(),
            $packageIndex,
            (string) $transactionId
        );
        if ($transaction->getResponseStatusCode() === 200) {
            Order::setShipmentStatus(
                $order->get_id(),
                $packageIndex,
                Order::SHIPMENT_STATUS_DRAFT
            );
            $responseBodyDecoded = $transaction->getResponseBodyDecoded();
            if (
                !empty($responseBodyDecoded['trackingLinks'])
                && is_array($responseBodyDecoded['trackingLinks'])
            ) {
                Order::setTrackingLinks(
                    $order->get_id(),
                    $packageIndex,
                    $responseBodyDecoded['trackingLinks']
                );
                return true;
            } else {
                return false;
            }
        } else {
            Order::setShipmentStatus(
                $order->get_id(),
                $packageIndex,
                Order::SHIPMENT_STATUS_FAILED
            );
            if ($notify) {
                $responseBodyDecoded = $transaction->getResponseBodyDecoded();
                if (
                    !empty($responseBodyDecoded)
                    && is_array($responseBodyDecoded)
                    && !empty($responseBodyDecoded['error'])
                    && is_string($responseBodyDecoded['error'])
                ) {
                    Session::pushError(
                        sprintf(
                            esc_html__('Error: %s', 'oktagon-x-connect-for-woocommerce'),
                            $responseBodyDecoded['error']
                        )
                    );
                }
            }
        }

        return false;
    }

    public static function createOrderPackageLiveShipment(
        \WC_Order $order,
        int $packageIndex,
        bool $notify = false
    ): bool {
        // Generate shipment data
        $serviceName = '';
        $serviceId = '';
        if (
            $shippingService = Order::getShippingService(
                $order->get_id(),
                $packageIndex
            )
        ) {
            $explode = explode('.', $shippingService, 2);
            $serviceName = $explode[0];
            $serviceId = $explode[1];
        }
        $shipmentData = Shipment::generateShipmentData(
            $order,
            $packageIndex
        );
        $shipmentData['serviceId'] = $serviceId;
        $transaction = self::getApiConnection()->createLiveShipment(
            $serviceName,
            $shipmentData
        );
        $transactionId = Transactions::saveTransaction(
            $transaction
        );
        Order::setApiLiveTransaction(
            $order->get_id(),
            $packageIndex,
            (string) $transactionId
        );
        if ($transaction->getResponseStatusCode() === 200) {
            Order::setShipmentStatus(
                $order->get_id(),
                $packageIndex,
                Order::SHIPMENT_STATUS_LIVE
            );
            $responseBodyDecoded = $transaction->getResponseBodyDecoded();
            if (
                !empty($responseBodyDecoded['trackingLinks'])
                && is_array($responseBodyDecoded['trackingLinks'])
                && !empty($responseBodyDecoded['shippingLabels'])
                && is_array($responseBodyDecoded['shippingLabels'])
                && !empty($responseBodyDecoded['shipmentNumber'])
            ) {
                Order::setShipmentNumber(
                    $order->get_id(),
                    $packageIndex,
                    $responseBodyDecoded['shipmentNumber']
                );
                $shippingLabels = [];
                foreach ($responseBodyDecoded['shippingLabels'] as $shippingLabelIndex => $base64EncodedShippingLabel) {
                    $pdfBasename = sprintf(
                        '%s/%s-%s_%s-%s-%s.pdf',
                        \wp_upload_dir()['subdir'],
                        self::UPLOAD_LABEL_PREFIX,
                        $order->get_id(),
                        $packageIndex,
                        $shippingLabelIndex + 1,
                        md5(
                            (string) rand(
                                1000,
                                9999
                            )
                        )
                    );
                    $pdfFilename = self::getShippingLabelFilename(
                        $pdfBasename
                    );
                    file_put_contents(
                        $pdfFilename,
                        base64_decode($base64EncodedShippingLabel)
                    );
                    $shippingLabels[] = $pdfBasename;
                }
                Order::setShippingLabels(
                    $order->get_id(),
                    $packageIndex,
                    $shippingLabels
                );
                Order::setTrackingLinks(
                    $order->get_id(),
                    $packageIndex,
                    $responseBodyDecoded['trackingLinks']
                );
                return true;
            } else {
                return false;
            }
        } else {
            Order::setShipmentStatus(
                $order->get_id(),
                $packageIndex,
                Order::SHIPMENT_STATUS_FAILED
            );
            if ($notify) {
                $responseBodyDecoded = $transaction->getResponseBodyDecoded();
                if (
                    !empty($responseBodyDecoded)
                    && is_array($responseBodyDecoded)
                    && !empty($responseBodyDecoded['error'])
                    && is_string($responseBodyDecoded['error'])
                ) {
                    Session::pushError(
                        sprintf(
                            esc_html__('Error: %s', 'oktagon-x-connect-for-woocommerce'),
                            $responseBodyDecoded['error']
                        )
                    );
                }
            }
        }

        return false;
    }

    /**
     * @throws Curl\Exceptions\InvalidArgument
     * @throws Curl\Exceptions\General
     * @throws Curl\Exceptions\Curl
     */
    public static function getPickUpAgentsForSpecificRequest(
        string $shippingServiceId,
        string $country,
        string $street,
        string $zipCode
    ): array {
        $explode = explode('.', $shippingServiceId, 2);
        if (
            empty($explode[0])
            || empty($explode[1])
        ) {
            return [];
        }
        $shippingService = $explode[0];
        $maximumServicePointsLimit = (int) self::getOption(
            'limit_maximum_service_points',
            '0'
        );
        $cacheKey = preg_replace(
            '/[^a-zA-Z0-9]/',
            '_',
            sprintf(
                'pick_up_agents_for_specific_request_%s_%s_%s_%s_%d_%s',
                $shippingServiceId,
                $country,
                $street,
                $zipCode,
                $maximumServicePointsLimit,
                self::getLicenseAndServiceCacheKey()
            )
        );
        if (Cache::test($cacheKey)) {
            return Cache::load($cacheKey);
        }
        $transaction = self::getApiConnection()->getPickUpAgentsForSpecificRequest(
            $shippingService,
            $explode[1],
            [
                'type' => 'PICKUP',
            ],
            $country,
            $street,
            $zipCode
        );
        Transactions::saveTransaction(
            $transaction
        );

        if ($transaction->getResponseStatusCode() === 200) {
            $agents = [];
            $rawAgents = $transaction->getResponseBodyDecoded();
            foreach ($rawAgents as $rawAgent) {
                if (
                    $maximumServicePointsLimit
                    && $maximumServicePointsLimit <= count($agents)
                ) {
                    continue;
                }
                $agent = $rawAgent;
                $agent['address'] = sprintf(
                    '%s%s, %s %s',
                    $rawAgent['address1'],
                    !empty($rawAgent['address2']) ? ', ' . $rawAgent['address1'] : '',
                    $rawAgent['zipCode'],
                    $rawAgent['city']
                );
                $agents[(string) $rawAgent['id']] = $agent;
            }
            Cache::save(
                $agents,
                $cacheKey,
                21600
            );
            return $agents;
        }
        return [];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public static function escapeValue(
        $value
    ) {
        if (is_array($value)) {
            $newArray = [];
            foreach ($value as $key => $value2) {
                $escapedKey = \wp_kses((string) $key, []);
                $escapedValue = self::escapeValue($value2);
                $newArray[$escapedKey] = $escapedValue;
            }
            $value = $newArray;
        } elseif (!is_object($value)) {
            $value = \wp_kses((string) $value, []);
        }
        return $value;
    }

    public static function hasAccount(): bool
    {
        $license = self::getLicense();
        return !empty($license['domain'])
            && !empty($license['password']);
    }

    public static function getOptions(): array
    {
        if (!isset(self::$options)) {
            self::$options = (array) \get_option(
                sprintf(
                    'woocommerce_%s_settings',
                    self::METHOD_ID
                )
            );
        }
        return self::$options;
    }

    public static function getInstanceOptions(
        int $instanceId
    ): array {
        $options = \get_option(
            sprintf(
                'woocommerce_%s_%d_settings',
                self::METHOD_ID,
                $instanceId
            ),
            true
        );
        if (!is_array($options)) {
            $options = [];
        }
        return $options;
    }

    public static function setOption(
        string $key,
        string $value
    ): void {
        $settings = self::getOptions();
        $settings[$key] = $value;
        self::$options = $settings;
        update_option(
            sprintf(
                'woocommerce_%s_settings',
                self::METHOD_ID
            ),
            $settings
        );
    }

    public static function isDebug(): bool
    {
        return !empty(self::getOption('is_debug'));
    }

    /**
     * @throws \Exception
     */
    public static function getPackageHashKey(
        array $package
    ): string {
        if (
            empty($package['contents'])
            || !is_array($package['contents'])
        ) {
            throw new \Exception(
                (string) esc_html__(
                    'Expecting array package contents!',
                    'oktagon-x-connect-for-woocommerce'
                )
            );
        }
        return implode(
            ',',
            array_keys($package['contents'])
        );
    }

    public static function log(
        string $message
    ): void {
        if (self::isDebug()) {
            if (empty(self::$logger)) {
                self::$logger = \wc_get_logger();
            }
            self::$logger->log(
                \WC_Log_Levels::DEBUG,
                $message,
                [
                    'source' => 'oktagon-x-connect-for-woocommerce'
                ]
            );
        }
    }

    public static function getOption(
        string $key,
        string $default = ''
    ): string {
        if ($settings = self::getOptions()) {
            if (
                !empty($key)
                && isset($settings[$key])
            ) {
                return $settings[$key];
            }
        }
        return $default;
    }

    /**
     * @see includes/abstracts/abstract-wc-shipping-method.php:323
     * @throws \Exception
     */
    public static function getOrderRateItems(
        \WC_Order $order,
        \WC_Order_Item_Shipping $rate
    ): array {
        $items = [];

        // Collect name and quantity for all packages in shipping rate
        $packageItems =
            [];
        $metaData =
            $rate->get_formatted_meta_data('');
        foreach ($metaData as $item) {
            if (
                stripos(
                    $item->value,
                    ' &times; '
                ) !== false
            ) {
                $explode = explode(
                    ' &times; ',
                    $item->value
                );
                if (
                    count($explode) > 1
                ) {
                    $matches = [];
                    $i = 0;
                    $lastIndex = count($explode) - 1;
                    $lastName = '';
                    $lastQuantity = 0;
                    foreach ($explode as $exploded) {
                        if ($i == 0) {
                            $lastName = trim($exploded);
                        } elseif ($i == $lastIndex) {
                            $lastQuantity = (int) $exploded;
                            $matches[] = [
                                'name' => $lastName,
                                'quantity' => $lastQuantity
                            ];
                        } else {
                            $nameAndQuantity = explode(
                                ', ',
                                $exploded,
                                2
                            );
                            if (
                                count($nameAndQuantity) > 1
                            ) {
                                $lastQuantity =
                                    (int) $nameAndQuantity[0];
                                $matches[] = [
                                    'name' => $lastName,
                                    'quantity' => $lastQuantity
                                ];
                                $lastName =
                                    trim($nameAndQuantity[1]);
                            }
                        }
                        $i++;
                    }

                    if (!empty($matches)) {
                        foreach ($matches as $match) {
                            if (
                                isset(
                                    $match['name'],
                                    $match['quantity']
                                )
                            ) {
                                if (!isset($packageItems[$match['name']])) {
                                    $packageItems[$match['name']] = [
                                        'name' => $match['name'],
                                        'quantity' => $match['quantity']
                                    ];
                                } else {
                                    $packageItems[$match['name']]['quantity'] +=
                                        $match['quantity'];
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($packageItems) {
            // Find product ids for all package items
            $orderItems =
                $order->get_items();
            if ($orderItems) {
                foreach ($orderItems as $orderItem) {
                    if (
                        is_a(
                            $orderItem,
                            '\WC_Order_Item_Product'
                        )
                    ) {
                        $orderItemName =
                            $orderItem->get_name();
                        // Is order-item included in package?
                        if (isset($packageItems[$orderItemName])) {
                            $subtotal =
                                (float) $orderItem->get_subtotal();
                            $subtotalTax =
                                (float) $orderItem->get_subtotal_tax();
                            $quantity =
                                (int) $packageItems[$orderItemName]['quantity'];
                            $items[] = [
                                'data' =>
                                    $orderItem,
                                'line_subtotal' =>
                                    (float) round(
                                        $subtotal,
                                        2
                                    )
                                    ,
                                'line_subtotal_tax' =>
                                    (float) round(
                                        $subtotalTax,
                                        2
                                    )
                                    ,
                                'quantity' =>
                                    $quantity
                            ];
                        }
                    } else {
                        throw new \Exception(
                            sprintf(
                                esc_html__(
                                    'Unexpected type: %s',
                                    'oktagon-x-connect-for-woocommerce'
                                ),
                                var_export($orderItem, true)
                            )
                        );
                    }
                }
            }
        }
        return $items;
    }

    public static function getFormattedShipmentStatus(
        string $status
    ): string {
        switch ($status) {
            case Order::SHIPMENT_STATUS_DRAFT:
                return (string) esc_html__('Draft', 'oktagon-x-connect-for-woocommerce');
            case Order::SHIPMENT_STATUS_FAILED:
                return (string) esc_html__('Failed', 'oktagon-x-connect-for-woocommerce');
            case Order::SHIPMENT_STATUS_LIVE:
                return (string) esc_html__('Live', 'oktagon-x-connect-for-woocommerce');
            default:
                return $status;
        }
    }
}
