<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Cache
{
    const CACHE_EXPIRATION = 60 * 60; // 1 hour
    const CACHE_PREFIX = 'oktagon-x-connect-for-woocommerce';

    public static function save(
        array $data,
        string $key,
        int $expiration = self::CACHE_EXPIRATION
    ): bool {
        if (!empty($key)) {
            $path = self::getCachePath($key);
            try {
                $response = \set_transient(
                    $path,
                    $data,
                    $expiration
                );
                if ($response) {
                    Meta::log(
                        sprintf(
                            esc_html__(
                                'Saved cache for key "%s" into "%s"',
                                'oktagon-x-connect-for-woocommerce'
                            ),
                            $key,
                            $path
                        )
                    );
                    return true;
                } else {
                    Meta::log(
                        sprintf(
                            esc_html__(
                                'Failed to save cache!',
                                'oktagon-x-connect-for-woocommerce'
                            )
                        )
                    );
                }
            } catch (\Exception $e) {
                Meta::log(
                    sprintf(
                        esc_html__(
                            'Failed to save cache, error: "%s"',
                            'oktagon-x-connect-for-woocommerce'
                        ),
                        $e->getMessage()
                    )
                );
            }
        }
        return false;
    }

    public static function test(
        string $key
    ): bool {
        return !empty($key) && self::load($key);
    }

    public static function load(
        string $key
    ): array {
        $path = self::getCachePath($key);
        $data = \get_transient($path);
        if (
            !empty($data)
            && is_array($data)
        ) {
            return $data;
        }
        return [];
    }

    private static function getCachePath(
        string $key
    ): string {
        $path = sprintf(
            '%s_%s',
            self::CACHE_PREFIX,
            $key
        );
        if (strlen($path) > 172) {
            /**
             * $transient parameter should be 172 characters or less in length as
             * WordPress will prefix your name with “_transient_” or “_transient_timeout_”
             * in the options table (depending on whether it expires or not).
             * Longer key names will silently fail. See Trac #15058.
             */
            $path = substr(
                $path,
                0,
                172
            );
        }
        return $path;
    }
}
