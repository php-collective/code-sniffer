<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\WhiteSpace;

use PhpCollective\Sniffs\WhiteSpace\TernarySpacingSniff;
use PhpCollective\Test\TestCase;

class TernarySpacingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testTernarySpacingSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new TernarySpacingSniff(), 7, 7);
    }

    /**
     * @return void
     */
    public function testTernarySpacingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new TernarySpacingSniff(), 7);
    }
}
