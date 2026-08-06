<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DocBlockStructureSniff;
use PhpCollective\Test\TestCase;

class DocBlockStructureSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockStructureSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new DocBlockStructureSniff(), 3, 3);
    }

    /**
     * @return void
     */
    public function testDocBlockStructureFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DocBlockStructureSniff(), 3);
    }
}
