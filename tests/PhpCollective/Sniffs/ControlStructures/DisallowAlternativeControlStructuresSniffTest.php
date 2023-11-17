<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\ControlStructures;

use PhpCollective\Sniffs\ControlStructures\DisallowAlternativeControlStructuresSniff;
use PhpCollective\Test\TestCase;

class DisallowAlternativeControlStructuresSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DisallowAlternativeControlStructuresSniff(), 5);
    }

    /**
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DisallowAlternativeControlStructuresSniff());
    }
}
