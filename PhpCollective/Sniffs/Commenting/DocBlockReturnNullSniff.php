<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Traits\BasicsTrait;
use PhpCollective\Traits\CommentingTrait;
use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode;

/**
 * Doc blocks should type-hint returning null for nullable return values (if null is used besides other return values).
 */
class DocBlockReturnNullSniff implements Sniff
{
    use BasicsTrait;
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
        $tokens = $phpcsFile->getTokens();

        // Don't mess with closures
        $prevIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPointer - 1, null, true);
        if (!$this->isGivenKind(Tokens::$methodPrefixes, $tokens[$prevIndex])) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $returnTypes = $this->extractReturnTypes($phpcsFile, $stackPointer);
        if (!$returnTypes) {
            return;
        }
        if (count($returnTypes) === 2 && in_array('', $returnTypes, true) && in_array('null', $returnTypes, true)) {
            $phpcsFile->addError('Void mixed with null is discouraged, use only `null` instead', $docBlockEndIndex, 'NullVoidMixed');

            return;
        }
        if (count($returnTypes) > 1 && in_array('', $returnTypes, true)) {
            $phpcsFile->addWarning('Void mixed with other return types is discouraged, use `null` instead', $docBlockEndIndex, 'InvalidVoid');

            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@return'], true)) {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];
            if (!$content) {
                continue;
            }

            /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode $valueNode */
            $valueNode = static::getValueNode($tokens[$i]['content'], $content);
            if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
                return;
            }
            $parts = $this->valueNodeParts($valueNode);

            $this->fixParts($phpcsFile, $classNameIndex, $returnTypes, $parts, $valueNode->description);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $classNameIndex
     * @param array<string> $returnTypes
     * @param array<string> $parts
     * @param string $appendix
     *
     * @return void
     */
    protected function fixParts(File $phpcsFile, int $classNameIndex, array $returnTypes, array $parts, string $appendix): void
    {
        if (!in_array('null', $returnTypes, true)) {
            // For now only "return null", later we can add all values to comparison
            return;
        }
        if (in_array('null', $parts, true) || in_array('mixed', $parts, true)) {
            return;
        }

        $newParts = $parts;
        $newParts[] = 'null';

        $newContent = implode('|', $newParts);

        $fix = $phpcsFile->addFixableError('Missing nullable type in `' . implode('|', $parts) . '` return annotation, expected `' . $newContent . '`', $classNameIndex, 'MissingNullable');
        if ($fix) {
            if ($appendix !== '') {
                $appendix = ' ' . $appendix;
            }
            $phpcsFile->fixer->replaceToken($classNameIndex, $newContent . $appendix);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @return int|null Stackpointer value of docblock end tag, or null if cannot be found
     */
    protected function findRelatedDocBlock(File $phpcsFile, int $stackPointer): ?int
    {
        $tokens = $phpcsFile->getTokens();

        $line = $tokens[$stackPointer]['line'];
        $beginningOfLine = $stackPointer;
        while (!empty($tokens[$beginningOfLine - 1]) && $tokens[$beginningOfLine - 1]['line'] === $line) {
            $beginningOfLine--;
        }

        if (!empty($tokens[$beginningOfLine - 2]) && $tokens[$beginningOfLine - 2]['type'] === 'T_DOC_COMMENT_CLOSE_TAG') {
            return $beginningOfLine - 2;
        }

        return null;
    }

    /**
     * For right now we only try to detect basic types.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $index
     *
     * @return array<string>
     */
    protected function extractReturnTypes(File $phpcsFile, int $index): array
    {
        $tokens = $phpcsFile->getTokens();

        if (empty($tokens[$index]['scope_opener']) || empty($tokens[$index]['scope_closer'])) {
            return [];
        }

        $types = [];

        $methodStartIndex = $tokens[$index]['scope_opener'];
        $methodEndIndex = $tokens[$index]['scope_closer'];

        for ($i = $methodStartIndex + 1; $i < $methodEndIndex; ++$i) {
            if ($this->isGivenKind([T_FUNCTION, T_CLOSURE], $tokens[$i])) {
                $endIndex = $tokens[$i]['scope_closer'];
                if (!empty($tokens[$i]['nested_parenthesis'])) {
                    $endIndex = array_pop($tokens[$i]['nested_parenthesis']);
                }

                $i = $endIndex;

                continue;
            }

            if (!$this->isGivenKind([T_RETURN], $tokens[$i])) {
                continue;
            }

            $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $i + 1, null, true);
            if (!$nextIndex) {
                continue;
            }
            $lastIndex = $phpcsFile->findNext(T_SEMICOLON, $nextIndex);

            $type = '';
            for ($j = $nextIndex; $j < $lastIndex; $j++) {
                $type .= $tokens[$j]['content'];
            }

            if (in_array($type, $types, true)) {
                continue;
            }
            $types[] = $type;
        }

        return $types;
    }
}
