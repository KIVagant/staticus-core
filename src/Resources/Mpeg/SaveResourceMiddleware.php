<?php
namespace Staticus\Resources\Mpeg;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Middlewares\SaveResourceMiddlewareAbstract;
use Staticus\Resources\ResourceDOInterface;
use Staticus\Config\ConfigInterface;

class SaveResourceMiddleware extends SaveResourceMiddlewareAbstract
{
    public function __construct(ResourceDO $resourceDO, FilesystemInterface $filesystem, ConfigInterface $config)
    {
        parent::__construct($resourceDO, $filesystem, $config);
    }
    protected function afterSave(ResourceDOInterface $resourceDO) {}
}