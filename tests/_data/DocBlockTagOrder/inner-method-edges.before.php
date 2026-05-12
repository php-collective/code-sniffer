<?php declare(strict_types = 1);

namespace PhpCollective;

/**
 * Class with @method lines exercising the type-expression walker.
 *
 * @method \Foo|null findOrCreate(array $search)
 * @method static \Foo newEmptyEntity()
 * @method \Cake\Datasource\ResultSetInterface<\Foo>|false saveMany(iterable $entities)
 * @method get(mixed $primaryKey)
 * @method \Foo\Container<array{a: int, b: string}> save(\Foo $entity)
 * @method \Foo somethingMalformed
 * @method \Foo customMethod(\Foo $entity)
 */
class FixMe
{
}
