<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Classes\Psr4;

class AutoloadabilityInspector
{
    /**
     * @var string
     */
    protected string $baseDirectory;

    /**
     * @var string
     */
    protected string $namespacePrefix;

    /**
     * @param string $baseDirectory
     * @param string $namespacePrefix
     */
    public function __construct(string $baseDirectory, string $namespacePrefix)
    {
        $this->baseDirectory = rtrim($baseDirectory, '/') . '/';
        $this->namespacePrefix = rtrim($namespacePrefix, '\\') . '\\';
    }

    /**
     * @param \PhpCollective\Sniffs\Classes\Psr4\ClassFileUnderInspection $classFile
     *
     * @return \PhpCollective\Sniffs\Classes\Psr4\InspectionResult
     */
    public function inspect(
        ClassFileUnderInspection $classFile,
    ): InspectionResult {
        return $this->classFileIsUnderBaseDirectory($classFile) ?
            $this->inspectAutoloadability($classFile) :
            new PSR4UnrelatedClass();
    }

    /**
     * @param \PhpCollective\Sniffs\Classes\Psr4\ClassFileUnderInspection $classFile
     *
     * @return bool
     */
    protected function classFileIsUnderBaseDirectory(
        ClassFileUnderInspection $classFile,
    ): bool {
        return strpos($classFile->getFileName(), $this->baseDirectory) === 0;
    }

    /**
     * @param \PhpCollective\Sniffs\Classes\Psr4\ClassFileUnderInspection $classFile
     *
     * @return \PhpCollective\Sniffs\Classes\Psr4\InspectionResult
     */
    protected function inspectAutoloadability(
        ClassFileUnderInspection $classFile,
    ): InspectionResult {
        $expectedClassName = $this->guessExpectedClassName($classFile);
        $actualClassName = $classFile->getClassName();

        return $expectedClassName === $actualClassName ?
            new AutoloadableClass() :
            new NonAutoloadableClass($expectedClassName, $actualClassName);
    }

    /**
     * @param \PhpCollective\Sniffs\Classes\Psr4\ClassFileUnderInspection $classFile
     *
     * @return string
     */
    protected function guessExpectedClassName(
        ClassFileUnderInspection $classFile,
    ): string {
        $relativeFileName = $this->guessRelativeFileName($classFile);
        $relativeClassName = $this->guessRelativeClassName($relativeFileName);

        return $this->guessFullyQualifiedClassName($relativeClassName);
    }

    /**
     * @param \PhpCollective\Sniffs\Classes\Psr4\ClassFileUnderInspection $classFile
     *
     * @return string
     */
    protected function guessRelativeFileName(
        ClassFileUnderInspection $classFile,
    ): string {
        assert($this->directoryEndsWithSlash());
        assert($this->classFileIsUnderBaseDirectory($classFile));

        return substr(
            $classFile->getFileName(),
            strlen($this->baseDirectory),
        );
    }

    /**
     * @param string $relativeFileName
     *
     * @return string
     */
    protected function guessRelativeClassName(string $relativeFileName): string
    {
        $basename = basename($relativeFileName);
        $filename = pathinfo($relativeFileName, \PATHINFO_FILENAME);
        $dirname = $basename === $relativeFileName ?
            '' :
            pathinfo($relativeFileName, \PATHINFO_DIRNAME) . '/';

        return str_replace('/', '\\', $dirname) . $filename;
    }

    /**
     * @param string $relativeClassName
     *
     * @return string
     */
    protected function guessFullyQualifiedClassName(
        string $relativeClassName,
    ): string {
        assert($this->namespaceEndsWithBackslash());

        return $this->namespacePrefix . $relativeClassName;
    }

    /**
     * @return bool
     */
    protected function directoryEndsWithSlash(): bool
    {
        return substr($this->baseDirectory, -1) === '/';
    }

    /**
     * @return bool
     */
    protected function namespaceEndsWithBackslash(): bool
    {
        return substr($this->namespacePrefix, -1) === '\\';
    }
}
