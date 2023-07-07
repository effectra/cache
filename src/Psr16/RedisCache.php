<?php

namespace Effectra\Cache\Psr16;

use Psr\SimpleCache\CacheInterface;
use Predis\ClientInterface;
use Predis\Response\Status;

class RedisCache implements CacheInterface
{
    private $redis;

    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
    }

    public function get($key, $default = null)
    {
        $this->validateKey($key);

        $value = $this->redis->get($key);

        return $value !== null ? $value : $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        $this->validateKey($key);

        if ($ttl !== null) {
            $ttl = max(1, (int) $ttl);
            $response = $this->redis->set($key, $value, 'EX', $ttl);
        } else {
            $response = $this->redis->set($key, $value);
        }

        return $response instanceof Status && $response->getPayload() === 'OK';
    }

    public function delete($key): bool
    {
        $this->validateKey($key);

        $response = $this->redis->del([$key]);

        return $response > 0;
    }

    public function clear(): bool
    {
        $response = $this->redis->flushdb();

        return $response instanceof Status && $response->getPayload() === 'OK';
    }

    public function getMultiple($keys, $default = null): iterable
    {
        if (!is_iterable($keys)) {
            throw new \InvalidArgumentException('Keys must be an iterable');
        }

        $values = $this->redis->mget($keys);

        $result = [];
        $index = 0;

        foreach ($keys as $key) {
            $result[$key] = $values[$index] !== null ? $values[$index] : $default;
            $index++;
        }

        return $result;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        if (!is_iterable($values)) {
            throw new \InvalidArgumentException('Values must be an iterable');
        }

        $pipeline = $this->redis->pipeline();

        foreach ($values as $key => $value) {
            $this->validateKey($key);

            if ($ttl !== null) {
                $ttl = max(1, (int) $ttl);
                $pipeline->set($key, $value, 'EX', $ttl);
            } else {
                $pipeline->set($key, $value);
            }
        }

        $responses = $pipeline->execute();

        foreach ($responses as $response) {
            if (!($response instanceof Status && $response->getPayload() === 'OK')) {
                return false;
            }
        }

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        if (!is_iterable($keys)) {
            throw new \InvalidArgumentException('Keys must be an iterable');
        }

        $response = $this->redis->del($keys);

        return $response > 0;
    }

    public function has($key): bool
    {
        $this->validateKey($key);

        return $this->redis->exists($key);
    }

    private function validateKey($key): void
    {
        if (!is_string($key)) {
            throw new \InvalidArgumentException('Key must be a string');
        }

        if ($key === '') {
            throw new \InvalidArgumentException('Key cannot be empty');
        }
    }
}
