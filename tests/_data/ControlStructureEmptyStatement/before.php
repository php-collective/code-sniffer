<?php

// Empty if statement
if ($foo);

// Empty foreach statement
foreach ($items as $item);

// Empty while statement
while ($condition);

// Empty for statement
for ($i = 0; $i < 10; $i++);

// Empty elseif statement
if ($bar) {
    echo "ok";
} elseif ($baz);
else {
    echo "else";
}

// These are OK - have actual bodies
if ($test) {
    echo "good";
}

foreach ($array as $value) {
    echo $value;
}

// do-while loops are OK - semicolon is required syntax
do {
    echo "something";
} while ($condition);

do {
    $lastRoot = $root;
    $root = dirname($root);
    if (is_dir($root . '/vendor/cakephp/cakephp')) {
        return $root;
    }
} while ($root !== $lastRoot);

// Empty do-while is also valid syntax
do {
} while ($x++);

// More complex do-while
do {
    echo "loop";
    break;
} while (true);

// Nested do-while
do {
    do {
        echo "nested";
    } while ($inner);
} while ($outer);

// Edge case: standalone while with semicolon (should be flagged)
while ($anotherCondition);
