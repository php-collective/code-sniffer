<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\ControlStructures;

use PhpCollective\Sniffs\ControlStructures\UnneededElseSniff;
use PhpCollective\Test\TestCase;

class UnneededElseSniffTest extends TestCase
{
    /**
     * Tests that the sniff finds the expected number of errors
     *
     * Expected errors:
     * - simpleReturn: 1 error (line 15 - else)
     * - allReturns: 2 errors (line 28 - elseif→if, line 31 - else)
     * - noReturnInFirstBranch: 0 errors (should NOT fix - critical test case!)
     * - withThrow: 1 error (line 65 - else)
     * - withExit: 1 error (line 75 - else)
     * - withBreak: 1 error (line 86 - else)
     * - withContinue: 1 error (line 98 - else)
     * - multipleStatements: 1 error (line 111 - else)
     * - nestedStructure: 1 error (line 121 - else)
     *
     * Total: 9 errors (8 else removals + 1 elseif→if conversion)
     *
     * @return void
     */
    public function testUnneededElseSniffer(): void
    {
        $this->assertSnifferFindsErrors(new UnneededElseSniff(), 9);
    }

    /**
     * Tests that the fixer correctly removes unneeded else blocks
     *
     * @return void
     */
    public function testUnneededElseFixer(): void
    {
        $this->assertSnifferCanFixErrors(new UnneededElseSniff());
    }
}
