<?php declare(strict_types = 1);

namespace PhpCollective;

// Tests instanceof with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in checkUseForInstanceOf()
class FixMe
{
    public function test($obj)
    {
        if ($obj instanceof \Foo\Bar\CheckType) {
            return true;
        }
    }
}
