<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

/**
 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
 */
class Setup
{
    public function execute(): void
    {
        // Setup
        /** @since Wordpress 2.1.0 */
        \wp_register_script(
            'oktagon-x-connect-for-woocommerce-setup-script',
            \plugins_url(
                'includes/assets/setup/js/script.js',
                dirname(__FILE__)
            ),
            ['jquery'],
            '1.0.2'
        );
        /** @since Wordpress 2.1.0 */
        \wp_enqueue_script(
            'oktagon-x-connect-for-woocommerce-setup-script'
        );
        /** @since Wordpress 2.6.0 */
        \wp_enqueue_style(
            'oktagon-x-connect-for-woocommerce-setup-style',
            \plugins_url(
                'includes/assets/setup/css/style.css',
                dirname(__FILE__)
            ),
            [],
            '1.0.2'
        );

        $page = [
            'body' => '',
            'body_after' => '</section>',
            'body_before' => '<section id="oktagon-x-connect-for-woocommerce-setup-body" class="form-table">',
            'message_error' => '',
            'message_error_after' => '</p></div>',
            'message_error_before' => '<div class="notice notice-error"><p>',
            'message_success' => '',
            'message_success_after' => '</p></div>',
            'message_success_before' => '<div class="notice notice-success"><p>',
            'subtitle' => '',
            'subtitle_after' => '</h2>',
            'subtitle_before' => '<h2>',
            'title' => esc_html__(
                'X-Connect Dashboard',
                'oktagon-x-connect-for-woocommerce'
            ),
            'title_after' => '</h1>',
            'title_before' => '<h1>',
            'wrapper_after' => '</div>',
            'wrapper_before' => '<div id="oktagon-x-connect-for-woocommerce-setup-body-wrapper" class="wrap">'
        ];
        if (is_admin()) {
            $route = $this->getRoute();
            $page = $this->dispatch(
                $page,
                $route
            );
        }
        $this->output($page);
        return;
    }

    private function output(
        array $page
    ): void {
        echo wp_kses(
            $this->getOutput($page),
            [
                'a' => ['href' => [], 'target' => []],
                'br' => [],
                'div' => ['class' => [], 'id' => []],
                'fieldset' => ['class' => [], 'id' => []],
                'form' => ['method' => [], 'action' => [], 'class' => []],
                'h1' => ['class' => [], 'id' => []],
                'h2' => ['class' => [], 'id' => []],
                'input' => ['checked' => [], 'class' => [], 'name' => [], 'value' => [], 'type' => []],
                'label' => [],
                'legend' => [],
                'option' => ['selected' => [], 'value' => []],
                'p' => ['class' => [], 'id' => []],
                'section' => ['class' => [], 'id' => []],
                'select' => ['class' => [], 'id' => [], 'name' => []],
                'span' => ['class' => [], 'id' => []],
                'strong' => ['class' => [], 'id' => []],
            ]
        );
        return;
    }

    private function getOutput(
        array $page
    ): string {
        return sprintf(
            '%s%s%s%s%s%s',
            $page['wrapper_before'],
            (!empty($page['title'])
                ? $page['title_before'] . $page['title'] . $page['title_after']
                : ''),
            (!empty($page['message_error'])
                ? $page['message_error_before'] . $page['message_error'] . $page['message_error_after']
                : ''),
            (!empty($page['message_success'])
                ? $page['message_success_before'] . $page['message_success'] . $page['message_success_after']
                : ''),
            (!empty($page['body'])
                ? $page['body_before'] . $page['body'] . $page['body_after']
                : ''),
            $page['wrapper_after']
        );
    }

    private function getRoute(): string
    {
        $route = 'configure';
        if (
            !empty($_GET['route'])
            && is_string($_GET['route'])
        ) {
            $rawRoute =
                sanitize_text_field($_GET['route']);
            if (
                preg_match(
                    '/^[a-zA-Z]+$/',
                    $rawRoute
                ) === 1
            ) {
                $route = $rawRoute;
            }
        }
        return $route;
    }

    private function dispatch(
        array $page,
        string $route
    ): array {
        $methodName = sprintf(
            '%sRoute',
            $route
        );
        if (
            method_exists(
                $this,
                $methodName
            )
        ) {
            $acl = sprintf(
                '%sRouteACL',
                $route
            );
            if (
                !method_exists(
                    $this,
                    $acl
                )
                || $this->$acl()
            ) {
                $page = $this->$methodName(
                    $page,
                    $route
                );
            } else {
                $page['message_error'] = esc_html__(
                    'You lack permissions to access this area!',
                    'oktagon-x-connect-for-woocommerce'
                );
            }
        } else {
            $page['message_error'] = esc_html__(
                'Route does not exist',
                'oktagon-x-connect-for-woocommerce'
            );
        }
        return $page;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @codingStandardsIgnoreStart
     */
    private function indexRoute(
        array $page,
        string $route
    ): array {
        /* @codingStandardsIgnoreStart */
        $pluginData = \get_plugin_data(
            dirname(__DIR__) . '/oktagon-x-connect-for-woocommerce.php',
            false,
            false
        );
        $mailSubject = rawurlencode(
            esc_html__(
                'I need help to get started with my plugin',
                'oktagon-x-connect-for-woocommerce'
            )
        );
        $mailBody = rawurlencode(
            sprintf(
                esc_html__(
                    "Describe what you need help with here:\n",
                    'oktagon-x-connect-for-woocommerce'
                )
            ) . "\n\n"
                . sprintf(
                    "\n\n---\nMy Environment:\nPHP-Version: %s\nPlugin-Version: %s\nWordpress-Version: %s\nPlugin-Name: %s\nDomain: %s",
                    phpversion(),
                    $pluginData['Version'],
                    get_bloginfo('version'),
                    'oktagon-x-connect-for-woocommerce/oktagon-x-connect-for-woocommerce',
                    Meta::getHostname()
                )
        );
        $page['subtitle'] = esc_html__(
            'Get started with your plugin',
            'oktagon-x-connect-for-woocommerce'
        );
        $page['body'] = Template::processTemplate(
            'setup/index',
            [
                'mailBody' =>
                    $mailBody,
                'mailSubject' =>
                    $mailSubject,
                'nextRoute' =>
                    Meta::hasAccount()
                    ? 'configure'
                    : 'account',
            ]
        );
        return $page;
    }

    private function redirect(
        array $page,
        string $url
    ): array {
        $page['subtitle'] = esc_html__(
            'Redirecting..',
            'oktagon-x-connect-for-woocommerce'
        );
        $page['body'] = Template::processTemplate(
            'setup/redirect',
            [
                'url' => $url,
            ]
        );
        return $page;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function configureRoute(
        array $page,
        string $route
    ): array {
        $page['subtitle'] = esc_html__(
            'Get started with the plugin',
            'oktagon-x-connect-for-woocommerce'
        );
        /* @codingStandardsIgnoreStart */
        $pluginData = \get_plugin_data(
            dirname(__DIR__) . '/oktagon-x-connect-for-woocommerce.php',
            false,
            false
        );
        $mailSubject = rawurlencode(
            esc_html__(
                'I need help to get started with my plugin',
                'oktagon-x-connect-for-woocommerce'
            )
        );
        $mailBody = rawurlencode(
            sprintf(
                esc_html__(
                    "Describe what you need help with here:\n",
                    'oktagon-x-connect-for-woocommerce'
                )
            ) . "\n\n"
                . sprintf(
                    "\n\n---\nMy Environment:\nPHP-Version: %s\nPlugin-Version: %s\nWordpress-Version: %s\nPlugin-Name: %s\nDomain: %s",
                    phpversion(),
                    $pluginData['Version'],
                    \get_bloginfo('version'),
                    'oktagon-x-connect-for-woocommerce/oktagon-x-connect-for-woocommerce',
                    Meta::getHostname()
                )
        );

        // License
        $license = Meta::getLicense();
        $licenseUsername = $license['domain'];
        $licensePassword = $license['password'];

        // Service
        $serviceCredentials = Meta::getServiceCredentials();
        $enabledServices = Meta::getEnabledServices();

        // General
        $orderProcessing = Meta::getOption('order_processing');
        $packageDescription = Meta::getOption('package_description', 'categories');
        $stackingDimension = Meta::getOption('stacking_dimension', 'length');
        $minimumPackageWeight = Meta::getOption('minimum_package_weight', '1');
        $isDebug = Meta::getOption('is_debug');
        $allowAnonymousStatistics = Meta::getOption('allow_anonymous_statistics', '1');

        // Automation
        $automationEnabled = (bool) Meta::getOption(
            'automation_enabled',
            ''
        );
        $automationOrderStatus = Meta::getOption(
            'automation_order_status',
            'wc-processing'
        );

        // Checkout
        $maximumServicePointsLimit = Meta::getOption(
            'limit_maximum_service_points',
            '0'
        );
        $adjustOrderReviewDesign = Meta::getOption(
            'adjust_order_review_design',
            '1'
        );

        // Consignor
        $consignorAddress1 = Meta::getOption(
            'consignor_address1',
            ''
        );
        $consignorAddress2 = Meta::getOption(
            'consignor_address2',
            ''
        );
        $consignorCity = Meta::getOption(
            'consignor_city',
            ''
        );
        $consignorCompany = Meta::getOption(
            'consignor_company',
            ''
        );
        $consignorContact = Meta::getOption(
            'consignor_contact',
            ''
        );
        $consignorCountry = Meta::getOption(
            'consignor_country',
            ''
        );
        $consignorEmail = Meta::getOption(
            'consignor_email',
            ''
        );
        $consignorIsCompany = Meta::getOption(
            'consignor_is_company',
            ''
        );
        $consignorName = Meta::getOption(
            'consignor_name',
            ''
        );
        $consignorPhone = Meta::getOption(
            'consignor_phone',
            ''
        );
        $consignorState = Meta::getOption(
            'consignor_state',
            ''
        );
        $consignorVatNumber = Meta::getOption(
            'consignor_vat_number',
            ''
        );
        $consignorZipCode = Meta::getOption(
            'consignor_zip_code',
            ''
        );
        if (empty($consignorCountry)) {
            $defaultLocation =
                \get_option('woocommerce_default_country');
            $senderCountry =
                $defaultLocation;
            if (
                strpos(
                    $defaultLocation,
                    ':'
                ) !== false
            ) {
                $explode = explode(
                    ':',
                    $defaultLocation
                );
                $senderCountry =
                    $explode[0];
            }
            $consignorCountry = $senderCountry;
        }

        // Catch valid nonce
        $validNonce = !empty($_POST['oktagon-x-connect-for-woocommerce-setup-nonce'])
            && \wp_verify_nonce(
                sanitize_text_field($_POST['oktagon-x-connect-for-woocommerce-setup-nonce']),
                'oktagon-x-connect-for-woocommerce-setup-nonce'
        );

        // Did we have a form-submit?
        if (
            $validNonce
            && !empty($_POST)
        ) {
            // License
            $licensePassword =
                !empty($_POST['licensePassword'])
                ? sanitize_text_field($_POST['licensePassword'])
                : '';
            Meta::setOption(
                'license_password',
                $licensePassword
            );

            // Service Credentials
            $serviceCredentialsNew = [];
            if (
                !empty($_POST['service_credentials'])
                && is_array($_POST['service_credentials'])
            ) {
                foreach ($_POST['service_credentials'] as $rawServiceName => $rawServiceData) {
                    $serviceName = sanitize_text_field($rawServiceName);
                    $serviceOptions = [];
                    if (
                        !empty($serviceName)
                        && !empty($rawServiceData)
                        && is_array($rawServiceData)
                    ) {
                        foreach ($rawServiceData as $rawKey => $rawValue) {
                            $key = sanitize_text_field($rawKey);
                            $value = sanitize_text_field($rawValue);
                            $serviceOptions[$key] = $value;
                        }
                    }
                    if ($serviceOptions) {
                        $serviceCredentialsNew[$serviceName] =
                            $serviceOptions;
                    }
                }
            }
            Meta::setOption(
                'service_credentials',
                base64_encode(wp_json_encode($serviceCredentialsNew))
            );
            $serviceCredentials =
                $serviceCredentialsNew;
            $enabledServicesNew =
                !empty($_POST['enabledServices'])
                && is_array($_POST['enabledServices'])
                ? $_POST['enabledServices']
                : [];
            if (
                empty($enabledServicesNew)
                || !is_array($enabledServicesNew)
            ) {
                $enabledServicesNew = [];
            }
            $enabledServices = [];
            foreach ($enabledServicesNew as $service => $isEnabled) {
                if (is_string($service)) {
                    $enabledServices[$service] = !empty($isEnabled);
                }
            }
            Meta::setOption(
                'enabled_services',
                base64_encode(wp_json_encode($enabledServices))
            );

            // General
            $orderProcessing = !empty($_POST['orderProcessing'])
                ? sanitize_text_field($_POST['orderProcessing'])
                : '';
            Meta::setOption(
                'order_processing',
                $orderProcessing
            );

            $packageDescription = !empty($_POST['packageDescription'])
                ? sanitize_text_field($_POST['packageDescription'])
                : '';
            Meta::setOption(
                'package_description',
                $packageDescription
            );

            $stackingDimension = !empty($_POST['stackingDimension'])
                ? sanitize_text_field($_POST['stackingDimension'])
                : '';
            Meta::setOption(
                'stacking_dimension',
                $stackingDimension
            );

            $minimumPackageWeight = (string) floatval(
                str_replace(
                    ',',
                    '.',
                    !empty($_POST['minimumPackageWeight'])
                    ? sanitize_text_field($_POST['minimumPackageWeight'])
                    : ''
                )
            );
            Meta::setOption(
                'minimum_package_weight',
                $minimumPackageWeight
            );

            $isDebug =
                (string) !empty($_POST['isDebug']);
            Meta::setOption(
                'is_debug',
                $isDebug
            );

            $allowAnonymousStatistics =
                (string) !empty($_POST['allowAnonymousStatistics']);
            Meta::setOption(
                'allow_anonymous_statistics',
                $allowAnonymousStatistics
            );

            // Automation
            $automationEnabled =
                (string) !empty($_POST['automationEnabled']);
            Meta::setOption(
                'automation_enabled',
                $automationEnabled
            );
            $automationOrderStatus =
                !empty($_POST['automationOrderStatus'])
                ? sanitize_text_field($_POST['automationOrderStatus'])
                : '';
            Meta::setOption(
                'automation_order_status',
                $automationOrderStatus
            );

            // Consignor
            $consignorAddress1 = !empty($_POST['consignorAddress1'])
                ? sanitize_text_field($_POST['consignorAddress1'])
                : '';
            Meta::setOption(
                'consignor_address1',
                $consignorAddress1
            );
            $consignorAddress2 = !empty($_POST['consignorAddress2'])
                ? sanitize_text_field('consignorAddress2')
                : '';
            Meta::setOption(
                'consignor_address2',
                $consignorAddress2
            );
            $consignorCity = !empty($_POST['consignorCity'])
                ? sanitize_text_field($_POST['consignorCity'])
                : '';
            Meta::setOption(
                'consignor_city',
                $consignorCity
            );
            $consignorCompany = !empty($_POST['consignorCompany'])
                ? sanitize_text_field($_POST['consignorCompany'])
                : '';
            Meta::setOption(
                'consignor_company',
                $consignorCompany
            );
            $consignorContact = !empty($_POST['consignorContact'])
                ? sanitize_text_field($_POST['consignorContact'])
                : '';
            Meta::setOption(
                'consignor_contact',
                $consignorContact
            );
            $consignorCountry = !empty($_POST['consignorCountry'])
                ? sanitize_text_field($_POST['consignorCountry'])
                : '';
            Meta::setOption(
                'consignor_country',
                $consignorCountry
            );
            $consignorEmail = !empty($_POST['consignorEmail'])
                ? sanitize_text_field($_POST['consignorEmail'])
                : '';
            Meta::setOption(
                'consignor_email',
                $consignorEmail
            );
            $consignorIsCompany = (string) !empty($_POST['consignorIsCompany']);
            Meta::setOption(
                'consignor_is_company',
                $consignorIsCompany
            );
            $consignorName = !empty($_POST['consignorName'])
                ? sanitize_text_field($_POST['consignorName'])
                : '';
            Meta::setOption(
                'consignor_name',
                $consignorName
            );
            $consignorPhone = !empty($_POST['consignorPhone'])
                ? sanitize_text_field($_POST['consignorPhone'])
                : '';
            Meta::setOption(
                'consignor_phone',
                $consignorPhone
            );
            $consignorState = !empty($_POST['consignorState'])
                ? sanitize_text_field($_POST['consignorState'])
                : '';
            Meta::setOption(
                'consignor_state',
                $consignorState
            );
            $consignorVatNumber = !empty($_POST['consignorVatNumber'])
                ? sanitize_text_field($_POST['consignorVatNumber'])
                : '';
            Meta::setOption(
                'consignor_vat_number',
                $consignorVatNumber
            );
            $consignorZipCode = !empty($_POST['consignorZipCode'])
                ? sanitize_text_field($_POST['consignorZipCode'])
                : '';
            Meta::setOption(
                'consignor_zip_code',
                $consignorZipCode
            );

            // Checkout
            $maximumServicePointsLimit =
                !empty($_POST['maximumServicePointsLimit'])
                ? (string) intval(sanitize_text_field($_POST['maximumServicePointsLimit']))
                : (string) 0;
            Meta::setOption(
                'limit_maximum_service_points',
                $maximumServicePointsLimit
            );
            $adjustOrderReviewDesign =
                (string) !empty($_POST['adjustOrderReviewDesign']);
            Meta::setOption(
                'adjust_order_review_design',
                $adjustOrderReviewDesign
            );

            $page['message_success'] = (string) esc_html__(
                'Successfully saved settings',
                'oktagon-x-connect-for-woocommerce'
            );

            // Validate all required fields
            if (
                !empty($serviceCredentials)
                && !empty($orderProcessing)
                && !empty($consignorAddress1)
                && !empty($consignorCountry)
                && !empty($consignorZipCode)
            ) {
                if (
                    !empty($_POST['submit'])
                    && $_POST['submit']
                    === esc_html__(
                        'Save and Finish',
                        'oktagon-x-connect-for-woocommerce'
                    )
                ) {
                    return $this->redirect(
                        $page,
                        (string) \admin_url('')
                    );
                }
            } else {
                $page['message_error'] = esc_html__(
                    'Please fill in all required fields.',
                    'oktagon-x-connect-for-woocommerce'
                );
            }
        }

        $services = Meta::getServices();

        // If service credentials are specified, test them
        $isValidCredentials = '-1';
        if (
            !empty($licenseUsername)
            && !empty($licensePassword)
            && !empty($serviceCredentials)
        ) {
            $isValidCredentials = Meta::isValidCredentials(
                [
                    'licensePassword' => $licensePassword,
                    'licenseUsername' => $licenseUsername,
                    'serviceCredentials' => $serviceCredentials,
                ]
            );
        }

        $page['body'] = Template::processTemplate(
            'setup/configure',
            [
                'adjustOrderReviewDesign' => $adjustOrderReviewDesign,
                'allowAnonymousStatistics' => $allowAnonymousStatistics,
                'automationEnabled' => $automationEnabled,
                'automationOrderStatus' => $automationOrderStatus,
                'consignorAddress1' => $consignorAddress1,
                'consignorAddress2' => $consignorAddress2,
                'consignorCity' => $consignorCity,
                'consignorCompany' => $consignorCompany,
                'consignorContact' => $consignorContact,
                'consignorCountry' => $consignorCountry,
                'consignorEmail' => $consignorEmail,
                'consignorIsCompany' => $consignorIsCompany,
                'consignorName' => $consignorName,
                'consignorPhone' => $consignorPhone,
                'consignorState' => $consignorState,
                'consignorVatNumber' => $consignorVatNumber,
                'consignorZipCode' => $consignorZipCode,
                'countries' => \WC()->countries->get_countries(),
                'enabledServices' => $enabledServices,
                'isDebug' => $isDebug,
                'isValidCredentials' => $isValidCredentials,
                'licensePassword' => $licensePassword,
                'licenseUsername' => $licenseUsername,
                'locale' => Meta::getLocale(),
                'mailBody' => $mailBody,
                'mailSubject' => $mailSubject,
                'maximumServicePointsLimit' => $maximumServicePointsLimit,
                'minimumPackageWeight' => $minimumPackageWeight,
                'orderProcessing' => $orderProcessing,
                'orderProcessings' => [
                    Meta::ORDER_PROCESSING_CREATE_DRAFT_SHIPMENTS => esc_html__(
                        'Create panel shipments',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    Meta::ORDER_PROCESSING_CREATE_LIVE_SHIPMENTS => esc_html__(
                        'Create live shipments',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                ],
                'orderStatuses' => \wc_get_order_statuses(),
                'packageDescription' => $packageDescription,
                'packageDescriptions' => [
                    'categories' => esc_html__(
                        'Product Categories',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    'products' => esc_html__(
                        'Product Names',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    'skus' => esc_html__(
                        'Product SKUs',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    'empty' => esc_html__(
                        'Empty',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                ],
                'serviceCredentials' => $serviceCredentials,
                'services' => $services,
                'stackingDimension' => $stackingDimension,
                'stackingDimensions' => [
                    'height' => esc_html__(
                        'Height',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    'length' => esc_html__(
                        'Length',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    'width' => esc_html__(
                        'Width',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                ],
            ]
        );
        return $page;
    }
}
