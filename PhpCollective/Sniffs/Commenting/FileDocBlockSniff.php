<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PhpCollective\Sniffs\AbstractSniffs\AbstractSniff;

/**
 * Checks if PHP class file has file doc block comment and has the expected content.
 * Use an empty .license file in your ROOT to remove all license doc blocks.
 */
class FileDocBlockSniff extends AbstractSniff
{
    /**
     * Cache of licenses to avoid file lookups.
     *
     * @var array<string>
     */
    protected $licenseMap = [];

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [
            T_NAMESPACE,
        ];
    }

    /**
     * @inheritDoc
     */
    public function process(File $phpCsFile, $stackPointer): void
    {
        $license = $this->getLicense($phpCsFile);
        if ($license === '') {
            return;
        }

        $fileDocBlockPointer = $this->fileDocBlockPointer($phpCsFile, $stackPointer);
        if ($fileDocBlockPointer === null) {
            $this->addMissingFileDocBlock($phpCsFile, $stackPointer, $license);

            return;
        }

        $this->assertNewlineBefore($phpCsFile, $stackPointer);
        $this->assertNewlineBefore($phpCsFile, $fileDocBlockPointer);

        if (!$this->isOwnFileDocBlock($phpCsFile, $fileDocBlockPointer)) {
            return;
        }

        $currentLicenseLines = $this->getFileDocBlockLines($phpCsFile, $fileDocBlockPointer);
        $currentLicense = $this->buildLicense($currentLicenseLines);

        if ($this->isCorrectFileDocBlock($currentLicense, $license)) {
            return;
        }

        $this->fixFileDocBlock($phpCsFile, $fileDocBlockPointer, $license);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     * @param string $license
     *
     * @return void
     */
    protected function addMissingFileDocBlock(File $phpCsFile, int $stackPointer, string $license): void
    {
        if (!$license) {
            return;
        }

        $fix = $phpCsFile->addFixableError('No file doc block', $stackPointer, 'FileDocBlockMissing');
        if (!$fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();

        $fileDocBlockStartPosition = $stackPointer - 1;

        $phpCsFile->fixer->addContent($fileDocBlockStartPosition, $license);
        $phpCsFile->fixer->addNewline($fileDocBlockStartPosition);
        $phpCsFile->fixer->addNewline($fileDocBlockStartPosition);

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $fileDocBlockStartPointer
     *
     * @return bool
     */
    protected function isOwnFileDocBlock(File $phpCsFile, int $fileDocBlockStartPointer): bool
    {
        $fileDockBlockLines = $this->getFileDocBlockLines($phpCsFile, $fileDocBlockStartPointer);
        if (!$fileDockBlockLines) {
            return false;
        }

        $firstLineComment = array_shift($fileDockBlockLines);

        if (strpos($firstLineComment, 'modified by ') !== false) {
            return false;
        }

        return true;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string
     */
    protected function getLicense(File $phpCsFile): string
    {
        $customLicense = $this->findLicense($phpCsFile);
        if (!$customLicense) {
            return '';
        }

        return $customLicense === 'none' ? '' : $customLicense;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     *
     * @return string|null
     */
    protected function findLicense(File $phpCsFile): ?string
    {
        $currentPath = getcwd() ?: '';
        if ($currentPath) {
            $currentPath .= DIRECTORY_SEPARATOR;
        }

        return $this->findCustomLicense($currentPath) ?: null;
    }

    /**
     * Gets license header to be used. Returns `none` for no license header as custom license.
     *
     * @param string $path
     *
     * @return string
     */
    protected function findCustomLicense(string $path): string
    {
        if (isset($this->licenseMap[$path])) {
            return $this->licenseMap[$path];
        }

        if (!file_exists($path . '.license')) {
            $this->licenseMap[$path] = '';

            return '';
        }

        $license = (string)file_get_contents($path . '.license');
        if (trim($license) === '') {
            $license = 'none';
        }

        $this->licenseMap[$path] = $license;

        return $license;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $fileDocBlockStartPosition
     * @param string $license
     *
     * @return void
     */
    protected function fixFileDocBlock(File $phpCsFile, int $fileDocBlockStartPosition, string $license): void
    {
        $fix = $phpCsFile->addFixableError('Wrong file doc block', $fileDocBlockStartPosition, 'FileDocBlockWrong');
        if (!$fix) {
            return;
        }

        $phpCsFile->fixer->beginChangeset();

        $this->clearFileDocBlock($phpCsFile, $fileDocBlockStartPosition);

        if ($license) {
            $phpCsFile->fixer->addContent($fileDocBlockStartPosition, $license);
        }

        $phpCsFile->fixer->endChangeset();
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return int|null
     */
    protected function fileDocBlockPointer(File $phpCsFile, int $stackPointer): ?int
    {
        $fileDocBlockStartPosition = $phpCsFile->findPrevious(T_DOC_COMMENT_OPEN_TAG, $stackPointer);

        return $fileDocBlockStartPosition !== false ? $fileDocBlockStartPosition : null;
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $fileDocBlockStartPosition
     *
     * @return void
     */
    protected function clearFileDocBlock(File $phpCsFile, int $fileDocBlockStartPosition): void
    {
        $tokens = $phpCsFile->getTokens();
        $fileDocBlockEndPosition = $tokens[$fileDocBlockStartPosition]['comment_closer'];

        for ($i = $fileDocBlockStartPosition; $i <= $fileDocBlockEndPosition + 1; $i++) {
            $phpCsFile->fixer->replaceToken($i, '');
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int|null $fileDocBlockStartPosition
     *
     * @return array<string>
     */
    protected function getFileDocBlockLines(File $phpCsFile, ?int $fileDocBlockStartPosition): array
    {
        $tokens = $phpCsFile->getTokens();
        $fileDocBlockEndPosition = $tokens[$fileDocBlockStartPosition]['comment_closer'];

        $result = [];
        for ($i = $fileDocBlockStartPosition; $i <= $fileDocBlockEndPosition; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_STRING) {
                continue;
            }
            $result[] = $tokens[$i]['content'];
        }

        return $result;
    }

    /**
     * @param string $currentLicense
     * @param string $expectedLicense
     *
     * @return bool
     */
    protected function isCorrectFileDocBlock(string $currentLicense, string $expectedLicense): bool
    {
        $currentLicense = str_replace(["\r\n", "\r"], "\n", $currentLicense);
        $expectedLicense = str_replace(["\r\n", "\r"], "\n", $expectedLicense);

        return trim($currentLicense) === trim($expectedLicense);
    }

    /**
     * @param array<string> $licenseLines
     *
     * @return string
     */
    protected function buildLicense(array $licenseLines): string
    {
        if (!$licenseLines) {
            return '';
        }

        $license = [];

        $license[] = '/**';
        foreach ($licenseLines as $licenseLine) {
            $license[] = ' * ' . $licenseLine;
        }
        $license[] = ' */';
        $license[] = '';

        return implode(PHP_EOL, $license);
    }
}
