<?php declare(strict_types = 1);

namespace PhpCollective;
use Foo\Bar\CheckType;

// Tests instanceof with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in checkUseForInstanceOf()
class FixMe
{
    public function test($obj)
    {
        if ($obj instanceof CheckType) {
            return true;
        }
    }
}
