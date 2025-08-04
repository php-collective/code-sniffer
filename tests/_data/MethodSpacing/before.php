<?php

class Test
{
    public function foo()
    {

        return 'bar';
    }

    public function bar()
    {
        return 'baz';

    }

    public function getMonthDifference($from, $to)
    {
        $diff = date_diff($from, $to);

        return (12 * $diff['y']) + $diff['m'];

    }
}