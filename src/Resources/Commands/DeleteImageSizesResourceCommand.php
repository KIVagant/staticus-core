<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Image\ResourceImageDOInterface;

class DeleteImageSizesResourceCommand implements ResourceCommandInterface
{
    use ShellFindImagesTrait;
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
        $command = $this->getFindSizesCommand();
        $command .= ' -delete';
        shell_exec($command . '> /dev/null 2>&1');

        return true;
    }
}