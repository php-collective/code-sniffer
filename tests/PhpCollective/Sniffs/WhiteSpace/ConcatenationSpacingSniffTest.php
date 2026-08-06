<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\WhiteSpace;

use PhpCollective\Sniffs\WhiteSpace\ConcatenationSpacingSniff;
use PhpCollective\Test\TestCase;

class ConcatenationSpacingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testConcatenationSpacingSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new ConcatenationSpacingSniff(), 4, 4);
    }

    /**
     * @return void
     */
    public function testConcatenationSpacingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new ConcatenationSpacingSniff(), 4);
    }
}
