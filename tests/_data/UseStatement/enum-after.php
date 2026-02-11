<?php declare(strict_types = 1);

namespace PhpCollective;
use Foo\Bar\SomeInterface;

// Tests PHP 8.1+ enum implements with FQCN
enum FixMe: string implements SomeInterface
{
    case One = 'one';
}
