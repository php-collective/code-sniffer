<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    /**
     * Only plain and fully-qualified global calls should be touched.
     */
    public function namespacedFunctionForms($value): array
    {
        $plain = intval($value);
        $fullyQualified = \intval($value);
        $qualified = Foo\intval($value);
        $relative = namespace\intval($value);

        return [$plain, $fullyQualified, $qualified, $relative];
    }
}
