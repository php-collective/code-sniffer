<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Classes\Psr4;

class NonAutoloadableClass implements InspectionResult
{
    /**
     * @var string
     */
    protected string $expectedClassName;

    /**
     * @var string
     */
    protected string $actualClassName;

    /**
     * @param string $expectedClassName
     * @param string $actualClassName
     */
    public function __construct(
        string $expectedClassName,
        string $actualClassName,
    ) {
        $this->expectedClassName = $expectedClassName;
        $this->actualClassName = $actualClassName;
    }

    /**
     * @return bool
     */
    public function isAutoloadable(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function isPsr4RelatedClass(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getExpectedClassName(): string
    {
        return $this->expectedClassName;
    }

    /**
     * @return string
     */
    public function getActualClassName(): string
    {
        return $this->actualClassName;
    }
}
