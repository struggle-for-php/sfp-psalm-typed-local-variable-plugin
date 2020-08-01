<?php


namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;


use Psalm\Context;
use Psalm\IssueBuffer;

final class TypedLocalVariableCheckerIgnoreCaseTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function ignoreVariableVar() : void
    {
        $this->addFile(__METHOD__, <<<'CODE'
<?php
function (): void {
    $var = 'var';
    ${$var} = 1;

    $var_name = 'bar';
    ${$var_name} = true;
    $bar = 1;
};
CODE
);
        $this->analyzeFile(__METHOD__, new Context());
        $this->assertSame(0, IssueBuffer::getErrorCount());
    }
}