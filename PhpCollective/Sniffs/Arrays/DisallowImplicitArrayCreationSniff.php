<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use SlevomatCodingStandard\Sniffs\Arrays\DisallowImplicitArrayCreationSniff as SlevomatDisallowImplicitArrayCreationSniff;

/**
 * Customize to exclude config files (non namespaced classes)
 */
class DisallowImplicitArrayCreationSniff extends SlevomatDisallowImplicitArrayCreationSniff
{
    /**
     * @inheritDoc
     */
    public function process(File $phpcsFile, $stackPtr): void
    {
        // We skip on config files.
        $fileName = $phpcsFile->getFilename();
        if ($this->hasLegacyImplicitCreation($fileName)) {
            return;
        }

        parent::process($phpcsFile, $stackPtr);
    }

    /**
     * @param string $fileName
     *
     * @return bool
     */
    protected function hasLegacyImplicitCreation(string $fileName): bool
    {
        if (str_contains($fileName, DIRECTORY_SEPARATOR . 'config_') || str_contains($fileName, DIRECTORY_SEPARATOR . 'config.')) {
            return true;
        }
        if (str_contains($fileName, DIRECTORY_SEPARATOR . 'cronjobs' . DIRECTORY_SEPARATOR)) {
            return true;
        }

        return false;
    }
}
