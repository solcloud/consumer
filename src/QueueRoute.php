<?php

namespace Solcloud\Consumer;

class QueueRoute
{

    /** @var string */
    private $exchange;
    /** @var string */
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
