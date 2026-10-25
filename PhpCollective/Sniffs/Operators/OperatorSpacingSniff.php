<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Operators;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Standards\PSR12\Sniffs\Operators\OperatorSpacingSniff as Psr12OperatorSpacingSniff;

/**
 * Operator spacing, with the `|` in a `catch` type list treated as a type separator.
 *
 * PHP tokenizes a catch type list (`catch (ValueError|TypeError $e)`) with plain `T_BITWISE_OR`
 * tokens, so the inherited sniff reads it as a bitwise operator and demands a space on both
 * sides. That is the opposite of the type separator rule enforced by
 * `Universal.Operators.TypeSeparatorSpacing`, which only listens for `T_TYPE_UNION` and never
 * sees a catch list. Downstream projects such as cakephp/cakephp had to silence
 * `PSR12.Operators.OperatorSpacing.NoSpaceBefore` and `PSR12.Operators.OperatorSpacing.NoSpaceAfter`
 * in their `phpcs.xml` to allow the no-space form, and that also silenced the real bitwise
 * operators. This sniff keeps every inherited check and treats the catch-list `|` like a union
 * type separator: no space, fixable.
 */
class OperatorSpacingSniff extends Psr12OperatorSpacingSniff
{
    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, int $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_BITWISE_OR && $this->isCatchTypeSeparator($stackPtr, $tokens) === true) {
            $this->checkTypeSeparator($phpcsFile, $stackPtr, $tokens, true);
            $this->checkTypeSeparator($phpcsFile, $stackPtr, $tokens, false);

            return;
        }

        return parent::process($phpcsFile, $stackPtr);
    }

    /**
     * Whether the `|` at `$stackPtr` is a separator in a `catch` type list.
     *
     * The tokenizer does not mark these as `T_TYPE_UNION`; they are plain `T_BITWISE_OR` inside
     * the parenthesis that the `catch` keyword owns. The innermost enclosing parenthesis is the
     * last entry of `nested_parenthesis` (keys are open-token positions, in source order). A
     * real bitwise `|` in the catch body has no parenthesis owned by `catch`, so it falls
     * through to the inherited bitwise-operator check.
     *
     * @param int                            $stackPtr Position of the `|`.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     *
     * @return bool
     */
    protected function isCatchTypeSeparator(int $stackPtr, array $tokens): bool
    {
        $nested = $tokens[$stackPtr]['nested_parenthesis'] ?? [];
        $open   = array_key_last($nested);
        if ($open === null) {
            return false;
        }

        $owner = $tokens[$open]['parenthesis_owner'] ?? null;

        return $owner !== null && $tokens[$owner]['code'] === T_CATCH;
    }

    /**
     * Reports and fixes the spacing on one side of the type separator. A union type separator
     * carries no space, so any whitespace here is the violation.
     *
     * @param \PHP_CodeSniffer\Files\File          $phpcsFile The file being scanned.
     * @param int                                  $stackPtr  Position of the `|`.
     * @param array<int, array<string, mixed>>     $tokens    The token stack.
     * @param bool                                 $before    Check the side before the operator.
     *
     * @return void
     */
    protected function checkTypeSeparator(File $phpcsFile, int $stackPtr, array $tokens, bool $before): void
    {
        $index = $before ? $stackPtr - 1 : $stackPtr + 1;
        if (!isset($tokens[$index]) || $tokens[$index]['code'] !== T_WHITESPACE) {
            return;
        }

        $side    = $before ? 'before' : 'after';
        $message = 'No space %s the `|` in a catch type list (it is a type separator); found %d';
        $code    = $before ? 'SpaceBefore' : 'SpaceAfter';
        $fix     = $phpcsFile->addFixableError($message, $stackPtr, $code, [$side, strlen($tokens[$index]['content'])]);

        if ($fix === true) {
            $phpcsFile->fixer->replaceToken($index, '');
        }
    }
}
