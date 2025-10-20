<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\ControlStructures;

use PhpCollective\Sniffs\ControlStructures\ControlStructureEmptyStatementSniff;
use PhpCollective\Test\TestCase;

class ControlStructureEmptyStatementSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testControlStructureEmptyStatementSniffer(): void
    {
        $this->assertSnifferFindsErrors(new ControlStructureEmptyStatementSniff(), 6);
    }

    /**
     * @return void
     */
    public function testControlStructureEmptyStatementFixer(): void
    {
        $this->assertSnifferCanFixErrors(new ControlStructureEmptyStatementSniff());
    }
}
