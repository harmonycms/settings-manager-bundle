<?php

declare(strict_types=1);

namespace App\DataFixtures\ORM;

use App\Entity\Setting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Harmony\Bundle\SettingsManagerBundle\Model\SettingDomain;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;

class LoadSwitchableControllerData extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $domain = new SettingDomain();
        $domain->setName('default');

        $setting = new Setting();
        $setting
            ->setName('switchable_controller_enabled')
            ->setDescription('Enables switchable controller')
            ->setType(Type::BOOL())
            ->setDomain($domain)
            ->setData(true);

        $manager->persist($setting);
        $manager->flush();
    }
}
