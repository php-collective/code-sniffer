<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * Always use simple casts instead of method invocation.
 */
class PreferCastOverFunctionSniff extends AbstractSniff
{
    /**
     * @var array<string>
     */
    protected static array $matching = [
        'strval' => 'string',
        'intval' => 'int',
        'floatval' => 'float',
        'boolval' => 'bool',
    ];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return $this->getGlobalFunctionNameTokenCodes();
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

        $tokens = $phpcsFile->getTokens();

        $key = $this->getGlobalFunctionName($phpcsFile, $stackPtr);
        if ($key === null || !isset(static::$matching[$key])) {
            return;
        }

        $tokenContent = $tokens[$stackPtr]['content'];
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if ($previous === false || in_array($tokens[$previous]['code'], $wrongTokens, true)) {
            return;
        }

        $openingBraceIndex = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        if ($openingBraceIndex === false || $tokens[$openingBraceIndex]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $closingBraceIndex = $tokens[$openingBraceIndex]['parenthesis_closer'];

        // We must ignore when commas are encountered
        if ($this->contains($phpcsFile, 'T_COMMA', $openingBraceIndex + 1, $closingBraceIndex - 1)) {
            return;
        }

        $error = $tokenContent . '() found, should be ' . static::$matching[$key] . ' cast.';

        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'MethodVsCast');
        if ($fix) {
            $this->fixContent($phpcsFile, $stackPtr, $key, $openingBraceIndex, $closingBraceIndex);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     * @param string $key
     * @param int $openingBraceIndex
     * @param int $closingBraceIndex
     *
     * @return void
     */
    protected function fixContent(
        File $phpcsFile,
        int $stackPtr,
        string $key,
        int $openingBraceIndex,
        int $closingBraceIndex,
    ): void {
        $needsBrackets = $this->needsBrackets($phpcsFile, $openingBraceIndex, $closingBraceIndex);

        $cast = '(' . static::$matching[$key] . ')';

        $phpcsFile->fixer->replaceToken($stackPtr, $cast);
        if (!$needsBrackets) {
            $phpcsFile->fixer->replaceToken($openingBraceIndex, '');
            $phpcsFile->fixer->replaceToken($closingBraceIndex, '');
        }
    }
}
