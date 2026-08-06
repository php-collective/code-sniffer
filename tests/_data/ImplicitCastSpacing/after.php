<?php

declare(strict_types=1);

namespace PhpCollective;

class ImplicitCastSpacingExample
{
    public function run(bool $ready, int $count, int $mask, callable $callable): int
    {
        $not = !$ready;
        $silenced = @$callable();
        $negated = -$count;

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
        $arrow = fn (): int => -$count;

        return (int)$not + (int)$silenced + $negated + $flipped + (int)$validNot
            + (int)$validSilenced + $validNegated + $validSubtraction
            + $constantMinus + $boolMinus + $nullMinus + $fqcnMinus + $doubleNegated + $arrow();
    }
}
