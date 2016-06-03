<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Staticus\Resources\Image\ResourceImageDO;
use Staticus\Resources\Png\ResourceDO;

require_once 'AddWrongFilesToDiskTrait.php';

class DeleteImageSizesResourceCommandTest extends \PHPUnit_Framework_TestCase
{
    use AddWrongFilesToDiskTrait;
    /**
     * @var ResourceImageDO
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
    public function getCommand(ResourceImageDO $resourceDO)
    {
        return new DeleteImageSizesResourceCommand($resourceDO, $this->filesystem);
    }

    /**
     * @return ResourceImageDO
     */
    public function getResourceDO()
    {
        return clone $this->resourceDO;
    }

    /**
     * @return ResourceImageDO
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
    public function testDeleteEmptyResourceSizes()
    {
        $resourceDO = $this->getResourceDO();
        $command = $this->getCommand($resourceDO);
        $command();
    }

    public function testDeleteMockResourceNotExistSizes()
    {
        $resourceDO = $this->getResourceDOMock();
        $command = $this->getCommand($resourceDO);
        $result = $command();
        $this->assertTrue($result);
    }

    public function testDeleteMockResourceExistsSizes()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO->getFilePath(), '');

        $resourceDOSize10x11 = $this->getResourceDOMock();
        $resourceDOSize10x11->setWidth(10);
        $resourceDOSize10x11->setHeight(11);
        $this->filesystem->put($resourceDOSize10x11->getFilePath(), '');

        $resourceDOSize20x21 = $this->getResourceDOMock();
        $resourceDOSize20x21->setWidth(20);
        $resourceDOSize20x21->setHeight(21);
        $this->filesystem->put($resourceDOSize20x21->getFilePath(), '');

        $yetAnotherWrongDO = clone $resourceDO;
        $yetAnotherWrongDO->setWidth(998);
        $yetAnotherWrongDO->setHeight(999);
        $this->addWrongFilesToDisk($resourceDO, 'Wrong1');
        $this->addWrongFilesToDisk($resourceDOSize10x11, 'Wrong2');
        $this->addWrongFilesToDisk($resourceDOSize20x21, 'Wrong3');
        $this->addWrongFilesToDisk($yetAnotherWrongDO, 'Wrong4');

        $modelFiles = $this->filesystem->listContents('/', true);

        // Make the expected model looks like filesystem after the command execution
        // With this model we will be sure that other files have not been deleted
        unset($modelFiles[56], $modelFiles[57]);
        $modelFiles[55] = [
            'type' => 'dir',
            'path' => 'testBase/png/def/def/0/c9f/20x21',
            'dirname' => 'testBase/png/def/def/0/c9f',
            'basename' => '20x21',
            'filename' => '20x21',
        ];

        $command = $this->getCommand($resourceDO);
        $result = $command();
        $this->assertTrue($result);
        $this->assertFalse($this->filesystem->has($resourceDOSize10x11->getFilePath()));
        $this->assertFalse($this->filesystem->has($resourceDOSize20x21->getFilePath()));
        $result = $this->filesystem->listContents('/', true);
        $this->assertEquals($modelFiles, $result);
    }
}
