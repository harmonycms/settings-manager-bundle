<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Twig;

use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsRouter;

class SettingsExtension extends \Twig_Extension
{
    private $settingsRouter;

    public function __construct(SettingsRouter $settingsRouter)
    {
        $this->settingsRouter = $settingsRouter;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('setting_get', [$this, 'getSetting']),
        ];
    }

    public function getSetting(string $settingName, $defaultValue = null)
    {
        return $this->settingsRouter->get($settingName, $defaultValue);
    }
}
