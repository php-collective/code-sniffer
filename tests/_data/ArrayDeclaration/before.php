<?php declare(strict_types = 1);

namespace PhpCollective;

class FixMe
{
    public function test(): void
    {
        $x = [
            'a' => 'b' . 'c',
            'x' => [
                'y' => 'z', 'z' => 'a',
            ],
            'c' => __FILE__, 'd' => Xyz::class, 'content' => $this->getContent($tokens, $i, $tagEnd),
        ];

        // Test case for nested arrays - should NOT be flagged
        $config = [
            'levels' => ['notice', 'info', 'debug'],
            'other' => 'value',
        ];

        // Multiple items with nested arrays - SHOULD be flagged
        $multi = [
            'first' => ['a', 'b'], 'second' => ['c', 'd'],
        ];

        // Mixed associative and non-associative items - SHOULD be flagged
        $url = [
            'controller' => 'ControllerName',
            'action' => 'view', $uuid,
            '?' => ['pdf' => 1],
        ];
    }
}
