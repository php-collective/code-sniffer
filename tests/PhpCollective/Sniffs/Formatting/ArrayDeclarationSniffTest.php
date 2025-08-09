<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Formatting;

use PhpCollective\Sniffs\Formatting\ArrayDeclarationSniff;
use PhpCollective\Test\TestCase;

class ArrayDeclarationSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new ArrayDeclarationSniff(), 4);
    }

    /**
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new ArrayDeclarationSniff());
    }
}
