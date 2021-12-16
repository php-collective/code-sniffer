<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace CodeSnifferTest\Spryker\Sniffs\Commenting;

use CodeSnifferTest\TestCase;
use Spryker\Sniffs\Commenting\TypeHintSniff;

class TypeHintSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testTypeHintSniffer(): void
    {
        $this->assertSnifferFindsErrors(new TypeHintSniff(), 11);
    }

    /**
     * @return void
     */
    public function testDocBlockThrowsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new TypeHintSniff());
    }
}
