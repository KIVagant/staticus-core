<?php
namespace Staticus\Middlewares;

use League\Flysystem\FilesystemInterface;
use Staticus\Resources\Commands\FindResourceOptionsCommand;
use Staticus\Resources\ResourceDOAbstract;
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
        $this->actionResult['resource'] = $this->resourceDO->toArray();
        $this->actionResult['exists'] = $this->isExist();
        $this->actionResult['options'] = $this->findOptions();
        array_walk($this->actionResult['options'], [$this, 'addLinkToOption'], ['resourceDO' => $this->resourceDO]);
    }

    protected function addLinkToOption(&$option, $key, $args)
    {
        /** @var ResourceDOInterface $resourceDO */
        $resourceDO = $args['resourceDO'];
        $query = [];
        $namespace = array_key_exists(ResourceDO::TOKEN_NAMESPACE, $option)
            ? '/' . $option[ResourceDO::TOKEN_NAMESPACE]
            : '';
        $link = $namespace . '/' . $resourceDO->getName() . '.' . $resourceDO->getType();
        $alt = $resourceDO->getNameAlternative();
        if ($alt) {
            $query['alt'] = $alt;
        }
        foreach ($option as $token => $value) {
            $query = $this->transformTokenToRoute($token, $value, $query);
        }
        $query = http_build_query($query, null, '&', PHP_QUERY_RFC3986); // RFC for correct spaces
        if ($query) {
            $link .= '?' . $query;
        }
        $option['link'] = $link;
    }


    protected function isExist()
    {
        $filePath = realpath($this->resourceDO->getFilePath());

        return $this->filesystem->has($filePath);
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
            ResourceDOAbstract::TOKEN_VARIANT,
            ResourceDOAbstract::TOKEN_VERSION,
        ];
    }

    /**
     * @param string $token
     * @param string $value
     * @param array $query
     * @return array
     */
    protected function transformTokenToRoute($token, $value, array $query)
    {
        switch ($token) {
            case ResourceDO::TOKEN_VERSION:
                if ((int)$value !== ResourceDO::DEFAULT_VERSION) {
                    $query['v'] = $value;
                }
                break;
            case ResourceDO::TOKEN_VARIANT:
                if ((string)$value !== ResourceDO::DEFAULT_VARIANT) {
                    $query['var'] = $value;
                }
                break;
        }

        return $query;
    }
}