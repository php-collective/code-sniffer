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

/**
 * Makes sure doc block param type array is only used once.
 * So `array|\Foo\Bar[]` would prefer just `\Foo\Bar[]`.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockParamArraySniff extends AbstractSniff
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
        $tokens = $phpcsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@param', '@return'], true)) {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];
            /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode $valueNode */
            $valueNode = static::getValueNode($tokens[$i]['content'], $content);
            if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
                return;
            }
            $parts = $this->valueNodeParts($valueNode);

            $detectedType = null;
            $types = ['array', 'iterable', 'list'];
            foreach ($types as $type) {
                if (!in_array($type, $parts, true) || !$this->containsTypeArray($parts, $type)) {
                    continue;
                }
                $detectedType = $type;
            }
            if (!$detectedType) {
                continue;
            }

            $error = 'Doc Block param type `' . $detectedType . '` not needed on top of  `...[]`';
            $fix = $phpcsFile->addFixableError($error, $classNameIndex, 'TypeDuplicated');
            if (!$fix) {
                continue;
            }

            $keys = array_keys($parts, $detectedType);
            foreach ($keys as $key) {
                unset($parts[$key]);
            }
            $content = $this->stringifyValueNode($parts, $valueNode);

            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($classNameIndex, $content);
            $phpcsFile->fixer->endChangeset();
        }
    }
}
