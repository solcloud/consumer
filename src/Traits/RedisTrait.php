<?php

namespace Solcloud\Consumer\Traits;

use Redis;

trait RedisTrait
{

    /** @var Redis */
    private $redis;

    public function setRedis(Redis $redis): void
    {
        $this->redis = $redis;
    }

    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * Try to insert to db only if not already exists
     * If $key not exists in db - insert it and return true
     * If $key already exists in db - keep value of original $key and return false
     * @param mixed $value
     * @return bool True if $key is not already in db, false otherwise
     */
    protected function jobLock(string $key, $value, int $expirationInSec): bool
    {
        return $this->getRedis()->set($key, $value, ['nx', 'ex' => $expirationInSec]);
    }

    /**
     * @param mixed $value
     */
    protected function jobUnlock(string $key, $value): void
    {
        $expectedValue = $this->getRedis()->get($key);
        if ($expectedValue && $expectedValue === $value) {
            $this->getRedis()->delete($key);
        }
    }

}
