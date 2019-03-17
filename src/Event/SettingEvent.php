<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Event;

use Harmony\Bundle\SettingsManagerBundle\Model\Setting;
use Symfony\Component\EventDispatcher\Event;

class SettingEvent extends Event
{
    protected $setting;

    public function __construct(Setting $setting)
    {
        $this->setting = $setting;
    }

    public function getSetting(): Setting
    {
        return $this->setting;
    }
}
