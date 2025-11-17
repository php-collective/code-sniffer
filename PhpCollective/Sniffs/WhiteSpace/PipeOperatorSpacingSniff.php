<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Ensures proper spacing around the pipe operator (|>) - a PHP 8.5 feature.
 *
 * The pipe operator allows for more readable function chaining:
 * $result = $input |> trim(...) |> strtolower(...);
 */
class PipeOperatorSpacingSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_BITWISE_OR];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Check if this is part of a pipe operator (|>)
        $nextNonWhitespace = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
        if (!$nextNonWhitespace || $tokens[$nextNonWhitespace]['code'] !== T_GREATER_THAN) {
            return;
        }

        // Check if there's whitespace between | and >
        if ($nextNonWhitespace !== $stackPtr + 1) {
            $fix = $phpcsFile->addFixableError(
                'Expected at least 1 space before ">"; 0 found',
                $stackPtr,
                'SpaceBetweenPipe',
            );
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                for ($i = $stackPtr + 1; $i < $nextNonWhitespace; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
                $phpcsFile->fixer->endChangeset();
            }
        }

        // Now check spacing around the pipe operator
        $this->checkSpacingBefore($phpcsFile, $stackPtr);
        $this->checkSpacingAfter($phpcsFile, $nextNonWhitespace);
    }

    /**
     * Check that there's exactly one space before the pipe operator
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkSpacingBefore(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $prevIndex = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if (!$prevIndex) {
            return;
        }

        // Check if we're at the start of a line
        if ($tokens[$prevIndex]['line'] !== $tokens[$stackPtr]['line']) {
            return;
        }

        if ($tokens[$stackPtr - 1]['code'] !== T_WHITESPACE) {
            $message = 'Expected at least 1 space before "|"; 0 found';
            $fix = $phpcsFile->addFixableError($message, $stackPtr, 'MissingBefore');
            if ($fix) {
                $phpcsFile->fixer->addContentBefore($stackPtr, ' ');
            }
        } else {
            $content = $tokens[$stackPtr - 1]['content'];
            if ($content !== ' ' && $tokens[$prevIndex]['line'] === $tokens[$stackPtr]['line']) {
                $message = 'Expected 1 space before "|", but %d found';
                $data = [strlen($content)];
                $fix = $phpcsFile->addFixableError($message, $stackPtr, 'TooManyBefore', $data);
                if ($fix) {
                    $phpcsFile->fixer->replaceToken($stackPtr - 1, ' ');
                }
            }
        }
    }

    /**
     * Check that there's exactly one space after the pipe operator
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $greaterThanPtr Pointer to the > token
     *
     * @return void
     */
    protected function checkSpacingAfter(File $phpcsFile, int $greaterThanPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, $greaterThanPtr + 1, null, true);
        if (!$nextIndex) {
            return;
        }

        // Check if next token is on a different line
        if ($tokens[$nextIndex]['line'] !== $tokens[$greaterThanPtr]['line']) {
            return;
        }

        if ($tokens[$greaterThanPtr + 1]['code'] !== T_WHITESPACE) {
            $message = 'Expected at least 1 space after ">"; 0 found';
            $fix = $phpcsFile->addFixableError($message, $greaterThanPtr, 'MissingAfter');
            if ($fix) {
                $phpcsFile->fixer->addContent($greaterThanPtr, ' ');
            }
        } else {
            $content = $tokens[$greaterThanPtr + 1]['content'];
            if ($content !== ' ' && $tokens[$nextIndex]['line'] === $tokens[$greaterThanPtr]['line']) {
                $message = 'Expected 1 space after ">", but %d found';
                $data = [strlen($content)];
                $fix = $phpcsFile->addFixableError($message, $greaterThanPtr, 'TooManyAfter', $data);
                if ($fix) {
                    $phpcsFile->fixer->replaceToken($greaterThanPtr + 1, ' ');
                }
            }
        }
    }
}
