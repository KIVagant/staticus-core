<?php
namespace Staticus\Resources\Commands;

trait ShellFindImagesTrait
{
    protected function getFindSizesCommand()
    {
        $command = 'find '
            . $this->resourceDO->getBaseDirectory()
            . ($this->resourceDO->getNamespace() ? $this->resourceDO->getNamespace() . DIRECTORY_SEPARATOR : '')
            . $this->resourceDO->getType() . DIRECTORY_SEPARATOR
            . $this->resourceDO->getVariant() . DIRECTORY_SEPARATOR
            . $this->resourceDO->getVersion() . DIRECTORY_SEPARATOR
            . '*x*' . DIRECTORY_SEPARATOR // only non-zero image sizes
            . ' -type f -name ' . $this->resourceDO->getUuid() . '.' . $this->resourceDO->getType();

        return $command;
    }
}