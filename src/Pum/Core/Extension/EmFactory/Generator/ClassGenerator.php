<?php

namespace Pum\Core\Extension\EmFactory\Generator;

use Pum\Core\Definition\ObjectDefinition;

/**
 * Responsible of generating entities from object definitions.
 */
class ClassGenerator
{
    /**
     * @var string|null
     */
    private $cacheDir;

    /**
     * @var string|null
     */
    private $projectName;

    /**
     * @var array
     */
    private $classNames;

    /**
     * Constructs the class generator.
     *
     * To disable cache, pass ``null`` as cache directory.
     *
     * @param string|null $cacheDir
     */
    public function __construct($projectName, $cacheDir = null)
    {
        $this->projectName = $projectName;
        $this->cacheDir    = $cacheDir;
    }

    /**
     * Verifies cache and returns the class name for given entity name.
     *
     * @return string|false returns classname if it was already generated, returns false if not generated.
     */
    public function isGenerated($name)
    {
        $className = $this->getClassName($name);

        if (class_exists($className)) {
            return $className;
        }

        if (null === $this->cacheDir) {
            return false;
        }

        $file = $this->cacheDir.'/'.$className;
        if (is_file($file)) {
            require_once $file;

            return $className;
        }

        return false;
    }

    /**
     * Generates a class from an object definition.
     *
     * @return string classname
     */
    public function generate(Definition $definition)
    {
        $className = $this->getClassName($definition->getName());
        $extend = $definition->getClassname() ? $definition->getClassname() : '\Pum\Core\Object\Object';
        $class = 'class '.$className.' extends '.$extend.' {}';

        if (null === $this->cacheDir) {
            eval($class);

            return $className;
        }

        $file = $this->cacheDir.'/'.$className;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($file, '<?php '.$class);

        require_once $file;

        return $className;
    }

    /**
     * Returns classname from an entity name.
     *
     * @return string
     */
    public function getClassName($name)
    {
        return 'pum_object_'.md5($this->projectName.$name);
    }
}
