<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Provider\Traits;

trait WritableProviderTrait
{
    public function isReadOnly(): bool
    {
        return false;
    }
}
