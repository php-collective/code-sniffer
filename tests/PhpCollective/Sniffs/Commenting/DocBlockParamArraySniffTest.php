<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DocBlockParamArraySniff;
use PhpCollective\Test\TestCase;

class DocBlockParamArraySniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DocBlockParamArraySniff(), 2);
    }

    /**
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DocBlockParamArraySniff());
    }
}
