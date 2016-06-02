<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Exceptions\CommandErrorException;
use Staticus\Resources\ResourceDOInterface;

class DestroyResourceCommand implements ResourceCommandInterface
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
            throw new CommandErrorException('Invalid destroy request');
        }
        if ($byPathOnly) {
            if (!$this->filesystem->delete($filePath)) {
                throw new CommandErrorException('The file cannot be removed: ' . $filePath);
            }
        } else {
            $command = $this->getShellFindCommand($baseDir, $namespace, $uuid, $type, $variant, $version);
            $command .= ' -delete';
            shell_exec($command . '> /dev/null 2>&1');
        }

        return $this->resourceDO;
    }

    /**
     * @param $baseDir
     * @param $namespace
     * @param $uuid
     * @param $type
     * @param string $variant
     * @param int $version
     * @return string
     * @deprecated
     * @todo: replace to FlySystem
     */
    protected function getShellFindCommand($baseDir, $namespace, $uuid, $type, $variant = ResourceDOInterface::DEFAULT_VARIANT, $version = ResourceDOInterface::DEFAULT_VERSION)
    {
        if ($namespace) {
            $namespace .= DIRECTORY_SEPARATOR;
        }
        $command = 'find ';
        if ($version !== ResourceDOInterface::DEFAULT_VERSION) {
            $command .= $baseDir . $namespace . $type . DIRECTORY_SEPARATOR . $variant . DIRECTORY_SEPARATOR . $version . DIRECTORY_SEPARATOR;
        } elseif ($variant !== ResourceDOInterface::DEFAULT_VARIANT) {
            $command .= $baseDir . $namespace . $type . DIRECTORY_SEPARATOR . $variant . DIRECTORY_SEPARATOR;
        } else {
            $command .= $baseDir . $namespace . $type . DIRECTORY_SEPARATOR;
        }

        $command .= ' -type f -name ' . $uuid . '.' . $type;

        return $command;
    }
}