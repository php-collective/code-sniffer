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

        /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode $valueNode */
        $valueNode = static::getValueNode($tokens[$pointer - 2]['content'], $docCommentContent);

        $printer = new Printer();
        $before = $printer->print($valueNode);

        // Check if the value node is invalid and handle it
        if ($valueNode instanceof InvalidTagValueNode) {
            // Attempt to clean up and process invalid types
            $fixedNode = $this->fixInvalidTagValueNode($valueNode);
            if ($fixedNode) {
                $valueNode = $fixedNode;
            }
        }

        if ($valueNode instanceof InvalidTagValueNode) {
            return;
        }

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
     * Attempt to fix an InvalidTagValueNode by parsing and correcting the types manually.
     *
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode $invalidNode
     *
     * @return \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode|null
     */
    protected function fixInvalidTagValueNode(InvalidTagValueNode $invalidNode): ?PhpDocTagValueNode
    {
        $value = $invalidNode->value;
        $rest = '';
        if (str_contains($value, '$')) {
            $string = trim(substr($value, 0, (int)strpos($value, '$')));
            $rest = trim(substr($value, strlen($string)));
            $value = $string;
        }

        // Try to parse and correct the invalid node's type (e.g., `?string|null`)
        if (str_contains($value, '|')) {
            // Split the types
            $types = explode('|', $value);

            $transformedTypes = [];
            $hasNullable = false;

            foreach ($types as $type) {
                $type = trim($type);

                // Handle `?Type` shorthand
                if (str_starts_with($type, '?')) {
                    $type = substr($type, 1); // Remove leading '?'
                    $transformedTypes[] = new IdentifierTypeNode($type);
                    $hasNullable = true; // Mark as nullable
                } elseif (strtolower($type) === 'null') {
                    // If 'null' is encountered, mark as nullable but don't add now
                    $hasNullable = true;
                } else {
                    $transformedTypes[] = new IdentifierTypeNode($type);
                }
            }

            // Add `null` at the end if the type is nullable
            if ($hasNullable) {
                $transformedTypes[] = new IdentifierTypeNode('null');
            }

            // Create a new UnionTypeNode with the transformed types
            return new ParamTagValueNode(
                new UnionTypeNode($transformedTypes),
                false,
                $rest,
                '',
                false,
            );
        }

        return null;
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

        // Handle UnionTypeNode (e.g., `Type|null`)
        if ($typeNode instanceof UnionTypeNode) {
            $transformedTypes = [];
            foreach ($typeNode->types as $subType) {
                $transformedTypes[] = $this->transformNullableType($subType); // Recursively transform
            }

            return new UnionTypeNode($transformedTypes);
        }

        return $typeNode;
    }
}
