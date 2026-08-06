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
     * PHP 8.5 tokenizes `|>` as a single T_PIPE token. On older versions, and on PHP 8.5
     * whenever the two characters are separated, it comes through as T_BITWISE_OR followed
     * by T_GREATER_THAN. Both forms have to be registered.
     *
     * @inheritDoc
     */
    public function register(): array
    {
        $tokens = [T_BITWISE_OR];

        if (defined('T_PIPE')) {
            /** @var int $pipe */
            $pipe = constant('T_PIPE');
            $tokens[] = $pipe;
        }

        return $tokens;
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // PHP 8.5+: the whole operator is a single token, so there is nothing in between.
        if ($tokens[$stackPtr]['type'] === 'T_PIPE') {
            $this->checkSpacingBefore($phpcsFile, $stackPtr);
            $this->checkSpacingAfter($phpcsFile, $stackPtr);

            return;
        }

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
                // Grow the preceding token rather than the operator itself: on PHP 8.5 the
                // whole `|>` is one token, so the "after" fix would target the same index and
                // one of the two changes would be dropped.
                $phpcsFile->fixer->addContent($stackPtr - 1, ' ');
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
     * @param int $pipeEnd Pointer to the last token of the operator: the `>` on the two-token
     *   form, or the whole `|>` token on PHP 8.5+.
     *
     * @return void
     */
    protected function checkSpacingAfter(File $phpcsFile, int $pipeEnd): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, $pipeEnd + 1, null, true);
        if (!$nextIndex) {
            return;
        }

        // Check if next token is on a different line
        if ($tokens[$nextIndex]['line'] !== $tokens[$pipeEnd]['line']) {
            return;
        }

        if ($tokens[$pipeEnd + 1]['code'] !== T_WHITESPACE) {
            $message = 'Expected at least 1 space after ">"; 0 found';
            $fix = $phpcsFile->addFixableError($message, $pipeEnd, 'MissingAfter');
            if ($fix) {
                $phpcsFile->fixer->addContentBefore($pipeEnd + 1, ' ');
            }
        } else {
            $content = $tokens[$pipeEnd + 1]['content'];
            if ($content !== ' ' && $tokens[$nextIndex]['line'] === $tokens[$pipeEnd]['line']) {
                $message = 'Expected 1 space after ">", but %d found';
                $data = [strlen($content)];
                $fix = $phpcsFile->addFixableError($message, $pipeEnd, 'TooManyAfter', $data);
                if ($fix) {
                    $phpcsFile->fixer->replaceToken($pipeEnd + 1, ' ');
                }
            }
        }
    }
}
