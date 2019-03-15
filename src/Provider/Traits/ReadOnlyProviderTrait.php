<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider\Traits;

use Harmony\Bundle\SettingsManagerBundle\Exception\ReadOnlyProviderException;
use Harmony\Bundle\SettingsManagerBundle\Model\DomainModel;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingModel;

trait ReadOnlyProviderTrait
{
    public function isReadOnly(): bool
    {
        return true;
    }

    public function save(SettingModel $settingModel): bool
    {
        throw new ReadOnlyProviderException(get_class($this));
    }

    public function delete(SettingModel $settingModel): bool
    {
        throw new ReadOnlyProviderException(get_class($this));
    }

    public function updateDomain(DomainModel $domainModel): bool
    {
        throw new ReadOnlyProviderException(get_class($this));
    }

    public function deleteDomain(string $domainName): bool
    {
        throw new ReadOnlyProviderException(get_class($this));
    }
}
