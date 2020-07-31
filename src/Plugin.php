<?php

declare(strict_types=1);

namespace Sfp\Psalm\TypedLocalVariablePlugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

class Plugin implements PluginEntryPointInterface
{
    /** @inheritDoc */
    public function __invoke(RegistrationInterface $psalm, ?SimpleXMLElement $config = null)
    {
        require_once __DIR__ . '/TypedLocalVariableChecker.php';
        $psalm->registerHooksFromClass(TypedLocalVariableChecker::class);
    }
}
