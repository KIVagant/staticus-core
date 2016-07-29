<?php
namespace Staticus\Resources\Middlewares;

use Staticus\Config\ConfigInterface;
use Staticus\Diactoros\Response\ResourceDoResponse;
use Staticus\Middlewares\MiddlewareAbstract;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Staticus\Exceptions\WrongRequestException;
use Staticus\Resources\ResourceDOInterface;
use Zend\Diactoros\Response\EmptyResponse;

abstract class PrepareResourceMiddlewareAbstract extends MiddlewareAbstract
{
    protected $resourceDO;
    /**
     * @var ConfigInterface
     */
    protected $config;

    public function __construct(ResourceDOInterface $resourceDO, ConfigInterface $config)
    {
        $this->resourceDO = $resourceDO;
        $this->config = $config;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next = null
    )
    {
        parent::__invoke($request, $response, $next);
        if (!$this->fillResource()) {

            return new EmptyResponse(404, $response->getHeaders());
        }

        // Pass the resource to the next middleware
        $response = new ResourceDoResponse($this->resourceDO, $response->getStatusCode(), $response->getHeaders());

        return $next($request, $response);
    }

    /**
     * Bugfix for the russian letters
     * @param string $file
     * @return mixed
     */
    protected function mb_basename($file)
    {
        $file = explode('/', $file);

        return end($file);
    }

    /**
     * @throws WrongRequestException
     * @todo: Write separate cleanup rules for each parameter
     */
    protected function fillResource()
    {
        $name = static::getParamFromRequest('name', $this->request);
        $name = $this->cleanup($name);
        $namespace = dirname($name);
        $name = $this->mb_basename($name);
        $name = $this->defaultValidator('name', $name, false
            , ResourceDOInterface::NAME_REG_SYMBOLS, $this->config->get('staticus.clean_resource_name'));
        $namespace = $this->defaultValidator('namespace', $namespace, true
            , ResourceDOInterface::NAMESPACE_REG_SYMBOLS, $this->config->get('staticus.clean_resource_name'));
        if (!$this->namespaceValidator($namespace)) {

            return false;
        }
        $alt = static::getParamFromRequest('alt', $this->request);
        $alt = $this->cleanup($alt);
        $var = static::getParamFromRequest('var', $this->request);
        $var = $this->cleanup($var);
        $var = $this->defaultValidator('var', $var, true, ResourceDOInterface::VARIANT_REG_SYMBOLS);
        $v = (int)static::getParamFromRequest('v', $this->request);
        $author = static::getParamFromRequest('author', $this->request);
        $author = $this->cleanup($author);
        $body = static::getParamFromPost('body', $this->request);
        $body = $this->cleanupBody($body);
        $dataDir = $this->config->get('staticus.data_dir');
        /**
         * You shouldn't check 'recreate' and 'destroy' params here.
         * @see \Staticus\Action\StaticMiddlewareAbstract::postAction
         * @see \Staticus\Action\StaticMiddlewareAbstract::deleteAction
         */
        $this->resourceDO
            ->reset()
            ->setBaseDirectory($dataDir)
            ->setNamespace($namespace)
            ->setName($name)
            ->setNameAlternative($alt)
            ->setBody($body)
            ->setVariant($var)
            ->setVersion($v)
            ->setAuthor($author);
        if (!$this->resourceDO->getType()) {
            $type = static::getParamFromRequest('type', $this->request);
            $type = $this->cleanup($type);
            $type = $this->defaultValidator('type', $type);
            $this->resourceDO->setType($type);
        }
        $this->fillResourceSpecialFields();

        return true;
    }
    abstract protected function fillResourceSpecialFields();

    /**
     * @param string $name
     * @return string
     */
    protected function cleanup($name)
    {
        $name = preg_replace('/\s+/u', ' ', trim(mb_strtolower(rawurldecode((string)$name), 'UTF-8')));
        $name = str_replace(['\\'], '', $name);

        return $name;
    }

    /**
     * @param string $text
     * @return string
     */
    protected function cleanupBody($text)
    {
        $text = preg_replace('/\s+/u', ' ', trim((string)$text));

        return $text;
    }

    /**
     * @param $name
     * @param ServerRequestInterface $request
     * @return string
     * @todo move this method somethere
     */
    public static function getParamFromRequest($name, ServerRequestInterface $request)
    {
        $attribute = $request->getAttribute($name);
        if ($attribute) {

            return $attribute;
        }
        $paramsGET = $request->getQueryParams();
        $paramsPOST = (array)$request->getParsedBody();

        $str = isset($paramsPOST[$name])
            ? $paramsPOST[$name]
            : (
                isset($paramsGET[$name])
                ? $paramsGET[$name]
                : ''
            );

        return $str;
    }

    /**
     * @param $name
     * @param ServerRequestInterface $request
     * @return string
     */
    public static function getParamFromPost($name, ServerRequestInterface $request)
    {
        $paramsPOST = (array)$request->getParsedBody();

        $str = isset($paramsPOST[$name])
            ? $paramsPOST[$name]
            : '';

        return $str;
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool $canBeEmpty
     * @param string $allowedRegexpSymbols
     * @param bool $replaceDeniedSymbols
     * @return string
     * @throws WrongRequestException
     */
    protected function defaultValidator(
        $name, $value, $canBeEmpty = false, $allowedRegexpSymbols = '\w\d\-_', $replaceDeniedSymbols = false)
    {
        if (!empty($value)) {
            if ($replaceDeniedSymbols) {
                $value = preg_replace('/\s+/u', ' ', preg_replace('/[^' . $allowedRegexpSymbols . ']+/ui', '', $value));
                $value = trim(preg_replace('/\/+/u', '/', $value));
            } else {
                if (!preg_match('/^[' . $allowedRegexpSymbols . ']+$/ui', $value)) {
                    throw new WrongRequestException('Wrong request param "' . $name . '": ' . $value);
                }
            }
        }
        if (empty($value) && !$canBeEmpty) {
            throw new WrongRequestException('Empty request param "' . $name . '"');
        }

        return $value;
    }

    /**
     * @param string $namespace
     * @return bool
     */
    protected function namespaceValidator($namespace)
    {
        $allowed = $this->config->get('staticus.namespaces');
        if ($namespace && !in_array($namespace, $allowed, true)) {
            foreach ($allowed as $item) {
                if (false !== strpos($item, '*') && fnmatch($item, $namespace)) {
                    // TODO: limitations for nested namespaces

                    return true;
                }
            }

            return false;
        }

        return true;
    }
}
