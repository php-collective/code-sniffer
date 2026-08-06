<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\PHP;

use PhpCollective\Sniffs\PHP\PreferCastOverFunctionSniff;
use PhpCollective\Test\TestCase;

class PreferCastOverFunctionSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testPreferCastOverFunctionSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new PreferCastOverFunctionSniff(), 2, 2);
    }

    /**
     * @return void
     */
    public function testPreferCastOverFunctionFixer(): void
    {
        $this->assertSnifferCanFixErrors(new PreferCastOverFunctionSniff(), 2);
    }
}
