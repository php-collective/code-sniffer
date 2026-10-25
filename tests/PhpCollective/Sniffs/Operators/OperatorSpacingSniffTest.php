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
    public function testBitwiseOperator(): void
    {
        $errors = $this->assertSnifferFindsFixableErrors(new OperatorSpacingSniff(), 2, 2);
        $sources = array_merge(...array_map(static function (array $line): array {
            return array_merge(...array_map(static function (array $column): array {
                return array_column($column, 'source');
            }, $line));
        }, $errors));
        $this->assertContains('PhpCollective.Operators.OperatorSpacing.NoSpaceBefore', $sources);
        $this->assertContains('PhpCollective.Operators.OperatorSpacing.NoSpaceAfter', $sources);
    }
}
