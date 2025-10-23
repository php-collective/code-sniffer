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
 * Checks if inline doc blocks have the correct order and format.
 */
class InlineDocBlockSniff extends AbstractSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_FUNCTION,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPointer): void
    {
        $tokens = $phpcsFile->getTokens();
        $startIndex = $phpcsFile->findNext(T_OPEN_CURLY_BRACKET, $stackPointer + 1);
        if (!$startIndex || empty($tokens[$startIndex]['bracket_closer'])) {
            return;
        }

        $endIndex = $tokens[$startIndex]['bracket_closer'];

        $this->fixDocCommentOpenTags($phpcsFile, $startIndex, $endIndex);

        $this->checkInlineComments($phpcsFile, $startIndex, $endIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return void
     */
    protected function fixDocCommentOpenTags(File $phpcsFile, int $startIndex, int $endIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            if ($tokens[$i]['code'] !== T_COMMENT) {
                continue;
            }

            if (!preg_match('|^\/\*\s*@\w+ (.+)|', $tokens[$i]['content'])) {
                continue;
            }

            $fix = $phpcsFile->addFixableError('Inline Doc Block comment should be using `/** ... */`', $i, 'InlineDocBlock');
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();

                $comment = $tokens[$i]['content'];
                $comment = str_replace('/*', '/**', $comment);

                $phpcsFile->fixer->replaceToken($i, $comment);

                $phpcsFile->fixer->endChangeset();
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $startIndex
     * @param int $endIndex
     *
     * @return void
     */
    protected function checkInlineComments(File $phpcsFile, int $startIndex, int $endIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_OPEN_TAG) {
                continue;
            }

            $commentEndTagIndex = $tokens[$i]['comment_closer'];

            if ($this->isNotInline($phpcsFile, $commentEndTagIndex)) {
                continue;
            }

            $isSingleLine = false;
            if ($tokens[$i]['line'] === $tokens[$commentEndTagIndex]['line']) {
                $isSingleLine = true;
            }

            $typeTag = $this->findTagIndex($tokens, $i, $commentEndTagIndex, T_DOC_COMMENT_TAG);
            $contentTag = $typeTag ? $this->findTagIndex($tokens, $typeTag, $commentEndTagIndex, T_DOC_COMMENT_STRING) : null;
            if ($typeTag === null || $contentTag === null && !$this->isAllowedTag($tokens[$typeTag]['content'])) {
                $phpcsFile->addError('Invalid Inline Doc Block', $i, 'DocBlockInvalid');

                continue;
            }

            if ($contentTag === null || $tokens[$typeTag]['content'] !== '@var') {
                // We ignore those
                continue;
            }

            $errors = $this->findErrors($phpcsFile, $contentTag, $isSingleLine);

            if (!$errors) {
                continue;
            }

            $fix = $phpcsFile->addFixableError('Invalid Inline Doc Block content: ' . implode(', ', $errors), $i, 'DocBlockContentInvalid');
            if (!$fix) {
                continue;
            }

            $phpcsFile->fixer->beginChangeset();

            $comment = $tokens[$contentTag]['content'];

            if (isset($errors['space-before-end']) || isset($errors['end'])) {
                $comment .= ' ';
            }

            if (isset($errors['order'])) {
                $comment = preg_replace('|^(.+?)\s+(.+?)\s*$|', '\2 \1 ', $comment);
            }

            $phpcsFile->fixer->replaceToken($contentTag, $comment);

            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    protected function isAllowedTag(string $tag): bool
    {
        if (str_starts_with($tag, '@phpstan-') || str_starts_with($tag, '@psalm-')) {
            return true;
        }

        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @param int $from
     * @param int $to
     * @param string $tagType
     *
     * @return int|null
     */
    protected function findTagIndex(array $tokens, int $from, int $to, string $tagType): ?int
    {
        for ($i = $from + 1; $i < $to; $i++) {
            if ($tokens[$i]['code'] === $tagType) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $contentIndex
     * @param bool $isSingleLine
     *
     * @return array<string>
     */
    protected function findErrors(File $phpcsFile, int $contentIndex, bool $isSingleLine): array
    {
        $tokens = $phpcsFile->getTokens();

        $comment = $tokens[$contentIndex]['content'];

        // SKip for complex arrays until next major
        if (str_contains($comment, '<') || str_starts_with($comment, 'array{') || str_starts_with($comment, 'object{')) {
            return [];
        }

        preg_match('#^(.+?)(\s+)(.+?)\s*$#', $comment, $contentMatches);
        if (!$contentMatches || empty($contentMatches[1]) || empty($contentMatches[2]) || empty($contentMatches[3])) {
            if ($this->hasReturnAsFollowingToken($phpcsFile, $contentIndex)) {
                return [];
            }

            $phpcsFile->addError('Invalid Inline Doc Block content, expected `{Type} ${var}` style', $contentIndex, 'ContentInvalid');

            return [];
        }

        $errors = [];

        if ($isSingleLine && !preg_match('|([a-z0-9]) $|i', $comment)) {
            $errors['space-before-end'] = 'Expected single space before ´*/´';
        }

        if (!preg_match('|^\$[a-z0-9_]+$|i', $contentMatches[3])) {
            $errors['order'] = 'Expected `{Type} ${var}`, got `' . $contentMatches[1] . $contentMatches[2] . $contentMatches[3] . '`';
        }

        return $errors;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $contentIndex
     *
     * @return bool
     */
    protected function hasReturnAsFollowingToken(File $phpcsFile, int $contentIndex): bool
    {
        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $contentIndex + 1, null, true);
        if (!$nextIndex) {
            return false;
        }

        $tokens = $phpcsFile->getTokens();

        return $tokens[$nextIndex]['code'] === T_RETURN;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $commentEndTagIndex
     *
     * @return bool
     */
    protected function isNotInline(File $phpcsFile, int $commentEndTagIndex): bool
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $commentEndTagIndex + 1, null, true);
        if ($nextIndex && $tokens[$nextIndex]['code'] === T_STATIC) {
            return true;
        }

        if ($nextIndex && $this->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE], $tokens[$nextIndex])) {
            return true;
        }

        return false;
    }
}
