<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider\Factory;

use Harmony\Bundle\SettingsManagerBundle\Provider\SettingsProviderInterface;

interface ProviderFactoryInterface
{
    public function get(): SettingsProviderInterface;
}
