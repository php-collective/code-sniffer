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
     * @var array<int|string>
     */
    protected const TOKEN_CODES_TO_CHECK = [
        T_OPEN_PARENTHESIS, T_STRING, T_EQUAL, T_LNUMBER, T_CLOSE_PARENTHESIS, T_SEMICOLON, T_COMMENT,
    ];

    /**
     * If declare statement should always be on first line together with PHP open tag.
     *
     * @var bool
     */
    public bool $declareOnFirstLine = false;

    /**
     * Only in effect if $declareOnFirstLine is false, e.g. a file docblock is between.
     *
     * @var int
     */
    public int $linesCountBeforeDeclare = 1;

    /**
     * @var int
     */
    public int $linesCountAfterDeclare = 1;

    /**
     * @var int
     */
    public int $spacesCountAroundEqualsSign = 0;

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

        $this->checkContent($phpcsFile, $stackPtr);

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
            $phpcsFile->addError('declare() statement should always be on the top', $stackPtr, 'DeclareStrictTypesWrongPosition');

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

        $isDeclaredOnFirstLine = $tokens[$stackPtr]['line'] === $tokens[$openTagPointer]['line'];
        $linesCountBefore = $isDeclaredOnFirstLine ? 0 : substr_count($whitespaceBefore, $phpcsFile->eolChar) - 1;
        if ($this->declareOnFirstLine) {
            if ($tokens[$openTagPointer]['line'] !== $tokens[$stackPtr]['line']) {
                $linesCountBefore++;
            }

            if ($linesCountBefore !== 0) {
                $fix = $phpcsFile->addFixableError(
                    sprintf(
                        'Expected %d line%s between opening tag and declare statement, found %d.',
                        $this->linesCountBeforeDeclare,
                        $this->linesCountBeforeDeclare === 1 ? '' : 's',
                        $linesCountBefore,
                    ),
                    $stackPtr,
                    'DeclareStrictTypesTooManyNewlines',
                );
                if ($fix) {
                    $phpcsFile->fixer->beginChangeset();

                    if ($pointerBeforeDeclare === $openTagPointer) {
                        $phpcsFile->fixer->replaceToken($openTagPointer, '<?php ');
                    }

                    for ($i = $openTagPointer + 1; $i < $stackPtr; $i++) {
                        $phpcsFile->fixer->replaceToken($i, '');
                    }

                    $phpcsFile->fixer->endChangeset();
                }
            }

            return;
        }

        if ($linesCountBefore !== $this->linesCountBeforeDeclare) {
            $fix = $phpcsFile->addFixableError(
                sprintf(
                    'Expected %d line%s before declare statement, found %d.',
                    $this->linesCountBeforeDeclare,
                    $this->linesCountBeforeDeclare === 1 ? '' : 's',
                    $linesCountBefore,
                ),
                $stackPtr,
                'DeclareStrictTypesWrongNewlines',
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
    protected function normalizeIntValue(mixed $value): int
    {
        return (int)trim((string)$value);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $declarePointer
     *
     * @return void
     */
    protected function checkContent(File $phpcsFile, int $declarePointer): void
    {
        $tokens = $phpcsFile->getTokens();

        $strictTypesPointer = null;
        for ($i = $tokens[$declarePointer]['parenthesis_opener'] + 1; $i < $tokens[$declarePointer]['parenthesis_closer']; $i++) {
            if ($tokens[$i]['code'] !== T_STRING || $tokens[$i]['content'] !== 'strict_types') {
                continue;
            }

            /** @var int $strictTypesPointer */
            $strictTypesPointer = $i;

            break;
        }

        if ($strictTypesPointer === null) {
            $fix = $phpcsFile->addFixableError(
                sprintf('Missing declare(%s).', $this->getStrictTypeDeclaration()),
                $declarePointer,
                'DeclareStrictTypesMissing',
            );
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addContentBefore(
                    $tokens[$declarePointer]['parenthesis_closer'],
                    ', ' . $this->getStrictTypeDeclaration(),
                );
                $phpcsFile->fixer->endChangeset();
            }

            return;
        }

        $numberPointer = TokenHelper::findNext($phpcsFile, T_LNUMBER, $strictTypesPointer + 1);
        if ($numberPointer && $tokens[$numberPointer]['content'] !== '1') {
            $fix = $phpcsFile->addFixableError(
                sprintf(
                    'Expected %s, found %s.',
                    $this->getStrictTypeDeclaration(),
                    TokenHelper::getContent($phpcsFile, $strictTypesPointer, $numberPointer),
                ),
                $strictTypesPointer,
                'DeclareStrictTypesMissing',
            );
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->replaceToken($numberPointer, '1');
                $phpcsFile->fixer->endChangeset();
            }

            return;
        }

        $strictTypesContent = TokenHelper::getContent($phpcsFile, $strictTypesPointer, $numberPointer);

        $format = sprintf('strict_types%1$s=%1$s1', str_repeat(' ', $this->spacesCountAroundEqualsSign));
        if ($strictTypesContent !== $format) {
            $message = sprintf(
                'Expected %s, found %s.',
                $format,
                $strictTypesContent,
            );
            if (!$numberPointer) {
                $phpcsFile->addError(
                    $message,
                    $strictTypesPointer,
                    'IncorrectStrictTypesFormat',
                );

                return;
            }

            $fix = $phpcsFile->addFixableError(
                $message,
                $strictTypesPointer,
                'IncorrectStrictTypesFormat',
            );
            if ($fix) {
                $phpcsFile->fixer->beginChangeset();

                FixerHelper::change($phpcsFile, $strictTypesPointer, $numberPointer, $format);

                $phpcsFile->fixer->endChangeset();
            }
        }
    }
}
