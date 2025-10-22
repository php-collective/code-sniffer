<?php

/**
 * Test file for ControlSignatureSniff - BEFORE auto-fixing
 */

// Test case 1: else on separate line
if (isset($_COOKIE['lastProfileLeft']) && $_COOKIE['lastProfileLeft'] != '0') {
    $lastProfileLeft = $_COOKIE['lastProfileLeft'];
} else {
    $lastProfileLeft = 0;
}

// Test case 2: Multiple elseif on separate lines
if ($rating >= 2000) {
    $level = 'Expert';
} elseif ($rating >= 1800) {
    $level = 'Advanced';
} elseif ($rating >= 1600) {
    $level = 'Intermediate';
} else {
    $level = 'Beginner';
}

// Test case 3: catch on separate line
try {
    throw new \Exception('Test');
} catch (\Exception $e) {
    echo $e->getMessage();
}

// Test case 4: finally on separate line (without catch)
try {
    $result = someFunctionCall();
} catch (\RuntimeException $e) {
    log($e);
} finally {
    cleanup();
}

// Test case 5: Already correct (should not trigger error)
if ($x > 0) {
    echo 'positive';
} else {
    echo 'non-positive';
}
