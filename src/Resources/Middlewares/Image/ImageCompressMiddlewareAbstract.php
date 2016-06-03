<?php
namespace Staticus\Resources\Middlewares\Image;

use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Staticus\Config\ConfigInterface;
use Staticus\Resources\ResourceDOInterface;

abstract class ImageCompressMiddlewareAbstract extends ImagePostProcessingMiddlewareAbstract
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    public function __construct(
        ResourceDOInterface $resourceDO
        , FilesystemInterface $filesystem
        , ConfigInterface $config
    )
    {
        parent::__construct($resourceDO, $filesystem);
        $this->config = $config;
    }

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
        if ($this->isAllowed()) {
            $interlacing = $this->config->get('staticus.images.compress.interlace', false);
            $quality = $this->config->get('staticus.images.compress.quality', false);
            $maxWidth = $this->config->get('staticus.images.compress.maxWidth', false);
            $maxHeight = $this->config->get('staticus.images.compress.maxHeight', false);
            $this->compress($this->resourceDO->getFilePath(), $interlacing, $quality, $maxWidth, $maxHeight);
        }

        return $next($request, $response);
    }

    public function compress($sourcePath, $interlacing = null, $quality = null, $maxWidth = null, $maxHeight = null)
    {
        if (!$interlacing && !$quality && (!$maxWidth || !$maxHeight)) {

            return;
        }
        $imagick = $this->getImagick($sourcePath);
        if ($interlacing) {
            $imagick->setInterlaceScheme($interlacing);
        }
        if ($quality) {
            $imagick->setImageCompressionQuality($quality);
        }
        if (
            // if width and height is already set in resourceDO, this middleware MUST not call default resizing
            !$this->resourceDO->getDimension()
            && $maxWidth
            && $imagick->getImageWidth() > $maxWidth
            && $maxHeight
            && $imagick->getImageHeight() > $maxHeight
        ) {
            $imagick->adaptiveResizeImage($maxWidth, $maxHeight, true);
        }
        $imagick->writeImage($sourcePath);
        $imagick->clear();
        $imagick->destroy();
    }

    /**
     * @return bool
     */
    protected function isAllowed()
    {
        return $this->config->get('staticus.images.compress.compress', false)
        && (
            $this->resourceDO->isNew() // For the POST method
            || $this->resourceDO->isRecreate() // For the POST method
        )
        && $this->filesystem->has($this->resourceDO->getFilePath());
    }
}
