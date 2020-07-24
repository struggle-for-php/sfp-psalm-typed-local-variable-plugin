<?php

namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;


use Psalm\Exception\CodeException;

class TypedLocalVariableCheckerTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function definedVariableByDocCommentShouldCheckedWhenAssigned() : void
    {
        $this->expectException(CodeException::class);
        $this->expectExceptionMessage('UnmatchedTypeIssue');

        $this->addFile(
            'somefile.php',
            <<<'CODE'
<?php
function () : void {
    /** @var int $x */
    $x = false;
};
CODE
        );
        $this->analyzeFile('somefile.php',  new \Psalm\Context());
    }

}
