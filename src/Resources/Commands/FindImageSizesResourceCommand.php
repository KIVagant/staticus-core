<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Image\ResourceImageDOInterface;

class FindImageSizesResourceCommand implements ResourceCommandInterface
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

    public function __invoke($allowedSizes = [])
    {
        $result = $this->execute();

        return $result;
    }

    /**
     * @return array|string
     */
    protected function execute()
    {
        $command = $this->getFindSizesCommand();
        $result = shell_exec($command);
        $matches = [];
        if (preg_match_all('/\/(?P<size>\d+x\d+)\//', $result, $matches)) {
            $result = $matches['size'];

            return $result;
        }

        return [];
    }
}