<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\NodeTraverser;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use SlevomatCodingStandard\Helpers\Annotation;
use SlevomatCodingStandard\Helpers\AnnotationHelper;
use SlevomatCodingStandard\Helpers\AnnotationTypeHelper;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FixerHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\SniffSettingsHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\TypeHintHelper;

/**
 * Disallows use of `?type` in favor of `type|null`. Reduces conflict or issues with other sniffs.
 */
class DisallowArrayTypeHintSyntaxSniff implements Sniff
{
    /**
     * @var string
     */
    public const CODE_DISALLOWED_ARRAY_TYPE_HINT_SYNTAX = 'DisallowedArrayTypeHintSyntax';

    /**
     * @var array<string>
     */
    public array $traversableTypeHints = [];

    /**
     * @var array<string, int>|null
     */
    protected ?array $normalizedTraversableTypeHints = null;

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
    public function process(File $phpcsFile, $pointer): void
    {
        $annotations = AnnotationHelper::getAnnotations($phpcsFile, $pointer);

        foreach ($annotations as $annotation) {
            $arrayTypeNodes = $this->getArrayTypeNodes($annotation->getValue());

            foreach ($arrayTypeNodes as $arrayTypeNode) {
                $fix = $phpcsFile->addFixableError(
                    sprintf(
                        'Usage of array type hint syntax in "%s" is disallowed, use generic type hint syntax instead.',
                        AnnotationTypeHelper::print($arrayTypeNode),
                    ),
                    $annotation->getStartPointer(),
                    static::CODE_DISALLOWED_ARRAY_TYPE_HINT_SYNTAX,
                );

                if (!$fix) {
                    continue;
                }

                /** @var \SlevomatCodingStandard\Helpers\ParsedDocComment $parsedDocComment */
                $parsedDocComment = DocCommentHelper::parseDocComment($phpcsFile, $pointer);

                /** @var list<\PHPStan\PhpDocParser\Ast\Type\UnionTypeNode> $unionTypeNodes */
                $unionTypeNodes = AnnotationHelper::getAnnotationNodesByType($annotation->getNode(), UnionTypeNode::class);
                $unionTypeNode = $this->findUnionTypeThatContainsArrayType($arrayTypeNode, $unionTypeNodes);

                if ($unionTypeNode !== null) {
                    if ($this->isUnionTypeGenericObjectCollection($unionTypeNodes[0])) {
                        $this->fixGenericObjectCollection($phpcsFile, $annotation, $pointer, $arrayTypeNode, $unionTypeNodes);

                        continue;
                    }

                    $genericIdentifier = $this->findGenericIdentifier(
                        $phpcsFile,
                        $pointer,
                        $unionTypeNode,
                        $annotation->getValue(),
                    );

                    if ($genericIdentifier !== null) {
                        $genericTypeNode = new GenericTypeNode(
                            new IdentifierTypeNode($genericIdentifier),
                            [$this->fixArrayNode($arrayTypeNode->type)],
                        );

                        $fixedDocComment = AnnotationHelper::fixAnnotation(
                            $parsedDocComment,
                            $annotation,
                            $unionTypeNode,
                            $genericTypeNode,
                        );
                    } else {
                        $genericTypeNode = new GenericTypeNode(
                            new IdentifierTypeNode('array'),
                            [$this->fixArrayNode($arrayTypeNode->type)],
                        );

                        $fixedDocComment = AnnotationHelper::fixAnnotation(
                            $parsedDocComment,
                            $annotation,
                            $arrayTypeNode,
                            $genericTypeNode,
                        );
                    }
                } else {
                    $genericIdentifier = $this->findGenericIdentifier(
                        $phpcsFile,
                        $pointer,
                        $arrayTypeNode,
                        $annotation->getValue(),
                    ) ?? 'array';

                    $genericTypeNode = new GenericTypeNode(
                        new IdentifierTypeNode($genericIdentifier),
                        [$this->fixArrayNode($arrayTypeNode->type)],
                    );
                    $this->fixAnnotation($phpcsFile, $annotation, $genericTypeNode);

                    continue;
                }

                $phpcsFile->fixer->beginChangeset();
                FixerHelper::change(
                    $phpcsFile,
                    $parsedDocComment->getOpenPointer(),
                    $parsedDocComment->getClosePointer(),
                    $fixedDocComment,
                );
                $phpcsFile->fixer->endChangeset();
            }
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param \SlevomatCodingStandard\Helpers\Annotation $annotation
     * @param string $fixedAnnotation
     *
     * @return void
     */
    protected function fixAnnotation(File $phpcsFile, Annotation $annotation, string $fixedAnnotation): void
    {
        /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode|mixed $value */
        $value = $annotation->getNode()->value;
        $parameterName = $value->parameterName ?? '';
        $variableName = $value->variableName ?? '';
        $description = $value->description ?? '';
        $propertyName = $value->propertyName ?? '';

        /** @var string $methodName */
        $methodName = $value->methodName ?? '';
        if ($methodName) {
            $parameters = $value->parameters ?? [];
            $list = [];
            foreach ($parameters as $parameter) {
                $list[] = (string)$parameter;
            }
            $methodName .= '(' . implode(', ', $list) . ')';
        }

        $fixedAnnotation = sprintf('%s %s %s %s %s %s', $fixedAnnotation, $parameterName, $variableName, $propertyName, $description, $methodName);
        /** @var string $fixedAnnotation */
        $fixedAnnotation = preg_replace('/\s+/', ' ', trim($fixedAnnotation));

        $nextToken = $phpcsFile->fixer->getTokenContent($annotation->getEndPointer() + 1);

        if ($nextToken === '*/') {
            $fixedAnnotation .= ' ';
        }

        $phpcsFile->fixer->replaceToken($annotation->getStartPointer() + 2, $fixedAnnotation);
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Node $node
     *
     * @return list<\PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode>
     */
    public function getArrayTypeNodes(Node $node): array
    {
        static $visitor;
        static $traverser;

        if ($visitor === null) {
            $visitor = new class extends AbstractNodeVisitor {
                /**
                 * @var list<\PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode>
                 */
                private array $nodes = [];

                /**
                 * @param \PHPStan\PhpDocParser\Ast\Node $node
                 *
                 * @return \PHPStan\PhpDocParser\Ast\Node|list<\PHPStan\PhpDocParser\Ast\Node>|\PHPStan\PhpDocParser\Ast\NodeTraverser|int|null
                 */
                public function enterNode(Node $node): Node|array|\PHPStan\PhpDocParser\Ast\NodeTraverser|int|null
                {
                    if ($node instanceof ArrayTypeNode) {
                        $this->nodes[] = $node;

                        if ($node->type instanceof ArrayTypeNode) {
                            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                        }
                    }

                    return null;
                }

                /**
                 * @return void
                 */
                public function cleanNodes(): void
                {
                    $this->nodes = [];
                }

                /**
                 * @return list<\PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode>
                 */
                public function getNodes(): array
                {
                    return $this->nodes;
                }
            };
        }

        if ($traverser === null) {
            $traverser = new NodeTraverser([$visitor]);
        }

        $visitor->cleanNodes();

        $traverser->traverse([$node]);

        return $visitor->getNodes();
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $node
     *
     * @return \PHPStan\PhpDocParser\Ast\Type\TypeNode
     */
    protected function fixArrayNode(TypeNode $node): TypeNode
    {
        if (!$node instanceof ArrayTypeNode) {
            return $node;
        }

        return new GenericTypeNode(new IdentifierTypeNode('array'), [$this->fixArrayNode($node->type)]);
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode $arrayTypeNode
     * @param array<\PHPStan\PhpDocParser\Ast\Type\UnionTypeNode> $unionTypeNodes
     *
     * @return \PHPStan\PhpDocParser\Ast\Type\UnionTypeNode|null
     */
    protected function findUnionTypeThatContainsArrayType(ArrayTypeNode $arrayTypeNode, array $unionTypeNodes): ?UnionTypeNode
    {
        foreach ($unionTypeNodes as $unionTypeNode) {
            if (in_array($arrayTypeNode, $unionTypeNode->types, true)) {
                return $unionTypeNode;
            }
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docCommentOpenPointer
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode $annotationValue
     *
     * @return string|null
     */
    protected function findGenericIdentifier(
        File $phpcsFile,
        int $docCommentOpenPointer,
        TypeNode $typeNode,
        PhpDocTagValueNode $annotationValue,
    ): ?string {
        if (!$typeNode instanceof UnionTypeNode) {
            if (!$annotationValue instanceof ParamTagValueNode && !$annotationValue instanceof ReturnTagValueNode) {
                return null;
            }

            $functionTokenCodes = [
                T_FUNCTION,
                T_CLOSURE,
                T_FN,
            ];
            $functionPointer = TokenHelper::findNext($phpcsFile, $functionTokenCodes, $docCommentOpenPointer + 1);

            if ($functionPointer === null || $phpcsFile->getTokens()[$functionPointer]['code'] !== T_FUNCTION) {
                return null;
            }

            if ($annotationValue instanceof ParamTagValueNode) {
                $parameterTypeHints = FunctionHelper::getParametersTypeHints($phpcsFile, $functionPointer);

                return array_key_exists(
                    $annotationValue->parameterName,
                    $parameterTypeHints,
                ) && $parameterTypeHints[$annotationValue->parameterName] !== null
                    ? $parameterTypeHints[$annotationValue->parameterName]->getTypeHint()
                    : null;
            }

            $returnType = FunctionHelper::findReturnTypeHint($phpcsFile, $functionPointer);

            return $returnType !== null ? $returnType->getTypeHint() : null;
        }

        if (count($typeNode->types) !== 2) {
            return null;
        }

        if (
            $typeNode->types[0] instanceof ArrayTypeNode
            && $typeNode->types[1] instanceof IdentifierTypeNode
            && $this->isTraversableType(
                TypeHintHelper::getFullyQualifiedTypeHint($phpcsFile, $docCommentOpenPointer, $typeNode->types[1]->name),
            )
        ) {
            return $typeNode->types[1]->name;
        }

        if (
            $typeNode->types[1] instanceof ArrayTypeNode
            && $typeNode->types[0] instanceof IdentifierTypeNode
            && $this->isTraversableType(
                TypeHintHelper::getFullyQualifiedTypeHint($phpcsFile, $docCommentOpenPointer, $typeNode->types[0]->name),
            )
        ) {
            return $typeNode->types[0]->name;
        }

        return null;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    protected function isTraversableType(string $type): bool
    {
        return TypeHintHelper::isSimpleIterableTypeHint($type) || array_key_exists($type, $this->getNormalizedTraversableTypeHints());
    }

    /**
     * @return array<string, int>
     */
    protected function getNormalizedTraversableTypeHints(): array
    {
        if ($this->normalizedTraversableTypeHints === null) {
            $this->normalizedTraversableTypeHints = array_flip(array_map(static function (string $typeHint): string {
                return NamespaceHelper::isFullyQualifiedName($typeHint)
                    ? $typeHint
                    : sprintf('%s%s', NamespaceHelper::NAMESPACE_SEPARATOR, $typeHint);
            }, SniffSettingsHelper::normalizeArray($this->traversableTypeHints)));
        }

        return $this->normalizedTraversableTypeHints;
    }

    /**
     * @param \SlevomatCodingStandard\Helpers\Annotation $annotation
     *
     * @return bool
     */
    protected function isGenericObjectCollection(Annotation $annotation): bool
    {
        $arrayTypeNodes = $this->getArrayTypeNodes($annotation->getValue());

        foreach ($arrayTypeNodes as $arrayTypeNode) {
            /** @var list<\PHPStan\PhpDocParser\Ast\Type\UnionTypeNode> $unionTypeNodes */
            $unionTypeNodes = AnnotationHelper::getAnnotationNodesByType($annotation->getNode(), UnionTypeNode::class);
            $unionTypeNode = $this->findUnionTypeThatContainsArrayType($arrayTypeNode, $unionTypeNodes);

            if ($unionTypeNode !== null) {
                if ($this->isUnionTypeGenericObjectCollection($unionTypeNodes[0])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\UnionTypeNode $unionTypeNode
     *
     * @return bool
     */
    protected function isUnionTypeGenericObjectCollection(UnionTypeNode $unionTypeNode): bool
    {
        return $this->hasGenericObject($unionTypeNode->types)
            && $this->containsArrayTypeNode($unionTypeNode->types);
    }

    /**
     * These generic object collections are not yet understood by IDEs like PHPStorm.
     *
     * @param array<\PHPStan\PhpDocParser\Ast\Type\TypeNode> $types
     *
     * @return bool
     */
    protected function hasGenericObject(array $types): bool
    {
        foreach ($types as $type) {
            if (!$type instanceof IdentifierTypeNode) {
                continue;
            }

            if (str_starts_with((string)$type, '\\')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     *
     * @return bool
     */
    protected function isGenericObject(TypeNode $typeNode): bool
    {
        return $typeNode instanceof IdentifierTypeNode && str_starts_with((string)$typeNode, '\\');
    }

    /**
     * @param array<\PHPStan\PhpDocParser\Ast\Type\TypeNode> $types
     *
     * @return bool
     */
    protected function containsArrayTypeNode(array $types): bool
    {
        foreach ($types as $type) {
            if (!$type instanceof ArrayTypeNode) {
                continue;
            }

            if ($type->type instanceof IdentifierTypeNode || $type->type instanceof ArrayTypeNode) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     *
     * @return bool
     */
    protected function isArrayTypeNode(TypeNode $typeNode): bool
    {
        return $typeNode instanceof ArrayTypeNode &&
            ($typeNode->type instanceof IdentifierTypeNode || $typeNode->type instanceof ArrayTypeNode);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param \SlevomatCodingStandard\Helpers\Annotation $annotation
     * @param int $docCommentOpenPointer
     * @param \PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode $typeNode
     * @param array<\PHPStan\PhpDocParser\Ast\Type\UnionTypeNode> $unionTypeNodes
     *
     * @return void
     */
    protected function fixGenericObjectCollection(
        File $phpcsFile,
        Annotation $annotation,
        int $docCommentOpenPointer,
        ArrayTypeNode $typeNode,
        array $unionTypeNodes,
    ): void {
        $genericType = null;
        $arrayType = null;
        $unionTypes = [];

        foreach ($unionTypeNodes[0]->types as $type) {
            if ($this->isGenericObject($type)) {
                if ($genericType !== null) {
                    return;
                }

                $genericType = (string)$type;

                continue;
            }

            if ($this->isArrayTypeNode($type)) {
                if ($arrayType !== null) {
                    return;
                }

                /** @var \PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode $arrayTypeNode */
                $arrayTypeNode = $type;
                $arrayType = $this->convertTypeToString($arrayTypeNode->type);

                continue;
            }

            $unionTypes[] = (string)$type;
        }

        if (!$genericType || !$arrayType) {
            return;
        }

        $fix = $phpcsFile->addFixableError(
            sprintf(
                'Usage of old type hint syntax for generic in `%s` is disallowed, use generic type hint syntax instead.',
                AnnotationTypeHelper::print($typeNode),
            ),
            $annotation->getStartPointer(),
            static::CODE_DISALLOWED_ARRAY_TYPE_HINT_SYNTAX,
        );

        if (!$fix) {
            return;
        }

        $fixedType = sprintf('%s<%s>', $genericType, $arrayType);
        array_unshift($unionTypes, $fixedType);
        $fixedType = implode('|', $unionTypes);

        $this->fixAnnotation($phpcsFile, $annotation, $fixedType);
    }

    /**
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     *
     * @return string
     */
    protected function convertTypeToString(TypeNode $typeNode): string
    {
        if ($typeNode instanceof ArrayTypeNode) {
            return sprintf('array<%s>', $this->convertTypeToString($typeNode->type));
        }

        return (string)$typeNode;
    }
}
