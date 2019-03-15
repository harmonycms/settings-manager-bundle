<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider\Factory;

use Harmony\Bundle\SettingsManagerBundle\Model\SettingModel;
use Harmony\Bundle\SettingsManagerBundle\Provider\ReadableSimpleSettingsProvider;
use Harmony\Bundle\SettingsManagerBundle\Provider\SettingsProviderInterface;
use Harmony\Bundle\SettingsManagerBundle\Provider\SimpleSettingsProvider;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class SimpleSettingsProviderFactory implements ProviderFactoryInterface
{
    private $serializer;
    private $normalizedData;
    private $readOnly;

    public function __construct(DenormalizerInterface $serializer, array $normalizedData, bool $readOnly = true)
    {
        $this->serializer = $serializer;
        $this->normalizedData = $normalizedData;
        $this->readOnly = $readOnly;
    }

    public function get(): SettingsProviderInterface
    {
        /** @var SettingModel[] $settings */
        $settings = $this->serializer->denormalize($this->normalizedData, SettingModel::class . '[]');

        if ($this->readOnly) {
            return new ReadableSimpleSettingsProvider($settings);
        }

        return new SimpleSettingsProvider($settings);
    }
}
