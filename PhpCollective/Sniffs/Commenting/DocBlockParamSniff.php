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

        // Build a map of method param variable names for lookup
        $methodParamsByName = [];
        foreach ($methodSignature as $methodParam) {
            $varName = $tokens[$methodParam['variableIndex']]['content'];
            $methodParamsByName[$varName] = $methodParam;
        }

        foreach ($docBlockParams as $docBlockParam) {
            // We let other sniffers take care of missing type for now
            if (str_contains($docBlockParam['type'], '$')) {
                continue;
            }

            $docBlockVariable = $docBlockParam['variable'];

            // Check if the doc block variable exists in the method signature
            if (isset($methodParamsByName[$docBlockVariable])) {
                // Variable name matches a method param - this is correct
                continue;
            }

            // Variable doesn't exist in method signature - report error
            $error = 'Doc Block param variable `' . $docBlockVariable . '` does not exist in method signature';
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

            // Build map of existing param variables to their docblock info
            $existingParamsByVar = [];
            foreach ($docBlockParams as $param) {
                $existingParamsByVar[$param['variable']] = $param;
            }

            // Build ordered list of what the @param section should look like
            $orderedParams = [];
            foreach ($methodSignature as $methodParam) {
                $variable = $tokens[$methodParam['variableIndex']]['content'];
                if (isset($existingParamsByVar[$variable])) {
                    // Use existing param's type (preserve user's more specific type)
                    $orderedParams[] = [
                        'type' => $existingParamsByVar[$variable]['type'],
                        'variable' => $variable,
                        'appendix' => $existingParamsByVar[$variable]['appendix'],
                        'existing' => true,
                    ];
                } else {
                    // Add new param with method signature type
                    $orderedParams[] = [
                        'type' => $methodParam['typehintFull'],
                        'variable' => $variable,
                        'appendix' => ' ' . $variable,
                        'existing' => false,
                    ];
                }
            }

            // Now we need to add the missing params in the correct positions
            // Strategy: find each existing param in the docblock and add missing ones around it

            // Build a map of existing @param tag positions by variable
            $paramTagsByVar = [];
            for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
                if ($tokens[$i]['type'] === 'T_DOC_COMMENT_TAG' && $tokens[$i]['content'] === '@param') {
                    $classNameIndex = $i + 2;
                    if (isset($tokens[$classNameIndex]) && $tokens[$classNameIndex]['type'] === 'T_DOC_COMMENT_STRING') {
                        $content = $tokens[$classNameIndex]['content'];
                        $spacePos = strpos($content, ' ');
                        if ($spacePos) {
                            $appendix = substr($content, $spacePos);
                            preg_match('/\$[^\s]+/', $appendix, $matches);
                            if ($matches) {
                                $paramTagsByVar[$matches[0]] = [
                                    'tagIndex' => $i,
                                    'stringIndex' => $classNameIndex,
                                ];
                            }
                        }
                    }
                }
            }

            $indent = $this->getIndentForParam($phpcsFile, $docBlockStartIndex, $docBlockEndIndex);

            // Process ordered params - insert missing ones at appropriate positions
            $pendingInserts = []; // Params to insert before the first existing one
            $lastExistingTagIndex = null;

            foreach ($orderedParams as $param) {
                if ($param['existing']) {
                    // This param exists - first flush any pending inserts before it
                    if ($pendingInserts !== [] && isset($paramTagsByVar[$param['variable']])) {
                        $tagInfo = $paramTagsByVar[$param['variable']];
                        // Find the whitespace/newline before this tag
                        $insertBeforeIndex = $tagInfo['tagIndex'];
                        for ($j = $tagInfo['tagIndex'] - 1; $j > $docBlockStartIndex; $j--) {
                            if ($tokens[$j]['type'] === 'T_DOC_COMMENT_WHITESPACE' && $tokens[$j]['content'] !== ' ') {
                                // This is the indentation whitespace, insert before it
                                $insertBeforeIndex = $j;

                                break;
                            }
                        }

                        // Reverse the array since addContentBefore inserts before the same position
                        // multiple times, which would reverse the order
                        foreach (array_reverse($pendingInserts) as $pendingParam) {
                            $paramLine = $indent . '* @param ' . $pendingParam['type'] . ' ' . $pendingParam['variable'] . "\n";
                            $phpcsFile->fixer->addContentBefore($insertBeforeIndex, $paramLine);
                        }
                        $pendingInserts = [];
                    }
                    $lastExistingTagIndex = $paramTagsByVar[$param['variable']]['tagIndex'] ?? null;
                } else {
                    // This param needs to be added
                    if ($lastExistingTagIndex === null) {
                        // No existing param seen yet - queue it
                        $pendingInserts[] = $param;
                    } else {
                        // Insert after the last existing param's line
                        // Find the start of the next line (the whitespace token after the newline)
                        $insertBeforeIndex = null;
                        $foundNewline = false;
                        for ($j = $lastExistingTagIndex + 1; $j < $docBlockEndIndex; $j++) {
                            if ($tokens[$j]['content'] === "\n") {
                                $foundNewline = true;

                                continue;
                            }
                            if ($foundNewline && $tokens[$j]['type'] === 'T_DOC_COMMENT_WHITESPACE') {
                                $insertBeforeIndex = $j;

                                break;
                            }
                        }

                        if ($insertBeforeIndex !== null) {
                            $paramLine = $indent . '* @param ' . $param['type'] . ' ' . $param['variable'] . "\n";
                            $phpcsFile->fixer->addContentBefore($insertBeforeIndex, $paramLine);
                        }
                        // Update lastExistingTagIndex to track where we just inserted
                        // (This is tricky - the token indices don't change, but logically we've added after)
                    }
                }
            }

            // If there are still pending inserts (all params come before existing ones, or no existing params)
            if ($pendingInserts !== []) {
                // Find the whitespace before the closing tag
                $insertBeforeIndex = null;
                for ($j = $docBlockEndIndex - 1; $j > $docBlockStartIndex; $j--) {
                    if ($tokens[$j]['type'] === 'T_DOC_COMMENT_WHITESPACE' && $tokens[$j]['content'] !== "\n" && strpos($tokens[$j]['content'], "\n") === false) {
                        $insertBeforeIndex = $j;

                        break;
                    }
                }

                if ($insertBeforeIndex !== null) {
                    // Reverse the array since addContentBefore inserts before the same position
                    // multiple times, which would reverse the order
                    foreach (array_reverse($pendingInserts) as $pendingParam) {
                        $paramLine = $indent . '* @param ' . $pendingParam['type'] . ' ' . $pendingParam['variable'] . "\n";
                        $phpcsFile->fixer->addContentBefore($insertBeforeIndex, $paramLine);
                    }
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
