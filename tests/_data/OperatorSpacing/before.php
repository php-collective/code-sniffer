<?php

declare(strict_types=1);

try {
    risky();
} catch (A|B $e) {
}

$value = $a|$b;
