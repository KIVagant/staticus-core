<?php
namespace Staticus\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Staticus\Config\ConfigInterface;
use Staticus\Config\Config;
use Staticus\Diactoros\Response\NotFoundResponse;
use Staticus\Exceptions\ExceptionCodes;
use Staticus\Exceptions\ResourceNotFoundException;
use Staticus\Exceptions\WrongRequestException;
use Staticus\Resources\ResourceDOInterface;
use Zend\Diactoros\Response\JsonResponse;

class ErrorHandler
{
    /**
     * @var ConfigInterface|Config
     */
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function __invoke($error, Request $request, Response $response, callable $next)
    {
        /*
         If $error is not an exception, it will use the response status if it already indicates an error
         (ie., >= 400 status), or will use a 500 status, and return the response directly with the reason phrase.
         */
        if ($error instanceof \Exception) {
            $className = $error->getTrace();
            if (isset($className[0]['class'])) {
                $className = $className[0]['class'];
            }
            if ($error instanceof ResourceNotFoundException) {
                $error = $this->getErrorArray($error->getMessage(), $this->getErrorCode($error, $className), $error->getResourceDO());

                return new NotFoundResponse($error);
            } else if ($error instanceof WrongRequestException) {

                /** @see \Zend\Diactoros\Response::$phrases */
                return $this->response(400, $error->getMessage(),
                    ExceptionCodes::code($className) . '.' . $error->getCode());
            } else {

                $message = $this->config->get('error_handler', false)
                    ? $error->getMessage()
                    : 'Internal error';

                /** @see \Zend\Diactoros\Response::$phrases */
                return $this->response(503, $message, $this->getErrorCode($error, $className));
            }
        } else {
            $next($request, $response, $next);
        }
    }
    protected function response($status, $message, $code)
    {
        $error = $this->getErrorArray($message, $code);

        return new JsonResponse($error, $status);
    }

    /**
     * @param $message
     * @param $code
     * @param ResourceDOInterface $resourceDO
     * @return array
     */
    protected function getErrorArray($message, $code, ResourceDOInterface $resourceDO = null)
    {
        $error = [
            'error' => [
                'title' => $message,
                'code' => $code,
            ],
        ];
        if ($resourceDO) {
            $error['error']['detail']['resource'] = $resourceDO->toArray();
        }

        return $error;
    }

    /**
     * @param $error
     * @param $className
     * @return string
     */
    protected function getErrorCode($error, $className)
    {
        return $error->getCode() . '.' . ExceptionCodes::code($className) . '.' . $error->getLine();
    }
}