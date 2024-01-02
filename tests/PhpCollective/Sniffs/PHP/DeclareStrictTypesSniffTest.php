<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\PHP;

use PhpCollective\Sniffs\PHP\DeclareStrictTypesSniff;
use PhpCollective\Test\TestCase;

class DeclareStrictTypesSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testDeclareStrictTypesSniffer(): void
    {
        $this->assertSnifferFindsErrors(new DeclareStrictTypesSniff(), 1);
    }

    /**
     * @return void
     */
    public function testDeclareStrictTypesFixer(): void
    {
        $this->assertSnifferCanFixErrors(new DeclareStrictTypesSniff());
    }

    /**
     * @return void
     */
    public function testDeclareStrictTypesFixerFirstLine(): void
    {
        $this->prefix = 'first-line_';

        $sniff = new DeclareStrictTypesSniff();
        $sniff->declareOnFirstLine = true;
        $this->assertSnifferCanFixErrors($sniff);
    }

    /**
     * @return void
     */
    public function testDeclareStrictTypesFixerZero(): void
    {
        $this->prefix = 'zero_';

        $sniff = new DeclareStrictTypesSniff();
        $sniff->linesCountBeforeDeclare = 0;

        $this->assertSnifferCanFixErrors($sniff);
    }
}
