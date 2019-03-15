<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Settings;

use Harmony\Bundle\SettingsManagerBundle\Event\SettingEvent;

interface EventManagerInterface
{
    public function dispatch(string $eventName, SettingEvent $event): void;
}
