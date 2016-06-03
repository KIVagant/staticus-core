<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Exceptions\CommandErrorException;
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
     * @param ResourceDOInterface $originResourceDO This is a model resource for comparing
     * @param ResourceDOInterface $suspectResourceDO This resource will be deleted, if it's equal to $originResourceDO
     * @param FilesystemInterface $filesystem
     */
    public function __construct(ResourceDOInterface $originResourceDO, ResourceDOInterface $suspectResourceDO, FilesystemInterface $filesystem)
    {
        $this->originResourceDO = $originResourceDO;
        $this->suspectResourceDO = $suspectResourceDO;
        $this->filesystem = $filesystem;
    }

    /**
     * @return ResourceDOInterface SuspectResource if it have been deleted or OriginResource if the Suspect is not equal
     */
    public function __invoke()
    {
        $originName = $this->originResourceDO->getName();
        $suspectName = $this->suspectResourceDO->getName();
        $originType = $this->originResourceDO->getType();
        $suspectType = $this->suspectResourceDO->getType();
        $originFilePath = $this->originResourceDO->getFilePath();
        $suspectFilePath = $this->suspectResourceDO->getFilePath();

        if (!$originName || !$originType) {
            throw new CommandErrorException('Cannot destroy equal resource: the origin resource is empty');
        }
        if (!$suspectName || !$suspectType) {
            throw new CommandErrorException('Cannot destroy equal resource: the suspect resource is empty');
        }
        if ($originFilePath === $suspectFilePath) {
            throw new CommandErrorException('Cannot destroy equal resource: Origin and Suspect have same paths');
        }

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