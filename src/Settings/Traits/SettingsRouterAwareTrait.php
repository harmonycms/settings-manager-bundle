<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Settings\Traits;

use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsRouter;

trait SettingsRouterAwareTrait
{
    /**
     * @var SettingsRouter
     */
    protected $settingsRouter;

    /**
     * @required
     */
    public function setSettingsRouter(SettingsRouter $settingsRouter): void
    {
        $this->settingsRouter = $settingsRouter;
    }
}
