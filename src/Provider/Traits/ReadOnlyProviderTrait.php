<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider\Traits;

use Harmony\Bundle\SettingsManagerBundle\Exception\ReadOnlyProviderException;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;

trait ReadOnlyProviderTrait
{
    public function isReadOnly(): bool
    {
        return true;
    }

    public function save(Setting $settingModel): bool
    {
        throw new ReadOnlyProviderException(get_class($this));
    }

    public function delete(Setting $settingModel): bool
    {
        throw new ReadOnlyProviderException(get_class($this));
    }

    public function updateDomain(SettingDomain $domainModel): bool
    {
        throw new ReadOnlyProviderException(get_class($this));
    }

    public function deleteDomain(string $domainName): bool
    {
        throw new ReadOnlyProviderException(get_class($this));
    }
}
