<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Functional\Provider;

use App\Entity\Setting;
use App\Entity\Tag;
use Harmony\Bundle\SettingsManagerBundle\Provider\DoctrineOrmSettingsProvider;
use Harmony\Bundle\SettingsManagerBundle\Provider\SettingsProviderInterface;

class DoctrineOrmSettingsProviderTest extends AbstractSettingsProviderTest
{
    protected function setUp()
    {
        $this->loadFixtures([]);

        parent::setUp();
    }

    protected function createProvider(): SettingsProviderInterface
    {
        return new DoctrineOrmSettingsProvider(
            $this->getContainer()->get('doctrine.orm.default_entity_manager'),
            Setting::class,
            Tag::class
        );
    }
}
