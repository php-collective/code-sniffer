<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\WhiteSpace;

use PhpCollective\Sniffs\WhiteSpace\PipeOperatorSpacingSniff;
use PhpCollective\Test\TestCase;

class PipeOperatorSpacingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testPipeOperatorSpacingSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new PipeOperatorSpacingSniff(), 10, 10);
    }

    /**
     * @return void
     */
    public function testPipeOperatorSpacingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new PipeOperatorSpacingSniff(), 10);
    }
}
