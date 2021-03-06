<?php
namespace Staticus\Middlewares;

use League\Flysystem\FilesystemInterface;
use Staticus\Exceptions\ErrorException;
use Staticus\Resources\ResourceDOInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Staticus\Resources\File\ResourceDO;

abstract class ActionGetAbstract extends MiddlewareAbstract
{
    /**
     * @var mixed
     */
    protected $generator;
    /**
     * @var ResourceDOInterface|ResourceDO
     */
    protected $resourceDO;
    /**
     * @var
     */
    protected $filesystem;

    public function __construct(ResourceDOInterface $resourceDO, FilesystemInterface $filesystem)
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
        $this->response = $this->action();

        return $this->next();
    }

    /**
     * @param string $path
     * @param string $filename Filename for saving dialog on the client-side
     * @param bool $forceSaveDialog
     * @return EmptyResponse
     * @throws ErrorException
     */
    protected function XAccelRedirect($path, $filename = '', $forceSaveDialog = false)
    {
        $mime = $this->filesystem->getMimetype($path);
        if (empty($mime)) {
            throw new ErrorException('Mime content type can not be reached');
        }
        $headers = [
            'X-Accel-Redirect' => $path,
            'Content-Type' => $mime,
            // '' =>
        ];
        if ($forceSaveDialog) {
            if (!$filename) {
                $filename = basename($path);
            }
            $headers['Content-Disposition'] = 'attachment; filename=' . $filename;
        }

        return new EmptyResponse(200, $headers);
    }

    protected function action()
    {
        $headers = [
            'Content-Type' => $this->resourceDO->getMimeType(),
        ];
        $filePath = $this->resourceDO->getFilePath();
        $filename = $this->resourceDO->getName() . '.' . $this->resourceDO->getType();
        if ($this->filesystem->has($filePath)) {

            return $this->XAccelRedirect(realpath($filePath), $filename, false);
        }
        /** @see \Zend\Diactoros\Response::$phrases */
        return new EmptyResponse(404, $headers);
    }
}