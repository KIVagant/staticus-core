<?php
namespace Staticus\Exceptions;
use Staticus\Resources\ResourceDOInterface;

class ResourceNotFoundException extends \RuntimeException
{

    public function __construct(ResourceDOInterface $resourceDO, $message = 'Resource not found', $code = null, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->setResourceDO($resourceDO);
    }

    /**
     * @var ResourceDOInterface
     */
    protected $resourceDO;

    /**
     * @return ResourceDOInterface
     */
    public function getResourceDO()
    {
        return $this->resourceDO;
    }

    /**
     * @param ResourceDOInterface $resourceDO
     */
    protected function setResourceDO(ResourceDOInterface $resourceDO)
    {
        $this->resourceDO = $resourceDO;
    }
}