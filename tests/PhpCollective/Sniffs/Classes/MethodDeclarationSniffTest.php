<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Classes;

use PhpCollective\Sniffs\Classes\MethodDeclarationSniff;
use PhpCollective\Test\TestCase;

class MethodDeclarationSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testMethodDeclarationSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new MethodDeclarationSniff(), 5, 4);
    }

    /**
     * @return void
     */
    public function testMethodDeclarationFixer(): void
    {
        $this->assertSnifferCanFixErrors(new MethodDeclarationSniff(), 4);
    }
}
