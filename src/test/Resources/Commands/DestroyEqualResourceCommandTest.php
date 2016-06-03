<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Staticus\Resources\File\ResourceDO;

class DestroyEqualResourceCommandTest extends \PHPUnit_Framework_TestCase
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
    public function getCommand(ResourceDO $resourceDOOrigin, ResourceDO $resourceDOSuspect)
    {
        return new DestroyEqualResourceCommand($resourceDOOrigin, $resourceDOSuspect, $this->filesystem);
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
     * @expectedExceptionMessage Cannot destroy equal resource: the origin resource is empty
     */
    public function testDestroyEmptyOriginResource()
    {
        $resourceDOOrigin = $this->getResourceDO();
        $resourceDOSuspect = $this->getResourceDO();
        $command = $this->getCommand($resourceDOOrigin, $resourceDOSuspect);
        $command();
    }

    /**
     * @expectedException \Staticus\Resources\Exceptions\CommandErrorException
     * @expectedExceptionMessage Cannot destroy equal resource: the suspect resource is empty
     */
    public function testDestroyEmptySuspectResource()
    {
        $resourceDOOrigin = $this->getResourceDOMock();
        $resourceDOSuspect = $this->getResourceDO();
        $command = $this->getCommand($resourceDOOrigin, $resourceDOSuspect);
        $command();
    }

    /**
     * @expectedException \Staticus\Resources\Exceptions\CommandErrorException
     * @expectedExceptionMessage Cannot destroy equal resource: Origin and Suspect have same paths
     */
    public function testDestroyEqualResourceWithSamePaths()
    {
        $resourceDOOrigin = $this->getResourceDOMock();
        $resourceDOSuspect = $this->getResourceDOMock();
        $this->filesystem->put($resourceDOOrigin->getFilePath(), '');
        $this->filesystem->put($resourceDOSuspect->getFilePath(), '');

        $command = $this->getCommand($resourceDOOrigin, $resourceDOSuspect);
        $command();

        $this->assertTrue($this->filesystem->has($resourceDOSuspect->getFilePath()));
    }

    public function testDestroyEqualResourceWithDifferentPaths()
    {
        $resourceDOOrigin = $this->getResourceDOMock();
        $resourceDOSuspect = $this->getResourceDOMock();
        $resourceDOSuspect->setVersion($resourceDOOrigin->getVersion() + 1);
        $this->filesystem->put($resourceDOOrigin->getFilePath(), 'Same content');
        $this->filesystem->put($resourceDOSuspect->getFilePath(), 'Same content');

        $command = $this->getCommand($resourceDOOrigin, $resourceDOSuspect);
        $result = $command();

        $this->assertEquals($resourceDOSuspect, $result);
        $this->assertTrue($this->filesystem->has($resourceDOOrigin->getFilePath()));
        $this->assertFalse($this->filesystem->has($resourceDOSuspect->getFilePath()));
    }

    public function testDestroyEqualResourceWithDifferentContent()
    {
        $resourceDOOrigin = $this->getResourceDOMock();
        $resourceDOSuspect = $this->getResourceDOMock();
        $resourceDOSuspect->setVersion($resourceDOOrigin->getVersion() + 1);
        $this->filesystem->put($resourceDOOrigin->getFilePath(), 'Same content');
        $this->filesystem->put($resourceDOSuspect->getFilePath(), 'Different content');

        $command = $this->getCommand($resourceDOOrigin, $resourceDOSuspect);
        $result = $command();

        $this->assertEquals($resourceDOOrigin, $result);
        $this->assertTrue($this->filesystem->has($resourceDOOrigin->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDOSuspect->getFilePath()));
    }
}
