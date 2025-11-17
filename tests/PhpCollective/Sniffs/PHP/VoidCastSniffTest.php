<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\PHP;

use PhpCollective\Sniffs\PHP\VoidCastSniff;
use PhpCollective\Test\TestCase;

class VoidCastSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testVoidCastSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new VoidCastSniff(), 7, 7);
    }

    /**
     * @return void
     */
    public function testVoidCastFixer(): void
    {
        $this->assertSnifferCanFixErrors(new VoidCastSniff(), 7);
    }
}
