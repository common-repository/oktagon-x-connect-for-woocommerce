<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Shipment
{
    /**
     * @throws \Exception
     * @since Woocommerce 3.0.0
     */
    public static function processOrder(
        int $orderId,
        bool $notify = true,
        bool $throwException = true
    ): bool {
        if ($order = \wc_get_order($orderId)) {
            /** @var \WC_Order $order */

            // Collect shippable order-packages
            $shipPackageIndexes = [];
            if ($shipping = $order->get_items('shipping')) {
                $packageIndex = 0;
                $packageCount = count($shipping);
                for ($packageIndex = 0; $packageIndex < $packageCount; $packageIndex++) {
                    if (
                        Order::getShippingService(
                            $orderId,
                            $packageIndex
                        )
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
                $shipPackageIndexes[] =
                    -1;
            }

            if (empty($shipPackageIndexes)) {
                if ($throwException) {
                    throw new \Exception(
                        sprintf(
                            esc_html__(
                                'Found no shipping service to ship in order %d!',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            $orderId
                        )
                    );
                } else {
                    Session::pushError(
                        sprintf(
                            esc_html__(
                                'Found no shipping service to ship in order %d!',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            $orderId
                        )
                    );
                    return false;
                }
            }

            $successes = 0;
            $failures = 0;
            foreach ($shipPackageIndexes as $packageIndex) {
                if (
                    self::processOrderPackageIndex(
                        $orderId,
                        $packageIndex,
                        false,
                        $order
                    )
                ) {
                    $successes++;
                } else {
                    $failures++;
                }
            }

            if ($notify) {
                if ($successes) {
                    Session::pushSuccess(
                        sprintf(
                            esc_html__(
                                'Successfully processed %d shipment-packages for order %d',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            $successes,
                            $orderId
                        )
                    );
                }
                if ($failures) {
                    Session::pushError(
                        sprintf(
                            esc_html__(
                                'Failed to process %d shipment-packages for order %d!',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            $failures,
                            $orderId
                        )
                    );
                }
            }
            return $successes && !$failures;
        } else {
            throw new \Exception(
                sprintf(
                    esc_html__(
                        'Could not process order %d because it was not found!',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    $orderId
                )
            );
        }
    }

    /**
     * @throws \Exception
     * phpcs:disable Generic.Files.LineLength.TooLong
     */
    public static function processOrderPackageIndex(
        int $orderId,
        int $packageIndex,
        bool $notify = true,
        \WC_Order $order = null
    ): bool {
        if ($order === null) {
            $order = \wc_get_order($orderId);
            if (empty($order)) {
                throw new \Exception(
                    sprintf(
                        esc_html__(
                            'Could not process order %d because it was not found!',
                            'oktagon-x-connect-for-woocommerce'
                        ),
                        $orderId
                    )
                );
            }
        }

        $orderProcessing = Meta::getOption(
            'order_processing'
        );
        if (empty($orderProcessing)) {
            Session::pushError(
                (string) esc_html__(
                    'Missing global setting for order-processing, please run the installation-guide first!',
                    'oktagon-x-connect-for-woocommerce'
                )
            );
            return false;
        }

        $shipmentStatus = Order::getShipmentStatus(
            $orderId,
            $packageIndex
        );

        if (
            $orderProcessing === Meta::ORDER_PROCESSING_CREATE_DRAFT_SHIPMENTS
            && $shipmentStatus === Order::SHIPMENT_STATUS_DRAFT
        ) {
            Session::pushError(
                sprintf(
                    esc_html__(
                        'Order %d already has a draft shipment!',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    $orderId
                )
            );
            return false;
        } elseif (
            $orderProcessing === Meta::ORDER_PROCESSING_CREATE_LIVE_SHIPMENTS
            && $shipmentStatus === Order::SHIPMENT_STATUS_LIVE
        ) {
            Session::pushError(
                sprintf(
                    esc_html__(
                        'Order %d already has a live shipment!',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    $orderId
                )
            );
            return false;
        } elseif ($shipmentStatus == Order::SHIPMENT_STATUS_FAILED) {
            Session::pushError(
                sprintf(
                    esc_html__(
                        'Order %d shipment has failed status, please fix error first!',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    $orderId
                )
            );
            return false;
        }

        // Create Shipment
        $success = false;
        try {
            $success = ($orderProcessing === Meta::ORDER_PROCESSING_CREATE_LIVE_SHIPMENTS
                ? Meta::createOrderPackageLiveShipment(
                    $order,
                    $packageIndex,
                    $notify
                )
                : Meta::createOrderPackageDraftShipment(
                    $order,
                    $packageIndex,
                    $notify
                )
            );

            if ($success) {
                if ($notify) {
                    if ($packageIndex >= 0) {
                        Session::pushSuccess(
                            sprintf(
                                esc_html__(
                                    'Successfully created shipment for order %d and package %d',
                                    'oktagon-x-connect-for-woocommerce'
                                ),
                                $orderId,
                                $packageIndex + 1
                            )
                        );
                    } else {
                        Session::pushSuccess(
                            sprintf(
                                esc_html__(
                                    'Successfully created shipment for order %d',
                                    'oktagon-x-connect-for-woocommerce'
                                ),
                                $orderId
                            )
                        );
                    }
                }
                return true;
            } else {
                if ($notify) {
                    if ($packageIndex >= 0) {
                        Session::pushError(
                            sprintf(
                                esc_html__(
                                    'Failed to create shipment for order %d and package %d!',
                                    'oktagon-x-connect-for-woocommerce'
                                ),
                                $orderId,
                                $packageIndex + 1
                            )
                        );
                    } else {
                        Session::pushError(
                            sprintf(
                                esc_html__(
                                    'Failed to create shipment for order %d!',
                                    'oktagon-x-connect-for-woocommerce'
                                ),
                                $orderId
                            )
                        );
                    }
                }
                return false;
            }
        } catch (\Exception $e) {
            if ($notify) {
                if ($packageIndex >= 0) {
                    Session::pushError(
                        sprintf(
                            esc_html__(
                                'Failed to create shipment for order %d and package %d! Error: %s',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            $orderId,
                            $packageIndex + 1,
                            $e->getMessage()
                        )
                    );
                } else {
                    Session::pushError(
                        sprintf(
                            esc_html__(
                                'Failed to create shipment for order %d! Error: %s',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            $orderId,
                            $e->getMessage()
                        )
                    );
                }
            }
            return false;
        }
    }

    public static function generateMergedPdf(
        array $pdfs,
        string $mergedFilename
    ): void {
        $fpdi = new \setasign\Fpdi\Fpdi();
        foreach ($pdfs as $pdf) {
            $pdfFilename = Meta::getShippingLabelFilename(
                $pdf
            );
            $nrOfPagesInPdf = $fpdi->setSourceFile( /* @phpstan-ignore-next-line */
                \setasign\Fpdi\PdfParser\StreamReader::createByString(
                    file_get_contents($pdfFilename)
                )
            );
            $pagesToMerge = range(1, $nrOfPagesInPdf);
            foreach ($pagesToMerge as $pageNr) {
                $template = $fpdi->importPage($pageNr);
                $size = $fpdi->getTemplateSize($template);
                $fpdi->AddPage(
                    $size['width'] > $size['height'] ? 'L' : 'P',
                    [$size['width'], $size['height']]
                );
                $fpdi->useTemplate($template);
            }
        }
        file_put_contents(
            $mergedFilename,
            $fpdi->Output('', 'S')
        );
    }

    public static function generateShipmentData(
        \WC_Order $order,
        int $packageIndex
    ): array {
        $additionalData = [];
        if (
            $additionalOptionsString = Order::getAdditionalOptions(
                $order->get_id(),
                $packageIndex
            )
        ) {
            try {
                $additionalData = json_decode(
                    $additionalOptionsString,
                    true
                );
            } catch (\Exception $e) {
                $additionalData = [];
            }
        }

        $agentId = '';
        if (
            $selectedServicePoint = Order::getSelectedServicePoint(
                $order->get_id(),
                $packageIndex
            )
        ) {
            $agentId = $selectedServicePoint['id'];
        }

        $consignor = [
            'address1' => Meta::getOption('consignor_address1'),
            'address2' => Meta::getOption('consignor_address2'),
            'city' => Meta::getOption('consignor_city'),
            'company' => Meta::getOption('consignor_company'),
            'contactPerson' => Meta::getOption('consignor_contact'),
            'country' => Meta::getOption('consignor_country'),
            'email' => Meta::getOption('consignor_email'),
            'isCompany' => !empty(Meta::getOption('consignor_is_company')),
            'name' => Meta::getOption('consignor_name'),
            'phone' => Meta::getOption('consignor_phone'),
            'state' => Meta::getOption('consignor_state'),
            'vatNumber' => Meta::getOption('consignor_vat_number'),
            'zipCode' => Meta::getOption('consignor_zip_code'),
        ];

        $recipient = [
            'address1' => $order->get_shipping_address_1(),
            'address2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'company' => $order->get_shipping_company(),
            'contactPerson' => sprintf(
                '%s %s',
                $order->get_shipping_first_name(),
                $order->get_shipping_last_name()
            ),
            'country' => $order->get_shipping_country(),
            'email' => $order->get_billing_email(),
            'id' => '',
            'isCompany' => !empty($order->get_shipping_company()),
            'name' => sprintf(
                '%s %s',
                $order->get_shipping_first_name(),
                $order->get_shipping_last_name()
            ),
            'phone' => method_exists($order, 'get_shipping_phone')
                && $order->get_shipping_phone()
                ? $order->get_shipping_phone()
                : $order->get_billing_phone(),
            'state' => $order->get_shipping_state(),
            'vatNumber' => '',
            'zipCode' => $order->get_shipping_postcode(),
        ];

        if ($packageIndex) {
            $reference = sprintf(
                'WC-XCONNECT-%s-%d',
                $order->get_order_number(),
                $packageIndex + 1
            );
        } else {
            $reference = sprintf(
                'WC-XCONNECT-%s',
                $order->get_order_number()
            );
        }

        $parcels = [];
        $customizeParcels = Order::getCustomizeParcels(
            $order->get_id(),
            $packageIndex
        );
        $customParcels = Order::getCustomParcels(
            $order->get_id(),
            $packageIndex
        );
        $calculatedParcels = $customizeParcels && $customParcels
            ? $customParcels
            : Physics::calculateOrderItems($order);
        foreach ($calculatedParcels as $calculatedParcel) {
            $parcels[] = [
                'currency' => (string) $order->get_currency(),
                'description' => $calculatedParcel['description'] ?? '',
                'dimensionUnit' => 'cm',
                'quantity' => 1,
                'unitHeight' => isset($calculatedParcel['height'])
                    ? (int) ($calculatedParcel['height'] * 100) // m -> cm
                    : 0,
                'unitLength' => isset($calculatedParcel['length'])
                    ? (int) ($calculatedParcel['length'] * 100) // m -> cm
                    : 0,
                'unitWeight' => isset($calculatedParcel['weight'])
                    ? (int) ($calculatedParcel['weight'] * 1000) // kg -> g
                    : 0,
                'unitWidth' => isset($calculatedParcel['width'])
                    ? (int) ($calculatedParcel['width'] * 100) // m -> cm
                    : 0,
                'weightUnit' => 'g',
            ];
        }

        $data = [
            'additionalData' => $additionalData,
            'agentId' => $agentId,
            'consignor' => $consignor,
            'parcels' => $parcels,
            'recipient' => $recipient,
            'reference' => $reference,
        ];
        return (array) \apply_filters(
            'wc_oktagon_xconnect_generate_shipment_data',
            $data,
            $order,
            $packageIndex
        );
    }
}
