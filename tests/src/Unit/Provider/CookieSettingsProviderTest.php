<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Unit\Provider;

use Harmony\Bundle\SettingsManagerBundle\Provider\AbstractCookieSettingsProvider;
use Harmony\Bundle\SettingsManagerBundle\Provider\CookieSettingsProvider;

class CookieSettingsProviderTest extends AbstractCookieSettingsProviderTest
{
    protected function createProvider(): AbstractCookieSettingsProvider
    {
        return new CookieSettingsProvider($this->serializer, 'YELLOW SUBMARINE, BLACK WIZARDRY', $this->cookieName);
    }
}
