<?php
namespace Staticus\Resources\Middlewares;

use Staticus\Diactoros\Response\FileContentResponse;
use Staticus\Diactoros\Response\FileUploadedResponse;
use Staticus\Diactoros\Response\ResourceDoResponse;
use Staticus\Middlewares\MiddlewareAbstract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Staticus\Resources\ResourceDOInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

abstract class ResourceResponseMiddlewareAbstract extends MiddlewareAbstract
{
    /**
     * @var ResourceDOInterface
     */
    protected $resourceDO;
    
    public function __construct(ResourceDOInterface $resourceDO)
    {
        $this->resourceDO = $resourceDO;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    )
    {
        parent::__invoke($request, $response, $next);
        if ($this->isSupportedResponse($response)) {
            $uri = $this->getUri($this->resourceDO);
            $data = [
                'resource' => $this->resourceDO->toArray(),
                'uri' => $uri,
            ];
            if (!empty($data)) {
                $headers = $response->getHeaders();
                $headers['Content-Type'] = 'application/json';

                // here is relative link without host url
                $headers['Link'] = '<' . rawurlencode($uri) . '>; rel="canonical"';
                $response = $this->getResponseObject($data, $response->getStatusCode(), $headers);
            }
        }

        return $next($request, $response);
    }

    /**
     * @return ResponseInterface
     */
    protected function getResponseObject($data, $status, array $headers)
    {
        $return = new JsonResponse($data, $status, $headers);

        return $return;
    }

    protected function getUri(ResourceDOInterface $resourceDO)
    {
        $uri = $resourceDO->getName() . '.' . $resourceDO->getType();
        $query = [];
        if (ResourceDOInterface::DEFAULT_VARIANT !== $resourceDO->getVariant()) {
            $query['var'] = $resourceDO->getVariant();
        }
        if (ResourceDOInterface::DEFAULT_NAME_ALTERNATIVE !== $resourceDO->getNameAlternative()) {
            $query['alt'] = $resourceDO->getNameAlternative();
        }
        if (ResourceDOInterface::DEFAULT_VERSION !== $resourceDO->getVersion()) {
            $query['v'] = $resourceDO->getVersion();
        }
        $query = http_build_query($query, null, '&', PHP_QUERY_RFC3986); // RFC for correct spaces
        if ($query) {
            $uri .= '?' . $query;
        }

        return $uri;
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    protected function isSupportedResponse(ResponseInterface $response)
    {
        return $response instanceof EmptyResponse
        || $response instanceof FileContentResponse
        || $response instanceof FileUploadedResponse
        || $response instanceof ResourceDoResponse;
    }
}
