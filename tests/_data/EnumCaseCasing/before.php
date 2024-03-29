<?php declare(strict_types = 1);

namespace PhpCollective;

enum DriverFeatureEnum: string
{
    /**
     * Common Table Expressions (with clause) support.
     */
    case CTE = 'cte';

    /**
     * Native JSON data type support.
     */
    case JsonType = 'json-type';

    /**
     * Disabling constraints without being in transaction support.
     */
    case DISABLE_CONSTRAINT = 'disable-constraint';

    case iNVALID = 'invalid';
}
