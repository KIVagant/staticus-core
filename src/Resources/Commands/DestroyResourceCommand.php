<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Exceptions\CommandErrorException;
use Staticus\Resources\ResourceDOAbstract;
use Staticus\Resources\ResourceDOInterface;

class DestroyResourceCommand implements ResourceCommandInterface
{
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

    /**
     * @param bool $byPathOnly If true, no search on disk will be executed
     * @return ResourceDOInterface
     */
    public function __invoke($byPathOnly = false)
    {
        $uuid = $this->resourceDO->getUuid();
        $type = $this->resourceDO->getType();
        $variant = $this->resourceDO->getVariant();
        $version = $this->resourceDO->getVersion();
        $baseDir = $this->resourceDO->getBaseDirectory();
        $namespace = $this->resourceDO->getNamespace();
        $filePath = $this->resourceDO->getFilePath();
        if (!$uuid || !$type || !$baseDir || !$filePath) {
            throw new CommandErrorException('Cannot destroy the empty resource');
        }
        if ($byPathOnly) {
            $this->deleteFile($filePath);
        } else {
            $command = new FindResourceOptionsCommand($this->resourceDO, $this->filesystem);
            $result = $command();
            foreach ($result as $item) {
                if (
                    $item[ResourceDOAbstract::TOKEN_TYPE] !== $type
                    || $item['filename'] !== $uuid
                    || ($namespace && ($item[ResourceDOAbstract::TOKEN_NAMESPACE] !== $namespace))
                ) {
                    continue;
                }
                if ($version !== ResourceDOInterface::DEFAULT_VERSION) {
                    if (
                        $variant === $item[ResourceDOAbstract::TOKEN_VARIANT] // delete versions only for current variant
                        && $version === (int)$item[ResourceDOAbstract::TOKEN_VERSION]
                    ) {
                        $this->deleteFile($item['path']);
                    }
                } elseif ($variant !== ResourceDOInterface::DEFAULT_VARIANT) {
                    if ($variant === $item[ResourceDOAbstract::TOKEN_VARIANT]) {
                        $this->deleteFile($item['path']);
                    }
                } else {
                    $this->deleteFile($item['path']);
                }
            }
        }

        return $this->resourceDO;
    }

    protected function deleteFile($filePath)
    {
        // If file is already gone somewhere, it is OK for us
        if ($this->filesystem->has($filePath) && !$this->filesystem->delete($filePath)) {
            throw new CommandErrorException('The file cannot be removed: ' . $filePath);
        }
    }
}