<?php

/**
 * Simple Null Cache Implementation for Google Client
 * This implements PSR-6 CacheItemPoolInterface but doesn't actually cache anything
 */
class NullCache implements \Psr\Cache\CacheItemPoolInterface
{
    public function getItem(string $key): \Psr\Cache\CacheItemInterface
    {
        return new NullCacheItem($key);
    }

    public function getItems(array $keys = []): iterable
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = new NullCacheItem($key);
        }
        return $items;
    }

    public function hasItem(string $key): bool
    {
        return false; // Never has any items
    }

    public function clear(): bool
    {
        return true; // Always successful since there's nothing to clear
    }

    public function deleteItem(string $key): bool
    {
        return true; // Always successful since there's nothing to delete
    }

    public function deleteItems(array $keys): bool
    {
        return true; // Always successful since there's nothing to delete
    }

    public function save(\Psr\Cache\CacheItemInterface $item): bool
    {
        return true; // Always successful since we don't actually save anything
    }

    public function saveDeferred(\Psr\Cache\CacheItemInterface $item): bool
    {
        return true; // Always successful since we don't actually save anything
    }

    public function commit(): bool
    {
        return true; // Always successful since there's nothing to commit
    }
}

/**
 * Simple Null Cache Item Implementation
 */
class NullCacheItem implements \Psr\Cache\CacheItemInterface
{
    private $key;
    private $value = null;
    private $isHit = false;

    public function __construct(string $key)
    {
        $this->key = $key;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    public function set($value): static
    {
        $this->value = $value;
        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        return $this;
    }

    public function expiresAfter(int|\DateInterval|null $time): static
    {
        return $this;
    }
}
