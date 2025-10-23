<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Traits\BasicsTrait;

/**
 * Checks that no YODA conditions (reversed order of natural conditions) are being used.
 */
class ConditionalExpressionOrderSniff implements Sniff
{
    use BasicsTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return Tokens::$comparisonTokens;
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPointer)
    {
        $tokens = $phpcsFile->getTokens();

        $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($stackPointer - 1), null, true);
        if (!in_array($tokens[$prevIndex]['code'], [T_TRUE, T_FALSE, T_NULL, T_LNUMBER, T_CONSTANT_ENCAPSED_STRING])) {
            return;
        }

        $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, ($prevIndex - 1), null, true);
        if (!$prevIndex) {
            return;
        }
        if ($this->isGivenKind(Tokens::$arithmeticTokens, $tokens[$prevIndex])) {
            return;
        }
        if ($this->isGivenKind([T_STRING_CONCAT], $tokens[$prevIndex])) {
            return;
        }

        $error = 'Usage of Yoda conditions is not allowed. Switch the expression order.';
        $prevContent = $tokens[$prevIndex]['content'];

        if (
            !$this->isGivenKind(Tokens::$assignmentTokens, $tokens[$prevIndex])
            && !$this->isGivenKind(Tokens::$booleanOperators, $tokens[$prevIndex])
            && $prevContent !== '('
        ) {
            // Not fixable
            $phpcsFile->addError($error, $stackPointer, 'YodaNotAllowed');

            return;
        }

        //TODO
    }
}
