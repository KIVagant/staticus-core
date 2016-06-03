<?php
namespace Staticus\Resources\Commands;

use League\Flysystem\Filesystem;
use Staticus\Resources\ResourceDOInterface;

class AddWrongFilesToDiskHelper
{
    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var \PHPUnit_Framework_TestCase
     */
    private $testCase;

    public function __construct(Filesystem $filesystem, \PHPUnit_Framework_TestCase $testCase)
    {
        $this->filesystem = $filesystem;
        $this->testCase = $testCase;
    }

    /**
     * Put bad files to the 'disk'
     * @param $resourceDO
     * @param $content
     * @return string
     */
    public function create(ResourceDOInterface $resourceDO, $content = '')
    {
        $resourceDO = clone $resourceDO;
        $resourceDO->setName('another');
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);

        $resourceDO = clone $resourceDO;
        $resourceDO->setNameAlternative('another');
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);

        $resourceDO = clone $resourceDO;
        $resourceDO->setBaseDirectory('another');
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);

        $resourceDO = clone $resourceDO;
        $resourceDO->setNamespace('another');
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);

        $resourceDO = clone $resourceDO;
        $resourceDO->setType('wrong-type');
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);
    }

    public function assertExist(ResourceDOInterface $resourceDO)
    {
        $resourceDO = clone $resourceDO;
        $resourceDO->setName('another');
        $filePath = $resourceDO->getFilePath();
        $this->testCase->assertTrue($this->filesystem->has($filePath));

        $resourceDO = clone $resourceDO;
        $resourceDO->setNameAlternative('another');
        $filePath = $resourceDO->getFilePath();
        $this->testCase->assertTrue($this->filesystem->has($filePath));

        $resourceDO = clone $resourceDO;
        $resourceDO->setBaseDirectory('another');
        $filePath = $resourceDO->getFilePath();
        $this->testCase->assertTrue($this->filesystem->has($filePath));

        $resourceDO = clone $resourceDO;
        $resourceDO->setNamespace('another');
        $filePath = $resourceDO->getFilePath();
        $this->testCase->assertTrue($this->filesystem->has($filePath));

        $resourceDO = clone $resourceDO;
        $resourceDO->setType('wrong-type');
        $filePath = $resourceDO->getFilePath();
        $this->testCase->assertTrue($this->filesystem->has($filePath));
    }
}