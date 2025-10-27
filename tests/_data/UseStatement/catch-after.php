<?php declare(strict_types = 1);

namespace PhpCollective;
use Foo\Bar\ExceptionType;

// Tests catch with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in checkUseForCatchOrCallable()
class FixMe
{
    public function test()
    {
        try {
            throw new \Exception();
        } catch (ExceptionType $e) {
            // Handle
        }
    }
}
