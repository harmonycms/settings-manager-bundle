<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\Subscriber;

use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsRouter;
use Harmony\Bundle\SettingsManagerBundle\Settings\Switchable\SwitchableCommandInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SwitchableCommandSubscriber implements EventSubscriberInterface
{
    private $settingsRouter;

    public function __construct(SettingsRouter $settingsRouter)
    {
        $this->settingsRouter = $settingsRouter;
    }

    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => ['onConsoleCommand']];
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if ($command instanceof SwitchableCommandInterface
            && !$command::isCommandEnabled($this->settingsRouter)
        ) {
            $event->getOutput()->writeln('Command is disabled');
            $event->disableCommand();
        }
    }
}
