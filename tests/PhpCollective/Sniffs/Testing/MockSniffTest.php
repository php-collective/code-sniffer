<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Testing;

use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCollective\Sniffs\Testing\MockSniff;
use PhpCollective\Test\TestCase;

class MockSniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testMockSniffer(): void
    {
        $this->assertSnifferFindsFixableErrors(new MockSniff(), 6, 4);
    }

    /**
     * @return void
     */
    public function testMockFixer(): void
    {
        $this->assertSnifferCanFixErrors(new MockSniff(), 4);
    }

    /**
     * @param \PHP_CodeSniffer\Sniffs\Sniff $sniffer
     *
     * @return string
     */
    protected function getDummyFileBefore(Sniff $sniffer): string
    {
        $fixture = parent::getDummyFileBefore($sniffer);
        $target = TMP . 'MockSniffFixtureTest.php';

        if (!is_dir(TMP)) {
            mkdir(TMP, 0770, true);
        }

        $contents = file_get_contents($fixture);
        $this->assertIsString($contents);
        $result = file_put_contents($target, $contents);
        $this->assertIsInt($result);

        return $target;
    }
}
