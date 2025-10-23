<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;
use PhpCollective\Traits\CommentingTrait;

/**
 * Method doc blocks should have a consistent grouping of tag types.
 * They also should have a single newline between description and tags.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockTagGroupingSniff extends AbstractSniff
{
    use CommentingTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_FUNCTION, T_CONST];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_FUNCTION) {
            // Don't mess with closures
            $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPtr - 1, null, true);
            if (!$this->isGivenKind(Tokens::$methodPrefixes, $tokens[$prevIndex])) {
                return;
            }
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPtr);
        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $this->checkFirstAnnotationTag($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
        $this->checkLastAnnotationTag($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
        $this->checkAnnotationTagGrouping($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function checkFirstAnnotationTag(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(T_DOC_COMMENT_TAG, $docBlockStartIndex + 1, $docBlockEndIndex);
        if (!$nextIndex) {
            return;
        }

        $prevIndex = $phpcsFile->findPrevious(T_DOC_COMMENT_STRING, $nextIndex - 1, $docBlockStartIndex + 1);
        if (!$prevIndex) {
            $this->checkBeginningOfDocBlock($phpcsFile, $docBlockStartIndex, $nextIndex);

            return;
        }

        $diff = $tokens[$nextIndex]['line'] - $tokens[$prevIndex]['line'];
        if ($diff === 2) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Expected 1 extra new line before tags, got ' . ($diff - 1), $nextIndex, 'ExtraLineMissing');
        if (!$fix) {
            return;
        }

        if ($diff > 2) {
            $phpcsFile->fixer->beginChangeset();

            for ($i = $prevIndex; $i < $nextIndex; $i++) {
                if ($tokens[$i]['line'] <= $tokens[$prevIndex]['line'] + 1 || $tokens[$i]['line'] >= $tokens[$nextIndex]['line']) {
                    continue;
                }
                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->endChangeset();

            return;
        }

        $i = $nextIndex;
        while ($tokens[$i]['line'] === $tokens[$nextIndex]['line']) {
            $i--;
        }

        $phpcsFile->fixer->beginChangeset();

        $indentation = $this->getIndentationWhitespace($phpcsFile, $docBlockEndIndex);
        $phpcsFile->fixer->addContentBefore($i, $indentation . '*');
        $phpcsFile->fixer->addNewlineBefore($i);

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function checkLastAnnotationTag(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        $prevIndex = $phpcsFile->findPrevious([T_DOC_COMMENT_TAG, T_DOC_COMMENT_STRING], $docBlockEndIndex - 1, $docBlockStartIndex);
        if (!$prevIndex) {
            return;
        }

        $diff = $tokens[$docBlockEndIndex]['line'] - $tokens[$prevIndex]['line'];
        if ($diff < 2) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Expected no extra blank line after tags, got ' . ($diff - 1), $prevIndex, 'NoExtraNewlineAfterTags');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        for ($i = $prevIndex; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['line'] <= $tokens[$prevIndex]['line'] || $tokens[$i]['line'] >= $tokens[$docBlockEndIndex]['line']) {
                continue;
            }
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $nextIndex
     *
     * @return void
     */
    protected function checkBeginningOfDocBlock(File $phpcsFile, int $docBlockStartIndex, int $nextIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        $diff = $tokens[$nextIndex]['line'] - $tokens[$docBlockStartIndex]['line'];
        if ($diff < 2) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Expected no extra blank line before tags, got ' . ($diff - 1), $nextIndex, 'NoExtraNewlineBeforeTags');
        if ($fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        for ($i = $docBlockStartIndex; $i < $nextIndex; $i++) {
            if ($tokens[$i]['line'] <= $tokens[$docBlockStartIndex]['line'] || $tokens[$i]['line'] >= $tokens[$nextIndex]['line']) {
                continue;
            }
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function checkAnnotationTagGrouping(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex): void
    {
        $tags = $this->readTags($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);

        $currentTag = null;
        foreach ($tags as $i => $tag) {
            if ($currentTag === null) {
                $currentTag = $tag['tag'];

                continue;
            }

            if ($currentTag === $tag['tag'] || str_starts_with($tag['tag'], $currentTag)) {
                $this->assertNoSpacing($phpcsFile, $tags[$i - 1], $tag);

                continue;
            }

            $this->assertSpacing($phpcsFile, $tags[$i - 1], $tag);
            $currentTag = $tag['tag'];
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return array<int, array<string, mixed>>
     */
    protected function readTags(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex): array
    {
        $tokens = $phpcsFile->getTokens();

        $tags = [];

        for ($i = $docBlockStartIndex; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_TAG) {
                continue;
            }

            $start = $this->getFirstTokenOfLine($tokens, $i);
            $end = $this->getEndIndex($tokens, $i);
            $tagEnd = $this->getTagEndIndex($tokens, $start, $end);

            $tag = [
                'index' => $i,
                'tag' => $tokens[$i]['content'],
                'tagEnd' => $tagEnd,
                'start' => $start,
                'end' => $end,
                'content' => $this->getContent($tokens, $i, $tagEnd),
            ];
            $tags[] = $tag;
            $i = $end;
        }

        return $tags;
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @param int $index
     *
     * @return int
     */
    protected function getEndIndex(array $tokens, int $index): int
    {
        $startIndex = $index;
        while (!empty($tokens[$index + 1]) && $tokens[$index + 1]['code'] !== T_DOC_COMMENT_CLOSE_TAG && $tokens[$index + 1]['code'] !== T_DOC_COMMENT_TAG) {
            $index++;
        }

        // Jump to the previous line
        $currentLine = $tokens[$index]['line'];
        while ($tokens[$index]['line'] === $currentLine) {
            $index--;
        }
        // Fix for single line doc blocks
        $index = max($index, $startIndex);

        return $this->getLastTokenOfLine($tokens, $index);
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @param int $start
     * @param int $end
     *
     * @return int
     */
    protected function getTagEndIndex(array $tokens, int $start, int $end): int
    {
        for ($i = $end; $i > $start; $i--) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_STRING) {
                continue;
            }

            return $i;
        }

        return $start;
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    protected function getContent(array $tokens, int $start, int $end): string
    {
        $content = '';
        for ($i = $start; $i <= $end; $i++) {
            $content .= $tokens[$i]['content'];
        }

        return $content;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param array<string, mixed> $first
     * @param array<string, mixed> $second
     *
     * @return void
     */
    protected function assertNoSpacing(File $phpcsFile, array $first, array $second): void
    {
        $tokens = $phpcsFile->getTokens();

        $lastIndexOfFirst = $first['tagEnd'];
        $lastLineOfFirst = $tokens[$lastIndexOfFirst]['line'];

        $tagIndexOfSecond = $second['index'];
        $firstLineOfSecond = $tokens[$tagIndexOfSecond]['line'];

        if ($lastLineOfFirst === $firstLineOfSecond - 1) {
            return;
        }

        $fix = $phpcsFile->addFixableError('No newline expected between tags of the same type `' . $first['tag'] . '`', $tagIndexOfSecond, 'NoNewlineBetweenSameType');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();

        for ($i = $first['tagEnd'] + 1; $i < $second['start']; $i++) {
            if ($tokens[$i]['line'] <= $lastLineOfFirst || $tokens[$i]['line'] >= $firstLineOfSecond) {
                continue;
            }

            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param array<string, mixed> $first
     * @param array<string, mixed> $second
     *
     * @return void
     */
    protected function assertSpacing(File $phpcsFile, array $first, array $second): void
    {
        $tokens = $phpcsFile->getTokens();

        $lastIndexOfFirst = $first['tagEnd'];
        $lastLineOfFirst = $tokens[$lastIndexOfFirst]['line'];

        $tagIndexOfSecond = $second['index'];
        $firstLineOfSecond = $tokens[$tagIndexOfSecond]['line'];

        if ($lastLineOfFirst === $firstLineOfSecond - 2) {
            return;
        }

        $error = 'A single newline expected between tags of different types `' . $first['tag'] . '`/`' . $second['tag'] . '`';
        $fix = $phpcsFile->addFixableError($error, $tagIndexOfSecond, 'NewlineBetweenDifferentTypes');
        if (!$fix) {
            return;
        }

        if ($lastLineOfFirst > $firstLineOfSecond - 2) {
            $phpcsFile->fixer->beginChangeset();

            $indentation = $this->getIndentationWhitespace($phpcsFile, $tagIndexOfSecond);
            $phpcsFile->fixer->addNewlineBefore($second['start']);
            $phpcsFile->fixer->addContentBefore($second['start'], $indentation . '*');

            $phpcsFile->fixer->endChangeset();

            return;
        }

        $phpcsFile->fixer->beginChangeset();

        for ($i = $first['tagEnd'] + 1; $i < $second['start']; $i++) {
            if ($tokens[$i]['line'] <= $firstLineOfSecond - 2) {
                continue;
            }

            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }
}
