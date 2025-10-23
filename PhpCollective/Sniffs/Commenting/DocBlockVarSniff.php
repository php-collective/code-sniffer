<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;
use PhpCollective\Traits\CommentingTrait;
use PhpCollective\Traits\UseStatementsTrait;

/**
 * Ensures Doc Blocks for variables exist and are correct.
 *
 * @author Mark Scherer
 * @license MIT
 */
class DocBlockVarSniff extends AbstractSniff
{
    use CommentingTrait;
    use UseStatementsTrait;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_VARIABLE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPointer): void
    {
        $tokens = $phpcsFile->getTokens();

        $previousIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $stackPointer - 1, null, true);

        $type = [];
        while ($previousIndex && in_array($tokens[$previousIndex]['code'], [T_STRING, T_NULLABLE, T_NULL, T_TYPE_UNION], true)) {
            $type[] = $tokens[$previousIndex]['content'];
            $previousIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $previousIndex - 1, null, true);
        }
        $type = array_reverse($type);

        // Skip these checks for typed ones for now
        if ($type) {
            $this->checkTyped($phpcsFile, $stackPointer, $type);

            return;
        }

        if ($previousIndex && $tokens[$previousIndex]['code'] === T_STATIC) {
            $previousIndex = $phpcsFile->findPrevious(Tokens::$emptyTokens, $previousIndex - 1, null, true);
        }

        // Skip inline comments here
        if (!$this->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE], $tokens[$previousIndex])) {
            return;
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            $phpcsFile->addError('Doc Block for property missing', $stackPointer, 'VarDocBlockMissing');

            return;
        }

        $this->handle($phpcsFile, $docBlockEndIndex, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     *
     * @return string|null
     */
    protected function findDefaultValueType(File $phpcsFile, int $stackPointer): ?string
    {
        $tokens = $phpcsFile->getTokens();

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $stackPointer + 1, null, true);
        if (!$nextIndex || !$this->isGivenKind(T_EQUAL, $tokens[$nextIndex])) {
            return null;
        }

        $nextIndex = $phpcsFile->findNext(Tokens::$emptyTokens, $nextIndex + 1, null, true);
        if (!$nextIndex) {
            return null;
        }

        return $this->detectType($tokens[$nextIndex]);
    }

    /**
     * @param array<string, mixed> $token
     *
     * @return string|null
     */
    protected function detectType(array $token): ?string
    {
        if ($this->isGivenKind(T_OPEN_SHORT_ARRAY, $token)) {
            return 'array';
        }

        if ($this->isGivenKind(T_LNUMBER, $token)) {
            return 'int';
        }

        if ($this->isGivenKind(T_CONSTANT_ENCAPSED_STRING, $token)) {
            return 'string';
        }

        if ($this->isGivenKind([T_TRUE], $token)) {
            return 'bool';
        }

        if ($this->isGivenKind([T_FALSE], $token)) {
            return 'false';
        }

        if ($this->isGivenKind(T_NULL, $token)) {
            return 'null';
        }

        return null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockEndIndex
     * @param int $docBlockStartIndex
     * @param string|null $defaultValueType
     *
     * @return void
     */
    protected function handleMissingVar(
        File $phpcsFile,
        int $docBlockEndIndex,
        int $docBlockStartIndex,
        ?string $defaultValueType,
    ): void {
        $error = 'Doc Block annotation @var for property missing';
        if ($defaultValueType === null) {
            $phpcsFile->addError($error, $docBlockEndIndex, 'DocBlockMissing');

            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $error .= ', type `' . $defaultValueType . '` detected';
        $fix = $phpcsFile->addFixableError($error, $docBlockEndIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $index = $phpcsFile->findPrevious(T_DOC_COMMENT_WHITESPACE, $docBlockEndIndex - 1, $docBlockStartIndex, true);
        if (!$index) {
            $index = $docBlockStartIndex;
        }

        $indentationLevel = $this->getIndentationLevel($phpcsFile, $docBlockEndIndex);

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addNewline($index);
        $phpcsFile->fixer->addContent($index, str_repeat(' ', $indentationLevel * 4) . ' * @var ' . $defaultValueType);
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $varIndex
     * @param string|null $defaultValueType
     *
     * @return void
     */
    protected function handleMissingVarType(File $phpcsFile, int $varIndex, ?string $defaultValueType): void
    {
        $error = 'Doc Block type for property annotation @var missing';
        if ($defaultValueType === null) {
            $phpcsFile->addError($error, $varIndex, 'VarTypeMissing');

            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        $error .= ', type `' . $defaultValueType . '` detected';
        $fix = $phpcsFile->addFixableError($error, $varIndex, 'WrongType');
        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->addContent($varIndex, ' ' . $defaultValueType);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     * @param array<string> $types
     *
     * @return void
     */
    protected function checkTyped(File $phpcsFile, int $stackPointer, array $types): void
    {
        foreach ($types as $key => $value) {
            if ($value === '?') {
                unset($types[$key]);
                $types[] = 'null';
            }
            if ($value === '|') {
                unset($types[$key]);
            }
        }

        $docBlockEndIndex = $this->findRelatedDocBlock($phpcsFile, $stackPointer);

        if (!$docBlockEndIndex) {
            return;
        }

        $this->handle($phpcsFile, $docBlockEndIndex, $stackPointer, $types);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $docBlockEndIndex
     * @param int $stackPointer
     * @param array<string> $types
     *
     * @return void
     */
    protected function handle(File $phpcsFile, int $docBlockEndIndex, int $stackPointer, array $types = []): void
    {
        $tokens = $phpcsFile->getTokens();

        /** @var int $docBlockStartIndex */
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];

        $defaultValueType = $this->findDefaultValueType($phpcsFile, $stackPointer);

        $varIndex = null;
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['type'] !== 'T_DOC_COMMENT_TAG') {
                continue;
            }
            if (!in_array($tokens[$i]['content'], ['@var'], true)) {
                continue;
            }

            $varIndex = $i;
        }

        if (!$varIndex) {
            if ($types) {
                return;
            }

            $this->handleMissingVar($phpcsFile, $docBlockEndIndex, $docBlockStartIndex, $defaultValueType);

            return;
        }

        $classNameIndex = $varIndex + 2;

        if ($tokens[$classNameIndex]['type'] !== 'T_DOC_COMMENT_STRING') {
            $this->handleMissingVarType($phpcsFile, $varIndex, $defaultValueType);

            return;
        }

        $content = $tokens[$classNameIndex]['content'];
        if (str_contains($content, '{') || str_contains($content, '<')) {
            return;
        }

        $appendix = '';
        $spaceIndex = strpos($content, ' ');
        if ($spaceIndex) {
            $appendix = substr($content, $spaceIndex);
            $content = substr($content, 0, $spaceIndex);
        }

        if (!$content) {
            $error = 'Doc Block type for property annotation @var missing';
            if ($defaultValueType) {
                $error .= ', type `' . $defaultValueType . '` detected';
            }
            $phpcsFile->addError($error, $stackPointer, 'VarTypeEmpty');

            return;
        }

        $comment = trim($appendix);
        if (mb_substr($comment, 0, 1) === '$') {
            $phpcsFile->addError('$var declaration only valid/needed inside inline doc blocks.', $stackPointer, 'CommentInvalid');
        }

        $this->handleDefaultValue($phpcsFile, $stackPointer, $defaultValueType, $content, $appendix, $classNameIndex);
        $this->handleTypes($phpcsFile, $stackPointer, $types, $content, $appendix, $classNameIndex);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     * @param string|null $defaultValueType
     * @param string $content
     * @param string $appendix
     * @param int $classNameIndex
     *
     * @return void
     */
    protected function handleDefaultValue(
        File $phpcsFile,
        int $stackPointer,
        ?string $defaultValueType,
        string $content,
        string $appendix,
        int $classNameIndex,
    ): void {
        if ($defaultValueType === null) {
            return;
        }

        $parts = explode('|', $content);

        if (in_array($defaultValueType, $parts, true)) {
            return;
        }
        if ($defaultValueType === 'array' && ($this->containsTypeArray($parts) || $this->containsTypeArray($parts, 'list'))) {
            return;
        }
        if ($defaultValueType === 'false' && in_array('bool', $parts, true)) {
            return;
        }

        if ($defaultValueType === 'false') {
            $defaultValueType = 'bool';
        }

        if (count($parts) > 1 || $defaultValueType === 'null') {
            $fix = $phpcsFile->addFixableError('Doc Block type for property annotation @var incorrect, type `' . $defaultValueType . '` missing', $stackPointer, 'VarTypeMissing');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($classNameIndex, implode('|', $parts) . '|' . $defaultValueType . $appendix);
            }

            return;
        }

        $fix = $phpcsFile->addFixableError('Doc Block type `' . $content . '` for property annotation @var incorrect, type `' . $defaultValueType . '` expected', $stackPointer, 'VarTypeIncorrect');
        if ($fix) {
            $phpcsFile->fixer->replaceToken($classNameIndex, $defaultValueType . $appendix);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $stackPointer
     * @param array<string> $types
     * @param mixed $content
     * @param string $appendix
     * @param int $classNameIndex
     *
     * @return void
     */
    protected function handleTypes(File $phpcsFile, int $stackPointer, array $types, mixed $content, string $appendix, int $classNameIndex): void
    {
        foreach ($types as $type) {
            if ($this->typesMatch($phpcsFile, $content, $type)) {
                continue;
            }

            $fix = $phpcsFile->addFixableError('Doc Block type `' . $content . '` for property annotation @var incorrect, type `' . $type . '` missing', $stackPointer, 'VarTypeMissing');
            if ($fix) {
                $phpcsFile->fixer->replaceToken($classNameIndex, $content . '|' . $type . $appendix);
            }
        }
    }

    /**
     * Check if two types match, considering use statement aliases.
     *
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param string $docBlockType
     * @param string $propertyType
     *
     * @return bool
     */
    protected function typesMatch(File $phpcsFile, string $docBlockType, string $propertyType): bool
    {
        // Direct match
        if (str_contains($docBlockType, $propertyType)) {
            return true;
        }

        // Check if property type is 'array' and doc block contains array syntax
        if ($propertyType === 'array' && $this->containsTypeArray([$docBlockType])) {
            return true;
        }

        // Get use statements
        $useStatements = $this->getUseStatements($phpcsFile);

        // Check if the property type is an alias
        if (isset($useStatements[$propertyType])) {
            $fullClassName = $useStatements[$propertyType]['fullName'];
            // Check if doc block contains the full class name (with or without leading backslash)
            if (str_contains($docBlockType, '\\' . $fullClassName) || str_contains($docBlockType, $fullClassName)) {
                return true;
            }
        }

        return false;
    }
}
