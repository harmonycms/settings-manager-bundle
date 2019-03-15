<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Exception;

class ReadOnlyProviderException extends \LogicException implements SettingsException
{
    public function __construct(string $providerName)
    {
        parent::__construct($providerName . ' setting provider is read only');
    }
}
