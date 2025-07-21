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

/**
 * Makes sure doc block param types are never just `null`, but always another type and optionally nullable on top.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockParamNotJustNullSniff extends AbstractSniff
{
    use CommentingTrait;
    use SignatureTrait;

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
    public function process(File $phpCsFile, $stackPointer): void
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $methodSignature = $this->getMethodSignature($phpCsFile, $stackPointer);
        if (!$methodSignature) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $paramCount = 0;
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@param'], true)) {
                continue;
            }

            if (empty($methodSignature[$paramCount])) {
                continue;
            }
            $paramCount++;

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                // Let another sniffer take care of the missing type
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];
            if (!$content) {
                continue;
            }

            $varIndex = strpos($content, '$');
            if ($varIndex === false) {
                continue;
            }

            $content = trim(substr($content, 0, $varIndex));
            if (!$content) {
                $phpCsFile->addError('Param type is empty', $classNameIndex, 'EmptyType');

                continue;
            }

            if ($content !== 'null') {
                continue;
            }

            $phpCsFile->addError('"null" as only param type does not make sense', $classNameIndex, 'NotJustNull');
        }
    }
}
