<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Arrays;

use PhpCollective\Sniffs\Arrays\ArrayBracketSpacingSniff;
use PhpCollective\Test\TestCase;

class ArrayBracketSpacingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testArrayBracketSpacingSniffer(): void
    {
        // 4 original errors + 1 new error from array with comments
        $this->assertSnifferFindsErrors(new ArrayBracketSpacingSniff(), 5);
    }

    /**
     * @return void
     */
    public function testArrayBracketSpacingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new ArrayBracketSpacingSniff());
    }
}
