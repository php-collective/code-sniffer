<?php declare(strict_types = 1);

namespace PhpCollective;
use Foo\Bar\BaseClass;

// Tests extends with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in parse() method
class FixMe extends BaseClass
{
}
