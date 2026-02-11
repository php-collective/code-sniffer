<?php declare(strict_types = 1);

namespace PhpCollective;

// Tests PHP 8+ attribute with T_NAME_QUALIFIED (partially qualified name)
#[Foo\Bar\SomeAttribute]
class FixMe
{
}
