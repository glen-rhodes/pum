<?php

namespace Pum\Core\Extension\EmFactory;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Pum\Core\Definition\ObjectDefinition;
use Pum\Core\Definition\Project;
use Pum\Core\EventListener\Event\BeamEvent;
use Pum\Core\EventListener\Event\ProjectEvent;
use Pum\Core\Extension\AbstractExtension;
use Pum\Core\ObjectFactory;
use Pum\Core\Extension\EmFactory\Doctrine\ObjectEntityManager;
use Pum\Core\Extension\EmFactory\Doctrine\Schema\SchemaTool;
use Doctrine\ORM\Configuration;
use Pum\Core\Extension\EmFactory\Doctrine\Metadata\Driver\PumDefinitionDriver;

class EmFactory
{
    /**
     * DBAL connection used to create/update/delete objects.
     *
     * @var Connection
     */
    protected $connection;

    protected $entityManagers = array();

    protected $configs = array();

    /**
     * @param Connection $connection DBAL connection to use to create dynamic tables.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function addConfiguration($key, Configuration $config)
    {
        $this->configs[$key] = $config;

        return $this;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Returns entity manager for a given name.
     *
     * @param ObjectFactory $objectFactory
     * @param String $projectName
     * @param String $entityManagerName
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getManager(ObjectFactory $objectFactory, $projectName, $entityManagerName = 'default')
    {
        if ($projectName instanceof Project) {
            $projectName = $projectName->getName();
        }

        if (isset($this->entityManagers[$projectName . $entityManagerName])) {
            return $this->entityManagers[$projectName . $entityManagerName];
        }

        return $this->entityManagers[$projectName . $entityManagerName] = $this->createManager($objectFactory, $projectName);
    }

    private function createManager(ObjectFactory $objectFactory, $projectName)
    {
        $config = null;
        if (array_key_exists($projectName, $this->configs)) {
            $config = $this->configs[$projectName];
        } else {
            $config = $this->configs['_pum'];
        }

        if (!$config->getMetadataDriverImpl()) {
            $config->setMetadataDriverImpl(new PumDefinitionDriver($objectFactory));
        }

        return ObjectEntityManager::createPum($objectFactory, $this->connection, $config, $projectName);
    }
}
