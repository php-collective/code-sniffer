<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Traits\UseStatementsTrait;

/**
 * Checks that attributes are always `\FQCN`.
 */
class AttributesSniff implements Sniff
{
    use UseStatementsTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_ATTRIBUTE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPtr + 1, null, true);
        if (!$nextIndex) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        if ($tokens[$nextIndex]['code'] === T_NS_SEPARATOR || $tokens[$nextIndex]['code'] === T_NAME_FULLY_QUALIFIED) {
            return;
        }

        // Get the attribute name (might be multi-token like Foo\Bar or just Foo)
        $attributeName = (string)$tokens[$nextIndex]['content'];
        $endIndex = $nextIndex;

        // Check if there are more parts to the attribute name (e.g., Foo\Bar)
        $checkIndex = $nextIndex + 1;
        while (
            isset($tokens[$checkIndex]) &&
            ($tokens[$checkIndex]['code'] === T_NS_SEPARATOR || $tokens[$checkIndex]['code'] === T_STRING)
        ) {
            $attributeName .= (string)$tokens[$checkIndex]['content'];
            $endIndex = $checkIndex;
            $checkIndex++;
        }

        // Extract just the first part for use statement lookup
        $firstPart = explode('\\', $attributeName)[0];

        // Look up the use statement
        $useStatements = $this->getUseStatements($phpcsFile);
        $fullyQualifiedName = null;

        if (isset($useStatements[$firstPart])) {
            // Found a use statement, construct the full name
            $useStatement = $useStatements[$firstPart];
            $fullName = (string)$useStatement['fullName'];
            if (str_contains($attributeName, '\\')) {
                // Replace first part with full name (e.g., Foo\Bar\Baz -> \Full\Namespace\Foo\Bar\Baz)
                $parts = explode('\\', $attributeName);
                array_shift($parts); // Remove first part
                $fullyQualifiedName = '\\' . $fullName . (count($parts) > 0 ? '\\' . implode('\\', $parts) : '');
            } else {
                $fullyQualifiedName = '\\' . $fullName;
            }
        }

        $fix = $phpcsFile->addFixableError('FQCN expected for attribute', $nextIndex, 'ExpectedFQCN');
        if ($fix) {
            $phpcsFile->fixer->beginChangeset();
            if ($fullyQualifiedName) {
                // Replace entire attribute name with fully qualified version
                for ($i = $nextIndex; $i <= $endIndex; $i++) {
                    $phpcsFile->fixer->replaceToken($i, '');
                }
                $phpcsFile->fixer->replaceToken($nextIndex, $fullyQualifiedName);
            } else {
                // No use statement found, just add leading backslash
                $phpcsFile->fixer->addContentBefore($nextIndex, '\\');
            }
            $phpcsFile->fixer->endChangeset();
        }
    }
}
