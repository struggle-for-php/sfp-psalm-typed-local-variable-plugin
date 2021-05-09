# sfp-psalm-typed-local-variable-plugin

finding mismatch type assignment in function/method scope with [psalm](https://psalm.dev/).

[![Packagist](https://img.shields.io/packagist/v/struggle-for-php/sfp-psalm-typed-local-variable-plugin.svg)](https://packagist.org/packages/struggle-for-php/sfp-psalm-typed-local-variable-plugin)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fstruggle-for-php%2Fsfp-psalm-typed-local-variable-plugin%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/struggle-for-php/sfp-psalm-typed-local-variable-plugin/master)
[![Psalm coverage](https://shepherd.dev/github/struggle-for-php/sfp-psalm-typed-local-variable-plugin/coverage.svg?)](https://shepherd.dev/github/struggle-for-php/sfp-psalm-typed-local-variable-plugin)


## Disclaimer
This is **Experimental** plugin.

## Demo

```php
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
    private $repository;

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
        $entity = $this->repository->findOneById(1); // ERROR
    }

    function works_with_intersection() : void
    {
        /** @var \DateTimeInterface&Mock $date */
        $date = new \DateTime('now'); // ERROR
        $date = date_mock(); // success
    }
}
```


```bash
$ ./vendor/bin/psalm -c demo.psalm.xml
Scanning files...
Analyzing files...

E

ERROR: InvalidScalarTypedLocalVariableIssue - demo/demo.php:23:28 - Type true should be a subtype of null|string
        $nullable_string = true; // ERROR


ERROR: InvalidTypedLocalVariableIssue - demo/demo.php:30:21 - Type DateTime should be a subtype of DateTimeImmutable
            $date = new \DateTime('tomorrow'); // ERROR


ERROR: InvalidScalarTypedLocalVariableIssue - demo/demo.php:35:17 - Type 1 should be a subtype of bool
        $bool = 1; // ERROR


ERROR: InvalidTypedLocalVariableIssue - demo/demo.php:41:19 - Type Entity|null should be a subtype of Entity
        $entity = $this->repository->findOneById(1); // ERROR


ERROR: InvalidTypedLocalVariableIssue - demo/demo.php:47:17 - Type DateTime should be a subtype of DateTimeInterface&Mock
        $date = new \DateTime('now'); // ERROR


------------------------------
5 errors found
------------------------------
```

## Limitation

* NOT support global variables.
* NOT support variables in namespace.
* NOT support [Variable variables](https://php.net/language.variables.variable)
* Non-each inline VariableReference.
  * eg.
```php
/** @var string $var1 */
/** @var bool $var2 */
$var1 = 'string'; // cannot determine type for $var1

// should fix like below
/** @var string $var1 */
$var1 = 'string';
/** @var bool $var2 */
$var2 = true;
```

## Installation
```
$ composer require --dev struggle-for-php/sfp-psalm-typed-local-variable-plugin
$ vendor/bin/psalm-plugin enable struggle-for-php/sfp-psalm-typed-local-variable-plugin
```

## Todo
- [ ] optional setting for only from_docblock typed.