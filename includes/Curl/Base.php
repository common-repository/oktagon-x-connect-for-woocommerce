<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect\Curl;

abstract class Base
{
    /**
     * @param mixed $value
     */
    protected function exportValue(
        $value
    ): string {
        if (
            is_array($value)
            || is_object($value)
        ) {
            return print_r(
                $value,
                true
            );
        }
        return $value;
    }

    /**
     * @param mixed $bool
     */
    protected function isBool(
        $bool
    ): bool {
        return is_bool($bool);
    }

    /**
     * @param mixed $array
     */
    protected function isValidArray(
        $array
    ): bool {
        return is_array($array);
    }

    /**
     * @param mixed $array
     */
    protected function isValidNumericalArray(
        $array
    ): bool {
        if (!$this->isValidArray($array)) {
            return false;
        }
        foreach (array_keys($array) as $key) {
            if (!$this->isValidInt($key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param mixed $array
     */
    public function isValidNonEmptyNumericalArray(
        $array
    ): bool {
        return $this->isValidNumericalArray($array)
            && !empty($array);
    }

    /**
     * @param mixed $array
     */
    protected function isValidNonEmptyAssociativeArray(
        $array
    ): bool {
        return
            $this->isValidAssociativeArray($array)
            && !empty($array);
    }

    /**
     * @param mixed $string
     */
    protected function isValidNonEmptyString(
        $string
    ): bool {
        return $this->isValidString($string)
            && !empty($string);
    }

    /**
     * @param mixed $array
     */
    protected function isValidAssociativeArray(
        $array
    ): bool {
        if (!$this->isValidArray($array)) {
            return false;
        }
        if (empty($array)) {
            return true;
        }
        foreach (array_keys($array) as $key) {
            if ($this->isValidString($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param mixed $string
     */
    protected function isValidString(
        $string
    ): bool {
        return is_string($string);
    }

    /**
     * @param mixed $int
     */
    protected function isValidInt(
        $int
    ): bool {
        return isset($int)
            && is_int($int);
    }

    /**
     * @param mixed $int
     */
    protected function isValidNonEmptyInt(
        $int
    ): bool {
        return $this->isValidInt($int)
            && !empty($int);
    }
}
