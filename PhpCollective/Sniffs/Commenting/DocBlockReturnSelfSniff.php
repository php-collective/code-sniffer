<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;
use SlevomatCodingStandard\Helpers\FunctionHelper;

/**
 * Doc blocks should type-hint returning itself as $this for fluent interface to work.
 * Chainable methods declared as such must not have any other return type in code.
 */
class DocBlockReturnSelfSniff extends AbstractSniff
{
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

        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if ($tokens[$i]['content'] !== '@return') {
                continue;
            }

            $classNameIndex = $i + 2;

            if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
                continue;
            }

            $content = $tokens[$classNameIndex]['content'];

            $appendix = '';
            $spaceIndex = strpos($content, ' ');
            if ($spaceIndex) {
                $appendix = substr($content, $spaceIndex);
                $content = substr($content, 0, $spaceIndex);
            }

            if (!$content) {
                continue;
            }

            if ($this->isStaticMethod($phpcsFile, $stackPointer)) {
                continue;
            }

            $parts = explode('|', $content);
            $returnTypes = $this->getReturnTypes($phpcsFile, $stackPointer);

            $this->assertCorrectDocBlockParts($phpcsFile, $classNameIndex, $parts, $returnTypes, $appendix);

            $this->assertChainableReturnType($phpcsFile, $stackPointer, $parts, $returnTypes);
            $this->fixClassToThis($phpcsFile, $classNameIndex, $parts, $appendix, $returnTypes);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $classNameIndex
     * @param array<string> $parts
     * @param array<string> $returnTypes
     * @param string $appendix
     *
     * @return void
     */
    protected function assertCorrectDocBlockParts(
        File $phpcsFile,
        int $classNameIndex,
        array $parts,
        array $returnTypes,
        string $appendix,
    ): void {
        $result = [];
        foreach ($parts as $key => $part) {
            if ($part !== 'self') {
                continue;
            }
            if ($returnTypes !== ['$this']) {
                continue;
            }

            $parts[$key] = '$this';
            $result[$part] = '$this';
        }

        if (!$result) {
            return;
        }

        $message = [];
        foreach ($result as $part => $useStatement) {
            $message[] = $part . ' => ' . $useStatement;
        }

        $fix = $phpcsFile->addFixableError(implode(', ', $message), $classNameIndex, 'SelfVsThis');
        if ($fix) {
            $newContent = implode('|', $parts);
            $phpcsFile->fixer->replaceToken($classNameIndex, $newContent . $appendix);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @return int|null Stackpointer value of docblock end tag, or null if cannot be found
     */
    protected function findRelatedDocBlock(File $phpcsFile, int $stackPointer): ?int
    {
        $tokens = $phpcsFile->getTokens();

        $line = $tokens[$stackPointer]['line'];
        $beginningOfLine = $stackPointer;
        while (!empty($tokens[$beginningOfLine - 1]) && $tokens[$beginningOfLine - 1]['line'] === $line) {
            $beginningOfLine--;
        }

        if (!empty($tokens[$beginningOfLine - 2]) && $tokens[$beginningOfLine - 2]['type'] === 'T_DOC_COMMENT_CLOSE_TAG') {
            return $beginningOfLine - 2;
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @return bool
     */
    protected function isStaticMethod(File $phpcsFile, int $stackPointer): bool
    {
        $tokens = $phpcsFile->getTokens();

        if (!in_array($tokens[$stackPointer]['code'], [T_FUNCTION], true)) {
            return false;
        }

        $methodProperties = $phpcsFile->getMethodProperties($stackPointer);

        return $methodProperties['is_static'];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $classNameIndex
     * @param array<string> $parts
     * @param string $appendix
     * @param array<string> $returnTypes
     *
     * @return void
     */
    protected function fixClassToThis(
        File $phpcsFile,
        int $classNameIndex,
        array $parts,
        string $appendix,
        array $returnTypes,
    ): void {
        $ownClassName = '\\' . $this->getClassName($phpcsFile);

        $result = [];
        foreach ($parts as $key => $part) {
            if ($part !== $ownClassName) {
                continue;
            }

            $parts[$key] = '$this';
            $result[$part] = '$this';
        }

        if (!$result) {
            return;
        }

        $isFluentInterfaceMethod = $returnTypes === ['$this'];
        if (!$isFluentInterfaceMethod) {
            return;
        }

        $message = [];
        foreach ($result as $part => $useStatement) {
            $message[] = $part . ' => ' . $useStatement;
        }

        $fix = $phpcsFile->addFixableError(implode(', ', $message), $classNameIndex, 'ClassVsThis');
        if ($fix) {
            $newContent = implode('|', $parts);
            $phpcsFile->fixer->replaceToken($classNameIndex, $newContent . $appendix);
        }
    }

    /**
     * We want to skip for static or other non chainable use cases.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @return array<string>
     */
    protected function getReturnTypes(File $phpcsFile, int $stackPointer): array
    {
        $tokens = $phpcsFile->getTokens();

        // We skip for interface methods
        if (empty($tokens[$stackPointer]['scope_opener']) || empty($tokens[$stackPointer]['scope_closer'])) {
            return [];
        }

        $scopeOpener = $tokens[$stackPointer]['scope_opener'];
        $scopeCloser = $tokens[$stackPointer]['scope_closer'];

        $returnTypes = [];
        for ($i = $scopeOpener; $i < $scopeCloser; $i++) {
            if ($tokens[$i]['code'] !== T_RETURN) {
                continue;
            }

            if (in_array(T_CLOSURE, $tokens[$i]['conditions'], true)) {
                continue;
            }

            $contentIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $i + 1, $scopeCloser, true);
            if (!$contentIndex) {
                continue;
            }

            if ($tokens[$contentIndex]['code'] === T_PARENT) {
                $parentMethodName = $tokens[$contentIndex + 2]['content'];

                if ($parentMethodName === FunctionHelper::getName($phpcsFile, $stackPointer)) {
                    continue;
                }
            }

            $content = $tokens[$contentIndex]['content'];

            $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $contentIndex + 1, $scopeCloser, true);
            if (!$nextIndex) {
                continue;
            }
            if ($tokens[$nextIndex]['code'] !== T_SEMICOLON) {
                $k = $nextIndex;
                while ($k < $scopeCloser && $tokens[$k]['code'] !== T_SEMICOLON) {
                    $content .= $tokens[$k]['content'];
                    $k++;
                }
            }

            $returnTypes[] = $content;
        }

        return array_unique($returnTypes);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     * @param array<string> $parts
     * @param array<string> $returnTypes
     *
     * @return void
     */
    protected function assertChainableReturnType(
        File $phpcsFile,
        int $stackPointer,
        array $parts,
        array $returnTypes,
    ): void {
        if ($returnTypes && $parts === ['$this'] && $returnTypes !== ['$this']) {
            $phpcsFile->addError('Chainable method (@return $this) cannot have multiple return types in code.', $stackPointer, 'InvalidChainable');
        }
    }
}
