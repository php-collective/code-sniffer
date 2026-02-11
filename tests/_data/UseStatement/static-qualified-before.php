<?php declare(strict_types = 1);

namespace PhpCollective;

// Tests static call with PHP 8+ T_NAME_QUALIFIED (partially qualified name) - fixed in checkUseForStatic()
class FixMe
{
    public function test()
    {
        if (App\Model\Enum\ActivityPhysioType::Exercise !== $foo) {
            return true;
        }
    }
}
