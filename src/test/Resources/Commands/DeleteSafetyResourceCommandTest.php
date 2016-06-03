<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Staticus\Resources\File\ResourceDO;

require_once 'AddWrongFilesToDiskHelper.php';
class DeleteSafetyResourceCommandTest extends \PHPUnit_Framework_TestCase
{

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
     * @return BackupResourceCommand
     */
    public function getCommand(ResourceDO $resourceDO)
    {
        return new DeleteSafetyResourceCommand($resourceDO, $this->filesystem);
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
     * @expectedExceptionMessage Cannot delete empty resource
     */
    public function testDeleteEmptyResource()
    {
        $resourceDO = $this->getResourceDO();
        $command = $this->getCommand($resourceDO);
        $command();
    }

    public function testDeleteResourceThatNotExists()
    {
        $resourceDO = $this->getResourceDOMock();
        $command = $this->getCommand($resourceDO);
        $result = $command();
        $this->assertEquals($resourceDO, $result);
    }

    public function testDeleteResourceThatIsExists()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO->getFilePath(), '');

        // Safety deletion command must create a backup of the resource with the 0 version
        $resourceDOBackup = clone $resourceDO;
        $resourceDOBackup->setVersion($resourceDO->getVersion() + 1);

        $command = $this->getCommand($resourceDO);
        $result = $command();
        $this->assertEquals($resourceDO, $result);
        $this->assertFalse($this->filesystem->has($resourceDO->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDOBackup->getFilePath()));
    }

    public function testDeleteResourceThatIsExistsAndSameVersionIsExists()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO->getFilePath(), 'The same content');

        // Now this version is already exist and has the same content as default resource
        $resourceDOBackup = clone $resourceDO;
        $resourceDOBackup->setVersion($resourceDO->getVersion() + 1);
        $this->filesystem->put($resourceDOBackup->getFilePath(), 'The same content');

        $command = $this->getCommand($resourceDO);
        $result = $command();

        $this->assertEquals($resourceDO, $result);
        $this->assertFalse($this->filesystem->has($resourceDO->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDOBackup->getFilePath()));
    }

    public function testDeleteResourceThatIsExistsAndSameVersionIsNotExists()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO->getFilePath(), 'The same content');

        // Now the different version is already exist and has another content
        $resourceDOAnother = clone $resourceDO;
        $resourceDOAnother->setVersion($resourceDO->getVersion() + 1);
        $this->filesystem->put($resourceDOAnother->getFilePath(), 'The different content');

        // This version should be created
        $resourceDOBackup = clone $resourceDO;
        $resourceDOBackup->setVersion($resourceDO->getVersion() + 2);

        $command = $this->getCommand($resourceDO);
        $result = $command();

        $this->assertEquals($resourceDO, $result);
        $this->assertFalse($this->filesystem->has($resourceDO->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDOAnother->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDOBackup->getFilePath()));
    }

    public function testDeleteResourceVersionButLeaveOther()
    {
        $resourceDO = $this->getResourceDOMock();
        $resourceDO->setVersion(2);
        $this->filesystem->put($resourceDO->getFilePath(), '');
        $this->wrongFiles->create($resourceDO);

        $model = $this->filesystem->listContents('/', true);
        unset($model[30]);

        $command = $this->getCommand($resourceDO);
        $result = $command();
        $this->assertEquals($resourceDO, $result);
        $this->assertFalse($this->filesystem->has($resourceDO->getFilePath()));
        $result = $this->filesystem->listContents('/', true);
        $this->assertEquals($model, $result);
    }

    public function testDeleteResourceButLeaveOther()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO->getFilePath(), '');
        $this->wrongFiles->create($resourceDO);

        $model = $this->filesystem->listContents('/', true);

        $model[30] = [
            'type' => 'dir',
            'path' => 'testBase/testType/def/def/1',
            'dirname' => 'testBase/testType/def/def',
            'basename' => '1',
            'filename' => '1',
        ];
        $model[31] = [
            'type' => 'dir',
            'path' => 'testBase/testType/def/def/1/c9f',
            'dirname' => 'testBase/testType/def/def/1',
            'basename' => 'c9f',
            'filename' => 'c9f',
        ];
        $model[32] = [
            'type' => 'file',
            'visibility' => 'public',
            'size' => 0,
            'path' => 'testBase/testType/def/def/1/c9f/c9f7e81bafc626421e04b573022e6203.testType',
            'dirname' => 'testBase/testType/def/def/1/c9f',
            'basename' => 'c9f7e81bafc626421e04b573022e6203.testType',
            'extension' => 'testType',
            'filename' => 'c9f7e81bafc626421e04b573022e6203',
        ];

        $command = $this->getCommand($resourceDO);
        $result = $command();
        $this->assertEquals($resourceDO, $result);
        $this->assertFalse($this->filesystem->has($resourceDO->getFilePath()));
        $result = $this->filesystem->listContents('/', true);
        unset($result[32]['timestamp']);
        $this->assertEquals($model, $result);
    }
}
