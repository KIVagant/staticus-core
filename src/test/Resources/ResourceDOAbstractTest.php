<?php
namespace Staticus\Resources;

class ResourceDO extends ResourceDOAbstract
{
    const TYPE = 'type';
    public function getMimeType()
    {

        return 'abstract';
    }
}

class ResourceDOAbstractTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResourceDO
     */
    protected $resourceDO;

    /**
     * @return ResourceDO
     */
    protected function getResourceDO()
    {
        return clone $this->resourceDO;
    }

    protected function setUp()
    {
        parent::setUp();
        $this->resourceDO = new ResourceDO();
    }

    public function testReset()
    {
        $resourceDO = $this->getResourceDO();
        $this->putTestValuesToResource($resourceDO);
        $resourceDO->reset();

        $this->assertEquals($this->resourceDO, $resourceDO);
    }
    protected function putTestValuesToResource(ResourceDO $resourceDO)
    {
        $resourceDO
            ->setName('testname')
            ->setNamespace('testnamespace')
            ->setType('testtype')
            ->setAuthor('testauthor')
            ->setBaseDirectory('testbasedir')
            ->setNameAlternative('testnamealternative')
            ->setNew(true)
            ->setRecreate(true)
            ->setVariant('testvariant')
            ->setVersion(2);
    }

    public function testGetMimeType()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getMimeType();
        $this->assertEquals('abstract', $result);
    }

    public function testUuid()
    {
        $resourceDO = $this->getResourceDO();

        // UUID must be created for a empty string
        $result = $resourceDO->getUuid();
        $modelUuid = 'd41d8cd98f00b204e9800998ecf8427e';
        $this->assertEquals($modelUuid, $result);

        // UUID must be changed after name have been changed
        $resourceDO->setName('testname');
        $modelUuid = 'afe107acd2e1b816b5da87f79c90fdc7';
        $result = $resourceDO->getUuid();
        $this->assertEquals($modelUuid, $result);

        // UUID must be changed after alter name have been changed
        $resourceDO->setNameAlternative('testnamealternative');
        $modelUuid = '8a0e2c505add6e3d1f83fd23f3a381a8';
        $result = $resourceDO->getUuid();
        $this->assertEquals($modelUuid, $result);

        // UUID must be changed after alter name have been changed
        $resourceDO->setNameAlternative();
        $modelUuid = 'afe107acd2e1b816b5da87f79c90fdc7';
        $result = $resourceDO->getUuid();
        $this->assertEquals($modelUuid, $result);
    }

    public function testName()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getName();
        $this->assertEquals('', $result);
        $model = 'testname';
        $resourceDO->setName($model);
        $result = $resourceDO->getName();
        $this->assertEquals($model, $result);

        $result = $resourceDO->getUuid();
        $modelUuid = 'afe107acd2e1b816b5da87f79c90fdc7';
        $this->assertEquals($modelUuid, $result);
    }

    public function testNamespace()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getNamespace();
        $this->assertEquals('', $result);
        $model = 'testnamespace';
        $resourceDO->setNamespace($model);
        $result = $resourceDO->getNamespace();
        $this->assertEquals($model, $result);

        $result = $resourceDO->getUuid();
        $modelUuid = 'd41d8cd98f00b204e9800998ecf8427e';
        $this->assertEquals($modelUuid, $result);

        $result = $resourceDO->getFilePath();
        $model = 'testnamespace/type/def/def/0/d41/d41d8cd98f00b204e9800998ecf8427e.type';
        $this->assertEquals($model, $result);
    }

    public function testType()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getType();
        $this->assertEquals('type', $result);
        $model = 'test';
        $resourceDO->setType($model);
        $result = $resourceDO->getType();
        $this->assertEquals($model, $result);
    }

    public function testAuthor()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getAuthor();
        $this->assertEquals('', $result);
        $model = 'test';
        $resourceDO->setAuthor($model);
        $result = $resourceDO->getAuthor();
        $this->assertEquals($model, $result);
    }

    public function testBaseDirectory()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getBaseDirectory();
        $this->assertEquals('', $result);

        $model = ''; // Empty
        $resourceDO->setBaseDirectory($model);
        $result = $resourceDO->getBaseDirectory();
        $this->assertEquals($model, $result);

        $model = 'testbasedir';
        $resourceDO->setBaseDirectory($model);
        $result = $resourceDO->getBaseDirectory();
        $this->assertEquals($model . '/', $result);

        $result = $resourceDO->getFilePath();
        $model = 'testbasedir/type/def/def/0/d41/d41d8cd98f00b204e9800998ecf8427e.type';
        $this->assertEquals($model, $result);
    }

    public function testNameAlternative()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getNameAlternative();
        $this->assertEquals('', $result);

        $model = 'test';
        $resourceDO->setNameAlternative($model);
        $result = $resourceDO->getNameAlternative();
        $this->assertEquals($model, $result);

        $result = $resourceDO->getUuid();
        $modelUuid = '098f6bcd4621d373cade4e832627b4f6';
        $this->assertEquals($modelUuid, $result);
    }

    public function testNew()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->isNew();
        $this->assertFalse($result);
        $resourceDO->setNew(true);
        $this->assertTrue($resourceDO->isNew());
    }

    public function testRecreate()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->isRecreate();
        $this->assertFalse($result);
        $resourceDO->setRecreate(true);
        $this->assertTrue($resourceDO->isRecreate());
    }

    public function testVariant()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getVariant();
        $this->assertEquals('def', $result);

        $model = 'testvariant';
        $resourceDO->setVariant($model);
        $result = $resourceDO->getVariant();
        $this->assertEquals($model, $result);

        $result = $resourceDO->getFilePath();
        $model = 'type/tes/testvariant/0/d41/d41d8cd98f00b204e9800998ecf8427e.type';
        $this->assertEquals($model, $result);
    }

    public function testVersion()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getVersion();
        $this->assertEquals(0, $result);

        $model = 2;
        $resourceDO->setVersion($model);
        $result = $resourceDO->getVersion();
        $this->assertEquals($model, $result);

        $result = $resourceDO->getFilePath();
        $model = 'type/def/def/2/d41/d41d8cd98f00b204e9800998ecf8427e.type';
        $this->assertEquals($model, $result);
    }

    public function testGenerateFilePathForEmptyResource()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->generateFilePath();
        $model = 'type/def/def/0/d41/d41d8cd98f00b204e9800998ecf8427e.type';
        $this->assertEquals($model, $result);
    }

    public function testGenerateFilePathForMockedResource()
    {
        $resourceDO = $this->getResourceDO();
        $this->putTestValuesToResource($resourceDO);
        $result = $resourceDO->generateFilePath();
        $model = 'testbasedir/testnamespace/testtype/tes/testvariant/2/8a0/8a0e2c505add6e3d1f83fd23f3a381a8.testtype';
        $this->assertEquals($model, $result);
    }

    public function testGetDirectoryTokensForEmptyResource()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getDirectoryTokens();
        $model = [
            'basedirectory' => '',
            'namespace' => '',
            'type' => 'type/',
            'shard_variant' => 'def/',
            'variant' => 'def/',
            'version' => '0/',
            'shard_filename' => 'd41/',
        ];
        $this->assertEquals($model, $result);
    }

    public function testGetDirectoryTokensForMockedResource()
    {
        $resourceDO = $this->getResourceDO();
        $this->putTestValuesToResource($resourceDO);
        $result = $resourceDO->getDirectoryTokens();
        $model = [
            'basedirectory' => 'testbasedir/',
            'namespace' => 'testnamespace/',
            'type' => 'testtype/',
            'shard_variant' => 'tes/',
            'variant' => 'testvariant/',
            'version' => '2/',
            'shard_filename' => '8a0/',
        ];
        $this->assertEquals($model, $result);
    }

    public function testEmptyResourceToArray()
    {
        $resourceDO = $this->getResourceDO();
        $model = [
            'name' => '',
            'nameAlternative' => '',
            'namespace' => '',
            'new' => false,
            'recreate' => false,
            'type' => 'type',
            'uuid' => '',
            'variant' => 'def',
            'version' => 0,
        ];
        $result = $resourceDO->toArray();
        $this->assertEquals($model, $result);
    }

    public function testMockedResourceToArray()
    {
        $resourceDO = $this->getResourceDO();
        $model = [
            'name' => 'testname',
            'nameAlternative' => 'testnamealternative',
            'namespace' => 'testnamespace',
            'new' => true,
            'recreate' => true,
            'type' => 'testtype',
            'uuid' => '8a0e2c505add6e3d1f83fd23f3a381a8',
            'variant' => 'testvariant',
            'version' => 2,
        ];
        $this->putTestValuesToResource($resourceDO);
        $result = $resourceDO->toArray();
        $this->assertEquals($model, $result);
    }
}
