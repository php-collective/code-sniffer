<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * Alternative control structures should be auto-fixed back to normal ones within normal PHP code.
 * Template code can skip this one if needed. But even there it is usually easier with this one.
 */
class DisallowAlternativeControlStructuresSniff extends AbstractSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_ENDIF, T_ENDFOR, T_ENDFOREACH, T_ENDSWITCH, T_ENDWHILE];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $token = $tokens[$stackPtr];
        if (empty($token['scope_opener'])) {
            return;
        }

        $openerIndex = $token['scope_opener'];
        $openerToken = $tokens[$openerIndex];

        $fixable = false;
        if ($openerToken['code'] === T_COLON) {
            $fixable = true;
        }

        if (!$fixable) {
            $phpcsFile->addError('Alternative control structure syntax should not be used', $openerIndex, 'AlternativeForbidden');

            return;
        }

        $fix = $phpcsFile->addFixableError('Alternative control structure syntax should not be used', $openerIndex, 'AlternativeForbidden');
        if (!$fix) {
            return;
        }

        $nextPtr = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        $semicolonPtr = null;
        if ($nextPtr && $tokens[$nextPtr]['code'] === T_SEMICOLON) {
            $semicolonPtr = $nextPtr;
        }

        $phpcsFile->fixer->beginChangeset();

        $phpcsFile->fixer->replaceToken($stackPtr, '}');
        if ($semicolonPtr) {
            $phpcsFile->fixer->replaceToken($semicolonPtr, '');
        }

        $phpcsFile->fixer->replaceToken($openerIndex, '{');

        $phpcsFile->fixer->endChangeset();
    }
}
