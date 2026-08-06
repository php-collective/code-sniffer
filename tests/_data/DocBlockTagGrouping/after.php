<?php

declare(strict_types=1);

namespace PhpCollective;

class DocBlockTagGroupingExample
{
    /**
     *
     * @var string
     */
    public const TAGS_START_TOO_LATE = 'start';

    /**
     * Tags need one separating line.
     *
     * @param string $name
     *
     * @return void
     */
    public function missingDescriptionTagSeparator(string $name): void
    {
    }

    /**
     * Same tags must stay together.
     *
     * @param string $first
     * @param string $second
     *
     * @return void
     */
    public function sameTagsHaveExtraSpacing(string $first, string $second): void
    {
    }

    /**
     * Different tags need one separator.
     *
     * @param string $value
     *
     * @throws \RuntimeException
     */
    public function differentTagsNeedSpacing(string $value): void
    {
    }

    /**
     * Extra spacing after tags.
     *
     * @return void
     */
    public function tagsHaveTrailingSpacing(): void
    {
    }

    /**
     * Valid grouping.
     *
     * @param string $value
     *
     * @return void
     */
    public function validGrouping(string $value): void
    {
    }

    public function closureIsLeftAlone(): void
    {
        /**
         * Closure doc block is ignored.
         * @return void
         */
        $closure = function (): void {
        };
    }
}
