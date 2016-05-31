<?php
namespace Staticus\Resources\Middlewares\Image;

use Staticus\Resources\Commands\DeleteImageSizesResourceCommand;
use Staticus\Resources\File\ResourceDO;
use Staticus\Resources\Middlewares\SaveResourceMiddlewareAbstract;
use Staticus\Resources\ResourceDOInterface;
use Staticus\Resources\Image\ResourceImageDO;

abstract class SaveImageMiddlewareAbstract extends SaveResourceMiddlewareAbstract
{
    protected function copyFileToDefaults(ResourceDOInterface $resourceDO)
    {
        /** @var ResourceImageDO $resourceDO */
        if (ResourceDO::DEFAULT_VARIANT !== $resourceDO->getVariant()) {
            $defaultDO = clone $resourceDO;
            $defaultDO->setVariant();
            $defaultDO->setVersion();
            $defaultDO->setWidth();
            $defaultDO->setHeight();
            $this->copyResource($resourceDO, $defaultDO);
        }
        if (ResourceDO::DEFAULT_VERSION !== $resourceDO->getVersion()) {
            $defaultDO = clone $resourceDO;
            $defaultDO->setVersion();
            $defaultDO->setWidth();
            $defaultDO->setHeight();
            $this->copyResource($resourceDO, $defaultDO);
        }
        if (ResourceImageDO::DEFAULT_SIZE !== $resourceDO->getSize()) {
            $defaultDO = clone $resourceDO;
            $defaultDO->setWidth();
            $defaultDO->setHeight();
            $this->copyResource($resourceDO, $defaultDO);
        }
    }
    protected function afterSave(ResourceDOInterface $resourceDO)
    {
        // If the basic version replaced and resources looks equal
        if (ResourceImageDO::DEFAULT_SIZE === $resourceDO->getSize()) {
            $command = new DeleteImageSizesResourceCommand($resourceDO, $this->filesystem);
            $command();
        }
    }
    protected function backup(ResourceDOInterface $resourceDO)
    {

        return ResourceImageDO::DEFAULT_SIZE === $resourceDO->getSize()
            ? parent::backup($resourceDO)
            : $resourceDO;
    }
    protected function destroyEqual(ResourceDOInterface $resourceDO, ResourceDOInterface $backupResourceVerDO)
    {
        return ResourceImageDO::DEFAULT_SIZE === $resourceDO->getSize()
            ? parent::destroyEqual($resourceDO, $backupResourceVerDO)
            : $resourceDO;
    }
}
