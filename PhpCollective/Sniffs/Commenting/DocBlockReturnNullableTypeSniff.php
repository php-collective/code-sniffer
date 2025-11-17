<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;
use PhpCollective\Traits\CommentingTrait;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;
use RuntimeException;
use SlevomatCodingStandard\Helpers\DocCommentHelper;
use SlevomatCodingStandard\Helpers\FunctionHelper;

/**
 * Checks for missing/superfluous `|null` in docblock return annotations.
 */
class DocBlockReturnNullableTypeSniff extends AbstractSniff
{
    use CommentingTrait;

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
        $returnType = FunctionHelper::findReturnTypeHint($phpcsFile, $stackPointer);
        if ($returnType === null) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $docBlockReturnIndex = $this->findDocBlockReturn($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
        if (!$docBlockReturnIndex) {
            return;
        }

        $nextIndex = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $docBlockReturnIndex + 1, $docBlockEndIndex);
        if (!$nextIndex) {
            return;
        }

        $docBlockReturnTypes = $this->parseDocBlockReturnTypes($phpcsFile, $nextIndex);
        if ($docBlockReturnTypes === null) {
            return;
        }

        if ($returnType->isNullable()) {
            $this->assertRequiredNullableReturnType($phpcsFile, $stackPointer, $docBlockReturnTypes);

            return;
        }

        if ($returnType->getTypeHint() === 'mixed') {
            return;
        }

        $this->assertNotNullableReturnType($phpcsFile, $stackPointer, $docBlockReturnTypes);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @return array<string>|null
     */
    protected function parseDocBlockReturnTypes(File $phpcsFile, int $stackPointer): ?array
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$stackPointer]['content'];
        /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode $valueNode */
        $valueNode = static::getValueNode($tokens[$stackPointer - 2]['content'], $content);
        if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
            return [];
        }

        return $this->valueNodeParts($valueNode);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return int|null
     */
    protected function findDocBlockReturn(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex): ?int
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if (!$this->isGivenKind(T_DOC_COMMENT_TAG, $tokens[$i])) {
                continue;
            }
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            return $i;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     * @param array<string> $docBlockReturnTypes
     *
     * @return void
     */
    public function assertNotNullableReturnType(File $phpcsFile, int $stackPointer, array $docBlockReturnTypes): void
    {
        if (!$docBlockReturnTypes) {
            return;
        }
        if (!in_array('null', $docBlockReturnTypes, true)) {
            return;
        }

        $errorMessage = 'Method should not have `null` in return type in doc block.';
        $fix = $phpcsFile->addFixableError($errorMessage, $stackPointer, 'ReturnNullableInvalid');

        if (!$fix) {
            return;
        }

        $this->removeNullFromDocBlockReturnType($phpcsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     * @param array<string> $docBlockReturnTypes
     *
     * @return void
     */
    public function assertRequiredNullableReturnType(
        File $phpcsFile,
        int $stackPointer,
        array $docBlockReturnTypes,
    ): void {
        if (!$docBlockReturnTypes) {
            return;
        }
        if (in_array('null', $docBlockReturnTypes, true)) {
            return;
        }

        $errorMessage = 'Method does not have `null` in return type in doc block.';
        $fix = $phpcsFile->addFixableError($errorMessage, $stackPointer, 'ReturnNullableMissing');

        if (!$fix) {
            return;
        }

        $this->addNullToDocBlockReturnType($phpcsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function addNullToDocBlockReturnType(File $phpcsFile, int $stackPointer): void
    {
        $returnTypeToken = $this->getDocBlockReturnTypeToken($phpcsFile, $stackPointer);

        $tokenIndex = $returnTypeToken['index'];
        $returnTypes = $returnTypeToken['token']['content'];
        $returnTypes = trim($returnTypes, '|') . '|null';

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($tokenIndex, $returnTypes);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function removeNullFromDocBlockReturnType(File $phpcsFile, int $stackPointer): void
    {
        $returnTypesToken = $this->getDocBlockReturnTypeToken($phpcsFile, $stackPointer);

        $tokenIndex = $returnTypesToken['index'];
        $returnTypes = explode('|', $returnTypesToken['token']['content']);
        foreach ($returnTypes as $key => $returnType) {
            if ($returnType === 'null') {
                unset($returnTypes[$key]);
            }
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($tokenIndex, implode('|', $returnTypes));
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @throws \RuntimeException
     *
     * @return array<string, mixed>
     */
    protected function getDocBlockReturnTypeToken(File $phpcsFile, int $stackPointer): array
    {
        $tokens = $phpcsFile->getTokens();

        $docBlockStartIndex = DocCommentHelper::findDocCommentOpenPointer($phpcsFile, $stackPointer);
        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            return [];
        }

        for ($i = $docBlockEndIndex; $i >= $docBlockStartIndex; $i--) {
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            $returnTypesTokenIndex = $phpcsFile->findNext(
                [T_DOC_COMMENT_WHITESPACE],
                $i + 1,
                null,
                true,
            );

            return [
                'tagIndex' => $i,
                'index' => $returnTypesTokenIndex,
                'token' => $tokens[$returnTypesTokenIndex],
            ];
        }

        throw new RuntimeException('No token found.');
    }
}
