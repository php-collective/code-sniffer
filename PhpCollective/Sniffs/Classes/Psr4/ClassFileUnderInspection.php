<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Classes\Psr4;

class ClassFileUnderInspection
{
    /**
     * @var string
     */
    protected string $fileName;

    /**
     * @var string
     */
    protected string $className;

    /**
     * @param string $fileName
     * @param string $className
     */
    public function __construct(string $fileName, string $className)
    {
        $this->fileName = $fileName;
        $this->className = ltrim($className, '\\');
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}
