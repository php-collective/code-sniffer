<?php declare(strict_types = 1);

namespace PhpCollective;
use Foo\Bar\SomeAttribute;

// Tests PHP 8+ attribute with T_NAME_QUALIFIED (partially qualified name)
#[SomeAttribute]
class FixMe
{
}
