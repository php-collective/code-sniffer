<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Operators;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PHPCSUtils\Utils\Operators;

/**
 * Places multiline operators at the start of the continuation line.
 */
class OperatorPositionSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_BOOLEAN_AND, T_BOOLEAN_OR,
            T_LOGICAL_AND, T_LOGICAL_OR, T_LOGICAL_XOR,
            T_COALESCE, T_INLINE_THEN, T_INLINE_ELSE,
            T_PLUS, T_MINUS, T_MULTIPLY, T_DIVIDE, T_MODULUS, T_POW,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $code = $tokens[$stackPtr]['code'];

        if (($code === T_PLUS || $code === T_MINUS) && Operators::isUnaryPlusMinus($phpcsFile, $stackPtr)) {
            return;
        }

        $previous = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if ($previous === false || $tokens[$previous]['line'] !== $tokens[$stackPtr]['line']) {
            return;
        }

        if ($code === T_INLINE_ELSE && $tokens[$previous]['code'] === T_INLINE_THEN) {
            return;
        }

        $end = $stackPtr;
        if ($code === T_INLINE_THEN) {
            $following = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
            if ($following !== false && $tokens[$following]['code'] === T_INLINE_ELSE) {
                if ($tokens[$following]['line'] !== $tokens[$stackPtr]['line']) {
                    $this->processSplitShortTernary($phpcsFile, $stackPtr, $following);

                    return;
                }
                $end = $following;
            }
        }

        $next = $phpcsFile->findNext(Tokens::$emptyTokens, $end + 1, null, true);
        if ($next === false || $tokens[$next]['line'] === $tokens[$end]['line']) {
            return;
        }

        $hasComment = false;
        for ($i = $stackPtr + 1; $i < $next; $i++) {
            if (isset(Tokens::$commentTokens[$tokens[$i]['code']])) {
                $hasComment = true;

                break;
            }
        }

        $operator = $end === $stackPtr ? $tokens[$stackPtr]['content'] : '?:';
        $message = 'Operator "%s" must be placed at the start of the continuation line';
        if ($hasComment) {
            $phpcsFile->addError($message, $stackPtr, 'IncorrectWithComment', [$operator]);

            return;
        }

        if (!$phpcsFile->addFixableError($message, $stackPtr, 'Incorrect', [$operator])) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        for ($i = $stackPtr; $i <= $end; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }
        if ($tokens[$stackPtr - 1]['code'] === T_WHITESPACE) {
            $phpcsFile->fixer->replaceToken($stackPtr - 1, '');
        }
        if ($tokens[$end + 1]['code'] === T_WHITESPACE) {
            $whitespace = $tokens[$end + 1]['content'];
            $phpcsFile->fixer->replaceToken($end + 1, preg_replace('/^[ \t]+(?=\r?\n)/', '', $whitespace));
        }
        $phpcsFile->fixer->addContentBefore($next, $operator . ' ');
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Handles `$a ?` followed by `: $b` on the next line by joining both into `?:` there.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $thenPtr
     * @param int $elsePtr
     *
     * @return void
     */
    protected function processSplitShortTernary(File $phpcsFile, int $thenPtr, int $elsePtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $message = 'Operator "?:" must be placed at the start of the continuation line';

        for ($i = $thenPtr + 1; $i < $elsePtr; $i++) {
            if (isset(Tokens::$commentTokens[$tokens[$i]['code']])) {
                $phpcsFile->addError($message, $thenPtr, 'IncorrectWithComment');

                return;
            }
        }

        if (!$phpcsFile->addFixableError($message, $thenPtr, 'Incorrect')) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($thenPtr, '');
        if ($tokens[$thenPtr - 1]['code'] === T_WHITESPACE) {
            $phpcsFile->fixer->replaceToken($thenPtr - 1, '');
        }
        $whitespace = $tokens[$thenPtr + 1]['code'] === T_WHITESPACE ? $tokens[$thenPtr + 1]['content'] : '';
        if ($whitespace !== '') {
            $phpcsFile->fixer->replaceToken($thenPtr + 1, (string)preg_replace('/^[ \t]+(?=\r?\n)/', '', $whitespace));
        }
        $phpcsFile->fixer->addContentBefore($elsePtr, '?');
        $phpcsFile->fixer->endChangeset();
    }
}
