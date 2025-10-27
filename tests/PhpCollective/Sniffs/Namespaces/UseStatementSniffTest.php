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

    /**
     * Tests extends with PHP 8+ T_NAME_FULLY_QUALIFIED (parse() method fix).
     *
     * @return void
     */
    public function testExtendsSniffer(): void
    {
        $this->prefix = 'extends-';
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
        $this->prefix = null;
    }

    /**
     * @return void
     */
    public function testExtendsFixer(): void
    {
        $this->prefix = 'extends-';
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
        $this->prefix = null;
    }

    /**
     * Tests implements with PHP 8+ T_NAME_FULLY_QUALIFIED (parse() method fix).
     *
     * @return void
     */
    public function testImplementsSniffer(): void
    {
        $this->prefix = 'implements-';
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
        $this->prefix = null;
    }

    /**
     * @return void
     */
    public function testImplementsFixer(): void
    {
        $this->prefix = 'implements-';
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
        $this->prefix = null;
    }

    /**
     * Tests new keyword with PHP 8+ T_NAME_FULLY_QUALIFIED (checkUseForNew() fix).
     *
     * @return void
     */
    public function testNewSniffer(): void
    {
        $this->prefix = 'new-';
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
        $this->prefix = null;
    }

    /**
     * @return void
     */
    public function testNewFixer(): void
    {
        $this->prefix = 'new-';
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
        $this->prefix = null;
    }

    /**
     * Tests static call with PHP 8+ T_NAME_FULLY_QUALIFIED (checkUseForStatic() fix).
     *
     * @return void
     */
    public function testStaticSniffer(): void
    {
        $this->prefix = 'static-';
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
        $this->prefix = null;
    }

    /**
     * @return void
     */
    public function testStaticFixer(): void
    {
        $this->prefix = 'static-';
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
        $this->prefix = null;
    }

    /**
     * Tests instanceof with PHP 8+ T_NAME_FULLY_QUALIFIED (checkUseForInstanceOf() fix).
     *
     * @return void
     */
    public function testInstanceofSniffer(): void
    {
        $this->prefix = 'instanceof-';
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
        $this->prefix = null;
    }

    /**
     * @return void
     */
    public function testInstanceofFixer(): void
    {
        $this->prefix = 'instanceof-';
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
        $this->prefix = null;
    }

    /**
     * Tests catch with PHP 8+ T_NAME_FULLY_QUALIFIED (checkUseForCatchOrCallable() fix).
     *
     * @return void
     */
    public function testCatchSniffer(): void
    {
        $this->prefix = 'catch-';
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
        $this->prefix = null;
    }

    /**
     * @return void
     */
    public function testCatchFixer(): void
    {
        $this->prefix = 'catch-';
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
        $this->prefix = null;
    }

    /**
     * Tests parameter type with PHP 8+ T_NAME_FULLY_QUALIFIED (checkUseForSignature() fix).
     *
     * @return void
     */
    public function testParamSniffer(): void
    {
        $this->prefix = 'param-';
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
        $this->prefix = null;
    }

    /**
     * @return void
     */
    public function testParamFixer(): void
    {
        $this->prefix = 'param-';
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
        $this->prefix = null;
    }

    /**
     * Tests return type with PHP 8+ T_NAME_FULLY_QUALIFIED (checkUseForReturnTypeHint() fix).
     *
     * @return void
     */
    public function testReturnSniffer(): void
    {
        $this->prefix = 'return-';
        $this->assertSnifferFindsErrors(new UseStatementSniff(), 1);
        $this->prefix = null;
    }

    /**
     * @return void
     */
    public function testReturnFixer(): void
    {
        $this->prefix = 'return-';
        $this->assertSnifferCanFixErrors(new UseStatementSniff());
        $this->prefix = null;
    }
}
