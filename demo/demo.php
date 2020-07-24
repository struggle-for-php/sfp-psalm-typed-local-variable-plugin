<?php

namespace X {

    class Klass {
        public function method() : void {
            $x = 1;
            $x = "a";
        }
    }

    function foo() : void {
        /** @var string $x */
        $x = 1;
    }
}

namespace {
    static function () : void {
        /** @var ?string $nullable_string */
        $nullable_string = 3;

        /** @var DateTimeImmutable $d */
        $d = new \DateTimeImmutable('now');

        /** @var DateTimeImmutable $date */
        $date = new \DateTime('now');
    };
}



