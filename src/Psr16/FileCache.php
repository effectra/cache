<?php

namespace Effectra\Cache\Psr16;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class FileCache implements CacheInterface
{
    private $cacheDir;
    
    /**
     * FileCache constructor.
     *
     * @param string $cacheDir The directory where cache files will be stored.
     */
    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
        $this->createCacheDirIfNotExists();
    }
    
    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null)
    {
        $this->validateKey($key);

        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            $contents = file_get_contents($filename);
            $data = unserialize($contents);
            
            if ($data['expiration'] === null || $data['expiration'] >= time()) {
                return $data['value'];
            } else {
                $this->delete($key);
            }
        }
        
        return $default;
    }
    
    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null): bool
    {
        $this->validateKey($key);

        $filename = $this->getCacheFilename($key);
        $data = [
            'value' => $value,
            'expiration' => $this->calculateExpiration($ttl),
        ];
        $serializedData = serialize($data);
        
        return file_put_contents($filename, $serializedData, LOCK_EX) !== false;
    }
    
    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        $this->deleteCacheDirectory();
        $this->createCacheDirIfNotExists();
        
        return true;
    }
    
    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
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
    
    private function deleteCacheDirectory(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->cacheDir),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $path) {
            if ($path->isFile()) {
                unlink($path->getPathname());
            } elseif ($path->isDir()) {
                rmdir($path->getPathname());
            }
        }
    }
    
    private function getCacheFilename($key): string
    {
        $hash = md5($key);
        
        return $this->cacheDir . '/' . $hash;
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
