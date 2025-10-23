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
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;

/**
 * Ensures Doc Blocks for constants exist and are correct.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockConstSniff extends AbstractSniff
{
    use CommentingTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_CONST,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPointer): void
    {
        $tokens = $phpcsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            $defaultValueType = $this->findDefaultValueType($phpcsFile, $stackPointer);
            if ($defaultValueType === null) {
                // Let's ignore for now
                //$phpcsFile->addError('Doc Block for const missing', $stackPointer, 'VarDocBlockMissing');

                return;
            }

            if ($defaultValueType === 'null') {
                $phpcsFile->addError('Doc Block `@var` with type `...|' . $defaultValueType . '` for const missing', $stackPointer, 'VarDocBlockMissing');

                return;
            }

            $fix = $phpcsFile->addFixableError('Doc Block for const missing', $stackPointer, 'VarDocBlockMissing');
            if (!$fix) {
                return;
            }

            $this->addDocBlock($phpcsFile, $stackPointer, $defaultValueType);

            return;
        }

        /** @var int $docBlockStartIndex */
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];
        if ($this->hasInheritDoc($phpcsFile, $docBlockStartIndex, $docBlockEndIndex)) {
            return;
        }

        $defaultValueType = $this->findDefaultValueType($phpcsFile, $stackPointer);

        $tagIndex = null;
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@var', '@const'], true)) {
                continue;
            }

            $tagIndex = $i;
        }

        if (!$tagIndex) {
            $this->handleMissingVar($phpcsFile, $docBlockEndIndex, $docBlockStartIndex, $defaultValueType);

            return;
        }

        $typeIndex = $tagIndex + 2;

        if ($tokens[$typeIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
            $this->handleMissingVarType($phpcsFile, $tagIndex, $defaultValueType);

            return;
        }

        $tagIndexContent = $tokens[$tagIndex]['content'];
        $requiresTagUpdate = $tagIndexContent !== '@var';
        if ($requiresTagUpdate) {
            $fix = $phpcsFile->addFixableError(sprintf('Wrong tag used, expected `%s`, got `%s`', '@var', $tagIndexContent), $tagIndex, 'WrongTag');
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($tagIndex, '@var');
                $phpcsFile->fixer->endChangeset();
            }
        }

        $content = $tokens[$typeIndex]['content'];
        if (!$content) {
            $error = 'Doc Block type for property annotation @var missing';
            if ($defaultValueType) {
                $error .= ', type `' . $defaultValueType . '` detected';
            }
            $phpcsFile->addError($error, $stackPointer, 'VarTypeEmpty');

            return;
        }

        if ($defaultValueType === null) {
            return;
        }

        /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode $valueNode */
        $valueNode = static::getValueNode($tokens[$tagIndex]['content'], $content);
        if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
            return;
        }
        $parts = $this->valueNodeParts($valueNode);

        if (in_array($defaultValueType, $parts, true)) {
            return;
        }
        if ($defaultValueType === 'array' && ($this->containsTypeArray($parts) || $this->containsTypeArray($parts, 'list'))) {
            return;
        }
        if ($defaultValueType === 'false' && in_array('bool', $parts, true)) {
            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $fix = $phpcsFile->addFixableError('Doc Block type `' . $content . '` for property annotation @var incorrect, type `' . $defaultValueType . '` expected', $stackPointer, 'VarTypeIncorrect');
        if ($fix) {
            $newComment = trim(sprintf(
                '%s %s %s',
                implode('|', $parts),
                $valueNode->variableName,
                $valueNode->description,
            ));
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($typeIndex, $newComment);
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @return string|null
     */
    protected function findDefaultValueType(File $phpcsFile, int $stackPointer): ?string
    {
        $tokens = $phpcsFile->getTokens();

        $nameIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPointer + 1, null, true);
        if (!$nameIndex || !$this->isGivenKind(T_STRING, $tokens[$nameIndex])) {
            return null;
        }

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $nameIndex + 1, null, true);
        if (!$nextIndex || !$this->isGivenKind(T_EQUAL, $tokens[$nextIndex])) {
            return null;
        }

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $nextIndex + 1, null, true);
        if (!$nextIndex) {
            return null;
        }

        return $this->detectType($tokens[$nextIndex]);
    }

    /**
     * @param array<string, mixed> $token
     *
     * @return string|null
     */
    protected function detectType(array $token): ?string
    {
        if ($this->isGivenKind(T_OPEN_SHORT_ARRAY, $token)) {
            return 'array';
        }

        if ($this->isGivenKind(T_LNUMBER, $token)) {
            return 'int';
        }

        if ($this->isGivenKind(T_CONSTANT_ENCAPSED_STRING, $token)) {
            return 'string';
        }

        if ($this->isGivenKind([T_TRUE], $token)) {
            return 'bool';
        }

        if ($this->isGivenKind([T_FALSE], $token)) {
            return 'false';
        }

        if ($this->isGivenKind(T_NULL, $token)) {
            return 'null';
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockEndIndex
     * @param int $docBlockStartIndex
     * @param string|null $defaultValueType
     *
     * @return void
     */
    protected function handleMissingVar(
        File $phpcsFile,
        int $docBlockEndIndex,
        int $docBlockStartIndex,
        ?string $defaultValueType,
    ): void {
        $error = 'Doc Block annotation @var for const missing';

        if ($defaultValueType === null) {
            // Let's skip for now for non-trivial cases
            //$phpcsFile->addError($error, $docBlockEndIndex, 'DocBlockMissing');

            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $error .= ', type `' . $defaultValueType . '` detected';

        if ($defaultValueType === 'null') {
            $phpcsFile->addError($error, $docBlockEndIndex, 'TypeMissing');

            return;
        }

        $fix = $phpcsFile->addFixableError($error, $docBlockEndIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $index = $phpcsFile->findPrevious(T_DOC_COMMENT_WHITESPACE, $docBlockEndIndex - 1, $docBlockStartIndex, true);
        if (!$index) {
            $index = $docBlockStartIndex;
        }

        $whitespace = $this->getIndentationWhitespace($phpcsFile, $index);

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addNewline($index);
        $phpcsFile->fixer->addContent($index, $whitespace . '* @var ' . $defaultValueType);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $varIndex
     * @param string|null $defaultValueType
     *
     * @return void
     */
    protected function handleMissingVarType(File $phpcsFile, int $varIndex, ?string $defaultValueType): void
    {
        $error = 'Doc Block type for property annotation @var missing';
        if ($defaultValueType === null) {
            $phpcsFile->addError($error, $varIndex, 'VarTypeMissing');

            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $error .= ', type `' . $defaultValueType . '` detected';
        $fix = $phpcsFile->addFixableError($error, $varIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addContent($varIndex, ' ' . $defaultValueType);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     * @param string $defaultValueType
     *
     * @return void
     */
    protected function addDocBlock(File $phpcsFile, int $stackPointer, string $defaultValueType): void
    {
        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $tokens = $phpcsFile->getTokens();

        $firstTokenOfLine = $this->getFirstTokenOfLine($tokens, $stackPointer);

        $prevContentIndex = $phpcsFile->findPrevious(T_WHITESPACE, $firstTokenOfLine - 1, null, true);
        if (!$prevContentIndex) {
            return;
        }

        if ($tokens[$prevContentIndex]['type'] === 'T_ATTRIBUTE_END') {
            $firstTokenOfLine = $this->getFirstTokenOfLine($tokens, $prevContentIndex);
        }

        $indentation = $this->getIndentationWhitespace($phpcsFile, $stackPointer);

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addNewlineBefore($firstTokenOfLine);
        $phpcsFile->fixer->addContentBefore($firstTokenOfLine, $indentation . ' */');
        $phpcsFile->fixer->addNewlineBefore($firstTokenOfLine);
        $phpcsFile->fixer->addContentBefore($firstTokenOfLine, $indentation . ' * @var ' . $defaultValueType);
        $phpcsFile->fixer->addNewlineBefore($firstTokenOfLine);
        $phpcsFile->fixer->addContentBefore($firstTokenOfLine, $indentation . '/**');

        $phpcsFile->fixer->endChangeset();
    }
}
