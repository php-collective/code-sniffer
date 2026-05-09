<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DocBlockTagOrderSniff;
use PhpCollective\Test\TestCase;

class DocBlockTagOrderSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockTagOrderSniffer(): void
    {
        $sniff = new DocBlockTagOrderSniff();
        // One error per misordered docblock: class-level + function-level.
        $this->assertSnifferFindsFixableErrors($sniff, 2, 2);
    }

    /**
     * @return void
     */
    public function testDocBlockTagOrderFixer(): void
    {
        $sniff = new DocBlockTagOrderSniff();
        $this->assertSnifferCanFixErrors($sniff, 2);
    }

    /**
     * Custom $classOrder set via property override (mirrors XML configuration).
     *
     * @return void
     */
    public function testDocBlockTagOrderCustomClassOrder(): void
    {
        $this->prefix = 'custom-order.';

        $sniff = new DocBlockTagOrderSniff();
        $sniff->classOrder = ['@mixin', '@method', '@property', '@extends'];

        $this->assertSnifferCanFixErrors($sniff, 1);

        $this->prefix = null;
    }

    /**
     * Tag names without leading "@" should be normalized.
     *
     * @return void
     */
    public function testDocBlockTagOrderCustomClassOrderWithoutAtPrefix(): void
    {
        $this->prefix = 'custom-order.';

        $sniff = new DocBlockTagOrderSniff();
        $sniff->classOrder = ['mixin', 'method', 'property', 'extends'];

        $this->assertSnifferCanFixErrors($sniff, 1);

        $this->prefix = null;
    }

    /**
     * Blank-line separators between tag groups should be normalized away when tags are reordered,
     * otherwise an orphan blank line may end up splitting a now-coherent same-kind tag group.
     *
     * @return void
     */
    public function testDocBlockTagOrderNormalizesBlankLineSeparators(): void
    {
        $this->prefix = 'blank-lines.';

        $sniff = new DocBlockTagOrderSniff();

        $this->assertSnifferCanFixErrors($sniff, 1);

        $this->prefix = null;
    }
}
