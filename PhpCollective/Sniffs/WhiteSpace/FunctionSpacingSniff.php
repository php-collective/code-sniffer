<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * There should always be newlines around functions/methods.
 */
class FunctionSpacingSniff extends AbstractSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_FUNCTION];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPointer)
    {
        $tokens = $phpcsFile->getTokens();

        $level = $tokens[$stackPointer]['level'];
        if ($level < 1) {
            return;
        }

        $openingBraceIndex = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPointer + 1);
        // Fix interface methods
        if (!$openingBraceIndex) {
            $openingParenthesisIndex = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPointer + 1);
            $closingParenthesisIndex = $tokens[$openingParenthesisIndex]['parenthesis_closer'];

            $semicolonIndex = $phpcsFile->findNext(T_SEMICOLON, $closingParenthesisIndex + 1);
            if (!$semicolonIndex) {
                return;
            }

            $nextContentIndex = $phpcsFile->findNext(T_WHITESPACE, $semicolonIndex + 1, null, true);
            if (!$nextContentIndex) {
                return;
            }

            // Do not mess with the end of the class
            if ($tokens[$nextContentIndex]['type'] === 'T_CLOSE_CURLY_BRACKET') {
                return;
            }

            if ($tokens[$nextContentIndex]['line'] - $tokens[$semicolonIndex]['line'] <= 1) {
                $fix = $phpcsFile->addFixableError('Every function/method needs a newline afterwards', $closingParenthesisIndex, 'AbstractAfter');
                if ($fix) {
                    $phpcsFile->fixer->addNewline($semicolonIndex);
                }
            }

            return;
        }

        if (empty($tokens[$openingBraceIndex]['scope_closer'])) {
            return;
        }

        $closingBraceIndex = $tokens[$openingBraceIndex]['scope_closer'];

        // Ignore closures
        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $closingBraceIndex + 1, null, true);
        if (in_array($tokens[$nextIndex]['content'], [';', ',', ')'], true)) {
            return;
        }

        $nextContentIndex = $phpcsFile->findNext(T_WHITESPACE, $closingBraceIndex + 1, null, true);
        if (!$nextContentIndex) {
            return;
        }

        // Do not mess with the end of the class
        if ($tokens[$nextContentIndex]['type'] === 'T_CLOSE_CURLY_BRACKET') {
            return;
        }

        $this->assertNewLineAtTheEnd($phpcsFile, $closingBraceIndex, $nextContentIndex);
        $this->assertNewLineAtTheBeginning($phpcsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $closingBraceIndex
     * @param int|null $nextContentIndex
     *
     * @return void
     */
    protected function assertNewLineAtTheEnd(File $phpcsFile, int $closingBraceIndex, ?int $nextContentIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        if (!$nextContentIndex || $tokens[$nextContentIndex]['line'] - $tokens[$closingBraceIndex]['line'] <= 1) {
            $fix = $phpcsFile->addFixableError('Every function/method needs a newline afterwards', $closingBraceIndex, 'ConcreteAfter');
            if ($fix) {
                $phpcsFile->fixer->addNewline($closingBraceIndex);
            }
        }
    }

    /**
     * Asserts newline at the beginning, including the doc block.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function assertNewLineAtTheBeginning(File $phpcsFile, int $stackPointer): void
    {
        $tokens = $phpcsFile->getTokens();

        $firstTokenInLineIndex = $this->getFirstTokenOfLine($tokens, $stackPointer);

        $prevContentIndex = $phpcsFile->findPrevious(T_WHITESPACE, $firstTokenInLineIndex - 1, null, true);
        if ($tokens[$prevContentIndex]['type'] === 'T_ATTRIBUTE_END') {
            return;
        }

        if ($tokens[$prevContentIndex]['code'] === T_DOC_COMMENT_CLOSE_TAG) {
            $firstTokenInLineIndex = $tokens[$prevContentIndex]['comment_opener'];
            $line = $tokens[$firstTokenInLineIndex]['line'];
            while ($tokens[$firstTokenInLineIndex - 1]['line'] === $line) {
                $firstTokenInLineIndex--;
            }
        }

        $prevContentIndex = $phpcsFile->findPrevious(T_WHITESPACE, $firstTokenInLineIndex - 1, null, true);
        if (!$prevContentIndex) {
            return;
        }

        // Do not mess with the start of the class
        if ($tokens[$prevContentIndex]['type'] === 'T_OPEN_CURLY_BRACKET') {
            return;
        }

        if ($tokens[$prevContentIndex]['line'] < $tokens[$firstTokenInLineIndex]['line'] - 1) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Every function/method needs a newline before', $firstTokenInLineIndex, 'ConcreteBefore');
        if ($fix) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->addNewline($prevContentIndex);
            $phpcsFile->fixer->endChangeset();
        }
    }
}
