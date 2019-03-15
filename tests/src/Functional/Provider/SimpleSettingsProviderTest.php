<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Functional\Provider;

use Harmony\Bundle\SettingsManagerBundle\Provider\SettingsProviderInterface;
use Harmony\Bundle\SettingsManagerBundle\Provider\SimpleSettingsProvider;

class SimpleSettingsProviderTest extends AbstractSettingsProviderTest
{
    protected function createProvider(): SettingsProviderInterface
    {
        return new SimpleSettingsProvider();
    }
}
