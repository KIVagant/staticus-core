<?php
namespace Staticus\Resources\Jpg;

use Staticus\Resources\Image\CropImageDO;

class ResourceDOTest extends \PHPUnit_Framework_TestCase
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
        $crop = $this->getCropMock();
        $resourceDO
            ->setType('testtype')
            ->setWidth(2)
            ->setHeight(3)
            ->setCrop($crop);
    }

    public function testGetMimeType()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getMimeType();
        $this->assertEquals('image/jpeg', $result);
    }

    public function testGenerateFilePathForEmptyResource()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->generateFilePath();
        $this->assertEquals('jpg/def/def/0/d41/0/d41d8cd98f00b204e9800998ecf8427e.jpg', $result);
    }

    public function testGenerateFilePathForMockedResource()
    {
        $resourceDO = $this->getResourceDO();
        $this->putTestValuesToResource($resourceDO);
        $result = $resourceDO->generateFilePath();
        $this->assertEquals('jpg/def/def/0/d41/2x3/d41d8cd98f00b204e9800998ecf8427e.jpg', $result);
    }

    public function testWidth()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getWidth();
        $this->assertEquals(0, $result);
        $model = 30;
        $resourceDO->setWidth($model);
        $result = $resourceDO->getWidth();
        $this->assertEquals($model, $result);
    }

    public function testHeight()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getHeight();
        $this->assertEquals(0, $result);
        $model = 30;
        $resourceDO->setHeight($model);
        $result = $resourceDO->getHeight();
        $this->assertEquals($model, $result);
    }

    public function testDimension()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getDimension();
        $this->assertEquals(0, $result);
        $model = 30;
        $model2 = 31;
        $resourceDO->setWidth($model);
        $resourceDO->setHeight($model2);
        $result = $resourceDO->getDimension();
        $this->assertEquals($model . 'x' . $model2, $result);
    }

    public function testCrop()
    {
        $resourceDO = $this->getResourceDO();
        $result = $resourceDO->getCrop();
        $this->assertEmpty($result);
        $crop = $this->getCropMock();
        $resourceDO->setCrop($crop);
        $result = $resourceDO->getCrop();
        $this->assertEquals($crop, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCropMock()
    {
        $crop = $this->getMockBuilder(CropImageDO::class)
            ->getMock();

        return $crop;
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
            'type' => 'jpg',
            'uuid' => '',
            'variant' => 'def',
            'version' => 0,
            'height' => 0,
            'width' => 0,
            'dimension' => 0,
        ];
        $result = $resourceDO->toArray();
        $this->assertEquals($model, $result);
    }

    public function testMockedResourceToArray()
    {
        $resourceDO = $this->getResourceDO();
        $model = [
            'name' => '',
            'nameAlternative' => '',
            'namespace' => '',
            'new' => false,
            'recreate' => false,
            'type' => 'jpg',
            'uuid' => 'd41d8cd98f00b204e9800998ecf8427e',
            'variant' => 'def',
            'version' => 0,
            'height' => 3,
            'width' => 2,
            'dimension' => '2x3',
            'crop' => null,
        ];
        $this->putTestValuesToResource($resourceDO);
        $result = $resourceDO->toArray();
        $this->assertEquals($model, $result);
    }
}
