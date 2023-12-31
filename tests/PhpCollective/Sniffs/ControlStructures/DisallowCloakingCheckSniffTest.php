<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\ControlStructures;

use PhpCollective\Sniffs\ControlStructures\DisallowCloakingCheckSniff;
use PhpCollective\Test\TestCase;

class DisallowCloakingCheckSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDisallowArrayTypeHintSyntaxSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DisallowCloakingCheckSniff(), 10);
    }

    /**
     * @return void
     */
    public function testDocBlockThrowsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DisallowCloakingCheckSniff());
    }
}
