<?php

namespace Pum\Bundle\TypeExtraBundle\Model;

use Pum\Bundle\TypeExtraBundle\Media\StorageInterface;


class Media
{
    protected $name;
    protected $path;
    protected $storage;

    public function __construct($name, $path)
    {
        $this->name = $name;
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set storage driver
     * @return self
     */
    public function setStorage(StorageInterface $storage)
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * @return Media
     */
    public function upload($file)
    {
        $media = new self($this->getName(), $this->storage->store($file));
        $this->storage->remove($this->getPath());

        return $media;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name . ", " . $this->path;
    }
}