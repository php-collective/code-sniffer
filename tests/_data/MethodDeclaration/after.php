<?php

declare(strict_types=1);

namespace PhpCollective;

abstract class MethodDeclarationExample
{
    final public function finalAfterVisibility(): void
    {
    }

    abstract public function abstractAfterVisibility(): void;

    public static function staticBeforeVisibility(): void
    {
    }

    final private function finalPrivate(): void
    {
    }

    final public static function validOrder(): void
    {
    }
}
