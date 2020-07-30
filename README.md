# struggle-for-php/sfp-psalm-typed-local-variable-plugin

finding mismatch type assignment in function/method scope with psalm.

## disclaimer
This is VERY VERY VERY **Experimental** .

## Limitation

* NOT support global variables.
* NOT support variables in namespace. 

## Todo
 - [x] Support Closure scope isolated from function scope.
 - [ ] type comparison with psalm's TypeAnalyzer.
 - [ ] function/method parameter treat as local variable
 - [ ] ... and more tests.

## demo
```
./vendor/bin/psalm -c demo.psalm.xml
```

```
Scanning files...
Analyzing files...

E

ERROR: UnmatchedTypeIssue - demo/demo.php:8:18 - original types are int, but assigned types are string (see https://psalm.dev/000)
            $x = "a";


ERROR: UnmatchedTypeIssue - demo/demo.php:14:14 - original types are string, but assigned types are int (see https://psalm.dev/000)
        $x = 1;


ERROR: UnmatchedTypeIssue - demo/demo.php:21:28 - original types are null|string, but assigned types are int (see https://psalm.dev/000)
        $nullable_string = 3;


ERROR: UnmatchedTypeIssue - demo/demo.php:27:17 - original types are DateTimeImmutable, but assigned types are DateTime (see https://psalm.dev/000)
        $date = new \DateTime('now'); // error


------------------------------
4 errors found
------------------------------
```