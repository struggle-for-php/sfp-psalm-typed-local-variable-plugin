<?php

namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;


use Psalm\Exception\CodeException;
use Psalm\IssueBuffer;

class TypedLocalVariableCheckerTest extends AbstractTestCase
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
    $x = bool;
};
CODE
        );
        $this->analyzeFile(__METHOD__,  new \Psalm\Context());
//        $this->assertSame(1, IssueBuffer::getErrorCount());
    }

}
