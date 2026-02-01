<?php

declare(strict_types=1);

namespace PhpCollective;

class PipeOperatorExample
{
    public function testPipeOperator(): string
    {
        $input = '  Hello World  ';

        // Correct usage
        $output = $input
            |> trim(...)
            |> strtolower(...);

        // Missing spaces
        $bad1 = $input |> trim(...) |> strtolower(...);

        // Extra spaces before |
        $bad2 = $input |> trim(...);

        // Extra spaces after >
        $bad3 = $input |> trim(...);

        // Combination of issues
        $bad4 = $input |> trim(...) |> strtolower(...);

        return $output;
    }
}
