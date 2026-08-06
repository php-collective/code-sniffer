<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\PHP;

use PhpCollective\Sniffs\PHP\PhpSapiConstantSniff;
use PhpCollective\Test\TestCase;

class PhpSapiConstantSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testPhpSapiConstantSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new PhpSapiConstantSniff(), 2, 2);
    }

    /**
     * @return void
     */
    public function testPhpSapiConstantFixer(): void
    {
        $this->assertSnifferCanFixErrors(new PhpSapiConstantSniff(), 2);
    }
}
