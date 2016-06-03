<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Exceptions\CommandErrorException;
use Staticus\Resources\ResourceDOAbstract;
use Staticus\Resources\ResourceDOInterface;

/** @noinspection PropertyCanBeStaticInspection */
class FindResourceOptionsCommand implements ResourceCommandInterface
{
    const DIRECTORY_RELATIVE = 'directory_relative';
    /**
     * @var ResourceDOInterface
     */
    protected $resourceDO;
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    protected $allowedProperties = [];

    public function __construct(ResourceDOInterface $resourceDO, FilesystemInterface $filesystem)
    {
        $this->resourceDO = $resourceDO;
        $this->filesystem = $filesystem;
    }

    public function __invoke(array $filter = [])
    {
        $this->allowedProperties = $filter;
        $options = $this->findAllResourceOptions($this->resourceDO);

        return $this->filterResourceOptions($options);
    }

    public function filterResourceOptions($options)
    {
        if (!empty($this->allowedProperties)) {
            foreach ($options as &$item) {
                $item = array_filter($item, [$this, 'filterAllowedProperties'], ARRAY_FILTER_USE_BOTH);
            }
            $options = array_filter($options);
        }

        return $options;
    }

    protected function findAllResourceOptions(ResourceDOInterface $resourceDO)
    {
        /** @var \League\Flysystem\FilesystemInterface $filesystem */
        $filesystem = $this->filesystem;
        $uuid = $resourceDO->getUuid();
        $type = $resourceDO->getType();
        $name = $resourceDO->getName();
        if (!$name || !$type) {
            throw new CommandErrorException('Can not look for options: resource is empty');
        }
        $basename = $resourceDO->getBaseDirectory();
        $namespace = $resourceDO->getNamespace();
        $path = $basename
            . ($namespace ? $namespace . DIRECTORY_SEPARATOR : '')
            . $type . DIRECTORY_SEPARATOR
        ;
        $found = $filesystem->listContents($path, true);
        $found = array_filter($found, function($file) use ($uuid, $type) {

            return array_key_exists('filename', $file)
            && $file['filename'] === $uuid
            && array_key_exists('extension', $file)
            && $file['extension'] === $type
            && array_key_exists('type', $file)
            // Warning: type field will be replaced by resource
            && $file['type'] === 'file'

                ;
        });
        array_walk(
            $found
            , [$this, 'hydrateElementFile']
            , [
                'resourceDO' => $resourceDO,
            ]
        );
        $found = array_values($found); // reset keys

        return $found;
    }

    protected function hydrateElementFile(&$file, $key, $args)
    {
        if (!array_key_exists('resourceDO', $args) || !$args['resourceDO'] instanceof ResourceDOInterface) {
            throw new CommandErrorException('Method expects ResourceDO in arguments');
        }

        /** @var ResourceDOInterface $resourceDO */
        $resourceDO = $args['resourceDO'];
        $file[static::DIRECTORY_RELATIVE] = str_replace(
            $resourceDO->getBaseDirectory()
            . ($resourceDO->getNamespace() ? $resourceDO->getNamespace() . DIRECTORY_SEPARATOR : '')
            , ''
            , DIRECTORY_SEPARATOR . $file['dirname']
        );
        $tokens = $resourceDO->getDirectoryTokens();
        $this->splitDirectoryByTokens($file, array_keys($tokens));
    }

    protected function filterAllowedProperties($value, $key)
    {

        return in_array($key, $this->allowedProperties, true);
    }

    /**
     * @param $file
     * @param $tokens
     * @return mixed
     */
    protected function splitDirectoryByTokens(&$file, $tokens)
    {
        $split = null;
        foreach ($tokens as $token) {
            if (ResourceDOAbstract::TOKEN_BASEDIRECTORY === $token) {
                continue;
            }
            if (ResourceDOAbstract::TOKEN_NAMESPACE === $token) {
                continue;
            }
            $split = null === $split
                ? strtok($file[static::DIRECTORY_RELATIVE], DIRECTORY_SEPARATOR)
                : strtok(DIRECTORY_SEPARATOR);
            $file[$token] = $split;
        }
        while (($split = strtok(DIRECTORY_SEPARATOR)) !== false) {
            $file['sub-options'][] = $split;
        }
    }
}