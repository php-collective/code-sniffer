<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\WhiteSpace;

use PhpCollective\Sniffs\WhiteSpace\EmptyEnclosingLineSniff;
use PhpCollective\Test\TestCase;

class EmptyEnclosingLineSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testEmptyEnclosingLineSniffer(): void
    {
        $this->assertSnifferFindsErrors(new EmptyEnclosingLineSniff(), 2);
    }

    /**
     * @return void
     */
    public function testEmptyEnclosingLineFixer(): void
    {
        $this->assertSnifferCanFixErrors(new EmptyEnclosingLineSniff());
    }

    /**
     * Tests that tabs are preserved when fixing empty enclosing line.
     *
     * @return void
     */
    public function testTabsPreservedSniffer(): void
    {
        $this->prefix = 'tabs-';
        $this->assertSnifferFindsErrors(new EmptyEnclosingLineSniff(), 1);
        $this->prefix = null;
    }

    /**
     * Tests fixer preserves indentation with tabs.
     *
     * This test verifies the fix for the bug where indentation was lost
     * when removing empty lines after opening braces.
     *
     * @return void
     */
    public function testTabsPreservedFixer(): void
    {
        $this->prefix = 'tabs-';
        $this->assertSnifferCanFixErrors(new EmptyEnclosingLineSniff());
        $this->prefix = null;
    }
}
