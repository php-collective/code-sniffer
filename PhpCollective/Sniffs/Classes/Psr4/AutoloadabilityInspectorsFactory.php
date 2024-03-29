<?php

declare(strict_types=1);

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace PhpCollective\Sniffs\Classes\Psr4;

use InvalidArgumentException;
use RuntimeException;

class AutoloadabilityInspectorsFactory
{
    /**
     * @param string|null $basePath
     * @param string $composerJsonPath
     *
     * @return \PhpCollective\Sniffs\Classes\Psr4\AutoloadabilityInspectors
     */
    public static function create(
        ?string $basePath,
        string $composerJsonPath,
    ): AutoloadabilityInspectors {
        $resolvedComposerJsonPath = static::resolveComposerJsonPath(
            $basePath,
            $composerJsonPath,
        );
        static::assertFileExists($resolvedComposerJsonPath);
        static::assertFileIsReadable($resolvedComposerJsonPath);

        return static::getPsr4Directories($resolvedComposerJsonPath);
    }

    /**
     * @param string $filename
     *
     * @throws \RuntimeException
     *
     * @return \PhpCollective\Sniffs\Classes\Psr4\AutoloadabilityInspectors
     */
    protected static function getPsr4Directories(
        string $filename,
    ): AutoloadabilityInspectors {
        $contents = file_get_contents($filename);

        if ($contents === false) {
            throw new RuntimeException("Unable to read file: {$filename}");
        }
        $data = json_decode($contents, true);

        if (!is_array($data)) {
            throw new RuntimeException(
                "Unable to decode json: {$filename}",
            );
        }
        $psr4Directories = [];

        $base = dirname($filename) . '/';
        if (isset($data['autoload']['psr-4'])) {
            foreach ($data['autoload']['psr-4'] as $namespace => $dirs) {
                if (!is_array($dirs)) {
                    $dirs = [$dirs];
                }
                foreach ($dirs as $dir) {
                    $psr4Directories[$dir] = new AutoloadabilityInspector(
                        $base . $dir,
                        $namespace,
                    );
                }
            }
        }

        if (isset($data['autoload-dev']['psr-4'])) {
            foreach ($data['autoload-dev']['psr-4'] as $namespace => $dirs) {
                if (!is_array($dirs)) {
                    $dirs = [$dirs];
                }
                foreach ($dirs as $dir) {
                    $psr4Directories[$dir] = new AutoloadabilityInspector(
                        $base . $dir,
                        $namespace,
                    );
                }
            }
        }

        krsort($psr4Directories);

        return new AutoloadabilityInspectors(...$psr4Directories);
    }

    /**
     * @param string|null $basePath
     * @param string $composerJsonPath
     *
     * @return string
     */
    protected static function resolveComposerJsonPath(
        ?string $basePath,
        string $composerJsonPath,
    ): string {
        return $basePath === null ?
            $composerJsonPath :
            $basePath . '/' . $composerJsonPath;
    }

    /**
     * @param string $filename
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected static function assertFileExists(string $filename): void
    {
        if (!is_file($filename)) {
            throw new InvalidArgumentException(
                "composer.json file not found: {$filename}",
            );
        }
    }

    /**
     * @param string $filename
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    protected static function assertFileIsReadable(string $filename): void
    {
        if (!is_readable($filename)) {
            throw new InvalidArgumentException(
                "composer.json file is not readable: {$filename}",
            );
        }
    }
}
