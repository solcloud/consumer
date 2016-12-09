<?php

namespace Solcloud\Consumer\Traits;

use Redis;

trait RedisTrait {

    /** @var Redis */
    private $redis;

    public function setRedis(Redis $redis) {
        $this->redis = $redis;
    }

    public function getRedis() {
        return $this->redis;
    }

    /**
     * Try to insert to db only if not already exists
     * If $key not exists in db - insert it and return true
     * If $key already exists in db - keep value of original $key and return false
     * @param type $key
     * @param type $value
     * @param type $expirationInSec
     * @return bool True if $key is not already in db, false otherwise
     */
    protected function jobLock($key, $value, $expirationInSec) {
        return $this->getRedis()->set($key, $value, ['nx', 'ex' => $expirationInSec]);
    }

    protected function jobUnlock($key, $value) {
        $expectedValue = $this->getRedis()->get($key);
        if ($expectedValue && $expectedValue === $value) {
            $this->getRedis()->delete($key);
        }
    }

}
