<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\ResourceDOInterface;

class DestroyEqualResourceCommand implements ResourceCommandInterface
{
    const HASH_ALGORITHM = 'md5';
    /**
     * @var ResourceDOInterface
     */
    protected $originResourceDO;
    /**
     * @var ResourceDOInterface
     */
    protected $suspectResourceDO;
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * @param ResourceDOInterface $originResourceDO
     * @param ResourceDOInterface $suspectResourceDO This resource will be deleted, if equal to $originResourceDO
     * @param FilesystemInterface $filesystem
     */
    public function __construct(ResourceDOInterface $originResourceDO, ResourceDOInterface $suspectResourceDO, FilesystemInterface $filesystem)
    {
        $this->originResourceDO = $originResourceDO;
        $this->suspectResourceDO = $suspectResourceDO;
        $this->filesystem = $filesystem;
    }

    /**
     * @return ResourceDOInterface
     */
    public function __invoke()
    {
        $originName = $this->originResourceDO->getName();
        $suspectName = $this->suspectResourceDO->getName();
        $originType = $this->originResourceDO->getType();
        $suspectType = $this->suspectResourceDO->getType();
        if (!$originName || !$originType) {
            throw new CommandErrorException('Invalid destroy equal request: the origin resource is empty');
        }
        if (!$suspectName || !$suspectType) {
            throw new CommandErrorException('Invalid destroy equal request: the suspect resource is empty');
        }

        $originFilePath = $this->originResourceDO->getFilePath();
        $suspectFilePath = $this->suspectResourceDO->getFilePath();

        // Unfortunately, this condition can not always work fine.
        // Because some Middlewares can compress, resize etc. the resource that saved before
        // and the second uploaded copy will never be equal
        if ($originType === $suspectType
            && $this->filesystem->has($originFilePath) === $this->filesystem->has($suspectFilePath)
            && $this->filesystem->getSize($originFilePath) === $this->filesystem->getSize($suspectFilePath)
            && $this->getFileHash($originFilePath) === $this->getFileHash($suspectFilePath)
        ) {
            $command = new DestroyResourceCommand($this->suspectResourceDO, $this->filesystem);

            return $command(true);
        }

        return $this->originResourceDO;
    }

    public function getFileHash($path)
    {
        $stream = $this->filesystem->readStream($path);
        if ($stream !== false) {
            $context = hash_init(self::HASH_ALGORITHM);
            hash_update_stream($context, $stream);

            return hash_final($context);
        }

        return false;
    }
}