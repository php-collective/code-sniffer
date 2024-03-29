<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Classes;

use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PhpCollective\Sniffs\Classes\Psr4\AutoloadabilityInspectors;
use PhpCollective\Sniffs\Classes\Psr4\AutoloadabilityInspectorsFactory;
use PhpCollective\Sniffs\Classes\Psr4\ClassFileUnderInspection;
use PhpCollective\Sniffs\Classes\Psr4\NonAutoloadableClass;
use RuntimeException;
use SlevomatCodingStandard\Helpers\ClassHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;

class Psr4Sniff implements Sniff
{
    /**
     * @var string
     */
    public const CODE_INCORRECT_CLASS_NAME = 'IncorrectClassName';

    /**
     * @var int
     */
    protected const INITIALIZED = 1;

    /**
     * @var int
     */
    protected const UNINITIALIZED = 0;

    protected const INITIALIZATION_FAILURE = -1;

    /**
     * File path of "composer.json".
     *
     * This must be relative path to "--basepath" option of phpcs command.
     *
     * @var string
     */
    public string $composerJsonPath = 'composer.json';

    /**
     * @var int
     */
    protected int $initialization = self::UNINITIALIZED;

    /**
     * @var \PhpCollective\Sniffs\Classes\Psr4\AutoloadabilityInspectors
     */
    protected AutoloadabilityInspectors $autoloadabilityInspectors;

    /**
     * @inheritDoc
     */
    public function register(): array
    {
        return [\T_CLASS, \T_INTERFACE, \T_TRAIT];
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function process(File $phpcsFile, $typePointer): void
    {
        $this->initializeThisSniffIfNotYet($phpcsFile->config);

        if ($this->initialization === static::INITIALIZATION_FAILURE) {
            return;
        }

        $classFile = $this->getClassFileOf($phpcsFile, $typePointer);
        $result = $this->autoloadabilityInspectors->inspect($classFile);

        if ($result instanceof NonAutoloadableClass) {
            $this->addError($phpcsFile, $result, $typePointer);
        }
    }

    /**
     * @param \PHP_CodeSniffer\Config $config
     *
     * @return void
     */
    protected function initializeThisSniffIfNotYet(Config $config): void
    {
        if ($this->initialization === static::UNINITIALIZED) {
            $this->initialization = static::INITIALIZATION_FAILURE;
            $this->autoloadabilityInspectors =
                AutoloadabilityInspectorsFactory::create(
                    $config->getSettings()['basepath'] ?: getcwd(),
                    $this->composerJsonPath,
                );
            $this->initialization = static::INITIALIZED;
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $typePointer
     *
     * @return \PhpCollective\Sniffs\Classes\Psr4\ClassFileUnderInspection
     */
    protected function getClassFileOf(
        File $phpcsFile,
        int $typePointer,
    ): ClassFileUnderInspection {
        return new ClassFileUnderInspection(
            $phpcsFile->getFilename(),
            ClassHelper::getFullyQualifiedName($phpcsFile, $typePointer),
        );
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param \PhpCollective\Sniffs\Classes\Psr4\NonAutoloadableClass $result
     * @param int $typePointer
     *
     * @throws \RuntimeException
     *
     * @return void
     */
    protected function addError(
        File $phpcsFile,
        NonAutoloadableClass $result,
        int $typePointer,
    ): void {
        $stackPtr = $this->getClassNameDeclarationPosition($phpcsFile, $typePointer);
        if ($stackPtr === null) {
            throw new RuntimeException('Cannot find class declaration position');
        }

        $phpcsFile->addError(
            sprintf(
                'Class name is not compliant with PSR-4 configuration. ' .
                'It should be `%s` instead of `%s`.',
                $result->getExpectedClassName(),
                $result->getActualClassName(),
            ),
            $stackPtr,
            static::CODE_INCORRECT_CLASS_NAME,
        );
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpcsFile
     * @param int $typePointer
     *
     * @return int|null
     */
    protected function getClassNameDeclarationPosition(
        File $phpcsFile,
        int $typePointer,
    ): ?int {
        return TokenHelper::findNext($phpcsFile, \T_STRING, $typePointer + 1);
    }
}
