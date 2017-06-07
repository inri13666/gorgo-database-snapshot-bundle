<?php

namespace Oro\Bundle\DatabaseSnapshotBundle\Service;

use Oro\Bundle\DatabaseSnapshotBundle\Exception\IsolatorNotFoundException;
use Oro\Bundle\DatabaseSnapshotBundle\Isolator\Database\DatabaseIsolatorInterface;
use Oro\Bundle\DatabaseSnapshotBundle\Model\DatabaseConfigurationInterface;

class IsolatorRegistry
{
    const SERVICE_TAG = 'oro.database.isolator';

    /** @var array|DatabaseIsolatorInterface[] */
    protected $isolators = [];

    /**
     * @param DatabaseIsolatorInterface $databaseIsolator
     * @param string $alias
     */
    public function addIsolator(DatabaseIsolatorInterface $databaseIsolator, $alias)
    {
        $this->isolators[$alias] = $databaseIsolator;
    }

    /**
     * @param DatabaseConfigurationInterface $configuration
     *
     * @return DatabaseIsolatorInterface
     */
    public function findIsolator(DatabaseConfigurationInterface $configuration)
    {
        foreach ($this->isolators as $isolator) {
            if ($isolator->isConfigurationSupported($configuration)) {
                return $isolator;
            }
        }

        throw new IsolatorNotFoundException();
    }
}
