<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider;

use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class LazyReadableSimpleSettingsProvider extends ReadableSimpleSettingsProvider
{
    private $serializer;
    private $normSettingsByDomain;
    private $normDomains;

    private $modelSettingsByDomain;
    private $modelDomains;
    private $modelDomainsEnabled;

    public function __construct(
        DenormalizerInterface $serializer,
        array $normSettingsByDomain,
        array $normDomains
    ) {
        parent::__construct([]);

        $this->serializer = $serializer;
        $this->normSettingsByDomain = $normSettingsByDomain;
        $this->normDomains = $normDomains;

        $this->modelSettingsByDomain = [];
        $this->modelDomains = [];
        $this->modelDomainsEnabled = [];
    }

    public function getSettings(array $domainNames): array
    {
        $out = [];

        foreach ($domainNames as $domainName) {
            if (isset($this->modelSettingsByDomain[$domainName])) {
                // has some models
                if (count($this->modelSettingsByDomain[$domainName])
                    !== count($this->normSettingsByDomain[$domainName])
                ) {
                    // denormalize missing models
                    $missingSettings = array_diff_key(
                        $this->normSettingsByDomain[$domainName],
                        $this->modelSettingsByDomain[$domainName]
                    );
                    $this->modelSettingsByDomain[$domainName] = array_replace(
                        $this->modelSettingsByDomain[$domainName],
                        $this->serializer->denormalize($missingSettings, Setting::class . '[]')
                    );
                }

                $out = array_merge($out, array_values($this->modelSettingsByDomain[$domainName]));
            } elseif (isset($this->normSettingsByDomain[$domainName])) {
                // has normalized models
                $this->modelSettingsByDomain[$domainName] = $this
                    ->serializer
                    ->denormalize($this->normSettingsByDomain[$domainName], Setting::class . '[]');
                $out = array_merge($out, array_values($this->modelSettingsByDomain[$domainName]));
            }
        }

        return $out;
    }

    public function getSettingsByName(array $domainNames, array $settingNames): array
    {
        $out = [];

        foreach ($domainNames as $domainName) {
            foreach ($settingNames as $settingName) {
                if (isset($this->modelSettingsByDomain[$domainName][$settingName])) {
                    // already has a model
                    $out[] = $this->modelSettingsByDomain[$domainName][$settingName];
                } elseif (isset($this->normSettingsByDomain[$domainName][$settingName])) {
                    // normalized data exists, make a model
                    $out[]
                        = $this->modelSettingsByDomain[$domainName][$settingName]
                        = $this->serializer->denormalize($this->normSettingsByDomain[$domainName][$settingName], Setting::class);
                }
            }
        }

        return $out;
    }

    public function getDomains(bool $onlyEnabled = false): array
    {
        if (count($this->normDomains) > 0 && count($this->modelDomains) === 0) {
            foreach ($this->normDomains as $normDomain) {
                /** @var SettingDomain $model */
                $model = $this->serializer->denormalize($normDomain, SettingDomain::class);
                $this->modelDomains[] = $model;
                $model->isEnabled() && ($this->modelDomainsEnabled[] = $model);
            }
        }

        return $onlyEnabled ? $this->modelDomainsEnabled : $this->modelDomains;
    }
}
