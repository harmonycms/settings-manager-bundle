<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Settings;

use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Harmony\Bundle\SettingsManagerBundle\Provider\SettingsProviderInterface;
use Harmony\Bundle\SettingsManagerBundle\Settings\Traits\DomainNameExtractTrait;

class ProvidersManager
{
    use DomainNameExtractTrait;

    /**
     * @var SettingsManager
     */
    private $settingsManager;

    public function __construct(SettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    /**
     * @param string[] $targetProviders
     * @param string[] $domains
     */
    public function warmUpProviders(string $sourceProvider, array $targetProviders, array $domains): void
    {
        $sourceSettings = $this->getSourceSettings($sourceProvider, $domains);

        foreach ($this->settingsManager->getProviders() as $name => $provider) {
            if (!\in_array($name, $targetProviders, true)) {
                continue;
            }

            $this->warmUpProvider($provider, $sourceSettings);
        }
    }

    /**
     * @param string[] $domains
     *
     * @return Setting[]
     */
    private function getSourceSettings(string $provider, array $domains): array
    {
        $configProvider = $this->settingsManager->getProvider($provider);

        if (empty($domains)) {
            $domainNames = $this->extractDomainNames($configProvider->getDomains());
        }

        return $configProvider->getSettings($domainNames ?? $domains);
    }

    /**
     * @param Setting[] $sourceSettings
     */
    private function warmUpProvider(SettingsProviderInterface $provider, array $sourceSettings): void
    {
        $domainNames = $this->extractDomainNames($provider->getDomains());
        $settings = $provider->getSettings($domainNames);

        $missingSettings = $this->getDiff($sourceSettings, $settings);

        if (empty($missingSettings)) {
            return;
        }

        foreach ($missingSettings as $settings) {
            $provider->save($settings);
        }
    }

    /**
     * @param Setting[] $sourceSettings
     * @param Setting[] $settings
     *
     * @return Setting[]
     */
    private function getDiff(array $sourceSettings, array $settings): array
    {
        $diff = [];
        foreach ($sourceSettings as $a) {
            $found = false;
            foreach ($settings as $b) {
                if ($a->getName() === $b->getName() && $a->getDomain()->getName() === $b->getDomain()->getName()) {
                    $found = true;
                    break;
                }
            }
            if ($found === false) {
                $diff[] = $a;
            }
        }

        return $diff;
    }
}
