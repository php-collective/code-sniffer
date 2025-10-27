<?php declare(strict_types = 1);

namespace PhpCollective;

// Tests catch with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in checkUseForCatchOrCallable()
class FixMe
{
    public function test()
    {
        try {
            throw new \Exception();
        } catch (\Foo\Bar\ExceptionType $e) {
            // Handle
        }
    }
}
