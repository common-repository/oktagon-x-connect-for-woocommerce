<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect\Curl;

class Transaction extends Base
{
    private Method $requestMethod;

    private string $requestUri;

    /**
     * Numerical indexed array.
     */
    private array $requestHeaders;

    private string $requestBody;

    /**
     * @var array|null
     */
    private array $requestBodyDecoded;

    private int $responseStatusCode;

    /**
     * Associative indexed array.
     *
     * @var array
     */
    private array $responseHeaders;

    private string $responseBody;

    /**
     * @var array|null
     */
    private array $responseBodyDecoded;

    /**
     * @throws Exceptions\InvalidArgument
     */
    public function __construct(
        Method $requestMethod,
        string $requestUri,
        array $requestHeaders,
        string $requestBody,
        int $responseStatusCode,
        array $responseHeaders,
        string $responseBody
    ) {
        if (
            $this->isValidRequestUri(
                $requestUri
            )
        ) {
            $this->requestUri =
                $requestUri;
        } else {
            throw new Exceptions\InvalidArgument(
                sprintf(
                    'Invalid request uri "%s"!',
                    $this->exportValue($requestUri)
                )
            );
        }
        if (
            $this->isValidRequestHeaders(
                $requestHeaders
            )
        ) {
            $this->requestHeaders =
                $requestHeaders;
        } else {
            throw new Exceptions\InvalidArgument(
                sprintf(
                    'Invalid request headers "%s"!',
                    $this->exportValue($requestHeaders)
                )
            );
        }
        if (
            $this->isValidRequestBody(
                $requestBody
            )
        ) {
            $this->requestBody =
                $requestBody;
        } else {
            throw new Exceptions\InvalidArgument(
                sprintf(
                    'Invalid request body "%s"!',
                    $this->exportValue($requestBody)
                )
            );
        }
        if (
            $this->isValidResponseStatusCode(
                $responseStatusCode
            )
        ) {
            $this->responseStatusCode =
                $responseStatusCode;
        } else {
            throw new Exceptions\InvalidArgument(
                sprintf(
                    'Invalid response status code "%s"!',
                    $this->exportValue($responseStatusCode)
                )
            );
        }
        if (
            $this->isValidResponseHeaders(
                $responseHeaders
            )
        ) {
            $this->responseHeaders =
                $responseHeaders;
        } else {
            throw new Exceptions\InvalidArgument(
                sprintf(
                    'Invalid response headers "%s"!',
                    $this->exportValue($responseHeaders)
                )
            );
        }
        if (
            $this->isValidResponseBody(
                $responseBody
            )
        ) {
            $this->responseBody =
                $responseBody;
        } else {
            throw new Exceptions\InvalidArgument(
                sprintf(
                    'Invalid response body "%s"!',
                    $this->exportValue($responseBody)
                )
            );
        }
        $this->requestMethod = $requestMethod;
    }

    /**
     * @param mixed $requestBody
     */
    private function isValidRequestBody(
        $requestBody = null
    ): bool {
        return $this->isValidString($requestBody);
    }

    /**
     * @param mixed $requestHeaders
     */
    public function isValidRequestHeaders(
        $requestHeaders = null
    ): bool {
        return $this->isValidAssociativeArray(
            $requestHeaders
        );
    }

    /**
     * @param mixed $responseHeaders
     */
    private function isValidResponseHeaders(
        $responseHeaders = null
    ): bool {
        return $this->isValidAssociativeArray(
            $responseHeaders
        );
    }

    /**
     * @param mixed $responseBody
     */
    private function isValidResponseBody(
        $responseBody = null
    ): bool {
        return $this->isValidString($responseBody);
    }

    /**
     * @param mixed $responseStatusCode
     */
    private function isValidResponseStatusCode(
        $responseStatusCode = null
    ): bool {
        return $this->isValidNonEmptyInt(
            $responseStatusCode
        );
    }

    public function getRequestHeaders(): array
    {
        return $this->requestHeaders;
    }

    public function getRequestMethod(): Method
    {
        return $this->requestMethod;
    }

    public function getRequestUri(): string
    {
        return $this->requestUri;
    }

    public function getRequestBody(): string
    {
        return $this->requestBody;
    }

    public function getResponseStatusCode(): int
    {
        return $this->responseStatusCode;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function getResponseBodyDecoded(): array
    {
        if (!isset($this->responseBodyDecoded)) {
            try {
                $this->responseBodyDecoded = json_decode(
                    $this->responseBody,
                    true,
                    512,
                    \JSON_THROW_ON_ERROR
                );
            } catch (\Exception $e) {
                $this->responseBodyDecoded = [];
            }
        }
        return $this->responseBodyDecoded;
    }

    public function getRequestBodyDecoded(): array
    {
        if (!isset($this->requestBodyDecoded)) {
            try {
                $this->requestBodyDecoded = json_decode(
                    $this->requestBody,
                    true
                );
            } catch (\Exception $e) {
                $this->requestBodyDecoded = [];
            }
        }
        return $this->requestBodyDecoded;
    }

    public function getResponseHeaders(): array
    {
        return $this->responseHeaders;
    }

    public function __toString(): string
    {
        return var_export(
            $this,
            true
        );
    }

    private function isValidRequestUri(
        $uri
    ): bool {
        return !empty($uri);
    }
}
