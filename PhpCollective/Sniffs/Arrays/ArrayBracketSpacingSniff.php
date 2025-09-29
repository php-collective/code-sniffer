<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures no extra blank lines before closing brackets in arrays.
 */
class ArrayBracketSpacingSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_OPEN_SHORT_ARRAY,
            T_ARRAY,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_ARRAY) {
            if (!isset($tokens[$stackPtr]['parenthesis_closer'])) {
                return;
            }
            $closerPtr = $tokens[$stackPtr]['parenthesis_closer'];
        } else {
            if (!isset($tokens[$stackPtr]['bracket_closer'])) {
                return;
            }
            $closerPtr = $tokens[$stackPtr]['bracket_closer'];
        }

        // Only process multi-line arrays
        if ($tokens[$stackPtr]['line'] === $tokens[$closerPtr]['line']) {
            return;
        }

        // Find the last non-empty token before the closer
        $lastContentPtr = $phpcsFile->findPrevious(
            T_WHITESPACE,
            $closerPtr - 1,
            $stackPtr,
            true,
        );

        if ($lastContentPtr === false) {
            return;
        }

        // Check if there are extra blank lines between last content and closer
        $lastContentLine = $tokens[$lastContentPtr]['line'];
        $closerLine = $tokens[$closerPtr]['line'];

        // We expect the closer to be on the next line after the last content
        // Any extra blank lines should be removed
        if ($closerLine - $lastContentLine > 1) {
            $error = 'Extra blank lines found before array closing bracket';

            // Check if the last content is a comment - these are problematic for auto-fixing
            if ($tokens[$lastContentPtr]['code'] === T_COMMENT) {
                // Report as non-fixable error when last element is a comment
                $phpcsFile->addError($error, $closerPtr, 'ExtraBlankLineBeforeCloser');

                return;
            }

            $fix = $phpcsFile->addFixableError($error, $closerPtr, 'ExtraBlankLineBeforeCloser');

            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();

                // Get the indentation for the closing bracket
                $indent = '';
                if ($tokens[$closerPtr]['column'] > 1) {
                    // Get the current line's indentation
                    $lineStart = $closerPtr;
                    while ($lineStart > 0 && $tokens[$lineStart - 1]['line'] === $tokens[$closerPtr]['line']) {
                        $lineStart--;
                    }
                    if ($lineStart < $closerPtr && $tokens[$lineStart]['code'] === T_WHITESPACE) {
                        $indent = $tokens[$lineStart]['content'];
                        // Remove any newlines from the indent
                        $indent = str_replace(["\n", "\r"], '', $indent);
                    }
                }

                // Find whitespace tokens between last content and closer
                $whitespaceTokens = [];
                for ($i = $lastContentPtr + 1; $i < $closerPtr; $i++) {
                    if ($tokens[$i]['code'] === T_WHITESPACE) {
                        $whitespaceTokens[] = $i;
                    }
                }

                // Find the position to insert the newline
                // If there's already whitespace, replace it; otherwise add new
                $nextToken = $lastContentPtr + 1;

                if ($nextToken < $closerPtr && $tokens[$nextToken]['code'] === T_WHITESPACE) {
                    // There's whitespace right after the last content
                    // Replace it with a single newline and indent
                    $phpcsFile->fixer->replaceToken($nextToken, "\n" . $indent);

                    // Remove any additional whitespace tokens
                    for ($i = $nextToken + 1; $i < $closerPtr; $i++) {
                        if ($tokens[$i]['code'] === T_WHITESPACE) {
                            $phpcsFile->fixer->replaceToken($i, '');
                        }
                    }
                } else {
                    // No whitespace immediately after, need to add it
                    $phpcsFile->fixer->addContent($lastContentPtr, "\n" . $indent);
                }

                $phpcsFile->fixer->endChangeset();
            }
        }
    }
}
