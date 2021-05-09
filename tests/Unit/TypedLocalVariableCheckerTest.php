<?php

declare(strict_types=1);

namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;

use Psalm\Context;
use Psalm\IssueBuffer;

use function current;
use function trim;

final class TypedLocalVariableCheckerTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function definedVariableByDocCommentShouldCheckedWhenAssigned(): void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
function func () : void {
    /** @var ?string $nullable */
    $nullable = null;
    $nullable = 'foo';
    $nullable = false;
    /** @var int|bool $union */
    $union = 1;
    $union = true;
    $union = "string";
    /** @var array{date: DateTimeInterface} $array_shape */
    $array_shape = ['date' => new DateTime('now')];
    $array_shape = ['date' => 'not DateTimeInterface obj'];
    /** @var mixed $mixed */
    $mixed = "string";
    $mixed = 0;
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());

        $this->assertSame('$nullable = false;', trim(current(IssueBuffer::getIssuesData())[0]->snippet));
        $this->assertSame('$union = "string";', trim(current(IssueBuffer::getIssuesData())[1]->snippet));
        $this->assertSame('$array_shape = [\'date\' => \'not DateTimeInterface obj\'];', trim(current(IssueBuffer::getIssuesData())[2]->snippet));
    }

    /**
     * @test
     */
    public function assignedVariableShouldCheckTypeUnmatchedWhenReAssigned(): void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
function func () : void {
    $string = "string";
    $string = "string2";
    $string = false;
    $int = 1;
    $int = 2;
    $int = false;
    $float = 0.1;
    $float = 0.2;
    // $float = 1; // allowed
    $float = false;
    $bool = false;
    $bool = true;
    $bool = 1;
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());

        $this->assertSame(4, IssueBuffer::getErrorCount());
        $this->assertSame('$string = false;', trim(current(IssueBuffer::getIssuesData())[0]->snippet));
        $this->assertSame('$int = false;', trim(current(IssueBuffer::getIssuesData())[1]->snippet));
        $this->assertSame('$float = false;', trim(current(IssueBuffer::getIssuesData())[2]->snippet));
        $this->assertSame('$bool = 1;', trim(current(IssueBuffer::getIssuesData())[3]->snippet));
    }

    /**
     * @test
     */
    public function typeCheckObject(): void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
function func () : void {
    $date = new \DateTimeImmutable('now');
    $date = new \DateTime('now');
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());
        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$date = new \DateTime(\'now\');', trim($issue->snippet));
    }

    /**
     * @test
     */
    public function castAssignmentExpression(): void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
function func () : void {
    $x = (bool) true;
    $x = 1;
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());

        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$x = 1;', trim($issue->snippet));
    }

    /**
     * @test
     */
    public function returnTypeShouldCheckAsAssignmentType(): void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
function func () : void {
    $x = time();
    $x = "a";
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());
        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$x = "a";', trim($issue->snippet));
    }

    /**
     * @test
     */
    public function paramsAsLocalVariable(): void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
/**
 * @param int $param_typed_docblock
 */
function func (int $param, $param_typed_docblock) : void {
    $param = "string";
    $param_typed_docblock = true;
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());
        $this->assertSame(2, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$param = "string";', trim($issue->snippet));
        $issue = current(IssueBuffer::getIssuesData())[1];
        $this->assertSame('$param_typed_docblock = true;', trim($issue->snippet));
    }

    /**
     * @test
     */
    public function nestedStmts(): void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
function func () : void {
    $date = new \DateTimeImmutable('now');
    if (rand() % 2 === 0) {
        if (rand() % 3 === 0) {
            $date = new \DateTime("tomorrow");
        }
    }
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());
        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$date = new \DateTime("tomorrow");', trim($issue->snippet));
    }

    /**
     * @test
     */
    public function mixedTypeCoercion(): void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
function foo(array $a) : void {
    /** @var string[] $x */
    $x = $a;
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('MixedTypeCoercionTypedLocalVariableIssue', $issue->type);
    }

    /**
     * @test
     */
    public function typeCoercion(): void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
class A {}
class B extends A {}

function takesA(A $a) : void {
    /** @var B $b */
    $b = $a;
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('TypeCoercionTypedLocalVariableIssue', $issue->type);
    }


    /**
     * @test
     */
    public function psalmType() : void
    {
        $this->addFile(__METHOD__, <<<'CODE'
<?php
/**
 * @psalm-type TSpecialType = bool|int
 */
class A
{
    public function method(): void
    {
        /** @var TSpecialType $x */
        $x = "a";
    }
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());
        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('InvalidScalarTypedLocalVariableIssue', $issue->type);
    }
}
