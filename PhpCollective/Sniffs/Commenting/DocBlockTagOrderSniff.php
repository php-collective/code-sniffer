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
     * Within-tag ordering: ordered list of name prefixes per tag type.
     *
     * Defaults empty - no inner ordering is applied unless configured.
     *
     * Configurable via XML:
     *
     * ``` xml
     * <rule ref="PhpCollective.Commenting.DocBlockTagOrder">
     *     <properties>
     *         <property name="innerOrder" type="array">
     *             <element key="@method"
     *                      value="newEmptyEntity,newEntity,newEntities,get,findOrCreate,find*,patchEntity,patchEntities,save,saveOrFail,saveMany*,delete,deleteOrFail,deleteMany*"/>
     *             <element key="@property" value=""/>
     *             <element key="@property-read" value=""/>
     *             <element key="@property-write" value=""/>
     *         </property>
     *     </properties>
     * </rule>
     * ```
     *
     * Each value is a comma-separated list of prefix patterns. A trailing `*` matches any
     * suffix; a pattern without `*` matches only the exact subject. Tags matching no pattern
     * float to the bottom of their bucket. Within each score class (matched-by-same-pattern,
     * or all-unmatched), tags are sorted alphabetically by extracted subject.
     *
     * An empty value means "no prefix priority - alphabetize everything in this bucket,"
     * which is the typical recipe for `@property` association lists.
     *
     * Only applied on class/interface/trait docblocks, not on function/method
     * docblocks - per-method tags like `@param`/`@return` are positional and have
     * no meaningful within-bucket subject.
     *
     * Subject extraction per tag:
     *  - `@method` -> method name (after optional `static` + return type)
     *  - `@property[-read|-write]` -> variable name minus leading `$`
     *  - `@mixin`/`@extends`/`@implements` -> last segment of the FQCN
     *  - `@template` -> template name token
     *
     * Note: PHPCS may convert empty XML `value=""` into `null` when populating array
     * properties; both forms are normalized to "no prefixes, alphabetize the bucket."
     *
     * @var array<string, string|array<int, string>|null>
     */
    public array $innerOrder = [];

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
        if (!$isFunction) {
            $tags = $this->checkInnerOrder($tags);
        }

        $this->fixOrder($phpcsFile, $docBlockStartIndex, $docBlockEndIndex, $tags, $order);
    }

    /**
     * Marks tags that violate within-bucket inner ordering with an `innerError` flag.
     *
     * Walks tags grouped by tag-type bucket. Within each bucket whose tag is configured
     * in `$innerOrder`, computes a sort key (score, subject) and flags every tag whose
     * key is less than its predecessor's.
     *
     * @param array<int, array<string, mixed>> $tags
     *
     * @return array<int, array<string, mixed>>
     */
    protected function checkInnerOrder(array $tags): array
    {
        if (!$this->innerOrder) {
            return $tags;
        }

        $byBucket = [];
        foreach ($tags as $i => $tag) {
            $byBucket[$tag['tag']][] = $i;
        }

        foreach ($byBucket as $tagName => $indexes) {
            if (!array_key_exists($tagName, $this->innerOrder)) {
                continue;
            }
            $prefixes = $this->normalizeInnerPrefixes($this->innerOrder[$tagName]);

            $previousKey = null;
            foreach ($indexes as $idx) {
                $value = ltrim(substr((string)$tags[$idx]['content'], strlen($tagName)));
                $subject = $this->extractSubject($tagName, $value);
                $score = $subject === null ? PHP_INT_MAX : $this->scoreSubject($subject, $prefixes);
                // Tuple second slot pushes null-subject (malformed/unparseable)
                // entries to the bottom of their score class; without it they'd
                // sort before real subjects because (string)null === ''.
                $key = [$score, $subject === null ? 1 : 0, (string)$subject];
                if ($previousKey !== null && $key < $previousKey) {
                    $tags[$idx]['innerError'] = sprintf(
                        'Inner order of %s tag wrong (subject "%s").',
                        $tagName,
                        (string)$subject,
                    );
                }
                $previousKey = $key;
            }
        }

        return $tags;
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

        // Walk back to the line that actually contains this tag's content,
        // skipping over any blank " * " separator lines so they are not folded
        // into this tag's range and re-emitted in the rebuilt docblock.
        while ($index > $startIndex && $tokens[$index]['code'] !== T_DOC_COMMENT_STRING && $tokens[$index]['code'] !== T_DOC_COMMENT_TAG) {
            $index--;
        }

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
        $bucketErrors = [];
        $innerErrors = [];
        foreach ($tags as $i => $tag) {
            if (isset($tag['error'])) {
                $bucketErrors[$i] = $tag['error'];
            }
            if (isset($tag['innerError'])) {
                $innerErrors[$i] = $tag['innerError'];
            }
        }

        if (!$bucketErrors && !$innerErrors) {
            return;
        }

        $fixBucket = false;
        if ($bucketErrors) {
            $fixBucket = $phpcsFile->addFixableError(
                'Invalid order of tags: ' . implode(', ', $bucketErrors),
                $docBlockEndIndex,
                'OrderInvalid',
            );
        }

        $fixInner = false;
        if ($innerErrors) {
            $fixInner = $phpcsFile->addFixableError(
                'Invalid inner order of tags: ' . implode(', ', $innerErrors),
                $docBlockEndIndex,
                'InnerOrderInvalid',
            );
        }

        if (!$fixBucket && !$fixInner) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $phpcsFile->fixer->beginChangeset();

        $order = $this->getTagOrderMap($orderList);

        $newOrder = [];
        foreach ($tags as $tag) {
            $tagOrder = $order[$tag['tag']] ?? -1;
            $newOrder[$tagOrder][] = [
                'tag' => (string)$tag['tag'],
                'content' => $this->getContent($tokens, $tag['start'], $tag['end']),
            ];
        }

        ksort($newOrder);
        if (isset($newOrder[-1])) {
            usort(
                $newOrder[-1],
                static fn (array $a, array $b): int => strcmp($a['content'], $b['content']),
            );
        }

        if ($fixInner) {
            foreach ($newOrder as $tagOrder => $entries) {
                // The -1 bucket holds tags not present in the outer order list,
                // so its entries can be a mix of tag types. Applying a single
                // tag's inner-order sort across that mix would extract subjects
                // with the wrong tag name and reorder unrelated tags. The
                // alphabetical-by-content sort applied above already covers it.
                if ($tagOrder === -1) {
                    continue;
                }
                $tagName = $entries[0]['tag'];
                if (!array_key_exists($tagName, $this->innerOrder)) {
                    continue;
                }
                $prefixes = $this->normalizeInnerPrefixes($this->innerOrder[$tagName]);

                usort($entries, function (array $a, array $b) use ($tagName, $prefixes): int {
                    $sa = $this->extractSubjectFromLine($a['content'], $tagName);
                    $sb = $this->extractSubjectFromLine($b['content'], $tagName);
                    $scoreA = $sa === null ? PHP_INT_MAX : $this->scoreSubject($sa, $prefixes);
                    $scoreB = $sb === null ? PHP_INT_MAX : $this->scoreSubject($sb, $prefixes);
                    if ($scoreA !== $scoreB) {
                        return $scoreA <=> $scoreB;
                    }
                    // Push null subjects (malformed/unparseable) to the bottom
                    // of the score class instead of letting (string)null === ''
                    // pull them above real subjects. Fall back to original line
                    // content when both are null so the order remains stable.
                    if ($sa === null && $sb === null) {
                        return strcmp($a['content'], $b['content']);
                    }
                    if ($sa === null) {
                        return 1;
                    }
                    if ($sb === null) {
                        return -1;
                    }

                    return strcmp($sa, $sb);
                });

                $newOrder[$tagOrder] = $entries;
            }
        }

        $content = '';
        foreach ($newOrder as $entries) {
            foreach ($entries as $entry) {
                $content .= $entry['content'];
            }
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
     * Extracts the subject from a full line (e.g. ` * @method \Foo save(...)`) by
     * locating the tag name and parsing what comes after.
     *
     * @param string $lineContent
     * @param string $tagName
     *
     * @return string|null
     */
    protected function extractSubjectFromLine(string $lineContent, string $tagName): ?string
    {
        $position = strpos($lineContent, $tagName);
        if ($position === false) {
            return null;
        }

        $value = ltrim(substr($lineContent, $position + strlen($tagName)));

        return $this->extractSubject($tagName, $value);
    }

    /**
     * Extracts the orderable subject from a tag's content for inner-ordering purposes.
     *
     * Returns null when the tag type is not handled or the content is malformed.
     *
     * @param string $tagName Tag name including leading "@" (e.g. "@method", "@property").
     * @param string $content Raw tag content as it appears in the docblock (the part after
     *                        the tag name, leading whitespace stripped).
     *
     * @return string|null
     */
    protected function extractSubject(string $tagName, string $content): ?string
    {
        $content = ltrim($content);

        switch ($tagName) {
            case '@property':
            case '@property-read':
            case '@property-write':
                return $this->extractMethodOrPropertySubject($content, true);
            case '@method':
                return $this->extractMethodOrPropertySubject($content, false);
            case '@mixin':
            case '@extends':
            case '@implements':
                if (preg_match('/^\\\\?([A-Za-z0-9_\\\\]+)/', $content, $m) === 1) {
                    $segments = explode('\\', $m[1]);
                    $last = end($segments);

                    return $last === '' ? null : $last;
                }

                return null;
            case '@template':
                if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)/', $content, $m) === 1) {
                    return $m[1];
                }

                return null;
        }

        return null;
    }

    /**
     * Walks a `@method` or `@property*` content string: skips an optional `static` keyword,
     * skips one type expression (with depth tracking for `<>`, `()`, `[]`), then returns
     * the next bareword. For `@property*`, also strips a leading `$` from the subject.
     *
     * @param string $content
     * @param bool $isProperty
     *
     * @return string|null
     */
    protected function extractMethodOrPropertySubject(string $content, bool $isProperty): ?string
    {
        $length = strlen($content);
        $i = 0;

        if (substr($content, $i, 7) === 'static ') {
            $i += 7;
            $i += strspn($content, " \t", $i);
        }

        $startsWithName = preg_match('/\A([A-Za-z_][A-Za-z0-9_]*)/', substr($content, $i), $m) === 1;
        if ($startsWithName) {
            $name = $m[1];
            $afterName = $i + strlen($name);
            $afterName += strspn($content, " \t", $afterName);
            $nextChar = $afterName < $length ? $content[$afterName] : '';
            $isMethodWithoutType = !$isProperty && $nextChar === '(';
            $isPropertyWithoutType = $isProperty && $nextChar === '$';
            if ($isMethodWithoutType) {
                return $name;
            }
            if ($isPropertyWithoutType) {
                $j = $afterName + 1;
                if (preg_match('/\A([A-Za-z_][A-Za-z0-9_]*)/', substr($content, $j), $m2) === 1) {
                    return $m2[1];
                }

                return null;
            }
        }

        $depthAngle = 0;
        $depthParen = 0;
        $depthBracket = 0;
        while ($i < $length) {
            $c = $content[$i];
            if ($depthAngle === 0 && $depthParen === 0 && $depthBracket === 0 && ($c === ' ' || $c === "\t")) {
                break;
            }
            if ($c === '<') {
                $depthAngle++;
            } elseif ($c === '>') {
                if ($depthAngle > 0) {
                    $depthAngle--;
                }
            } elseif ($c === '(') {
                $depthParen++;
            } elseif ($c === ')') {
                if ($depthParen > 0) {
                    $depthParen--;
                }
            } elseif ($c === '[') {
                $depthBracket++;
            } elseif ($c === ']') {
                if ($depthBracket > 0) {
                    $depthBracket--;
                }
            }
            $i++;
        }

        $i += strspn($content, " \t", $i);

        if ($isProperty) {
            if ($i < $length && $content[$i] === '$') {
                $i++;
            }
            if (preg_match('/\A([A-Za-z_][A-Za-z0-9_]*)/', substr($content, $i), $m) === 1) {
                return $m[1];
            }

            return null;
        }

        if (preg_match('/\A([A-Za-z_][A-Za-z0-9_]*)/', substr($content, $i), $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /**
     * Scores a subject against an ordered prefix list. Lower score wins.
     * Unmatched subjects return PHP_INT_MAX so they float to the bottom.
     *
     * @param string $subject
     * @param array<int, string> $prefixes
     *
     * @return int
     */
    protected function scoreSubject(string $subject, array $prefixes): int
    {
        foreach ($prefixes as $i => $pattern) {
            if ($pattern === '') {
                continue;
            }
            if (str_ends_with($pattern, '*')) {
                $needle = substr($pattern, 0, -1);
                if ($needle === '' || str_starts_with($subject, $needle)) {
                    return $i;
                }
            } elseif ($subject === $pattern) {
                return $i;
            }
        }

        return PHP_INT_MAX;
    }

    /**
     * Normalizes the configured `$innerOrder` entry for a tag into an ordered prefix list.
     * Empty inputs (null, "", [""]) become [] so every subject is unmatched and the bucket
     * sorts purely alphabetically.
     *
     * @param array<int, string>|string|null $value
     *
     * @return array<int, string>
     */
    protected function normalizeInnerPrefixes(array|string|null $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        } else {
            $value = array_map(static fn ($v): string => trim((string)$v), $value);
        }

        return array_values(array_filter($value, static fn (string $v): bool => $v !== ''));
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
