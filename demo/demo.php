<?php
class Entity{}
interface Repository
{
    public function findOneById(int $id): ?Entity;
}
interface Mock{}
/** @return \DateTimeInterface&Mock */
function date_mock() {
    return new class('now') extends \DateTime implements Mock{};
}

class Demo
{
    /** @var Repository */
    private $respository;

    function typed_by_phpdoc() : void
    {
        /** @var string|null $nullable_string */
        $nullable_string = null;
        $nullable_string = "a";
        $nullable_string = true; // ERROR
    }

    function typed_by_assignement() : void
    {
        $date = new \DateTimeImmutable('now');
        if (\rand() % 2 === 0) {
            $date = new \DateTime('tomorrow'); // ERROR
        }

        $bool = true; //direct typed without doc-block
        $bool = false; // ok (currently, this plugin treats true|false as bool)
        $bool = 1; // ERROR
    }

    function mismatch_by_return() : void
    {
        /** @var Entity $entity */
        $entity = $this->respository->findOneById(1); // ERROR
    }

    function works_with_intersection() : void
    {
        /** @var \DateTimeInterface&Mock $date */
        $date = new \DateTime('now'); // ERROR
        $date = date_mock(); // success
    }
}

