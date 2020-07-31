<?php

declare(strict_types=1);

namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;

use Psalm\Context;
use Psalm\IssueBuffer;

use function file_get_contents;

final class DemoTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function assert_demo_script(): void
    {
        $this->addFile(__METHOD__, file_get_contents(__DIR__ . '/../../demo/demo.php'));

        $this->analyzeFile(__METHOD__, new Context());
        $this->assertSame(4, IssueBuffer::getErrorCount());
    }
}
