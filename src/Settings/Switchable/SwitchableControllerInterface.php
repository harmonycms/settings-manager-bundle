<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Settings\Switchable;

use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsRouter;

interface SwitchableControllerInterface
{
    public static function isControllerEnabled(SettingsRouter $router): bool;
}
