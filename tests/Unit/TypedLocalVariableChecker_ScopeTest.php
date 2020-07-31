<?php
namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;


use Psalm\IssueBuffer;

final class TypedLocalVariableChecker_ScopeTest extends AbstractTestCase
{

    /**
     * @test
     */
    public function closureVariablesAreSeparatedScope() : void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
class Klass {
    function func () : void {
        $closure = function () : string {
            $d = 3;
            $x = new \DateTime('now');
            function () : void {
                /** @var int $x */
                $x = 1;
                $y = 3;
                function () : void {};
                $x = 'foo';
                $y = 4;
            };
            function () : void {
                $x = true;
            };
            $x = 'bar';
            
            return 'string';
        };
    }
}
function () : void {
    $x = 'baz';
};
CODE
        );
        $this->analyzeFile(__METHOD__,  new \Psalm\Context());
        $this->assertSame(2, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$x = \'foo\';', trim($issue->snippet));

        $issue = current(IssueBuffer::getIssuesData())[1];
        $this->assertSame('$x = \'bar\';', trim($issue->snippet));
    }

    /**
     * @test
     */
    public function classMethodVariablesAreSeparatedScope() : void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
class A {
    /** @var int */
    private $prop;
    public function __construct()
    {
        $this->prop = 3;
    }

    public function func() : void {
        /** @var int $x */
        $x = true;
    }
}
class B {
    public function func() : void {
        /** @var bool $x */
        $x = null;
    }
}
CODE
        );

        $this->analyzeFile(__METHOD__,  new \Psalm\Context());

        $this->assertSame(2, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$x = true;', trim($issue->snippet));
        $issue = current(IssueBuffer::getIssuesData())[1];
        $this->assertSame('$x = null;', trim($issue->snippet));
    }

    /**
     * @test
     */
    public function anonymousClassMethodVariablesAreSeparatedScope() : void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
function func () {
    new class {
        public function func() : void {
            /** @var int $x */
            $x = true;
        }
    };
    new class {
        public function func() : void {
            /** @var bool $x */
            $x = null;
        }
    };
}
CODE
        );

        $this->analyzeFile(__METHOD__,  new \Psalm\Context());
        $this->assertSame(2, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$x = true;', trim($issue->snippet));
        $issue = current(IssueBuffer::getIssuesData())[1];
        $this->assertSame('$x = null;', trim($issue->snippet));
    }
}