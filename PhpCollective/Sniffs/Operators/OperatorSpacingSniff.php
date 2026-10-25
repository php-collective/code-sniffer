<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Operators;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Operators\OperatorSpacingSniff as Psr12OperatorSpacingSniff;
use PhpCollective\Sniffs\WhiteSpace\CatchTypeSeparatorSpacingSniff;

/**
 * Applies PSR12 operator spacing outside catch type lists.
 */
class OperatorSpacingSniff extends Psr12OperatorSpacingSniff
{
    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return int|null
     */
    public function process(File $phpcsFile, int $stackPtr): ?int
    {
        if (CatchTypeSeparatorSpacingSniff::isCatchTypeSeparator($phpcsFile, $stackPtr)) {
            return null;
        }

        // The parent returns a pointer to skip over declare() statements
        return parent::process($phpcsFile, $stackPtr);
    }
}
