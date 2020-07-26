<?php
namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;


use Psalm\IssueBuffer;

final class TypedLocalVariableChecker_ScopeTest extends AbstractTestCase
{

    /**
     * @test
     */
    public function closureVariableNamesAreSeparatedScope() : void
    {
        $this->addFile(
            __METHOD__,


            <<<'CODE'
<?php
class Klass {
    function func () : void {
        $var = 'var';
        $closure = function () : string {
            $d = 3;$d = 2;
            function () : void {
                /** @var int $x */
                $x = 1;
                $y = 3;
                function () : void {};
                $y = 4;
            };
            function () : void {
                /** @var bool $x */
                $l = true;
            };
            $e = 3;
            
            return 'string';
        };
        $var = 3;
    }
}
CODE
        );
        $this->analyzeFile(__METHOD__,  new \Psalm\Context());
//        $this->assertSame(0, IssueBuffer::getErrorCount());

        var_dump(IssueBuffer::getIssuesData());
    }
}