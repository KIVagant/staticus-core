<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Staticus\Resources\File\ResourceDO;

class FindResourceLastVersionCommandTest extends \PHPUnit_Framework_TestCase
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
     * @return FindResourceLastVersionCommand
     */
    public function getCommand(ResourceDO $resourceDO)
    {
        return new FindResourceLastVersionCommand($resourceDO, $this->filesystem);
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
    public function testFindEmptyResourceVersion()
    {
        $resourceDO = $this->getResourceDO();
        $command = $this->getCommand($resourceDO);
        $command();
    }

    public function testFindVersion1()
    {
        $resourceDO_v0 = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO_v0->getFilePath(), '');

        // Put one more version on disk
        $expectedVersion = $resourceDO_v0->getVersion() + 1;
        $resourceDO_v1 = clone $resourceDO_v0;
        $resourceDO_v1->setVersion($expectedVersion);
        $this->filesystem->put($resourceDO_v1->getFilePath(), '');

        $command = $this->getCommand($resourceDO_v0);
        $result = $command();
        $this->assertEquals($expectedVersion, $result);
    }

    public function testFindVersion8()
    {
        $resourceDO_v0 = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO_v0->getFilePath(), '');

        // Put one more version on disk
        $expectedVersion = $resourceDO_v0->getVersion() + 7;
        $resourceDO_v1 = clone $resourceDO_v0;
        $resourceDO_v1->setVersion($expectedVersion);
        $this->filesystem->put($resourceDO_v1->getFilePath(), '');

        $command = $this->getCommand($resourceDO_v0);
        $result = $command();
        $this->assertEquals($expectedVersion, $result);
    }
}
