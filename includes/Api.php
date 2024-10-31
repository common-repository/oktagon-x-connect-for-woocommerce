<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Api
{
    private Curl\Connection $curlConnection;
    private string $username;
    private string $password;
    private array $serviceCredentials;

    /**
     * @throws Curl\Exceptions\InvalidArgument
     * @throws Curl\Exceptions\InvalidEnvironment
     * @throws \Exception
     */
    public function __construct(
        string $username,
        string $password,
        array $serviceCredentials
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->serviceCredentials = $serviceCredentials;
        $this->curlConnection = new Curl\Connection(
            [
                'baseUri' => 'https://shipping.oktagon.se',
            ]
        );
    }

    /**
     * @throws Curl\Exceptions\InvalidArgument
     * @throws Curl\Exceptions\General
     * @throws Curl\Exceptions\Curl
     */
    public function metaGetServices(): Curl\Transaction
    {
        return $this->curlConnection->relativeRequest(
            '/meta-get-services',
            [
                'Authorization' => sprintf(
                    'Basic %s',
                    base64_encode(
                        sprintf(
                            '%s:%s',
                            $this->username,
                            $this->password
                        )
                    )
                )
            ],
            [],
            new Curl\Methods\Get(),
        );
    }

    /**
     * @throws Curl\Exceptions\InvalidArgument
     * @throws Curl\Exceptions\General
     * @throws Curl\Exceptions\Curl
     */
    public function getPickUpAgentsForSpecificRequest(
        string $serviceName,
        string $serviceId,
        array $additionalData,
        string $country,
        string $street,
        string $zipCode
    ): Curl\Transaction {
        $serviceCredentials = !empty($this->serviceCredentials[$serviceName])
            ? $this->serviceCredentials[$serviceName]
            : [];
        return $this->curlConnection->relativeRequest(
            '/get-pick-up-agents-for-specific-request',
            [
                'Authorization' => sprintf(
                    'Basic %s',
                    base64_encode(
                        sprintf(
                            '%s:%s',
                            $this->username,
                            $this->password
                        )
                    )
                ),
                'Meta-Service-Credentials' => base64_encode(
                    json_encode($serviceCredentials)
                ),
                'Meta-Service-Name' => $serviceName,
            ],
            [
                'additionalData' => $additionalData,
                'country' => $country,
                'service' => $serviceId,
                'street' => $street,
                'zipCode' => $zipCode,
            ],
            new Curl\Methods\Get(),
        );
    }

    /**
     * @throws Curl\Exceptions\InvalidArgument
     * @throws Curl\Exceptions\General
     * @throws Curl\Exceptions\Curl
     */
    public function createDraftShipment(
        string $serviceName,
        array $shipmentData
    ): Curl\Transaction {
        $serviceCredentials = !empty($this->serviceCredentials[$serviceName])
            ? $this->serviceCredentials[$serviceName]
            : [];
        return $this->curlConnection->relativeRequest(
            '/create-draft-shipment',
            [
                'Authorization' => sprintf(
                    'Basic %s',
                    base64_encode(
                        sprintf(
                            '%s:%s',
                            $this->username,
                            $this->password
                        )
                    )
                ),
                'Meta-Service-Credentials' => base64_encode(
                    json_encode($serviceCredentials)
                ),
                'Meta-Service-Name' => $serviceName,
            ],
            $shipmentData,
            new Curl\Methods\Post()
        );
    }

    /**
     * @throws Curl\Exceptions\InvalidArgument
     * @throws Curl\Exceptions\General
     * @throws Curl\Exceptions\Curl
     */
    public function createLiveShipment(
        string $serviceName,
        array $shipmentData
    ): Curl\Transaction {
        $serviceCredentials = !empty($this->serviceCredentials[$serviceName])
            ? $this->serviceCredentials[$serviceName]
            : [];
        return $this->curlConnection->relativeRequest(
            '/create-live-shipment',
            [
                'Authorization' => sprintf(
                    'Basic %s',
                    base64_encode(
                        sprintf(
                            '%s:%s',
                            $this->username,
                            $this->password
                        )
                    )
                ),
                'Meta-Service-Credentials' => base64_encode(
                    json_encode($serviceCredentials)
                ),
                'Meta-Service-Name' => $serviceName,
            ],
            $shipmentData,
            new Curl\Methods\Post()
        );
    }

    /**
     * @throws Curl\Exceptions\InvalidArgument
     * @throws Curl\Exceptions\General
     * @throws Curl\Exceptions\Curl
     */
    public function getShippingOptionsInGeneral(
        string $serviceName
    ): Curl\Transaction {
        $serviceCredentials = !empty($this->serviceCredentials[$serviceName])
            ? $this->serviceCredentials[$serviceName]
            : [];
        return $this->curlConnection->relativeRequest(
            '/get-shipping-options-in-general',
            [
                'Authorization' => sprintf(
                    'Basic %s',
                    base64_encode(
                        sprintf(
                            '%s:%s',
                            $this->username,
                            $this->password
                        )
                    )
                ),
                'Meta-Service-Credentials' => base64_encode(
                    json_encode($serviceCredentials)
                ),
                'Meta-Service-Name' => $serviceName,
            ],
            [],
            new Curl\Methods\Get()
        );
    }
}
