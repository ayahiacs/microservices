<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

class CacheHelper
{
    /**
     * Retrieve a value from cache or store the result of the given callback.
     *
     * This implements a simple cache-aside pattern.
     *
     * @param  int|\DateTimeInterface|null  $ttl
     * @return mixed
     */
    public static function remember(string $key, \Closure $callback, $ttl = null)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Store a value in cache.
     */
    public static function put(string $key, $value, $ttl = null): void
    {
        Cache::put($key, $value, $ttl);
    }

    /**
     * Remove a key from cache.
     */
    public static function forget(string $key): void
    {
        Cache::forget($key);
    }
}
