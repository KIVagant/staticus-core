<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\ResourceDOInterface;

class FindResourceLastVersionCommand implements ResourceCommandInterface
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
        $command = new FindResourceOptionsCommand($this->resourceDO, $this->filesystem);
        $result = $command([
            'version',
        ]);
        $lastVersion = 0;
        if (!empty($result)) {
            array_filter($result, function ($found) use (&$lastVersion) {
                $found = (int)$found['version'];
                $lastVersion = $found > $lastVersion
                    ? $found
                    : $lastVersion;

                return false;
            });
        }

        return $lastVersion;
    }
}