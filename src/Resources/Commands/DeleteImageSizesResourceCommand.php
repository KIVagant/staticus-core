<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Image\ResourceImageDOInterface;

class DeleteImageSizesResourceCommand implements ResourceCommandInterface
{
    /**
     * @var ResourceImageDOInterface
     */
    protected $resourceDO;
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    public function __construct(ResourceImageDOInterface $resourceDO, FilesystemInterface $filesystem)
    {
        $this->resourceDO = $resourceDO;
        $this->filesystem = $filesystem;
    }

    public function __invoke()
    {
        return $this->execute();
    }

    protected function execute()
    {
        $command = 'find '
            . $this->resourceDO->getBaseDirectory()
            . ($this->resourceDO->getNamespace() ? $this->resourceDO->getNamespace() . DIRECTORY_SEPARATOR : '')
            . $this->resourceDO->getType() . DIRECTORY_SEPARATOR
            . $this->resourceDO->getVariant() . DIRECTORY_SEPARATOR
            . $this->resourceDO->getVersion() . DIRECTORY_SEPARATOR
            . '*x*' . DIRECTORY_SEPARATOR // only non-zero image sizes
            . ' -type f -name ' . $this->resourceDO->getUuid() . '.' . $this->resourceDO->getType();

        $command .= ' -delete';
        shell_exec($command . '> /dev/null 2>&1');

        return true;
    }
}