<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\ResourceDOInterface;

class FindVersionsResourceCommand implements ResourceCommandInterface
{
    use ShellFindCommandTrait;
    /**
     * @var ResourceDOInterface
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

    public function __invoke()
    {
        $uuid = $this->resourceDO->getUuid();
        $type = $this->resourceDO->getType();
        $variant = $this->resourceDO->getVariant();
        $baseDir = $this->resourceDO->getBaseDirectory();
        $namespace = $this->resourceDO->getNamespace();

        return $this->findAllVersions($baseDir, $namespace, $uuid, $type, $variant);
    }
}