<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Image\ResourceImageDO;
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
        $command = new FindResourceOptionsCommand($this->resourceDO, $this->filesystem);
        $result = $command([
            ResourceImageDO::TOKEN_DIMENSION,
        ]);
        if (is_array($result) && $result) {
            foreach ($result as $dimension) {
                $dimension = array_key_exists(ResourceImageDO::TOKEN_DIMENSION, $dimension)
                    ? $dimension[ResourceImageDO::TOKEN_DIMENSION]
                    : '';
                $dimension = explode('x', $dimension);
                if ($dimension && 2 === count($dimension)) {
                    $destroyDO = clone $this->resourceDO;
                    $destroyDO->setWidth($dimension[0]);
                    $destroyDO->setHeight($dimension[1]);
                    $command = new DestroyResourceCommand($destroyDO, $this->filesystem);
                    $command(true);
                }
            }
        }

        return true;
    }
}