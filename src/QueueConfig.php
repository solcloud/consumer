<?php

namespace Solcloud\Consumer;

class QueueConfig
{

    /** @var string */
    protected $host = 'CHANGEME';
    /** @var int */
    protected $port = 5672;
    /** @var string */
    protected $username = 'CHANGEME';
    /** @var string */
    protected $password = 'CHANGEME';
    /** @var string */
    protected $vhost = 'CHANGEME';
    /** @var bool */
    protected $insist = false;
    /** @var string */
    protected $loginMethod = 'AMQPLAIN';
    /** @var string */
    protected $locale = 'en_US';
    /** @var int */
    protected $connectionTimeoutSec = 6;
    /** @var int */
    protected $readWriteTimeoutSec = 6;
    /** @var float */
    protected $rpcTimeoutFloat = 6.0;
    /** @var bool */
    protected $keepalive = true;
    /** @var int */
    protected $heartbeatSec = 0;

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getVhost(): string
    {
        return $this->vhost;
    }

    public function getInsist(): bool
    {
        return $this->insist;
    }

    public function getLoginMethod(): string
    {
        return $this->loginMethod;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getConnectionTimeoutSec(): int
    {
        return $this->connectionTimeoutSec;
    }

    public function getReadWriteTimeoutSec(): int
    {
        return $this->readWriteTimeoutSec;
    }

    public function getKeepalive(): bool
    {
        return $this->keepalive;
    }

    public function getHeartbeatSec(): int
    {
        return $this->heartbeatSec;
    }

    public function getRpcTimeoutFloat(): float
    {
        return $this->rpcTimeoutFloat;
    }

    public function setHost(string $host): self
    {
        $this->host = $host;
        return $this;
    }

    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setVhost(string $vhost): self
    {
        $this->vhost = $vhost;
        return $this;
    }

    public function setInsist(bool $insist): self
    {
        $this->insist = $insist;
        return $this;
    }

    public function setLoginMethod(string $loginMethod): self
    {
        $this->loginMethod = $loginMethod;
        return $this;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function setConnectionTimeoutSec(int $connectionTimeoutSec): self
    {
        $this->connectionTimeoutSec = $connectionTimeoutSec;
        return $this;
    }

    public function setReadWriteTimeoutSec(int $readWriteTimeoutSec): self
    {
        $this->readWriteTimeoutSec = $readWriteTimeoutSec;
        return $this;
    }

    public function setKeepalive(bool $keepalive): self
    {
        $this->keepalive = $keepalive;
        return $this;
    }

    public function setHeartbeatSec(int $heartbeatSec): self
    {
        $this->heartbeatSec = $heartbeatSec;
        return $this;
    }

    public function setRpcTimeoutFloat(int $rpcTimeoutFloat): void
    {
        $this->rpcTimeoutFloat = $rpcTimeoutFloat;
    }

}
