<?php

namespace Oro\Bundle\DatabaseSnapshotBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    const CONFIG_NODE = 'oro_database_snapshot';

    const MYSQL_NODE = 'mysql';
    const MYSQL_BIN_NODE = 'mysql';
    const MYSQLDUMP_BIN_NODE = 'mysqldump';

    const POSTGRESQL_NODE = 'postgresql';
    const PSQL_BIN_NODE = 'psql';
    const CREATEDB_BIN_NODE = 'createdb';
    const DROPDB_BIN_NODE = 'dropdb';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder->root(self::CONFIG_NODE)
            ->children()
                ->arrayNode(self::MYSQL_NODE)
                    ->addDefaultsIfNotSet()
                    ->info('Binaries paths for MySQL')
                    ->children()
                        ->scalarNode(self::MYSQL_BIN_NODE)
                            ->defaultValue('mysql')
                        ->end()
                        ->scalarNode(self::MYSQLDUMP_BIN_NODE)
                            ->defaultValue('mysqldump')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode(self::POSTGRESQL_NODE)
                    ->info('Binaries paths for PostgreSQL')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode(self::CREATEDB_BIN_NODE)
                            ->defaultValue('createdb')
                        ->end()
                        ->scalarNode(self::DROPDB_BIN_NODE)
                            ->defaultValue('dropdb')
                        ->end()
                        ->scalarNode(self::PSQL_BIN_NODE)
                            ->defaultValue('psql')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
