<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\PHP;

use PhpCollective\Sniffs\PHP\DisallowFunctionsSniff;
use PhpCollective\Test\TestCase;

class DisallowFunctionsSniffTest extends TestCase
{
    /**
     * The sniff ships with an empty list that projects fill in via the ruleset, so the tests have
     * to populate it. It is static, so it has to be put back or it leaks into every later test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        DisallowFunctionsSniff::$disallowed = [];

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testDisallowFunctionsSniffer(): void
    {
        DisallowFunctionsSniff::$disallowed = ['pos' => 'Use current() instead'];

        $this->assertSnifferFindsFixableErrors(new DisallowFunctionsSniff(), 2, 0);
    }

    /**
     * @return void
     */
    public function testDisallowFunctionsFixer(): void
    {
        DisallowFunctionsSniff::$disallowed = ['pos' => 'Use current() instead'];

        $this->assertSnifferCanFixErrors(new DisallowFunctionsSniff(), 0);
    }
}
