<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DocCommentSniff;
use PhpCollective\Test\TestCase;

class DocCommentSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocCommentSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new DocCommentSniff(), 4, 4);
    }

    /**
     * @return void
     */
    public function testDocCommentFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DocCommentSniff(), 4);
    }
}
