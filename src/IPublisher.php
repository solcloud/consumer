<?php

namespace Solcloud\Consumer;

interface IPublisher
{
    public function send(array $data, QueueRoute $route, array $meta = []): void;

}
