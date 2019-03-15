<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Tests\Unit\Provider;

use Harmony\Bundle\SettingsManagerBundle\Provider\AbstractCookieSettingsProvider;
use Harmony\Bundle\SettingsManagerBundle\Provider\AsymmetricCookieSettingsProvider;
use ParagonIE\Paseto\Protocol\Version2;

class AsymmetricCookieSettingsProviderTest extends AbstractCookieSettingsProviderTest
{
    private static $asymmetricKey = null;

    protected function createProvider(): AbstractCookieSettingsProvider
    {
        //make separate encoding and decoding tests reuse same key pair
        if (null === self::$asymmetricKey) {
            self::$asymmetricKey = Version2::generateAsymmetricSecretKey();
        }

        return new AsymmetricCookieSettingsProvider(
            $this->serializer,
            self::$asymmetricKey->raw(),
            self::$asymmetricKey->getPublicKey()->raw(),
            $this->cookieName
        );
    }
}
