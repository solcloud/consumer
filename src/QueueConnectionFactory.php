<?php

namespace Solcloud\Consumer;

use Solcloud\Consumer\QueueConfig;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Connection\AMQPLazySocketConnection;

class QueueConnectionFactory
{

    /**
     * @var QueueConfig
     */
    protected $config;

    public function __construct(QueueConfig $config)
    {
        $this->config = $config;
    }

    public function createStreamConnection(bool $lazy = false): AMQPStreamConnection
    {
        if ($lazy) {
            return new AMQPLazyConnection(
                    $this->config->getHost()
                    , $this->config->getPort()
                    , $this->config->getUsername()
                    , $this->config->getPassword()
                    , $this->config->getVhost()
                    , $this->config->getInsist()
                    , $this->config->getLoginMethod()
                    , null
                    , $this->config->getLocale()
                    , $this->config->getConnectionTimeoutSec()
                    , $this->config->getReadWriteTimeoutSec()
                    , null
                    , $this->config->getKeepalive()
                    , $this->config->getHeartbeatSec()
                    , $this->config->getRpcTimeoutFloat()
            );
        }

        return new AMQPStreamConnection(
                $this->config->getHost()
                , $this->config->getPort()
                , $this->config->getUsername()
                , $this->config->getPassword()
                , $this->config->getVhost()
                , $this->config->getInsist()
                , $this->config->getLoginMethod()
                , null
                , $this->config->getLocale()
                , $this->config->getConnectionTimeoutSec()
                , $this->config->getReadWriteTimeoutSec()
                , null
                , $this->config->getKeepalive()
                , $this->config->getHeartbeatSec()
                , $this->config->getRpcTimeoutFloat()
        );
    }

    public function createSocketConnection(bool $lazy = false): AMQPSocketConnection
    {

        if ($lazy) {
            return new AMQPLazySocketConnection(
                    $this->config->getHost()
                    , $this->config->getPort()
                    , $this->config->getUsername()
                    , $this->config->getPassword()
                    , $this->config->getVhost()
                    , $this->config->getInsist()
                    , $this->config->getLoginMethod()
                    , null
                    , $this->config->getLocale()
                    , $this->config->getConnectionTimeoutSec()
                    , $this->config->getKeepalive()
                    , $this->config->getReadWriteTimeoutSec()
                    , $this->config->getHeartbeatSec()
                    , $this->config->getRpcTimeoutFloat()
            );
        }

        return new AMQPSocketConnection(
                $this->config->getHost()
                , $this->config->getPort()
                , $this->config->getUsername()
                , $this->config->getPassword()
                , $this->config->getVhost()
                , $this->config->getInsist()
                , $this->config->getLoginMethod()
                , null
                , $this->config->getLocale()
                , $this->config->getConnectionTimeoutSec()
                , $this->config->getKeepalive()
                , $this->config->getReadWriteTimeoutSec()
                , $this->config->getHeartbeatSec()
                , $this->config->getRpcTimeoutFloat()
        );
    }

}
