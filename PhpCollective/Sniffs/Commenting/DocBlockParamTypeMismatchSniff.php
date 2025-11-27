<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;
use PhpCollective\Traits\CommentingTrait;
use PhpCollective\Traits\SignatureTrait;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

/**
 * Checks that @param docblock types don't include types that are not allowed by the method signature type hint.
 *
 * For example, if the method signature has `Node $parent`, then `@param Node|string $parent` is wrong
 * because `string` is not compatible with `Node`. This sniff will auto-fix by removing the incompatible type.
 */
class DocBlockParamTypeMismatchSniff extends AbstractSniff
{
    use CommentingTrait;
    use SignatureTrait;

    /**
     * @var array<string>
     */
    protected static array $basicTypes = [
        'string',
        'int',
        'float',
        'bool',
        'array',
        'object',
        'callable',
        'iterable',
        'mixed',
        'null',
        'true',
        'false',
        'resource',
        'void',
        'never',
        'self',
        'static',
        'parent',
    ];

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
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPtr);
        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        if ($this->hasInheritDoc($phpcsFile, $docBlockStartIndex, $docBlockEndIndex)) {
            return;
        }

        $methodSignature = $this->getMethodSignature($phpcsFile, $stackPtr);
        if (!$methodSignature) {
            return;
        }

        // Build a map of method param variable names to their type hints
        $methodParamTypes = [];
        foreach ($methodSignature as $param) {
            $varName = $tokens[$param['variableIndex']]['content'];
            $methodParamTypes[$varName] = [
                'typehint' => $param['typehint'],
                'typehintFull' => $param['typehintFull'],
                'nullable' => $param['nullable'],
            ];
        }

        // Find all @param tags in the docblock
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if ($tokens[$i]['content'] !== '@param') {
                continue;
            }

            $contentIndex = $i + 2;
            if (!isset($tokens[$contentIndex]) || $tokens[$contentIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$contentIndex]['content'];
            $this->checkParamType($phpcsFile, $contentIndex, $content, $methodParamTypes);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $contentIndex
     * @param string $content
     * @param array<string, array<string, mixed>> $methodParamTypes
     *
     * @return void
     */
    protected function checkParamType(File $phpcsFile, int $contentIndex, string $content, array $methodParamTypes): void
    {
        $valueNode = static::getValueNode('@param', $content);

        if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
            return;
        }

        assert($valueNode instanceof ParamTagValueNode);
        $paramName = $valueNode->parameterName;

        if (!isset($methodParamTypes[$paramName])) {
            return;
        }

        $methodType = $methodParamTypes[$paramName];
        $typehint = $methodType['typehint'];

        // Skip if method has no type hint
        if ($typehint === '') {
            return;
        }

        // Skip if method type hint is 'mixed' - anything is allowed
        if ($typehint === 'mixed') {
            return;
        }

        // Get the docblock types
        $docBlockTypes = $this->extractTypesFromNode($valueNode);

        // Check for incompatible types
        $invalidTypes = $this->findInvalidTypes($docBlockTypes, $typehint, $methodType['nullable']);

        if ($invalidTypes === []) {
            return;
        }

        // Filter out the invalid types
        $validTypes = array_diff($docBlockTypes, $invalidTypes);

        if ($validTypes === []) {
            // All types are invalid - just report, don't auto-fix as this needs manual attention
            $phpcsFile->addError(
                'Doc block param type `%s` is incompatible with type hint `%s` for %s',
                $contentIndex,
                'Incompatible',
                [implode('|', $invalidTypes), $typehint, $paramName],
            );

            return;
        }

        $fix = $phpcsFile->addFixableError(
            'Doc block param type contains `%s` which is incompatible with type hint `%s` for %s',
            $contentIndex,
            'IncompatibleType',
            [implode('|', $invalidTypes), $typehint, $paramName],
        );

        if (!$fix) {
            return;
        }

        // Build the new content
        $newTypeString = implode('|', $validTypes);
        $newContent = $this->stringifyValueNode(array_values($validTypes), $valueNode);

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($contentIndex, $newContent);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Extract type strings from a ParamTagValueNode.
     *
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode $valueNode
     *
     * @return array<string>
     */
    protected function extractTypesFromNode(ParamTagValueNode $valueNode): array
    {
        $type = $valueNode->type;

        if ($type instanceof UnionTypeNode) {
            $types = [];
            foreach ($type->types as $subType) {
                $types[] = (string)$subType;
            }

            return $types;
        }

        if ($type instanceof NullableTypeNode) {
            return [(string)$type->type, 'null'];
        }

        return [(string)$type];
    }

    /**
     * Find types in docblock that are not compatible with the method type hint.
     *
     * @param array<string> $docBlockTypes
     * @param string $typehint
     * @param bool $nullable
     *
     * @return array<string>
     */
    protected function findInvalidTypes(array $docBlockTypes, string $typehint, bool $nullable): array
    {
        $invalidTypes = [];

        // Parse the type hint (could be union type like string|int)
        $allowedTypes = $this->parseTypehint($typehint, $nullable);

        foreach ($docBlockTypes as $docType) {
            if (!$this->isTypeCompatible($docType, $allowedTypes, $typehint)) {
                $invalidTypes[] = $docType;
            }
        }

        return $invalidTypes;
    }

    /**
     * Parse a type hint into an array of allowed types.
     *
     * @param string $typehint
     * @param bool $nullable
     *
     * @return array<string>
     */
    protected function parseTypehint(string $typehint, bool $nullable): array
    {
        // Handle union types (PHP 8.0+)
        $types = explode('|', $typehint);

        if ($nullable && !in_array('null', $types, true)) {
            $types[] = 'null';
        }

        return $types;
    }

    /**
     * Check if a docblock type is compatible with the allowed types from the type hint.
     *
     * @param string $docType
     * @param array<string> $allowedTypes
     * @param string $originalTypehint
     *
     * @return bool
     */
    protected function isTypeCompatible(string $docType, array $allowedTypes, string $originalTypehint): bool
    {
        $normalizedDocType = ltrim($docType, '\\');
        $normalizedDocTypeLower = strtolower($normalizedDocType);

        foreach ($allowedTypes as $allowedType) {
            $normalizedAllowed = ltrim($allowedType, '\\');
            $normalizedAllowedLower = strtolower($normalizedAllowed);

            // Exact match (case-insensitive for basic types)
            if ($normalizedDocTypeLower === $normalizedAllowedLower) {
                return true;
            }

            // Check for class name match (case-sensitive for class names)
            if ($normalizedDocType === $normalizedAllowed) {
                return true;
            }

            // Short class name matches full class name
            if ($this->classNamesMatch($normalizedDocType, $normalizedAllowed)) {
                return true;
            }
        }

        // Special case: array type hint allows array<...>, ...<...>, ...[]
        if (in_array('array', $allowedTypes, true) || in_array('iterable', $allowedTypes, true)) {
            if (str_contains($docType, '[]') || str_contains($docType, '<') || str_starts_with($docType, 'array')) {
                return true;
            }
        }

        // Special case: callable
        if (in_array('callable', $allowedTypes, true)) {
            if (str_starts_with($docType, 'callable') || $docType === 'Closure' || $docType === '\\Closure') {
                return true;
            }
        }

        // Special case: object type hint allows any class
        if (in_array('object', $allowedTypes, true)) {
            if ($this->isClassName($docType)) {
                return true;
            }
        }

        // Special case: bool allows true/false
        if (in_array('bool', $allowedTypes, true)) {
            if ($normalizedDocTypeLower === 'true' || $normalizedDocTypeLower === 'false') {
                return true;
            }
        }

        // Special case: int allows positive-int, negative-int, non-negative-int, non-positive-int
        if (in_array('int', $allowedTypes, true)) {
            if (str_contains($normalizedDocTypeLower, 'int')) {
                return true;
            }
        }

        // Special case: string allows class-string, non-empty-string, etc.
        if (in_array('string', $allowedTypes, true)) {
            if (str_contains($normalizedDocTypeLower, 'string')) {
                return true;
            }
        }

        // Skip class-to-class comparison - too complex (inheritance, interfaces)
        // Only flag when a basic type is used with a class type hint
        if ($this->isClassName($docType) && $this->hasClassTypeHint($allowedTypes)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the allowed types contain at least one class type hint.
     *
     * @param array<string> $allowedTypes
     *
     * @return bool
     */
    protected function hasClassTypeHint(array $allowedTypes): bool
    {
        foreach ($allowedTypes as $type) {
            if ($this->isClassName($type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if two class names match (one might be short, one might be fully qualified).
     *
     * @param string $type1
     * @param string $type2
     *
     * @return bool
     */
    protected function classNamesMatch(string $type1, string $type2): bool
    {
        // If either is a basic type, they must match exactly
        if (in_array(strtolower($type1), static::$basicTypes, true) || in_array(strtolower($type2), static::$basicTypes, true)) {
            return false;
        }

        $shortName1 = $this->getShortClassName($type1);
        $shortName2 = $this->getShortClassName($type2);

        return $shortName1 === $shortName2;
    }

    /**
     * Get the short class name from a potentially fully qualified name.
     *
     * @param string $className
     *
     * @return string
     */
    protected function getShortClassName(string $className): string
    {
        $parts = explode('\\', $className);

        return end($parts);
    }

    /**
     * Check if a type looks like a class name (not a basic type).
     *
     * @param string $type
     *
     * @return bool
     */
    protected function isClassName(string $type): bool
    {
        $normalizedType = ltrim($type, '\\');

        // If it contains a namespace separator, it's definitely a class
        if (str_contains($normalizedType, '\\')) {
            return true;
        }

        // If it starts with uppercase and is not a basic type, it's likely a class
        if (preg_match('/^[A-Z]/', $normalizedType) && !in_array(strtolower($normalizedType), static::$basicTypes, true)) {
            return true;
        }

        return false;
    }
}
