<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DisallowShorthandNullableTypeHintSniff;
use PhpCollective\Test\TestCase;

class DisallowShorthandNullableTypeHintSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DisallowShorthandNullableTypeHintSniff(), 5);
    }

    /**
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DisallowShorthandNullableTypeHintSniff());
    }
}
