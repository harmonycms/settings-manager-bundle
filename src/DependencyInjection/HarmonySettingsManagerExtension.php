<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Harmony\Bundle\SettingsManagerBundle\DataCollector\SettingsCollector;
use Harmony\Bundle\SettingsManagerBundle\Enqueue\Consumption\WarmupSettingsManagerExtension;
use Harmony\Bundle\SettingsManagerBundle\Provider\DoctrineOrmSettingsProvider;
use Harmony\Bundle\SettingsManagerBundle\Provider\Factory\SimpleSettingsProviderFactory;
use Harmony\Bundle\SettingsManagerBundle\Provider\LazyReadableSimpleSettingsProvider;
use Harmony\Bundle\SettingsManagerBundle\Provider\SettingsProviderInterface;
use Harmony\Bundle\SettingsManagerBundle\Settings\EventManagerInterface;
use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsManager;
use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsRouter;
use Harmony\Bundle\SettingsManagerBundle\Settings\SettingsStore;
use Harmony\Bundle\SettingsManagerBundle\Subscriber\SwitchableCommandSubscriber;
use Harmony\Bundle\SettingsManagerBundle\Subscriber\SwitchableControllerSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * Class HarmonySettingsManagerExtension
 *
 * @package Harmony\Bundle\SettingsManagerBundle\DependencyInjection
 */
class HarmonySettingsManagerExtension extends Extension
{

    /**
     * Loads a specific configuration.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
        $loader->load('serializer.yaml');
        $loader->load('validators.yaml');
        $loader->load('command.yaml');

        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['TwigBundle'])) {
            $loader->load('twig.yaml');
        }

        if ($config['profiler']['enabled']) {
            $this->loadDataCollector($container);
        }

        if ($config['logger']['enabled']) {
            $container->setAlias('settings_manager.logger', $config['logger']['service_id']);
        }

        $this->loadSettingsManager($container);
        $this->loadSettingsRouter($container);
        $this->loadSimpleProvider($config, $container);

        if ($container->has('doctrine.orm.default_entity_manager')) {
            $this->loadOrmProvider($config, $container);
        }

        $this->loadListeners($config['listeners'], $container);
        $this->loadEnqueueExtension($config['enqueue_extension'], $container);
    }

    /**
     * @param ContainerBuilder $container
     */
    public function loadSettingsRouter(ContainerBuilder $container): void
    {
        $container->register(SettingsRouter::class, SettingsRouter::class)
            ->setPublic(true)
            ->setArgument(0, new Reference(SettingsManager::class))
            ->setArgument(1, new Reference(SettingsStore::class))
            ->setArgument(2, new Reference(EventManagerInterface::class));
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function loadEnqueueExtension(array $config, ContainerBuilder $container): void
    {
        if (!$config['enabled']) {
            return;
        }

        $container->register(WarmupSettingsManagerExtension::class, WarmupSettingsManagerExtension::class)
            ->addMethodCall('setSettingsRouter', [new Reference(SettingsRouter::class)])
            ->addMethodCall('setDivider', [$config['divider']])
            ->addTag('enqueue.consumption.extension', ['priority' => $config['priority']]);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadSettingsManager(ContainerBuilder $container): void
    {
        $container->register(SettingsManager::class, SettingsManager::class)
            ->setPublic(true)
            ->setLazy(true)
            ->setArgument('$eventManager', new Reference(EventManagerInterface::class))
            ->addMethodCall('setLogger', [
                new Reference('settings_manager.logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ]);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function loadSimpleProvider(array $config, ContainerBuilder $container): void
    {
        $settings = array_merge($config['settings'],
            $this->loadSettingsFromFiles($config['settings_files'], $container));

        if (!$config['settings_config']['lazy']) {
            $container->register('settings_manager.provider.config', SimpleSettingsProviderFactory::class)
                ->setArguments([new Reference('settings_manager.serializer'), $settings, true])
                ->setPublic(false)
                ->addTag('settings_manager.provider_factory', [
                    'provider' => SettingsProviderInterface::DEFAULT_PROVIDER,
                    'priority' => $config['settings_config']['priority'],
                ]);

            return;
        }

        $normalizedDomains          = [];
        $normalizedSettingsByDomain = [];

        foreach ($settings as $setting) {
            $normalizedDomains[$setting['domain']['name']]                            = $setting['domain'];
            $normalizedSettingsByDomain[$setting['domain']['name']][$setting['name']] = $setting;
        }

        $container->register('settings_manager.provider.config', LazyReadableSimpleSettingsProvider::class)
            ->setArguments([
                new Reference('settings_manager.serializer'),
                $normalizedSettingsByDomain,
                $normalizedDomains,
            ])
            ->setPublic(false)
            ->addTag('settings_manager.provider', [
                'provider' => SettingsProviderInterface::DEFAULT_PROVIDER,
                'priority' => $config['settings_config']['priority'],
            ]);
    }

    /**
     * @param array            $files
     * @param ContainerBuilder $container
     *
     * @return array
     */
    private function loadSettingsFromFiles(array $files, ContainerBuilder $container): array
    {
        $configuration = new Configuration();
        $settings      = [];

        foreach ($files as $file) {
            if (file_exists($file)) {
                $fileContents      = Yaml::parseFile($file,
                    Yaml::PARSE_CONSTANT | Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
                $processedContents = $this->processConfiguration($configuration,
                    ['harmony_settings_manager' => ['settings' => $fileContents]]);

                $settings = array_merge($settings, $processedContents['settings']);
                $container->addResource(new FileResource($file));
            }
        }

        return $settings;
    }

    /**
     * @param ContainerBuilder $container
     */
    private function loadDataCollector(ContainerBuilder $container): void
    {
        $container->register(SettingsCollector::class, SettingsCollector::class)
            ->setArgument('$settingsStore', new Reference(SettingsStore::class))
            ->setPublic(false)
            ->addTag('data_collector', [
                'id'       => 'settings_manager.settings_collector',
                'template' => '@HarmonySettingsManager/profiler/profiler.html.twig',
            ]);
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function loadListeners(array $config, ContainerBuilder $container): void
    {
        if ($config['controller']['enabled']) {
            $container->register(SwitchableControllerSubscriber::class, SwitchableControllerSubscriber::class)
                ->setArgument('$settingsRouter', new Reference(SettingsRouter::class))
                ->setPublic(false)
                ->addTag('kernel.event_subscriber');
        }

        if ($config['command']['enabled']) {
            $container->register(SwitchableCommandSubscriber::class, SwitchableCommandSubscriber::class)
                ->setArgument('$settingsRouter', new Reference(SettingsRouter::class))
                ->setPublic(false)
                ->addTag('kernel.event_subscriber');
        }
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function loadOrmProvider(array $config, ContainerBuilder $container): void
    {
        $container->register(DoctrineOrmSettingsProvider::class, DoctrineOrmSettingsProvider::class)
            ->setArgument('$entityManager', new Reference('doctrine.orm.default_entity_manager'))
            ->setArgument('$settingsEntityClass', $config['settings_classes']['setting_entity'])
            ->addTag('settings_manager.provider', ['provider' => 'orm', 'priority' => 20]);
    }
}
