<?php

namespace Makis83\LaravelBundle\Traits\models;

use Closure;
use JsonException;
use Makis83\Helpers\Data;
use Makis83\Helpers\Text;
use Illuminate\Cache\TaggableStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for caching Eloquent model data.
 * This trait should be used with Illuminate\Database\Eloquent\Model classes.
 * Created by PhpStorm.
 * User: max
 * Date: 2024-09-18
 * Time: 19:16
 *
 * @method static saved(Closure $callback)
 * @method static deleted(Closure $callback)
 * @method static softDeleted(Closure $callback)
 * @method static restored(Closure $callback)
 * @method static forceDeleted(Closure $callback)
 */
trait UsesCache
{
    /**
     * @var array<string, mixed> $simpleCacheData Array for storing cached data during runtime
     */
    private static array $simpleCacheData = [];


    /**
     * Boots the trait.
     * This method is called automatically when the model that uses the trait is instantiated.
     *
     * @return void
     */
    public static function bootUsesCache(): void
    {
        // Ensure the trait is applied to a model
        if (!is_subclass_of(static::class, Model::class)) {
            return;
        }

        // Flush cache on model save
        static::saved(static function (): void {
            static::cacheFlush();
        });

        // Flush cache on model delete
        static::deleted(static function (): void {
            static::cacheFlush();
        });

        // Flush cache on model soft delete
        if (method_exists(static::class, 'softDeleted')) {
            static::softDeleted(static function () {
                static::cacheFlush();
            });
        }

        // Flush cache on model restore
        if (method_exists(static::class, 'restored')) {
            static::restored(static function () {
                static::cacheFlush();
            });
        }

        // Flush cache on model force delete
        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(static function () {
                static::cacheFlush();
            });
        }
    }


    /**
     * Caches data (during runtime).
     *
     * @param string $key Key name
     * @param mixed $data Data to be cached
     * @return bool Whether the data were successfully cached
     */
    public static function simpleCacheAdd(string $key, mixed $data): bool
    {
        // Validate key
        if ('' === trim($key)) {
            return false;
        }

        // Cache data
        static::$simpleCacheData[$key] = $data;
        return true;
    }


    /**
     * Returns previously cached data.
     *
     * @param string $key Key name
     * @return mixed Cached data (false if no data is cached)
     */
    public static function simpleCacheGet(string $key): mixed
    {
        // Validate key
        if ('' === trim($key)) {
            return false;
        }

        // Detect if array element exists
        if (array_key_exists($key, static::$simpleCacheData)) {
            return static::$simpleCacheData[$key];
        }

        // Default value
        return false;
    }


    /**
     * Removes the cached data.
     *
     * @param string $key Key name
     * @return boolean Whether the cached data were successfully removed
     */
    public static function simpleCacheRemove(string $key): bool
    {
        // Validate key
        if (!array_key_exists($key, static::$simpleCacheData)) {
            return false;
        }

        // Remove the data
        unset(static::$simpleCacheData[$key]);

        // Result
        return true;
    }


    /**
     * Completely removes all cached data.
     *
     * @return void
     */
    public static function simpleCacheFlush(): void
    {
        static::$simpleCacheData = [];
    }


    /**
     * Returns cache store name.
     *
     * @return null|string Cache store name (null if not set)
     */
    public static function cacheStore(): null|string
    {
        // Try to get data from class property
        if (property_exists(static::class, 'cacheStore')) {
            return static::$cacheStore;
        }

        // Default cache store
        return Config::get('cache.store', 'redis');
    }


    /**
     * Returns array of class cache tags.
     *
     * @return string[] Array of cache tags
     */
    public static function cacheTags(): array
    {
        // Try to get data from class property
        if (property_exists(static::class, 'cacheTags') && is_array(static::$cacheTags)) {
            $tags = static::$cacheTags;
            return [...$tags, static::cacheTagDefault()];
        }

        // Default cache tag
        return [static::cacheTagDefault()];
    }


    /**
     * Returns default cache tag name.
     *
     * @return string Default cache tag
     */
    public static function cacheTagDefault(): string
    {
        return Text::classNameToId(static::class) . '-cache-default';
    }


    /**
     * Flushes model cache.
     *
     * @return bool Whether the class cache was flushed
     */
    public static function cacheFlush(): bool
    {
        // Get cache store
        $store = Cache::store(static::cacheStore());

        // Flush tags cache if the store supports tags
        if ($store instanceof TaggableStore) {
            return $store->tags(static::cacheTags())->flush();
        }

        // Flush the whole store cache
        return $store->getStore()->flush();
    }


    /**
     * Returns a unique hash value for the given data.
     *
     * @param mixed $data Data to be hashed
     * @param bool $useHashing Whether to use hashing for less key length
     * @return string Unique hash value
     */
    public static function cacheKey(mixed $data, bool $useHashing = true): string
    {
        try {
            $key = Data::jsonEncode($data);
        } catch (JsonException) {
            $key = serialize($data);
        }

        return $useHashing ? md5($key) : $key;
    }
}
