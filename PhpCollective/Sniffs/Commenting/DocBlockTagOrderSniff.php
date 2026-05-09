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
 * Function/method and class/interface/trait doc blocks should have a consistent order of tag types.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockTagOrderSniff extends AbstractSniff
{
    use CommentingTrait;

    /**
     * Tag order for function/method docblocks. All other tags will go above those.
     *
     * Configurable via XML:
     *
     * ``` xml
     * <rule ref="PhpCollective.Commenting.DocBlockTagOrder">
     *     <properties>
     *         <property name="order" type="array" value="deprecated,see,param,throws,return"/>
     *     </properties>
     * </rule>
     * ```
     *
     * Note: leading "at" sign on tag names is optional; it will be normalized.
     *
     * @var array<int, string>
     */
    public array $order = [
        '@deprecated',
        '@see',
        '@param',
        '@throws',
        '@return',
    ];

    /**
     * Tag order for class/interface/trait docblocks. All other tags will go above those.
     *
     * Configurable via XML the same way as `$order`.
     *
     * @var array<int, string>
     */
    public array $classOrder = [
        '@template',
        '@extends',
        '@implements',
        '@property',
        '@property-read',
        '@property-write',
        '@method',
        '@mixin',
    ];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $isFunction = $tokens[$stackPtr]['code'] === T_FUNCTION;

        if ($isFunction) {
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

        $order = $isFunction ? $this->order : $this->classOrder;

        $tags = $this->readTags($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
        $tags = $this->checkAnnotationTagOrder($tags, $order);

        $this->fixOrder($phpcsFile, $docBlockStartIndex, $docBlockEndIndex, $tags, $order);
    }

    /**
     * @param array<int, array<string, mixed>> $tags
     * @param array<int, string> $orderList
     *
     * @return array<int, array<string, mixed>>
     */
    protected function checkAnnotationTagOrder(array $tags, array $orderList): array
    {
        $order = $this->getTagOrderMap($orderList);

        $currentOrder = null;
        foreach ($tags as $i => $tag) {
            if (!isset($order[$tag['tag']])) {
                if ($currentOrder !== null) {
                    $tags[$i]['error'] = 'Position of ' . $tag['tag'] . ' tag too low.';

                    return $tags;
                }

                continue;
            }

            $tagOrder = $order[$tag['tag']];
            if ($currentOrder === null || $tagOrder >= $currentOrder) {
                $currentOrder = $tagOrder;

                continue;
            }

            $tags[$i]['error'] = 'Position of ' . $tag['tag'] . ' tag too low.';
        }

        return $tags;
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
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @param array<int, array<string, mixed>> $tags
     * @param array<int, string> $orderList
     *
     * @return void
     */
    protected function fixOrder(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex, array $tags, array $orderList): void
    {
        $errors = [];
        foreach ($tags as $i => $tag) {
            if (isset($tag['error'])) {
                $errors[$i] = $tag['error'];
            }
        }

        if (!$errors) {
            return;
        }

        $fix = $phpcsFile->addFixableError('Invalid order of tags: ' . implode(', ', $errors), $docBlockEndIndex, 'OrderInvalid');
        if (!$fix) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $phpcsFile->fixer->beginChangeset();

        $order = $this->getTagOrderMap($orderList);

        $newOrder = [];
        foreach ($tags as $tag) {
            $tagOrder = $order[$tag['tag']] ?? -1;
            $newOrder[$tagOrder][] = $this->getContent($tokens, $tag['start'], $tag['end']);
        }

        ksort($newOrder);
        if (isset($newOrder[-1])) {
            ksort($newOrder[-1]);
        }

        $content = '';
        foreach ($newOrder as $tagGroup) {
            $content .= implode('', $tagGroup);
        }

        $firstTagTokenIndex = $tags[0]['start'];
        $lastTagTokenIndex = $tags[count($tags) - 1]['end'];

        for ($i = $firstTagTokenIndex; $i < $lastTagTokenIndex; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->replaceToken($lastTagTokenIndex, $content);

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param array<int, string> $orderList
     *
     * @return array<string, int>
     */
    protected function getTagOrderMap(array $orderList): array
    {
        $normalized = [];
        foreach ($orderList as $tag) {
            $normalized[] = str_starts_with($tag, '@') ? $tag : '@' . $tag;
        }

        return array_flip($normalized);
    }
}
