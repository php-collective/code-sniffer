<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * Converts double quotes to single quotes for simple strings.
 *
 * @author Gregor Harlan <gharlan@web.de>
 * @author Mark Scherer
 */
class SingleQuoteSniff extends AbstractSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_CONSTANT_ENCAPSED_STRING];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Skip for complex multiline
        $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
        if ($prevIndex && $tokens[$prevIndex]['code'] === T_CONSTANT_ENCAPSED_STRING) {
            return;
        }

        $content = $tokens[$stackPtr]['content'];
        if (
            $content[0] === '"'
            && !str_contains($content, "'")
            && !str_contains($content, "\n")
            // regex: odd number of backslashes, not followed by double quote or dollar
            && !preg_match('/(?<!\\\\)(?:\\\\{2})*\\\\(?!["$\\\\])/', $content)
        ) {
            $fix = $phpcsFile->addFixableError(
                'Use single instead of double quotes for simple strings.',
                $stackPtr,
                'UseSingleQuote',
            );
            if ($fix) {
                $content = substr($content, 1, -1);
                $content = str_replace(['\\"', '\\$'], ['"', '$'], $content);
                $phpcsFile->fixer->replaceToken($stackPtr, '\'' . $content . '\'');
            }
        }
    }
}
