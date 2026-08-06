<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\WhiteSpace;

use PhpCollective\Sniffs\WhiteSpace\FunctionSpacingSniff;
use PhpCollective\Test\TestCase;

class FunctionSpacingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testFunctionSpacingSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new FunctionSpacingSniff(), 2, 2);
    }

    /**
     * @return void
     */
    public function testFunctionSpacingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new FunctionSpacingSniff(), 2);
    }
}
