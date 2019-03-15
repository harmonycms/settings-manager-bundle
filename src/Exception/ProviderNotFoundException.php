<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Exception;

class ProviderNotFoundException extends \LogicException implements SettingsException
{
    public function __construct(string $providerName)
    {
        parent::__construct("Settings provider named '{$providerName}' not found");
    }
}
