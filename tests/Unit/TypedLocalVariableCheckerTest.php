<?php

namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;

use Psalm\IssueBuffer;

final class TypedLocalVariableCheckerTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function definedVariableByDocCommentShouldCheckedWhenAssigned() : void
    {
        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
function func () : void {
    /** @var int|bool $x */
    $x = "string";
    /** @var mixed $mixed */
    $mixed = "string";
    $mixed = 0;
}
CODE
        );
        $this->analyzeFile(__METHOD__,  new \Psalm\Context());
        $this->assertSame(1, IssueBuffer::getErrorCount());
    }

    /**
     * @test
     */
    public function assignedVariableShouldCheckTypeUnmatchedWhenReAssigned() : void
    {

        $this->addFile(
            __METHOD__,
            <<<'CODE'
<?php
function func () : void {
    $x = "s";
    $x = "string2";    
    $x = false;
}
CODE
        );
        $this->analyzeFile(__METHOD__,  new \Psalm\Context());

        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$x = false;', trim($issue->snippet));
    }

    /**
     * @test
     */
    public function typeCheckObject()
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
        $this->analyzeFile(__METHOD__,  new \Psalm\Context());
        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$date = new \DateTime(\'now\');', trim($issue->snippet));
    }

    /**
     * @test
     */
    public function castAssignmentExpression() : void 
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
        $this->analyzeFile(__METHOD__,  new \Psalm\Context());
        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$x = 1;', trim($issue->snippet));
    }

    /**
     * @test
     */
    public function returnTypeShouldCheckAsAssignmentType() : void
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
        $this->analyzeFile(__METHOD__,  new \Psalm\Context());
        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$x = "a";', trim($issue->snippet));
    }
}
