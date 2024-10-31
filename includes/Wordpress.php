<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Wordpress
{
    public function __construct()
    {
        /** @since Wordpress 2.8.0 */
        \add_action(
            'admin_enqueue_scripts',
            [
                $this,
                'adminEnqueueScripts'
            ]
        );
        /** @since Wordpress 3.1.0 */
        \add_action(
            'admin_notices',
            [
                $this,
                'adminNotices'
            ]
        );

        if (
            Meta::hasAccount()
        ) {
            /** @since Wordpress 3.0.0 */
            \add_action(
                'add_meta_boxes',
                [
                    $this,
                    'addMetaBoxes'
                ]
            );

            // Add columns to order-view
            /** @since Woocommerce 3.0.0 */
            \add_filter(
                'manage_shop_order_posts_columns',
                [
                    $this,
                    'manageShopOrderPostsColumns'
                ],
                100,
                2
            );
            /** @since WooCommerce 8+ */
            \add_filter(
                'woocommerce_shop_order_list_table_columns',
                [
                    $this,
                    'manageShopOrderPostsColumns'
                ],
                10,
                2
            );
            /** @since Wordpress 1.5.0 */
            \add_filter(
                'manage_posts_custom_column',
                [
                    $this,
                    'managePostsCustomColumn'
                ],
                100,
                2
            );
            /**
             * @since WooCommerce 8+
             */
            \add_action(
                'manage_woocommerce_page_wc-orders_custom_column',
                [
                    $this,
                    'managePostsCustomColumn2'
                ],
                100,
                2
            );
            /** @since Wordpress 2.8.0 */
            \add_action(
                'admin_footer-edit.php',
                [
                    $this,
                    'customAdminFooter',
                ]
            );
            $version = \get_bloginfo('version');
            if (version_compare($version, '4.7.0', '>=')) {
                /** @see \WC_Admin_Post_Types->__construct() */
                /** @since Wordpress 4.7.0 */
                \add_filter(
                    'bulk_actions-edit-shop_order',
                    [
                        $this,
                        'bulkActionsShopOrder'
                    ]
                );
                /**
                 * @since WooCommerce 8+
                 */
                \add_filter(
                    'bulk_actions-woocommerce_page_wc-orders',
                    [
                        $this,
                        'bulkActionsShopOrder'
                    ]
                );
            }
        }
        \add_action(
            'init',
            [
                $this,
                'init'
            ]
        );
        /* @since Wordpress 2.1.0 */
        \add_action(
            'load-edit.php',
            [
                $this,
                'loadEdit',
            ]
        );
        /**
         * WooCommerce 8+
         */
        \add_filter(
            'handle_bulk_actions-woocommerce_page_wc-orders',
            [
                $this,
                'loadEdit'
            ]
        );
        \add_action(
            'admin_menu',
            [
                $this,
                'adminMenu'
            ]
        );
        /**
         * WooCommerce 8+
         */
        \register_shutdown_function([
            '\Oktagon\WooCommerce\XConnect\Order',
            'propagateChanges'
        ]);

        // Ajax Order Actions

        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-order-action-clear-errors',
            [
                $this,
                'ajaxOrderActionClearErrors'
            ]
        );
        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-order-action-process-package',
            [
                $this,
                'ajaxOrderActionProcessPackage'
            ]
        );
        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-order-action-change-service-point',
            [
                $this,
                'ajaxOrderActionChangeServicePoint'
            ]
        );
        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-order-action-change-additional-options',
            [
                $this,
                'ajaxOrderActionChangeAdditionalOptions'
            ]
        );
        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-order-action-change-service',
            [
                $this,
                'ajaxOrderActionChangeService'
            ]
        );
        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-order-action-update-parcels',
            [
                $this,
                'ajaxOrderActionUpdateParcels'
            ]
        );
        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-order-action-process-order',
            [
                $this,
                'ajaxOrderActionProcessOrder'
            ]
        );
    }

    public function ajaxOrderActionProcessOrder(): void
    {
        $error = '';
        $success = false;
        $orderId = !empty($_POST['orderId'])
            ? (int) sanitize_text_field($_POST['orderId'])
            : 0;
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        if (
            \wp_verify_nonce(
                $nonce,
                'wc_oktagon_x_connect_order_action'
            )
            && $orderId
        ) {
            try {
                $this->orderActionProcessOrder(
                    $orderId
                );
                $success = true;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
        \wp_die(wp_json_encode([
            'error' => $error,
            'success' => $success,
        ]));
    }

    public function ajaxOrderActionChangeService(): void
    {
        $error = '';
        $success = false;
        $orderId = !empty($_POST['orderId'])
            ? (int) sanitize_text_field($_POST['orderId'])
            : 0;
        $packageId = !empty($_POST['packageId'])
            ? sanitize_text_field($_POST['packageId'])
            : 0;
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        if (
            \wp_verify_nonce(
                $nonce,
                'wc_oktagon_x_connect_order_action'
            )
            && $orderId
        ) {
            $service = !empty($_POST['service'])
                ? sanitize_text_field($_POST['service'])
                : '';
            try {
                $this->orderActionChangeService(
                    $orderId,
                    $packageId,
                    $service
                );
                $success = true;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
        \wp_die(wp_json_encode([
            'error' => $error,
            'success' => $success,
        ]));
    }

    private function orderActionUpdateCustomParcels(
        int $postId,
        int $packageIndex,
        bool $customizeParcel,
        array $packages
    ): void {
        Order::setCustomizeParcels(
            $postId,
            $packageIndex,
            (string) $customizeParcel
        );
        $customParcels = [];
        foreach ($packages as $customParcel) {
            $customParcels[] = [
                'description' => !empty($customParcel['description'])
                    ? Meta::escapeValue((string) $customParcel['description'])
                    : '',
                'height' => !empty($customParcel['height'])
                    ? (string) floatval(
                        str_replace(
                            ',',
                            '.',
                            (string) $customParcel['height']
                        )
                    )
                    : '0',
                'length' => !empty($customParcel['length'])
                    ? (string) floatval(
                        str_replace(
                            ',',
                            '.',
                            (string) $customParcel['length']
                        )
                    )
                    : '0',
                'weight' => !empty($customParcel['weight'])
                    ? (string) floatval(
                        str_replace(
                            ',',
                            '.',
                            (string) $customParcel['weight']
                        )
                    )
                    : '0',
                'width' => !empty($customParcel['width'])
                    ? (string) floatval(
                        str_replace(
                            ',',
                            '.',
                            (string) $customParcel['width']
                        )
                    )
                    : '0',
            ];
        }
        if ($customParcels) {
            Order::setCustomParcels(
                $postId,
                $packageIndex,
                $customParcels
            );
        } else {
            Order::clearCustomParcels(
                $postId,
                $packageIndex
            );
        }
        Order::clearShipmentStatus(
            $postId,
            $packageIndex
        );
        Session::pushSuccess(
            sprintf(
                esc_html__(
                    'Changed parcels settings on order %d and package %d',
                    'oktagon-x-connect-for-woocommerce'
                ),
                $postId,
                $packageIndex + 1
            )
        );
    }

    public function ajaxOrderActionChangeAdditionalOptions(): void
    {
        $error = '';
        $success = false;
        $orderId = !empty($_POST['orderId'])
            ? (int) sanitize_text_field($_POST['orderId'])
            : 0;
        $packageId = !empty($_POST['packageId'])
            ? (int) sanitize_text_field($_POST['packageId'])
            : 0;
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        if (
            \wp_verify_nonce(
                $nonce,
                'wc_oktagon_x_connect_order_action'
            )
            && $orderId
        ) {
            $additionalOptions = [];
            if (
                !empty($_POST['additionalOptions'])
                && is_array($_POST['additionalOptions'])
            ) {
                foreach ($_POST['additionalOptions'] as $rawKey => $rawValue) {
                    $key = sanitize_text_field($rawKey);
                    $value = sanitize_text_field($rawValue);
                    if (
                        !empty($key)
                        && is_string($key)
                        && is_string($value)
                    ) {
                        if (
                            $value === 'true'
                            || $value === 'false'
                        ) {
                            $additionalOptions[$key] = ($value === 'true');
                        } else {
                            $additionalOptions[$key] = $value;
                        }
                    }
                }
            }
            try {
                $this->orderActionChangeAdditionalOptions(
                    $orderId,
                    $packageId,
                    $additionalOptions
                );
                $success = true;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
        \wp_die(wp_json_encode([
            'error' => $error,
            'success' => $success,
        ]));
    }

    private function orderActionClearErrors(
        int $postId,
        int $packageIndex
    ): void {
        Order::clearShipmentStatus(
            $postId,
            $packageIndex
        );
        Session::pushSuccess(
            sprintf(
                esc_html__(
                    'Flagged errors as fixed on order %s and package %d!',
                    'oktagon-x-connect-for-woocommerce'
                ),
                $postId,
                $packageIndex + 1
            )
        );
    }

    private function orderActionProcessPackage(
        int $postId,
        int $packageIndex
    ): void {
        Shipment::processOrderPackageIndex(
            $postId,
            $packageIndex
        );
    }

    private function orderActionProcessOrder(
        int $postId
    ): void {
        Shipment::processOrder(
            $postId
        );
    }

    private function orderActionChangeServicePoint(
        int $postId,
        int $packageIndex,
        array $newSelectedServicePoint
    ): void {
        Order::setSelectedServicePoint(
            $postId,
            $packageIndex,
            $newSelectedServicePoint
        );
        Order::clearShipmentStatus(
            $postId,
            $packageIndex
        );
        Session::pushSuccess(
            sprintf(
                esc_html__(
                    'Changed selected service point on order %d and package %d',
                    'oktagon-x-connect-for-woocommerce'
                ),
                $postId,
                $packageIndex + 1
            )
        );
    }

    private function orderActionChangeAdditionalOptions(
        int $postId,
        int $packageIndex,
        array $newAdditionalOptions
    ): void {
        Order::setAdditionalOptions(
            $postId,
            $packageIndex,
            wp_json_encode($newAdditionalOptions)
        );
        Order::clearShipmentStatus(
            $postId,
            $packageIndex
        );
        Session::pushSuccess(
            sprintf(
                esc_html__(
                    'Changed additional options on order %d and package %d',
                    'oktagon-x-connect-for-woocommerce'
                ),
                $postId,
                $packageIndex + 1
            )
        );
    }

    private function orderActionChangeService(
        int $postId,
        int $packageIndex,
        string $newShippingService
    ): void {
        Order::setShippingService(
            $postId,
            $packageIndex,
            $newShippingService
        );
        Order::setSelectedServicePoint(
            $postId,
            $packageIndex,
            []
        );
        Order::clearShipmentStatus(
            $postId,
            $packageIndex
        );
        Session::pushSuccess(
            sprintf(
                esc_html__(
                    'Changed shipping service on order %d and package %d',
                    'oktagon-x-connect-for-woocommerce'
                ),
                $postId,
                $packageIndex + 1
            )
        );
    }

    public function ajaxOrderActionChangeServicePoint(): void
    {
        $error = '';
        $success = false;
        $orderId = !empty($_POST['orderId'])
            ? (int) sanitize_text_field($_POST['orderId'])
            : 0;
        $packageId = !empty($_POST['packageId'])
            ? (int) sanitize_text_field($_POST['packageId'])
            : 0;
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        if (
            \wp_verify_nonce(
                $nonce,
                'wc_oktagon_x_connect_order_action'
            )
            && $orderId
        ) {
            $servicePointRaw = !empty($_POST['servicePoint'])
                ? sanitize_text_field($_POST['servicePoint'])
                : '';
            $servicePoint = [];
            try {
                $decodedServicePoint = json_decode(
                    base64_decode(
                        $servicePointRaw
                    ),
                    true
                );
                if (
                    $decodedServicePoint
                    && is_array($decodedServicePoint)
                ) {
                    $servicePoint = $decodedServicePoint;
                }
            } catch (\Exception $e) {
                $servicePoint = [];
            }
            if ($servicePoint) {
                try {
                    $this->orderActionChangeServicePoint(
                        $orderId,
                        $packageId,
                        $servicePoint
                    );
                    $success = true;
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }
        \wp_die(wp_json_encode([
            'error' => $error,
            'success' => $success,
        ]));
    }

    public function ajaxOrderActionProcessPackage(): void
    {
        $error = '';
        $success = false;
        $orderId = !empty($_POST['orderId'])
            ? (int) sanitize_text_field($_POST['orderId'])
            : 0;
        $packageId = !empty($_POST['packageId'])
            ? (int) sanitize_text_field($_POST['packageId'])
            : 0;
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        if (
            \wp_verify_nonce(
                $nonce,
                'wc_oktagon_x_connect_order_action'
            )
            && $orderId
        ) {
            try {
                $this->orderActionProcessPackage(
                    $orderId,
                    $packageId
                );
                $success = true;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
        \wp_die(wp_json_encode([
            'error' => $error,
            'success' => $success,
        ]));
    }

    public function ajaxOrderActionClearErrors(): void
    {
        $error = '';
        $success = false;
        $orderId = !empty($_POST['orderId'])
            ? (int) sanitize_text_field($_POST['orderId'])
            : 0;
        $packageId = !empty($_POST['packageId'])
            ? (int) sanitize_text_field($_POST['packageId'])
            : 0;
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        if (
            \wp_verify_nonce(
                $nonce,
                'wc_oktagon_x_connect_order_action'
            )
            && $orderId
        ) {
            try {
                $this->orderActionClearErrors(
                    $orderId,
                    $packageId
                );
                $success = true;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
        \wp_die(wp_json_encode([
            'error' => $error,
            'success' => $success,
        ]));
    }

    /**
     * @suppress PhanTypeMismatchDimAssignment,PhanTypeMismatchDimFetch
     */
    public function ajaxOrderActionUpdateParcels(): void
    {
        $error = '';
        $success = false;
        $orderId = !empty($_POST['orderId'])
            ? (int) sanitize_text_field($_POST['orderId'])
            : 0;
        $packageId = !empty($_POST['packageId'])
            ? (int) sanitize_text_field($_POST['packageId'])
            : 0;
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        $packages = [];
        if (
            \wp_verify_nonce(
                $nonce,
                'wc_oktagon_x_connect_order_action'
            )
            && $orderId
        ) {
            $customizeParcels = !empty($_POST['customizeParcels']);
            if (
                !empty($_POST['packages'])
                && is_array($_POST['packages'])
            ) {
                foreach ($_POST['packages'] as $rawPackage) {
                    $packageDescription = !empty($rawPackage['description'])
                        ? sanitize_text_field($rawPackage['description'])
                        : '';
                    $packageHeight = !empty($rawPackage['height'])
                        ? sanitize_text_field($rawPackage['height'])
                        : '0';
                    $packageLength = !empty($rawPackage['length'])
                        ? sanitize_text_field($rawPackage['length'])
                        : '0';
                    $packageWeight = !empty($rawPackage['weight'])
                        ? sanitize_text_field($rawPackage['weight'])
                        : '0';
                    $packageWidth = !empty($rawPackage['width'])
                        ? sanitize_text_field($rawPackage['width'])
                        : '0';
                    $packages[] = [
                        'description' => $packageDescription,
                        'height' => $packageHeight,
                        'length' => $packageLength,
                        'weight' => $packageWeight,
                        'width' => $packageWidth,
                    ];
                }
            }
            try {
                $this->orderActionUpdateCustomParcels(
                    $orderId,
                    $packageId,
                    $customizeParcels,
                    $packages
                );
                $success = true;
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }
        \wp_die(wp_json_encode(
            [
                'error' => $error,
                'packages' => $packages,
                'success' => $success,
            ]
        ));
    }

    public function init()
    {
        if (
            !\load_plugin_textdomain(
                'oktagon-x-connect-for-woocommerce',
                false,
                'oktagon-x-connect-for-woocommerce/languages'
            )
        ) {
            Meta::log(
                'Failed to load text domain for oktagon-x-connect-for-woocommerce!'
            );
        }
    }

    public function adminMenu()
    {
        \add_submenu_page(
            'woocommerce',
            (string) esc_html__(
                'X-Connect',
                'oktagon-x-connect-for-woocommerce'
            ),
            (string) esc_html__(
                'X-Connect',
                'oktagon-x-connect-for-woocommerce'
            ),
            'manage_woocommerce',
            'oktagon-x-connect-for-woocommerce-setup',
            [
                $this,
                'setup'
            ]
        );
    }

    public function setup()
    {
        $setup = new Setup();
        $setup->execute();
    }

    /**
     * @see \WC_Admin_Post_Types->shop_order_bulk_actions()
     */
    public function bulkActionsShopOrder(array $actions): array
    {
        $actions['oktagon-x-connect-for-woocommerce-bulk-labels'] = esc_html__(
            "X-Connect - Print Shipping Labels",
            'oktagon-x-connect-for-woocommerce'
        );
        $actions['oktagon-x-connect-for-woocommerce-bulk-process'] = esc_html__(
            "X-Connect - Process Shipping",
            'oktagon-x-connect-for-woocommerce'
        );
        return $actions;
    }

    /**
     * Handle bulk actions
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function loadEdit()
    {
        /** @since Wordpress 3.1.0 */
        if ($wpListTable = _get_list_table('WP_Posts_List_Table')) {
            $action = $wpListTable->current_action();
            /** @since Wordpress 1.2.0 */
            if (
                !empty($action)
                && ($action === 'oktagon-x-connect-for-woocommerce-bulk-labels'
                    || $action === 'oktagon-x-connect-for-woocommerce-bulk-process')
            ) {
                $postIds = (isset($_GET['post'])
                    ? array_map('intval', $_GET['post'])
                    : []);
                if (!$postIds) {
                    $postIds = (isset($_GET['id'])
                        ? array_map('intval', $_GET['id'])
                        : []);
                }
                if (count($postIds)) {
                    /** @since Wordpress 2.0.0 */
                    if (current_user_can('edit_posts')) {
                        if (
                            $action === 'oktagon-x-connect-for-woocommerce-bulk-labels'
                        ) {
                            $shippingLabels = [];
                            $shipmentNumbers = [];
                            foreach ($postIds as $postId) {
                                if ($order = \wc_get_order($postId)) {
                                    if ($orderShipping = $order->get_items('shipping')) {
                                        $packageId = 0;
                                        $packageCount = count($orderShipping);
                                        for ($packageId = 0; $packageId < $packageCount; $packageId++) {
                                            if (
                                                $status = Order::getShipmentStatus(
                                                    $postId,
                                                    $packageId
                                                )
                                            ) {
                                                if ($status === Order::SHIPMENT_STATUS_LIVE) {
                                                    if (
                                                        $packageShippingLabels = Order::getShippingLabels(
                                                            $postId,
                                                            $packageId
                                                        )
                                                    ) {
                                                        $shippingLabels = array_merge(
                                                            $shippingLabels,
                                                            $packageShippingLabels
                                                        );
                                                    }
                                                    if (
                                                        $packageShipmentNumber = Order::getShipmentNumber(
                                                            $postId,
                                                            $packageId
                                                        )
                                                    ) {
                                                        $shipmentNumbers[] =
                                                            $packageShipmentNumber;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    if (
                                        $status = Order::getShipmentStatus(
                                            $postId,
                                            -1
                                        )
                                    ) {
                                        if ($status === Order::SHIPMENT_STATUS_LIVE) {
                                            if (
                                                $packageShippingLabels = Order::getShippingLabels(
                                                    $postId,
                                                    -1
                                                )
                                            ) {
                                                $shippingLabels = array_merge(
                                                    $shippingLabels,
                                                    $packageShippingLabels
                                                );
                                            }
                                            if (
                                                $packageShipmentNumber = Order::getShipmentNumber(
                                                    $postId,
                                                    -1
                                                )
                                            ) {
                                                $shipmentNumbers[] =
                                                    $packageShipmentNumber;
                                            }
                                        }
                                    }
                                }
                            }

                            if (count($shippingLabels) > 1) {
                                $mergedBasename = sprintf(
                                    '%s/%s_%s.pdf',
                                    \wp_upload_dir()['subdir'],
                                    Meta::UPLOAD_LABEL_PREFIX,
                                    md5(
                                        implode(
                                            ',',
                                            $shipmentNumbers
                                        )
                                    )
                                );
                                $mergedFilename = Meta::getShippingLabelFilename(
                                    $mergedBasename
                                );
                                $mergedUrl = Meta::getShippingLabelUrl(
                                    $mergedBasename
                                );

                                if (file_exists($mergedFilename)) {
                                    Session::pushDownload(
                                        $mergedUrl
                                    );
                                    Session::pushSuccess(
                                        sprintf(
                                            '<a target="_blank" href="%s">%s</a>',
                                            $mergedUrl,
                                            esc_html__(
                                                'Downloading labels in new window..',
                                                'oktagon-x-connect-for-woocommerce'
                                            )
                                        )
                                    );
                                } else {
                                    Shipment::generateMergedPdf(
                                        $shippingLabels,
                                        $mergedFilename
                                    );
                                    Session::pushDownload(
                                        $mergedUrl
                                    );
                                    // phpcs:disable Generic.Files.LineLength.TooLong
                                    Session::pushSuccess(
                                        sprintf(
                                            '<a target="_blank" href="%s">%s</a>',
                                            $mergedUrl,
                                            esc_html__(
                                                'Downloading labels in new window..',
                                                'oktagon-x-connect-for-woocommerce'
                                            )
                                        )
                                    );
                                    // phpcs:enable Generic.Files.LineLength.TooLong
                                }
                            } elseif (count($shippingLabels) == 1) {
                                $labelUrl = Meta::getShippingLabelUrl(
                                    reset($shippingLabels)
                                );
                                Session::pushDownload($labelUrl);
                                Session::pushSuccess(
                                    sprintf(
                                        '<a target="_blank" href="%s">%s</a>',
                                        $labelUrl,
                                        esc_html__(
                                            'Downloading label in new window..',
                                            'oktagon-x-connect-for-woocommerce'
                                        )
                                    )
                                );
                            } else {
                                Session::pushError(
                                    sprintf(
                                        esc_html__(
                                            'Failed to find any shipping-labels for orders %s!',
                                            'oktagon-x-connect-for-woocommerce'
                                        ),
                                        implode(',', $postIds)
                                    )
                                );
                            }
                        } elseif ($action === 'oktagon-x-connect-for-woocommerce-bulk-process') {
                            $count = 0;
                            foreach ($postIds as $postId) {
                                if (
                                    Shipment::processOrder(
                                        $postId,
                                        true,
                                        false
                                    )
                                ) {
                                    $count++;
                                }
                            }

                            if ($count) {
                                Session::pushSuccess(
                                    sprintf(
                                        (string) esc_html__(
                                            'Bulk process of shipments completed, %d items processed.',
                                            'oktagon-x-connect-for-woocommerce'
                                        ),
                                        $count
                                    )
                                );
                            } else {
                                Session::pushError(
                                    (string) esc_html__(
                                        'Bulk process of shipments completed, no items processed.',
                                        'oktagon-x-connect-for-woocommerce'
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function customAdminFooter()
    {
        /**
         * @var string $post_type
         */
        global $post_type;
        if (
            !empty($post_type)
            && $post_type === 'shop_order'
        ) {
            $version = \get_bloginfo('version');
            if (version_compare($version, '4.7.0', '<')) {
                // NOTE: Bulk actions if Wordpress version if below 4.7.0
                echo \wp_kses(
                    '<script type="text/javascript">'
                    . 'jQuery(document).ready(function() {'
                    . 'jQuery("<option>").val("oktagon-x-connect-for-woocommerce-bulk-labels").text("'
                    . esc_html__("Download shipping labels", 'oktagon-x-connect-for-woocommerce')
                    . '").appendTo("select[name=\'action\']");'
                    . 'jQuery("<option>").val("oktagon-x-connect-for-woocommerce-bulk-labels").text("'
                    . esc_html__("Download shipping labels", 'oktagon-x-connect-for-woocommerce')
                    . '").appendTo("select[name=\'action2\']");'
                    . 'jQuery("<option>").val("oktagon-x-connect-for-woocommerce-bulk-process").text("'
                    . esc_html__("Process shipping", 'oktagon-x-connect-for-woocommerce')
                    . '").appendTo("select[name=\'action\']");'
                    . 'jQuery("<option>").val("oktagon-x-connect-for-woocommerce-bulk-process").text("'
                    . esc_html__("Process shipping", 'oktagon-x-connect-for-woocommerce')
                    . '").appendTo("select[name=\'action2\']");'
                    . '""});</script>',
                    [
                        'script' => ['type' => []],
                    ]
                );
            }
        }
    }

    public function adminNotices()
    {
        // Wizard
        $isSetupPage = !empty($_GET['page'])
            && $_GET['page'] === 'oktagon-x-connect-for-woocommerce-setup';
        $hideWizard = $isSetupPage
            || Meta::hasAccount();
        if (!$hideWizard) {
            printf(
                '<div class="notice notice-info oktagon-x-connect-for-woocommerce-wizard-notice">
                <p><strong>%s</strong> <span>%s</span></p>
                </div>',
                esc_html__('Oktagon X-Connect', 'oktagon-x-connect-for-woocommerce'),
                sprintf(
                    '<a href="%s">%s</a>',
                    \esc_attr(admin_url('admin.php?page=oktagon-x-connect-for-woocommerce-setup')),
                    esc_html__(
                        'Please setup your shipping by using our installation guide.',
                        'oktagon-x-connect-for-woocommerce'
                    )
                )
            );
        }

        if (
            Session::hasSuccess()
        ) {
            while (
                $message = Session::popSuccess()
            ) {
                printf(
                    '<div class="notice notice-success"><p>%s</p></div>',
                    \wp_kses(
                        $message,
                        [
                            'a' => ['target' => [], 'href' => []],
                        ]
                    )
                );
            }
        }
        if (
            Session::hasError()
        ) {
            while (
                $message = Session::popError()
            ) {
                printf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    \wp_kses(
                        $message,
                        [
                            'a' => ['target' => [], 'href' => []],
                        ]
                    )
                );
            }
        }
        if (Session::hasDownload()) {
            while (
                $url = Session::popDownload()
            ) {
                echo \wp_kses(
                    '<script type="text/javascript">'
                    . 'jQuery(document).ready(function() { '
                    . 'window.open("'
                    . \esc_attr($url) . '", "_blank");'
                    . ' });</script>',
                    [
                        'script' => ['type' => []],
                    ]
                );
            }
        }
    }

    /**
     * Adds styles and scripts to admin
     */
    public function adminEnqueueScripts()
    {
        /** @since Wordpress 2.1.0 */
        \wp_register_script(
            'oktagon-x-connect-for-woocommerce-admin-script',
            \plugins_url(
                'includes/assets/admin/js/script.js',
                dirname(__FILE__)
            ),
            ['jquery'],
            '1.0.2'
        );
        /** @since Wordpress 2.1.0 */
        \wp_enqueue_script(
            'oktagon-x-connect-for-woocommerce-admin-script'
        );

        /** @since Wordpress 2.6.0 */
        \wp_enqueue_style(
            'oktagon-x-connect-for-woocommerce-admin-style',
            \plugins_url(
                'includes/assets/admin/css/style.css',
                dirname(__FILE__)
            ),
            [],
            '1.0.2'
        );
    }

    public function addMetaBoxes(string $postType): void
    {
        if (
            !empty($postType)
            && $postType === 'shop_order'
        ) {
            /** @since Wordpress 2.5.0 */
            \add_meta_box(
                'oktagon-x-connect-for-woocommerce-order',
                (string) esc_html__(
                    'X-Connect',
                    'oktagon-x-connect-for-woocommerce'
                ),
                [
                    $this,
                    'renderMetaBoxesShopOrder'
                ],
                $postType,
                'side'
            );
        } elseif (
            !empty($postType)
            && $postType === 'woocommerce_page_wc-orders'
        ) {
            /** @since Wordpress 2.5.0 */
            \add_meta_box(
                'oktagon-x-connect-for-woocommerce-order',
                (string) esc_html__(
                    'X-Connect',
                    'oktagon-x-connect-for-woocommerce'
                ),
                [
                    $this,
                    'renderMetaBoxesPageWcOrders'
                ],
                $postType,
                'side'
            );
        }
    }

    public function renderMetaBoxesShopOrder(
        \WP_Post $post
    ): void {
        $this->renderMetaBoxes($post->ID);
    }

    public function renderMetaBoxesPageWcOrders(
        \Automattic\WooCommerce\Admin\Overrides\Order $order
    ): void {
        $this->renderMetaBoxes($order->get_id());
    }

    public function renderMetaBox(
        \WC_Order $order,
        int $packageId = -1,
        \WC_Order_Item_Shipping $instance = null
    ): void {
        $orderId = $order->get_id();
        $shipmentStatus = Order::getShipmentStatus(
            $orderId,
            $packageId
        );
        $orderProcessing = Meta::getOption(
            'order_processing'
        );
        $packageItems = ($instance
            ? Meta::getOrderRateItems(
                $order,
                $instance
            )
            : []
        );
        $customizeParcels = Order::getCustomizeParcels(
            $orderId,
            $packageId
        );
        $customParcels = Order::getCustomParcels(
            $orderId,
            $packageId
        );
        $calculatedParcels = $customizeParcels && $customParcels
            ? $customParcels
            : Physics::calculateOrderItems($order);
        if (!$customParcels) {
            $customParcels = $calculatedParcels;
        }

        $shippingService = Order::getShippingService(
            $orderId,
            $packageId
        );
        $shippingServices = Meta::getShippingOptionsInGeneral();

        // Try to find shipping service title
        $shippingServiceId = '';
        $shippingServiceTitle = '';
        $shippingServiceObject = [];
        if (
            $shippingService
            && $shippingServices
        ) {
            $explode = explode('.', $shippingService, 2);
            $shippingServiceApi = $explode[0];
            $shippingServiceId = $explode[1];
            if (!empty($shippingServices[$shippingServiceApi])) {
                foreach ($shippingServices[$shippingServiceApi] as $s) {
                    if ($s['id'] === $shippingServiceId) {
                        $shippingServiceObject = $s;
                        $shippingServiceTitle = $s['title'];
                        break;
                    }
                }
            }
        }

        $additionalOptions = [];
        if (
            $additionalOptionsString = Order::getAdditionalOptions(
                $orderId,
                $packageId
            )
        ) {
            try {
                $additionalOptions = json_decode(
                    $additionalOptionsString,
                    true
                );
            } catch (\Exception $e) {
                $additionalOptions = [];
            }
        }
        echo \wp_kses(
            Template::processTemplate(
                'order/meta_boxes',
                [
                    'additionalOptions' => $additionalOptions,
                    'apiDraftTransaction' => Transactions::getTransactionById(
                        Order::getApiDraftTransaction(
                            $orderId,
                            $packageId
                        )
                    ),
                    'apiLiveTransaction' => Transactions::getTransactionById(
                        Order::getApiLiveTransaction(
                            $orderId,
                            $packageId
                        )
                    ),
                    'calculatedParcels' =>
                        $calculatedParcels,
                    'customizeParcels' => $customizeParcels,
                    'customParcels' => $customParcels,
                    'instance' => $instance,
                    'isDebug' => Meta::getOption('is_debug'),
                    'locale' => Meta::getLocale(),
                    'order' => $order,
                    'orderId' => $orderId,
                    'packageId' => $packageId,
                    'packageItems' => $packageItems,
                    'selectedServicePoint' => Order::getSelectedServicePoint(
                        $orderId,
                        $packageId
                    ),
                    'servicePoints' => Meta::getPickUpAgentsForSpecificRequest(
                        $shippingService,
                        $order->get_shipping_country(),
                        $order->get_shipping_address_1(),
                        $order->get_shipping_postcode()
                    ),
                    'shipmentNumber' => Order::getShipmentNumber(
                        $orderId,
                        $packageId
                    ),
                    'shipmentStatus' => Meta::getFormattedShipmentStatus(
                        Order::getShipmentStatus(
                            $orderId,
                            $packageId
                        )
                    ),
                    'shippingService' => $shippingService,
                    'shippingServiceId' => $shippingServiceId,
                    'shippingServiceObject' => $shippingServiceObject,
                    'shippingServiceTitle' => $shippingServiceTitle,
                    'shippingServices' => $shippingServices,
                    'shipmentStatusValue' =>
                        $shipmentStatus,
                    'shippingLabels' => Order::getShippingLabels(
                        $orderId,
                        $packageId
                    ),
                    'showFixedErrorsButton' => $shipmentStatus === Order::SHIPMENT_STATUS_FAILED,
                    'showProcessButton' =>
                        ($orderProcessing == Meta::ORDER_PROCESSING_CREATE_DRAFT_SHIPMENTS
                            && $shipmentStatus !== Order::SHIPMENT_STATUS_DRAFT)
                        || ($orderProcessing == Meta::ORDER_PROCESSING_CREATE_LIVE_SHIPMENTS
                            && $shipmentStatus !== Order::SHIPMENT_STATUS_LIVE),
                    'trackingLinks' => Order::getTrackingLinks(
                        $orderId,
                        $packageId
                    ),
                ]
            ),
            [
                'a' => [
                    'class' => [],
                    'data-action' => [],
                    'href' => [],
                    'id' => [],
                    'target' => [],
                ],
                'br' => [],
                'button' => ['class' => [], 'id' => []],
                'dd' => [
                    'class' => [],
                    'data-service-id' => [],
                    'data-service-title' => [],
                    'data-value' => [],
                    'id' => [],
                ],
                'div' => ['class' => [], 'id' => []],
                'dl' => ['class' => [], 'id' => []],
                'dt' => ['class' => [], 'id' => []],
                'fieldset' => [
                    'class' => [],
                    'data-nonce' => [],
                    'data-order' => [],
                    'data-package' => [],
                    'id' => [],
                ],
                'form' => ['method' => [], 'action' => [], 'class' => []],
                'h1' => ['class' => [], 'id' => []],
                'h2' => ['class' => [], 'id' => []],
                'input' => [
                    'checked' => [],
                    'class' => [],
                    'data-key' => [],
                    'name' => [],
                    'value' => [],
                    'type' => [],
                ],
                'label' => [],
                'legend' => [],
                'option' => ['selected' => [], 'value' => []],
                'p' => ['class' => [], 'id' => []],
                'pre' => ['class' => [], 'id' => []],
                'script' => ['class' => [], 'id' => []],
                'section' => ['class' => [], 'id' => []],
                'select' => [
                    'class' => [],
                    'data-key' => [],
                    'id' => [],
                    'name' => [],
                ],
                'span' => ['class' => [], 'id' => []],
                'strong' => ['class' => [], 'id' => []],
                'table' => ['class' => [], 'id' => []],
                'tbody' => ['class' => [], 'id' => []],
                'td' => ['class' => [], 'id' => []],
                'th' => ['class' => [], 'id' => []],
                'thead' => ['class' => [], 'id' => []],
                'tr' => ['class' => [], 'data-package' => [], 'id' => []],
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function renderMetaBoxes(int $postId): void
    {
        /** @since Woocommerce 3.0.0 */
        if ($order = \wc_get_order($postId)) {
            /** @var \WC_Order $order */
            if ($shipping = $order->get_items('shipping')) {
                $packageIndex = 0;
                foreach ($shipping as $shippingMethod) {
                    if (
                        is_a(
                            $shippingMethod,
                            '\WC_Order_Item_Shipping'
                        )
                    ) {
                        $this->renderMetaBox(
                            $order,
                            $packageIndex,
                            $shippingMethod
                        );
                    } else {
                        throw new \Exception(
                            sprintf(
                                'Unexpected type: %s',
                                var_export($shippingMethod, true)
                            )
                        );
                    }
                    $packageIndex++;
                }
            } else {
                echo wp_kses_post('<fieldset><legend>');
                $this->renderMetaBox(
                    $order
                );
                echo wp_kses_post('</fieldset>');
            }
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function manageShopOrderPostsColumns(
        array $columns = []
    ): array {
        $columns['oktagon-x-connect-for-woocommerce-order'] = esc_html__(
            'X-Connect',
            'oktagon-x-connect-for-woocommerce'
        );
        return $columns;
    }

    public function managePostsCustomColumn(
        string $column,
        int $orderId,
        bool $echo = true
    ): ?string {
        $html = '';
        if (
            !empty($column)
            && $orderId !== null
            && $column === 'oktagon-x-connect-for-woocommerce-order'
        ) {
            $packageIndex = 0;
            if ($order = \wc_get_order($orderId)) {
                /** @var \WC_Order $order */
                $foundOrderToProcess = false;
                $foundReturnToProcess = false;
                $hasFailedShipment = false;

                if ($shipping = $order->get_items('shipping')) {
                    $packageCount = count($shipping);
                    for ($packageIndex = 0; $packageIndex < $packageCount; $packageIndex++) {
                        /**
                         * Get order status
                         *
                         * @since Wordpress 1.5.0
                         */
                        /** @since Woocommerce 3.0.0 */
                        if (
                            Order::getShippingService(
                                $orderId,
                                $packageIndex
                            )
                        ) {
                            $html .= $this->orderTablePackageActions(
                                $orderId,
                                $packageIndex,
                                $foundOrderToProcess
                            );
                        }
                        if (
                            Order::getShipmentStatus(
                                $orderId,
                                $packageIndex
                            ) === Order::SHIPMENT_STATUS_FAILED
                        ) {
                            $hasFailedShipment = true;
                        }
                    }
                } else {
                    $packageIndex = -1;
                    if (
                        Order::getShippingService(
                            $orderId,
                            $packageIndex
                        )
                    ) {
                        $html .= $this->orderTablePackageActions(
                            $orderId,
                            $packageIndex,
                            $foundOrderToProcess
                        );
                    }
                    if (
                        Order::getShipmentStatus(
                            $orderId,
                            $packageIndex
                        ) === Order::SHIPMENT_STATUS_FAILED
                    ) {
                        $hasFailedShipment = true;
                    }
                }

                if ($foundOrderToProcess) {
                    $nonceValue = \wp_create_nonce(
                        'wc_oktagon_x_connect_order_action'
                    );

                    $html .= sprintf(
                        '<a class="button button-primary oktagon-x-connect-for-woocommerce-order-table-action" '
                        . 'data-nonce="%s" data-action="process" data-id="%s">%s</a>',
                        \esc_html((string) $nonceValue),
                        \esc_html((string) $orderId),
                        esc_html__(
                            'Process',
                            'oktagon-x-connect-for-woocommerce'
                        )
                    );
                }

                if ($foundReturnToProcess) {
                    $nonceValue = \wp_create_nonce(
                        'wc_oktagon_x_connect_order_action'
                    );

                    $html .= sprintf(
                        '<a class="button button-primary oktagon-x-connect-for-woocommerce-order-table-action" '
                        . 'data-nonce="%s" data-action="return" data-id="%s">%s</a>',
                        \esc_html((string) $nonceValue),
                        \esc_html((string) $orderId),
                        esc_html__(
                            'Return',
                            'oktagon-x-connect-for-woocommerce'
                        )
                    );
                    // phpcs:enable Generic.Files.LineLength.TooLong
                }

                if ($hasFailedShipment) {
                    $html .= '<span class="oktagon-x-connect-for-woocommerce-shipment-error"></span>';
                }
                $html = (string) \apply_filters(
                    'oktagon-x-connect-for-woocommerce-order-column-unprocessed',
                    $html,
                    $orderId,
                    $packageIndex
                );
                if ($echo) {
                    echo \wp_kses_post($html);
                }
            }
        }
        return $html;
    }

    private function orderTablePackageActions(
        int $orderId,
        int $packageIndex,
        bool &$foundOrderToProcess
    ): string {
        $status = Order::getShipmentStatus(
            $orderId,
            $packageIndex
        );
        $orderProcessing = Meta::getOption(
            'order_processing'
        );
        if ($status === Order::SHIPMENT_STATUS_LIVE) {
            $html = '<dl class="oktagon-x-connect-for-woocommerce-order-table">';
            if (
                $shipmentNumber = Order::getShipmentNumber(
                    $orderId,
                    $packageIndex
                )
            ) {
                $html .= sprintf(
                    '<dt class="shipment-number">%s</dt><dd class="shipment-number">%s</dd>',
                    esc_html__('Shipment Number', 'oktagon-x-connect-for-woocommerce'),
                    \esc_html($shipmentNumber)
                );
            }
            if (
                $shippingLabels = Order::getShippingLabels(
                    $orderId,
                    $packageIndex
                )
            ) {
                $shippingLabelsHtml = '<ul class="shipping-labels">';
                foreach ($shippingLabels as $rawShippingLabelIndex => $shippingLabel) {
                    $shippingLabelIndex = (int) $rawShippingLabelIndex;
                    $shippingLabelsHtml .= sprintf(
                        '<li class="shipping-label shipping-label-%s"><a target="_blank" href="%s">%s</a></li>',
                        ($shippingLabelIndex > 0 ? 'following' : 'first'),
                        \esc_html(Meta::getShippingLabelUrl($shippingLabel)),
                        sprintf(
                            esc_html__(
                                'Shipping Label #%s',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            \esc_html((string) ($shippingLabelIndex + 1))
                        )
                    );
                }
                $shippingLabelsHtml .= '</ul>';
                $html .= sprintf(
                    '<dt class="shipping-labels">%s</dt><dd class="shipping-labels">%s</dd>',
                    esc_html__('Shipping Labels', 'oktagon-x-connect-for-woocommerce'),
                    $shippingLabelsHtml
                );
            }
            if (
                $trackingLinks = Order::getTrackingLinks(
                    $orderId,
                    $packageIndex
                )
            ) {
                $trackingLinksHtml = '<ul class="tracking-links">';
                foreach ($trackingLinks as $rawTrackingLinkIndex => $trackingLink) {
                    $trackingLinkIndex = (int) $rawTrackingLinkIndex;
                    $trackingLinksHtml .= sprintf(
                        '<li class="tracking-link tracking-link-%s"><a target="_blank" href="%s">%s</a></li>',
                        ($trackingLinkIndex > 0 ? 'following' : 'first'),
                        \esc_html($trackingLink),
                        sprintf(
                            esc_html__('Tracking Link #%s', 'oktagon-x-connect-for-woocommerce'),
                            \esc_html((string) ($trackingLinkIndex + 1))
                        )
                    );
                }
                $trackingLinksHtml .= '</ul>';
                $html .= sprintf(
                    '<dt class="tracking-links">%s</dt><dd class="tracking-links">%s</dd>',
                    esc_html__('Tracking Links', 'oktagon-x-connect-for-woocommerce'),
                    $trackingLinksHtml
                );
            }
            $html = \apply_filters(
                'oktagon-x-connect-for-woocommerce-order_column_processed',
                $html,
                $orderId,
                $packageIndex
            );
            $html .= '</dl>';
            return $html;
        } else {
            if (
                (
                    $orderProcessing === Meta::ORDER_PROCESSING_CREATE_LIVE_SHIPMENTS
                    && $status !== Order::SHIPMENT_STATUS_LIVE
                )
                || (
                    $orderProcessing === Meta::ORDER_PROCESSING_CREATE_DRAFT_SHIPMENTS
                    && $status !== Order::SHIPMENT_STATUS_DRAFT
                )
            ) {
                $foundOrderToProcess = true;
            }
        }
        return '';
    }

    public function managePostsCustomColumn2(
        string $column,
        \Automattic\WooCommerce\Admin\Overrides\Order $order
    ): ?string {
        return $this->managePostsCustomColumn(
            $column,
            $order->get_id()
        );
    }
}
