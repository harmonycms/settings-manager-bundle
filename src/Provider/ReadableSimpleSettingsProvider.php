<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider;

use Harmony\Bundle\SettingsManagerBundle\Provider\Traits\ReadOnlyProviderTrait;

class ReadableSimpleSettingsProvider extends SimpleSettingsProvider
{
    use ReadOnlyProviderTrait;
}
