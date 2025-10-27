<?php declare(strict_types = 1);

namespace PhpCollective;

// Tests static call with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in checkUseForStatic()
class FixMe
{
    public function test()
    {
        \Foo\Bar\StaticClass::method();
    }
}
