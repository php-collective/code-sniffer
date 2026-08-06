<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    /**
     * Only plain and fully-qualified global calls should be reported.
     */
    public function namespacedFunctionForms(array $list): array
    {
        $plain = pos($list);
        $fullyQualified = \pos($list);
        $qualified = Foo\pos($list);
        $relative = namespace\pos($list);

        return [$plain, $fullyQualified, $qualified, $relative];
    }
}
