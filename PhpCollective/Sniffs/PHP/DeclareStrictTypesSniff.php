<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\FixerHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;

class DeclareStrictTypesSniff implements Sniff
{
    /**
     * @var string
     */
    public const CODE_DECLARE_STRICT_TYPES_WRONG_POSITION = 'DeclareStrictTypesWrongPosition';

    /**
     * @var array<int|string>
     */
    protected const TOKEN_CODES_TO_CHECK = [
        T_OPEN_PARENTHESIS, T_STRING, T_EQUAL, T_LNUMBER, T_CLOSE_PARENTHESIS, T_SEMICOLON, T_COMMENT,
    ];

    /**
     * If declare statement should always be on first line after PHP open tag.
     *
     * @var bool
     */
    public $declareOnFirstLine = true;

    /**
     * Only in effect if $declareOnFirstLine is false, e.g. a file docblock is between.
     *
     * @var int
     */
    public $linesCountBeforeDeclare = 1;

    /**
     * @var int
     */
    public $linesCountAfterDeclare = 1;

    /**
     * @var int
     */
    public $spacesCountAroundEqualsSign = 0;

    /**
     * @return array<int, (int|string)>
     */
    public function register(): array
    {
        return [
            T_DECLARE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $this->onBeforeProcess();

        $openTagPointer = TokenHelper::findPrevious($phpcsFile, T_OPEN_TAG, $stackPtr - 1);
        if ($openTagPointer === null) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        // Don't do any changes in scripts file
        if ($this->isScript($tokens)) {
            return;
        }

        if ($this->declareOnFirstLine && $this->hasNonEmptyTokensInBetween($tokens, $stackPtr, $openTagPointer)) {
            $phpcsFile->addError('declare() statement should always be on the top', $stackPtr, static::CODE_DECLARE_STRICT_TYPES_WRONG_POSITION);

            return;
        }

        $openTagPointer = TokenHelper::findPrevious($phpcsFile, T_OPEN_TAG, $stackPtr - 1);
        if ($openTagPointer === null) {
            return;
        }

        $pointerBeforeDeclare = TokenHelper::findPreviousNonWhitespace($phpcsFile, $stackPtr - 1);
        if ($pointerBeforeDeclare === null) {
            return;
        }

        $whitespaceBefore = '';
        if ($pointerBeforeDeclare === $openTagPointer) {
            $whitespaceBefore .= substr($tokens[$openTagPointer]['content'], strlen('<?php'));
        }

        if ($pointerBeforeDeclare + 1 !== $stackPtr) {
            $whitespaceBefore .= TokenHelper::getContent($phpcsFile, $pointerBeforeDeclare + 1, $stackPtr - 1);
        }

        $declareOnFirstLine = $tokens[$stackPtr]['line'] === $tokens[$openTagPointer]['line'];
        $linesCountBefore = $declareOnFirstLine ? 0 : substr_count($whitespaceBefore, $phpcsFile->eolChar) - 1;
        if ($declareOnFirstLine || $linesCountBefore !== $this->linesCountBeforeDeclare) {
            $fix = $phpcsFile->addFixableError(
                sprintf(
                    'Expected %d line%s before declare statement, found %d.',
                    $this->linesCountBeforeDeclare,
                    $this->linesCountBeforeDeclare === 1 ? '' : 's',
                    $linesCountBefore,
                ),
                $stackPtr,
                self::CODE_DECLARE_STRICT_TYPES_WRONG_POSITION,
            );
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();

                if ($pointerBeforeDeclare === $openTagPointer) {
                    $phpcsFile->fixer->replaceToken($openTagPointer, '<?php');
                }

                FixerHelper::removeBetween($phpcsFile, $pointerBeforeDeclare, $stackPtr);

                for ($i = 0; $i <= $this->linesCountBeforeDeclare; $i++) {
                    $phpcsFile->fixer->addNewline($pointerBeforeDeclare);
                }
                $phpcsFile->fixer->endChangeset();
            }
        }
    }

    /**
     * @return void
     */
    protected function onBeforeProcess(): void
    {
        $this->linesCountBeforeDeclare = $this->declareOnFirstLine ? 0 : $this->normalizeIntValue($this->linesCountBeforeDeclare);
        $this->linesCountAfterDeclare = $this->normalizeIntValue($this->linesCountAfterDeclare);
        $this->spacesCountAroundEqualsSign = $this->normalizeIntValue($this->spacesCountAroundEqualsSign);
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     *
     * @return bool
     */
    protected function isScript(array $tokens): bool
    {
        for ($i = 0, $max = count($tokens); $i < $max; ++$i) {
            $tokenCode = $tokens[$i]['code'];
            if (in_array($tokenCode, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @param int $declarePosition
     *
     * @return bool
     */
    protected function isDeclareAfterOpenTag(array $tokens, int $declarePosition): bool
    {
        for ($i = $declarePosition - 1; $i >= 0; --$i) {
            if ($tokens[$i]['code'] === T_OPEN_TAG) {
                return true;
            }
            if ($tokens[$i]['code'] === T_WHITESPACE) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $tokens
     * @param int $stackPtr
     * @param int $openTagPointer
     *
     * @return bool
     */
    protected function hasNonEmptyTokensInBetween(array $tokens, int $stackPtr, int $openTagPointer): bool
    {
        $docTokenPrefix = 'T_DOC_COMMENT_';
        for ($i = $stackPtr - 1; $i > $openTagPointer; --$i) {
            $tokenCode = $tokens[$i]['code'];
            if (
                substr($tokens[$i]['type'], 0, strlen($docTokenPrefix)) === $docTokenPrefix
                || in_array($tokenCode, static::TOKEN_CODES_TO_CHECK)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getStrictTypeDeclaration(): string
    {
        return sprintf(
            'strict_types%s=%s1',
            str_repeat(' ', $this->spacesCountAroundEqualsSign),
            str_repeat(' ', $this->spacesCountAroundEqualsSign),
        );
    }

    /**
     * @param mixed $value Int value to normalize
     *
     * @return int
     */
    protected function normalizeIntValue($value): int
    {
        return (int)trim((string)$value);
    }
}
