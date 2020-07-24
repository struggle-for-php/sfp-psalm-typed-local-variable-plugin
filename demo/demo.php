<?php

static function () : void {

    $non_docblock = "a";

    /** @var string $x */
    $x = "a";
    $x = 1;

    /** @var ?string $nullable_string */
    $nullable_string = 3;
};


/** @var float $y */
$y = "a"; // error

///** @var DateTimeImmutable $d */
//$d = new \DateTimeImmutable('now');
//
///** @var DateTimeImmutable $date */
//$date = new \DateTime('now'); // error

