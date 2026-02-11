<?php declare(strict_types = 1);

namespace PhpCollective;
use App\Model\Enum\ActivityPhysioType;

// Tests static call with PHP 8+ T_NAME_QUALIFIED (partially qualified name) - fixed in checkUseForStatic()
class FixMe
{
    public function test()
    {
        if (ActivityPhysioType::Exercise !== $foo) {
            return true;
        }
    }
}
