# Oro Database Snapshot Bundle

### Limitations
- Database user should have rights for DROP & CREATE DATABASE
- PHP environment should allow to use "putenv" function

### Installation

If you are using it within Oro Application, you should follow next manual

```
    composer require gorgo13/database-snapshot-bundle
    php app/console cache:clear
```

If you are using it not with Oro Products then you additionally must register bundle at `AppKernel` 
```
class AppKernel extends AcmeKernel
{
    public function registerBundles()
    {
        $bundles = array(
            .....
            new \Oro\Bundle\DatabaseSnapshotBundle\OroDatabaseSnapshotBundle(),
        );

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
```

### Configuration

If you have custom paths for database engine binaries then you can override them at config

```
oro_database_snapshot:
    mysql:
        mysql: {PATH_TO_MYSQL_BINARY} # default "mysql"
        mysqldump: {PATH_TO_MYSQLDUMP_BINARY} # default "mysqldump"
    postgresql:
        dropdb: {PATH_TO_DROPDB_BINARY} # default "dropdb"
        createdb: {PATH_TO_CREATEDB_BINARY} # default "createdb"
        psql: {PATH_TO_PSQL_BINARY} #default "psql"
```

### CLI Commands

If your application have "Doctrine Bundle" then you able to use following commands
- `doctrine:connections` - displays the list of all registered connections within application
- `oro:database:snapshot:dump` - creates database snapshot for given connection with optional suffix
    - `connection` is connection name to be dumped (OPTIONAL, Default: null)
    - `id` is suffix for dump name (be used for restore command) (OPTIONAL, Default: current date) 
- `oro:database:snapshot:restore` - restores database snapshot for given connection with optional suffix
    - `connection` is connection name to be dumped (OPTIONAL, Default: null)
    - `id` is suffix for dump name (be used for restore command) (OPTIONAL, Default: current date)
