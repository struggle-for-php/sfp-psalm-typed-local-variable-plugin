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
}
CODE
        );
        $this->analyzeFile(__METHOD__, new Context());

        $this->assertSame(3, IssueBuffer::getErrorCount());
        $this->assertSame('$string = false;', trim(current(IssueBuffer::getIssuesData())[0]->snippet));
        $this->assertSame('$int = false;', trim(current(IssueBuffer::getIssuesData())[1]->snippet));
        $this->assertSame('$float = false;', trim(current(IssueBuffer::getIssuesData())[2]->snippet));
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
    $x = (bool) ( (bool) "string");
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
}
