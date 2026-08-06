<?php

declare(strict_types=1);

namespace PhpCollective;

class ImplicitCastSpacingExample
{
    public function run(bool $ready, int $count, int $mask, callable $callable, array $items): int
    {
        $not = ! $ready;
        $silenced = @ $callable();
        $negated = - $count;

        $flipped = ~ $mask;
        $validNot = !$ready;
        $validSilenced = @$callable();
        $validNegated = -$count;
        $validSubtraction = $count - $mask;
        $constantMinus = __LINE__ - 1;
        $boolMinus = (int)true - 1;
        $nullMinus = (int)null - 1;
        $fqcnMinus = \PHP_INT_MAX - 1;
        $doubleNegated = - -$count;
        $arrow = fn (): int => - $count;
        $reference = & $items;
        $validReference = &$items;
        $validBitwiseAnd = $count & $mask;

        foreach ($items as & $item) {
            $item = (int)$item;
        }
        unset($item);

        $this->byRef($items);

        return (int)$not + (int)$silenced + $negated + $flipped + (int)$validNot
            + (int)$validSilenced + $validNegated + $validSubtraction
            + $constantMinus + $boolMinus + $nullMinus + $fqcnMinus + $doubleNegated + $arrow()
            + count($reference) + count($validReference) + $validBitwiseAnd;
    }

    /**
     * @param array<int> $items
     *
     * @return void
     */
    protected function byRef(array & $items): void
    {
        $items[] = 1;
    }

    /**
     * @param int ...$args
     *
     * @return void
     */
    protected function byRefVariadic(& ...$args): void
    {
        $args[] = 1;
    }

    /**
     * @return array<int>
     */
    protected function & byRefReturn(): array
    {
        static $items = [];

        return $items;
    }
}
