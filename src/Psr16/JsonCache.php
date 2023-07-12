<?php

namespace Effectra\Cache\Psr16;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class JsonCache implements CacheInterface
{
    private $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
        $this->createCacheDirIfNotExists();
    }

    public function get(string $key, $default = null):mixed
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

    public function set(string $key, $value,null|int|\DateInterval $ttl = null): bool
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

    public function delete($key): bool
    {
        $this->validateKey($key);

        $filename = $this->getCacheFilename($key);

        if (file_exists($filename)) {
            return unlink($filename);
        }

        return false;
    }

    public function clear(): bool
    {
        $this->deleteCacheFiles();
        $this->createCacheDirIfNotExists();

        return true;
    }

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

    public function has($key): bool
    {
        $this->validateKey($key);

        return $this->get($key) !== null;
    }

    private function createCacheDirIfNotExists(): void
    {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    private function deleteCacheFiles(): void
    {
        $files = glob($this->cacheDir . '/*.json');

        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function getCacheFilename($key): string
    {
        $hash = md5($key);

        return $this->cacheDir . '/' . $hash . '.json';
    }

    private function calculateExpiration($ttl)
    {
        if ($ttl === null) {
            return null;
        }

        return time() + $ttl;
    }

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
