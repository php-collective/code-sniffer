<?php

namespace TestApp;

use Djot\Node\Node;

class DocBlockParamNotNullableTestClass
{
    /**
     * Type hint is Node, but docblock says Node|string - string is incompatible
     *
     * @param Node $parent Should be fixed to just Node
     * @param int $sourceLine
     */
    public function parseWithExtraType(Node $parent, int $sourceLine = 0): void
    {
    }

    /**
     * Type hint is string, but docblock says string|int - int is incompatible
     *
     * @param string $name Should be fixed to just string
     */
    public function setNameWithExtraType(string $name): void
    {
    }

    /**
     * Type hint is ?Node (nullable), docblock has Node|string|null - string is incompatible
     *
     * @param Node|null $parent Should be fixed to Node|null
     */
    public function parseNullableWithExtraType(?Node $parent): void
    {
    }

    /**
     * Valid: Type hint is Node, docblock is Node - no change needed
     *
     * @param Node $parent Already correct
     */
    public function parseCorrect(Node $parent): void
    {
    }

    /**
     * Valid: Type hint is ?Node, docblock is Node|null - no change needed
     *
     * @param Node|null $parent Already correct
     */
    public function parseNullableCorrect(?Node $parent): void
    {
    }

    /**
     * Valid: No type hint, so any docblock type is allowed
     *
     * @param Node|string $parent No type hint, so this is fine
     */
    public function parseNoTypeHint($parent): void
    {
    }

    /**
     * Valid: Type hint is mixed, so any docblock type is allowed
     *
     * @param Node|string $parent Mixed allows anything
     */
    public function parseMixed(mixed $parent): void
    {
    }

    /**
     * Type hint is array, docblock has array|string - string is incompatible
     *
     * @param array $items Should be fixed to just array
     */
    public function processArrayWithExtraType(array $items): void
    {
    }

    /**
     * Valid: Type hint is array, docblock has array<string> - this is valid
     *
     * @param array<string> $items Generic array is fine
     */
    public function processArrayGeneric(array $items): void
    {
    }

    /**
     * Valid: Type hint is array, docblock has string[] - this is valid
     *
     * @param string[] $items Array notation is fine
     */
    public function processArrayBracket(array $items): void
    {
    }

    /**
     * Type hint is int, docblock has int|string - string is incompatible
     *
     * @param int $count Should be fixed to just int
     */
    public function setCountWithExtraType(int $count): void
    {
    }

    /**
     * Multiple invalid types
     *
     * @param Node $parent Should be fixed to just Node
     */
    public function multipleInvalidTypes(Node $parent): void
    {
    }

    /**
     * FQCN type hint with short docblock type
     *
     * @param Node $parent Should be fixed to just Node
     */
    public function fqcnTypeHint(\Djot\Node\Node $parent): void
    {
    }

    /**
     * Valid: callable allows Closure
     *
     * @param callable|Closure $callback Both valid for callable
     */
    public function callableWithClosure(callable $callback): void
    {
    }

    /**
     * Valid: bool allows true/false
     *
     * @param bool|true|false $flag All valid for bool
     */
    public function boolWithTrueFalse(bool $flag): void
    {
    }

    /**
     * Type hint is bool, docblock has bool|string - string is incompatible
     *
     * @param bool $flag Should be fixed to just bool
     */
    public function boolWithExtraType(bool $flag): void
    {
    }

    /**
     * Valid: object allows any class
     *
     * @param SomeClass $obj Class is valid for object type hint
     */
    public function objectWithClass(object $obj): void
    {
    }

    /**
     * Valid: int allows positive-int, negative-int, etc.
     *
     * @param positive-int $count Valid for int
     */
    public function intWithPositiveInt(int $count): void
    {
    }

    /**
     * Valid: string allows class-string, non-empty-string, etc.
     *
     * @param non-empty-string $name Valid for string
     */
    public function stringWithNonEmpty(string $name): void
    {
    }

    /**
     * Union type hint - string|int, docblock has string|int|bool - bool is incompatible
     *
     * @param string|int $value Should be fixed to string|int
     */
    public function unionTypeWithExtraType(string|int $value): void
    {
    }

    /**
     * Valid: Union type hint - string|int, docblock matches
     *
     * @param string|int $value Already correct
     */
    public function unionTypeCorrect(string|int $value): void
    {
    }

    /**
     * All docblock types are invalid - should NOT auto-fix, just error
     *
     * @param string $parent All types wrong, needs manual fix
     */
    public function allTypesInvalid(Node $parent): void
    {
    }
}
