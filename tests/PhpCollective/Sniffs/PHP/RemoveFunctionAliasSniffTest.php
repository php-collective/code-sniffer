<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\PHP;

use PhpCollective\Sniffs\PHP\RemoveFunctionAliasSniff;
use PhpCollective\Test\TestCase;

class RemoveFunctionAliasSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testRemoveFunctionAliasSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new RemoveFunctionAliasSniff(), 16, 16);
    }

    /**
     * @return void
     */
    public function testRemoveFunctionAliasFixer(): void
    {
        $this->assertSnifferCanFixErrors(new RemoveFunctionAliasSniff(), 16);
    }
}
