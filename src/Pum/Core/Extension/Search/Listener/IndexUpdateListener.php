<?php

namespace Pum\Core\Extension\Search\Listener;

use Pum\Core\Definition\ObjectDefinition;
use Pum\Core\Definition\Project;
use Pum\Core\Event\BeamEvent;
use Pum\Core\Event\ObjectDefinitionEvent;
use Pum\Core\Event\ObjectEvent;
use Pum\Core\Event\ProjectEvent;
use Pum\Core\Events;
use Pum\Core\Extension\EmFactory\EmFactory;
use Pum\Core\Extension\Search\SearchEngine;
use Pum\Core\Extension\Search\SearchableInterface;
use Pum\Core\ObjectFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Bridge\Monolog\Logger;

class IndexUpdateListener implements EventSubscriberInterface
{
    const MAX_ITEMS = 250;

    private $searchEngine;
    private $emFactory;
    private $logger;

    public function __construct(SearchEngine $searchEngine, EmFactory $emFactory, Logger $logger)
    {
        $this->searchEngine  = $searchEngine;
        $this->emFactory     = $emFactory;
        $this->logger        = $logger;
    }

    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            Events::OBJECT_INSERT                   => 'onObjectChange',
            Events::OBJECT_UPDATE                   => 'onObjectChange',
            Events::OBJECT_DELETE                   => 'onObjectDelete',

            Events::OBJECT_DEFINITION_SEARCH_UPDATE => 'onSearchUpdate',
        );
    }

    public function onObjectChange(ObjectEvent $event)
    {
        $obj = $event->getObject();
        if (!$obj instanceof SearchableInterface || !$obj->getId()) {
            return;
        }

        try {
            $this->searchEngine->put($obj);
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    public function onObjectDelete(ObjectEvent $event)
    {
        $obj = $event->getObject();
        if (!$obj instanceof SearchableInterface) {
            return;
        }

        try {
            $this->searchEngine->delete($obj);
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    public function onSearchUpdate(ObjectDefinitionEvent $event)
    {
        $projects = $event->getObjectDefinition()->getBeam()->getProjects();

        try {
            foreach ($projects as $project) {
                $this->updateProject($project, $event->getObjectFactory(), $event->getObjectDefinition());
            }
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
        }
    }

    private function updateProject(Project $project, ObjectFactory $objectFactory, ObjectDefinition $object = null)
    {
        $indexName = SearchEngine::getIndexName($project->getName());
        $em        = $this->emFactory->getManager($objectFactory, $project->getName());

        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        if (null === $object) {
            $objects = $project->getObjects();
        } else {
            $objects = array($object);
        }

        foreach ($objects as $object) {
            $typeName = SearchEngine::getTypeName($object->getName());

            if (!$object->isSearchEnabled()) {
                $this->searchEngine->deleteIndex($indexName, $typeName);

                continue;
            }

            $this->searchEngine->updateIndex($indexName, $typeName, $object);

            $repo      = $em->getRepository($object->getName());
            $count     = $repo->countBy();
            $iteration = ceil($count/self::MAX_ITEMS);

            for ($i = 0; $i < $iteration; $i++) {
                $this->putCollection($repo, $limit=self::MAX_ITEMS, $offset=$i*self::MAX_ITEMS);
                $em->clear();
                gc_collect_cycles();
            }
        }
    }

    private function putCollection($repository, $limit, $offset)
    {
        foreach ($repository->getObjectsBy(array(), null, $limit, $offset) as $obj) {
            try {
                $this->searchEngine->put($obj);
            } catch (\Exception $e) {
                $this->logError($e->getMessage());
            }
        }
    }

    private function logError($error)
    {
        $this->logger->err("Search error : ".$error);
    }
}
