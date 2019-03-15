<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Settings\Switchable;

use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsRouter;

interface SwitchableCommandInterface
{
    public static function isCommandEnabled(SettingsRouter $router): bool;
}
