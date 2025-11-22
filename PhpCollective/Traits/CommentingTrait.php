<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Traits;

use PHP_CodeSniffer\Files\File;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

/**
 * Common functionality around commenting.
 */
trait CommentingTrait
{
    /**
     * @param string $tagName tag name
     * @param string $tagComment tag comment
     *
     * @return \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode
     */
    protected static function getValueNode(string $tagName, string $tagComment): PhpDocTagValueNode
    {
        static $phpDocParser;
        if (!$phpDocParser) {
            $config = new ParserConfig(usedAttributes: []);
            $constExprParser = new ConstExprParser($config);
            $phpDocParser = new PhpDocParser($config, new TypeParser($config, $constExprParser), $constExprParser);
        }

        static $phpDocLexer;
        if (!$phpDocLexer) {
            $config = new ParserConfig(usedAttributes: []);
            $phpDocLexer = new Lexer($config);
        }

        return $phpDocParser->parseTagValue(new TokenIterator($phpDocLexer->tokenize($tagComment)), $tagName);
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode $valueNode
     *
     * @return array<string>
     */
    protected function valueNodeParts(PhpDocTagValueNode $valueNode): array
    {
        if ($valueNode instanceof MethodTagValueNode) {
            $types = [$valueNode->returnType];
        } elseif ($valueNode instanceof GenericTagValueNode) {
            $types = [$valueNode];
        } elseif ($valueNode->type instanceof UnionTypeNode) {
            $types = $valueNode->type->types;
        } else {
            $types = [$valueNode->type];
        }

        $result = [];
        foreach ($types as $type) {
            $result[] = (string)$type;
        }

        return $result;
    }

    /**
     * @param array<string> $parts
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode $valueNode
     *
     * @return string
     */
    protected function stringifyValueNode(array $parts, PhpDocTagValueNode $valueNode): string
    {
        if ($valueNode instanceof ParamTagValueNode) {
            return trim(sprintf(
                '%s %s%s %s',
                implode('|', $parts),
                $valueNode->isVariadic ? '...' : '',
                $valueNode->parameterName,
                $valueNode->description,
            ));
        }
        if ($valueNode instanceof ReturnTagValueNode) {
            return trim(sprintf(
                '%s%s',
                implode('|', $parts),
                $valueNode->description,
            ));
        }
        if ($valueNode instanceof MethodTagValueNode) {
            return trim(sprintf(
                '%s %s() %s',
                implode('|', $parts),
                $valueNode->methodName,
                $valueNode->description,
            ));
        }
        if ($valueNode instanceof VarTagValueNode) {
            return trim(sprintf(
                '%s %s%s',
                implode('|', $parts),
                $valueNode->variableName,
                $valueNode->description,
            ));
        }
        if ($valueNode instanceof PropertyTagValueNode) {
            return trim(sprintf(
                '%s %s%s',
                implode('|', $parts),
                $valueNode->propertyName,
                $valueNode->description,
            ));
        }
        if ($valueNode instanceof ThrowsTagValueNode) {
            return trim(sprintf(
                '%s %s',
                implode('|', $parts),
                $valueNode->description,
            ));
        }

        return trim(implode('|', $parts));
    }

    /**
     * Looks for either `@inheritDoc` or `{@inheritDoc}`.
     * Also allows `@inheritdoc` or `{@inheritdoc}` aliases.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @param string $check
     *
     * @return bool
     */
    protected function hasInheritDoc(File $phpcsFile, $docBlockStartIndex, $docBlockEndIndex, $check = '@inheritDoc')
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; ++$i) {
            if (empty($tokens[$i]['content'])) {
                continue;
            }
            $content = $tokens[$i]['content'];
            $pos = stripos($content, $check);
            if ($pos === false) {
                continue;
            }

            if ($pos && str_starts_with($check, '@') && substr($content, $pos - 1, $pos) === '{') {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Allow \Foo\Bar[] or array<\Foo\Bar> to pass as array.
     *
     * @param array<string> $docBlockTypes
     * @param string $iterableType
     *
     * @return bool
     */
    protected function containsTypeArray(array $docBlockTypes, string $iterableType = 'array'): bool
    {
        foreach ($docBlockTypes as $docBlockType) {
            if (str_contains($docBlockType, '[]') || str_starts_with($docBlockType, $iterableType . '<')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks for ...<...>.
     *
     * @param array<string> $docBlockTypes
     *
     * @return bool
     */
    protected function containsIterableSyntax(array $docBlockTypes): bool
    {
        foreach ($docBlockTypes as $docBlockType) {
            if (str_contains($docBlockType, '<')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Collects a potentially multi-line type annotation from a doc block.
     *
     * This handles complex types like:
     * - array<string, array{msgid: string, msgid_plural: string|null}>
     * - Multi-line array shapes with nested structures
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $tagIndex The index of the @param/@return/@var tag
     * @param int $docBlockEndIndex The end of the doc block
     *
     * @return array{type: string, variable: string, description: string, endIndex: int}|null
     */
    protected function collectMultiLineType(File $phpcsFile, int $tagIndex, int $docBlockEndIndex): ?array
    {
        $tokens = $phpcsFile->getTokens();

        // Find the first content token after the tag
        $contentIndex = $tagIndex + 2;
        if (!isset($tokens[$contentIndex]) || $tokens[$contentIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
            return null;
        }

        $collectedContent = '';
        $bracketDepth = 0;
        $endIndex = $contentIndex;

        // Collect content across multiple lines if brackets are open
        for ($i = $contentIndex; $i < $docBlockEndIndex; $i++) {
            $token = $tokens[$i];

            if ($token['type'] === 'T_DOC_COMMENT_STRING') {
                $content = $token['content'];
                $collectedContent .= $content;
                $endIndex = $i;

                // Count bracket depth
                $bracketDepth += substr_count($content, '<') + substr_count($content, '{') + substr_count($content, '(');
                $bracketDepth -= substr_count($content, '>') + substr_count($content, '}') + substr_count($content, ')');

                // If brackets are balanced and we have content, check if we have the full type
                if ($bracketDepth <= 0) {
                    break;
                }
            } elseif ($token['type'] === 'T_DOC_COMMENT_WHITESPACE') {
                // Add a space for line continuations (replacing newlines and asterisks)
                if ($bracketDepth > 0 && str_contains($token['content'], "\n")) {
                    $collectedContent .= ' ';
                }
            } elseif ($token['type'] === 'T_DOC_COMMENT_STAR') {
                // Skip the leading asterisk on continuation lines
                continue;
            } elseif ($token['type'] === 'T_DOC_COMMENT_TAG') {
                // Hit another tag, stop collecting
                break;
            }
        }

        // Normalize whitespace (collapse multiple spaces)
        $collectedContent = (string)preg_replace('/\s+/', ' ', trim($collectedContent));

        // Parse the collected content to extract type, variable, and description
        return $this->parseCollectedTypeContent($collectedContent, $endIndex);
    }

    /**
     * Parse the collected content to extract type, variable name, and description.
     *
     * @param string $content The collected content
     * @param int $endIndex The ending token index
     *
     * @return array{type: string, variable: string, description: string, endIndex: int}|null
     */
    protected function parseCollectedTypeContent(string $content, int $endIndex): ?array
    {
        // Find the variable name (starts with $)
        if (!preg_match('/^(.+?)\s+(\$\S+)(?:\s+(.*))?$/', $content, $matches)) {
            // Maybe just a type without variable (for @return)
            if (preg_match('/^(\S+)(?:\s+(.*))?$/', $content, $matches)) {
                return [
                    'type' => $matches[1],
                    'variable' => '',
                    'description' => $matches[2] ?? '',
                    'endIndex' => $endIndex,
                ];
            }

            return null;
        }

        return [
            'type' => trim($matches[1]),
            'variable' => $matches[2],
            'description' => $matches[3] ?? '',
            'endIndex' => $endIndex,
        ];
    }

    /**
     * @param array<\PHPStan\PhpDocParser\Ast\Type\TypeNode|string> $typeNodes type nodes
     *
     * @return string
     */
    protected function renderUnionTypes(array $typeNodes): string
    {
        return (string)preg_replace(
            ['/ ([\|&]) /', '/<\(/', '/\)>/', '/\), /', '/, \(/'],
            ['${1}', '<', '>', ', ', ', '],
            implode('|', $typeNodes),
        );
    }
}
