<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\WhiteSpace;

use PhpCollective\Sniffs\WhiteSpace\CatchTypeSeparatorSpacingSniff;
use PhpCollective\Test\TestCase;

class CatchTypeSeparatorSpacingSniffTest extends TestCase
{
    public function testSpacing(): void
    {
        $this->assertSnifferFindsFixableErrors(new CatchTypeSeparatorSpacingSniff(), 7, 5);
    }

    public function testFixer(): void
    {
        $this->assertSnifferCanFixErrors(new CatchTypeSeparatorSpacingSniff(), 5);
    }
}
