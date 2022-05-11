<?php

namespace Solcloud\Consumer;

use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel as Channel;
use PhpAmqpLib\Connection\AbstractConnection as Connection;
use PhpAmqpLib\Message\AMQPMessage as Message;

class Publisher
{

    /**
     * @var Channel|null
     */
    private $channel;
    /**
     * @var Connection|null
     */
    private $connection;

    public function __construct(Connection $connection = null, Channel $channel = null)
    {
        if ($connection === null && $channel === null) {
            throw new InvalidArgumentException('Please provide at least connection or channel');
        }

        $this->channel = $channel;
        $this->connection = $connection;
    }

    /**
     * @param array<mixed> $data
     * @param array<mixed> $meta
     */
    public function send(array $data, QueueRoute $route, array $meta = []): void
    {
        $this->publishMessage($this->createMessageHelper($meta, $data), $route->getExchange(), $route->getRoutingKey());
    }


    /**
     * Create new message
     * @param string $body
     * @param array<string,int|string> $properties
     * @return Message
     */
    public function createMessage(string $body = '', array $properties = []): Message
    {
        return new Message($body, $properties);
    }

    /**
     * Create string for use as msg body payload
     * @param mixed $meta
     * @param mixed $data
     * @return string
     */
    public function createMessageBody($meta = [], $data = []): string
    {
        $failIfFalse = json_encode(
            [
                'meta' => $meta,
                'data' => $data,
            ]
        );

        if ($failIfFalse === false) {
            throw new Exception("JSON encode failed, probably invalid characters, binary data, depth limit etc.");
        }

        return $failIfFalse;
    }

    /**
     * Create msg that can be published to broker
     * @param mixed $meta
     * @param mixed $data
     * @param bool $persistent
     * @param array<string,int|string> $properties
     * @return Message
     */
    public function createMessageHelper($meta = [], $data = [], bool $persistent = true, array $properties = []): Message
    {
        return $this->createMessage(
            $this->createMessageBody($meta, $data)
            , array_merge(
                [
                    'delivery_mode' => ($persistent ? Message::DELIVERY_MODE_PERSISTENT : Message::DELIVERY_MODE_NON_PERSISTENT),
                ], $properties
            )
        );
    }

    /**
     * Publish $msg to broker
     * @param Message $msg Recommend to call $this->createMessage()
     * @param string $exchange Name of exchange to publish
     * @param string $routing_key What routing key to use
     * @param bool $mandatory
     * @param bool $immediate
     * @param int $ticket
     */
    public function publishMessage(Message $msg, string $exchange = '', string $routing_key = '', bool $mandatory = false, bool $immediate = false, int $ticket = null): void
    {
        if (!$this->channel) {
            $this->channel = $this->connection->channel();
        }
        $this->channel->basic_publish($msg, $exchange, $routing_key, $mandatory, $immediate, $ticket);
    }


}
