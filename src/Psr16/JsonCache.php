<?php

namespace Effectra\Cache\Psr16;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * JsonCache implements the CacheInterface and provides a JSON-based caching mechanism.
 */
class JsonCache implements CacheInterface
{
    /**
     * The directory where cache files will be stored.
     *
     * @var string
     */
    private $cacheDir;

    /**
     * JsonCache constructor.
     *
     * @param string $cacheDir The directory where cache files will be stored.
     */
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
        $this->createCacheDirIfNotExists();
    }

    /**
     * Retrieves an item from the cache based on its key.
     *
     * @param string $key The key of the item to retrieve.
     * @param mixed $default The default value to return if the item does not exist.
     * @return mixed The value of the item if it exists, or the default value otherwise.
     */
    public function get(string $key, $default = null): mixed
    {
        $this->validateKey($key);

        $filename = $this->getCacheFilename($key);

        if (file_exists($filename)) {
            $contents = file_get_contents($filename);
            $data = json_decode($contents, true);

            if ($data['expiration'] === null || $data['expiration'] >= time()) {
                return $data['value'];
            } else {
                $this->delete($key);
            }
        }

        return $default;
    }

    /**
     * Stores an item in the cache with the specified key and value.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value to store.
     * @param null|int|\DateInterval $ttl The time-to-live (TTL) value in seconds or a DateInterval instance.
     * @return bool True on success, false on failure.
     */
    public function set(string $key, $value, $ttl = null): bool
    {
        $this->validateKey($key);

        $filename = $this->getCacheFilename($key);
        $data = [
            'value' => $value,
            'expiration' => $this->calculateExpiration($ttl),
        ];
        $jsonData = json_encode($data);

        return file_put_contents($filename, $jsonData, LOCK_EX) !== false;
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

        $filename = $this->getCacheFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
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
        $this->deleteCacheFiles();
        $this->createCacheDirIfNotExists();

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
    public function getMultiple($keys, $default = null): iterable
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentException('Keys must be an iterable');
        }

        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    /**
     * Stores multiple items in the cache.
     *
     * @param iterable $values A key-value array of items to store.
     * @param null|int|\DateInterval $ttl The time-to-live (TTL) value in seconds or a DateInterval instance.
     * @return bool True on success, false on failure.
     * @throws InvalidArgumentException If the values parameter is not iterable.
     */
    public function setMultiple($values, $ttl = null): bool
    {
        if (!is_iterable($values)) {
            throw new InvalidArgumentException('Values must be an iterable');
        }

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
    public function deleteMultiple($keys): bool
    {
        if (!is_iterable($keys)) {
            throw new InvalidArgumentException('Keys must be an iterable');
        }

        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
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

        return $this->get($key) !== null;
    }

    /**
     * Creates the cache directory if it does not exist.
     *
     * @return void
     */
    private function createCacheDirIfNotExists(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * Deletes all cache files in the cache directory.
     *
     * @return void
     */
    private function deleteCacheFiles(): void
    {
        $files = glob($this->cacheDir . '/*.json');

        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Generates the cache filename based on the key.
     *
     * @param string $key The key of the item.
     * @return string The cache filename.
     */
    private function getCacheFilename($key): string
    {
        $hash = md5($key);

        return $this->cacheDir . '/' . $hash . '.json';
    }

    /**
     * Calculates the expiration timestamp based on the TTL value.
     *
     * @param null|int|\DateInterval $ttl The time-to-live (TTL) value in seconds or a DateInterval instance.
     * @return int|null The expiration timestamp or null if no TTL is specified.
     */
    private function calculateExpiration($ttl)
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof \DateInterval) {
            $ttl = (new \DateTime())->add($ttl)->getTimestamp() - time();
        }

        return time() + $ttl;
    }

    /**
     * Validates the cache key.
     *
     * @param mixed $key The cache key to validate.
     * @return void
     * @throws InvalidArgumentException If the key is not a string or is empty.
     */
    private function validateKey($key): void
    {
        if (!is_string($key)) {
            throw new InvalidArgumentException('Key must be a string');
        }

        if ($key === '') {
            throw new InvalidArgumentException('Key cannot be empty');
        }
    }
}
