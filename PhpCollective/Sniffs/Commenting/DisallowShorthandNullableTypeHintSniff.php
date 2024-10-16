<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCollective\Traits\CommentingTrait;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Printer\Printer;

/**
 * Disallows use of `?type` in favor of `type|null`. Reduces conflict or issues with other sniffs.
 */
class DisallowShorthandNullableTypeHintSniff implements Sniff
{
    use CommentingTrait;

    /**
     * @var string
     */
    public const CODE_DISALLOWED_SHORTHAND_TYPE_HINT = 'DisallowedShorthandTypeHint';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_DOC_COMMENT_STRING,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $pointer): void
    {
        $tokens = $phpcsFile->getTokens();
        $docCommentContent = $tokens[$pointer]['content'];

        /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode $valueNode */
        $valueNode = static::getValueNode($tokens[$pointer - 2]['content'], $docCommentContent);
        if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
            return;
        }

        $printer = new Printer();
        $before = $printer->print($valueNode);
        // Traverse and fix the nullable types
        $this->traversePhpDocNode($valueNode);

        $after = $printer->print($valueNode);

        if ($after === $before) {
            return;
        }

        $message = sprintf('Shorthand nullable `%s` invalid, use `%s` instead.', $before, $after);
        $fixable = $phpcsFile->addFixableError($message, $pointer, static::CODE_DISALLOWED_SHORTHAND_TYPE_HINT);
        if ($fixable) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($pointer, $after);
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Traverse and transform the PHPDoc AST.
     *
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode $phpDocNode
     *
     * @return void
     */
    protected function traversePhpDocNode(PhpDocTagValueNode $phpDocNode): void
    {
        if (
            $phpDocNode instanceof ParamTagValueNode
            || $phpDocNode instanceof ReturnTagValueNode
            || $phpDocNode instanceof VarTagValueNode
        ) {
            // Traverse the type node recursively
            $phpDocNode->type = $this->transformNullableType($phpDocNode->type);
        }
    }

    /**
     * Traverse and transform nullable types.
     *
     * @param \PHPStan\PhpDocParser\Ast\Type\TypeNode $typeNode
     *
     * @return \PHPStan\PhpDocParser\Ast\Type\TypeNode
     */
    protected function transformNullableType(TypeNode $typeNode): TypeNode
    {
        if ($typeNode instanceof NullableTypeNode) {
            $innerType = $typeNode->type;

            // Convert `?Type` to `Type|null`
            return new UnionTypeNode([
                $innerType,
                new IdentifierTypeNode('null'),
            ]);
        }

        // Recursively handle UnionTypeNode (e.g., `Type|null`)
        if ($typeNode instanceof UnionTypeNode) {
            // Traverse each type in the union and transform nullable types
            foreach ($typeNode->types as &$subType) {
                $subType = $this->transformNullableType($subType);
            }

            return $typeNode;
        }

        // Recursively handle other nodes that might contain nested types
        if (property_exists($typeNode, 'types') && is_array($typeNode->types)) {
            foreach ($typeNode->types as &$subType) {
                $subType = $this->transformNullableType($subType);
            }
        }

        return $typeNode;
    }

    /**
     * @param array<string> $types
     *
     * @return bool
     */
    protected function containsShorthand(array $types): bool
    {
        foreach ($types as $type) {
            if (str_starts_with($type, '?')) {
                return true;
            }
        }

        return false;
    }
}
