<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Internal;

use PhpCollective\Sniffs\Internal\SprykerPreferStaticOverSelfSniff;
use PhpCollective\Test\TestCase;

class SprykerPreferStaticOverSelfSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new SprykerPreferStaticOverSelfSniff(), 2);
    }

    /**
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new SprykerPreferStaticOverSelfSniff());
    }
}
