<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Classes;

use PhpCollective\Sniffs\Classes\EnumCaseCasingSniff;
use PhpCollective\Test\TestCase;

class EnumCaseCasingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new EnumCaseCasingSniff(), 3);
    }

    /**
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new EnumCaseCasingSniff());
    }
}
