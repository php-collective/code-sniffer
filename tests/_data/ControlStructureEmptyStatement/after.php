<?php

// Empty if statement
if ($foo) {
}

// Empty foreach statement
foreach ($items as $item) {
}

// Empty while statement
while ($condition) {
}

// Empty for statement
for ($i = 0; $i < 10; $i++) {
}

// Empty elseif statement
if ($bar) {
    echo "ok";
} elseif ($baz) {
}
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
