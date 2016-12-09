<?php

namespace Solcloud\Consumer;

class QueueRoute
{

    private $exchange;
    private $routingKey;

    public function __construct(string $routingKey, string $exchange = '')
    {
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
    }

    public function getExchange(): string
    {
        return $this->exchange;
    }

    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }

}
