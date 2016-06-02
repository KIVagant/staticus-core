<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\ResourceDOInterface;

class BackupResourceCommand implements ResourceCommandInterface
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
     * @param null $lastVersion You can set the last existing version manually, if needed
     * @return ResourceDOInterface|int
     */
    public function __invoke($lastVersion = null)
    {
        if (null === $lastVersion) {
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
        }

        return $this->backupResource($lastVersion + 1);
    }

    /**
     * @param $newVersion
     */
    protected function backupResource($newVersion)
    {
        $backupResourceDO = clone $this->resourceDO;
        $backupResourceDO->setVersion($newVersion);
        $command = new CopyResourceCommand($this->resourceDO, $backupResourceDO, $this->filesystem);

        return $command();
    }
}