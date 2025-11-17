<?php

declare(strict_types=1);

namespace PhpCollective;

class VoidCastExample
{
    public function testVoidCast(): void
    {
        // Correct usage
        (void) $this->methodWithReturn();

        // Missing space after cast
        (void)$this->anotherMethod();

        // Space inside cast
        ( void ) $this->yetAnotherMethod();

        // Extra spaces after cast
        (void)  $this->oneMoreMethod();

        // Combination of issues
        ( void )$this->finalMethod();
    }

    private function methodWithReturn(): string
    {
        return 'result';
    }

    private function anotherMethod(): int
    {
        return 42;
    }

    private function yetAnotherMethod(): bool
    {
        return true;
    }

    private function oneMoreMethod(): array
    {
        return [];
    }

    private function finalMethod(): mixed
    {
        return null;
    }
}
