<?php

declare(strict_types=1);

namespace PhpCollective;

class DocBlockStructureExample
{
    /***
     * Opening marker has too many stars.
     */
    public const OPENING = 'opening';

    /**
     * Closing marker has too many stars.
     **/
    public const CLOSING = 'closing';

    /** Summary starts on the opening line.
     */
    public const BEGINNING = 'beginning';

    /** Single-line comments are left alone. */
    public const VALID_SINGLE_LINE = 'valid';

    /**
     * Valid multi-line comments are left alone.
     */
    public const VALID_MULTI_LINE = 'valid';
}
