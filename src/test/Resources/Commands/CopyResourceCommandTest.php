<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Staticus\Resources\File\ResourceDO;

class CopyResourceCommandTest extends \PHPUnit_Framework_TestCase
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
     * @return CopyResourceCommand
     */
    public function getCommand(ResourceDO $resourceDOSource, ResourceDO $resourceDODest)
    {
        return new CopyResourceCommand($resourceDOSource, $resourceDODest, $this->filesystem);
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
     * @expectedExceptionMessage Source resource cannot be empty
     */
    public function testCopyEmptySourceResource()
    {
        $resourceDOSource = $this->getResourceDO();
        $resourceDODest = $this->getResourceDO();
        $command = $this->getCommand($resourceDOSource, $resourceDODest);
        $command();
    }

    /**
     * @expectedException \Staticus\Resources\Exceptions\CommandErrorException
     * @expectedExceptionMessage Destination resource cannot be empty
     */
    public function testCopyEmptyDestinationResource()
    {
        $resourceDOSource = $this->getResourceDOMock();
        $resourceDODest = $this->getResourceDO();
        $command = $this->getCommand($resourceDOSource, $resourceDODest);
        $command();
    }

    /**
     * @expectedException \Staticus\Resources\Exceptions\CommandErrorException
     * @expectedExceptionMessage Origin file is not exists: testBase/testType/def/def/0/c9f/c9f7e81bafc626421e04b573022e6203.testType
     */
    public function testCopyResourceNotExists()
    {
        $resourceDOSource = $this->getResourceDOMock();
        $resourceDODest = $this->getResourceDOMock();
        $resourceDODest->setNameAlternative('testAlternative');

        $command = $this->getCommand($resourceDOSource, $resourceDODest);
        $command();
    }

    /**
     * @expectedException \Staticus\Resources\Exceptions\CommandErrorException
     * @expectedExceptionMessage Source and destination paths is equal
     */
    public function testCopyResourceToItself()
    {
        $resourceDOSource = $this->getResourceDOMock();
        $resourceDODest = $resourceDOSource;
        $this->filesystem->put($resourceDOSource->getFilePath(), '');

        $command = $this->getCommand($resourceDOSource, $resourceDODest);
        $command();
    }

    public function testCopyResourceNormal()
    {
        $resourceDOSource = $this->getResourceDOMock();
        $resourceDODest = $this->getResourceDOMock();
        $resourceDODest->setVersion($resourceDOSource->getVersion() + 1);
        $this->filesystem->put($resourceDOSource->getFilePath(), '');

        $command = $this->getCommand($resourceDOSource, $resourceDODest);
        $result = $command();

        $this->assertEquals($resourceDODest, $result);
        $this->assertTrue($this->filesystem->has($resourceDODest->getFilePath(), ''));
    }

    public function testCopyResourceToExists()
    {
        $resourceDOSource = $this->getResourceDOMock();
        $resourceDODest = $this->getResourceDOMock();
        $resourceDODest->setVersion($resourceDOSource->getVersion() + 1);
        $this->filesystem->put($resourceDOSource->getFilePath(), '');
        $this->filesystem->put($resourceDODest->getFilePath(), '');

        $command = $this->getCommand($resourceDOSource, $resourceDODest);
        $result = $command();

        $this->assertEquals($resourceDOSource, $result);
    }

    public function testReplaceNotExistsCopy()
    {
        $resourceDOSource = $this->getResourceDOMock();
        $resourceDODest = $this->getResourceDOMock();
        $resourceDODest->setVersion($resourceDOSource->getVersion() + 1);
        $contentExpected = 'New content';
        $this->filesystem->put($resourceDOSource->getFilePath(), $contentExpected);

        $command = $this->getCommand($resourceDOSource, $resourceDODest);
        $result = $command(true);

        $this->assertEquals($resourceDODest, $result);
        $this->assertEquals($contentExpected, $this->filesystem->read($resourceDODest->getFilePath()));
    }

    public function testReplaceExistsCopy()
    {
        $resourceDOSource = $this->getResourceDOMock();
        $resourceDODest = $this->getResourceDOMock();
        $resourceDODest->setVersion($resourceDOSource->getVersion() + 1);
        $contentExpected = 'New content';
        $this->filesystem->put($resourceDOSource->getFilePath(), $contentExpected);
        $this->filesystem->put($resourceDODest->getFilePath(), 'Exist content');

        $command = $this->getCommand($resourceDOSource, $resourceDODest);
        $result = $command(true);

        $this->assertEquals($resourceDODest, $result);
        $this->assertEquals($contentExpected, $this->filesystem->read($resourceDODest->getFilePath()));
    }
}
