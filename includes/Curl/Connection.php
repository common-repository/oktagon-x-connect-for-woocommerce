<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect\Curl;

class Connection extends Base
{
    private string $baseUri = '';

    /**
     * @throws Exceptions\InvalidArgument
     */
    public function __construct(
        array $configuration
    ) {
        if (!$this->isValidConfiguration($configuration)) {
            throw new Exceptions\InvalidArgument(
                sprintf(
                    'Invalid configuration for connection: "%s"!',
                    $this->exportValue($configuration)
                )
            );
        }
        $this->baseUri = $configuration['baseUri'];
    }

    /**
     * @throws Exceptions\InvalidArgument
     * @throws Exceptions\General
     * @throws Exceptions\Curl
     */
    public function relativeRequest(
        string $relativeRequestUri,
        array $requestHeaders,
        array $requestBody,
        Method $requestMethod,
        bool $requestBodyAsJson = true
    ): Transaction {
        return $this->absoluteRequest(
            $this->baseUri . $relativeRequestUri,
            $requestHeaders,
            $requestBody,
            $requestMethod,
            $requestBodyAsJson,
        );
    }

    /**
     * @throws Exceptions\InvalidArgument
     * @throws Exceptions\General
     * @throws Exceptions\Curl
     */
    public function absoluteRequest(
        string $absoluteRequestUri,
        array $requestHeaders,
        array $requestBody,
        Method $requestMethod,
        bool $requestBodyAsJson = true
    ): Transaction {
        $responseHeaders = [];

        // Argument validation
        if (!$this->isValidRequestUri($absoluteRequestUri)) {
            throw new Exceptions\InvalidArgument(
                sprintf(
                    'Invalid request URI "%s"!',
                    $this->exportValue($absoluteRequestUri)
                )
            );
        }
        if (!$this->isValidRequestBody($requestBody)) {
            throw new Exceptions\InvalidArgument(
                sprintf(
                    'Invalid request body "%s"!',
                    $this->exportValue($requestBody)
                )
            );
        }

        $requestUri = $absoluteRequestUri;
        if ($requestMethod instanceof Methods\Get) {
            if (!empty($requestBody)) {
                $encodedBody = $this->encodeData($requestBody);
                $requestUri .= '?' . $encodedBody;
            }
            $requestBody = '';
        }

        $wpRemoteGetArgs = [
            'timeout' => 10., // 10 seconds
        ];

        if ($requestMethod instanceof Methods\Delete) {
            $wpRemoteGetArgs['method'] = 'DELETE';
        } elseif ($requestMethod instanceof Methods\Get) {
            $wpRemoteGetArgs['method'] = 'GET';
        } elseif ($requestMethod instanceof Methods\Post) {
            $wpRemoteGetArgs['method'] = 'POST';
        } elseif ($requestMethod instanceof Methods\Put) {
            $wpRemoteGetArgs['method'] = 'PUT';
        }
        if (
            $requestMethod instanceof Methods\Delete
            || $requestMethod instanceof Methods\Post
            || $requestMethod instanceof Methods\Put
        ) {
            if (!empty($requestBody)) {
                if ($requestBodyAsJson) {
                    $requestHeaders['content-type'] =
                        'application/json';
                    $requestBody =
                        wp_json_encode($requestBody);
                } else {
                    $requestBody =
                        $this->encodeData($requestBody);
                }
            } else {
                $requestBody = '';
            }
        }

        $wpRemoteGetArgs['body'] = $requestBody;

        if (!empty($requestHeaders)) {
            $wpRemoteGetArgs['headers'] = $requestHeaders;
        }

        // Perform request
        $responseBody = '';
        $responseHeaders = [];
        $responseStatusCode = 0;
        try {
            $response = \wp_remote_get(
                $requestUri,
                $wpRemoteGetArgs
            );
            if (
                !is_array($response)
                || \is_wp_error($response)
            ) {
                throw new Exceptions\Curl(
                    sprintf(
                        __(
                            'Unexpected response: %s',
                            'oktagon-x-connect-for-woocommerce'
                        ),
                        var_export($response, true)
                    )
                );
            }
        } catch (\Throwable $e) {
            throw new Exceptions\General(
                $e->getMessage()
            );
        }

        $responseBody = \wp_remote_retrieve_body(
            $response
        );
        $responseStatusCode = \wp_remote_retrieve_response_code(
            $response
        );

        // Collect response-headers
        if (
            !empty($response)
            && is_array($response)
            && !empty($response['headers'])
            && is_array($response['headers'])
        ) {
            foreach ($response['headers'] as $key => $value) {
                $responseHeaders[strtolower(trim($key))] =
                    trim($value);
            }
        }

        return new Transaction(
            $requestMethod,
            $requestUri,
            $requestHeaders,
            $requestBody,
            $responseStatusCode,
            $responseHeaders,
            $responseBody
        );
    }

    private function isValidConfiguration(
        array $configuration
    ): bool {
        if (
            !$this->isValidNonEmptyAssociativeArray(
                $configuration
            )
        ) {
            return false;
        }
        if (
            !isset($configuration['baseUri'])
            || !$this->isValidNonEmptyString(
                $configuration['baseUri']
            )
        ) {
            return false;
        }
        return true;
    }

    /**
     * @param mixed $uri
     */
    private function isValidRequestUri(
        $uri
    ): bool {
        if (
            empty($uri)
            || !is_string($uri)
        ) {
            return false;
        }
        return true;
    }

    private function isValidRequestBody(
        array $requestBody
    ): bool {
        return $this->isValidArray($requestBody);
    }

    private function encodeData(
        array $data
    ): string {
        return http_build_query($data);
    }
}
