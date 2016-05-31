<?php
namespace Staticus\Middlewares;

use League\Flysystem\FilesystemInterface;
use Staticus\Exceptions\ResourceNotFoundException;
use Staticus\Resources\Commands\FindResourceOptionsCommand;
use Staticus\Resources\ResourceDOInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Staticus\Resources\File\ResourceDO;
use Zend\Diactoros\Response\JsonResponse;

abstract class ActionListAbstract extends MiddlewareAbstract
{
    protected $actionResult = [];
    /**
     * @var ResourceDOInterface|ResourceDO
     */
    protected $resourceDO;

    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    public function __construct(
        ResourceDOInterface $resourceDO
        , FilesystemInterface $filesystem
    )
    {
        $this->resourceDO = $resourceDO;
        $this->filesystem = $filesystem;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param callable|null $next
     * @return EmptyResponse
     * @throws \Exception
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    )
    {
        parent::__invoke($request, $response, $next);

        $this->action();
        $this->response = new JsonResponse($this->actionResult);

        return $this->next();
    }

    protected function action()
    {
        $this->actionResult['current'] = $this->findCurrentResource();
        $this->actionResult['options'] = $this->findOptions();
    }

    protected function findCurrentResource()
    {
        $filePath = realpath($this->resourceDO->getFilePath());
        if (!$this->filesystem->has($filePath)) {

            throw new ResourceNotFoundException($this->resourceDO);
        }
        $current = $this->resourceDO->toArray();

        return $current;
    }

    protected function findOptions()
    {
        $command = new FindResourceOptionsCommand($this->resourceDO, $this->filesystem);

        return $command($this->allowedProperties());
    }

    protected function allowedProperties()
    {
        return [
            'size',
            'timestamp',
            'variant',
            'version',
        ];
    }
}