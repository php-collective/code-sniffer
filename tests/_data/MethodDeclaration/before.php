<?php

declare(strict_types=1);

namespace PhpCollective;

abstract class MethodDeclarationExample
{
    public final function finalAfterVisibility(): void
    {
    }

    public abstract function abstractAfterVisibility(): void;

    static public function staticBeforeVisibility(): void
    {
    }

    private final function finalPrivate(): void
    {
    }

    final public static function validOrder(): void
    {
    }
}
