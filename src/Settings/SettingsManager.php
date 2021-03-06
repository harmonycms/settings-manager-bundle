<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Settings;

use Harmony\Bundle\SettingsManagerBundle\Event\SettingChangeEvent;
use Harmony\Bundle\SettingsManagerBundle\Exception\ProviderNotFoundException;
use Harmony\Bundle\SettingsManagerBundle\Exception\ReadOnlyProviderException;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Harmony\Bundle\SettingsManagerBundle\Provider\SettingsProviderInterface;
use Harmony\Bundle\SettingsManagerBundle\SettingsManagerEvents;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class SettingsManager
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Settings
 */
class SettingsManager implements LoggerAwareInterface
{

    use LoggerAwareTrait;

    /**
     * @var SettingsProviderInterface[]
     */
    private $providers;

    /**
     * @var EventManagerInterface
     */
    private $eventManager;

    /**
     * @param SettingsProviderInterface[] $providers
     * @param EventManagerInterface       $eventManager
     */
    public function __construct(array $providers, EventManagerInterface $eventManager)
    {
        $this->providers    = $providers;
        $this->eventManager = $eventManager;
    }

    /**
     * @return SettingsProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get a single setting from a domain (optional).
     *
     * @param string $name
     * @param string $domain
     *
     * @return Setting|mixed
     */
    public function getSetting(string $name, string $domain = 'default')
    {
        // Only 1 value by domain can exists
        $settings = $this->getSettingsByName([$domain], [$name]);

        // Get the first and unique value of array
        return array_shift($settings);
    }

    /**
     * @param null|string $providerName
     * @param bool        $onlyEnabled
     *
     * @return SettingDomain[]
     */
    public function getDomains(string $providerName = null, bool $onlyEnabled = false): array
    {
        $domains   = [];
        $providers = $providerName !== null ? [$providerName => $this->getProvider($providerName)] : $this->providers;

        foreach ($providers as $provider) {
            foreach ($provider->getDomains($onlyEnabled) as $domainModel) {
                $domains[$domainModel->getName()][$domainModel->getPriority()] = $domainModel;
            }
        }

        foreach ($domains as &$domainGroup) {
            $domainGroup = $domainGroup[max(array_keys($domainGroup))];
        }

        return $domains;
    }

    /**
     * @param string[] $domainNames
     * @param string[] $settingNames
     *
     * @return Setting[]
     */
    public function getSettingsByName(array $domainNames, array $settingNames): array
    {
        $settings = [[]];

        /** @var SettingsProviderInterface $provider */
        foreach (array_reverse($this->providers) as $pName => $provider) {
            $providerSettings = [];
            foreach ($provider->getSettingsByName($domainNames, $settingNames) as $settingModel) {
                if ($settingModel instanceof Setting) {
                    $settingModel->setProviderName($pName);
                    $providerSettings[] = $settingModel;
                    unset($settingNames[array_search($settingModel->getName(), $settingNames, true)]);
                } else {
                    $this->logger && $this->logger->warning('SettingsManager: received null setting', [
                        'sProviderName' => $pName,
                        'sSettingName'  => $settingNames,
                    ]);
                }
            }

            $settings[] = $providerSettings;

            // check if already has enough
            if (count($settingNames) === 0) {
                break;
            }
        }

        return array_merge(...$settings);
    }

    /**
     * @param string[] $domainNames
     *
     * @return Setting[]
     */
    public function getSettingsByDomain(array $domainNames): array
    {
        $settings = [[]];

        foreach ($this->providers as $pName => $provider) {
            $providerSettings = [];
            foreach ($provider->getSettings($domainNames) as $settingModel) {
                $settingModel->setProviderName($pName);
                $providerSettings[$settingModel->getName()] = $settingModel;
            }

            $settings[] = $providerSettings;
        }

        return array_replace(...$settings);
    }

    /**
     * @param string[] $domainNames
     * @param string   $tagName
     *
     * @return Setting[]
     */
    public function getEnabledSettingsByTag(array $domainNames, string $tagName): array
    {
        $settings = [[]];

        foreach ($this->providers as $pName => $provider) {
            $providerSettings = [];
            foreach ($provider->getSettings($domainNames) as $settingModel) {
                if ($settingModel->hasTag($tagName)) {
                    $settingModel->setProviderName($pName);
                    $providerSettings[$settingModel->getName()] = $settingModel;
                }
            }

            $settings[] = $providerSettings;
        }

        return array_replace(...$settings);
    }

    /**
     * Tries to update an existing provider or saves to a new provider.
     *
     * @param Setting $settingModel
     *
     * @return bool
     */
    public function save(Setting $settingModel): bool
    {
        if ($settingModel->getProviderName()) {
            try {
                $result = $this->providers[$settingModel->getProviderName()]->save($settingModel);
            }
            catch (ReadOnlyProviderException $e) {
                $result = false;
            }

            if ($result === true) {
                $this->logger && $this->logger->info('SettingsManager: setting updated', [
                    'sSettingName'   => $settingModel->getName(),
                    'sSettingType'   => $settingModel->getType()->getValue(),
                    'sSettingValue'  => json_encode($settingModel->getDataValue()),
                    'sDomainName'    => $settingModel->getDomain()->getName(),
                    'sDomainEnabled' => $settingModel->getDomain()->isReadOnly(),
                    'sProviderName'  => $settingModel->getProviderName(),
                ]);
                $this->eventManager->dispatch(SettingsManagerEvents::SAVE_SETTING,
                    new SettingChangeEvent($settingModel));

                return $result;
            }
        }

        $closed = $settingModel->getProviderName() !== null;

        foreach ($this->providers as $name => $provider) {
            if ($closed) {
                if ($settingModel->getProviderName() === $name) {
                    $closed = false;
                } else {
                    continue;
                }
            }

            try {
                if (!$provider->isReadOnly() && $provider->save($settingModel) !== false) {
                    $this->logger && $this->logger->info('SettingsManager: setting saved', [
                        'sSettingName'   => $settingModel->getName(),
                        'sSettingType'   => $settingModel->getType()->getValue(),
                        'sSettingValue'  => json_encode($settingModel->getDataValue()),
                        'sDomainName'    => $settingModel->getDomain()->getName(),
                        'sDomainEnabled' => $settingModel->getDomain()->isReadOnly(),
                        'sProviderName'  => $settingModel->getProviderName(),
                    ]);
                    $this->eventManager->dispatch(SettingsManagerEvents::SAVE_SETTING,
                        new SettingChangeEvent($settingModel));

                    return true;
                }
            }
            catch (ReadOnlyProviderException $e) {
                // go to next provider
            }
        }

        return false;
    }

    /**
     * @deprecated use save()
     *
     * @param Setting $settingModel
     *
     * @return bool
     */
    public function update(Setting $settingModel): bool
    {
        return $this->save($settingModel);
    }

    /**
     * @param Setting $settingModel
     *
     * @return bool
     */
    public function delete(Setting $settingModel): bool
    {
        $changed = false;

        if ($settingModel->getProviderName()) {
            $changed = $this->providers[$settingModel->getProviderName()]->delete($settingModel);
        } else {
            foreach ($this->providers as $provider) {
                if ($provider->delete($settingModel)) {
                    $changed = true;
                }
            }
        }

        if ($changed) {
            $this->eventManager->dispatch(SettingsManagerEvents::DELETE_SETTING, new SettingChangeEvent($settingModel));
        }

        return $changed;
    }

    /**
     * Saves settings from domain to specific provider. Mostly used for setting population.
     *
     * @param string $domainName
     * @param string $providerName
     */
    public function copyDomainToProvider(string $domainName, string $providerName): void
    {
        $provider = $this->getProvider($providerName);
        $settings = $this->getSettingsByDomain([$domainName]);

        foreach ($settings as $setting) {
            $provider->save($setting);
        }

        $this->logger && $this->logger->info('SettingsManager: domain copied', [
            'sDomainName'   => $domainName,
            'sProviderName' => $providerName,
        ]);
    }

    /**
     * @param SettingDomain $domainModel
     * @param null|string   $providerName
     */
    public function updateDomain(SettingDomain $domainModel, string $providerName = null): void
    {
        if ($providerName !== null) {
            $provider = $this->getProvider($providerName);
            $provider->updateDomain($domainModel);
        } else {
            foreach ($this->providers as $provider) {
                if (!$provider->isReadOnly()) {
                    $provider->updateDomain($domainModel);
                }
            }
        }

        $this->logger && $this->logger->info('SettingsManager: domain updated', [
            'sProviderName'   => $providerName,
            'sDomainName'     => $domainModel->getName(),
            'bDomainEnabled'  => $domainModel->isEnabled(),
            'iDomainPriority' => $domainModel->getPriority(),
        ]);
    }

    /**
     * @param string      $domainName
     * @param null|string $providerName
     */
    public function deleteDomain(string $domainName, string $providerName = null): void
    {
        if ($providerName !== null) {
            $provider = $this->getProvider($providerName);
            $provider->deleteDomain($domainName);
        } else {
            foreach ($this->providers as $provider) {
                if (!$provider->isReadOnly()) {
                    $provider->deleteDomain($domainName);
                }
            }
        }

        $this->logger && $this->logger->info('SettingsManager: domain deleted', [
            'sProviderName' => $providerName,
            'sDomainName'   => $domainName,
        ]);
    }

    public function getProvider(string $providerName): SettingsProviderInterface
    {
        if (!isset($this->providers[$providerName])) {
            throw new ProviderNotFoundException($providerName);
        }

        return $this->providers[$providerName];
    }
}
