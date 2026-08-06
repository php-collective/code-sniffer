<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\PHP;

use PhpCollective\Sniffs\PHP\NoIsNullSniff;
use PhpCollective\Test\TestCase;

class NoIsNullSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testNoIsNullSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new NoIsNullSniff(), 10, 10);
    }

    /**
     * @return void
     */
    public function testNoIsNullFixer(): void
    {
        $this->assertSnifferCanFixErrors(new NoIsNullSniff(), 10);
    }
}
