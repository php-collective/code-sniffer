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
        $this->assertSnifferFindsErrors(new ArrayBracketSpacingSniff(), 4);
    }

    /**
     * @return void
     */
    public function testArrayBracketSpacingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new ArrayBracketSpacingSniff());
    }
}
