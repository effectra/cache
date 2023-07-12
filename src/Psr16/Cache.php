<?php

namespace Effectra\Cache\Psr16;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Cache is a simple in-memory implementation of the CacheInterface.
 */
class Cache implements CacheInterface
{
    /**
     * The cache storage.
     *
     * @var array
     */
    private $cache = [];

    /**
     * Retrieves an item from the cache based on its key.
     *
     * @param string $key The key of the item to retrieve.
     * @param mixed $default The default value to return if the item does not exist.
     * @return mixed The value of the item if it exists, or the default value otherwise.
     */
    public function get(string $key, $default = null)
    {
        if ($this->has($key)) {
            return $this->cache[$key];
        }

        return $default;
    }

    /**
     * Stores an item in the cache with the specified key and value.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value to store.
     * @param int|null $ttl The time-to-live (TTL) value in seconds. Not used in this implementation.
     * @return bool True on success, false on failure.
     */
    public function set(string $key, $value, $ttl = null): bool
    {
        $this->cache[$key] = $value;

        return true;
    }

    /**
     * Deletes an item from the cache based on its key.
     *
     * @param string $key The key of the item to delete.
     * @return bool True if the item was successfully removed, false otherwise.
     */
    public function delete(string $key): bool
    {
        if ($this->has($key)) {
            unset($this->cache[$key]);

            return true;
        }

        return false;
    }

    /**
     * Clears the entire cache.
     *
     * @return bool True on success, false on failure.
     */
    public function clear(): bool
    {
        $this->cache = [];

        return true;
    }

    /**
     * Retrieves multiple items from the cache based on their keys.
     *
     * @param iterable $keys The keys of the items to retrieve.
     * @param mixed $default The default value to return for keys that do not exist.
     * @return iterable A key-value array of items.
     * @throws InvalidArgumentException If the keys parameter is not iterable.
     */
    public function getMultiple(iterable $keys, $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * Stores multiple items in the cache.
     *
     * @param iterable $values A key-value array of items to store.
     * @param int|null $ttl The time-to-live (TTL) value in seconds. Not used in this implementation.
     * @return bool True on success, false on failure.
     * @throws InvalidArgumentException If the values parameter is not iterable.
     */
    public function setMultiple(iterable $values, $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * Deletes multiple items from the cache based on their keys.
     *
     * @param iterable $keys The keys of the items to delete.
     * @return bool True on success, false on failure.
     * @throws InvalidArgumentException If the keys parameter is not iterable.
     */
    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Checks if an item exists in the cache.
     *
     * @param string $key The key of the item to check.
     * @return bool True if the item exists, false otherwise.
     */
    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }

    /**
     * Validates the cache key.
     *
     * @param string $key The cache key to validate.
     * @return void
     * @throws InvalidArgumentException If the key is empty.
     */
    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('The cache key cannot be empty.');
        }
    }
}
