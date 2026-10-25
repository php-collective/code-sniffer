<?php

try {
    risky();
} catch (A | B $e) {
} catch (A | B | C) {
} catch (A|B $e) {
} catch (\Example\A | \Example\B $e) {
} catch (A /* note */ | B $e) {
} catch (A/* note */|B $e) {
} catch (A
    | B $e) {
} catch (A |
    B $e) {
}

$value = $a|$b;
