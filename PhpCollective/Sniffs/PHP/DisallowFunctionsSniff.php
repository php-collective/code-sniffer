<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * Do not use functions that are problematic or not safe for upgrading.
 */
class DisallowFunctionsSniff extends AbstractSniff
{
    /**
     * @var array<string>
     */
    public static array $disallowed = [];

    /**
     * @var array<int>
     */
    protected static array $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return $this->getGlobalFunctionNameTokenCodes();
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $this->checkForbiddenFunctions($phpcsFile, $stackPtr);
        $this->checkImplodeUsage($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkForbiddenFunctions(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $key = $this->getGlobalFunctionName($phpcsFile, $stackPtr);
        if ($key === null || !isset(static::$disallowed[$key])) {
            return;
        }

        $tokenContent = $tokens[$stackPtr]['content'];
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($previous === false || in_array($tokens[$previous]['code'], static::$wrongTokens)) {
            return;
        }

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $error = 'Function ' . $tokenContent . '() usage found: ' . static::$disallowed[$key] . '.';
        $phpcsFile->addError($error, $stackPtr, 'LongInvalid');
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkImplodeUsage(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $key = $this->getGlobalFunctionName($phpcsFile, $stackPtr);
        if ($key !== 'implode') {
            return;
        }

        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($previous === false || in_array($tokens[$previous]['code'], static::$wrongTokens)) {
            return;
        }

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }
        $closingBrace = $tokens[$openingBrace]['parenthesis_closer'];

        //Count arguments
        $args = $this->getArgCount($phpcsFile, $openingBrace, $closingBrace);
        if ($args !== 1) {
            return;
        }

        $fix = $phpcsFile->addFixableError('implode() must always be used with 2 args.', $stackPtr, 'Invalid');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addContent($openingBrace, '\'\', ');
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openingBrace
     * @param int $closingBrace
     *
     * @return int
     */
    protected function getArgCount(File $phpcsFile, int $openingBrace, int $closingBrace): int
    {
        $tokens = $phpcsFile->getTokens();
        $count = 0;
        for ($i = $openingBrace + 1; $i < $closingBrace; $i++) {
            if ($tokens[$i]['content'] === ',') {
                $count++;
            }
        }

        return $count + 1;
    }
}
