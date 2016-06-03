<?php
namespace Staticus\Auth\Factories;

use Staticus\Auth\SaveHandlers\Redis;
use Staticus\Config\ConfigInterface;
use Zend\Session\Config\SessionConfig;
use Zend\Session\SessionManager;

class SessionManagerFactory
{
    protected $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config->get('auth.session');
    }

    public function __invoke()
    {
        $sessionConfig = new SessionConfig();
        $sessionConfig->setOptions($this->config['options']);
        $sessionManager = new SessionManager($sessionConfig);
        if (class_exists(\Redis::class)) {
            $saveHandler = new Redis(
                $this->config['redis']['host'],
                $this->config['redis']['port'],
                $this->config['redis']['password']
            );
            $sessionManager->setSaveHandler($saveHandler);
            $sessionManager->start();
        } else {
            trigger_error('Redis extension is not found. '
                . \Staticus\Auth\AuthSessionMiddleware::class . ' will not work.', E_USER_NOTICE);
        }

        return $sessionManager;
    }
}
