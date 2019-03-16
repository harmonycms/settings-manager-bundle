<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Twig;

use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsRouter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class SettingsExtension
 *
 * @package Harmony\Bundle\SettingsManagerBundle\Twig
 */
class SettingsExtension extends AbstractExtension
{

    /** @var SettingsRouter $settingsRouter */
    private $settingsRouter;

    /**
     * SettingsExtension constructor.
     *
     * @param SettingsRouter $settingsRouter
     */
    public function __construct(SettingsRouter $settingsRouter)
    {
        $this->settingsRouter = $settingsRouter;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('setting_get', [$this, 'getSetting']),
        ];
    }

    /**
     * @param string $settingName
     * @param null   $defaultValue
     *
     * @return mixed
     */
    public function getSetting(string $settingName, $defaultValue = null)
    {
        return $this->settingsRouter->get($settingName, $defaultValue);
    }
}
