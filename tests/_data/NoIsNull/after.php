<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    /**
     * Basic is_null() should become === null
     */
    public function basicIsNull($x): bool
    {
        return $x === null;
    }

    /**
     * Negated !is_null() should become !== null
     */
    public function negatedIsNull($x): bool
    {
        return $x !== null;
    }

    /**
     * Leading comparison: true === is_null($x) should simplify
     */
    public function leadingComparisonTrue($x): bool
    {
        return $x === null;
    }

    /**
     * Leading comparison: false === is_null($x) should simplify and negate
     */
    public function leadingComparisonFalse($x): bool
    {
        return $x !== null;
    }

    /**
     * Trailing comparison: is_null($x) === true should simplify
     */
    public function trailingComparisonTrue($x): bool
    {
        return $x === null;
    }

    /**
     * Trailing comparison: is_null($x) === false should simplify and negate
     */
    public function trailingComparisonFalse($x): bool
    {
        return $x !== null;
    }

    /**
     * Trailing comparison with !== true should simplify and negate
     */
    public function trailingComparisonNotTrue($x): bool
    {
        return $x !== null;
    }

    /**
     * Trailing comparison with !== false should simplify (double negation)
     */
    public function trailingComparisonNotFalse($x): bool
    {
        return $x === null;
    }
}
