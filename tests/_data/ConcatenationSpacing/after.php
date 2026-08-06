<?php

declare(strict_types=1);

namespace PhpCollective;

class ConcatenationSpacingExample
{
    public function render(string $first, string $second): string
    {
        $missingBefore = $first . 'b';
        $missingAfter = $first . 'b';
        $tooManyBefore = $first . 'b';
        $tooManyAfter = $first . 'b';

        $valid = $first . 'b';
        $multiline = $first
            . $second;

        return $missingBefore . $missingAfter . $tooManyBefore . $tooManyAfter . $valid . $multiline;
    }
}
