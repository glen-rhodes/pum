<?php

namespace Pum\Bundle\TypeExtraBundle\Media;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Pum\Bundle\TypeExtraBundle\Exception\MediaNotFoundException;
use Pum\Bundle\TypeExtraBundle\Model\Media;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Pum\Core\Extension\Util\Namer;

class FilesystemStorage implements StorageInterface
{
    protected $directory;
    protected $path;
    protected $dateFolder;

    public function __construct($directory, $path, $dateFolder = false)
    {
        $this->directory  = $directory;
        $this->path       = $path;
        $this->dateFolder = $dateFolder;
    }

    /**
     * return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * return FilesystemStorage
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * return FilesystemStorage
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * return boolean
     */
    public function isDateFolder()
    {
        return $this->dateFolder;
    }

    /**
     * return FilesystemStorage
     */
    public function setDateFolder($dateFolder)
    {
        $this->dateFolder = $dateFolder;

        return $this;
    }

    /**
     * return upload directory
     */
    public function getUploadFolder()
    {
        return $this->getDirectory().$this->getPath();
    }

    /**
     * store a file
     */
    public function store(\SplFileInfo $file)
    {
        $fileName = $this->generateFileName($file);
        $copy     = $this->getUploadFolder().$fileName;
        $folder   = dirname($copy);

        if (!is_dir($folder)) {
            if (false === @mkdir($folder, 0777, true)) {
                throw new FileException(sprintf('Unable to create the "%s" directory', $folder));
            }
        }
        if (false === @copy($file, $copy)) {
            throw new FileException(sprintf('Unable to write in the "%s" directory', $folder));
        }

        return $fileName;
    }

    /**
     * return the file
     */
    public function getFile($media_id, $filename = null)
    {
        if ($media_id instanceof Media) {
            if (null === $filename) {
                $filename = $media_id->getName($extension = false);
            }

            $media_id = $media_id->getId();
        }

        $file = $this->getUploadFolder().$media_id;

        if ($this->exists($file)) {
            if (null === $filename) {
                $filename = basename($file);
            }

            $info      = new \SplFileInfo($file);
            $extension = pathinfo($info->getFilename(), PATHINFO_EXTENSION);

            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.Namer::toLowercase($filename).'.'.$extension);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);

            exit;
        }

        throw new MediaNotFoundException($file);
    }

    /**
     * remove a file
     */
    public function remove($id, $inSubFolders = false)
    {
        $result = true;

        if ($id) {
            $dir     = realpath($this->getUploadFolder()).DIRECTORY_SEPARATOR;
            $files[] = $dir.$id;

            if ($inSubFolders) {
                // TODO find a better way to do this
                $directories = glob($dir.'*_*' , GLOB_ONLYDIR);

                foreach ($directories as $directorie) {
                    $files[] = $directorie.DIRECTORY_SEPARATOR.$id;
                }
            }

            foreach(array_unique($files) as $file) {
                if ($this->exists($file)) {
                    if (false === @unlink($file)) {
                        $result = false;
                        error_log(sprintf('Unable to delete the file "%s"', $file));
                    }
                }
            }
        }

        return $result;
    }

    /**
     * return file existence
     */
    public function exists($file)
    {
        if ($file instanceof Media) {
            if (false === $file->exists()) {
                return false;
            }

            $file = $this->getUploadFolder().$file->getId();
        }

        return file_exists($file);
    }

    /**
     * return webpath
     */
    public function getWebPath(Media $media, $width = 0, $height = 0, $options = array())
    {
        if (false === $media->exists()) {
            return null;
        }

        $folder         = dirname($this->getPath().$media->getId()).'/';
        $filename       = $outputFilename = basename($media->getId());
        $forceResize    = (isset($options['force_resize'])) ? $options['force_resize'] : false;

        if ($media->isImage()) {
            $sourceFolder   = dirname($this->getUploadFolder().$media->getId()).'/';

            if (isset($options['extension']) && in_array($options['extension'], array('jpg', 'jpeg', 'gif', 'png', 'wbmp', 'xbm'))) {
                $ext = pathinfo($sourceFolder.$filename, PATHINFO_EXTENSION);
                if ($ext != $options['extension']) {
                    $outputFilename = substr($filename, 0, strrpos($filename, '.')) . '.' . $options['extension'];
                }
            }

            if ($forceResize || (null !== $media->getWidth() && null !== $media->getHeight() && $media->getWidth() > $width && $media->getHeight() > $height)) {
                if ($width != 0 || $height != 0) {
                    $resizeFolder   = (string)$width.'_'.(string)$height.'/';
                    $folder         .= $resizeFolder;

                    if (!$this->exists($sourceFolder.$resizeFolder.$outputFilename)) {
                        if (!$this->resize($sourceFolder, $sourceFolder.$resizeFolder, $filename, $outputFilename, $width, $height)) {
                            return $folder.$filename;
                        }
                    }
                }
            }
            else if ($filename != $outputFilename) {
                if (!$this->exists($sourceFolder.$outputFilename)) {
                    if (!$this->resize($sourceFolder, $sourceFolder, $filename, $outputFilename)) {
                        return $folder.$filename;
                    }
                }
            }
        }

        return $folder.$outputFilename;
    }

    /**
     * return webpath
     */
    public function getWebPathFromId($id, $isImage, $width = 0, $height = 0, $options = array())
    {
        $folder   = dirname($id).'/';
        $filename = $outputFilename = basename($id);

        if ($isImage) {
            $sourceFolder = dirname($this->getUploadFolder().$id).'/';

            if ($width != 0 || $height != 0) {
                $resizeFolder = (string)$width.'_'.(string)$height.'/';
                $folder      .= $resizeFolder;

                if (isset($options['extension']) && in_array($options['extension'], array('jpg', 'jpeg', 'gif', 'png', 'wbmp', 'xbm'))) {
                    $ext = pathinfo($sourceFolder.$filename, PATHINFO_EXTENSION);
                    if ($ext != $options['extension']) {
                        $outputFilename = substr($filename, 0, strrpos($filename, '.')) . '.' . $options['extension'];
                    }
                }

                if (!$this->exists($sourceFolder.$resizeFolder.$outputFilename)) {
                    if (!$this->resize($sourceFolder, $sourceFolder.$resizeFolder, $filename, $outputFilename, $width, $height)) {
                        return $this->getPath().$folder.$filename;
                    }
                }
            }
            else if ($filename != $outputFilename) {
                if (!$this->exists($sourceFolder.$outputFilename)) {
                    if (!$this->resize($sourceFolder, $sourceFolder, $filename, $outputFilename)) {
                        return $this->getPath().$folder.$filename;
                    }
                }
            }
        }

        return $this->getPath().$folder.$outputFilename;
    }

    /**
     * return string
     */
    public function guessMime(\SplFileInfo $file)
    {
        $guesser = MimeTypeGuesser::getInstance();

        return $guesser->guess($file);
    }

    /**
     * return array
     */
    public function guessImageSize(\SplFileInfo $file)
    {
        if (!in_array($this->guessMime($file), Media::imagesMime())) {
            return array(null, null);
        }

        list($width, $height) = getimagesize($file);

        return array($width, $height);
    }

    private function resize($src, $dest, $id, $output, $width = 0, $height = 0)
    {
        if (!$this->exists($origin = $src.$id)) {
            return;
        }

        if (!is_dir($dest)) {
            if (false === @mkdir($dest, 0777, true)) {
                throw new FileException(sprintf('Unable to create the "%s" directory', $dest));
            }
        } elseif (!is_writable($dest)) {
            throw new FileException(sprintf('Unable to write in the "%s" directory', $dest));
        }

        try {
            $imagine = new Imagine();
            $image   = $imagine->open($src.$id);

            if ($width && $height) {
                $image->resize(new Box($width, $height));
            } elseif ($height == 0) {
                $image->resize($image->getSize()->widen($width));
            } elseif ($width == 0) {
                $image->resize($image->getSize()->heighten($height));
            }

            $image->save($dest.$output);

            return true;
        }
        catch (Imagine\Exception\Exception $e) {
            return false;
        }
    }

    /**
     * return an unique filename
     */
    private function generateFileName(\SplFileInfo $file)
    {
        $extension = $file->guessExtension();

        if (!$extension || null === $extension) {
            $extension = 'bin';
        } else if ($extension == 'jpeg') {
             $extension = 'jpg';
        }

        $dateFolder = '';
        if ($this->dateFolder) {
            $dateFolder = date("Y/m/d/");
        }

        $i = 0;
        do {
            $fileName = md5(uniqid().time().$i).'.'.$extension;

            $preFolder = '';
            if ($this->dateFolder) {
                $preFolder = substr($fileName, 0, 1).'/';
            }

            $i++;
        } while ($this->exists($this->getUploadFolder().$dateFolder.$preFolder.$fileName) && $i < 10000);

        return $dateFolder.$preFolder.$fileName;
    }

}
