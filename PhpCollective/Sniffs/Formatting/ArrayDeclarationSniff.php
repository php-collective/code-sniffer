<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Formatting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * @category PHP
 * @package PHP_CodeSniffer
 * @author Greg Sherwood <gsherwood@squiz.net>
 * @author Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link http://pear.php.net/package/PHP_CodeSniffer
 *
 * @modified by Mark Scherer with some minor fixes and removal of error-prone parts
 */
class ArrayDeclarationSniff implements Sniff
{
    /**
     * Controls when multi-line indentation rules are applied.
     *
     * Options:
     * - 'assoc' (default): Only enforce one item per line for associative arrays
     * - 'all': Enforce one item per line for all arrays (both associative and indexed)
     *
     * @var string
     */
    public string $multiLineIndentationMode = 'assoc';

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_OPEN_SHORT_ARRAY,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        $arrayStart = $stackPtr;
        $arrayEnd = $tokens[$stackPtr]['bracket_closer'];

        // Check for empty arrays.
        $content = $phpcsFile->findNext(T_WHITESPACE, ($arrayStart + 1), ($arrayEnd + 1), true);
        if ($content === $arrayEnd) {
            return;
        }

        if ($tokens[$arrayStart]['line'] === $tokens[$arrayEnd]['line']) {
            $this->processSingleLineArray($phpcsFile, $arrayStart, $arrayEnd);
        } else {
            $this->processMultiLineArray($phpcsFile, $stackPtr, $arrayStart, $arrayEnd);
            $this->processMultiLineIndentation($phpcsFile, $arrayStart, $arrayEnd);
        }
    }

    /**
     * Processes a single-line array definition.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being checked.
     * @param int $arrayStart The token that starts the array definition.
     * @param int $arrayEnd The token that ends the array definition.
     *
     * @return void
     */
    public function processSingleLineArray(File $phpcsFile, int $arrayStart, int $arrayEnd): void
    {
        $tokens = $phpcsFile->getTokens();

        // Check if there are multiple values. If so, then it has to be multiple lines
        // unless it is contained inside a function call or condition.
        $commas = [];
        for ($i = ($arrayStart + 1); $i < $arrayEnd; $i++) {
            // Skip bracketed statements, like function calls.
            if ($tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                $i = $tokens[$i]['parenthesis_closer'];

                continue;
            }

            if ($tokens[$i]['code'] === T_COMMA) {
                // Before counting this comma, make sure we are not
                // at the end of the array.
                $next = $phpcsFile->findNext(T_WHITESPACE, ($i + 1), $arrayEnd, true);
                if ($next !== false) {
                    $commas[] = $i;
                } else {
                    // There is a comma at the end of a single line array.
                    $error = 'Comma not allowed after last value in single-line array declaration';
                    $fix = $phpcsFile->addFixableError($error, $i, 'CommaAfterLast');
                    if ($fix === true) {
                        $phpcsFile->fixer->beginChangeset();

                        for ($j = $i; $j < $arrayEnd; $j++) {
                            $phpcsFile->fixer->replaceToken($j, '');
                        }

                        $phpcsFile->fixer->endChangeset();
                    }
                }
            }
        }
    }

    /**
     * Processes a multi-line array definition.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile The current file being checked.
     * @param int $stackPtr The position of the current token in the stack passed in $tokens.
     * @param int $arrayStart The token that starts the array definition.
     * @param int $arrayEnd The token that ends the array definition.
     *
     * @return void
     */
    public function processMultiLineArray(File $phpcsFile, int $stackPtr, int $arrayStart, int $arrayEnd): void
    {
        $tokens = $phpcsFile->getTokens();

        $keyUsed = false;
        $indices = [];
        $maxLength = 0;

        if ($tokens[$stackPtr]['code'] === T_ARRAY) {
            $lastToken = $tokens[$stackPtr]['parenthesis_opener'];
        } else {
            $lastToken = $stackPtr;
        }

        // Find all the double arrows that reside in this scope.
        for ($nextToken = ($stackPtr + 1); $nextToken < $arrayEnd; $nextToken++) {
            // Skip bracketed statements, like function calls.
            if (
                $tokens[$nextToken]['code'] === T_OPEN_PARENTHESIS
                && (isset($tokens[$nextToken]['parenthesis_owner']) === false
                    || $tokens[$nextToken]['parenthesis_owner'] !== $stackPtr)
            ) {
                $nextToken = $tokens[$nextToken]['parenthesis_closer'];

                continue;
            }

            if ($tokens[$nextToken]['code'] === T_ARRAY) {
                // Let subsequent calls of this test handle nested arrays.
                if ($tokens[$lastToken]['code'] !== T_DOUBLE_ARROW) {
                    $indices[] = ['value' => $nextToken];
                    $lastToken = $nextToken;
                }

                $parenthesisCloseIndex = $tokens[$tokens[$nextToken]['parenthesis_opener']]['parenthesis_closer'];
                $nextTokenIndex = $phpcsFile->findNext(T_WHITESPACE, ($parenthesisCloseIndex + 1), null, true);
                if (!$nextTokenIndex) {
                    break;
                }

                if ($tokens[$nextTokenIndex]['code'] !== T_COMMA) {
                    $nextToken = $nextTokenIndex - 1;
                } else {
                    $lastToken = $nextTokenIndex;
                }

                continue;
            }

            if ($tokens[$nextToken]['code'] === T_OPEN_SHORT_ARRAY) {
                // Let subsequent calls of this test handle nested arrays.
                if ($tokens[$lastToken]['code'] !== T_DOUBLE_ARROW) {
                    $indices[] = ['value' => $nextToken];
                    $lastToken = $nextToken;
                }

                $bracketCloseIndex = $tokens[$nextToken]['bracket_closer'];
                $nextTokenIndex = $phpcsFile->findNext(T_WHITESPACE, ($bracketCloseIndex + 1), null, true);
                if (!$nextTokenIndex) {
                    break;
                }

                if ($tokens[$nextTokenIndex]['code'] !== T_COMMA) {
                    $nextToken = $nextTokenIndex - 1;
                } else {
                    $lastToken = $nextTokenIndex;
                }

                continue;
            }

            if ($tokens[$nextToken]['code'] === T_CLOSURE) {
                if ($tokens[$lastToken]['code'] !== T_DOUBLE_ARROW) {
                    $indices[] = ['value' => $nextToken];
                    $lastToken = $nextToken;
                }

                $nextToken = $tokens[$nextToken]['scope_closer'];
                $nextTokenIndex = $phpcsFile->findNext(T_WHITESPACE, ($nextToken + 1), null, true);
                if (!$nextTokenIndex) {
                    break;
                }

                if ($tokens[$nextTokenIndex]['code'] !== T_COMMA) {
                    $nextToken = $nextTokenIndex - 1;
                } else {
                    $lastToken = $nextTokenIndex;
                }

                continue;
            }

            if (
                $tokens[$nextToken]['code'] !== T_DOUBLE_ARROW
                && $tokens[$nextToken]['code'] !== T_COMMA
            ) {
                continue;
            }

            $currentEntry = [];

            if ($tokens[$nextToken]['code'] === T_COMMA) {
                $stackPtrCount = 0;
                if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
                    $stackPtrCount = count($tokens[$stackPtr]['nested_parenthesis']);
                }

                $commaCount = 0;
                if (isset($tokens[$nextToken]['nested_parenthesis']) === true) {
                    $commaCount = count($tokens[$nextToken]['nested_parenthesis']);
                    if ($tokens[$stackPtr]['code'] === T_ARRAY) {
                        // Remove parenthesis that are used to define the array.
                        $commaCount--;
                    }
                }

                if ($commaCount > $stackPtrCount) {
                    // This comma is inside more parenthesis than the ARRAY keyword,
                    // then there it is actually a comma used to separate arguments
                    // in a function call.
                    continue;
                }

                if ($keyUsed === true && $tokens[$lastToken]['code'] === T_COMMA) {
                    return;
                }

                if ($keyUsed === false) {
                    $valueContent = $phpcsFile->findNext(
                        Tokens::$emptyTokens,
                        ($lastToken + 1),
                        $nextToken,
                        true,
                    );

                    $indices[] = ['value' => $valueContent];
                }

                $lastToken = $nextToken;

                continue;
            }

            if ($tokens[$nextToken]['code'] === T_DOUBLE_ARROW) {
                $currentEntry['arrow'] = $nextToken;
                $keyUsed = true;

                // Find the start of index that uses this double arrow.
                $indexEnd = (int)$phpcsFile->findPrevious(T_WHITESPACE, ($nextToken - 1), $arrayStart, true);
                $indexStart = $phpcsFile->findStartOfStatement($indexEnd);

                if ($indexStart === $indexEnd) {
                    $currentEntry['index'] = $indexEnd;
                    $currentEntry['index_content'] = $tokens[$indexEnd]['content'];
                } else {
                    $currentEntry['index'] = $indexStart;
                    $currentEntry['index_content'] = $phpcsFile->getTokensAsString($indexStart, ($indexEnd - $indexStart + 1));
                }

                $indexLength = strlen($currentEntry['index_content']);
                if ($maxLength < $indexLength) {
                    $maxLength = $indexLength;
                }

                // Find the value of this index.
                $nextContent = $phpcsFile->findNext(
                    Tokens::$emptyTokens,
                    ($nextToken + 1),
                    $arrayEnd,
                    true,
                );

                $currentEntry['value'] = $nextContent;
                $indices[] = $currentEntry;
                $lastToken = $nextToken;
            }
        }

        $numValues = count($indices);

        foreach ($indices as $index) {
            if (isset($index['index']) === false) {
                // Array value only.
                if ($tokens[$index['value']]['line'] === $tokens[$stackPtr]['line'] && $numValues > 1) {
                    $error = 'The first value in a multi-value array must be on a new line';
                    //FIXME indentation
                    $phpcsFile->addError($error, $stackPtr, 'FirstValueNoNewline');

                    continue;

                    /*
                    $fix = $phpcsFile->addFixableError($error, $stackPtr, 'FirstValueNoNewline');
                    if ($fix === true) {
                        $phpcsFile->fixer->addNewlineBefore($index['value']);

                        // We might also have to fix indentation here
                    }
                    */
                }

                continue;
            }

            $indexLine = $tokens[$index['index']]['line'];

            if ($indexLine === $tokens[$stackPtr]['line']) {
                $error = 'The first index in a multi-value array must be on a new line';
                $fix = $phpcsFile->addFixableError($error, $index['index'], 'FirstIndexNoNewline');
                if ($fix === true) {
                    $phpcsFile->fixer->addNewlineBefore($index['index']);
                }
            }
        }
    }

    protected function processMultiLineIndentation(File $phpcsFile, int $arrayStart, int $arrayEnd): void
    {
        $tokens = $phpcsFile->getTokens();
        $pairs = [];

        $i = $arrayStart + 1;
        while ($i < $arrayEnd) {
            $token = $tokens[$i];

            if (in_array($token['code'], Tokens::$emptyTokens, true)) {
                $i++;

                continue;
            }

            // Skip over nested structures (function calls, arrays, etc.)
            if ($token['code'] === T_OPEN_PARENTHESIS && isset($token['parenthesis_closer'])) {
                $i = $token['parenthesis_closer'] + 1;

                continue;
            }
            if ($token['code'] === T_OPEN_SHORT_ARRAY && isset($token['bracket_closer'])) {
                $i = $token['bracket_closer'] + 1;

                continue;
            }
            if ($token['code'] === T_ARRAY && isset($token['parenthesis_closer'])) {
                $i = $token['parenthesis_closer'] + 1;

                continue;
            }

            // Handle key => value
            if ($token['code'] === T_DOUBLE_ARROW) {
                $keyEnd = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, $arrayStart, true);
                if ($keyEnd === false) {
                    break;
                }
                $keyStart = $phpcsFile->findStartOfStatement($keyEnd);

                $valueStart = $phpcsFile->findNext(Tokens::$emptyTokens, $i + 1, $arrayEnd, true);
                if ($valueStart === false) {
                    break;
                }

                // Find the end of the value expression (handles function calls, etc.)
                $valueEnd = $valueStart;
                $depth = 0;

                for ($j = $valueStart; $j < $arrayEnd; $j++) {
                    $currentToken = $tokens[$j];

                    // Handle string literals
                    if (in_array($currentToken['code'], [T_CONSTANT_ENCAPSED_STRING, T_DOUBLE_QUOTED_STRING], true)) {
                        $valueEnd = $j;

                        continue;
                    }

                    // Track parentheses depth
                    if ($currentToken['code'] === T_OPEN_PARENTHESIS) {
                        $depth++;
                    } elseif ($currentToken['code'] === T_CLOSE_PARENTHESIS) {
                        $depth--;
                    }

                    // Stop at comma when we're at depth 0 (not inside function call)
                    if ($currentToken['code'] === T_COMMA && $depth === 0) {
                        break;
                    }

                    // Skip whitespace and comments when determining end
                    if (!in_array($currentToken['code'], Tokens::$emptyTokens, true)) {
                        $valueEnd = $j;
                    }
                }

                $pairs[] = [
                    'key' => $keyStart,
                    'arrow' => $i,
                    'value' => $valueStart,
                    'value_end' => $valueEnd,
                    'line' => $tokens[$keyStart]['line'],
                    'is_associative' => true,
                ];

                $i = $phpcsFile->findNext([T_COMMA], $valueEnd + 1, $arrayEnd);
                if ($i === false) {
                    break;
                }

                $i++;

                continue;
            }

            // Handle single value (non-associative)
            if ($token['code'] !== T_COMMA) {
                // Find the end of this value expression (handles function calls, etc.)
                $valueEnd = $i;
                $depth = 0;

                for ($j = $i; $j < $arrayEnd; $j++) {
                    $currentToken = $tokens[$j];

                    // Handle string literals
                    if (in_array($currentToken['code'], [T_CONSTANT_ENCAPSED_STRING, T_DOUBLE_QUOTED_STRING], true)) {
                        $valueEnd = $j;

                        continue;
                    }

                    // Track parentheses depth
                    if ($currentToken['code'] === T_OPEN_PARENTHESIS) {
                        $depth++;
                    } elseif ($currentToken['code'] === T_CLOSE_PARENTHESIS) {
                        $depth--;
                    }

                    // Stop at comma when we're at depth 0 (not inside function call)
                    if ($currentToken['code'] === T_COMMA && $depth === 0) {
                        break;
                    }

                    // Skip whitespace and comments when determining end
                    if (!in_array($currentToken['code'], Tokens::$emptyTokens, true)) {
                        $valueEnd = $j;
                    }
                }

                $pairs[] = [
                    'key' => null,
                    'arrow' => null,
                    'value' => $i,
                    'value_end' => $valueEnd,
                    'line' => $tokens[$i]['line'],
                    'is_associative' => false,
                ];

                $i = $phpcsFile->findNext([T_COMMA], $valueEnd + 1, $arrayEnd);
                if ($i === false) {
                    break;
                }
                $i++;
            } else {
                $i++;
            }
        }

        // Group by line, but only if fully single-line expressions
        $lineCounts = [];
        foreach ($pairs as $pair) {
            $startLine = $tokens[$pair['key'] ?? $pair['value']]['line'];
            $endLine = $tokens[$pair['value_end'] ?: $pair['value']]['line'];

            if ($startLine === $endLine) {
                $lineCounts[$startLine][] = $pair;
            }
        }

        foreach ($lineCounts as $line => $items) {
            if (count($items) < 2) {
                continue;
            }

            // Check if we should process these items based on configuration
            $shouldProcess = false;
            if ($this->multiLineIndentationMode === 'all') {
                $shouldProcess = true;
            } else {
                // In 'assoc' mode, only process if at least one item on this line is associative
                foreach ($items as $item) {
                    if ($item['is_associative']) {
                        $shouldProcess = true;

                        break;
                    }
                }
            }

            if (!$shouldProcess) {
                continue;
            }

            foreach ($items as $i => $pair) {
                // In 'assoc' mode, only flag associative items
                if ($this->multiLineIndentationMode === 'assoc' && !$pair['is_associative']) {
                    continue;
                }

                $ptr = $pair['key'] ?? $pair['value'];
                $error = 'Each array item must be on its own line in a multi-line array';
                $fix = $phpcsFile->addFixableError($error, $ptr, 'MultipleItemsPerLine');

                if ($fix) {
                    // Calculate proper indentation by looking at existing properly indented items in this array
                    $baseIndent = '';

                    // Look for the first properly indented item in this array to match its indentation
                    for ($searchIdx = $arrayStart + 1; $searchIdx < $arrayEnd; $searchIdx++) {
                        if (
                            $tokens[$searchIdx]['line'] > $tokens[$arrayStart]['line'] &&
                            !in_array($tokens[$searchIdx]['code'], Tokens::$emptyTokens, true)
                        ) {
                            // Extract actual indentation from the line (only leading whitespace)
                            $lineStart = $phpcsFile->findFirstOnLine([], $searchIdx);
                            if ($lineStart !== false && $lineStart < $searchIdx) {
                                $indentContent = $phpcsFile->getTokensAsString($lineStart, $searchIdx - $lineStart);
                                // Only keep leading whitespace (tabs and spaces), remove any other characters
                                if (preg_match('/^[\t ]*/', $indentContent, $matches)) {
                                    $baseIndent = $matches[0];
                                }
                            } else {
                                // Fallback to column-based calculation
                                $indentLevel = $tokens[$searchIdx]['column'] - 1;
                                $baseIndent = str_repeat(' ', $indentLevel);
                            }

                            break;
                        }
                    }

                    // Fallback: detect indentation style and calculate based on array position
                    if ($baseIndent === '') {
                        // Detect if file uses tabs or spaces by looking at existing indentation
                        $usesTabs = false;
                        $indentSize = 4; // Default to 4 spaces

                        // Scan the file to detect indentation style
                        $count = count($tokens);
                        for ($detectIdx = 0; $detectIdx < $count; $detectIdx++) {
                            if (
                                $tokens[$detectIdx]['code'] === T_WHITESPACE &&
                                $tokens[$detectIdx]['line'] !== $tokens[$detectIdx - 1]['line']
                            ) {
                                $whitespace = $tokens[$detectIdx]['content'];
                                if (str_contains($whitespace, "\t")) {
                                    $usesTabs = true;

                                    break;
                                } elseif (strlen($whitespace) > 0) {
                                    // Count spaces to determine indent size
                                    $spaceCount = strlen(str_replace(["\n", "\r"], '', $whitespace));
                                    if ($spaceCount > 0 && $spaceCount % 4 === 0) {
                                        $indentSize = 4;
                                    } elseif ($spaceCount > 0 && $spaceCount % 2 === 0) {
                                        $indentSize = 2;
                                    }
                                }
                            }
                        }

                        // Calculate indentation based on array nesting
                        $arrayColumn = $tokens[$arrayStart]['column'];
                        $indentLevel = (int)(($arrayColumn + 3) / ($usesTabs ? 1 : $indentSize));

                        if ($usesTabs) {
                            $baseIndent = str_repeat("\t", $indentLevel);
                        } else {
                            $baseIndent = str_repeat(' ', $arrayColumn + 3);
                        }
                    }

                    $phpcsFile->fixer->beginChangeset();
                    foreach ($items as $j => $p) {
                        if ($j === 0) {
                            continue;
                        }

                        // In 'assoc' mode, only fix associative items
                        if ($this->multiLineIndentationMode === 'assoc' && !$p['is_associative']) {
                            continue;
                        }

                        $targetPtr = $p['key'] ?? $p['value'];

                        // Find any whitespace before the target token and remove it
                        $prevToken = $targetPtr - 1;
                        while ($prevToken >= $arrayStart && in_array($tokens[$prevToken]['code'], [T_WHITESPACE, T_COMMA], true)) {
                            if ($tokens[$prevToken]['code'] === T_WHITESPACE) {
                                $phpcsFile->fixer->replaceToken($prevToken, '');
                            }
                            $prevToken--;
                        }

                        $phpcsFile->fixer->addContentBefore($targetPtr, "\n" . $baseIndent);
                    }
                    $phpcsFile->fixer->endChangeset();
                }

                break; // Only one error per line
            }
        }
    }
}
