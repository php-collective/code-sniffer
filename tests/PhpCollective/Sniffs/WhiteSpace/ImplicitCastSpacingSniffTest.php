<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\WhiteSpace;

use PhpCollective\Sniffs\WhiteSpace\ImplicitCastSpacingSniff;
use PhpCollective\Test\TestCase;

class ImplicitCastSpacingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testImplicitCastSpacingSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new ImplicitCastSpacingSniff(), 8, 8);
    }

    /**
     * @return void
     */
    public function testImplicitCastSpacingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new ImplicitCastSpacingSniff(), 8);
    }
}
