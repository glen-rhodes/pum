<?php

namespace Pum\Core\Doctrine;

use Doctrine\DBAL\Connection;
use Pum\Core\Manager;

class EntityManagerFactory
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var ObjectEntityManager
     */
    private $entityManager;

    /**
     * Constructor.
     *
     * @param Manager $manager the PUM manager to use
     */
    public function __construct(Manager $manager, Connection $connection)
    {
        $this->manager = $manager;
        $this->connection = $connection;
    }

    public function getEntityManager()
    {
        if (null !== $this->entityManager) {
            return $this->entityManager;
        }

        return $this->entityManager = ObjectEntityManager::createPum($this->manager, $this->connection);
    }
}
