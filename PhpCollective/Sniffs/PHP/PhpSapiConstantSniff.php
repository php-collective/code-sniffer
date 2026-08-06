<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * Always use PHP_SAPI constant instead of php_sapi_name() function.
 */
class PhpSapiConstantSniff extends AbstractSniff
{
    /**
     * @var string
     */
    protected const PHP_SAPI = 'PHP_SAPI';

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
        $tokens = $phpcsFile->getTokens();

        $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

        $functionName = $this->getGlobalFunctionName($phpcsFile, $stackPtr);
        if ($functionName !== 'php_sapi_name') {
            return;
        }

        $tokenContent = $tokens[$stackPtr]['content'];
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true);
        if ($previous === false || in_array($tokens[$previous]['code'], $wrongTokens, true)) {
            return;
        }

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);
        if ($openingBrace === false || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        $closingBrace = $phpcsFile->findNext(T_WHITESPACE, $openingBrace + 1, null, true);
        if ($closingBrace === false || $tokens[$closingBrace]['type'] !== 'T_CLOSE_PARENTHESIS') {
            return;
        }

        $error = $tokenContent . '() found, should be const ' . static::PHP_SAPI . '.';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'MethodVsConst');
        if ($fix) {
            $phpcsFile->fixer->replaceToken($stackPtr, static::PHP_SAPI);
            for ($i = $openingBrace; $i <= $closingBrace; ++$i) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }
    }
}
