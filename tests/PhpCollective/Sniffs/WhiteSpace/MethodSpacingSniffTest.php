<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\WhiteSpace;

use PhpCollective\Sniffs\WhiteSpace\MethodSpacingSniff;
use PhpCollective\Test\TestCase;

class MethodSpacingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testMethodSpacingSniffer(): void
    {
        $this->assertSnifferFindsErrors(new MethodSpacingSniff(), 3);
    }

    /**
     * @return void
     */
    public function testMethodSpacingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new MethodSpacingSniff());
    }
}
