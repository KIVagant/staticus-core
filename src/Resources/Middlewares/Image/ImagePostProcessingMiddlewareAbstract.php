<?php
namespace Staticus\Resources\Middlewares\Image;

use League\Flysystem\FilesystemInterface;
use Staticus\Diactoros\Response\ResourceDoResponse;
use Staticus\Middlewares\MiddlewareAbstract;
use Staticus\Resources\Exceptions\SaveResourceErrorException;
use Staticus\Resources\Image\ResourceImageDOInterface;
use Staticus\Resources\ResourceDOInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Stratigility\Http\Response;

abstract class ImagePostProcessingMiddlewareAbstract extends MiddlewareAbstract
{
    /**
     * @var ResourceImageDOInterface
     */
    protected $resourceDO;
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    public function __construct(ResourceDOInterface $resourceDO, FilesystemInterface $filesystem)
    {
        $this->resourceDO = $resourceDO;
        $this->filesystem = $filesystem;
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    protected function isSupportedResponse(ResponseInterface $response)
    {
        return $response instanceof EmptyResponse
        || $response instanceof ResourceDoResponse
        || $response instanceof Response;
    }

    /**
     * @return \Staticus\Resources\Image\ResourceImageDOInterface
     */
    protected function getResourceWithoutSizes()
    {
        $modelResourceDO = clone $this->resourceDO;
        $modelResourceDO->setWidth();
        $modelResourceDO->setHeight();

        return $modelResourceDO;
    }

    /**
     * @param $directory
     * @throws SaveResourceErrorException
     * @see \Staticus\Resources\Middlewares\SaveResourceMiddlewareAbstract::createDirectory
     */
    protected function createDirectory($directory)
    {
        if (!$this->filesystem->createDir($directory)) {
            throw new SaveResourceErrorException('Can\'t create a directory: ' . $directory);
        }
    }

    protected function getImagick($sourcePath)
    {
        if (!class_exists(\Imagick::class)) {
            throw new SaveResourceErrorException('Imagick is not installed');
        }

        return new \Imagick(realpath($sourcePath));
    }
}