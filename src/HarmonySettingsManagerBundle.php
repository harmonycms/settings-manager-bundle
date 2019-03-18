<?php

declare(strict_types=1);

namespace Harmony\Bundle\SettingsManagerBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\ODM\MongoDB\Types\Type as MongoDbType;
use Harmony\Bundle\CoreBundle\Doctrine\Type\PhpEnumType;
use Harmony\Bundle\CoreBundle\Doctrine\Type\PhpOdmEnumType;
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
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function boot()
    {
        parent::boot();

        if (\class_exists(PhpEnumType::class) && !DoctrineType::hasType('setting_type_enum')) {
            PhpEnumType::registerEnumType('setting_type_enum', Type::class);
        }
        if (\class_exists(PhpOdmEnumType::class) && !MongoDbType::hasType('setting_type_enum')) {
            PhpOdmEnumType::registerEnumType('setting_type_enum', Type::class);
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

        // get all bundles
        $bundles = $container->getParameter('kernel.bundles');

        $mappings = [
            realpath(__DIR__ . '/Resources/config/doctrine-mapping') => 'Harmony\Bundle\SettingsManagerBundle\Model'
        ];
        if (\class_exists(DoctrineMongoDBMappingsPass::class) && isset($bundles['DoctrineMongoDBBundle'])) {
            $container->addCompilerPass(DoctrineMongoDBMappingsPass::createXmlMappingDriver($mappings, []));
        } elseif (\class_exists(DoctrineOrmMappingsPass::class) && isset($bundles['DoctrineBundle'])) {
            $container->addCompilerPass(DoctrineOrmMappingsPass::createXmlMappingDriver($mappings));
        }
    }
}
