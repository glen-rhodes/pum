<?php

namespace Pum\Core;

use Pum\Core\BuilderRegistry\BuilderRegistryInterface;
use Pum\Core\Cache\CacheInterface;
use Pum\Core\Cache\StaticCache;
use Pum\Core\ClassBuilder\ClassBuilder;
use Pum\Core\Context\FieldBuildContext;
use Pum\Core\Context\ObjectBuildContext;
use Pum\Core\Definition\Beam;
use Pum\Core\Definition\Project;
use Pum\Core\Event\BeamEvent;
use Pum\Core\Event\ProjectEvent;
use Pum\Core\Exception\DefinitionNotFoundException;
use Pum\Core\Exception\TypeNotFoundException;
use Pum\Core\Schema\SchemaInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjectFactory
{
    protected $registry;
    protected $schema;
    protected $eventDispatcher;
    protected $cache;

    public function __construct(BuilderRegistryInterface $registry, SchemaInterface $schema, CacheInterface $cache = null, EventDispatcherInterface $eventDispatcher = null)
    {
        if (null === $eventDispatcher) {
            $eventDispatcher = new EventDispatcher();
        }

        if (null === $cache) {
            $cache = new StaticCache();
        }

        $this->registry        = $registry;
        $this->schema          = $schema;
        $this->cache           = $cache;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getTypeNames()
    {
        return $this->registry->getTypeNames();
    }

    public function getTypeHierarchy($name, $interface = null)
    {
        return $this->registry->getHierarchy($name, $interface);
    }

    public function getProjectAndObjectFromClass($class)
    {
        if (!$this->isProjectClass($class)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" is not an object factory class.', $class));
        }

        $project = $this->getProject($class::PUM_PROJECT);

        return array($project, $project->getObject($class::PUM_OBJECT));
    }

    /**
     * @throws ClassNotFoundException
     */
    public function loadClassFromCache($class, $project)
    {
        $this->cache->loadClass($class, $project);
    }

    public function isProjectClass($name)
    {
        return 0 === strpos($name, 'pum_obj_');
    }

    public function getClassName($projectName, $objectName)
    {
        $class = 'pum_obj_'.preg_replace('/[^a-zA-Z_]/', '_', strtolower($objectName)).'_'.$this->cache->getSalt($projectName);

        // avoid infinite loop
        static $building;
        if (null === $building) {
            $building = array();
        }
        if (in_array($class, $building)) {
            return $class;
        }
        $building[] = $class;

        try {
            $this->loadClass($class, $projectName, $objectName);
        } catch (\Exception $e) {
            $pos = array_search($class, $building);
            if (false !== $pos) {
                unset($building[$pos]);
            }

            throw $e;
        }

        // remove from loop
        $pos = array_search($class, $building);
        if (false !== $pos) {
            unset($building[$pos]);
        }

        return $class;
    }

    public function createObject($projectName, $objectName)
    {
        $class = $this->getClassName($projectName, $objectName);

        $this->loadClass($class, $projectName, $objectName);

        return new $class;
    }

    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    private function loadClass($class, $projectName, $objectName)
    {
        if (class_exists($class)) {
            return;
        }

        if ($this->cache->hasClass($class, $projectName)) {
            $this->cache->loadClass($class, $projectName);
        } else {
            $code = $this->buildClass($class, $projectName, $objectName);
            $this->cache->saveClass($class, $code, $projectName);
        }
    }

    private function buildClass($class, $projectName, $objectName)
    {
        $project = $this->schema->getProject($projectName);
        $object  = $project->getObject($objectName);

        $classBuilder = new ClassBuilder($class);
        $classBuilder->createConstant('PUM_PROJECT', var_export($projectName, true));
        $classBuilder->createConstant('PUM_OBJECT', var_export($objectName, true));
        $classBuilder->createConstant('PUM_BEAM', var_export($object->getBeam()->getName(), true));
        $classBuilder->createProperty('id');
        $classBuilder->addGetMethod('id');

        $classname = $object->getClassname();
        if ($classname && class_exists($classname)) {
            $classBuilder->setExtends($classname);
        } elseif ($classname && !class_exists($classname)) {
            $project->addContextError(sprintf('Class "%s" was not found.', $classname));
        }

        foreach ($object->getFields() as $field) {
            try {
                $types = $this->registry->getHierarchy($field->getType());
            } catch (TypeNotFoundException $e) {
                $project->addContextError(sprintf(
                    'Field type "%s" does not exist. Registered for field "%s".',
                    $field->getType(),
                    $objectName.'->'.$field->getName()
                ));

                continue;
            }

            $options = $field->getTypeOptions();

            $resolver = new OptionsResolver();
            foreach ($types as $type) {
                $type->setDefaultOptions($resolver);
            }
            $options = $resolver->resolve($options);

            $context = new FieldBuildContext($project, $classBuilder, $field, $options);
            $context->setObjectFactory($this);
            foreach ($types as $type) {
                $type->buildField($context);
            }
        }

        foreach (array('name', 'title', 'label', 'fullname') as $eligible) {
            if ($object->hasField($eligible)) {
                $classBuilder->createMethod('__toString', '', 'return (string) $this->get'.ucfirst($eligible).'();');
                break;
            }
        }
        if (!$classBuilder->hasMethod('__toString')) {
            $classBuilder->createMethod('__toString', '', 'return "'.$object->getName().' #" . $this->getId();');
        }

        $behaviors = array_map(function ($behavior) {
            return $this->registry->getBehavior($behavior);
        }, $object->getBehaviors());

        $context = new ObjectBuildContext($project, $classBuilder, $object);
        foreach ($behaviors as $behavior) {
            $behavior->buildObject($context);
        }

        return $classBuilder->getCode();
    }

    /**
     * Saves a project (existing or new).
     */
    public function saveProject(Project $project)
    {

        $project->resetContextMessages();
        $project->addContextInfo("Updating project");
        $this->cache->clear($project->getName());
        $this->schema->saveProject($project);
        $project->addContextInfo("Finished updating project");

        // project might have changed
        $this->schema->saveProject($project);
    }

    /**
     * Saves a beam (existing or new).
     */
    public function saveBeam(Beam $beam)
    {
        foreach ($beam->getProjects() as $project) {
            $this->cache->clear($project->getName());
        }

        $this->schema->saveBeam($beam);

        foreach ($beam->getProjects() as $project) {
            $this->saveProject($project);
        }
    }

    /**
     * Deletes a project (existing or new).
     */
    public function deleteProject(Project $project)
    {
        $this->cache->clear($project->getName());
        $this->schema->deleteProject($project);
    }

    /**
     * Deletes a beam (existing or new).
     */
    public function deleteBeam(Beam $beam)
    {
        foreach ($beam->getProjects() as $project) {
            $this->cache->clear($project->getName());
        }
        $this->schema->deleteBeam($beam);
    }

    /**
     * Returns definition of an object.
     *
     * @param string $name name of the definition to fetch
     *
     * @return ObjectDefinition
     */
    public function getDefinition($projectName, $name)
    {
        return $this->schema->getProject($projectName)->getObject($name);
    }
    /**
     * @return Beam
     */
    public function getBeam($name)
    {
        return $this->schema->getBeam($name);
    }

    /**
     * @return Project
     */
    public function getProject($name)
    {
        return $this->schema->getProject($name);
    }

    /**
     * Returns all beams.
     *
     * @return array
     */
    public function getAllBeams()
    {
        $result = array();
        foreach ($this->schema->getBeamNames() as $name) {
            $result[] = $this->schema->getBeam($name);
        }

        return $result;
    }
    /**
     * Returns all projects.
     *
     * @return array
     */
    public function getAllProjects()
    {
        $result = array();
        foreach ($this->schema->getProjectNames() as $name) {
            $result[] = $this->schema->getProject($name);
        }

        return $result;
    }

    /**
     * @return SchemaInterface
     */
    public function getSchema()
    {
        return $this->schema;
    }
}
