<?php

declare(strict_types=1);

namespace App\DataFixtures\ORM;

use App\Entity\Setting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Harmony\Bundle\SettingsManagerBundle\Model\DomainModel;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;

class LoadSwitchableCommandData extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $domain = new DomainModel();
        $domain->setName('default');

        $setting = new Setting();
        $setting
            ->setName('switchable_command_enabled')
            ->setDescription('Enables switchable:print command')
            ->setType(Type::BOOL())
            ->setDomain($domain)
            ->setData(true);

        $manager->persist($setting);
        $manager->flush();
    }
}
