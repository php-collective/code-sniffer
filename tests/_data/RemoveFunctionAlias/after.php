<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    /**
     * Type check aliases.
     */
    public function typeChecks($x): bool
    {
        return is_int($x) || is_int($x) || is_float($x) || is_float($x) || is_writable($x);
    }

    /**
     * Conversion alias deprecated in PHP 8.5.
     */
    public function conversion($x): float
    {
        return floatval($x);
    }

    /**
     * Array and string aliases.
     */
    public function arraysAndStrings(array $list, string $string)
    {
        $count = count($list);
        $first = current($list);
        $exists = array_key_exists('a', $list);
        $joined = implode(',', $list);
        $part = strstr($string, 'a');
        $trimmed = rtrim($string);

        return [$count, $first, $exists, $joined, $part, $trimmed];
    }

    /**
     * Resource and runtime aliases.
     */
    public function runtime($handle, string $file): void
    {
        ini_set('display_errors', '1');
        fwrite($handle, 'foo');
        highlight_file($file);
        trigger_error('Something went wrong');
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
