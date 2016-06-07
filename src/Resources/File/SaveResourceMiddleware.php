<?php
namespace Staticus\Resources\File;

use League\Flysystem\FilesystemInterface;
use Staticus\Config\ConfigInterface;
use Staticus\Resources\Middlewares\SaveResourceMiddlewareAbstract;
use Staticus\Resources\ResourceDOInterface;

class SaveResourceMiddleware extends SaveResourceMiddlewareAbstract
{
    public function __construct(ResourceDO $resourceDO, FilesystemInterface $filesystem, ConfigInterface $config)
    {
        parent::__construct($resourceDO, $filesystem, $config);
    }
    protected function afterSave(ResourceDOInterface $resourceDO) {}
}