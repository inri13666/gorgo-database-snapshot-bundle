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

### Behat Isolation

- Comment or Remove following service definitions at `Oro/Bundle/TestFrameworkBundle/Behat/ServiceContainer/config/isolators.yml`
```
  oro_behat_extension.isolation.windows_mysql_isolator:
    class: 'Oro\Bundle\TestFrameworkBundle\Behat\Isolation\WindowsMysqlIsolator'
    arguments:
      - '@symfony2_extension.kernel'
    tags:
      - { name: 'oro_behat.isolator', priority: 110 }

  oro_behat_extension.isolation.unix_mysql_simple_isolator:
    class: 'Oro\Bundle\TestFrameworkBundle\Behat\Isolation\UnixMysqlSyncIsolator'
    arguments:
      - '@symfony2_extension.kernel'
    tags:
      - { name: 'oro_behat.isolator', priority: 110 }

  oro_behat_extension.isolation.unix_pgsql_isolator:
    class: 'Oro\Bundle\TestFrameworkBundle\Behat\Isolation\UnixPgsqlIsolator'
    arguments:
      - '@symfony2_extension.kernel'
    tags:
      - { name: 'oro_behat.isolator', priority: 110 }
```
- Add following lines to `Oro/Bundle/TestFrameworkBundle/Behat/ServiceContainer/config/kernel_services.yml`
```
    oro_behat.extension.isolation.database:
        class: Oro\Bundle\DatabaseSnapshotBundle\Isolator\Behat\DatabaseBehatIsolator
        factory: ['@oro_test.kernel_service_factory', get]
        arguments: ['oro_datasnap.behat.extension.isolation.database']
        tags:
            - { name: 'oro_behat.isolator', priority: 110 }
```
- Clear cache & Enjoy :)


### Known issues

- PostgerSQL may fail sometimes with following message:
```
  [Symfony\Component\Process\Exception\ProcessFailedException]
  The command "dropdb --if-exists -U postgres -h 127.0.0.1 -p 5432 acme_db" failed.
  Exit Code: 1(General error)
  Working directory: /var/home/sites/acme-site
  Output:
  ================
  Error Output:
  ================
  dropdb: database removal failed: ERROR:  database "acme_db" is being accessed by other users
  DETAIL:  There are 1 other sessions using the database.
```
OR
```
  [Symfony\Component\Process\Exception\ProcessFailedException]
  The command "createdb -U postgres -h 127.0.0.1 -p 5432 -O postgres -T acme_db backup_acme_db_8ba0bdc20ffb74f130ec21d2d1d737fe" failed.
  Exit Code: 1(General error)
  Working directory: /var/home/sites/acme-site
  Output:
  ================
  Error Output:
  ================
  createdb: database creation failed: ERROR:  source database "acme_db" is being accessed by other users
  DETAIL:  There is 1 other session using the database.
```
