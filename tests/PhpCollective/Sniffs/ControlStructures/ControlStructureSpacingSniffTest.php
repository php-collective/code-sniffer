<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\ControlStructures;

use PhpCollective\Sniffs\ControlStructures\ControlStructureSpacingSniff;
use PhpCollective\Test\TestCase;

class ControlStructureSpacingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testControlStructureSpacingSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new ControlStructureSpacingSniff(), 6, 6);
    }

    /**
     * @return void
     */
    public function testControlStructureSpacingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new ControlStructureSpacingSniff(), 6);
    }
}
