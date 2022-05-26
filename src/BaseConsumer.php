<?php

declare(strict_types=1);

namespace Solcloud\Consumer;

use Exception;
use PhpAmqpLib\Message\AMQPMessage as Message;
use Solcloud\Consumer\Exceptions\MessageCannotBeParsed;
use Solcloud\Consumer\Exceptions\NumberOfProcessedMessagesExceed;
use Throwable;

abstract class BaseConsumer extends AbstractConsumer
{

    /** @var mixed */
    protected $meta;

    /** @var mixed */
    protected $data;

    /** @var mixed */
    protected $response;

    /** @var integer */
    private $numberOfProcessedMessages = 0;

    /** @var integer */
    private $maximumNumberOfProcessedMessages = -1;

    /** @var null|string */
    private $failedExchange = null;

    /** @var null|string */
    private $failedRoutingKey = null;

    /** @var null|callable */
    private $afterMessageProcessingCallback = null;

    protected function setup(): void
    {
        $this->callbackDefault = function ($msg) {
            try {
                $this->process($msg);
                $this->processSucceed();
            } catch (MessageCannotBeParsed $ex) {
                $this->processUnparsed($ex);
            } catch (Throwable $ex) {
                $this->processFailed($ex);
            } finally {
                $this->finishProcessing();
                $this->sendAck();
            }

            if (is_callable($this->afterMessageProcessingCallback)) {
                call_user_func($this->afterMessageProcessingCallback);
            }

            $this->numberOfProcessedMessages++;
            if ($this->maximumNumberOfProcessedMessages !== -1 && $this->numberOfProcessedMessages >= $this->maximumNumberOfProcessedMessages) {
                throw new NumberOfProcessedMessagesExceed;
            }
        };
    }

    /**
     * Try to parse $msg, if unable to parse throw MessageCannotBeParsed
     *
     * If throws MessageCannotBeParsed, processUnparsed() will fire
     * @param Message $msg
     * @throws MessageCannotBeParsed
     */
    protected function parseMessage(Message $msg): void
    {
        $data = json_decode($msg->getBody());
        if ($data === null) {
            throw new MessageCannotBeParsed('Json decode failed! Not valid json data or recursion limit hit!');
        }
        if (!isset($data->meta) || !isset($data->data)) {
            throw new MessageCannotBeParsed('Unable to find $data->meta OR $data->data in msg payload');
        }

        $this->meta = $data->meta;
        $this->data = $data->data;
        $this->response = null;
    }

    protected function isInvalidMessage(): bool
    {
        return false;
    }

    /**
     * Called when processing of msg succed (no exception is thrown)
     *
     * Warning: this method is not checked so beware of exception or fatal error
     */
    protected function processSucceed(): void
    {
        // empty hook
    }

    /**
     * Called when parsing of msg failed (MessageCannotBeParsed or child is thrown $ex)
     *
     * Warning: this method is not checked so beware of exception or fatal error
     */
    protected function processUnparsed(Throwable $ex): void
    {
        $this->getLogger()->error($ex);

        $this->sendCurrentMsgCopyToFailed();
    }

    /**
     * Called when processing of msg failed (exception $ex is thrown)
     *
     * Warning: this method is not checked so beware of exception or fatal error
     */
    protected function processFailed(Throwable $ex): void
    {
        $this->getLogger()->error($ex); // kinda not good worldwide but mostly yes

        $this->sendCurrentMsgCopyToFailed();
    }

    /**
     * Called at the end of msg processing no matter if processing failed or succed
     *
     * Warning: this method is not checked so beware of exception or fatal error
     */
    protected function finishProcessing(): void
    {
        // empty hook
    }

    /**
     * Create msg that can be published to broker
     * @param mixed $meta
     * @param mixed $data
     * @param bool $persistent
     * @return Message
     */
    public function createMessageHelper($meta = [], $data = [], bool $persistent = true): Message
    {
        $properties['delivery_mode'] = ($persistent ? Message::DELIVERY_MODE_PERSISTENT : Message::DELIVERY_MODE_NON_PERSISTENT);
        if (is_numeric($meta['_priority'] ?? false)) {
            $properties['priority'] = (int)min(1, max(255, (int)$meta['_priority']));
        }

        return parent::createMessage(
            $this->createMessageBody($meta, $data)
            , $properties
        );
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
     * If isset failedExchange AND isset failedRoutingKey - send current msg copy there
     */
    protected function sendCurrentMsgCopyToFailed(): void
    {
        if (isset($this->failedExchange) && isset($this->failedRoutingKey)) {
            $this->publishMessage($this->getMessage(), $this->failedExchange, $this->failedRoutingKey);
        }
    }

    /**
     * Publish current msg data to targets from $queueRoute
     */
    protected function publishCurrentMsgDataToRoute(QueueRoute $queueRoute): void
    {
        $this->publishCurrentMsgDataTo($queueRoute->getExchange(), $queueRoute->getRoutingKey());
    }

    /**
     * Publish current msg data to another exchange, if default values are provided than msg is sent to current queue using amqp default exchange
     * @param string|null $exchange
     * @param string|null $routingKey
     */
    protected function publishCurrentMsgDataTo(string $exchange = null, string $routingKey = null): void
    {
        if ($exchange === null && $routingKey === null) {
            $exchange = '';
            $routingKey = $this->getConsumingQueueName();
        }
        $this->publishMessage($this->createMessageHelper($this->meta, $this->data), $exchange, $routingKey);
    }

    /**
     * Ack this msg and send current msg data to current queue
     * use this if you change msg data and want it to publish to same queue
     */
    protected function republishCurrentMsgData(): void
    {
        $this->sendAck();
        $this->publishCurrentMsgDataTo();
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response): void
    {
        $this->response = $response;
    }

    public function getNumberOfProcessedMessages(): int
    {
        return $this->numberOfProcessedMessages;
    }

    public function getMaximumNumberOfProcessedMessages(): int
    {
        return $this->maximumNumberOfProcessedMessages;
    }

    public function setMaximumNumberOfProcessedMessages(int $maximumNumberOfProcessedMessages): void
    {
        $this->maximumNumberOfProcessedMessages = $maximumNumberOfProcessedMessages;
    }

    public function getFailedExchange(): ?string
    {
        return $this->failedExchange;
    }

    public function setFailedExchange(string $failedExchange = null): void
    {
        $this->failedExchange = $failedExchange;
    }

    public function getFailedRoutingKey(): ?string
    {
        return $this->failedRoutingKey;
    }

    public function setFailedRoutingKey(string $failedRoutingKey = null): void
    {
        $this->failedRoutingKey = $failedRoutingKey;
    }

    /**
     * User provided callback after finishing (acking) current message
     * @param null|callable $callable function(): void
     * @return void
     */
    public function setAfterMessageProcessingCallback(?callable $callable): void
    {
        $this->afterMessageProcessingCallback = $callable;
    }

}
