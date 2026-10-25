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
 * Requires catch type separators to have no surrounding whitespace.
 */
class CatchTypeSeparatorSpacingSniff implements Sniff
{
    public function register(): array
    {
        return [T_BITWISE_OR];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        if (!self::isCatchTypeSeparator($phpcsFile, $stackPtr)) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $before = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        $after = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);

        if ($before === false || $after === false) {
            return;
        }

        $newline = $tokens[$before]['line'] !== $tokens[$stackPtr]['line']
            || $tokens[$after]['line'] !== $tokens[$stackPtr]['line'];
        $spacing = false;
        for ($i = $before + 1; $i < $after; $i++) {
            if ($tokens[$i]['code'] === T_WHITESPACE) {
                $spacing = true;

                break;
            }
        }
        if (!$newline && !$spacing) {
            return;
        }

        $message = 'Expected no whitespace around the catch type separator';
        if ($newline) {
            $phpcsFile->addError($message, $stackPtr, 'SpacesAround');

            return;
        }

        $fix = $phpcsFile->addFixableError($message, $stackPtr, 'SpacesAround');
        if ($fix) {
            $phpcsFile->fixer->beginChangeset();
            for ($i = $before + 1; $i < $after; $i++) {
                if ($i !== $stackPtr && $tokens[$i]['code'] === T_WHITESPACE) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
            }
            $phpcsFile->fixer->endChangeset();
        }
    }

    public static function isCatchTypeSeparator(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        if ($tokens[$stackPtr]['code'] !== T_BITWISE_OR || empty($tokens[$stackPtr]['nested_parenthesis'])) {
            return false;
        }

        $openers = array_keys($tokens[$stackPtr]['nested_parenthesis']);
        $opener = end($openers);

        return isset($tokens[$opener]['parenthesis_owner'])
            && $tokens[$tokens[$opener]['parenthesis_owner']]['code'] === T_CATCH;
    }
}
