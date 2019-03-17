<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider;

use Aws\Ssm\SsmClient;
use Harmony\Bundle\SettingsManagerBundle\Exception\ReadOnlyProviderException;
use Harmony\Bundle\SettingsManagerBundle\Exception\UnknownTypeException;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;
use Harmony\Bundle\SettingsManagerBundle\Provider\Traits\ReadOnlyProviderTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class AwsSsmSettingsProvider extends SimpleSettingsProvider
{
    private const TYPE_MAP = [
        'double' => Type::FLOAT,
        'boolean' => Type::BOOL,
        'array' => Type::YAML,
        'integer' => Type::INT,
        'string' => Type::STRING,
        'choice' => Type::CHOICE,
    ];

    use ReadOnlyProviderTrait;

    private $ssmClient;
    private $denormalizer;
    private $parameterNames;

    public function __construct(SsmClient $ssmClient, DenormalizerInterface $denormalizer, array $parameterNames)
    {
        parent::__construct([]);

        $this->ssmClient = $ssmClient;
        $this->denormalizer = $denormalizer;
        $this->parameterNames = $parameterNames;
    }

    public function getSettings(array $domainNames): array
    {
        $this->fetch();

        return parent::getSettings($domainNames);
    }

    public function getSettingsByName(array $domainNames, array $settingNames): array
    {
        $this->fetch();

        return parent::getSettingsByName($domainNames, $settingNames);
    }

    public function getDomains(bool $onlyEnabled = false): array
    {
        $this->fetch();

        return parent::getDomains($onlyEnabled);
    }

    public function save(Setting $settingModel): bool
    {
        if (!in_array($settingModel->getName(), $this->parameterNames)) {
            throw new ReadOnlyProviderException(get_class($this));
        }

        $this->ssmClient->putParameter([
            'Name' => $settingModel->getName(),
            'Overwrite' => true,
            'Type' => 'String',
            'Value' => json_encode($settingModel->getData()),
        ]);

        return parent::save($settingModel);
    }

    private function fetch(): void
    {
        $result = $this->ssmClient->getParameters(['Names' => $this->parameterNames]);
        foreach ($result->get('Parameters') as $parameter) {
            $value = @json_decode($parameter['Value'], true);
            if ($value === null) {
                $value = $parameter['Value'];
            }

            $setting = $this->denormalizer->denormalize(
                [
                    'name' => $parameter['Name'],
                    'domain' => [
                        'name' => SettingDomain::DEFAULT_NAME,
                        'enabled' => true,
                    ],
                    'type' => $this->resolveType($value),
                    'data' => [
                        'value' => $value,
                    ],
                ],
                Setting::class
            );
            $this->settings[] = $setting;
        }
    }

    private function resolveType($value): string
    {
        $type = gettype($value);

        if (isset(self::TYPE_MAP[$type])) {
            return self::TYPE_MAP[$type];
        }

        throw new UnknownTypeException($type);
    }
}
