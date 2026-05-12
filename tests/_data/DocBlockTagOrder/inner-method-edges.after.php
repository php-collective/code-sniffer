<?php declare(strict_types = 1);

namespace PhpCollective;

/**
 * Class with @method lines exercising the type-expression walker.
 *
 * @method static \Foo newEmptyEntity()
 * @method get(mixed $primaryKey)
 * @method \Foo|null findOrCreate(array $search)
 * @method \Foo\Container<array{a: int, b: string}> save(\Foo $entity)
 * @method \Cake\Datasource\ResultSetInterface<\Foo>|false saveMany(iterable $entities)
 * @method \Foo customMethod(\Foo $entity)
 * @method \Foo somethingMalformed
 */
class FixMe
{
}
