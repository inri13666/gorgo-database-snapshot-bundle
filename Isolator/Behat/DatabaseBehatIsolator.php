<?php

namespace Oro\Bundle\DatabaseSnapshotBundle\Isolator\Behat;

use Oro\Bundle\DatabaseSnapshotBundle\Model\DatabaseConfigurationModel;
use Oro\Bundle\DatabaseSnapshotBundle\Service\IsolatorRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DatabaseBehatIsolator implements Isolation\IsolatorInterface
{
    use ContainerAwareTrait;

    /** @var IsolatorRegistry */
    protected $isolatorRegistry;

    /** @var DatabaseConfigurationModel */
    protected $configuration;

    /** @var string */
    protected $sid;

    public function __construct(IsolatorRegistry $isolatorRegistry, $installed)
    {
        $this->isolatorRegistry = $isolatorRegistry;
        $this->sid = md5($installed);
    }

    /**
     * @return DatabaseConfigurationModel
     */
    protected function getConfiguration()
    {
        $container = $this->container;
        if (!$this->configuration) {
            $this->configuration = new DatabaseConfigurationModel();
            $this->configuration
                ->setDriver($container->getParameter('database_driver'))
                ->setHost($container->getParameter('database_host'))
                ->setPort($container->getParameter('database_port'))
                ->setDbName($container->getParameter('database_name'))
                ->setUser($container->getParameter('database_user'))
                ->setPassword($container->getParameter('database_password'));
        }

        return $this->configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Isolation\Event\BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Dumping current application database</info>');
        $this->isolatorRegistry->findIsolator($this->getConfiguration())
            ->dump($this->sid, $this->getConfiguration());
        $event->writeln('<info>Dump created</info>');
    }

    /**
     * {@inheritdoc}
     */
    public function beforeTest(Isolation\Event\BeforeIsolatedTestEvent $event)
    {
        // Do Nothing
    }

    /**
     * {@inheritdoc}
     */
    public function afterTest(Isolation\Event\AfterIsolatedTestEvent $event)
    {
        $config = $this->getConfiguration();
        $isolator = $this->isolatorRegistry->findIsolator($config);
        $isolator->restore($this->sid, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Isolation\Event\AfterFinishTestsEvent $event)
    {
        $config = $this->getConfiguration();
        $isolator = $this->isolatorRegistry->findIsolator($config);
        $isolator->restore($this->sid, $config);
        $isolator->drop(
            $isolator->getBackupDbName($this->sid, $config),
            $config
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(ContainerInterface $container)
    {
        $this->setContainer($container);

        try {
            $this->isolatorRegistry->findIsolator($this->getConfiguration());

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restoreState(Isolation\Event\RestoreStateEvent $event)
    {
        $config = $this->getConfiguration();
        $isolator = $this->isolatorRegistry->findIsolator($config);
        $event->writeln('<info>Begin to restore the state of Db...</info>');
        if ($isolator->verify($isolator->getBackupDbName($this->sid, $config), $config)) {
            $event->writeln('<info>Drop/Create Db</info>');
            $isolator->restore($this->sid, $config);
            $isolator->drop(
                $isolator->getBackupDbName($this->sid, $config),
                $config
            );
            $event->writeln('<info>Db was restored from dump</info>');
        } else {
            $event->writeln('<info>Db was not restored from dump</info>');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isOutdatedState()
    {
        $config = $this->getConfiguration();
        $isolator = $this->isolatorRegistry->findIsolator($config);

        return $isolator->verify($isolator->getBackupDbName($this->sid, $config), $config);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "Akuma Isolator";
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'database';
    }
}
