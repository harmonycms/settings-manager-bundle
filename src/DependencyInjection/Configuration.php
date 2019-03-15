<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle\DependencyInjection;

use Harmony\Bundle\SettingsManagerBundle\Model\DomainModel;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Harmony\Bundle\SettingsManagerBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    /**
     * @return TreeBuilder
     * @throws \ReflectionException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('harmony_settings_manager');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->arrayNode('enqueue_extension')
                    ->canBeEnabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('divider')->defaultValue(1)->end()
                        ->integerNode('priority')->defaultValue(100)->end()
                    ->end()
                ->end()
                ->arrayNode('profiler')
                    ->canBeEnabled()
                    ->addDefaultsIfNotSet()
                ->end()
                ->arrayNode('logger')
                    ->addDefaultsIfNotSet()
                    ->canBeEnabled()
                    ->validate()
                        ->ifTrue(function ($v) {
                            return $v['enabled'] && !isset($v['service_id']);
                        })
                        ->thenInvalid('logger service_id is missing')
                    ->end()
                    ->children()
                        ->scalarNode('service_id')
                            ->example('monolog.logger.settings')
                            ->info('Psr\Log\LoggerInterface service id')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('settings_config')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('lazy')
                            ->defaultTrue()
                            ->info('Settings from config will be lazy denormalized in provider.')
                        ->end()
                        ->integerNode('priority')
                            ->defaultValue(-10)
                            ->info('Priority for settings from configuration')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('settings_files')
                    ->scalarPrototype()->end()
                ->end()
                ->arrayNode('settings_classes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('setting_entity')
                            ->info('Setting entity class (FQDN)')
                            ->defaultValue('App\Entity\Setting')
                        ->end()
                        ->scalarNode('setting_tag_entity')
                            ->info('SettingTag entity class (FQDN)')
                            ->defaultValue('App\Entity\SettingTag')
                        ->end()
                    ->end()
                ->end()
                ->append($this->getSettingsNode())
                ->append($this->getListenersNode())
            ->end();

        return $treeBuilder;
    }

    /**
     * @return NodeDefinition
     * @throws \ReflectionException
     */
    private function getSettingsNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('settings');
        $node = $treeBuilder->getRootNode();
        $node
        ->arrayPrototype()
            ->children()
                ->scalarNode('name')->isRequired()->end()
                ->scalarNode('description')->end()
                ->arrayNode('domain')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return [
                                'name' => $v,
                                'enabled' => true, // domains from config are enabled by default
                                'read_only' => true, // all config domains are read only
                            ];
                        })
                    ->end()
                    ->children()
                        ->scalarNode('name')->defaultValue(DomainModel::DEFAULT_NAME)->end()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->booleanNode('read_only')->defaultTrue()->end()
                    ->end()
                ->end()
                ->arrayNode('tags')
                    ->arrayPrototype()
                        ->beforeNormalization()
                        ->ifString()
                        ->then(function ($v) {
                            return ['name' => $v];
                        })
                        ->end()
                        ->children()
                            ->scalarNode('name')->end()
                        ->end()
                    ->end()
                ->end()
                ->enumNode('type')
                    ->values(array_values(Type::toArray()))
                    ->isRequired()
                ->end()
                ->arrayNode('type_options')
                   ->variablePrototype()->end()
                ->end()
                ->arrayNode('data')
                    ->beforeNormalization()
                    ->always()
                    ->then(function ($v) {
                        if (is_string($v) || is_int($v) || is_float($v)) {
                            return ['value' => $v];
                        }

                        if (is_array($v) && isset($v['value'])) {
                            return $v;
                        }

                        return ['value' => $v];
                    })
                    ->end()
                    ->children()
                        ->variableNode('value')
                        ->isRequired()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('choices')
                    ->variablePrototype()->end()
                ->end()
            ->end()
        ->end();

        return $node;
    }

    /**
     * @return NodeDefinition
     */
    private function getListenersNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('listeners');
        $node = $treeBuilder->getRootNode();
        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('controller')->canBeEnabled()->end()
                ->arrayNode('command')->canBeEnabled()->end()
            ->end();

        return $node;
    }
}
