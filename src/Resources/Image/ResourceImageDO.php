<?php
namespace Staticus\Resources\Image;
use Staticus\Resources\ResourceDOAbstract;

/**
 * Domain Object
 * @package Staticus\Resources\File
 */
abstract class ResourceImageDO extends ResourceDOAbstract implements ResourceImageDOInterface
{
    const DEFAULT_WIDTH = 0;
    const DEFAULT_HEIGHT = 0;
    const DEFAULT_DIMENSION = 0;
    const TOKEN_DIMENSION = 'dimension';

    protected $width = 0;
    protected $height = 0;

    /**
     * @var CropImageDOInterface
     */
    protected $crop;

    public function reset()
    {
        parent::reset();
        $this->type = static::TYPE;
        $this->width = 0;
        $this->height = 0;
        $this->crop = null;
        return $this;
    }

    /**
     * You can't change the concrete ImageType
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        return $this;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param mixed $width
     * @return ResourceImageDO
     */
    public function setWidth($width = self::DEFAULT_WIDTH)
    {
        $this->width = (int)$width;
        $this->setFilePath();

        return $this;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param mixed $height
     * @return ResourceImageDO
     */
    public function setHeight($height = self::DEFAULT_HEIGHT)
    {
        $this->height = (int)$height;
        $this->setFilePath();

        return $this;
    }

    protected function setFilePath()
    {
        $this->filePath = $this->generateFilePath();
    }

    /**
     * Map of the resource directory elements.
     * For example, you can use it with the strtok() method. Or for routes buildings.
     *
     * @return array
     * @see strtok()
     * @example strtok($relative_path, '/');
     */
    public function getDirectoryTokens()
    {
        $tokens = parent::getDirectoryTokens();
        $tokens[static::TOKEN_DIMENSION] = $this->getDimension() . DIRECTORY_SEPARATOR;

        return $tokens;
    }

    /**
     * @return int|string
     */
    public function getDimension()
    {
        $width = $this->getWidth();
        $height = $this->getHeight();
        $size = ($width > 0 && $height > 0)
            ? $width . 'x' . $height
            : static::DEFAULT_DIMENSION;

        return $size;
    }

    /**
     * @return CropImageDOInterface|null
     */
    public function getCrop()
    {
        return $this->crop;
    }

    /**
     * @param CropImageDOInterface $crop
     * @return ResourceImageDO
     */
    public function setCrop(CropImageDOInterface $crop = null)
    {
        $this->crop = $crop;
        return $this;
    }

    public function toArray()
    {
        $data = parent::toArray();
        if ($this->crop) {
            $data['crop'] = $this->crop->toArray();
        } else {
            unset($data['crop']);
        }
        $data[self::TOKEN_DIMENSION] = '' . $this->getDimension();

        return $data;
    }

}