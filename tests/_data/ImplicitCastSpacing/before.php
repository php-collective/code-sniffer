<?php

declare(strict_types=1);

namespace PhpCollective;

class ImplicitCastSpacingExample
{
    public function run(bool $ready, int $count, int $mask, callable $callable): int
    {
        $not = ! $ready;
        $silenced = @ $callable();
        $flipped = ~ $mask;
        ++ $count;
        $count --;

        $validNot = !$ready;
        $validSilenced = @$callable();
        ++$count;
        $count++;

        return (int)$not + (int)$silenced + $flipped + $count + (int)$validNot + (int)$validSilenced;
    }
}
