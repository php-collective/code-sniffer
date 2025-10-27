<?php declare(strict_types = 1);

namespace PhpCollective;
use Foo\Bar\ParamType;

// Tests parameter type with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in checkUseForSignature()
class FixMe
{
    public function test(ParamType $param)
    {
    }
}
