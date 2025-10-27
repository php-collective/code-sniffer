<?php declare(strict_types = 1);

namespace PhpCollective;

// Tests new with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in checkUseForNew()
class FixMe
{
    public function test()
    {
        $obj = new \Foo\Bar\NewClass();
    }
}
