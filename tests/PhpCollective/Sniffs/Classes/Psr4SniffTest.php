<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Classes;

use PhpCollective\Sniffs\Classes\Psr4Sniff;
use PhpCollective\Test\TestCase;

class Psr4SniffTest extends TestCase
{
    /**
     * @return void
     */
    public function testMissingComposerJsonDisablesSniff(): void
    {
        $sniff = new Psr4Sniff();
        $sniff->composerJsonPath = 'does-not-exist/composer.json';

        $this->assertSnifferFindsErrors($sniff, 0);
    }
}
