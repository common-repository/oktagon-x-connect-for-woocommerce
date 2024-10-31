<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Transactions
{
    private static $trimmed = false;

    public static function saveTransaction(
        Curl\Transaction $transaction
    ): int {
        global $wpdb;
        self::autoTrim();
        $tableName = $wpdb->prefix
            . 'oktagon_x_connect_for_woocommerce_api_transactions';

        $requestMethodString = 'GET';
        if ($transaction->getRequestMethod() instanceof Curl\Methods\Delete) {
            $requestMethodString = 'DELETE';
        } elseif ($transaction->getRequestMethod() instanceof Curl\Methods\Post) {
            $requestMethodString = 'POST';
        } elseif ($transaction->getRequestMethod() instanceof Curl\Methods\Put) {
            $requestMethodString = 'PUT';
        }

        $wpdb->insert(
            $tableName,
            [
                'added' =>
                    current_time('mysql'),
                'request_headers' => wp_json_encode(
                    self::maskSensitiveData(
                        $transaction->getRequestHeaders()
                    )
                ),
                'request_body' =>
                    $transaction->getRequestBody(),
                'request_method' =>
                    $requestMethodString,
                'request_uri' =>
                    $transaction->getRequestUri(),
                'response_body' =>
                    $transaction->getResponseBody(),
                'response_headers' => wp_json_encode(
                    self::maskSensitiveData(
                        $transaction->getResponseHeaders()
                    )
                ),
                'response_status_code' =>
                    $transaction->getResponseStatusCode(),
            ]
        );
        return $wpdb->insert_id;
    }

    public static function getTransactionById(
        string $transactionId
    ): ?Curl\Transaction {
        global $wpdb;
        if (!empty($transactionId)) {
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM "
                    . "`{$wpdb->prefix}oktagon_x_connect_for_woocommerce_api_transactions` "
                    . "WHERE transaction_id = %d "
                    . "LIMIT 1",
                    [
                        (int) $transactionId,
                    ]
                ),
                \ARRAY_A
            );
            if (!empty($row)) {
                if ($row['request_method'] === 'DELETE') {
                    $requestMethodObject = new Curl\Methods\Delete();
                } elseif ($row['request_method'] === 'POST') {
                    $requestMethodObject = new Curl\Methods\Post();
                } elseif ($row['request_method'] === 'PUT') {
                    $requestMethodObject = new Curl\Methods\Put();
                } else {
                    $requestMethodObject = new Curl\Methods\Get();
                }

                return new Curl\Transaction(
                    $requestMethodObject,
                    $row['request_uri'],
                    json_decode(
                        $row['request_headers'],
                        true
                    ),
                    $row['request_body'],
                    (int) $row['response_status_code'],
                    json_decode(
                        $row['response_headers'],
                        true
                    ),
                    $row['response_body']
                );
            }
        }
        return null;
    }

    private static function trimTransactions(): void
    {
        global $wpdb;
        $sql = "DELETE FROM `"
            . "{$wpdb->prefix}oktagon_x_connect_for_woocommerce_api_transactions` "
            . "WHERE `added` < NOW() - INTERVAL 30 DAY";
        $wpdb->query($sql);
    }

    private static function maskSensitiveData(array $requestHeaders): array
    {
        $whiteListedKeys = [
            'content-length' => true,
            'content-type' => true,
            'meta-service-name' => true,
        ];
        foreach (array_keys($requestHeaders) as $requestHeaderKey) {
            $formattedRequestHeader = strtolower($requestHeaderKey);
            if (!isset($whiteListedKeys[$formattedRequestHeader])) {
                unset($requestHeaders[$requestHeaderKey]);
            }
        }
        return $requestHeaders;
    }

    private static function autoTrim(): void
    {
        if (!self::$trimmed) {
            self::trimTransactions();
            self::$trimmed = true;
        }
    }
}
