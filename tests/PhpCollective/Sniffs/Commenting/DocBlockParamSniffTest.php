<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DocBlockParamSniff;
use PhpCollective\Test\TestCase;

class DocBlockParamSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockParamSniffer(): void
    {
        // Expected errors:
        // Line 32: SignatureMismatch - not fully typed with @dataProvider (not fixable - missing type)
        // Line 37: SignatureMismatch - partially documented (fixable - can add missing param)
        // Line 51: VariableWrong - wrong variable name (not fixable - needs manual correction)
        // Line 59: SignatureMismatch - extra @param count mismatch (not fixable - complex case)
        // Line 64: ExtraParam - extra @param $extra (fixable - can remove extra param)
        // Line 74: ExtraParam - no params but has @param (fixable - can remove param)
        // Line 84: MissingType - missing type in @param (not fixable - needs manual type)
        // Line 87: SignatureMismatch - params don't match after missing type (not fixable)
        // Line 169: SignatureMismatch - middle param documented, need to add before and after (fixable)
        $this->assertSnifferFindsErrors(new DocBlockParamSniff(), 9);
    }

    /**
     * @return void
     */
    public function testDocBlockParamFixer(): void
    {
        // 4 fixable errors:
        // Line 37: Can add missing @param
        // Line 64: Can remove extra @param
        // Line 74: Can remove @param when no params
        // Line 169: Can add missing @param before and after existing param (order preserved)
        $this->assertSnifferCanFixErrors(new DocBlockParamSniff(), 4);
    }
}
