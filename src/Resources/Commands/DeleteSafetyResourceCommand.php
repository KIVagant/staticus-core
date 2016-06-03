<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Exceptions\CommandErrorException;
use Staticus\Resources\ResourceDOInterface;

class DeleteSafetyResourceCommand implements ResourceCommandInterface
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

    public function __invoke()
    {
        $version = $this->resourceDO->getVersion();
        $filePath = $this->resourceDO->getFilePath();
        if (!$this->resourceDO->getName() || !$this->resourceDO->getType() || !$this->resourceDO->getBaseDirectory()) {
            throw new CommandErrorException('Cannot delete empty resource');
        }
        if ($this->filesystem->has($filePath)) {

            // Make backup of the default version
            if (ResourceDOInterface::DEFAULT_VERSION === $version) {
                $lastVersion = $this->findLastVersion();

                // But only if previous existing version is not the default and not has the same content as deleting
                if (ResourceDOInterface::DEFAULT_VERSION !== $lastVersion) {
                    $lastVersionResourceDO = clone $this->resourceDO;
                    $lastVersionResourceDO->setVersion($lastVersion);
                    $command = new DestroyEqualResourceCommand(
                        $lastVersionResourceDO
                        , $this->resourceDO
                        , $this->filesystem
                    );
                    $result = $command();
                    if ($result === $this->resourceDO) {

                        // If the previous file version already the same, current version is already deleted
                        // and backup and yet another deletion is not needed anymore
                        return $this->resourceDO;
                    }
                }

                $command = new BackupResourceCommand($this->resourceDO, $this->filesystem);
                $command($lastVersion);
            }

            $this->deleteFile($filePath);

            return $this->resourceDO;
        }

        return $this->resourceDO;
    }

    /**
     * @param $filePath
     */
    protected function deleteFile($filePath)
    {
        if (!$this->filesystem->delete($filePath)) {
            throw new CommandErrorException('The file cannot be removed: ' . $filePath);
        }
    }

    /**
     * @return int
     */
    protected function findLastVersion()
    {
        $command = new FindResourceLastVersionCommand($this->resourceDO, $this->filesystem);

        return $command();
    }
}