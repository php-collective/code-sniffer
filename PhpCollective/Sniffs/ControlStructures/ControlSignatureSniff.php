<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Verifies that control statements conform to their coding standards.
 *
 * Ensures that:
 * - Closing braces and subsequent else/elseif/catch/finally keywords are on the same line
 * - Single space separates the closing brace from the keyword
 */
class ControlSignatureSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_ELSE,
            T_ELSEIF,
            T_CATCH,
            T_FINALLY,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Check for alternative syntax (colon instead of braces)
        if (
            isset($tokens[$stackPtr]['scope_opener']) === true
            && $tokens[$tokens[$stackPtr]['scope_opener']]['code'] === T_COLON
        ) {
            // Alternative syntax, skip
            return;
        }

        // Find the closing brace before this keyword
        $closer = $phpcsFile->findPrevious(Tokens::EMPTY_TOKENS, ($stackPtr - 1), null, true);
        if ($closer === false || $tokens[$closer]['code'] !== T_CLOSE_CURLY_BRACKET) {
            return;
        }

        // Check if closing brace and keyword are on different lines
        if ($tokens[$closer]['line'] === $tokens[$stackPtr]['line']) {
            // Already on same line, nothing to fix
            return;
        }

        // Check if there's a comment between the closing brace and keyword
        if ($phpcsFile->findNext(Tokens::COMMENT_TOKENS, ($closer + 1), $stackPtr) !== false) {
            // Comment found, don't auto-fix to preserve it
            $error = 'Expected closing brace and %s keyword on same line; found on separate lines';
            $data = [strtolower($tokens[$stackPtr]['content'])];
            $phpcsFile->addError($error, $closer, 'SpaceAfterCloseBrace', $data);

            return;
        }

        // Add fixable error
        $error = 'Expected closing brace and %s keyword on same line; found on separate lines';
        $data = [strtolower($tokens[$stackPtr]['content'])];
        $fix = $phpcsFile->addFixableError($error, $closer, 'SpaceAfterCloseBrace', $data);

        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();

            // Replace all tokens between closing brace and keyword with a single space
            for ($i = ($closer + 1); $i < $stackPtr; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            // Add single space after closing brace
            $phpcsFile->fixer->addContent($closer, ' ');

            $phpcsFile->fixer->endChangeset();
        }
    }
}
