# Consumer

Consumer needs _AMQPChannel_ channel as only dependency.

## Channel setup

If you have channel instance in your project already than you can skip this, otherwise lets setup rabbitmq connection, we recommend to use container for
this.

```php
$config = new \Solcloud\Consumer\QueueConfig();
$config
    ->setHost('solcloud_rabbitmq')
    ->setVhost('/')
    #->setHeartbeatSec(5)
    ->setUsername('dev')
    ->setPassword('dev')
;
$connectionFactory = new \Solcloud\Consumer\QueueConnectionFactory($config);
$connection = $connectionFactory->createSocketConnection();
#(new \PhpAmqpLib\Connection\Heartbeat\PCNTLHeartbeatSender($connection))->register(); // if heartbeat and pcntl_async_signals() is available
```

Create channel from connection or use your own channel

```php
/** @var \PhpAmqpLib\Channel\AMQPChannel $channel */
$channel = $connection->channel();
```

## Worker

Create _worker_ (consumer) class for your business logic and inject _$channel_ dependency. You can extend _AbstractConsumer_ for lightweight abstraction or use "solcloud standard" _BaseConsumer_. We will use _BaseConsumer_ in this example 

```php
$worker = new class($channel) extends \Solcloud\Consumer\BaseConsumer {

    protected function run(): void
    {
        // Your hard work here
        echo "Processing message: " . $this->data->id . PHP_EOL;
    }

};
```

Start consuming message from queue using blocking method _wait_

```php
$worker->consume($consumeQueueName);
while ($worker->hasCallback()) {
    try {
        // While we have callback lets enter event loop with some timeout
        $worker->wait(rand(8, 11));
    } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $ex) {
        echo $ex->getMessage() . PHP_EOL;
    }
}
$worker->closeChannel();
```

## Message publishing

For message publish you can use _$worker_ directly or use rabbitmq management plugin or different scripts

```php
$worker->publishMessage(
    $worker->createMessageHelper([], ["id" => 1]),
    '',
    $consumeQueueName
); // OR open rabbitmq management and publish: {"meta":[],"data":{"id":1}}
```

## Logging

Worker can log to `Psr\Log\LoggerInterface` compatible logger. 

```php
$worker->setLogger(new YourPsrLogger());
$worker->getLogger()->info('Something');
```
