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

                // Remove all tokens between last content and closer
                for ($i = $lastContentPtr + 1; $i < $closerPtr; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }

                // Add a single newline with proper indentation after the last content
                $phpcsFile->fixer->addContent($lastContentPtr, "\n" . $indent);

                $phpcsFile->fixer->endChangeset();
            }
        }
    }
}
