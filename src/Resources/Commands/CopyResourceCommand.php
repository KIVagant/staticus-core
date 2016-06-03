<?php

namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Exceptions\CommandErrorException;
use Staticus\Resources\ResourceDOInterface;

class CopyResourceCommand implements ResourceCommandInterface
{
    /**
     * @var ResourceDOInterface
     */
    protected $originResourceDO;
    /**
     * @var ResourceDOInterface
     */
    protected $newResourceDO;
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    public function __construct(ResourceDOInterface $originResourceDO, ResourceDOInterface $newResourceDO, FilesystemInterface $filesystem)
    {
        $this->originResourceDO = $originResourceDO;
        $this->newResourceDO = $newResourceDO;
        $this->filesystem = $filesystem;
    }

    /**
     * @param bool $replace Replace exist file or just do nothing
     * @return ResourceDOInterface
     * @throws CommandErrorException
     */
    public function __invoke($replace = false)
    {
        if (!$this->originResourceDO->getName() || !$this->originResourceDO->getType()) {
            throw new CommandErrorException('Source resource cannot be empty');
        }
        if (!$this->newResourceDO->getName() || !$this->newResourceDO->getType()) {
            throw new CommandErrorException('Destination resource cannot be empty');
        }
        $originPath = $this->originResourceDO->getFilePath();
        $newPath = $this->newResourceDO->getFilePath();
        if ($originPath === $newPath) {
            throw new CommandErrorException('Source and destination paths is equal');
        }
        if (!$this->filesystem->has($originPath)) {
            throw new CommandErrorException('Origin file is not exists: ' . $originPath);
        }
        $exists = $this->filesystem->has($newPath);
        if (!$exists || $replace) {
            $this->copyFile($originPath, $newPath, $exists && $replace);

            return $this->newResourceDO;
        }

        return $this->originResourceDO;
    }

    protected function copyFile($fromFullPath, $toFullPath, $replace = false)
    {
        $this->createDirectory(dirname($toFullPath));
        if ($replace) {
            $this->filesystem->delete($toFullPath);
        }
        if (!$this->filesystem->copy($fromFullPath, $toFullPath)) {
            throw new CommandErrorException('File cannot be copied to the path ' . $toFullPath);
        }
    }

    protected function createDirectory($directory)
    {
        if (!$this->filesystem->createDir($directory)) {
            throw new CommandErrorException('Can\'t create a directory: ' . $directory);
        }
    }
}