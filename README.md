# struggle-for-php/sfp-psalm-typed-local-variable-plugin

finding mismatch type assignment in function/method scope with psalm.

[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fstruggle-for-php%2Fsfp-psalm-typed-local-variable-plugin%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/struggle-for-php/sfp-psalm-typed-local-variable-plugin/master)
[![Psalm coverage](https://shepherd.dev/github/struggle-for-php/sfp-psalm-typed-local-variable-plugin/coverage.svg?)](https://shepherd.dev/github/struggle-for-php/sfp-psalm-typed-local-variable-plugin)


## disclaimer
This is VERY VERY **Experimental** .

## Limitation

* NOT support global variables.
* NOT support variables in namespace. 

## demo

```php
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
```


```bash
$ ./vendor/bin/psalm -c demo.psalm.xml
Scanning files...
Analyzing files...

E

ERROR: InvalidScalarTypedLocalVariableIssue - demo/demo.php:16:32 - Type true should be a subtype of null|string (see https://psalm.dev/000)
            $nullable_string = true; // error


ERROR: InvalidScalarTypedLocalVariableIssue - demo/demo.php:19:21 - Type int(1) should be a subtype of true (see https://psalm.dev/000)
            $bool = 1; //error


ERROR: InvalidTypedLocalVariableIssue - demo/demo.php:22:34 - Type DateTime should be a subtype of DateTimeInterface&X\Mock (see https://psalm.dev/000)
            $intersection_type = new \DateTime('now'); // error


ERROR: InvalidTypedLocalVariableIssue - demo/demo.php:30:29 - Type DateTime should be a subtype of DateTimeImmutable (see https://psalm.dev/000)
                    $date = new \DateTime('tomorrow'); // error


------------------------------
4 errors found
------------------------------

Checks took 17.10 seconds and used 194.889MB of memory
Psalm was able to infer types for 100% of the codebase
```