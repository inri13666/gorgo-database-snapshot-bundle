<?php

namespace Oro\Bundle\DatabaseSnapshotBundle\Isolator\Database;

use Oro\Bundle\DatabaseSnapshotBundle\Model\DatabaseConfigurationInterface;
use Oro\Bundle\DatabaseSnapshotBundle\Service\ProcessExecutor;

class MysqlDatabaseIsolator extends AbstractDatabaseIsolator
{
    /**
     * @var string
     */
    protected $mysqlBin = 'mysql';

    /**
     * @var string
     */
    protected $mysqlDumpBin = 'mysqldump';

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
     * @param string $mysqlBin
     *
     * @return $this
     */
    public function setMysqlBin($mysqlBin)
    {
        $this->mysqlBin = $mysqlBin;

        return $this;
    }

    /**
     * @param string $mysqlDumpBin
     *
     * @return $this
     */
    public function setMysqlDumpBin($mysqlDumpBin)
    {
        $this->mysqlDumpBin = $mysqlDumpBin;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dump($id, DatabaseConfigurationInterface $config)
    {
        if ($config->getPassword()) {
            putenv(sprintf('MYSQL_PWD=%s', $config->getPassword()));
        }

        $database = $this->getBackupDbName($id, $config);

        $user = $this->resolveUser($config);
        $host = $this->resolveHost($config);
        $port = $this->resolvePort($config);

        if ($this->verify($config->getDbName(), $config)) {
            $this->drop($database, $config);
            $this->processExecutor->execute(
                $this->getCreateDatabaseCommand($user, $host, $port, $database),
                $config->getTimeout()
            );
            $this->processExecutor->execute(
                $this->getDumpCommand($user, $host, $port, $config->getDbName(), $database),
                $config->getTimeout()
            );
        } else {
            throw new \Exception(sprintf('Verification failed for "%s"', $config->getDbName()));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restore($id, DatabaseConfigurationInterface $config)
    {
        if ($config->getPassword()) {
            putenv(sprintf('MYSQL_PWD=%s', $config->getPassword()));
        }

        $database = $this->getBackupDbName($id, $config);

        $user = $this->resolveUser($config);
        $host = $this->resolveHost($config);
        $port = $this->resolvePort($config);

        if ($this->verify($database, $config)) {
            $this->drop($config->getDbName(), $config);
            $this->processExecutor->execute(
                $this->getCreateDatabaseCommand($user, $host, $port, $config->getDbName()),
                $config->getTimeout()
            );
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
            putenv(sprintf('MYSQL_PWD=%s', $config->getPassword()));
        }

        $user = $this->resolveUser($config);
        $host = $this->resolveHost($config);
        $port = $this->resolvePort($config);

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
            putenv(sprintf('MYSQL_PWD=%s', $config->getPassword()));
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
            DatabaseConfigurationInterface::DRIVER_PDO_MYSQL,
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
            '%s -u %s -h %s --port %d -e "use "%s";"',
            $this->mysqlBin,
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
    protected function getDropDatabaseCommand($user, $host, $port, $database)
    {
        return sprintf(
            '%s -u %s -h %s --port %d -e "DROP DATABASE IF EXISTS "%s";"',
            $this->mysqlBin,
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
    protected function getCreateDatabaseCommand($user, $host, $port, $database)
    {
        return sprintf(
            '%s -u %s -h %s --port %d -e "CREATE DATABASE "%s";"',
            $this->mysqlBin,
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
            '%s -u %s -h %s --port %d "%s" | %s -u %s -h %s --port %d "%s"',
            $this->mysqlDumpBin,
            $user,
            $host,
            $port,
            $databaseFrom,
            $this->mysqlBin,
            $user,
            $host,
            $port,
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
        return $config->getUser() ?: 'root';
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
        return $config->getPort() ?: 3306;
    }
}
