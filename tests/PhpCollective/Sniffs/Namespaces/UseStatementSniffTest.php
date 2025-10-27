<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Test\PhpCollective\Sniffs\Namespaces;

use PhpCollective\Sniffs\Namespaces\UseStatementSniff;
use PhpCollective\Test\TestCase;

/**
 * Tests for UseStatementSniff which enforces use of use statements instead of inline FQCNs.
 *
 * This sniff has been updated with the following fixes ported from PSR2R NoInlineFullyQualifiedClassName:
 * - PHP 8+ T_NAME_FULLY_QUALIFIED token support in all check methods
 * - PHP 8+ T_NAME_FULLY_QUALIFIED token support in parse() method for extends/implements
 * - shortName fallback when alias is null (prevents undefined replacements)
 * - str_contains() instead of deprecated strpos()
 *
 * The fixes ensure the sniff works correctly on PHP 8+ where inline FQCNs like \Foo\Bar\Class
 * are tokenized as a single T_NAME_FULLY_QUALIFIED token instead of multiple T_NS_SEPARATOR + T_STRING tokens.
 */
class UseStatementSniffTest extends TestCase
{
    /**
     * Tests property type hint with PHP 8+ T_NAME_FULLY_QUALIFIED token.
     *
     * @return void
     */
    public function testDocBlockConstSniffer(): void
    {
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
    }

    /**
     * Tests fixer with shortName fallback.
     *
     * @return void
     */
    public function testDocBlockConstFixer(): void
    {
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
    }

}
