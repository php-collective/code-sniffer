<?php declare(strict_types = 1);

namespace PhpCollective;

// Tests PHP 8.1+ enum implements with FQCN
enum FixMe: string implements \Foo\Bar\SomeInterface
{
    case One = 'one';
}
