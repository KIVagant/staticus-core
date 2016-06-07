<?php
namespace Staticus\Resources\Middlewares\Image;

use Staticus\Resources\Commands\DeleteImageSizesResourceCommand;
use Staticus\Resources\File\ResourceDO;
use Staticus\Resources\Image\ResourceImageDOInterface;
use Staticus\Resources\Middlewares\SaveResourceMiddlewareAbstract;
use Staticus\Resources\ResourceDOInterface;
use Staticus\Resources\Image\ResourceImageDO;

abstract class SaveImageMiddlewareAbstract extends SaveResourceMiddlewareAbstract
{
    protected function copyFileToDefaults(ResourceDOInterface $resourceDO)
    {
        /** @var ResourceImageDO $resourceDO */
        if (
            ResourceDO::DEFAULT_VARIANT !== $resourceDO->getVariant()
            && $this->config->get('staticus.magic_defaults.variant')
        ) {
            $defaultDO = clone $resourceDO;
            $defaultDO->setVariant();
            $defaultDO->setVersion();
            $defaultDO->setWidth();
            $defaultDO->setHeight();
            $this->copyResource($resourceDO, $defaultDO);
        }
        if (
            ResourceDO::DEFAULT_VERSION !== $resourceDO->getVersion()
            && $this->config->get('staticus.magic_defaults.version')
        ) {
            $defaultDO = clone $resourceDO;
            $defaultDO->setVersion();
            $defaultDO->setWidth();
            $defaultDO->setHeight();
            $this->copyResource($resourceDO, $defaultDO);
        }
        if (
            ResourceImageDO::DEFAULT_DIMENSION !== $resourceDO->getDimension()
            && $this->config->get('staticus.magic_defaults.dimension')
        ) {
            $defaultDO = clone $resourceDO;
            $defaultDO->setWidth();
            $defaultDO->setHeight();
            $this->copyResource($resourceDO, $defaultDO);
        }
    }
    protected function afterSave(ResourceDOInterface $resourceDO)
    {
        /** @var ResourceImageDOInterface $resourceDO */
        if (ResourceImageDO::DEFAULT_DIMENSION === $resourceDO->getDimension()) {
            $command = new DeleteImageSizesResourceCommand($resourceDO, $this->filesystem);
            $command();
        }
    }
    protected function backup(ResourceDOInterface $resourceDO)
    {
        /** @var ResourceImageDOInterface $resourceDO */
        return ResourceImageDO::DEFAULT_DIMENSION === $resourceDO->getDimension()
            ? parent::backup($resourceDO)
            : $resourceDO;
    }
    protected function destroyEqual(ResourceDOInterface $resourceDO, ResourceDOInterface $backupResourceVerDO)
    {
        /** @var ResourceImageDOInterface $resourceDO */
        return ResourceImageDO::DEFAULT_DIMENSION === $resourceDO->getDimension()
            ? parent::destroyEqual($resourceDO, $backupResourceVerDO)
            : $resourceDO;
    }
}
