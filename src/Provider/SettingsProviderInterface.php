<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider;

use Harmony\Bundle\SettingsManagerBundle\Exception\ReadOnlyProviderException;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;

/**
 * Interface SettingsProviderInterface
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Provider
 */
interface SettingsProviderInterface
{

    /**
     *  Default provider name
     */
    public const DEFAULT_PROVIDER = 'config';

    /**
     * In almost every case settings manager can avoid calling this provider by readonly flag.
     * When settings manager is requested to do an update this flag is ignored on source provider.
     *
     * @return bool
     */
    public function isReadOnly(): bool;

    /**
     * Collects all settings based on given domains.
     *
     * @param string[] $domainNames Domains names to check
     *
     * @return Setting[]
     */
    public function getSettings(array $domainNames): array;

    /**
     * Returns setting by name.
     *
     * @param string[] $domainNames  Domains names to check
     * @param string[] $settingNames Settings to check in those domains
     *
     * @return Setting[]
     */
    public function getSettingsByName(array $domainNames, array $settingNames): array;

    /**
     * Saves setting model.
     * Settings manager can still try to call this method even if it's read only.
     * In case make sure it throws ReadOnlyProviderException.
     *
     * @param Setting $settingModel
     *
     * @return bool Status of save process
     * @throws ReadOnlyProviderException When provider is read only
     */
    public function save(Setting $settingModel): bool;

    /**
     * Removes setting from provider.
     *
     * @param Setting $settingModel
     *
     * @return bool
     */
    public function delete(Setting $settingModel): bool;

    /**
     * Collects all domain models.
     *
     * @param bool $onlyEnabled
     *
     * @return SettingDomain[]
     */
    public function getDomains(bool $onlyEnabled = false): array;

    /**
     * Updates domain model in provider.
     *
     * @param SettingDomain $domainModel
     *
     * @return bool
     */
    public function updateDomain(SettingDomain $domainModel): bool;

    /**
     * Removes domain and all settings associated with it.
     *
     * @param string $domainName
     *
     * @return bool
     */
    public function deleteDomain(string $domainName): bool;
}
