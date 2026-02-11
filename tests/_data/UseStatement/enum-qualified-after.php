<?php declare(strict_types = 1);

namespace PhpCollective;
use Foo\Bar\SomeInterface;

// Tests PHP 8.1+ enum implements with T_NAME_QUALIFIED (partially qualified name)
enum FixMe: string implements SomeInterface
{
    case One = 'one';
}
