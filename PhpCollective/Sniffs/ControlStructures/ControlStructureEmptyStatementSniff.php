<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * Detects and fixes control structures with empty statements (e.g., `if (...);`).
 *
 * This catches the common bug where a semicolon is accidentally placed after the condition,
 * creating an empty statement instead of a block:
 *
 * ```php
 * if ($foo); // Empty statement - almost always a bug
 * {
 *     // This block is not part of the if!
 * }
 * ```
 *
 * Auto-fixes by converting the semicolon to an empty block: `if ($foo) {}`
 */
class ControlStructureEmptyStatementSniff extends AbstractSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_IF,
            T_ELSEIF,
            T_FOR,
            T_FOREACH,
            T_WHILE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Skip if there's no parenthesis (shouldn't happen, but be safe)
        if (!isset($tokens[$stackPtr]['parenthesis_closer'])) {
            return;
        }

        $closer = $tokens[$stackPtr]['parenthesis_closer'];

        // Find the next non-whitespace token after the closing parenthesis
        $next = $phpcsFile->findNext(Tokens::$emptyTokens, $closer + 1, null, true);

        if ($next === false) {
            return;
        }

        // Check if it's a semicolon (empty statement)
        if ($tokens[$next]['code'] === T_SEMICOLON) {
            $controlStructure = strtolower($tokens[$stackPtr]['content']);
            $error = sprintf(
                'Empty %s statement detected: semicolon found after closing parenthesis. Did you accidentally add a semicolon?',
                $controlStructure,
            );

            $fix = $phpcsFile->addFixableError($error, $next, 'EmptyStatement');
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();

                // Replace semicolon with opening brace
                $phpcsFile->fixer->replaceToken($next, ' {');

                // Add newline and closing brace after the semicolon position
                $phpcsFile->fixer->addContent($next, "\n}");

                $phpcsFile->fixer->endChangeset();
            }
        }
    }
}
