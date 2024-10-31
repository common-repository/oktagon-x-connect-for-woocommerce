<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Lexer
{
    const TOKEN_EOF = 0;
    const TOKEN_NO_MATCH = 1;
    const TOKEN_WEIGHT = 2;
    const TOKEN_SUBTOTAL = 3;
    const TOKEN_FLOAT = 4;
    const TOKEN_INT = 5;
    const TOKEN_LESSER_OR_EQUALS = 6;
    const TOKEN_LESSER_OR_GREATER = 7;
    const TOKEN_LESSER = 8;
    const TOKEN_GREATER_OR_EQUALS = 9;
    const TOKEN_GREATER = 10;
    const TOKEN_AND = 11;
    const TOKEN_OR = 12;
    const TOKEN_EQUALS = 13;
    const TOKEN_SUBTOTAL_POST_DISCOUNTS = 14;

    private string $input;
    private int $index;

    public function __construct(
        string $input
    ) {
        $this->input = $input;
        $this->index = 0;
    }

    public function popToken(): array
    {
        return $this->getToken(true);
    }

    public function peekToken(): array
    {
        return $this->getToken(false);
    }

    /**
     * @throws \Exception
     */
    private function getToken($moveInputHead = false): array
    {
        $input = &$this->input;
        $index = $this->index;

        // Return "EOF" on end of input
        if (empty($input)) {
            return [self::TOKEN_EOF];
        }

        // Use offset
        if ($index) {
            $input = substr($input, $index);
        }

        // Skip white-space
        $matches = [];
        if (
            preg_match(
                '/^[\t ]+/',
                $input,
                $matches
            ) === 1
        ) {
            $input = substr($input, strlen($matches[0]));

            // Return "EOF" on end of input
            if (empty($input)) {
                return [self::TOKEN_EOF];
            }
        }

        $matches = [];
        if (preg_match('/^\$weight/i', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_WEIGHT];
        } elseif (preg_match('/^\$subtotalPostDiscounts/i', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_SUBTOTAL_POST_DISCOUNTS];
        } elseif (preg_match('/^\$subtotal/i', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_SUBTOTAL];
        } elseif (preg_match('/^[0-9]+\.[0-9]+/', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [
                self::TOKEN_FLOAT,
                (float) $matches[0],
            ];
        } elseif (preg_match('/^[0-9]+,[0-9]+/', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [
                self::TOKEN_FLOAT,
                (float) str_replace(',', '.', $matches[0])
            ];
        } elseif (preg_match('/^[0-9]+/', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [
                self::TOKEN_INT,
                (int) $matches[0]
            ];
        } elseif (preg_match('/^<=/', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_LESSER_OR_EQUALS];
        } elseif (preg_match('/^<>/', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_LESSER_OR_GREATER];
        } elseif (preg_match('/^</', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_LESSER];
        } elseif (preg_match('/^>=/', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_GREATER_OR_EQUALS];
        } elseif (preg_match('/^>/', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_GREATER];
        } elseif (preg_match('/^=/', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_EQUALS];
        } elseif (preg_match('/^&/', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_AND];
        } elseif (preg_match('/^\|/', $input, $matches) === 1) {
            if ($moveInputHead) {
                $input = substr(
                    $input,
                    strlen($matches[0])
                );
            }
            return [self::TOKEN_OR];
        } else {
            throw new \Exception(
                sprintf(
                    'Invalid input at %d in "%s"',
                    $index,
                    $input
                )
            );
        }
    }
}
