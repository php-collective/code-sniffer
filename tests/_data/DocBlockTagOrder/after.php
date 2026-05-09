<?php declare(strict_types = 1);

namespace PhpCollective;

/**
 * Class-level docblock with scrambled tag groups.
 *
 * @author Some Author
 * @extends \BaseTable<array{Slugged: \Behavior, Tree: \Behavior}>
 * @property \Foo $Boards
 * @property \Foo $LastUsers
 * @method \Foo save(\Bar $entity, array $options = [])
 * @method \Foo get(mixed $primaryKey, array $finder = 'all')
 * @mixin \BehaviorOne
 * @mixin \BehaviorTwo
 */
class FixMe
{
    /**
     * @param string $foo
     * @throws \RuntimeException
     * @return string
     */
    public function badOrder(string $foo): string
    {
        return $foo;
    }
}
