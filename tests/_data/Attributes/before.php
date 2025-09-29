<?php declare(strict_types = 1);

namespace PhpCollective;

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

// Incorrect - missing leading backslash
#[AllowDynamicProperties]
class IncorrectExample1
{
}

// Incorrect - not FQCN
#[MyAttribute]
class IncorrectExample2
{
}

// Multiple attributes - mixed correct and incorrect
#[\AllowDynamicProperties]
#[MyAttribute]
#[\Another\Namespace\Attr]
class MixedExample
{
}

// Attribute with parameters - correct
#[\Attribute(Attribute::TARGET_CLASS)]
class WithParameters
{
}

// Attribute with parameters - incorrect
#[Attribute(Attribute::TARGET_METHOD)]
class WithParametersIncorrect
{
}