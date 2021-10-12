<?php

require __DIR__ . '/vendor/autoload.php';

use Solcloud\Consumer\BaseConsumer;
use Solcloud\Consumer\QueueConfig;
use Solcloud\Consumer\QueueConnectionFactory;

///////// Configs
$useAck = true;
$prefetchCount = 1;
$consumeQueueName = 'test';

$config = new QueueConfig();
$config
    ->setHost('solcloud_rabbitmq')
    ->setVhost('/')
    #->setHeartbeatSec(5)
    ->setUsername('dev')
    ->setPassword('dev')
;
$connectionFactory = new QueueConnectionFactory($config);
$connection = $connectionFactory->createSocketConnection();
/** @var \PhpAmqpLib\Channel\AMQPChannel $channel */
$channel = $connection->channel();
#(new \PhpAmqpLib\Connection\Heartbeat\PCNTLHeartbeatSender($connection))->register(); // if heartbeat and pcntl_async_signals() is available
/////////


// Main worker class for business logic
$worker = new class($channel) extends BaseConsumer {

    protected function run(): void
    {
        echo "Processing message: " . $this->data->id . PHP_EOL;
    }

};
$worker->setMaximumNumberOfProcessedMessages(3);
$worker->setAfterMessageProcessingCallback(function (): void {
    echo "Message processing done" . PHP_EOL;
});
$worker->setPrefetch($prefetchCount);
$worker->setFailedRoutingKey('failed');
$worker->setLogger(new class extends \Psr\Log\AbstractLogger {

    public function log($level, $message, array $context = [])
    {
        echo $message . PHP_EOL;
    }

});

// Publish same message to queue
$worker->publishMessage(
    $worker->createMessageHelper([], ["id" => 1]),
    '',
    $consumeQueueName
); // OR open rabbitmq management and publish: {"meta":[],"data":{"id":1}}

$worker->consume($consumeQueueName, !$useAck);
while ($worker->hasCallback()) {
    try {
        $worker->wait(rand(8, 11));
    } catch (\Solcloud\Consumer\Exceptions\NumberOfProcessedMessagesExceed $ex) {
        echo $ex->getMessage() . PHP_EOL;
        break;
    } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $ex) {
        echo $ex->getMessage() . PHP_EOL;
    }
}
$worker->closeChannel();
