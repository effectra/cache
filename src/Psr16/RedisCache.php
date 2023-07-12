<?php

namespace Effectra\Cache\Psr16;

use Predis\Client;
use Psr\SimpleCache\CacheInterface;
use Predis\ClientInterface;
use Predis\Response\Status;

/**
 * RedisCache implements the CacheInterface and provides a caching mechanism using Redis.
 */
class RedisCache implements CacheInterface
{
    /**
     * The Redis client instance.
     *
     * @var ClientInterface
     */
    private ClientInterface $redis;

    /**
     * RedisCache constructor.
     *
     * @param ClientInterface|null $redis The Redis client instance to use. If not provided, a new instance of Client will be created.
     */
    public function __construct(ClientInterface $redis = null)
    {
        $this->redis = $redis ?: new Client();
    }

    /**
     * Retrieves an item from the cache based on its key.
     *
     * @param string $key The key of the item to retrieve.
     * @param mixed $default The default value to return if the item does not exist.
     * @return mixed The value of the item if it exists, or the default value otherwise.
     */
    public function get($key, $default = null)
    {
        $this->validateKey($key);

        $value = $this->redis->get($key);

        return $value !== null ? $value : $default;
    }

    /**
     * Stores an item in the cache with the specified key and value.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value to store.
     * @param int|null $ttl The time-to-live (TTL) value in seconds.
     * @return bool True on success, false on failure.
     */
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

    /**
     * Deletes an item from the cache based on its key.
     *
     * @param string $key The key of the item to delete.
     * @return bool True if the item was successfully removed, false otherwise.
     */
    public function delete($key): bool
    {
        $this->validateKey($key);

        $response = $this->redis->del([$key]);

        return $response > 0;
    }

    /**
     * Clears the entire cache.
     *
     * @return bool True on success, false on failure.
     */
    public function clear(): bool
    {
        $response = $this->redis->flushdb();

        return $response instanceof Status && $response->getPayload() === 'OK';
    }

    /**
     * Retrieves multiple items from the cache based on their keys.
     *
     * @param iterable $keys The keys of the items to retrieve.
     * @param mixed $default The default value to return for keys that do not exist.
     * @return iterable A key-value array of items.
     * @throws \InvalidArgumentException If the keys parameter is not iterable.
     */
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

    /**
     * Stores multiple items in the cache.
     *
     * @param iterable $values A key-value array of items to store.
     * @param int|null $ttl The time-to-live (TTL) value in seconds.
     * @return bool True on success, false on failure.
     * @throws \InvalidArgumentException If the values parameter is not iterable.
     */
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

    /**
     * Deletes multiple items from the cache based on their keys.
     *
     * @param iterable $keys The keys of the items to delete.
     * @return bool True on success, false on failure.
     * @throws \InvalidArgumentException If the keys parameter is not iterable.
     */
    public function deleteMultiple($keys): bool
    {
        if (!is_iterable($keys)) {
            throw new \InvalidArgumentException('Keys must be an iterable');
        }

        $response = $this->redis->del($keys);

        return $response > 0;
    }

    /**
     * Checks if an item exists in the cache.
     *
     * @param string $key The key of the item to check.
     * @return bool True if the item exists, false otherwise.
     */
    public function has($key): bool
    {
        $this->validateKey($key);

        return $this->redis->exists($key);
    }

    /**
     * Validates the cache key.
     *
     * @param mixed $key The cache key to validate.
     * @return void
     * @throws \InvalidArgumentException If the key is not a string or is empty.
     */
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
