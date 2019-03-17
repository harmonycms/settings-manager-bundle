<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Functional\Provider;

use Harmony\Bundle\SettingsManagerBundle\Provider\DoctrineOrmSettingsProvider;
use Harmony\Bundle\SettingsManagerBundle\Provider\SettingsProviderInterface;
use Predis\Client;
use Predis\CommunicationException;
use Harmony\Bundle\SettingsManagerBundle\Provider\DecoratingPredisSettingsProvider;
use App\Entity\Setting;
use App\Entity\SettingTag;

class DecoratingPredisSettingsProviderTest extends AbstractSettingsProviderTest
{
    /**
     * @var Client|\Redis
     */
    protected $redis;

    protected function setUp()
    {
        $this->loadFixtures([]);

        parent::setUp();
    }

    protected function createProvider(): SettingsProviderInterface
    {
        $this->redis = new Client(
            ['host' => getenv('REDIS_HOST'), 'port' => getenv('REDIS_PORT')],
            ['parameters' => ['database' => 0, 'timeout' => 1.0]]
        );

        try {
            $this->redis->ping();
        } catch (CommunicationException $e) {
            $this->markTestSkipped('Running redis server required');
        }

        $container = $this->getContainer();

        return new DecoratingPredisSettingsProvider(
            new DoctrineOrmSettingsProvider(
                $container->get('doctrine.orm.default_entity_manager'),
                Setting::class,
                SettingTag::class
            ),
            $this->redis,
            $container->get('test.settings_manager.serializer')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->redis->flushdb();
        parent::tearDown();
    }
}
