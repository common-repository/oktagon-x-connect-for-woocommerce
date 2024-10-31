<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Ajax
{
    public function __construct()
    {
        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-get-shipping-options-in-general',
            [
                $this,
                'getShippingOptionsInGeneral'
            ]
        );
        \add_action(
            'wp_ajax_oktagon-x-connect-for-woocommerce-validate-code',
            [
                $this,
                'validateCode'
            ]
        );
    }

    public function validateCode(): void
    {
        $code = !empty($_POST['code'])
            ? sanitize_text_field($_POST['code'])
            : '';
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        $error = '';
        if (
            !\wp_verify_nonce(
                $nonce,
                'woocommerce-settings'
            )
        ) {
            $error = 'Invalid nonce!';
        }
        $valid = false;
        if (!$error) {
            try {
                $parser = new Parser($code);
                $valid = true;
                $parser->evaluate();
            } catch (\Exception $e) {
                $valid = false;
                $error = $e->getMessage();
            }
        }
        echo wp_json_encode([
            'error' => $error,
            'valid' => $valid,
        ]);
        \wp_die();
    }

    public function getShippingOptionsInGeneral(): void
    {
        $nonce = !empty($_POST['nonce'])
            ? sanitize_text_field($_POST['nonce'])
            : '';
        $locale = '';
        $error = '';
        $options = [];
        if (
            !\wp_verify_nonce(
                $nonce,
                'woocommerce-settings'
            )
        ) {
            $error = 'Invalid nonce!';
        }
        if (!$error) {
            $locale = Meta::getLocale();
            $options = Meta::getShippingOptionsInGeneral();
        }
        echo wp_json_encode([
            'error' => $error,
            'locale' => $locale,
            'options' => $options,
        ]);
        \wp_die();
    }
}
