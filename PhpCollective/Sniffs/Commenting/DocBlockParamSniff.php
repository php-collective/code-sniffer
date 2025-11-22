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
    public function process(File $phpcsFile, $stackPointer): void
    {
        $tokens = $phpcsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        if ($this->hasInheritDoc($phpcsFile, $docBlockStartIndex, $docBlockEndIndex)) {
            return;
        }

        $methodSignature = $this->getMethodSignature($phpcsFile, $stackPointer);
        if (!$methodSignature) {
            $this->assertNoParams($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);

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
                $phpcsFile->addError('Missing type in param doc block', $i, 'MissingType');
                $hasMissingTypes = true;

                continue;
            }

            $content = $tokens[$classNameIndex]['content'];

            // Check if the content starts with $ (missing type)
            if (str_starts_with($content, '$')) {
                $phpcsFile->addError('Missing type in param doc block', $i, 'MissingType');
                $hasMissingTypes = true;

                continue;
            }

            // Check if this might be a multi-line type (has unclosed brackets)
            $openBrackets = substr_count($content, '<') + substr_count($content, '{') + substr_count($content, '(');
            $closeBrackets = substr_count($content, '>') + substr_count($content, '}') + substr_count($content, ')');

            if ($openBrackets > $closeBrackets) {
                // Multi-line type annotation - collect across lines
                $multiLineResult = $this->collectMultiLineType($phpcsFile, $i, $docBlockEndIndex);
                if ($multiLineResult !== null) {
                    $docBlockParams[] = [
                        'index' => $classNameIndex,
                        'type' => $multiLineResult['type'],
                        'variable' => $multiLineResult['variable'],
                        'appendix' => ' ' . $multiLineResult['variable'] . ($multiLineResult['description'] ? ' ' . $multiLineResult['description'] : ''),
                    ];
                    // Skip to the end of the multi-line annotation
                    $i = $multiLineResult['endIndex'];

                    continue;
                }
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
            if (!$hasMissingTypes && count($docBlockParams) < count($methodSignature) && $this->canAddMissingParams($phpcsFile, $docBlockStartIndex, $docBlockEndIndex, $docBlockParams, $methodSignature)) {
                return;
            }

            // Check if we have extra params that can be removed
            if (count($docBlockParams) > count($methodSignature)) {
                $this->handleExtraParams($phpcsFile, $docBlockStartIndex, $docBlockEndIndex, $docBlockParams, $methodSignature);

                return;
            }

            $phpcsFile->addError('Doc Block params do not match method signature', $stackPointer, 'SignatureMismatch');

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
            $phpcsFile->addError($error, $docBlockParam['index'], 'VariableWrong');
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return void
     */
    protected function assertNoParams(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if ($tokens[$i]['content'] !== '@param') {
                continue;
            }

            $fix = $phpcsFile->addFixableError('Doc Block param does not match method signature and should be removed', $i, 'ExtraParam');

            if ($fix === true) {
                $this->removeParamLine($phpcsFile, $i);
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
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @param array<array<string, mixed>> $docBlockParams
     * @param array<int, array<string, mixed>> $methodSignature
     *
     * @return bool
     */
    protected function canAddMissingParams(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex, array $docBlockParams, array $methodSignature): bool
    {
        $tokens = $phpcsFile->getTokens();

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

        $fix = $phpcsFile->addFixableError('Doc Block params do not match method signature', $docBlockStartIndex + 1, 'SignatureMismatch');

        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();

            // Build list of existing param variables
            $existingVars = [];
            foreach ($docBlockParams as $param) {
                $existingVars[] = $param['variable'];
            }

            // Add missing params
            foreach ($methodSignature as $methodParam) {
                $variable = $tokens[$methodParam['variableIndex']]['content'];
                if (!in_array($variable, $existingVars, true)) {
                    $indent = $this->getIndentForParam($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);
                    $paramLine = "\n" . $indent . '* @param ' . $methodParam['typehintFull'] . ' ' . $variable;

                    $phpcsFile->fixer->addContentBefore($insertPosition, $paramLine);
                }
            }

            $phpcsFile->fixer->endChangeset();
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $paramTagIndex
     *
     * @return void
     */
    protected function removeParamLine(File $phpcsFile, int $paramTagIndex): void
    {
        $tokens = $phpcsFile->getTokens();

        $phpcsFile->fixer->beginChangeset();

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
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     *
     * @return string
     */
    protected function getIndentForParam(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex): string
    {
        $tokens = $phpcsFile->getTokens();

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
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @param array<array<string, mixed>> $docBlockParams
     * @param array<int, array<string, mixed>> $methodSignature
     *
     * @return void
     */
    protected function handleExtraParams(File $phpcsFile, int $docBlockStartIndex, int $docBlockEndIndex, array $docBlockParams, array $methodSignature): void
    {
        $tokens = $phpcsFile->getTokens();

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
                $fix = $phpcsFile->addFixableError('Doc Block param does not match method signature and should be removed', $i, 'ExtraParam');

                if ($fix === true) {
                    $hasFixableError = true;
                    $this->removeParamLine($phpcsFile, $i);
                }
            }
        }

        if (!$hasFixableError) {
            $phpcsFile->addError('Doc Block params do not match method signature', $docBlockStartIndex + 1, 'SignatureMismatch');
        }
    }
}
