<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Classes\Psr4;

interface InspectionResult
{
    /**
     * @return bool
     */
    public function isAutoloadable(): bool;

    /**
     * @return bool
     */
    public function isPsr4RelatedClass(): bool;
}
