<?php

declare(strict_types=1);

namespace Oktagon\WooCommerce\XConnect;

class Parser
{
    private Lexer $lexer;
    private string $input = '';
    private static array $sdtTables = [];

    const ACTION_ACCEPT = 0;
    const ACTION_REDUCE = 1;
    const ACTION_SHIFT = 2;

    const ACTION_TABLES = [
        0 => [
            Lexer::TOKEN_EOF => self::ACTION_ACCEPT,
            Lexer::TOKEN_FLOAT => self::ACTION_SHIFT,
            Lexer::TOKEN_INT => self::ACTION_SHIFT,
            Lexer::TOKEN_WEIGHT => self::ACTION_SHIFT,
            Lexer::TOKEN_SUBTOTAL => self::ACTION_SHIFT,
            Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS => self::ACTION_SHIFT,
        ],
        1 => [
            Lexer::TOKEN_LESSER_OR_EQUALS => self::ACTION_SHIFT,
            Lexer::TOKEN_LESSER_OR_GREATER => self::ACTION_SHIFT,
            Lexer::TOKEN_LESSER => self::ACTION_SHIFT,
            Lexer::TOKEN_GREATER_OR_EQUALS => self::ACTION_SHIFT,
            Lexer::TOKEN_GREATER => self::ACTION_SHIFT,
            Lexer::TOKEN_EQUALS => self::ACTION_SHIFT,
        ],
        2 => [
            Lexer::TOKEN_FLOAT => self::ACTION_SHIFT,
            Lexer::TOKEN_INT => self::ACTION_SHIFT,
            Lexer::TOKEN_WEIGHT => self::ACTION_SHIFT,
            Lexer::TOKEN_SUBTOTAL => self::ACTION_SHIFT,
            Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS => self::ACTION_SHIFT,
        ],
        3 => [
            Lexer::TOKEN_EOF => [self::ACTION_REDUCE, 3],
            Lexer::TOKEN_AND => [self::ACTION_REDUCE, 3],
            Lexer::TOKEN_OR => [self::ACTION_REDUCE, 3],
        ],
        4 => [
            Lexer::TOKEN_AND => self::ACTION_SHIFT,
            Lexer::TOKEN_OR => self::ACTION_SHIFT,
        ],
        5 => [
            Lexer::TOKEN_FLOAT => self::ACTION_SHIFT,
            Lexer::TOKEN_INT => self::ACTION_SHIFT,
            Lexer::TOKEN_WEIGHT => self::ACTION_SHIFT,
            Lexer::TOKEN_SUBTOTAL => self::ACTION_SHIFT,
            Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS => self::ACTION_SHIFT,
        ],
        6 => [
            Lexer::TOKEN_LESSER_OR_EQUALS => self::ACTION_SHIFT,
            Lexer::TOKEN_LESSER_OR_GREATER => self::ACTION_SHIFT,
            Lexer::TOKEN_LESSER => self::ACTION_SHIFT,
            Lexer::TOKEN_GREATER_OR_EQUALS => self::ACTION_SHIFT,
            Lexer::TOKEN_GREATER => self::ACTION_SHIFT,
            Lexer::TOKEN_EQUALS => self::ACTION_SHIFT,
        ],
        7 => [
            Lexer::TOKEN_FLOAT => self::ACTION_SHIFT,
            Lexer::TOKEN_INT => self::ACTION_SHIFT,
            Lexer::TOKEN_WEIGHT => self::ACTION_SHIFT,
            Lexer::TOKEN_SUBTOTAL => self::ACTION_SHIFT,
            Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS => self::ACTION_SHIFT,
        ],
        8 => [
            Lexer::TOKEN_EOF => [self::ACTION_REDUCE, 4],
            Lexer::TOKEN_AND => [self::ACTION_REDUCE, 4],
            Lexer::TOKEN_OR => [self::ACTION_REDUCE, 4],
        ],
    ];
    const GOTO_TABLES = [
        0 => [
            Lexer::TOKEN_FLOAT => 1,
            Lexer::TOKEN_INT => 1,
            Lexer::TOKEN_WEIGHT => 1,
            Lexer::TOKEN_SUBTOTAL => 1,
            Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS => 1,
        ],
        1 => [
            Lexer::TOKEN_EQUALS => 2,
            Lexer::TOKEN_GREATER => 2,
            Lexer::TOKEN_GREATER_OR_EQUALS => 2,
            Lexer::TOKEN_LESSER => 2,
            Lexer::TOKEN_LESSER_OR_EQUALS => 2,
            Lexer::TOKEN_LESSER_OR_GREATER => 2,
        ],
        2 => [
            Lexer::TOKEN_FLOAT => 3,
            Lexer::TOKEN_INT => 3,
            Lexer::TOKEN_WEIGHT => 3,
            Lexer::TOKEN_SUBTOTAL => 3,
            Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS => 3,
        ],
        3 => [
            Lexer::TOKEN_EOF => 0,
            Lexer::TOKEN_AND => 4,
            Lexer::TOKEN_OR => 4,
        ],
        4 => [
            Lexer::TOKEN_AND => 5,
            Lexer::TOKEN_OR => 5,
        ],
        5 => [
            Lexer::TOKEN_FLOAT => 6,
            Lexer::TOKEN_INT => 6,
            Lexer::TOKEN_WEIGHT => 6,
            Lexer::TOKEN_SUBTOTAL => 6,
            Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS => 6,
        ],
        6 => [
            Lexer::TOKEN_EQUALS => 7,
            Lexer::TOKEN_GREATER => 7,
            Lexer::TOKEN_GREATER_OR_EQUALS => 7,
            Lexer::TOKEN_LESSER => 7,
            Lexer::TOKEN_LESSER_OR_EQUALS => 7,
            Lexer::TOKEN_LESSER_OR_GREATER => 7,
        ],
        7 => [
            Lexer::TOKEN_FLOAT => 8,
            Lexer::TOKEN_INT => 8,
            Lexer::TOKEN_WEIGHT => 8,
            Lexer::TOKEN_SUBTOTAL => 8,
            Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS => 8,
        ],
        8 => [
            Lexer::TOKEN_EOF => 0,
            Lexer::TOKEN_AND => 4,
            Lexer::TOKEN_OR => 4,
        ],
    ];

    public function __construct(
        string $input
    ) {
        $this->input = $input;
        self::$sdtTables = [
            3 => function (
                bool $output,
                array $args,
                float $weight,
                float $subtotal,
                float $subtotalPostDiscounts
            ): bool {

                $aToken = $args[2];
                switch ($aToken[0]) {
                    case Lexer::TOKEN_FLOAT:
                    case Lexer::TOKEN_INT:
                        $aToken = (float) $aToken[1];
                        break;
                    case Lexer::TOKEN_WEIGHT:
                        $aToken = $weight;
                        break;
                    case Lexer::TOKEN_SUBTOTAL:
                        $aToken = $subtotal;
                        break;
                    case Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS:
                        $aToken = $subtotalPostDiscounts;
                        break;
                    default:
                        throw new \Exception(
                            sprintf(
                                'Unexpected a token "%s"!',
                                var_export($aToken, true)
                            )
                        );
                }

                $bToken = $args[0];
                switch ($bToken[0]) {
                    case Lexer::TOKEN_FLOAT:
                    case Lexer::TOKEN_INT:
                        $bToken = (float) $bToken[1];
                        break;
                    case Lexer::TOKEN_WEIGHT:
                        $bToken = $weight;
                        break;
                    case Lexer::TOKEN_SUBTOTAL:
                        $bToken = $subtotal;
                        break;
                    case Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS:
                        $bToken = $subtotalPostDiscounts;
                        break;
                    default:
                        throw new \Exception(
                            sprintf(
                                'Unexpected b token "%s"!',
                                var_export($bToken, true)
                            )
                        );
                }

                $local = true;
                $operatorToken = $args[1];
                switch ($operatorToken[0]) {
                    case Lexer::TOKEN_LESSER_OR_EQUALS:
                        $local = $aToken <= $bToken;
                        break;
                    case Lexer::TOKEN_LESSER_OR_GREATER:
                        $local = $aToken <> $bToken;
                        break;
                    case Lexer::TOKEN_LESSER:
                        $local = $aToken < $bToken;
                        break;
                    case Lexer::TOKEN_GREATER_OR_EQUALS:
                        $local = $aToken >= $bToken;
                        break;
                    case Lexer::TOKEN_GREATER:
                        $local = $aToken > $bToken;
                        break;
                    case Lexer::TOKEN_EQUALS:
                        $local = $aToken === $bToken;
                        break;
                    default:
                        throw new \Exception(
                            sprintf(
                                'Unexpected operator token "%s"!',
                                var_export($operatorToken, true)
                            )
                        );
                }

                return $output && $local;
            },
            8 => function (
                bool $output,
                array $args,
                float $weight,
                float $subtotal,
                float $subtotalPostDiscounts
            ): bool {

                $aToken = $args[2];
                switch ($aToken[0]) {
                    case Lexer::TOKEN_FLOAT:
                    case Lexer::TOKEN_INT:
                        $aToken = (float) $aToken[1];
                        break;
                    case Lexer::TOKEN_WEIGHT:
                        $aToken = $weight;
                        break;
                    case Lexer::TOKEN_SUBTOTAL:
                        $aToken = $subtotal;
                        break;
                    case Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS:
                        $aToken = $subtotalPostDiscounts;
                        break;
                    default:
                        throw new \Exception(
                            sprintf(
                                'Unexpected a token "%s"!',
                                var_export($aToken, true)
                            )
                        );
                }

                $bToken = $args[0];
                switch ($bToken[0]) {
                    case Lexer::TOKEN_FLOAT:
                    case Lexer::TOKEN_INT:
                        $bToken = (float) $bToken[1];
                        break;
                    case Lexer::TOKEN_WEIGHT:
                        $bToken = $weight;
                        break;
                    case Lexer::TOKEN_SUBTOTAL:
                        $bToken = $subtotal;
                        break;
                    case Lexer::TOKEN_SUBTOTAL_POST_DISCOUNTS:
                        $bToken = $subtotalPostDiscounts;
                        break;
                    default:
                        throw new \Exception(
                            sprintf(
                                'Unexpected b token "%s"!',
                                var_export($bToken, true)
                            )
                        );
                }

                $local = true;
                $operatorToken = $args[1];
                switch ($operatorToken[0]) {
                    case Lexer::TOKEN_LESSER_OR_EQUALS:
                        $local = $aToken <= $bToken;
                        break;
                    case Lexer::TOKEN_LESSER_OR_GREATER:
                        $local = $aToken <> $bToken;
                        break;
                    case Lexer::TOKEN_LESSER:
                        $local = $aToken < $bToken;
                        break;
                    case Lexer::TOKEN_GREATER_OR_EQUALS:
                        $local = $aToken >= $bToken;
                        break;
                    case Lexer::TOKEN_GREATER:
                        $local = $aToken > $bToken;
                        break;
                    case Lexer::TOKEN_EQUALS:
                        $local = $aToken === $bToken;
                        break;
                    default:
                        throw new \Exception(
                            sprintf(
                                'Unexpected operator token "%s"!',
                                var_export($operatorToken, true)
                            )
                        );
                }

                $op2 = $args[3];
                switch ($op2[0]) {
                    case Lexer::TOKEN_AND:
                        return $output && $local;
                    case Lexer::TOKEN_OR:
                        return $output || $local;
                    default:
                        throw new \Exception(
                            sprintf(
                                'Unexpected operator "%s"!',
                                var_export($op2, true)
                            )
                        );
                }
            },
        ];
    }

    /**
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UndefinedVariable)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function evaluate(
        float $weight = 0.0,
        float $subtotal = 0.0,
        float $subtotalPostDiscounts = 0.0
    ): bool {
        $this->lexer = new Lexer($this->input);
        $stillOpen = true;
        $state = 0;
        $stack = [];
        $output = true;
        while ($stillOpen) {
            $token = $this->lexer->peekToken();
            $tokenType = $token[0];
            $actionTable = self::ACTION_TABLES[$state];
            if (!isset($actionTable[$tokenType])) {
                throw new \Exception(
                    sprintf(
                        'Unexpected token "%s" in state %s',
                        var_export($token, true),
                        var_export($state, true)
                    )
                );
            }

            $action = $actionTable[$tokenType];
            $actionType = is_array($action) ? $action[0] : $action;
            $goto = self::GOTO_TABLES[$state][$tokenType] ?? false;
            switch ($actionType) {
                case self::ACTION_SHIFT:
                    $stack[] = $this->lexer->popToken();
                    if ($goto !== false) {
                        $state = $goto;
                    }
                    break;
                case self::ACTION_REDUCE:
                    $reduceCount = $action[1];
                    $args = [];
                    for ($i = 0; $i < $reduceCount; $i++) {
                        $args[] = array_pop($stack);
                    }

                    if (isset(self::$sdtTables[$state])) {
                        $output = call_user_func(
                            self::$sdtTables[$state],
                            $output,
                            $args,
                            $weight,
                            $subtotal,
                            $subtotalPostDiscounts
                        );
                    }

                    if ($goto !== false) {
                        $state = $goto;
                    } else {
                        throw new \Exception(
                            sprintf(
                                'Nowhere to go after reduce in state %s!',
                                var_export($state, true)
                            )
                        );
                    }

                    break;
                case self::ACTION_ACCEPT:
                    $stillOpen = false;
                    break;
            }
        }

        return $output;
    }
}
