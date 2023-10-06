<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Formatting;

use PhpCollective\Sniffs\Formatting\MethodSignatureParametersLineBreakMethodSniff;
use PhpCollective\Test\TestCase;

class MethodSignatureParametersLineBreakMethodSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testMethodSignatureParametersLineBreakMethodSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new MethodSignatureParametersLineBreakMethodSniff(), 7, 7);
    }

    /**
     * @return void
     */
    public function testMethodSignatureParametersLineBreakMethodFixer(): void
    {
        $this->assertSnifferCanFixErrors(new MethodSignatureParametersLineBreakMethodSniff());
    }
}
