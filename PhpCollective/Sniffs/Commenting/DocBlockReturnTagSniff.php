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
 * Verifies that a `@return` tag description does not start with $ sign to avoid accidental variable copy-and-paste.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockReturnTagSniff extends AbstractSniff
{
    use CommentingTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_DOC_COMMENT_TAG];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['content'] !== '@return') {
            return;
        }

        $nextIndex = $phpcsFile->findNext(T_DOC_COMMENT_STRING, $stackPtr + 1, $stackPtr + 3);
        if (!$nextIndex) {
            return;
        }

        $this->assertDescription($phpcsFile, $nextIndex, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $nextIndex
     * @param int $stackPtr
     *
     * @return void
     */
    protected function assertDescription(File $phpcsFile, int $nextIndex, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $content = $tokens[$nextIndex]['content'];
        /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\TypelessParamTagValueNode|\PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode $valueNode */
        $valueNode = static::getValueNode($tokens[$stackPtr]['content'], $content);
        if ($valueNode instanceof InvalidTagValueNode || $valueNode instanceof TypelessParamTagValueNode) {
            return;
        }

        $returnTypes = $this->valueNodeParts($valueNode);
        $typeString = $this->renderUnionTypes($returnTypes);

        if (!str_starts_with($content, $typeString)) {
            return;
        }

        $description = mb_substr($content, mb_strlen($typeString) + 1);
        if (!$description || !str_starts_with($description, '$')) {
            return;
        }

        $phpcsFile->addError('Description for return annotation must not start with `$`/variable. Use normal sentence instead.', $nextIndex, 'Invalid');
    }
}
