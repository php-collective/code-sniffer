<?php declare(strict_types = 1);

namespace PhpCollective;
use Foo\Bar\StaticClass;

// Tests static call with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in checkUseForStatic()
class FixMe
{
    public function test()
    {
        StaticClass::method();
    }
}
