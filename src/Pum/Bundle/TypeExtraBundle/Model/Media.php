<?php

namespace Pum\Bundle\TypeExtraBundle\Model;

use Pum\Bundle\TypeExtraBundle\Media\StorageInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Media
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * A file pending for storage, or modification of an existing image.
     *
     * @var SplFileInfo
     */
    protected $file;

    /**
     * @param string $id
     * @param string $name
     */
    public function __construct($id = null, $name = null, $file = null)
    {
        $this->id      = $id;
        $this->name    = $name;
        $this->file    = $file;
    }

    public function exists()
    {
        return null !== $this->id;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return Media
     */
    public function setFile(\SplFileInfo $file)
    {
        $this->file = $file;

        if (!$this->name) {
            if ($file instanceof UploadedFile) {
                $this->name = $file->getClientOriginalName();
            } else {
                $this->name = $file->getBasename();
            }
        }

        return $this;
    }

    /**
     * @return Media
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getImageUrl(StorageInterface $storage, $width = 0, $height = 0)
    {
        return $this->exists() ? $storage->getWebPath($this->getId(), $width, $height) : null;
    }
}
