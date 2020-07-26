<?php
namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;

use Psalm\IssueBuffer;

final class DemoTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function assert_demo_script() : void
    {
        $this->addFile(__METHOD__, file_get_contents(__DIR__ . '/../../demo/demo.php'));

        $this->analyzeFile(__METHOD__,  new \Psalm\Context());
        $this->assertSame(4, IssueBuffer::getErrorCount());
        // todo more assertions
    }

}