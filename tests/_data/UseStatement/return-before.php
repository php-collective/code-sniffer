<?php declare(strict_types = 1);

namespace PhpCollective;

// Tests return type with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in checkUseForReturnTypeHint()
class FixMe
{
    public function test(): \Foo\Bar\ReturnType
    {
    }
}
