<?php

namespace Solcloud\Consumer;

class NullPublisher implements IPublisher
{

    public function send(array $data, QueueRoute $route, array $meta = []): void
    {
        // do nothing
    }

}
