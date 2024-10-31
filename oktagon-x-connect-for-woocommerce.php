<?php

// phpcs:disable Generic.Files.LineLength.TooLong
// phpcs:disable PSR1.Files.SideEffects

/**
 * Author URI: https://www.oktagon.se/
 * Author: Oktagon
 * Description: Create shipping options connected with shipping-services, display pick-up points in checkout, create shipments on orders, get shipment shipping labels and track shipments.
 * Domain Path: /languages
 * License URI: https://www.gnu.org/licenses/gpl.html
 * License: GPLv3
 * Namespace: Oktagon\WooCommerce\XConnect
 * Network: true
 * Plugin Name: Oktagon X-Connect for WooCommerce
 * Requires at least: 3.0.0
 * Requires PHP: 7.4
 * Version: 1.0.2
 */

declare(strict_types=1);

'@phan-file-suppress PhanNoopNewNoSideEffects';

if (!defined('ABSPATH')) {
    exit;
}

require_once(ABSPATH . 'wp-admin/includes/plugin.php');

__('Oktagon X-Connect for WooCommerce', 'oktagon-x-connect-for-woocommerce');
__('Create shipping options connected with shipping-services, display pick-up points in checkout, create shipments on orders, get shipment shipping labels and track shipments.', 'oktagon-x-connect-for-woocommerce');
__('Shipping method with connection to a shipping-service.', 'oktagon-x-connect-for-woocommerce');

require_once(__DIR__ . '/vendor/autoload.php');

function oktagon_x_connect_for_woocommerce_plugins_loaded()
{
    new \Oktagon\WooCommerce\XConnect\Ajax();
    new \Oktagon\WooCommerce\XConnect\Meta();
    new \Oktagon\WooCommerce\XConnect\Wordpress();
    new \Oktagon\WooCommerce\XConnect\Woocommerce();
    new \Oktagon\WooCommerce\XConnect\ShippingMethodInit();
}
add_action('plugins_loaded', 'oktagon_x_connect_for_woocommerce_plugins_loaded');

function oktagon_x_connect_for_woocommerce_cant_find_woocommerce()
{
    printf(
        '<div class="notice notice-error"><p>'
        . '<strong>%s ERROR:</strong> <span>%s</span></p></div>',
        (string) esc_html__('Oktagon X-Connect', 'oktagon-x-connect-for-woocommerce'),
        (string) esc_html__(
            "Can't find WooCommerce, is it installed and activated?",
            'oktagon-x-connect-for-woocommerce'
        )
    );
}

// Check if WooCommerce is active (requires wodpress 2.5.0)
$needle = 'woocommerce/woocommerce.php';
if (!\is_plugin_active($needle)) {
    \add_action(
        'admin_notices',
        'oktagon_x_connect_for_woocommerce_cant_find_woocommerce'
    );
}

// Plugin deactivation
function oktagon_x_connect_for_woocommerce_deactivation()
{
    global $wpdb;
    // Uninstall API Transaction Table
    $sql = "DROP TABLE IF EXISTS "
        . "`{$wpdb->prefix}oktagon_x_connect_for_woocommerce_api_transactions`";
    $wpdb->query($sql);
}
\register_deactivation_hook(
    __FILE__,
    'oktagon_x_connect_for_woocommerce_deactivation'
);

// Flag that this plug-in support HPOS
function oktagon_x_connect_for_woocommerce_before_woocommerce_init()
{
    if (class_exists('\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
}
\add_action(
    'before_woocommerce_init',
    'oktagon_x_connect_for_woocommerce_before_woocommerce_init'
);

/**
 * @SuppressWarnings(PHPMD.ExitExpression)
 */
function oktagon_x_connect_for_woocommerce_activation()
{
    global $wpdb;

    // Validate PHP version
    if (
        \version_compare(
            \phpversion(),
            '7.4.0'
        ) < 0
    ) {
        die(
            esc_html__(
                'This plugin requires PHP version 7.4.0+!',
                'oktagon-x-connect-for-woocommerce'
            )
        );
    }

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create API Transaction Table
    $charsetCollate =
        $wpdb->get_charset_collate();
    $sql = "CREATE TABLE `{$wpdb->prefix}oktagon_x_connect_for_woocommerce_api_transactions` (
        transaction_id INT(11) NOT NULL AUTO_INCREMENT,
        request_method VARCHAR(10) NOT NULL,
        request_uri LONGTEXT NOT NULL,
        request_headers LONGTEXT NOT NULL,
        request_body LONGTEXT NOT NULL,
        response_body LONGTEXT NOT NULL,
        response_status_code INT(3) NOT NULL,
        response_headers LONGTEXT NOT NULL,
        added DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (transaction_id)
        ) $charsetCollate;";
    \dbDelta($sql);
}
\register_activation_hook(
    __FILE__,
    'oktagon_x_connect_for_woocommerce_activation'
);
