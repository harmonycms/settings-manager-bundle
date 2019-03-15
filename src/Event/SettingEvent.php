<?php
declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Event;

use Harmony\Bundle\SettingsManagerBundle\Model\SettingModel;
use Symfony\Component\EventDispatcher\Event;

class SettingEvent extends Event
{
    protected $setting;

    public function __construct(SettingModel $setting)
    {
        $this->setting = $setting;
    }

    public function getSetting(): SettingModel
    {
        return $this->setting;
    }
}
