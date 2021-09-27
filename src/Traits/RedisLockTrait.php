<?php

namespace Solcloud\Consumer\Traits;

trait RedisLockTrait
{

    use RedisTrait;

    /**
     * @param int|string $jobUniqueIdentifier
     */
    public function jobExists(string $workerSubject, $jobUniqueIdentifier): bool
    {
        $jobStatusValueOrFalse = $this->getRedis()->get($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier));
        return ($jobStatusValueOrFalse !== false);
    }

    /**
     * @param int|string $jobUniqueIdentifier
     */
    public function isJobComplete(string $workerSubject, $jobUniqueIdentifier): bool
    {
        $jobStatusValueOrFalse = $this->getRedis()->get($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier));
        if ($jobStatusValueOrFalse !== false && $jobStatusValueOrFalse === 'OK') {
            return true;
        }

        return false;
    }

    /**
     * @param int|string $jobUniqueIdentifier
     */
    public function setJobComplete(string $workerSubject, $jobUniqueIdentifier, int $expirationInSec = 24 * 60 * 60): void
    {
        $this->getRedis()->set($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier), 'OK', ['ex' => $expirationInSec]);
    }

    /**
     * @param int|string $jobUniqueIdentifier
     */
    public function isJobFailed(string $workerSubject, $jobUniqueIdentifier): bool
    {
        $jobStatusValueOrFalse = $this->getRedis()->get($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier));
        if ($jobStatusValueOrFalse !== false && $jobStatusValueOrFalse === 'FAIL') {
            return true;
        }

        return false;
    }

    /**
     * @param int|string $jobUniqueIdentifier
     */
    public function setJobFailed(string $workerSubject, $jobUniqueIdentifier, int $expirationInSec = 24 * 60 * 60): void
    {
        $this->getRedis()->set($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier), 'FAIL', ['ex' => $expirationInSec]);
    }

    /**
     * @param int|string $jobUniqueIdentifier
     */
    public function isJobWaiting(string $workerSubject, $jobUniqueIdentifier): bool
    {
        $jobStatusValueOrFalse = $this->getRedis()->get($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier));
        if ($jobStatusValueOrFalse !== false && $jobStatusValueOrFalse === 'WAIT') {
            return true;
        }

        return false;
    }

    /**
     * @param int|string $jobUniqueIdentifier
     */
    public function setJobWaiting(string $workerSubject, $jobUniqueIdentifier, int $expirationInSec = 24 * 60 * 60): void
    {
        $this->getRedis()->set($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier), 'WAIT', ['ex' => $expirationInSec]);
    }

    /**
     * @param int|string $jobUniqueIdentifier
     */
    public function getJobRetryCount(string $workerSubject, $jobUniqueIdentifier): int
    {
        $jobRetryValueOrFalse = $this->getRedis()->get($this->generateLockKey('retry', $workerSubject, $jobUniqueIdentifier));
        if ($jobRetryValueOrFalse === false) {
            return 0;
        }

        return (int)$jobRetryValueOrFalse;
    }

    /**
     * @param int|string $jobUniqueIdentifier
     */
    public function incrementJobRetryCount(string $workerSubject, $jobUniqueIdentifier): void
    {
        $this->getRedis()->incr($this->generateLockKey('retry', $workerSubject, $jobUniqueIdentifier));
    }

    /**
     * @param int|string $jobUniqueIdentifier
     */
    public function createJobIfNotExists(string $workerSubject, $jobUniqueIdentifier, int $expirationInSec = 24 * 60 * 60): void
    {
        $this->getRedis()->set($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier), 'WAIT', [
            'nx', 'ex' => $expirationInSec,
        ]);
        $this->getRedis()->set($this->generateLockKey('retry', $workerSubject, $jobUniqueIdentifier), 0, ['nx', 'ex' => $expirationInSec]);
    }

    /**
     * @param int|string $jobUniqueIdentifier
     */
    protected function generateLockKey(string $subject, string $workerSubject, $jobUniqueIdentifier): string
    {
        return sprintf('%s:%s:%s', $subject, $workerSubject, $jobUniqueIdentifier);
    }

}
