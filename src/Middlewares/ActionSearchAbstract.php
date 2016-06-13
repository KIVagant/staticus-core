<?php
namespace Staticus\Middlewares;

use Staticus\Acl\Roles;
use Staticus\Resources\Middlewares\PrepareResourceMiddlewareAbstract;
use Staticus\Resources\ResourceDOInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Staticus\Resources\File\ResourceDO;
use Zend\Diactoros\Response\JsonResponse;
use Staticus\Auth\UserInterface;
use Staticus\Auth\User;

abstract class ActionSearchAbstract extends MiddlewareAbstract
{
    const DEFAULT_CURSOR = 1;

    /**
     * @var ResourceDOInterface|ResourceDO
     */
    protected $resourceDO;

    /**
     * Search provider
     * @var mixed
     */
    protected $searcher;

    /**
     * @var UserInterface|User
     */
    protected $user;

    public function __construct(
        ResourceDOInterface $resourceDO
        , $generator
        , UserInterface $user
    )
    {
        $this->resourceDO = $resourceDO;
        $this->searcher = $generator;
        $this->user = $user;
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
        $this->response = $this->action();

        return $this->next();
    }

    /**
     * @return string
     */
    abstract protected function search();

    abstract protected function getQuery();

    protected function action()
    {
        $response = $this->search();

        return new JsonResponse(['found' => $response]);
    }

    /**
     * @return int
     */
    protected function getCursor()
    {
        $roles = $this->user->getRoles();
        if (in_array(Roles::ADMIN, $roles, true)) {
            $cursor = (int)PrepareResourceMiddlewareAbstract::getParamFromRequest('cursor', $this->request);

            return $cursor;
        }

        return self::DEFAULT_CURSOR;
    }
}