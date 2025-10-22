<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\ControlStructures;

use PhpCollective\Sniffs\ControlStructures\ControlSignatureSniff;
use PhpCollective\Test\TestCase;

class ControlSignatureSniffTest extends TestCase
{
    /**
     * Tests that the sniff finds the expected number of errors
     *
     * Expected errors:
     * - Line 10: } else on separate lines
     * - Line 18: } elseif on separate lines
     * - Line 21: } elseif on separate lines (second one)
     * - Line 24: } else on separate lines (second one)
     * - Line 32: } catch on separate lines
     * - Line 40: } catch on separate lines (second one)
     * - Line 43: } finally on separate lines
     *
     * Total: 7 errors
     *
     * @return void
     */
    public function testControlSignatureSniffer(): void
    {
        $this->assertSnifferFindsErrors(new ControlSignatureSniff(), 7);
    }

    /**
     * Tests that the fixer correctly moves keywords to same line as closing brace
     *
     * @return void
     */
    public function testControlSignatureFixer(): void
    {
        $this->assertSnifferCanFixErrors(new ControlSignatureSniff());
    }
}
