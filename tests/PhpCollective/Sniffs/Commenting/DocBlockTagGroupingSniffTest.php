<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DocBlockTagGroupingSniff;
use PhpCollective\Test\TestCase;

class DocBlockTagGroupingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockTagGroupingSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new DocBlockTagGroupingSniff(), 6, 6);
    }

    /**
     * @return void
     */
    public function testDocBlockTagGroupingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DocBlockTagGroupingSniff(), 6);
    }
}
