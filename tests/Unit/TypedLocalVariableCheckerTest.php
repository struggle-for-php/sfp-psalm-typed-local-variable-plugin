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
function () : void {
    /** @var int|bool $x */
    $x = "string";
};
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
function () : void {
    $x = "string";
    $x = "string2";    
    $x = false;
};
CODE
        );
        $this->analyzeFile(__METHOD__,  new \Psalm\Context());
        $this->assertSame(1, IssueBuffer::getErrorCount());
        $issue = current(IssueBuffer::getIssuesData())[0];
        $this->assertSame('$x = false;', trim($issue->snippet));
        $this->assertSame('UnmatchedTypeIssue', $issue->type);
    }
}
