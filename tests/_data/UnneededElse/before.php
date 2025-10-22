<?php

namespace Test;

class UnneededElseExample
{
    /**
     * Simple if-else with return - should fix
     */
    public function simpleReturn($condition)
    {
        if ($condition) {
            return 'foo';
        } else {
            $bar = 'baz';
            echo $bar;
        }
    }

    /**
     * if-elseif-else with all returns - should fix
     */
    public function allReturns($value)
    {
        if ($value === 1) {
            return 'one';
        } elseif ($value === 2) {
            return 'two';
        } else {
            $default = 'other';
            return $default;
        }
    }

    /**
     * if without return - should NOT fix
     * This is the critical test case to prevent the behavior-changing bug
     */
    public function noReturnInFirstBranch($id, $referer)
    {
        if ($id > 0 && $referer === '/admin') {
            $value = true;
        } elseif ($id > 0) {
            $value = false;

            return redirect();
        } else {
            error('Problem');

            return redirect();
        }

        autoRender(false);
    }

    /**
     * if-else with throw - should fix
     */
    public function withThrow($data)
    {
        if (!$data) {
            throw new \Exception('No data');
        } else {
            $processed = process($data);
            return $processed;
        }
    }

    /**
     * if-else with exit - should fix
     */
    public function withExit($valid)
    {
        if (!$valid) {
            exit('Invalid');
        } else {
            $result = 'valid';
            echo $result;
        }
    }

    /**
     * if-else with break - should fix (in loop context)
     */
    public function withBreak(array $items)
    {
        foreach ($items as $item) {
            if ($item === 'stop') {
                break;
            } else {
                $processed = process($item);
                echo $processed;
            }
        }
    }

    /**
     * if-else with continue - should fix (in loop context)
     */
    public function withContinue(array $items)
    {
        foreach ($items as $item) {
            if ($item === 'skip') {
                continue;
            } else {
                $processed = process($item);
                echo $processed;
            }
        }
    }

    /**
     * Multiple statements in else - should fix and preserve all
     */
    public function multipleStatements($condition)
    {
        if ($condition) {
            return 'early';
        } else {
            $a = 1;
            $b = 2;
            $c = $a + $b;
            echo $c;
        }
    }

    /**
     * Nested if-else - outer should fix
     */
    public function nestedStructure($outer, $inner)
    {
        if ($outer) {
            return 'outer';
        } else {
            if ($inner) {
                $x = 'inner';
            } else {
                $x = 'default';
            }
            echo $x;
        }
    }
}
