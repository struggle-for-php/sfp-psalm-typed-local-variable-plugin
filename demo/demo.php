<?php

/** demonstration */

$non_docblock = "a"; // no error

/** @var string $x */
$x = "a";

/** @var ?string $nullable_string */
$nullable_string = null;

/** @var float $y */
$y = "a"; // error

/** @var DateTimeImmutable $d */
$d = new \DateTimeImmutable('now');

/** @var DateTimeImmutable $date */
$date = new \DateTime('now'); // error

