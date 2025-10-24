<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\WhiteSpace;

use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * Detects orphaned indentation - lines that are over-indented without a scope change.
 * This catches cases where code has extra indentation (e.g., leftover from a deleted block).
 *
 * @author Mark Scherer
 * @license MIT
 */
class ConsistentIndentSniff extends AbstractSniff
{
    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [T_WHITESPACE];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Only check whitespace at the start of lines (indentation)
        if ($stackPtr > 0 && $tokens[$stackPtr - 1]['line'] === $tokens[$stackPtr]['line']) {
            return;
        }

        $line = $tokens[$stackPtr]['line'];

        // Skip first line and lines in docblocks
        if ($line === 1 || !empty($tokens[$stackPtr]['nested_attributes'])) {
            return;
        }

        // Get the current indentation level
        $currentIndent = $this->getIndentLevel($phpcsFile, $tokens[$stackPtr]);
        if ($currentIndent === 0) {
            return;
        }

        // Find the next non-whitespace token on this line
        $nextToken = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        if ($nextToken === false || $tokens[$nextToken]['line'] !== $line) {
            // Empty line or no content
            return;
        }

        // Skip closing braces - they're allowed to be dedented
        if ($tokens[$nextToken]['code'] === T_CLOSE_CURLY_BRACKET) {
            return;
        }

        // Get the expected indentation based on scope
        $expectedIndent = $this->getExpectedIndent($tokens[$nextToken]);

        // Skip anything that could be intentional (most things)
        if ($this->isInsideClosure($phpcsFile, $nextToken, $tokens)) {
            return;
        }
        if ($this->isInsideArray($phpcsFile, $nextToken, $tokens)) {
            return;
        }
        if ($this->isInsideSwitchCase($phpcsFile, $nextToken, $tokens)) {
            return;
        }
        if ($tokens[$nextToken]['code'] === T_COMMENT || $tokens[$nextToken]['code'] === T_DOC_COMMENT_OPEN_TAG) {
            return;
        }
        if ($this->startsWithContinuationOperator($nextToken, $tokens)) {
            return;
        }

        // Find previous content line
        $prevLine = $this->findPreviousContentLine($phpcsFile, $stackPtr, $tokens);
        if ($prevLine === null) {
            return;
        }

        // Only flag if:
        // 1. Previous line is a closing brace or semicolon (not in middle of multi-line construct)
        // 2. Current line is over-indented by MORE than expected
        // 3. Previous line doesn't indicate continuation
        if (!$this->isPreviousLineComplete($prevLine, $tokens)) {
            return; // Previous line suggests continuation
        }

        if ($this->isValidContinuation($prevLine, $tokens)) {
            return; // Previous line ends with continuation token
        }

        // Skip if we're in a multi-line condition or complex structure
        if ($this->isInMultiLineConstruct($phpcsFile, $nextToken, $tokens)) {
            return;
        }

        // Additional safety: if this looks like it could be in a case block, skip it
        // Case blocks don't show up in conditions, so expected might be wrong
        if ($this->couldBeInCaseBlock($phpcsFile, $stackPtr, $tokens)) {
            return;
        }

        // Skip return/break/continue after closing brace ONLY if inside a switch (case blocks have special rules)
        if (in_array($tokens[$nextToken]['code'], [T_RETURN, T_BREAK, T_CONTINUE], true)) {
            if ($tokens[$prevLine]['code'] === T_CLOSE_CURLY_BRACKET) {
                // Check if we're actually inside a switch statement
                $conditions = $tokens[$nextToken]['conditions'] ?? [];
                foreach ($conditions as $condPtr => $condCode) {
                    if ($condCode === T_SWITCH) {
                        return; // Inside switch - too risky to flag
                    }
                }
            }
        }

        // Only flag if significantly over-indented (more than expected)
        if ($currentIndent > $expectedIndent) {
            // Detect indentation type
            $indentChar = $this->getIndentationCharacter($tokens[$stackPtr]['content']);
            $isTab = ($indentChar === "\t");

            if ($isTab) {
                $error = 'Line indented incorrectly; expected %d tabs, found %d tabs';
                $data = [$expectedIndent, $currentIndent];
            } else {
                $error = 'Line indented incorrectly; expected %d spaces, found %d spaces';
                $data = [$expectedIndent * 4, $currentIndent * 4];
            }

            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'Incorrect', $data);

            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                if ($isTab) {
                    $phpcsFile->fixer->replaceToken($stackPtr, str_repeat("\t", $expectedIndent));
                } else {
                    $phpcsFile->fixer->replaceToken($stackPtr, str_repeat('    ', $expectedIndent));
                }
                $phpcsFile->fixer->endChangeset();
            }
        }
    }

    /**
     * Get the indentation level (number of indent units) for a whitespace token.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param array<string, mixed> $token
     *
     * @return int
     */
    protected function getIndentLevel(File $phpcsFile, array $token): int
    {
        $content = $token['orig_content'] ?? $token['content'];

        // Check if using tabs (for mixed indentation)
        if (str_contains($content, "\t")) {
            return substr_count($content, "\t");
        }

        // Using spaces (4 spaces per indent level)
        return (int)(strlen($content) / 4);
    }

    /**
     * Get the expected indentation level based on scope.
     *
     * @param array<string, mixed> $token
     *
     * @return int
     */
    protected function getExpectedIndent(array $token): int
    {
        $conditions = $token['conditions'];

        return count($conditions);
    }

    /**
     * Find the previous line that has actual content (not blank, not comment-only).
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return int|null
     */
    protected function findPreviousContentLine(File $phpcsFile, int $stackPtr, array $tokens): ?int
    {
        $currentLine = $tokens[$stackPtr]['line'];

        for ($i = $stackPtr - 1; $i >= 0; $i--) {
            if ($tokens[$i]['line'] >= $currentLine) {
                continue;
            }

            // Skip whitespace and comments
            if ($tokens[$i]['code'] === T_WHITESPACE || $tokens[$i]['code'] === T_COMMENT) {
                continue;
            }

            return $i;
        }

        return null;
    }

    /**
     * Check if this line starts with a continuation operator.
     *
     * @param int $nextToken First non-whitespace token on the line
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool
     */
    protected function startsWithContinuationOperator(int $nextToken, array $tokens): bool
    {
        $continuationStarters = [
            T_STRING_CONCAT,
            T_OBJECT_OPERATOR,
            T_NULLSAFE_OBJECT_OPERATOR,
            T_BOOLEAN_AND,
            T_BOOLEAN_OR,
            T_LOGICAL_AND,
            T_LOGICAL_OR,
            T_PLUS,
            T_MINUS,
            T_MULTIPLY,
            T_DIVIDE,
            T_INLINE_THEN,
            T_INLINE_ELSE,
            T_COALESCE,
        ];

        return in_array($tokens[$nextToken]['code'], $continuationStarters, true);
    }

    /**
     * Check if this looks like a valid continuation line (allowed to have extra indentation).
     *
     * @param int $prevToken Previous content token
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool True if this is a valid continuation, false if it should match scope indent
     */
    protected function isValidContinuation(int $prevToken, array $tokens): bool
    {
        $prevCode = $tokens[$prevToken]['code'];

        // Tokens that indicate the next line is a continuation
        $continuationTokens = [
            T_PLUS,
            T_MINUS,
            T_MULTIPLY,
            T_DIVIDE,
            T_MODULUS,
            T_STRING_CONCAT,
            T_COMMA,
            T_OPEN_PARENTHESIS,
            T_OPEN_SQUARE_BRACKET,
            T_OPEN_SHORT_ARRAY,
            T_OPEN_CURLY_BRACKET,
            T_DOUBLE_ARROW,
            T_BOOLEAN_AND,
            T_BOOLEAN_OR,
            T_LOGICAL_AND,
            T_LOGICAL_OR,
            T_INSTANCEOF,
            T_INLINE_THEN,
            T_INLINE_ELSE,
            T_COALESCE,
            T_OBJECT_OPERATOR,
            T_NULLSAFE_OBJECT_OPERATOR,
            T_EQUAL,
            T_PLUS_EQUAL,
            T_MINUS_EQUAL,
            T_MUL_EQUAL,
            T_DIV_EQUAL,
            T_CONCAT_EQUAL,
            T_MOD_EQUAL,
        ];

        if (in_array($prevCode, $continuationTokens, true)) {
            return true;
        }

        // Check string representation for bracket tokens (PHPCS sometimes uses string codes)
        $prevContent = $tokens[$prevToken]['content'] ?? '';
        if ($prevContent === '[' || $prevContent === '(' || $prevContent === '{') {
            return true;
        }

        return false;
    }

    /**
     * Check if the current position is inside a closure/anonymous function.
     * PHPCS doesn't properly track closures in the conditions array, so we need to detect them manually.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool
     */
    protected function isInsideClosure(File $phpcsFile, int $stackPtr, array $tokens): bool
    {
        // Look backward for a closure token (T_CLOSURE, T_FN, or T_FUNCTION for anonymous functions)
        $closureTypes = [T_CLOSURE, T_FN];

        // Search backward for a closure opening
        for ($i = $stackPtr - 1; $i >= 0; $i--) {
            // Check for T_FUNCTION (could be a named method or anonymous function)
            if ($tokens[$i]['code'] === T_FUNCTION) {
                // Check if this is a named function (not a closure)
                $nextNonWhitespace = $phpcsFile->findNext(T_WHITESPACE, $i + 1, null, true);
                if ($nextNonWhitespace !== false && $tokens[$nextNonWhitespace]['code'] === T_STRING) {
                    // Named function/method - stop searching, we're not in a closure
                    return false;
                }

                // It's an anonymous function (closure) - check if we're inside it
                if (isset($tokens[$i]['scope_opener']) && isset($tokens[$i]['scope_closer'])) {
                    if ($stackPtr > $tokens[$i]['scope_opener'] && $stackPtr < $tokens[$i]['scope_closer']) {
                        return true;
                    }
                }
            }

            if (in_array($tokens[$i]['code'], $closureTypes, true)) {
                // Found a closure, check if our position is within its scope
                if (isset($tokens[$i]['scope_opener']) && isset($tokens[$i]['scope_closer'])) {
                    if ($stackPtr > $tokens[$i]['scope_opener'] && $stackPtr < $tokens[$i]['scope_closer']) {
                        return true;
                    }
                }
            }

            // Stop searching if we've gone too far (more than 2000 tokens back)
            // Large closures with validation logic can easily exceed 500 tokens
            if ($stackPtr - $i > 2000) {
                break;
            }
        }

        return false;
    }

    /**
     * Check if the current position is inside an array where indentation tracking may be unreliable.
     * This includes multi-dimensional arrays and arrays with closures as values.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool
     */
    protected function isInsideArray(File $phpcsFile, int $stackPtr, array $tokens): bool
    {
        // Check if we're inside nested parenthesis/brackets
        if (isset($tokens[$stackPtr]['nested_parenthesis']) && count($tokens[$stackPtr]['nested_parenthesis']) > 0) {
            // Look for array-like constructs (short array syntax, function calls with array args)
            foreach ($tokens[$stackPtr]['nested_parenthesis'] as $opener => $closer) {
                if ($tokens[$opener]['code'] === T_OPEN_SHORT_ARRAY) {
                    // Check if this array contains closures or is multi-dimensional
                    for ($i = $opener + 1; $i < $closer; $i++) {
                        if ($tokens[$i]['code'] === T_CLOSURE || $tokens[$i]['code'] === T_FN) {
                            return true; // Array with closures
                        }
                        if ($tokens[$i]['code'] === T_OPEN_SHORT_ARRAY) {
                            return true; // Multi-dimensional array
                        }
                    }
                }
            }
        }

        // Check for old array() syntax
        $prevContent = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if ($prevContent !== false) {
            // Look backward for array constructs
            for ($i = $prevContent; $i >= max(0, $stackPtr - 100); $i--) {
                if ($tokens[$i]['code'] === T_ARRAY) {
                    if (isset($tokens[$i]['parenthesis_opener']) && isset($tokens[$i]['parenthesis_closer'])) {
                        if ($stackPtr > $tokens[$i]['parenthesis_opener'] && $stackPtr < $tokens[$i]['parenthesis_closer']) {
                            // Inside array(), check if it contains closures
                            for ($j = $tokens[$i]['parenthesis_opener'] + 1; $j < $tokens[$i]['parenthesis_closer']; $j++) {
                                if ($tokens[$j]['code'] === T_CLOSURE || $tokens[$j]['code'] === T_FN) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if the current position is inside a switch/case block.
     * Case blocks have special indentation rules per PER Coding Style.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool
     */
    protected function isInsideSwitchCase(File $phpcsFile, int $stackPtr, array $tokens): bool
    {
        // Look backward for a switch statement
        for ($i = $stackPtr - 1; $i >= 0; $i--) {
            if ($tokens[$i]['code'] === T_SWITCH) {
                // Found a switch, check if we're within its scope
                if (isset($tokens[$i]['scope_opener']) && isset($tokens[$i]['scope_closer'])) {
                    if ($stackPtr > $tokens[$i]['scope_opener'] && $stackPtr < $tokens[$i]['scope_closer']) {
                        return true;
                    }
                }
            }

            // Stop if we've gone too far back
            if ($stackPtr - $i > 500) {
                break;
            }
        }

        return false;
    }

    /**
     * Check if the previous line is "complete" (ends with statement terminator or closing brace).
     * If not, the next line might be a continuation.
     *
     * @param int $prevToken
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool
     */
    protected function isPreviousLineComplete(int $prevToken, array $tokens): bool
    {
        $prevCode = $tokens[$prevToken]['code'];

        // Line is complete if it ends with:
        $completeTokens = [
            T_SEMICOLON,
            T_CLOSE_CURLY_BRACKET,
            T_CLOSE_PARENTHESIS, // Could be end of condition
            T_COLON, // Could be end of case label
        ];

        return in_array($prevCode, $completeTokens, true);
    }

    /**
     * Check if we're in a multi-line construct (condition, array, etc.).
     * Look for unmatched opening parentheses/brackets.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool
     */
    protected function isInMultiLineConstruct(File $phpcsFile, int $stackPtr, array $tokens): bool
    {
        // Check if we have unmatched parentheses on previous lines
        $currentLine = $tokens[$stackPtr]['line'];
        $openParens = 0;
        $openBrackets = 0;

        // Look backward on the same statement to check for unmatched parens/brackets
        for ($i = $stackPtr - 1; $i >= 0; $i--) {
            // Stop at statement boundaries
            if ($tokens[$i]['code'] === T_SEMICOLON || $tokens[$i]['code'] === T_OPEN_CURLY_BRACKET) {
                break;
            }

            // Count parentheses and brackets
            if ($tokens[$i]['code'] === T_OPEN_PARENTHESIS) {
                $openParens++;
            } elseif ($tokens[$i]['code'] === T_CLOSE_PARENTHESIS) {
                $openParens--;
            } elseif ($tokens[$i]['code'] === T_OPEN_SQUARE_BRACKET || $tokens[$i]['code'] === T_OPEN_SHORT_ARRAY) {
                $openBrackets++;
            } elseif ($tokens[$i]['code'] === T_CLOSE_SQUARE_BRACKET) {
                $openBrackets--;
            }

            // Stop searching after reasonable distance
            if ($stackPtr - $i > 200) {
                break;
            }
        }

        // If we have unmatched opening parens/brackets, we're in a multi-line construct
        return $openParens > 0 || $openBrackets > 0;
    }

    /**
     * Check if this could be in a case block by looking for case/default keywords nearby.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool
     */
    protected function couldBeInCaseBlock(File $phpcsFile, int $stackPtr, array $tokens): bool
    {
        // Look backward for case/default keywords (not too far)
        for ($i = $stackPtr - 1; $i >= max(0, $stackPtr - 100); $i--) {
            if ($tokens[$i]['code'] === T_CASE || $tokens[$i]['code'] === T_DEFAULT) {
                // Found a case/default, likely in a case block
                return true;
            }
            // If we hit another control structure, stop
            if ($tokens[$i]['code'] === T_IF || $tokens[$i]['code'] === T_WHILE || $tokens[$i]['code'] === T_FOR) {
                break;
            }
        }

        return false;
    }
}
