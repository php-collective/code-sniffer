<?php declare(strict_types = 1);

namespace PhpCollective;

use Tools\Mailer\Message as MailerMessage;
use Some\Other\Class;
use Yet\Another\Thing as AnotherAlias;

class FixMe
{
    protected $x = [];

    /**
     * @var array
     */
    protected $y = [];

    /**
     * @var array<int, string>
     */
    protected $foo = [];

    /**
     * Stack of warnings.
     *
     * @var list<string>
     */
    protected $warnings = [];

    /**
     * The left parts of the join condition
     *
     * @var list<string|null>
     */
    protected array $left = [];

    /**
     * @var \Tools\Mailer\Message
     */
    protected MailerMessage $message;

    /**
     * @var \Some\Other\MyClass|null
     */
    protected ?MyClass $class;

    /**
     * @var \Yet\Another\Thing
     */
    protected AnotherAlias $another;

    /**
     * @var string[]
     */
    protected array $fixtures = [
        'plugin.QueueScheduler.SchedulerRows',
    ];

    /**
     * @var int[]
     */
    protected array $numbers = [1, 2, 3];

    /**
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * @var MyClass[]
     */
    protected array $objects;
}
