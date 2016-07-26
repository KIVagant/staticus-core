<?php
namespace Staticus\Resources\Png;

use League\Flysystem\FilesystemInterface;
use Staticus\Config\ConfigInterface;
use Staticus\Resources\Middlewares\Image\ImageResizeMiddlewareAbstract;

class ResizeMiddleware extends ImageResizeMiddlewareAbstract
{
    public function __construct(ResourceDO $resourceDO, FilesystemInterface $filesystem, ConfigInterface $config)
    {
        parent::__construct($resourceDO, $filesystem, $config);
    }
}
