<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Template
{
    /**
     * @throws \Exception
     */
    public static function processTemplate(
        string $template,
        array $variables = []
    ): string {
        $file = sprintf(
            '%s/templates/%s.phtml',
            dirname(__FILE__),
            $template
        );
        if (file_exists($file)) {
            if (
                !empty($variables)
                && is_array($variables)
            ) {
                foreach ($variables as $key => $value) {
                    ${$key} = $value;
                }
            }
            ob_start();
            include($file);
            if (
                !empty($variables)
                && is_array($variables)
            ) {
                foreach ($variables as $key => $value) {
                    unset(${$key});
                }
            }
            return ob_get_clean();
        } else {
            throw new \Exception(
                sprintf(
                    esc_html__(
                        'Failed to find template: "%s" at: "%s"',
                        'oktagon-x-connect-for-woocommerce'
                    ),
                    $template,
                    $file
                )
            );
        }
    }
}
