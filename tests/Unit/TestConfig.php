<?php

namespace SfpTest\Psalm\TypedLocalVariablePlugin\Unit;

use Sfp\Psalm\TypedLocalVariablePlugin\Plugin;
use const DIRECTORY_SEPARATOR;
use function getcwd;
use Psalm\Config;

/**
 * borrowed from psalm 3.12.0 (not HEAD)
 * https://github.com/vimeo/psalm/blob/3.12.0/tests/TestConfig.php
 * @see https://github.com/vimeo/psalm/pull/3183
 */
final class TestConfig extends Config
{
    /** @var Config\ProjectFileFilter|null */
    private static $cached_project_files = null;

    /**
     * @psalm-suppress PossiblyNullPropertyAssignmentValue because cache_directory isn't strictly nullable
     */
    public function __construct()
    {
        parent::__construct();
        $this->addPluginClass(Plugin::class);

        $this->throw_exception = false;
        $this->use_docblock_types = true;
        $this->level = 1;
        $this->cache_directory = null;

        $this->base_dir = getcwd() . DIRECTORY_SEPARATOR;


        if (!self::$cached_project_files) {
            self::$cached_project_files = Config\ProjectFileFilter::loadFromXMLElement(
                new \SimpleXMLElement($this->getContents()),
                $this->base_dir,
                true
            );
        }

        $this->project_files = self::$cached_project_files;

        $this->collectPredefinedConstants();
        $this->collectPredefinedFunctions();

    }

    protected function getContents() : string
    {
        return <<<'EOF'
<?xml version="1.0"?>
<psalm>
    <projectFiles>
        <directory name="src" />
    </projectFiles>
    <plugins>
        <pluginClass class="Sfp\Psalm\TypedLocalVariablePlugin\Plugin" />
    </plugins>
</psalm>
EOF;
    }

    public function getComposerFilePathForClassLike($fq_classlike_name)
    {
        return false;
    }

    public function getProjectDirectories()
    {
        return [];
    }
}