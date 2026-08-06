<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{

    /**
     * Attribute names sit in front of a parenthesis but name a class, not a function.
     */
    #[Pos(1), Chop(2)]
    #[\Join(3)]
    public int $attributed = 1;
    /**
     * Type check aliases.
     */
    public function typeChecks($x): bool
    {
        return is_integer($x) || is_long($x) || is_real($x) || is_double($x) || is_writeable($x);
    }

    /**
     * Conversion alias deprecated in PHP 8.5.
     */
    public function conversion($x): float
    {
        return doubleval($x);
    }

    /**
     * Array and string aliases.
     */
    public function arraysAndStrings(array $list, string $string)
    {
        $count = sizeof($list);
        $first = pos($list);
        $exists = key_exists('a', $list);
        $joined = join(',', $list);
        $part = strchr($string, 'a');
        $trimmed = chop($string);

        return [$count, $first, $exists, $joined, $part, $trimmed];
    }

    /**
     * Only plain and fully-qualified global calls should be touched.
     */
    public function namespacedFunctionForms(array $list): array
    {
        $plain = pos($list);
        $fullyQualified = \pos($list);
        $qualified = Foo\pos($list);
        $relative = namespace\pos($list);

        return [$plain, $fullyQualified, $qualified, $relative];
    }

    /**
     * Resource and runtime aliases.
     */
    public function runtime($handle, string $file): void
    {
        ini_alter('display_errors', '1');
        fputs($handle, 'foo');
        show_source($file);
        user_error('Something went wrong');
    }

    /**
     * Method calls, static calls, declarations and instantiations must not be touched.
     */
    public function chop(string $string): string
    {
        $a = $this->join(',', []);
        $b = static::sizeof([]);
        $c = new Join();
        $d = $this->pos;

        return $string . $a . $b . $c . $d;
    }
}
