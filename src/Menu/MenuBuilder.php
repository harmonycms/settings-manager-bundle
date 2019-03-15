<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Harmony\Bundle\SettingsManagerBundle\Event\ConfigureMenuEvent;
use Harmony\Bundle\SettingsManagerBundle\SettingsManagerEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MenuBuilder
{
    private $factory;
    private $eventDispatcher;

    public function __construct(FactoryInterface $factory, EventDispatcherInterface $eventDispatcher)
    {
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createTopMenu(array $options): ItemInterface
    {
        $menu = $this->factory->createItem('root');
        $menu
            ->addChild('navbar.settings_list', ['route' => 'settings_index'])
            ->setExtra('translation_domain', 'HarmonySettingsManager');
        $menu
            ->addChild('navbar.domain_list', ['route' => 'settings_domain_index'])
            ->setExtra('translation_domain', 'HarmonySettingsManager');

        $this->eventDispatcher->dispatch(
            SettingsManagerEvents::CONFIGURE_MENU,
            new ConfigureMenuEvent($this->factory, $menu)
        );

        return $menu;
    }
}
