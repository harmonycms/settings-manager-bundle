<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Unit\Validator\Constraints;

use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsManager;
use Harmony\Bundle\SettingsManagerBundle\Validator\Constraints\UniqueSetting;
use Harmony\Bundle\SettingsManagerBundle\Validator\Constraints\UniqueSettingValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueSettingValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var MockObject|SettingsManager
     */
    protected $settingsManager;

    protected function createValidator()
    {
        $this->settingsManager = $this->createMock(SettingsManager::class);

        return new UniqueSettingValidator($this->settingsManager);
    }

    public function testValid()
    {
        $this
            ->settingsManager
            ->expects($this->once())
            ->method('getSettingsByName')
            ->with(['dc'], ['batman'])
            ->willReturn([]);

        $setting = new Setting();
        $setting->setName('batman');
        $setting->setDomain((new SettingDomain())->setName('dc'));

        $this->validator->validate($setting, new UniqueSetting());
        $this->assertNoViolation();
    }

    public function testInvalid()
    {
        $setting = new Setting();
        $setting->setName('batman');
        $setting->setDomain((new SettingDomain())->setName('dc'));

        $this
            ->settingsManager
            ->expects($this->once())
            ->method('getSettingsByName')
            ->with(['dc'], ['batman'])
            ->willReturn([$setting]);

        $this->validator->validate($setting, new UniqueSetting());
        $this
            ->buildViolation('{{ domainName }} domain already has setting named {{ settingName }}')
            ->setParameter('{{ domainName }}', 'dc')
            ->setParameter('{{ settingName }}', 'batman')
            ->assertRaised();
    }
}
