<?php

declare(strict_types=1);

/*
 * Verifies that every approach within a benchmark group produces the same PHP
 * result for the same input, so the benchmarks compare like with like.
 *
 * Usage: php bin/verify.php   (exits non-zero if any group is inconsistent)
 */

use Automapper\Bench\Verifier;

require __DIR__ . '/../vendor/autoload.php';

$groups = [
    'denormalize' => Verifier::denormalizeResults(...),
    'normalize' => Verifier::normalizeResults(...),
    'deserialize' => Verifier::deserializeResults(...),
    'serialize' => Verifier::serializeResults(...),
    'object-to-object' => Verifier::objectToObjectResults(...),
    'collection' => Verifier::collectionResults(...),
];

$exit = 0;

foreach ($groups as $group => $build) {
    $results = $build();
    $reference = null;
    $referenceName = null;

    echo "\n== {$group} ==\n";

    foreach ($results as $name => $value) {
        if (null === $reference) {
            $reference = $value;
            $referenceName = $name;
            echo \sprintf("  ref  %s\n", $name);

            continue;
        }

        if ($value === $reference) {
            echo \sprintf("  ok   %s\n", $name);
        } else {
            $exit = 1;
            echo \sprintf("  DIFF %s  (does not match %s)\n", $name, $referenceName);
            echo \sprintf("       expected: %s\n", json_encode($reference));
            echo \sprintf("       actual:   %s\n", json_encode($value));
        }
    }
}

echo "\n" . (0 === $exit ? "All groups consistent.\n" : "Inconsistencies found.\n");

exit($exit);
