<?php

declare(strict_types=1);

namespace Solcloud\Consumer;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel as Channel;
use PhpAmqpLib\Message\AMQPMessage as Message;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

abstract class AbstractConsumer
{

    /** @var Channel */
    private $channel;

    /** @var Message */
    private $msg;

    /** @var null|callable */
    private $callback = null;

    /** @var null|callable */
    protected $callbackDefault = null;

    /** @var boolean */
    private $isExpectingAck = false;

    /** @var boolean */
    private $ackSend = false;

    /** @var null|string */
    private $consumingQueue = null;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
        $this->logger = new NullLogger();

        $this->setup();
    }

    /**
     * Called after constructor
     */
    protected function setup(): void
    {
        // empty hook
    }

    /**
     * Main method for processing $msg from queue
     *
     * Recommended to call this method from callback (default or defined one using setCallback)
     * Try to avoid override this method
     * @param Message $msg
     * @return void
     */
    public function process(Message $msg): void
    {
        $this->ackSend = false;
        $this->msg = $msg;

        $this->parseMessage($msg);

        if ($this->isInvalidMessage()) {
            $this->sendAck();

            return;
        }

        $this->beforeRun();
        $this->run();
        $this->afterRun();

        $this->sendAck();
    }

    /**
     * Validate and parse message
     */
    protected abstract function parseMessage(Message $msg): void;

    /**
     * Decide if current msg is valid
     * @return boolean When false processing continue, when true msg is acknowledgment and consumer move to next msg
     */
    protected abstract function isInvalidMessage(): bool;

    /**
     * Setup function, called before run function
     */
    protected function beforeRun(): void
    {
        // empty hook
    }

    /**
     * Main consumer function for business logic
     */
    protected abstract function run(): void;

    /**
     * Teardown function, called after run function
     */
    protected function afterRun(): void
    {
        // empty hook
    }

    /**
     * Set custom callback for processing msg that take precedence over default one
     * @param callable $callback function (Message $msg): void {}
     */
    public function setCallback(callable $callback): void
    {
        $this->callback = $callback;
    }

    /**
     * Start consuming messages from $queueName, using provided params
     * @param string $queueName Name of queue consumer should consume from
     * @param bool $no_ack Use acknowledgment or not
     * @param string $consumer_tag
     * @param bool $no_local
     * @param bool $exclusive
     * @param bool $nowait
     * @param ?int $ticket
     * @param array $arguments
     */
    public function consume(string $queueName, bool $no_ack = false, string $consumer_tag = '', bool $no_local = false, bool $exclusive = false, bool $nowait = false, int $ticket = null, array $arguments = []): void
    {
        $this->consumingQueue = $queueName;
        $this->isExpectingAck = !$no_ack;
        $this->channel->basic_consume($queueName, $consumer_tag, $no_local, $no_ack, $exclusive, $nowait, function (Message $msg) {
            if (is_callable($this->callback)) {
                call_user_func($this->callback, $msg);
            } elseif (is_callable($this->callbackDefault)) {
                call_user_func($this->callbackDefault, $msg);
            } else {
                throw new Exception("Consumer do not provide callable callback!");
            }
        }, $ticket, $arguments);
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
        $this->channel->basic_publish($msg, $exchange, $routing_key, $mandatory, $immediate, $ticket);
    }

    /**
     * Get current msg, strict typed exception if no msg
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->msg;
    }

    /**
     * Return true if current message was already delivered to some consumer but she or broker decided to republish message
     * @return bool
     */
    public function isMessageRedelivered(): bool
    {
        return (bool)$this->getMessage()->isRedelivered();
    }

    /**
     * Send ack to broker telling that processing this message is complete and broker could delete msg
     *
     * It is ok to call this function multiple times, function will send ack only ones and only when expecting and only if not already called sendReject
     */
    public function sendAck(): void
    {
        if ($this->isExpectingAck && !$this->ackSend) {
            $this->getMessage()->ack(false);
            $this->ackSend = true;
        }
    }

    /**
     * Send nack to broker indicating that broker should requeue msg if $requeue is true, otherwise broker delete msg from queue
     *
     * It is ok to call this function multiple times, function will send reject only ones and only when expecting and only if not already called sendAck
     * @param boolean $requeue True will instruct broker to move msg to head of queue, false will delete message from queue
     */
    public function sendReject(bool $requeue = true): void
    {
        if ($this->isExpectingAck && !$this->ackSend) {
            $this->getMessage()->reject($requeue);
            $this->ackSend = true;
        }
    }

    /**
     * Set how many (or what size) messages consumer should get from broker at once
     * @param int $prefetchCount
     * @param int $prefetchSizeOctet
     */
    public function setPrefetch(int $prefetchCount, int $prefetchSizeOctet = 0): void
    {
        $this->channel->basic_qos($prefetchSizeOctet, $prefetchCount, false);
    }

    /**
     * Blocking function for waiting on socket for communication
     * @param int $idleTimeout Max time in sec for waiting for data on socket, if expired PhpAmqpLib\Exception\AMQPTimeoutException is thrown
     * @param string[] $allowedMethods Specify array of methods string codes that this blocking function should wait for
     */
    public function wait(int $idleTimeout, array $allowedMethods = null): void
    {
        $this->channel->wait($allowedMethods, false, $idleTimeout);
    }

    /**
     * Return if consumer has callback for processing msg on channel
     * @return bool
     */
    public function hasCallback(): bool
    {
        return $this->channel->is_consuming();
    }

    public function closeChannel(): void
    {
        $this->channel->close();
    }

    /**
     * Use this method for maintaining connection (heartbeat, tcp) between time expensive task from process() method
     */
    public function refreshConnection(): void
    {
        $this->publishMessage($this->createMessage()); // for now best way to do it
    }

    /**
     * Get name of currently consuming queue
     */
    public function getConsumingQueueName(): ?string
    {
        return $this->consumingQueue;
    }

    /**
     * Set logger for logging
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

}
