<?php

try {
    doThing();
} catch (ValueError|TypeError $e) {
    $x = $a | $b;
}
