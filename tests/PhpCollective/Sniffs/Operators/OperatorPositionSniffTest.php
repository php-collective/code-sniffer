<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Operators;

use PhpCollective\Sniffs\Operators\OperatorPositionSniff;
use PhpCollective\Test\TestCase;

class OperatorPositionSniffTest extends TestCase
{
    public function testReportsOperators(): void
    {
        $errors = $this->assertSnifferFindsFixableErrors(new OperatorPositionSniff(), 28, 27);
        $commentErrors = array_values($errors[57]);
        $this->assertSame('PhpCollective.Operators.OperatorPosition.IncorrectWithComment', $commentErrors[0][0]['source']);
    }

    public function testFixesOperators(): void
    {
        $this->assertSnifferCanFixErrors(new OperatorPositionSniff(), 27);
    }
}
