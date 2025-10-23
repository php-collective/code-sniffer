<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * Ensures Doc Blocks for variables are not just type null, but always another type and optionally nullable on top.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockVarNotJustNullSniff extends AbstractSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_VARIABLE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPointer): void
    {
        $tokens = $phpcsFile->getTokens();

        $previousIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPointer - 1, null, true);
        if ($previousIndex && $tokens[$previousIndex]['code'] === T_STATIC) {
            $previousIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $previousIndex - 1, null, true);
        }

        if (!$this->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE], $tokens[$previousIndex])) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $varIndex = null;
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@var'], true)) {
                continue;
            }

            $varIndex = $i;
        }

        if (!$varIndex) {
            return;
        }

        $typeIndex = (int)$varIndex + 2;
        if (!isset($tokens[$typeIndex]) || !isset($tokens[$typeIndex]['content'])) {
            return;
        }

        $content = $tokens[$typeIndex]['content'];
        $spaceIndex = strpos($content, ' ');
        if ($spaceIndex) {
            $content = substr($content, 0, $spaceIndex);
        }

        if (!$content) {
            return;
        }

        if ($content !== 'null') {
            return;
        }

        $phpcsFile->addError('Doc Block type `' . $content . '` for annotation @var not enough.', $stackPointer, 'VarTypeIncorrect');
    }
}
