<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DocBlockThrowsSniff;
use PhpCollective\Test\TestCase;

class DocBlockThrowsSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockThrowsSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DocBlockThrowsSniff(), 6);
    }

    /**
     * @return void
     */
    public function testDocBlockThrowsFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DocBlockThrowsSniff(), 6);
    }
}
