<?php

namespace Solcloud\Consumer\Traits;

trait RedisLockTrait {

    use RedisTrait;

    public function jobExists($workerSubject, $jobUniqueIdentifier) {
        $jobStatusValueOrFalse = $this->getRedis()->get($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier));
        return ($jobStatusValueOrFalse !== FALSE);
    }

    public function isJobComplete($workerSubject, $jobUniqueIdentifier) {
        $jobStatusValueOrFalse = $this->getRedis()->get($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier));
        if ($jobStatusValueOrFalse !== FALSE && $jobStatusValueOrFalse === 'OK') {
            return TRUE;
        }

        return FALSE;
    }

    public function setJobComplete($workerSubject, $jobUniqueIdentifier, $expirationInSec = 24 * 60 * 60) {
        $this->getRedis()->set($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier), 'OK', ['ex' => $expirationInSec]);
    }

    public function isJobFailed($workerSubject, $jobUniqueIdentifier) {
        $jobStatusValueOrFalse = $this->getRedis()->get($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier));
        if ($jobStatusValueOrFalse !== FALSE && $jobStatusValueOrFalse === 'FAIL') {
            return TRUE;
        }

        return FALSE;
    }

    public function setJobFailed($workerSubject, $jobUniqueIdentifier, $expirationInSec = 24 * 60 * 60) {
        $this->getRedis()->set($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier), 'FAIL', ['ex' => $expirationInSec]);
    }

    public function isJobWaiting($workerSubject, $jobUniqueIdentifier) {
        $jobStatusValueOrFalse = $this->getRedis()->get($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier));
        if ($jobStatusValueOrFalse !== FALSE && $jobStatusValueOrFalse === 'WAIT') {
            return TRUE;
        }

        return FALSE;
    }

    public function setJobWaiting($workerSubject, $jobUniqueIdentifier, $expirationInSec = 24 * 60 * 60) {
        $this->getRedis()->set($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier), 'WAIT', ['ex' => $expirationInSec]);
    }

    public function getJobRetryCount($workerSubject, $jobUniqueIdentifier) {
        $jobRetryValueOrFalse = $this->getRedis()->get($this->generateLockKey('retry', $workerSubject, $jobUniqueIdentifier));
        if ($jobRetryValueOrFalse === FALSE) {
            return 0;
        }

        return $jobRetryValueOrFalse;
    }

    public function incrementJobRetryCount($workerSubject, $jobUniqueIdentifier) {
        $this->getRedis()->incr($this->generateLockKey('retry', $workerSubject, $jobUniqueIdentifier));
    }

    public function createJobIfNotExists($workerSubject, $jobUniqueIdentifier, $expirationInSec = 24 * 60 * 60) {
        $this->getRedis()->set($this->generateLockKey('status', $workerSubject, $jobUniqueIdentifier), 'WAIT', ['nx', 'ex' => $expirationInSec]);
        $this->getRedis()->set($this->generateLockKey('retry', $workerSubject, $jobUniqueIdentifier), 0, ['nx', 'ex' => $expirationInSec]);
    }

    protected function generateLockKey($subject, $workerSubject, $jobUniqueIdentifier) {
        return sprintf('%s:%s:%s', $subject, $workerSubject, $jobUniqueIdentifier);
    }

}
