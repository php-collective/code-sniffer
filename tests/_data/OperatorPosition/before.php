<?php

$and = $a &&
    $b;
$or = $a ||
    $b;
$logicalAnd = $a and
    $b;
$logicalOr = $a or
    $b;
$logicalXor = $a xor
    $b;
$coalesce = $a ??
    $b;
$ternary = $a ?
    $b :
    $c;
$short = $a ?: 
    $b;
$shortSpaced = $a ? :
    $b;
$nested = $a ?
    ($b ?
        $c :
        $d) :
    $e;
$plus = $a +
    $b;
$minus = $a -
    $b;
$multiply = $a *
    $b;
$divide = $a /
    $b;
$modulus = $a %
    $b;
$power = $a **
    $b;
if ($a &&
    $b) {
    consume($a ||
        $b);
}
returnValue($a +
    $b);
$array = [$a ??
    $b];
$unary = -1;
$unary = +1;
$unary = [-1, +1];
$unary = (-1);
$unary = [1, -1];
$unary = $a +
    -$b;
$correct = $a
    && $b;
$comment = $a && // Keep this comment.
    $b;
function nullable(?string $value): ?string
{
    return $value;
}
function named(string $value): string
{
    return $value;
}
function returned(int $a, int $b): int
{
    return $a +
        $b;
}
function returnedUnary(int $a): int
{
    return -$a;
}
$assignment = $a =
    $b;
$comparison = $a ==
    $b;
$bitwise = $a |
    $b;
named(value: 'test');
switch ($a) {
    case 1:
        break;
    default:
        break;
}
$match = match ($a) {
    1 => 'one',
    default => 'other',
};
$splitShort = $a ?
    : $b;
