<?php

declare(strict_types=1);

namespace Sfp\Psalm\TypedLocalVariablePlugin;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null) : void
    {
        require_once __DIR__ . '/TypedLocalVariableChecker.php';
        $registration->registerHooksFromClass(TypedLocalVariableChecker::class);
    }
}
