<?php
namespace X {

    interface Mock{}

    /** @return \DateTimeInterface&Mock */
    function date_mock() {
        return new class('now') extends \DateTime implements Mock{};
    };

    class Klass {
        public function method() : void {
            /** @var string|null $nullable_string */
            $nullable_string = null;
            $nullable_string = "a";
            $nullable_string = true; // error

            $bool = true; //direct typed without doc-block
            $bool = 1; //error

            /** @var \DateTimeInterface&Mock $intersection_type */
            $intersection_type = new \DateTime('now'); // error
            $intersection_type = date_mock();

            (static function(): void {
                /** @var \DateTimeImmutable $date  */
                $date = new \DateTimeImmutable('now');

                if (rand() % 2 === 0) {
                    $date = new \DateTime('tomorrow'); // error
                }
            })();
        }
    }
}
