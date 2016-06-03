<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use Staticus\Resources\File\ResourceDO;

require_once 'AddWrongFilesToDiskHelper.php';
class DestroyResourceCommandTest extends \PHPUnit_Framework_TestCase
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
     * @return DestroyResourceCommand
     */
    public function getCommand(ResourceDO $resourceDO)
    {
        return new DestroyResourceCommand($resourceDO, $this->filesystem);
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
     * @expectedExceptionMessage Cannot destroy the empty resource
     */
    public function testDestroyEmptyResource()
    {
        $resourceDO = $this->getResourceDO();
        $command = $this->getCommand($resourceDO);
        $command();
    }

    public function testDestroyResourceThatNotExist()
    {
        $resourceDO = $this->getResourceDOMock();
        $command = $this->getCommand($resourceDO);
        $result = $command(true);
        $this->assertEquals($resourceDO, $result);
    }

    public function testDestroyResourceByPath()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO->getFilePath(), '');

        $command = $this->getCommand($resourceDO);
        $result = $command(true);

        $this->assertEquals($resourceDO, $result);
        $this->assertFalse($this->filesystem->has($resourceDO->getFilePath(), ''));
    }

    public function testDestroyResourceByPathButLeaveOthers()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->wrongFiles->create($resourceDO);
        $this->testDestroyResourceByPath();
        $this->wrongFiles->assertExist($resourceDO);
    }

    public function testDestroyResourceVersion()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO->getFilePath(), '');

        $resourceDO_v1 = clone $resourceDO;
        $resourceDO_v1->setVersion(1);
        $this->filesystem->put($resourceDO_v1->getFilePath(), '');

        $resourceDO_v2 = clone $resourceDO;
        $resourceDO_v2->setVersion(2);
        $this->filesystem->put($resourceDO_v2->getFilePath(), '');

        $resourceDO_var = clone $resourceDO;
        $resourceDO_var->setVariant('variant');
        $this->filesystem->put($resourceDO_var->getFilePath(), '');

        $resourceDO_var_v2 = clone $resourceDO_var;
        $resourceDO_var_v2->setVersion(2);
        $this->filesystem->put($resourceDO_var_v2->getFilePath(), '');

        $command = $this->getCommand($resourceDO_v1);
        $result = $command();

        $this->assertEquals($resourceDO_v1, $result);
        $this->assertFalse($this->filesystem->has($resourceDO_v1->getFilePath()));

        $this->assertTrue($this->filesystem->has($resourceDO_v2->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDO->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDO_var->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDO_var_v2->getFilePath()));
    }

    public function testDestroyResourceVersionButLeaveOthers()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->wrongFiles->create($resourceDO);
        $this->testDestroyResourceVersion();
        $this->wrongFiles->assertExist($resourceDO);
    }

    public function testDestroyResourceVariant()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO->getFilePath(), '');

        $resourceDO_v2 = clone $resourceDO;
        $resourceDO_v2->setVersion(2);
        $this->filesystem->put($resourceDO_v2->getFilePath(), '');

        $resourceDO_var1 = clone $resourceDO;
        $resourceDO_var1->setVariant('1');
        $this->filesystem->put($resourceDO_var1->getFilePath(), '');

        // variant version must be deleted too
        $resourceDO_var1_v1 = clone $resourceDO_var1;
        $resourceDO_var1_v1->setVersion(1);
        $this->filesystem->put($resourceDO_var1_v1->getFilePath(), '');

        $resourceDO_var2 = clone $resourceDO;
        $resourceDO_var2->setVariant('2');
        $this->filesystem->put($resourceDO_var2->getFilePath(), '');

        $resourceDO_var2_v1 = clone $resourceDO_var2;
        $resourceDO_var2_v1->setVersion(2);
        $this->filesystem->put($resourceDO_var2_v1->getFilePath(), '');

        $command = $this->getCommand($resourceDO_var1);
        $result = $command();

        $this->assertEquals($resourceDO_var1, $result);

        $this->assertFalse($this->filesystem->has($resourceDO_var1->getFilePath()));
        $this->assertFalse($this->filesystem->has($resourceDO_var1_v1->getFilePath()));

        $this->assertTrue($this->filesystem->has($resourceDO_v2->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDO_var2->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDO_var2_v1->getFilePath()));
        $this->assertTrue($this->filesystem->has($resourceDO->getFilePath()));
    }

    public function testDestroyResourceVariantButLeaveOthers()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->wrongFiles->create($resourceDO);
        $this->testDestroyResourceVariant();
        $this->wrongFiles->assertExist($resourceDO);
    }

    public function testDestroyResourceAllVariantsAndVersions()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->filesystem->put($resourceDO->getFilePath(), '');

        $resourceDO_v2 = clone $resourceDO;
        $resourceDO_v2->setVersion(2);
        $this->filesystem->put($resourceDO_v2->getFilePath(), '');

        $resourceDO_var1 = clone $resourceDO;
        $resourceDO_var1->setVariant('1');
        $this->filesystem->put($resourceDO_var1->getFilePath(), '');

        // variant version must be deleted too
        $resourceDO_var1_v1 = clone $resourceDO_var1;
        $resourceDO_var1_v1->setVersion(1);
        $this->filesystem->put($resourceDO_var1_v1->getFilePath(), '');

        $resourceDO_var2 = clone $resourceDO;
        $resourceDO_var2->setVariant('2');
        $this->filesystem->put($resourceDO_var2->getFilePath(), '');

        $resourceDO_var2_v1 = clone $resourceDO_var2;
        $resourceDO_var2_v1->setVersion(2);
        $this->filesystem->put($resourceDO_var2_v1->getFilePath(), '');

        $command = $this->getCommand($resourceDO);
        $result = $command();

        $this->assertEquals($resourceDO, $result);

        $this->assertFalse($this->filesystem->has($resourceDO_var1->getFilePath()));
        $this->assertFalse($this->filesystem->has($resourceDO_var1_v1->getFilePath()));
        $this->assertFalse($this->filesystem->has($resourceDO_v2->getFilePath()));
        $this->assertFalse($this->filesystem->has($resourceDO_var2->getFilePath()));
        $this->assertFalse($this->filesystem->has($resourceDO_var2_v1->getFilePath()));
        $this->assertFalse($this->filesystem->has($resourceDO->getFilePath()));
    }

    public function testDestroyResourceAllVariantsAndVersionsButLeaveOthers()
    {
        $resourceDO = $this->getResourceDOMock();
        $this->wrongFiles->create($resourceDO);
        $this->testDestroyResourceAllVariantsAndVersions();
        $this->wrongFiles->assertExist($resourceDO);
    }
}
