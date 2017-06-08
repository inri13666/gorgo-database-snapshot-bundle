<?php

namespace Oro\Bundle\DatabaseSnapshotBundle\Isolator\Database;

use Oro\Bundle\DatabaseSnapshotBundle\Model\DatabaseConfigurationInterface;
use Oro\Bundle\DatabaseSnapshotBundle\Service\ProcessExecutor;

class PgsqlDatabaseIsolator extends AbstractDatabaseIsolator
{
    /**
     * @var string
     */
    protected $dropdbBin = 'dropdb';

    /**
     * @var string
     */
    protected $createdbBin = 'createdb';

    /**
     * @var string
     */
    protected $psqlBin = 'psql';

    /**
     * @var ProcessExecutor
     */
    protected $processExecutor;

    /**
     * @param ProcessExecutor $processExecutor
     */
    public function __construct(ProcessExecutor $processExecutor)
    {
        $this->processExecutor = $processExecutor;
    }

    /**
     * @param string $dropdbBin
     *
     * @return $this
     */
    public function setDropdbBin($dropdbBin)
    {
        $this->dropdbBin = $dropdbBin;

        return $this;
    }

    /**
     * @param string $createdbBin
     *
     * @return $this
     */
    public function setCreatedbBin($createdbBin)
    {
        $this->createdbBin = $createdbBin;

        return $this;
    }

    /**
     * @param string $psqlBin
     *
     * @return $this
     */
    public function setPsqlBin($psqlBin)
    {
        $this->psqlBin = $psqlBin;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dump($id, DatabaseConfigurationInterface $config)
    {
        $setPasswordCommand = null;

        if ($config->getPassword()) {
            putenv(sprintf('PGPASSWORD=%s', $config->getPassword()));
        }

        $database = $this->getBackupDbName($id, $config);

        $user = $this->resolveUser($config);
        $host = $this->resolveHost($config);
        $port = $this->resolvePort($config);

        if ($this->verify($config->getDbName(), $config)) {
            $this->processExecutor->execute(
                $this->getKillConnectionsCommand(
                    $user,
                    $host,
                    $port,
                    $config->getDbName()
                ),
                $config->getTimeout()
            );
            $this->processExecutor->execute(
                $this->getKillConnectionsCommand($user, $host, $port, $database),
                $config->getTimeout()
            );
            $this->drop($database, $config);
            $this->processExecutor->execute(
                $this->getDumpCommand($user, $host, $port, $config->getDbName(), $database),
                $config->getTimeout()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restore($id, DatabaseConfigurationInterface $config)
    {
        if ($config->getPassword()) {
            putenv(sprintf('PGPASSWORD=%s', $config->getPassword()));
        }

        $database = $this->getBackupDbName($id, $config);

        $user = $this->resolveUser($config);
        $host = $this->resolveHost($config);
        $port = $this->resolvePort($config);

        if ($this->verify($database, $config)) {
            $this->processExecutor->execute(
                $this->getKillConnectionsCommand(
                    $user,
                    $host,
                    $port,
                    $config->getDbName()
                ),
                $config->getTimeout()
            );
            $this->processExecutor->execute(
                $this->getKillConnectionsCommand($user, $host, $port, $database),
                $config->getTimeout()
            );
            $this->drop($config->getDbName(), $config);
            $this->processExecutor->execute(
                $this->getDumpCommand($user, $host, $port, $database, $config->getDbName()),
                $config->getTimeout()
            );
        } else {
            throw new \Exception(sprintf('Verification failed for "%s"', $config->getDbName()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function drop($name, DatabaseConfigurationInterface $config)
    {
        if ($config->getPassword()) {
            putenv(sprintf('PGPASSWORD=%s', $config->getPassword()));
        }

        $user = $this->resolveUser($config);
        $host = $this->resolveHost($config);
        $port = $this->resolvePort($config);

        $this->processExecutor->execute(
            $this->getKillConnectionsCommand($user, $host, $port, $name),
            $config->getTimeout()
        );
        $this->processExecutor->execute(
            $this->getDropDatabaseCommand($user, $host, $port, $name),
            $config->getTimeout()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function verify($name, DatabaseConfigurationInterface $config)
    {
        if ($config->getPassword()) {
            putenv(sprintf('PGPASSWORD=%s', $config->getPassword()));
        }

        $user = $this->resolveUser($config);
        $host = $this->resolveHost($config);
        $port = $this->resolvePort($config);

        try {
            $this->processExecutor->execute(
                $this->getVerifyDatabaseCommand($user, $host, $port, $name),
                $config->getTimeout()
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedOs()
    {
        return [
            AbstractDatabaseIsolator::OS_WINDOWS,
            AbstractDatabaseIsolator::OS_LINUX,
            AbstractDatabaseIsolator::OS_MAC,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedDrivers()
    {
        return [
            DatabaseConfigurationInterface::DRIVER_PDO_POSTGRESQL,
        ];
    }

    /**
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $database
     *
     * @return string
     */
    protected function getVerifyDatabaseCommand($user, $host, $port, $database)
    {
        return sprintf(
            '%s -U %s -h %s -p %d -d %s -c "SELECT 1;"',
            $this->psqlBin,
            $user,
            $host,
            $port,
            $database
        );
    }

    /**
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $database
     *
     * @return string
     */
    protected function getKillConnectionsCommand($user, $host, $port, $database)
    {
        $killQuery = sprintf(
            'SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = \'%s\';',
            $database
        );

        return sprintf(
            '%s -U %s -h %s -p %d template1 -t -c "%s"',
            $this->psqlBin,
            $user,
            $host,
            $port,
            $killQuery
        );
    }

    /**
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $database
     *
     * @return string
     */
    protected function getDropDatabaseCommand($user, $host, $port, $database)
    {
        return sprintf(
            '%s --if-exists -U %s -h %s -p %d %s',
            $this->dropdbBin,
            $user,
            $host,
            $port,
            $database
        );
    }

    /**
     * @param string $user
     * @param string $host
     * @param int $port
     * @param string $databaseFrom
     * @param string $databaseTo
     *
     * @return string
     */
    protected function getDumpCommand($user, $host, $port, $databaseFrom, $databaseTo)
    {
        return sprintf(
            '%s -U %s -h %s -p %d -O %s -T %s %s',
            $this->createdbBin,
            $user,
            $host,
            $port,
            $user,
            $databaseFrom,
            $databaseTo
        );
    }

    /**
     * @param DatabaseConfigurationInterface $config
     *
     * @return string
     */
    protected function resolveUser(DatabaseConfigurationInterface $config)
    {
        return $config->getUser() ?: 'postgres';
    }

    /**
     * @param DatabaseConfigurationInterface $config
     *
     * @return string
     */
    protected function resolveHost(DatabaseConfigurationInterface $config)
    {
        return $config->getHost() ?: '127.0.0.1';
    }

    /**
     * @param DatabaseConfigurationInterface $config
     *
     * @return int
     */
    protected function resolvePort(DatabaseConfigurationInterface $config)
    {
        return $config->getPort() ?: 5432;
    }
}
