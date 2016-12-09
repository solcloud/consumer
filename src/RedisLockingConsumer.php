<?php

namespace Solcloud\Consumer;

use Throwable;
use Solcloud\Consumer\Traits\RedisLockTrait;
use PhpAmqpLib\Message\AMQPMessage as Message;
use Solcloud\Consumer\Exceptions\MessageCannotBeParsed;

abstract class RedisLockingConsumer extends BaseConsumer
{

    use RedisLockTrait;

    /** @var integer|string */
    private $jobId;

    /** @var integer */
    private $retryLimitForMessage = 0;

    /** @var integer */
    private $jobLockTimeInSec = 15552000; // half year

    /** @var string */
    private $jobLockKey;

    /** @var string */
    private $jobLockValue;

    /** @var boolean */
    private $useLockingFeature = false;

    /** @var null|string */
    private $delayedExchange = null;

    /** @var null|string */
    private $delayedRoutingKey = null;

    /** @var boolean */
    private $markJobAsComplete = true;

    protected abstract function getWorkerIdentifier(): string;

    protected function parseMessage(Message $msg): void
    {
        parent::parseMessage($msg);

        $this->setMarkJobAsComplete(true);
        $this->useLockingFeature = false;
        if (isset($this->meta->_id) && $this->meta->_id !== '') {
            $this->jobId = $this->meta->_id;
            $this->createJobIfNotExists($this->getWorkerIdentifier(), $this->jobId);

            if ($this->isJobFailed($this->getWorkerIdentifier(), $this->jobId)) {
                throw new MessageCannotBeParsed("Job is marked as 'FAILED'. Skipping as unparsedMessage. WorkerID: '{$this->getWorkerIdentifier()}', JobID: '{$this->jobId}'.");
            }

            $this->useLockingFeature = true;
        } else {
            $this->getLogger()->error("JobId (msg->meta->_id) is NULL or ''! Unable to use LockingFeature! Fallbacking to process without locks.");
        }
    }

    protected function isInvalidMessage(): bool
    {
        if ($this->useLockingFeature()) {
            if ($this->isJobComplete($this->getWorkerIdentifier(), $this->jobId)) {
                $this->getLogger()->info("Job is marked as 'COMPLETE'. Returning as invalid message. WorkerID: '{$this->getWorkerIdentifier()}', JobID: '{$this->jobId}'.");
                return TRUE;
            }

            $this->jobLockKey = $this->generateLockKey('lock', $this->getWorkerIdentifier(), $this->jobId);
            $this->jobLockValue = gethostname() . '_' . microtime() . '_' . $this->jobLockKey;
            if ($this->jobLock($this->jobLockKey, $this->jobLockValue, $this->jobLockTimeInSec) === FALSE) {
                if ($this->isMessageRedelivered()) {
                    $this->getLogger()->error("Already locked with key '{$this->jobLockKey}', redelivered flag was set, previous worker probably died or no delay strategy, sending msg copy to failed");
                    $this->sendCurrentMsgCopyToFailed();
                } else {
                    $this->getLogger()->info("Already locked with key '{$this->jobLockKey}'. Returning as invalid message.");
                }
                return TRUE;
            }
        }

        return parent::isInvalidMessage();
    }

    protected function processSucceed(): void
    {
        if ($this->useLockingFeature() && $this->getMarkJobAsComplete()) {
            $this->setJobComplete($this->getWorkerIdentifier(), $this->jobId);
        }
    }

    protected function processFailed(Throwable $ex): void
    {
        if ($this->useLockingFeature()) {
            $this->getLogger()->error($ex);
            if ($this->getJobRetryCount($this->getWorkerIdentifier(), $this->jobId) >= $this->retryLimitForMessage) {
                $this->getLogger()->warning("Message exceed 'retryLimitForMessage' ({$this->retryLimitForMessage}). Deleting message, setting 'FAIL' status and sending msg copy to failed");
                $this->setJobFailed($this->getWorkerIdentifier(), $this->jobId);
                $this->sendCurrentMsgCopyToFailed();
            } else {
                $this->incrementJobRetryCount($this->getWorkerIdentifier(), $this->jobId);
                $this->delayCurrentJob();
            }
        } else {
            parent::processFailed($ex);
        }
    }

    protected function delayCurrentJob(): void
    {
        if (isset($this->delayedExchange) && isset($this->delayedRoutingKey)) {
            $this->publishMessage($this->getMessage(), $this->delayedExchange, $this->delayedRoutingKey);
        } else {
            $this->getLogger()->warning("DelayedExchange AND/OR DelayedRoutingKey is NOT set, fallbacking to reject job with requeu.");
            $this->sendReject(TRUE);
        }

        $this->setMarkJobAsComplete(false);
    }

    protected function finishProcessing(): void
    {
        if ($this->useLockingFeature()) {
            $this->jobUnlock($this->jobLockKey, $this->jobLockValue);
        }
    }

    protected function useLockingFeature(): bool
    {
        return $this->useLockingFeature;
    }

    public function getRetryLimitForMessage(): int
    {
        return $this->retryLimitForMessage;
    }

    public function setRetryLimitForMessage(int $retryLimitForMessage): void
    {
        $this->retryLimitForMessage = $retryLimitForMessage;
    }

    public function getJobLockTimeInSec(): int
    {
        return $this->jobLockTimeInSec;
    }

    public function setJobLockTimeInSec(int $jobLockTimeInSec): void
    {
        $this->jobLockTimeInSec = $jobLockTimeInSec;
    }

    public function getDelayedExchange(): ?string
    {
        return $this->delayedExchange;
    }

    public function setDelayedExchange(string $delayedExchange = null): void
    {
        $this->delayedExchange = $delayedExchange;
    }

    public function getDelayedRoutingKey(): ?string
    {
        return $this->delayedRoutingKey;
    }

    public function setDelayedRoutingKey(string $delayedRoutingKey = null): void
    {
        $this->delayedRoutingKey = $delayedRoutingKey;
    }

    public function getMarkJobAsComplete(): bool
    {
        return $this->markJobAsComplete;
    }

    public function setMarkJobAsComplete(bool $markJobAsComplete): void
    {
        $this->markJobAsComplete = $markJobAsComplete;
    }

}
