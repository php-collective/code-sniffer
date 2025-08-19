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
 * Makes sure doc block param types match the variable name of the method signature.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockParamSniff extends AbstractSniff
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

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        if ($this->hasInheritDoc($phpCsFile, $docBlockStartIndex, $docBlockEndIndex)) {
            return;
        }

        $methodSignature = $this->getMethodSignature($phpCsFile, $stackPointer);
        if (!$methodSignature) {
            $this->assertNoParams($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);

            return;
        }

        $docBlockParams = [];
        $hasMissingTypes = false;
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@param'], true)) {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                $phpCsFile->addError('Missing type in param doc block', $i, 'MissingType');
                $hasMissingTypes = true;

                continue;
            }

            $content = $tokens[$classNameIndex]['content'];

            // Check if the content starts with $ (missing type)
            if (str_starts_with($content, '$')) {
                $phpCsFile->addError('Missing type in param doc block', $i, 'MissingType');
                $hasMissingTypes = true;

                continue;
            }

            $appendix = '';
            $spacePos = strpos($content, ' ');
            if ($spacePos) {
                $appendix = substr($content, $spacePos);
                $content = substr($content, 0, $spacePos);
            }

            preg_match('/\$[^\s]+/', $appendix, $matches);
            $variable = $matches ? $matches[0] : '';

            $docBlockParams[] = [
                'index' => $classNameIndex,
                'type' => $content,
                'variable' => $variable,
                'appendix' => $appendix,
            ];
        }

        // If no @param annotations found, check if all parameters are fully typed
        // Only skip validation if all parameters have type declarations
        if (count($docBlockParams) === 0) {
            if ($this->areAllParametersFullyTyped($methodSignature)) {
                return;
            }
        }

        if (count($docBlockParams) !== count($methodSignature)) {
            // Check if we can fix by adding missing params (when all method params are typed and no missing types in existing params)
            if (!$hasMissingTypes && count($docBlockParams) < count($methodSignature) && $this->canAddMissingParams($phpCsFile, $docBlockStartIndex, $docBlockEndIndex, $docBlockParams, $methodSignature)) {
                return;
            }

            // Check if we have extra params that can be removed
            if (count($docBlockParams) > count($methodSignature)) {
                $this->handleExtraParams($phpCsFile, $docBlockStartIndex, $docBlockEndIndex, $docBlockParams, $methodSignature);

                return;
            }

            $phpCsFile->addError('Doc Block params do not match method signature', $stackPointer, 'SignatureMismatch');

            return;
        }

        foreach ($docBlockParams as $docBlockParam) {
            /** @var array<string, mixed> $methodParam */
            $methodParam = array_shift($methodSignature);
            $variableName = $tokens[$methodParam['variableIndex']]['content'];

            if ($docBlockParam['variable'] === $variableName) {
                continue;
            }
            // We let other sniffers take care of missing type for now
            if (str_contains($docBlockParam['type'], '$')) {
                continue;
            }

            $error = 'Doc Block param variable `' . $docBlockParam['variable'] . '` should be `' . $variableName . '`';
            // For now just report (buggy yet)
            $phpCsFile->addError($error, $docBlockParam['index'], 'VariableWrong');
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function assertNoParams(File $phpCsFile, int $docBlockStartIndex, int $docBlockEndIndex): void
    {
        $tokens = $phpCsFile->getTokens();

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if ($tokens[$i]['content'] !== '@param') {
                continue;
            }

            $fix = $phpCsFile->addFixableError('Doc Block param does not match method signature and should be removed', $i, 'ExtraParam');

            if ($fix === true) {
                $this->removeParamLine($phpCsFile, $i);
            }
        }
    }

    /**
     * Check if all method parameters are fully typed.
     *
     * @param array<int, array<string, mixed>> $methodSignature
     *
     * @return bool
     */
    protected function areAllParametersFullyTyped(array $methodSignature): bool
    {
        foreach ($methodSignature as $param) {
            // Parameter must have a type hint
            if (empty($param['typehint'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @param array<array<string, mixed>> $docBlockParams
     * @param array<int, array<string, mixed>> $methodSignature
     *
     * @return bool
     */
    protected function canAddMissingParams(File $phpCsFile, int $docBlockStartIndex, int $docBlockEndIndex, array $docBlockParams, array $methodSignature): bool
    {
        $tokens = $phpCsFile->getTokens();

        // Check if all params have types so we can add them
        foreach ($methodSignature as $param) {
            if (empty($param['typehintFull'])) {
                return false;
            }
        }

        // Find the position to insert new params (after last @param or before close comment)
        $insertPosition = $docBlockEndIndex - 1;
        $lastParamIndex = null;

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] === 'T_DOC_COMMENT_TAG' && $tokens[$i]['content'] === '@param') {
                $lastParamIndex = $i;
                // Find the end of this param line
                for ($j = $i + 1; $j < $docBlockEndIndex; $j++) {
                    if ($tokens[$j]['content'] === "\n" || $tokens[$j]['type'] === 'T_DOC_COMMENT_CLOSE_TAG') {
                        $insertPosition = $j;

                        break;
                    }
                }
            }
        }

        $fix = $phpCsFile->addFixableError('Doc Block params do not match method signature', $docBlockStartIndex + 1, 'SignatureMismatch');

        if ($fix === true) {
            $phpCsFile->fixer->beginChangeset();

            // Build list of existing param variables
            $existingVars = [];
            foreach ($docBlockParams as $param) {
                $existingVars[] = $param['variable'];
            }

            // Add missing params
            foreach ($methodSignature as $methodParam) {
                $variable = $tokens[$methodParam['variableIndex']]['content'];
                if (!in_array($variable, $existingVars, true)) {
                    $indent = $this->getIndentForParam($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);
                    $paramLine = "\n" . $indent . '* @param ' . $methodParam['typehintFull'] . ' ' . $variable;

                    $phpCsFile->fixer->addContentBefore($insertPosition, $paramLine);
                }
            }

            $phpCsFile->fixer->endChangeset();
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $paramTagIndex
     *
     * @return void
     */
    protected function removeParamLine(File $phpCsFile, int $paramTagIndex): void
    {
        $tokens = $phpCsFile->getTokens();

        $phpCsFile->fixer->beginChangeset();

        // Find the start of the line
        $lineStart = $paramTagIndex;
        for ($i = $paramTagIndex - 1; $i >= 0; $i--) {
            if ($tokens[$i]['content'] === "\n") {
                break;
            }
            $lineStart = $i;
        }

        // Find the end of the line
        $lineEnd = $paramTagIndex;
        $count = count($tokens);
        for ($i = $paramTagIndex + 1; $i < $count; $i++) {
            $lineEnd = $i;
            if ($tokens[$i]['content'] === "\n") {
                break;
            }
        }

        // Remove the entire line
        for ($i = $lineStart; $i <= $lineEnd; $i++) {
            $phpCsFile->fixer->replaceToken($i, '');
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return string
     */
    protected function getIndentForParam(File $phpCsFile, int $docBlockStartIndex, int $docBlockEndIndex): string
    {
        $tokens = $phpCsFile->getTokens();

        // Find an existing @param or use the doc block start
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] === 'T_DOC_COMMENT_TAG') {
                // Get the indent from this line
                for ($j = $i - 1; $j >= 0; $j--) {
                    if ($tokens[$j]['content'] === "\n") {
                        if (isset($tokens[$j + 1]) && $tokens[$j + 1]['type'] === 'T_DOC_COMMENT_WHITESPACE') {
                            return $tokens[$j + 1]['content'];
                        }

                        break;
                    }
                }
            }
        }

        return '     ';
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @param array<array<string, mixed>> $docBlockParams
     * @param array<int, array<string, mixed>> $methodSignature
     *
     * @return void
     */
    protected function handleExtraParams(File $phpCsFile, int $docBlockStartIndex, int $docBlockEndIndex, array $docBlockParams, array $methodSignature): void
    {
        $tokens = $phpCsFile->getTokens();

        // Build list of expected param variables
        $expectedVars = [];
        foreach ($methodSignature as $param) {
            $expectedVars[] = $tokens[$param['variableIndex']]['content'];
        }

        // Find and mark extra params for removal
        $hasFixableError = false;
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG' || $tokens[$i]['content'] !== '@param') {
                continue;
            }

            // Find the variable name for this @param
            $variable = null;
            $classNameIndex = $i + 2;
            if (isset($tokens[$classNameIndex]) && $tokens[$classNameIndex]['type'] === 'T_DOC_COMMENT_STRING') {
                $content = $tokens[$classNameIndex]['content'];

                // Check if content starts with $ (missing type)
                if (str_starts_with($content, '$')) {
                    $variable = explode(' ', $content)[0];
                } else {
                    // Extract variable from content
                    $spacePos = strpos($content, ' ');
                    if ($spacePos) {
                        $appendix = substr($content, $spacePos);
                        preg_match('/\$[^\s]+/', $appendix, $matches);
                        $variable = $matches ? $matches[0] : null;
                    }
                }
            }

            // If this param is not in the expected list, mark for removal
            if ($variable && !in_array($variable, $expectedVars, true)) {
                $fix = $phpCsFile->addFixableError('Doc Block param does not match method signature and should be removed', $i, 'ExtraParam');

                if ($fix === true) {
                    $hasFixableError = true;
                    $this->removeParamLine($phpCsFile, $i);
                }
            }
        }

        if (!$hasFixableError) {
            $phpCsFile->addError('Doc Block params do not match method signature', $docBlockStartIndex + 1, 'SignatureMismatch');
        }
    }
}
