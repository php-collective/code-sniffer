<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DisallowArrayTypeHintSyntaxSniff;
use PhpCollective\Test\TestCase;

class DisallowArrayTypeHintSyntaxSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDisallowArrayTypeHintSyntaxSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DisallowArrayTypeHintSyntaxSniff(), 12);
    }

    /**
     * @return void
     */
    public function testDocBlockThrowsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DisallowArrayTypeHintSyntaxSniff());
    }
}
