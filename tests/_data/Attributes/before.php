<?php declare(strict_types = 1);

namespace PhpCollective;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\DataProvider;

// Correct - FQCN with leading backslash
#[\AllowDynamicProperties]
class CorrectExample1
{
}

// Correct - FQCN with namespace separator tokens
#[\Some\Namespace\MyAttribute]
class CorrectExample2
{
}

// Incorrect - missing leading backslash (no use statement, should just add \)
#[AllowDynamicProperties]
class IncorrectExample1
{
}

// Incorrect - has use statement, should expand to full FQCN
#[DoesNotPerformAssertions]
class IncorrectExample2
{
}

// Multiple attributes - mixed correct and incorrect
#[\AllowDynamicProperties]
#[DataProvider]
#[\Another\Namespace\Attr]
class MixedExample
{
}

// Attribute with parameters - correct
#[\Attribute(Attribute::TARGET_CLASS)]
class WithParameters
{
}

// Attribute with parameters - incorrect (has use statement)
#[DoesNotPerformAssertions]
class WithParametersIncorrect
{
}