<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    /**
     * Only plain and fully-qualified global calls should be touched.
     */
    public function namespacedFunctionForms(): array
    {
        $plain = php_sapi_name();
        $fullyQualified = \php_sapi_name();
        $qualified = Foo\php_sapi_name();
        $relative = namespace\php_sapi_name();

        return [$plain, $fullyQualified, $qualified, $relative];
    }
}
