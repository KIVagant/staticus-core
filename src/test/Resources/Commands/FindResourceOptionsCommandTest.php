<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Staticus\Resources\File\ResourceDO;
use Staticus\Resources\ResourceDOAbstract;

require_once 'AddWrongFilesToDiskHelper.php';

class FindResourceOptionsCommandTest extends \PHPUnit_Framework_TestCase
{
    const BASE_DIR = '/this/is/a/test';

    /**
     * @var ResourceDO
     */
    protected $resourceDO;
    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var AddWrongFilesToDiskHelper
     */
    protected $wrongFiles;

    protected function setUp()
    {
        parent::setUp();
        $this->resourceDO = new ResourceDO();
        $this->filesystem = new Filesystem(new MemoryAdapter());
        $this->wrongFiles = new AddWrongFilesToDiskHelper($this->filesystem, $this);
    }

    /**
     * @return FindResourceOptionsCommand
     */
    public function getCommand(ResourceDO $resourceDO)
    {
        return new FindResourceOptionsCommand($resourceDO, $this->filesystem);
    }

    /**
     * @return ResourceDO
     */
    public function getResourceDO()
    {
        return clone $this->resourceDO;
    }

    /**
     * @expectedException \Staticus\Resources\Exceptions\CommandErrorException
     * @expectedExceptionMessage Can not look for options: resource is empty
     */
    public function testFindEmptyResource()
    {
        $resourceDO = $this->getResourceDO();
        $command = $this->getCommand($resourceDO);
        $command();
    }

    public function testFindResourceDataProvider()
    {
        return [
            //  $namespace, $type, $variant, $version, $name, $nameAlternative, $author
            [ '', 'jpg', 'def', '0', 'Aloha', '', 'user-puser' ],
            [ 'space', 'txt', 'def', '0', 'Hello', '', 'user-cucuser' ],
            [ 'space', 'txt', 'def', '0', 'Hello', 'Привет', 'user-vantuser' ],
            [ 'my/long/space', 'jpg', 'varvarvar', '3', 'Aloha', '$%^&*O', 'user-shmuser' ],
        ];
    }

    /**
     * @dataProvider testFindResourceDataProvider
     */
    public function testFindResourceMock($namespace, $type, $variant, $version, $name, $nameAlternative, $author)
    {
        $content = 'just a test';
        $resourceDO = $this->prepareResource($namespace, $type, $variant, $version, $name, $nameAlternative, $author);
        $uuid = $resourceDO->getUuid();

        // SAVE CURRENT
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);
        $this->assertTrue($this->filesystem->has($filePath));

        // SAVE ANOTHER VERSION
        $resourceDOVersion = clone $resourceDO;
        $version2 = (string)($version + 1);
        $resourceDOVersion->setVersion($version2);

        $filePath = $resourceDOVersion->getFilePath();
        $this->filesystem->put($filePath, $content);
        $this->assertTrue($this->filesystem->has($filePath));

        // SAVE ANOTHER VARIANT
        $resourceDOVariant = clone $resourceDO;
        $variant2 = $variant . '_second';
        $resourceDOVariant->setVariant($variant2);

        $filePath = $resourceDOVariant->getFilePath();
        $this->filesystem->put($filePath, $content);
        $this->assertTrue($this->filesystem->has($filePath));

        // SAVE WRONG FILES
        $this->wrongFiles->create($resourceDO, $content);

        $modelBaseDir = substr(self::BASE_DIR, 1, 100);
        $shardVariant = substr($variant, 0, ResourceDOAbstract::SHARD_SLICE_LENGTH);
        $shardFilename = substr($uuid, 0, ResourceDOAbstract::SHARD_SLICE_LENGTH);
        $namespacePath = $namespace ? $namespace . '/' : '';
        $model = [
            [
                ResourceDOAbstract::TOKEN_TYPE => $type,
                'visibility' => 'public',
                'path' => $modelBaseDir . '/' . $namespacePath . $type . '/' . $shardVariant . '/' . $variant . '/' . $version . '/' . $shardFilename . '/' . $uuid . '.' . $type,
                'dirname' => $modelBaseDir . '/' . $namespacePath . $type . '/' . $shardVariant . '/' . $variant . '/' . $version . '/' . $shardFilename,
                'basename' => $uuid . '.' . $type,
                'extension' => $type,
                'filename' => $uuid,
                'directory_relative' => $type . '/' . $shardVariant . '/' . $variant . '/' . $version . '/' . $shardFilename,
                ResourceDOAbstract::TOKEN_SHARD_VARIANT => $shardVariant,
                ResourceDOAbstract::TOKEN_VARIANT => $variant,
                ResourceDOAbstract::TOKEN_VERSION => $version,
                ResourceDOAbstract::TOKEN_SHARD_FILENAME => $shardFilename,
            ],
            [
                ResourceDOAbstract::TOKEN_TYPE => $type,
                'visibility' => 'public',
                'path' => $modelBaseDir . '/' . $namespacePath . $type . '/' . $shardVariant . '/' . $variant . '/' . $version2 . '/' . $shardFilename . '/' . $uuid . '.' . $type,
                'dirname' => $modelBaseDir . '/' . $namespacePath . $type . '/' . $shardVariant . '/' . $variant . '/' . $version2 . '/' . $shardFilename,
                'basename' => $uuid . '.' . $type,
                'extension' => $type,
                'filename' => $uuid,
                'directory_relative' => $type . '/' . $shardVariant . '/' . $variant . '/' . $version2 . '/' . $shardFilename,
                ResourceDOAbstract::TOKEN_SHARD_VARIANT => $shardVariant,
                ResourceDOAbstract::TOKEN_VARIANT => $variant,
                ResourceDOAbstract::TOKEN_VERSION => $version2,
                ResourceDOAbstract::TOKEN_SHARD_FILENAME => $shardFilename,
            ],
            [
                ResourceDOAbstract::TOKEN_TYPE => $type,
                'visibility' => 'public',
                'path' => $modelBaseDir . '/' . $namespacePath . $type . '/' . $shardVariant . '/' . $variant2 . '/' . $version . '/' . $shardFilename . '/' . $uuid . '.' . $type,
                'dirname' => $modelBaseDir . '/' . $namespacePath . $type . '/' . $shardVariant . '/' . $variant2 . '/' . $version . '/' . $shardFilename,
                'basename' => $uuid . '.' . $type,
                'extension' => $type,
                'filename' => $uuid,
                'directory_relative' => $type . '/' . $shardVariant . '/' . $variant2 . '/' . $version . '/' . $shardFilename,
                ResourceDOAbstract::TOKEN_SHARD_VARIANT => $shardVariant,
                ResourceDOAbstract::TOKEN_VARIANT => $variant2,
                ResourceDOAbstract::TOKEN_VERSION => $version,
                ResourceDOAbstract::TOKEN_SHARD_FILENAME => $shardFilename,
            ],
        ];

        $command = $this->getCommand($resourceDO);
        $result = $command();
        foreach ($result as &$item) {
            $this->assertArrayHasKey('size', $item);
            $this->assertArrayHasKey('timestamp', $item);
            unset($item['size'], $item['timestamp']);
        }

        $this->assertEquals($model, $result);
    }

    protected function prepareResource($namespace, $type, $variant, $version, $name, $nameAlternative, $author)
    {
        $resourceDO = $this->getResourceDO();
        $resourceDO->setBaseDirectory(self::BASE_DIR)
            ->setNamespace($namespace)
            ->setType($type)
            ->setVariant($variant)
            ->setVersion($version)
            ->setName($name)
            ->setNameAlternative($nameAlternative)
            ->setAuthor($author);

        return $resourceDO;
    }


}