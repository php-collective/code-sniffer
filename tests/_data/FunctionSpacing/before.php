<?php

declare(strict_types=1);

namespace PhpCollective;

interface FunctionSpacingContract
{
    public function first(): void;
    public function second(): void;

    public function last(): void;
}

class FunctionSpacingExample
{
    private string $name = 'x';
    public function needsBlankBefore(): void
    {
    }

    public function needsBlankAfter(): void
    {
    }
    private string $other = 'y';

    #[\Override]
    public function attributeIsLeftAlone(): void
    {
    }

    public function closureIsLeftAlone(): void
    {
        $closure = function (): void {
        };
    }
}
