<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Staticus\Resources\File\ResourceDO;
use Staticus\Resources\ResourceDOInterface;

class BackupResourceCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceDO
     */
    protected $resourceDO;
    /**
     * @var Filesystem
     */
    protected $filesystem;

    protected function setUp()
    {
        parent::setUp();
        $this->resourceDO = new ResourceDO();
        $this->filesystem = new Filesystem(new MemoryAdapter());
    }

    /**
     * @return BackupResourceCommand
     */
    public function getCommand(ResourceDO $resourceDO)
    {
        return new BackupResourceCommand($resourceDO, $this->filesystem);
    }

    /**
     * @return ResourceDO
     */
    public function getResourceDO()
    {
        return clone $this->resourceDO;
    }

    /**
     * @return ResourceDO
     */
    public function getResourceDOMock()
    {
        $resourceDO = clone $this->resourceDO;

        return $resourceDO
            ->setBaseDirectory('testBase')
            ->setName('testResource')
            ->setType('testType')
        ;
    }

    /**
     * @expectedException \Staticus\Resources\Exceptions\CommandErrorException
     * @expectedExceptionMessage Can not look for options: resource is empty
     */
    public function testBackupEmptyResource()
    {
        $resourceDO = $this->getResourceDO();
        $command = $this->getCommand($resourceDO);
        $command();
    }

    public function testBackupFirstVersionOfTheResource()
    {
        $resourceDO_v0 = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO_v0->getFilePath(), '');

        $expectedVersion = '1';
        $command = $this->getCommand($resourceDO_v0);
        $result = $command();
        $this->assertInstanceOf(ResourceDOInterface::class, $result);
        $this->assertEquals($expectedVersion, $result->getVersion());

        $resourceDO_v1 = clone $resourceDO_v0;
        $resourceDO_v1->setVersion($expectedVersion);
        $this->assertTrue($this->filesystem->has($resourceDO_v1->getFilePath(), ''));
    }

    public function testBackupResourceToTheSpecificVersion()
    {
        $resourceDO_v0 = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO_v0->getFilePath(), '');

        $expectedVersion = '8';
        $command = $this->getCommand($resourceDO_v0);
        $result = $command(7);
        $this->assertInstanceOf(ResourceDOInterface::class, $result);
        $this->assertEquals($expectedVersion, $result->getVersion());

        $resourceDO_v8 = clone $resourceDO_v0;
        $resourceDO_v8->setVersion($expectedVersion);
        $this->assertTrue($this->filesystem->has($resourceDO_v8->getFilePath(), ''));
    }

    public function testBackupResourceWhenAnotherCopyAlreadyExist()
    {
        $resourceDO_v0 = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO_v0->getFilePath(), '');

        // Put one more version on disk
        $resourceDO_v1 = clone $resourceDO_v0;
        $resourceDO_v1->setVersion($resourceDO_v0->getVersion() + 1);
        $this->filesystem->put($resourceDO_v1->getFilePath(), '');

        $expectedVersion = (string)($resourceDO_v0->getVersion() + 2);
        $command = $this->getCommand($resourceDO_v0);
        $result = $command();
        $this->assertInstanceOf(ResourceDOInterface::class, $result);
        $this->assertEquals($expectedVersion, $result->getVersion());

        $resourceDO_v2 = clone $resourceDO_v0;
        $resourceDO_v2->setVersion($expectedVersion);
        $this->assertTrue($this->filesystem->has($resourceDO_v2->getFilePath(), ''));
    }
}
