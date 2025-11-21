<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Validates (void) cast usage - a PHP 8.5 feature.
 *
 * Ensures proper spacing and formatting around void casts.
 */
class VoidCastSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_OPEN_PARENTHESIS];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Check if this is a (void) cast pattern
        $nextNonWhitespace = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
        if (!$nextNonWhitespace || $tokens[$nextNonWhitespace]['code'] !== T_STRING) {
            return;
        }

        if (strtolower($tokens[$nextNonWhitespace]['content']) !== 'void') {
            return;
        }

        $closeParenthesis = $phpcsFile->findNext(Tokens::$emptyTokens, $nextNonWhitespace + 1, null, true);
        if (!$closeParenthesis || $tokens[$closeParenthesis]['code'] !== T_CLOSE_PARENTHESIS) {
            return;
        }

        // We have a (void) cast - check spacing
        $this->checkSpacingBeforeCast($phpcsFile, $stackPtr);
        $this->checkSpacingWithinCast($phpcsFile, $stackPtr, $nextNonWhitespace, $closeParenthesis);
        $this->checkSpacingAfterCast($phpcsFile, $closeParenthesis);
    }

    /**
     * Check that there's no space before the opening parenthesis of the cast
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkSpacingBeforeCast(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Check if previous token is whitespace at statement start, which is OK
        $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if (!$prevIndex) {
            return;
        }

        // If there's whitespace before the cast and we're not at statement start, it might be intentional
        // We mainly want to avoid cases like `foo (void)bar()`
        if ($tokens[$stackPtr - 1]['code'] === T_WHITESPACE) {
            $prevToken = $tokens[$prevIndex];
            // Only warn if the previous non-whitespace token suggests this is mid-expression
            if (
                in_array($prevToken['code'], [T_STRING, T_VARIABLE, T_CLOSE_PARENTHESIS, T_CLOSE_SQUARE_BRACKET], true)
                && $prevToken['line'] === $tokens[$stackPtr]['line']
            ) {
                $fix = $phpcsFile->addFixableError(
                    'No space expected before void cast',
                    $stackPtr - 1,
                    'SpaceBeforeCast',
                );
                if ($fix) {
                    $phpcsFile->fixer->replaceToken($stackPtr - 1, '');
                }
            }
        }
    }

    /**
     * Check that there's no space within the cast (void) not ( void )
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openParen
     * @param int $voidToken
     * @param int $closeParen
     *
     * @return void
     */
    protected function checkSpacingWithinCast(File $phpcsFile, int $openParen, int $voidToken, int $closeParen): void
    {
        $tokens = $phpcsFile->getTokens();

        // Check space after opening parenthesis
        if ($voidToken !== $openParen + 1) {
            $fix = $phpcsFile->addFixableError(
                'No space expected after opening parenthesis in void cast',
                $openParen + 1,
                'SpaceAfterOpenParen',
            );
            if ($fix) {
                for ($i = $openParen + 1; $i < $voidToken; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
            }
        }

        // Check space before closing parenthesis
        if ($closeParen !== $voidToken + 1) {
            $fix = $phpcsFile->addFixableError(
                'No space expected before closing parenthesis in void cast',
                $voidToken + 1,
                'SpaceBeforeCloseParen',
            );
            if ($fix) {
                for ($i = $voidToken + 1; $i < $closeParen; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
            }
        }
    }

    /**
     * Check that there's exactly one space after the cast
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $closeParen
     *
     * @return void
     */
    protected function checkSpacingAfterCast(File $phpcsFile, int $closeParen): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextToken = $closeParen + 1;
        if (!isset($tokens[$nextToken])) {
            return;
        }

        if ($tokens[$nextToken]['code'] !== T_WHITESPACE) {
            $fix = $phpcsFile->addFixableError(
                'Expected 1 space after void cast, but 0 found',
                $closeParen,
                'MissingSpaceAfter',
            );
            if ($fix) {
                $phpcsFile->fixer->addContent($closeParen, ' ');
            }
        } else {
            $nextNonWhitespace = $phpcsFile->findNext(Tokens::$emptyTokens, $nextToken, null, true);
            if ($nextNonWhitespace && $tokens[$nextNonWhitespace]['line'] === $tokens[$closeParen]['line']) {
                // Same line - should be exactly one space
                if ($tokens[$nextToken]['content'] !== ' ') {
                    $fix = $phpcsFile->addFixableError(
                        'Expected 1 space after void cast, but %d found',
                        $nextToken,
                        'TooManySpacesAfter',
                        [strlen($tokens[$nextToken]['content'])],
                    );
                    if ($fix) {
                        $phpcsFile->fixer->replaceToken($nextToken, ' ');
                    }
                }
            }
        }
    }
}
