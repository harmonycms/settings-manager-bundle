<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle;

/**
 * Class SettingsManagerEvents
 *
 * @package Harmony\Bundle\SettingsManagerBundle
 */
final class SettingsManagerEvents
{

    /**
     * Provides an ability to modify or extend menu.
     * Event class \Harmony\Bundle\SettingsManagerBundle\Event\ConfigureMenuEvent
     */
    public const CONFIGURE_MENU = 'settings_manager.configure_menu';

    /**
     * Provides an ability to modify setting just before fetch.
     * Event class \Harmony\Bundle\SettingsManagerBundle\Event\GetSettingEvent
     */
    public const GET_SETTING = 'settings_manager.get_setting';

    /**
     * Provides an ability to modify setting just before putting it into form.
     * Could be used to modify choices
     * Event class \Harmony\Bundle\SettingsManagerBundle\Event\SettingChangeEvent
     */
    public const PRE_EDIT_SETTING = 'settings_manager.pre_edit_setting';

    /**
     * Provides an ability to know about saved setting.
     * Event class \Harmony\Bundle\SettingsManagerBundle\Event\SettingChangeEvent
     */
    public const SAVE_SETTING = 'settings_manager.save_setting';

    /**
     * Provides an ability to inform about setting deletion.
     * Event class \Harmony\Bundle\SettingsManagerBundle\Event\SettingChangeEvent
     */
    public const DELETE_SETTING = 'settings_manager.delete_setting';

    private function __construct()
    {
    }
}
