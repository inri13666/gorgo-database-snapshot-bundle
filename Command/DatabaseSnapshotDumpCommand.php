<?php

namespace Oro\Bundle\DatabaseSnapshotBundle\Command;

use Doctrine\DBAL\Connection;
use Oro\Bundle\DatabaseSnapshotBundle\Model\DatabaseConfigurationModel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseSnapshotDumpCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:database:snapshot:dump')
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, '', null)
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, '', (new \DateTime())->format('Ymdhis'));
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return class_exists('\Doctrine\DBAL\Connection');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $connection */
        $connection = $this->getContainer()->get('doctrine')->getConnection($input->getOption('connection'));

        $configuration = new DatabaseConfigurationModel();
        $configuration->setDbName($connection->getDatabase())
            ->setDriver($connection->getDriver()->getName())
            ->setHost($connection->getHost())
            ->setPort($connection->getPort())
            ->setUser($connection->getUsername())
            ->setPassword($connection->getPassword());

        $isolator = $this->getContainer()->get('oro_datasnap.isolation.isolator.registry')
            ->findIsolator($configuration);
        $sid = $input->getOption('id');
        $isolator->dump($sid, $configuration);
        $output->writeln(sprintf('Generated dump with sid <info>%s</info>', $sid));
    }
}
