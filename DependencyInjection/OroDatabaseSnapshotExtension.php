<?php

namespace Oro\Bundle\DatabaseSnapshotBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

class OroDatabaseSnapshotExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if ($container->hasDefinition('oro_datasnap.isolation.isolator.pdo_mysql')) {
            $mysqlConfig = $config[Configuration::MYSQL_NODE];
            $definition = $container->getDefinition('oro_datasnap.isolation.isolator.pdo_mysql');
            $definition->addMethodCall('setMysqlBin', [$mysqlConfig[Configuration::MYSQL_BIN_NODE]]);
            $definition->addMethodCall('setMysqlDumpBin', [$mysqlConfig[Configuration::MYSQLDUMP_BIN_NODE]]);
        }

        if ($container->hasDefinition('oro_datasnap.isolation.isolator.pdo_pgsql')) {
            $pgsqlConfig = $config[Configuration::POSTGRESQL_NODE];
            $definition = $container->getDefinition('oro_datasnap.isolation.isolator.pdo_pgsql');
            $definition->addMethodCall('setDropdbBin', [$pgsqlConfig[Configuration::DROPDB_BIN_NODE]]);
            $definition->addMethodCall('setCreatedbBin', [$pgsqlConfig[Configuration::CREATEDB_BIN_NODE]]);
            $definition->addMethodCall('setPsqlBin', [$pgsqlConfig[Configuration::PSQL_BIN_NODE]]);
        }
    }
}
