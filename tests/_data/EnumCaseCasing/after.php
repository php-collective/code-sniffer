<?php declare(strict_types = 1);

namespace PhpCollective;

enum DriverFeatureEnum: string
{
    /**
     * Common Table Expressions (with clause) support.
     */
    case Cte = 'cte';

    /**
     * Disabling constraints without being in transaction support.
     */
    case DISABLE_CONSTRAINT_WITHOUT_TRANSACTION = 'disble-constarint-without-transaction';

    /**
     * Native JSON data type support.
     */
    case Json = 'json';
}
