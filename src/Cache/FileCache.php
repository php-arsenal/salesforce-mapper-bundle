<?php

namespace PhpArsenal\SalesforceMapperBundle\Cache;

use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class FileCache implements CacheItemPoolInterface
{
    private FilesystemAdapter $cache;

    public function __construct(string $dir)
    {
        $this->cache = new FilesystemAdapter('salesforce', 0, $dir);
    }

    public function getItem(string $key): \Psr\Cache\CacheItemInterface
    {
        return $this->cache->getItem($this->normalizeKey($key));
    }

    public function getItems(array $keys = []): iterable
    {
        return $this->cache->getItems(array_map([$this, 'normalizeKey'], $keys));
    }

    public function hasItem(string $key): bool
    {
        return $this->cache->hasItem($this->normalizeKey($key));
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    public function deleteItem(string $key): bool
    {
        return $this->cache->deleteItem($this->normalizeKey($key));
    }

    public function deleteItems(array $keys): bool
    {
        return $this->cache->deleteItems(array_map([$this, 'normalizeKey'], $keys));
    }

    public function save(\Psr\Cache\CacheItemInterface $item): bool
    {
        return $this->cache->save($item);
    }

    public function saveDeferred(\Psr\Cache\CacheItemInterface $item): bool
    {
        return $this->cache->saveDeferred($item);
    }

    public function commit(): bool
    {
        return $this->cache->commit();
    }

    private function normalizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_.]/', '_', $key);
    }

    // legacy methods for backward compat
    public function fetch(string $id): mixed
    {
        $item = $this->getItem($id);
        return $item->isHit() ? $item->get() : null;
    }

    public function contains(string $id): bool
    {
        return $this->hasItem($id);
    }

    public function store(string $id, mixed $data, int $lifeTime = 0): bool
    {
        $item = $this->getItem($id);
        $item->set($data);
        if ($lifeTime > 0) {
            $item->expiresAfter($lifeTime);
        }
        return $this->save($item);
    }

    public function remove(string $id): bool
    {
        return $this->deleteItem($id);
    }
}
