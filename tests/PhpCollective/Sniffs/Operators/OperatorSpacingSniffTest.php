<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Operators;

use PhpCollective\Sniffs\Operators\OperatorSpacingSniff;
use PhpCollective\Test\TestCase;

class OperatorSpacingSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testOperatorSpacingSniff(): void
    {
        // The `|` in a catch type list is a type separator: no space allowed, so the spaced
        // separator is reported on both sides (2 fixable errors). The real bitwise operator in
        // the catch body keeps the inherited spacing rule: the missing space after it is the
        // third fixable error, proving the inherited check still runs for genuine operators.
        $this->assertSnifferFindsFixableErrors(new OperatorSpacingSniff(), 3, 3);
    }

    /**
     * @return void
     */
    public function testOperatorSpacingFixer(): void
    {
        $this->assertSnifferCanFixErrors(new OperatorSpacingSniff(), 3);
    }
}
