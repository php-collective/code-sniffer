<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\PHP;

use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * Do not use aliases or long forms of functions.
 */
class RemoveFunctionAliasSniff extends AbstractSniff
{
    /**
     * @see http://php.net/manual/en/aliases.php
     *
     * @var array<string>
     */
    public static array $matching = [
        'is_integer' => 'is_int',
        'is_long' => 'is_int',
        'is_real' => 'is_float',
        'is_double' => 'is_float',
        'doubleval' => 'floatval',
        'is_writeable' => 'is_writable',
        'join' => 'implode',
        'key_exists' => 'array_key_exists', // Deprecated function
        'sizeof' => 'count',
        'strchr' => 'strstr',
        'ini_alter' => 'ini_set',
        'fputs' => 'fwrite',
        'chop' => 'rtrim',
        'pos' => 'current',
        'show_source' => 'highlight_file',
        'user_error' => 'trigger_error',
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
        $this->checkFixableAliases($phpcsFile, $stackPtr);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPtr
     *
     * @return void
     */
    protected function checkFixableAliases(File $phpcsFile, int $stackPtr): void
    {
        $wrongTokens = [T_FUNCTION, T_OBJECT_OPERATOR, T_NEW, T_DOUBLE_COLON];

        $tokens = $phpcsFile->getTokens();

        $key = $this->getGlobalFunctionName($phpcsFile, $stackPtr);
        if ($key === null || !isset(static::$matching[$key])) {
            return;
        }

        $tokenContent = $tokens[$stackPtr]['content'];
        $previous = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($previous === false || in_array($tokens[$previous]['code'], $wrongTokens)) {
            return;
        }

        $openingBrace = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
        if (!$openingBrace || $tokens[$openingBrace]['type'] !== 'T_OPEN_PARENTHESIS') {
            return;
        }

        // A fully qualified call keeps its leading backslash, in the message as well as in the fix.
        $replacement = static::$matching[$key];
        if ($tokens[$stackPtr]['code'] === T_NAME_FULLY_QUALIFIED) {
            $replacement = '\\' . $replacement;
        }

        $error = 'Function name ' . $tokenContent . '() found, should be ' . $replacement . '().';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'LongInvalid');
        if ($fix) {
            $phpcsFile->fixer->replaceToken($stackPtr, $replacement);
        }
    }
}
