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
 * No whitespace should be between implicit cast and variable, the same as with other casts.
 * This includes incrementor and decrementor.
 */
class ImplicitCastSpacingSniff implements Sniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_BOOLEAN_NOT, T_NONE, T_ASPERAND, T_INC, T_DEC, T_MINUS];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_INC || $tokens[$stackPtr]['code'] === T_DEC) {
            $this->processIncDec($phpcsFile, $stackPtr);

            return;
        }

        // A minus is only an implicit cast when it negates; as a subtraction it wants its spaces.
        if ($tokens[$stackPtr]['code'] === T_MINUS) {
            if (!$this->isUnaryOperator($phpcsFile, $stackPtr)) {
                return;
            }

            // `- -$i` must keep its space: closing it up would produce `--$i`, a decrement.
            $followingIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
            if ($followingIndex !== false && in_array($tokens[$followingIndex]['code'], [T_MINUS, T_DEC], true)) {
                return;
            }
        }

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);

        if ($nextIndex === false || $nextIndex - $stackPtr === 1) {
            return;
        }

        $fix = $phpcsFile->addFixableError('No whitespace should be between ' . $tokens[$stackPtr]['content'] . ' and variable.', $stackPtr, 'WhitespaceBetweenContentVariable');
        if ($fix && $phpcsFile->fixer->enabled) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($stackPtr + 1, '');
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * A minus is unary only when what precedes it cannot end a value - an operator, an opening
     * bracket, a comma, `return` and so on.
     *
     * The test is deliberately this way round. Listing what may PRECEDE a subtraction instead
     * would have to enumerate every value-producing token, and anything forgotten (`__LINE__`,
     * `true`, `null`, a qualified constant) would be read as a negation and "fixed" into
     * `__LINE__ -1`. Defaulting to binary keeps an unknown predecessor harmless.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return bool
     */
    protected function isUnaryOperator(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        $previousIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if ($previousIndex === false) {
            return true;
        }

        static $unaryPrefixes = null;

        if ($unaryPrefixes === null) {
            $unaryPrefixes = Tokens::$operators
                + Tokens::$assignmentTokens
                + Tokens::$comparisonTokens
                + Tokens::$booleanOperators
                + Tokens::$castTokens
                + [
                    T_OPEN_PARENTHESIS => T_OPEN_PARENTHESIS,
                    T_OPEN_SQUARE_BRACKET => T_OPEN_SQUARE_BRACKET,
                    T_OPEN_SHORT_ARRAY => T_OPEN_SHORT_ARRAY,
                    T_OPEN_CURLY_BRACKET => T_OPEN_CURLY_BRACKET,
                    T_COMMA => T_COMMA,
                    T_SEMICOLON => T_SEMICOLON,
                    T_COLON => T_COLON,
                    T_DOUBLE_ARROW => T_DOUBLE_ARROW,
                    T_INLINE_THEN => T_INLINE_THEN,
                    T_INLINE_ELSE => T_INLINE_ELSE,
                    T_RETURN => T_RETURN,
                    T_ECHO => T_ECHO,
                    T_PRINT => T_PRINT,
                    T_CASE => T_CASE,
                    T_BOOLEAN_NOT => T_BOOLEAN_NOT,
                    T_YIELD => T_YIELD,
                    T_YIELD_FROM => T_YIELD_FROM,
                    T_THROW => T_THROW,
                    T_FN_ARROW => T_FN_ARROW,
                    T_MATCH_ARROW => T_MATCH_ARROW,
                    T_OPEN_TAG => T_OPEN_TAG,
                    T_OPEN_TAG_WITH_ECHO => T_OPEN_TAG_WITH_ECHO,
                ];
        }

        return isset($unaryPrefixes[$tokens[$previousIndex]['code']]);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function processIncDec(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        if ($nextIndex !== false && $tokens[$nextIndex]['code'] === T_VARIABLE) {
            if ($nextIndex - $stackPtr === 1) {
                return;
            }

            $fix = $phpcsFile->addFixableError('No whitespace should be between incrementor and variable.', $stackPtr, 'WhitespaceBeforeVariable');
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($stackPtr + 1, '');
                $phpcsFile->fixer->endChangeset();
            }

            return;
        }

        $prevIndex = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if ($prevIndex !== false && $tokens[$prevIndex]['code'] === T_VARIABLE) {
            if ($stackPtr - $prevIndex === 1) {
                return;
            }

            $fix = $phpcsFile->addFixableError('No whitespace should be between variable and incrementor.', $stackPtr, 'WhitespaceAfterVariable');
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($stackPtr - 1, '');
                $phpcsFile->fixer->endChangeset();
            }

            return;
        }
    }
}
