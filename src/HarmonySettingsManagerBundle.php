<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle;

use Acelaya\Doctrine\Type\PhpEnumType;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Harmony\Bundle\SettingsManagerBundle\DependencyInjection\Compiler\ProviderFactoryPass;
use Harmony\Bundle\SettingsManagerBundle\DependencyInjection\Compiler\ProviderPass;
use Harmony\Bundle\SettingsManagerBundle\DependencyInjection\Compiler\SettingsAwarePass;
use Harmony\Bundle\SettingsManagerBundle\Model\Type;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class HarmonySettingsManagerBundle
 *
 * @package Harmony\Bundle\SettingsManagerBundle
 */
class HarmonySettingsManagerBundle extends Bundle
{

    /**
     * Boots the Bundle.
     */
    public function boot()
    {
        parent::boot();

        if (class_exists(PhpEnumType::class) && !DoctrineType::hasType('setting_type_enum')) {
            PhpEnumType::registerEnumType('setting_type_enum', Type::class);
        }
    }

    /**
     * Builds the bundle.
     * It is only ever called once when the cache is empty.
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ProviderFactoryPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
        $container->addCompilerPass(new ProviderPass());
        $container->addCompilerPass(new SettingsAwarePass());

        $mappings = [
            realpath(__DIR__ . '/Resources/config/doctrine-mapping') => 'Harmony\Bundle\SettingsManagerBundle\Model'
        ];
        if (class_exists(DoctrineOrmMappingsPass::class) && $container->has('doctrine.orm.default_entity_manager')) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver($mappings));
        }
    }
}
