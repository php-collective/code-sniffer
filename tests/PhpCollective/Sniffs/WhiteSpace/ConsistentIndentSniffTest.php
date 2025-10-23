<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\WhiteSpace;

use PhpCollective\Sniffs\WhiteSpace\ConsistentIndentSniff;
use PhpCollective\Test\TestCase;

class ConsistentIndentSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testConsistentIndentSniffer(): void
    {
        $this->assertSnifferFindsErrors(new ConsistentIndentSniff(), 2);
    }

    /**
     * @return void
     */
    public function testConsistentIndentFixer(): void
    {
        $this->assertSnifferCanFixErrors(new ConsistentIndentSniff());
    }
}
