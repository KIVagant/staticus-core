<?php
namespace Staticus\Middlewares;

use League\Flysystem\FilesystemInterface;
use Staticus\Diactoros\DownloadedFile;
use Staticus\Diactoros\Response\FileUploadedResponse;
use Staticus\Exceptions\ErrorException;
use Staticus\Resources\Middlewares\PrepareResourceMiddlewareAbstract;
use Staticus\Resources\ResourceDOInterface;
use Staticus\Diactoros\Response\FileContentResponse;
use Zend\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Staticus\Resources\File\ResourceDO;
use Zend\Diactoros\UploadedFile;

abstract class ActionPostAbstract extends MiddlewareAbstract
{
    const RECREATE_COMMAND = 'recreate';
    const URI_COMMAND = 'uri';

    /**
     * Generator provider
     * @var mixed
     */
    protected $generator;

    /**
     * @var ResourceDOInterface|ResourceDO
     */
    protected $resourceDO;
    /**
     * @var FilesystemInterface
     */
    protected $filesystem;

    public function __construct(
        ResourceDOInterface $resourceDO, FilesystemInterface $filesystem, $fractal)
    {
        $this->resourceDO = $resourceDO;
        $this->filesystem = $filesystem;
        $this->generator = $fractal;
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

    abstract protected function generate(ResourceDOInterface $resourceDO);

    protected function action()
    {
        $headers = [
            'Content-Type' => $this->resourceDO->getMimeType(),
        ];
        $filePath = $this->resourceDO->getFilePath();
        $fileExists = is_file($filePath);
        $recreate = PrepareResourceMiddlewareAbstract::getParamFromRequest(static::RECREATE_COMMAND, $this->request);
        $uri = PrepareResourceMiddlewareAbstract::getParamFromRequest(static::URI_COMMAND, $this->request);
        $recreate = $fileExists && $recreate;
        $this->resourceDO->setNew(!$fileExists);
        if (!$fileExists || $recreate) {
            $this->resourceDO->setRecreate($recreate);
            $upload = $this->upload();

            // Upload must be with high priority
            if ($upload) {

                /** @see \Zend\Diactoros\Response::$phrases */
                return new FileUploadedResponse($upload, 201, $headers);
            } elseif ($uri) {
                $upload = $this->download($this->resourceDO, $uri);

                /** @see \Zend\Diactoros\Response::$phrases */
                return new FileUploadedResponse($upload, 201, $headers);
            } else {
                $body = $this->generate($this->resourceDO);

                /** @see \Zend\Diactoros\Response::$phrases */
                return new FileContentResponse($body, 201, $headers);
            }

        }

        /** @see \Zend\Diactoros\Response::$phrases */
        return new EmptyResponse(304, $headers);
    }

    /**
     * @return UploadedFile|null
     */
    protected function upload()
    {
        $uploaded = $this->request->getUploadedFiles();
        $uploaded = current($uploaded);
        if ($uploaded instanceof UploadedFile) {

            return $uploaded;
        }

        return null;
    }

    /**
     * @param ResourceDOInterface $resourceDO
     * @param string $uri
     * @return DownloadedFile
     * @throws ErrorException
     * @throws \Exception
     */
    protected function download(ResourceDOInterface $resourceDO, $uri)
    {
        // ------------
        // @todo refactoring: move downloading code from here to separate service!
        // ------------
        $dir = DATA_DIR . 'download' . DIRECTORY_SEPARATOR;
        $file = $this->resourceDO->getUuid() . '_' . time() . '_' . mt_rand(100, 200) . '.tmp';
        if(!@mkdir($dir) && !is_dir($dir)) {
            throw new ErrorException('Can\'t create the directory: ' . $dir);
        }
        if (is_file($file)) {
            if(!unlink($file)) {
                throw new ErrorException('Can\'t remove old file: ' . $dir . $file);
            }
        }
        $resource = fopen($dir . $file, 'w+');
        if (!$resource) {
            throw new ErrorException('Can\'t create the file for writting: ' . $dir . $file);
        }
        $uriEnc = str_replace(' ', '%20', $uri);
        $headers = [
            "Accept: " . $resourceDO->getMimeType(),
            "Cache-Control: no-cache",
            "Pragma: no-cache",
        ];
        $curlHandle = curl_init($uriEnc);
        if (!$curlHandle) {
            throw new ErrorException('Curl error for uri: ' . $uri . ': cannot create resource');
        }
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, static::CURL_TIMEOUT);
        // Save curl result to the file
        curl_setopt($curlHandle, CURLOPT_FILE, $resource);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlHandle, CURLOPT_MAXREDIRS, 2);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        // get curl response
        curl_exec($curlHandle);
        if (curl_errno($curlHandle)) {
            $error = curl_error($curlHandle);
            curl_close($curlHandle);
            fclose($resource);
            throw new ErrorException('Curl error for uri: ' . $uri . '; ' . $error);
        }
        $size = (int)curl_getinfo($curlHandle, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($curlHandle);
        fclose($resource);
        // ------------

        $downloaded = new DownloadedFile(
            $dir . $file
            , $size
            , UPLOAD_ERR_OK
            , $resourceDO->getName() . '.' . $resourceDO->getType()
            , $resourceDO->getMimeType()
        );

        return $downloaded;
    }
}
