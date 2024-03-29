<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Classes\Psr4;

class AutoloadableClass implements InspectionResult
{
    /**
     * @return bool
     */
    public function isAutoloadable(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isPsr4RelatedClass(): bool
    {
        return true;
    }
}
