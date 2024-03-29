<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

class EnumCaseCasingSniff extends AbstractSniff
{
    /**
     * @return array<int, (int|string)>
     */
    public function register(): array
    {
        return [T_ENUM_CASE];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
        if (!$nextIndex || $tokens[$nextIndex]['code'] !== T_STRING) {
            return;
        }

        $content = $tokens[$nextIndex]['content'];
        if (!preg_match('/[A-Z]/', $content)) {
            return;
        }

        if (str_contains($content, '_')) {
            $phpcsFile->addError('Enum cases must be in PascalCase format', $nextIndex, 'NotFixableWrongCasing');

            return;
        }

        if (preg_match('/[a-z]/', $content)) {
            if (preg_match('/^[a-z]/', $content)) {
                $phpcsFile->addError('Enum cases must be in PascalCase format', $nextIndex, 'NotFixableWrongCasing');
            }

            return;
        }

        $result = mb_strtolower($content);
        $result = ucfirst($result);

        if ($result === $content) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Enum cases must be in PascalCase format', $nextIndex, 'WrongCasing');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($nextIndex, $result);
        $phpcsFile->fixer->endChangeset();
    }
}
