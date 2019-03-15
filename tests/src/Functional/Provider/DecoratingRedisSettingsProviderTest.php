<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Functional\Provider;

use Harmony\Bundle\SettingsManagerBundle\Provider\DecoratingRedisSettingsProvider;
use Harmony\Bundle\SettingsManagerBundle\Provider\DoctrineOrmSettingsProvider;
use App\Entity\Setting;
use App\Entity\Tag;
use Harmony\Bundle\SettingsManagerBundle\Provider\SettingsProviderInterface;

class RedisDoctrineOrmSettingsProviderTest extends DecoratingPredisSettingsProviderTest
{
    protected function createProvider(): SettingsProviderInterface
    {
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('phpredis extension required');
        }

        $this->redis = new \Redis();

        if (@$this->redis->connect(getenv('REDIS_HOST'), (int) getenv('REDIS_PORT'), 1.0) === false) {
            $this->markTestSkipped('Running redis server required');
        }

        $container = $this->getContainer();

        return new DecoratingRedisSettingsProvider(
            new DoctrineOrmSettingsProvider(
                $container->get('doctrine.orm.default_entity_manager'),
                Setting::class,
                Tag::class
            ),
            $this->redis,
            $container->get('test.settings_manager.serializer')
        );
    }
}
