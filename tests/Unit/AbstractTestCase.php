<?php

declare(strict_types=1);

namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\Providers;
use Psalm\IssueBuffer;
use RuntimeException;
use SfpTest\Psalm\TypedLocalVariablePlugin\Unit\Internal\Provider;

use function define;
use function defined;
use function getcwd;
use function ini_set;
use function method_exists;

use const DIRECTORY_SEPARATOR;

/**
 * borrowed from psalm
 */
abstract class AbstractTestCase extends BaseTestCase
{
    protected static string $src_dir_path;

    protected ProjectAnalyzer $project_analyzer;

    protected Provider\FakeFileProvider $file_provider;

    public static function setUpBeforeClass(): void
    {
        ini_set('memory_limit', '-1');

        if (! defined('PSALM_VERSION')) {
            define('PSALM_VERSION', '2.0.0');
        }

        if (! defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', '4.0.0');
        }

        parent::setUpBeforeClass();
        self::$src_dir_path = getcwd() . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;
    }

    protected function makeConfig(): Config
    {
        return new TestConfig();
    }

    public function setUp(): void
    {
        parent::setUp();

        FileAnalyzer::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();

        $config = $this->makeConfig();

        $providers = new Providers(
            $this->file_provider,
            new Provider\FakeParserCacheProvider()
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers
        );

        $this->project_analyzer->setPhpVersion('7.3');
        $config->initializePlugins($this->project_analyzer);

        IssueBuffer::clear();
    }

    public function tearDown(): void
    {
        FileAnalyzer::clearCache();
    }

    public function addFile(string $file_path, string $contents): void
    {
        $this->file_provider->registerFile($file_path, $contents);
        $this->project_analyzer->getCodebase()->scanner->addFileToShallowScan($file_path);
    }

    public function analyzeFile(string $file_path, Context $context, bool $track_unused_suppressions = true): void
    {
        $codebase = $this->project_analyzer->getCodebase();
        $codebase->addFilesToAnalyze([$file_path => $file_path]);
        // $codebase->find_unused_code = 'always';

        $codebase->scanFiles();

        $codebase->config->visitStubFiles($codebase);

        if ($codebase->alter_code) {
            $this->project_analyzer->interpretRefactors();
        }

        $this->project_analyzer->trackUnusedSuppressions();


        $file_analyzer = new FileAnalyzer(
            $this->project_analyzer,
            $file_path,
            $codebase->config->shortenFileName($file_path)
        );
        $file_analyzer->analyze($context);

        if ($codebase->taint) {
            $codebase->taint->connectSinksAndSources();
        }

        if (! $track_unused_suppressions) {
            return;
        }

        IssueBuffer::processUnusedSuppressions($codebase->file_provider);
    }

    protected function getTestName(bool $withDataSet = true): string
    {
        $name = parent::getName($withDataSet);
        /**
         * @psalm-suppress TypeDoesNotContainNull PHPUnit 8.2 made it non-nullable again
         */
        if ($name === null) {
            throw new RuntimeException('anonymous test - shouldn\'t happen');
        }

        return $name;
    }

    /**
     * Compatibility alias
     */
    public function expectExceptionMessageRegExp(string $regexp): void
    {
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches($regexp);
        } else {
            /** @psalm-suppress UndefinedMethod */
            parent::expectExceptionMessageRegExp($regexp);
        }
    }

    public static function assertRegExp(string $pattern, string $string, string $message = ''): void
    {
        if (method_exists(self::class, 'assertMatchesRegularExpression')) {
            self::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            parent::assertRegExp($pattern, $string, $message);
        }
    }
}
