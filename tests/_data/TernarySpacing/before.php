<?php

declare(strict_types=1);

namespace PhpCollective;

class TernarySpacingExample
{
    public function format(bool $first, bool $second, string $value): string
    {
        $missingBefore = $first? 'yes' : 'no';
        $missingAfter = $first ?'yes' : 'no';
        $missingElseBefore = $first ? 'yes': 'no';
        $missingElseAfter = $first ? 'yes' :'no';
        $tooManyBefore = $first  ? 'yes' : 'no';
        $tooManyAfter = $first ?  'yes' : 'no';
        $short = $value ? : 'fallback';

        $valid = $second ? 'left' : 'right';
        $validShort = $value ?: 'fallback';
        $multiline = $first
            ? 'yes'
            : 'no';

        return $missingBefore . $missingAfter . $missingElseBefore . $missingElseAfter . $tooManyBefore . $tooManyAfter . $short . $valid . $validShort . $multiline;
    }
}
