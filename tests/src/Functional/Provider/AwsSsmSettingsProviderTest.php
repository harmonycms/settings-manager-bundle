<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Functional\Provider;

use Aws\Result;
use Aws\Ssm\SsmClient;
use Harmony\Bundle\SettingsManagerBundle\Exception\ReadOnlyProviderException;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;
use Harmony\Bundle\SettingsManagerBundle\Provider\AwsSsmSettingsProvider;
use Harmony\Bundle\SettingsManagerBundle\Serializer\Normalizer\SettingDomainNormalizer;
use Harmony\Bundle\SettingsManagerBundle\Serializer\Normalizer\SettingNormalizer;
use Harmony\Bundle\SettingsManagerBundle\Serializer\Normalizer\SettingTagNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;

class AwsSsmSettingsProviderTest extends TestCase
{
    /**
     * @var SsmClient|MockObject
     */
    private $awsSsmClientMock;

    /**
     * @var Serializer
     */
    private $serializer;

    protected function setUp()
    {
        $this->awsSsmClientMock = $this->createPartialMock(SsmClient::class, ['getParameters', 'putParameter']);
        $this->serializer = new Serializer(
            [
                new ArrayDenormalizer(),
                new SettingNormalizer(),
                new SettingDomainNormalizer(),
                new SettingTagNormalizer(),
            ],
            [
                new JsonEncoder(),
            ]
        );
    }

    public function testGetSettingsWithNoParameters(): void
    {
        $settingsProvider = $this->createSettingsProvider([]);

        $awsResult = $this->createConfiguredMock(
            Result::class,
            [
                'get' => [],
            ]
        );

        $this->awsSsmClientMock
            ->method('getParameters')
            ->willReturnMap([
                [['Names' => []], $awsResult],
            ]);

        /** @var Setting[] $settings */
        $settings = $settingsProvider->getSettings([SettingDomain::DEFAULT_NAME]);

        $this->assertCount(0, $settings);
    }

    public function testGetSettingsWithSingleParameter(): void
    {
        $settingsProvider = $this->createSettingsProvider(['parameter_a']);

        $awsResult = $this->createConfiguredMock(
            Result::class,
            [
                'get' => [
                    [
                        'Name'  => 'Parameter A Name',
                        'Value' => 'a_value',
                    ],
                ],
            ]
        );

        $this->awsSsmClientMock
            ->method('getParameters')
            ->willReturnMap([
                [['Names' => ['parameter_a']], $awsResult],
            ]);

        /** @var Setting[] $settings */
        $settings = $settingsProvider->getSettings([SettingDomain::DEFAULT_NAME]);

        $this->assertCount(1, $settings);

        $this->assertSame('Parameter A Name', $settings[0]->getName());
        $this->assertSame(SettingDomain::DEFAULT_NAME, $settings[0]->getDomain()->getName());
        $this->assertSame(Type::STRING, $settings[0]->getType()->getValue());
        $this->assertSame('a_value', $settings[0]->getData());
    }

    public function testGetSettingsWithMultipleParameters(): void
    {
        $settingsProvider = $this->createSettingsProvider(['parameter_a', 'parameter_b', 'parameter_c', 'parameter_d']);

        $awsResult = $this->createConfiguredMock(
            Result::class,
            [
                'get' => [
                    // Not json encoded, bc compatiblity
                    [
                        'Name'  => 'Parameter A Name',
                        'Value' => 'a_value',
                    ],
                    [
                        'Name'  => 'Parameter B Name',
                        'Value' => 'b_value',
                    ],
                    // Serialized values
                    [
                        'Name'  => 'Parameter C Name',
                        'Value' => '"c_value"',
                    ],
                    [
                        'Name'  => 'Parameter D Name',
                        'Value' => '["parameter_d_1","parameter_d_2","parameter_d_3"]',
                    ],
                ],
            ]
        );

        $this->awsSsmClientMock
            ->method('getParameters')
            ->willReturnMap([
                [['Names' => ['parameter_a', 'parameter_b', 'parameter_c', 'parameter_d']], $awsResult],
            ]);

        /** @var Setting[] $settings */
        $settings = $settingsProvider->getSettings([SettingDomain::DEFAULT_NAME]);

        $this->assertCount(4, $settings);

        $this->assertSame('Parameter A Name', $settings[0]->getName());
        $this->assertSame(SettingDomain::DEFAULT_NAME, $settings[0]->getDomain()->getName());
        $this->assertSame(Type::STRING, $settings[0]->getType()->getValue());
        $this->assertSame('a_value', $settings[0]->getData());

        $this->assertSame('Parameter B Name', $settings[1]->getName());
        $this->assertSame(SettingDomain::DEFAULT_NAME, $settings[1]->getDomain()->getName());
        $this->assertSame(Type::STRING, $settings[1]->getType()->getValue());
        $this->assertSame('b_value', $settings[1]->getData());

        $this->assertSame('Parameter C Name', $settings[2]->getName());
        $this->assertSame(SettingDomain::DEFAULT_NAME, $settings[2]->getDomain()->getName());
        $this->assertSame(Type::STRING, $settings[2]->getType()->getValue());
        $this->assertSame('c_value', $settings[2]->getData());

        $this->assertSame('Parameter D Name', $settings[3]->getName());
        $this->assertSame(SettingDomain::DEFAULT_NAME, $settings[3]->getDomain()->getName());
        $this->assertSame(Type::YAML, $settings[3]->getType()->getValue());
        $this->assertSame(['parameter_d_1', 'parameter_d_2', 'parameter_d_3'], $settings[3]->getData());
    }

    public function testGetSettingsMultipleTimesFetchesOnlyOnce(): void
    {
        $settingsProvider = $this->createSettingsProvider([]);

        $awsResult = $this->createConfiguredMock(
            Result::class,
            [
                'get' => [],
            ]
        );

        $this->awsSsmClientMock
            ->expects($this->exactly(2))
            ->method('getParameters')
            ->willReturnMap([
                [['Names' => []], $awsResult],
            ]);

        $settingsProvider->getSettings([SettingDomain::DEFAULT_NAME]);
        $settingsProvider->getSettings([SettingDomain::DEFAULT_NAME]);
    }

    public function testSave(): void
    {
        $settingsProvider = $this->createSettingsProvider(['Parameter A Name']);

        $setting = (new Setting())
            ->setName('Parameter A Name')
            ->setData('a_value');

        $this->awsSsmClientMock
            ->expects($this->once())
            ->method('putParameter')
            ->with(
                [
                    'Name'      => 'Parameter A Name',
                    'Overwrite' => true,
                    'Type'      => 'String',
                    'Value'     => '"a_value"',
                ]
            );

        $settingsProvider->save($setting);
    }

    public function testInvalidSave(): void
    {
        $this->expectException(ReadOnlyProviderException::class);
        $this->expectExceptionMessage('Harmony\Bundle\SettingsManagerBundle\Provider\AwsSsmSettingsProvider setting provider is read only');

        $settingsProvider = $this->createSettingsProvider(['Parameter A Name']);

        $setting = (new Setting())
            ->setName('Invalid parameter A Name')
            ->setData('a_value');

        $this->awsSsmClientMock
            ->expects($this->never())
            ->method('putParameter');

        $settingsProvider->save($setting);
    }

    private function createSettingsProvider(array $parameterNames): AwsSsmSettingsProvider
    {
        return new AwsSsmSettingsProvider(
            $this->awsSsmClientMock,
            $this->serializer,
            $parameterNames
        );
    }
}
