<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;
use Spryker\Tools\Traits\CommentingTrait;
use Spryker\Tools\Traits\SignatureTrait;

/**
 * Doc block {inheritDoc} should come before any tags.
 */
class DocBlockInheritSniff extends AbstractSprykerSniff
{
    use CommentingTrait;
    use SignatureTrait;

    protected const INHERIT_DOC = '{@inheritDoc}';
    protected const INHERIT_DOC_INVALID = '{inheritDoc}';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_DOC_COMMENT_OPEN_TAG,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $closingTagIndex = $tokens[$stackPtr]['comment_closer'];

        $this->assertType($phpcsFile, $stackPtr, $closingTagIndex);
        $this->assertOrder($phpcsFile, $stackPtr, $closingTagIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openingTagIndex
     * @param int $closingTagIndex
     *
     * @return void
     */
    protected function assertType(File $phpcsFile, int $openingTagIndex, int $closingTagIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $openingTagIndex + 1; $i < $closingTagIndex; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_STRING || empty($tokens[$i]['content'])) {
                continue;
            }

            if (stripos($tokens[$i]['content'], static::INHERIT_DOC_INVALID) !== 0) {
                continue;
            }

            $fix = $phpcsFile->addFixableError('Invalid type used for ' . static::INHERIT_DOC, $i, 'InvalidType');
            if (!$fix) {
                continue;
            }

            $phpcsFile->fixer->beginChangeset();

            $content = str_replace([static::INHERIT_DOC_INVALID, strtolower(static::INHERIT_DOC_INVALID)], static::INHERIT_DOC, $tokens[$i]['content']);
            $phpcsFile->fixer->replaceToken($i, $content);

            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openingTagIndex
     * @param int $closingTagIndex
     *
     * @return void
     */
    protected function assertOrder(File $phpcsFile, int $openingTagIndex, int $closingTagIndex): void
    {
        $inheritDocIndex = $this->getInheritDocIndex($phpcsFile, $openingTagIndex, $closingTagIndex);
        if (!$inheritDocIndex) {
            return;
        }

        $firstTagIndex = $this->getFirstTagIndex($phpcsFile, $openingTagIndex, $closingTagIndex);
        if (!$firstTagIndex) {
            return;
        }

        if ($firstTagIndex > $inheritDocIndex) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $fix = $phpcsFile->addFixableError('`' . static::INHERIT_DOC . '` should come before any tags (`' . $tokens[$firstTagIndex]['content'] . '`).', $inheritDocIndex, 'InvalidOrder');
        if (!$fix) {
            return;
        }

        $inheritDocLineBeginningIndex = $this->getFirstTokenOfLine($tokens, $inheritDocIndex);
        $firstTagLineBeginningIndex = $this->getFirstTokenOfLine($tokens, $firstTagIndex);

        $content = '';
        $i = $inheritDocLineBeginningIndex;

        $phpcsFile->fixer->beginChangeset();
        while ($tokens[$i]['line'] === $tokens[$inheritDocLineBeginningIndex]['line']) {
            $content .= $tokens[$i]['content'];
            $phpcsFile->fixer->replaceToken($i, '');
            $i++;
        }

        $i = $firstTagLineBeginningIndex;
        $phpcsFile->fixer->addContentBefore($i, $content);

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openingTagIndex
     * @param int $closingTagIndex
     *
     * @return int|null
     */
    protected function getInheritDocIndex(File $phpcsFile, int $openingTagIndex, int $closingTagIndex): ?int
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $openingTagIndex + 1; $i < $closingTagIndex; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_STRING || empty($tokens[$i]['content'])) {
                continue;
            }

            if (stripos($tokens[$i]['content'], static::INHERIT_DOC) === 0) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $openingTagIndex
     * @param int $closingTagIndex
     *
     * @return int|null
     */
    protected function getFirstTagIndex(File $phpcsFile, int $openingTagIndex, int $closingTagIndex): ?int
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $openingTagIndex + 1; $i < $closingTagIndex; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_TAG) {
                continue;
            }

            return $i;
        }

        return null;
    }
}