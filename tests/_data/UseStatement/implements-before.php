<?php declare(strict_types = 1);

namespace PhpCollective;

// Tests implements with PHP 8+ T_NAME_FULLY_QUALIFIED - fixed in parse() method
class FixMe implements \Foo\Bar\InterfaceA
{
}
