<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    /**
     * @var string|null Some Comment
     */
    protected $string1 = null;

    /**
     * @var string|int|null
     */
    protected $string2 = null;

    /**
     * @param string|null $string1
     * @param string|null $string2
     *
     * @return string|null Some Comment
     */
    public function doSth(?string $string1, ?string $string2 = null): ?string
    {
        return $string1 ?: $string2;
    }
}
