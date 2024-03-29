<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Classes\Psr4;

class AutoloadabilityInspectors
{
    /**
     * @var array<\PhpCollective\Sniffs\Classes\Psr4\AutoloadabilityInspector>
     */
    protected array $inspectors = [];

    /**
     * @param \PhpCollective\Sniffs\Classes\Psr4\AutoloadabilityInspector ...$inspectors
     */
    public function __construct(AutoloadabilityInspector ...$inspectors)
    {
        $this->inspectors = $inspectors;
    }

    /**
     * @noinspection MultipleReturnStatementsInspection
     *
     * @param \PhpCollective\Sniffs\Classes\Psr4\ClassFileUnderInspection $classFile
     *
     * @return \PhpCollective\Sniffs\Classes\Psr4\InspectionResult
     */
    public function inspect(
        ClassFileUnderInspection $classFile,
    ): InspectionResult {
        foreach ($this->inspectors as $inspector) {
            $result = $inspector->inspect($classFile);

            if ($result->isPsr4RelatedClass()) {
                return $result;
            }
        }

        return new PSR4UnrelatedClass();
    }
}
