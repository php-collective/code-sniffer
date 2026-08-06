<?php

declare(strict_types=1);

namespace PhpCollective;

use RuntimeException;
use Throwable;

class ControlStructureSpacingExample
{
    public function run(): void
    {
        try {
            throw new RuntimeException('first');
        } catch (RuntimeException $exception) {
        }

        try {
            throw new RuntimeException('second');
        } catch (Throwable $exception) {
        }

        try {
            throw new RuntimeException('valid');
        } catch (Throwable $exception) {
        }
    }
}
