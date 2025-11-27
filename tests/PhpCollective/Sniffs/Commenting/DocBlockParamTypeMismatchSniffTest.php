<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Commenting;

use PhpCollective\Sniffs\Commenting\DocBlockParamTypeMismatchSniff;
use PhpCollective\Test\TestCase;

class DocBlockParamTypeMismatchSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDocBlockParamTypeMismatchSniffer(): void
    {
        // Expected errors:
        // Line 12: Node|string -> string is incompatible with Node (fixable)
        // Line 22: string|int -> int is incompatible with string (fixable)
        // Line 30: Node|string|null -> string is incompatible with ?Node (fixable)
        // Line 70: array|string -> string is incompatible with array (fixable)
        // Line 92: int|string -> string is incompatible with int (fixable)
        // Line 100: Node|string|array -> string and array are incompatible with Node (fixable)
        // Line 108: Node|string -> string is incompatible with Node (fixable)
        // Line 130: bool|string -> string is incompatible with bool (fixable)
        // Line 160: string|int|bool -> bool is incompatible with string|int (fixable)
        // Line 176: string -> string is incompatible with Node (NOT fixable - all types invalid)
        $this->assertSnifferFindsErrors(new DocBlockParamTypeMismatchSniff(), 10);
    }

    /**
     * @return void
     */
    public function testDocBlockParamTypeMismatchFixer(): void
    {
        // 9 fixable errors (all except the last one where all types are invalid)
        $this->assertSnifferCanFixErrors(new DocBlockParamTypeMismatchSniff(), 9);
    }
}
