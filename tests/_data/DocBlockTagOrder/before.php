<?php declare(strict_types = 1);

namespace PhpCollective;

/**
 * Class-level docblock with scrambled tag groups.
 *
 * @author Some Author
 * @method \Foo save(\Bar $entity, array $options = [])
 * @property \Foo $Boards
 * @mixin \BehaviorOne
 * @extends \BaseTable<array{Slugged: \Behavior, Tree: \Behavior}>
 * @method \Foo get(mixed $primaryKey, array $finder = 'all')
 * @property \Foo $LastUsers
 * @mixin \BehaviorTwo
 */
class FixMe
{
    /**
     * @return string
     * @param string $foo
     * @throws \RuntimeException
     */
    public function badOrder(string $foo): string
    {
        return $foo;
    }
}
