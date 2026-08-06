<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    /**
     * Only plain and fully-qualified global calls should be touched.
     */
    public function namespacedFunctionForms(): array
    {
        $plain = PHP_SAPI;
        $fullyQualified = PHP_SAPI;
        $qualified = Foo\php_sapi_name();
        $relative = namespace\php_sapi_name();

        return [$plain, $fullyQualified, $qualified, $relative];
    }
}
