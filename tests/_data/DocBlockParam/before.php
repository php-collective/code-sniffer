<?php

namespace TestApp;

class DocBlockParamTestClass
{
    /**
     * Fully typed method with @dataProvider - should not require @param
     *
     * @dataProvider provideTestData
     */
    public function testFullyTypedWithDataProvider(string $param1, int $param2): void
    {
        // No @param required since all params are typed
    }

    /**
     * Fully typed method with @see - should not require @param
     *
     * @see https://example.com
     */
    public function fullyTypedWithSee(string $param1, ?int $param2): void
    {
        // No @param required since all params are typed
    }

    /**
     * Not fully typed with @dataProvider - should require @param
     *
     * @dataProvider provideTestData
     */
    public function testNotFullyTypedWithDataProvider($param1, int $param2): void
    {
        // Should error: missing @param for untyped parameter
    }

    /**
     * Partially documented - should require all @param
     *
     * @dataProvider provideTestData
     * @param string $param1
     */
    public function testPartiallyDocumented(string $param1, int $param2): void
    {
        // Should error: missing @param for $param2
    }

    /**
     * Wrong variable name in @param
     *
     * @param string $wrong
     * @param int $param2
     */
    public function wrongVariableName(string $param1, int $param2): void
    {
        // Should error: wrong variable name
    }

    /**
     * Extra @param
     *
     * @param string $param1
     * @param int $param2
     * @param bool $extra
     */
    public function extraParam(string $param1, int $param2): void
    {
        // Should error: extra @param
    }

    /**
     * No params but has @param
     *
     * @param string $param1
     */
    public function noParamsButHasDocBlock(): void
    {
        // Should error: @param should be removed
    }

    /**
     * Missing type in @param
     *
     * @param $param1
     * @param int $param2
     */
    public function missingTypeInParam(string $param1, int $param2): void
    {
        // Should error: missing type in @param
    }

    /**
     * Fully typed with mixed annotation types - should not require @param
     *
     * @throws \Exception
     * @dataProvider provideData
     * @see SomeClass::someMethod()
     * @deprecated
     */
    public function fullyTypedWithMixedAnnotations(string $param1, int $param2, bool $param3): void
    {
        // No @param required since all params are typed
    }

    /**
     * With @inheritDoc - should skip validation
     *
     * @inheritDoc
     */
    public function withInheritDoc($param1, $param2): void
    {
        // Should not error due to @inheritDoc
    }
}