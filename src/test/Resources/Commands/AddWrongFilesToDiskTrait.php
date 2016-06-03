<?php
namespace Staticus\Resources\Commands;

use Staticus\Resources\ResourceDOInterface;

trait AddWrongFilesToDiskTrait
{
    /**
     * Put bad files to the 'disk'
     * @param $resourceDO
     * @param $content
     * @return string
     */
    protected function addWrongFilesToDisk(ResourceDOInterface $resourceDO, $content)
    {
        $resourceDO = clone $resourceDO;
        $resourceDO->setName('another');
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);
        $this->assertTrue($this->filesystem->has($filePath));

        $resourceDO = clone $resourceDO;
        $resourceDO->setNameAlternative('another');
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);
        $this->assertTrue($this->filesystem->has($filePath));

        $resourceDO = clone $resourceDO;
        $resourceDO->setBaseDirectory('another');
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);
        $this->assertTrue($this->filesystem->has($filePath));

        $resourceDO = clone $resourceDO;
        $resourceDO->setNamespace('another');
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);
        $this->assertTrue($this->filesystem->has($filePath));

        $resourceDO = clone $resourceDO;
        $resourceDO->setType('wrong-type');
        $filePath = $resourceDO->getFilePath();
        $this->filesystem->put($filePath, $content);
        $this->assertTrue($this->filesystem->has($filePath));
    }
}