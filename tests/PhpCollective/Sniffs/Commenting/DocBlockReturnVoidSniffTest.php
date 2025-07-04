<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DocBlockReturnVoidSniff;
use PhpCollective\Test\TestCase;

class DocBlockReturnVoidSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockReturnVoidSniffer(): void
    {
        $sniff = new DocBlockReturnVoidSniff();
        $sniff->strict = true;
        $this->assertSnifferFindsFixableErrors($sniff, 3, 3);
    }

    /**
     * @return void
     */
    public function testDocBlockReturnVoidFixer(): void
    {
        $sniff = new DocBlockReturnVoidSniff();
        $sniff->strict = true;
        $this->assertSnifferCanFixErrors($sniff, 3);
    }
}
