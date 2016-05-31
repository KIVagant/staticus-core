<?php
namespace Staticus\Resources\Middlewares\Image;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Staticus\Resources\Image\CropImageDOInterface;
use Zend\Expressive\Container\Exception\NotFoundException;

abstract class ImageCropMiddlewareAbstract extends ImagePostProcessingMiddlewareAbstract
{
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    )
    {
        parent::__invoke($request, $response, $next);
        if (!$this->isSupportedResponse($response)) {

            return $next($request, $response);
        }
        $crop = $this->resourceDO->getCrop();
        if ($this->resourceDO->getSize() && $crop) {
            $path = $this->resourceDO->getFilePath();

            // (POST) Resource just created or re-created
            if ($this->resourceDO->isNew()
                || $this->resourceDO->isRecreate()
            ) {
                /**
                 * Explanation: If it's just an artifact that is left from the previous file after re-creation
                 * than you need to remove it exact in recreation moment
                 * @see \Staticus\Resources\Middlewares\Image\SaveImageMiddlewareAbstract::afterSave
                 */
                // Some of previous middlewares already created this file size
                if ($this->filesystem->has($path)) {
                    $this->cropImage($path, $path, $crop);
                } else {
                    $modelResourceDO = $this->getResourceWithoutSizes();
                    $this->cropImage($modelResourceDO->getFilePath(), $path, $crop);
                }

            // (GET) Resource should be exist, just check if this size wasn't created before
            } else if (!$this->filesystem->has($path)) {
                $modelResourceDO = $this->getResourceWithoutSizes();
                $this->cropImage($modelResourceDO->getFilePath(), $path, $crop);
            }
        }

        return $next($request, $response);
    }

    public function cropImage($sourcePath, $destinationPath, CropImageDOInterface $crop)
    {
        if (!$this->filesystem->has($sourcePath)) {
            throw new NotFoundException('Can not crop. Resource is not found');
        }
        $this->createDirectory(dirname($destinationPath));
        $imagick = $this->getImagick($sourcePath);
        $imagick->cropImage(
            $crop->getWidth(),
            $crop->getHeight(),
            $crop->getX(),
            $crop->getY()
        );
        $imagick->writeImage($destinationPath);
        $imagick->clear();
        $imagick->destroy();
    }
}
